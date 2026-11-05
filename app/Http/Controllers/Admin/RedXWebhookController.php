<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\OrderStatus;
use App\Models\Courierapi;
use App\Models\FundTransaction;
use App\Models\SmsGateway;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Services\RedXService;
use App\Enums\OrderStatus as OrderStatusEnum;
use Illuminate\Support\Facades\Log;

class RedXWebhookController extends Controller
{
    /**
     * Handle RedX webhook callbacks
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request)
    {
        try {
            $trackingNumber = trim((string) $request->input('tracking_number', ''));
            $invoiceNumber = trim((string) $request->input('invoice_number', ''));

            // The payload is not trusted. RedX's webhook authentication is not documented or verified in this codebase,
            // so a callback can only trigger a lookup. The status is read back from RedX's own parcel API, with our
            // authenticated token, and that status is the only one applied. A forged callback cannot set a status.
            if ($trackingNumber === '') {
                return response()->json(['success' => false, 'message' => 'Missing required field: tracking_number'], 400);
            }

            $order = Order::query()
                ->where('courier_type', 'redx')
                ->where('courier_tracking_id', $trackingNumber)
                ->first();

            if (!$order || ($invoiceNumber !== '' && (string) $order->invoice_id !== $invoiceNumber)) {
                Log::warning('RedX Webhook: order not found for the callback', [
                    'tracking_number' => $trackingNumber,
                ]);

                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            $details = (new RedXService())->getParcelDetails($trackingNumber);
            $redxStatus = strtolower((string) data_get($details, 'parcel.status', ''));

            if ($redxStatus === '') {
                // Fail closed: nothing is changed unless RedX confirms the status.
                Log::warning('RedX Webhook: status could not be verified with RedX; nothing changed', [
                    'order_id' => $order->id,
                    'tracking_number' => $trackingNumber,
                ]);

                return response()->json(['success' => false, 'message' => 'Status could not be verified; nothing changed'], 503);
            }

            $newEnum = (new RedXService())->mapStatusToOrderStatus($redxStatus);
            if ($newEnum === null) {
                Log::warning('RedX Webhook: no status mapping for the verified RedX status', [
                    'order_id' => $order->id,
                    'redx_status' => $redxStatus,
                ]);

                return response()->json(['success' => true, 'message' => 'No status change'], 200);
            }

            // Locked, re-read and applied in one transaction with its stock, serial and cash effects. A repeated
            // delivery is a no-op, and a change the order's current status does not allow (for example after a reset)
            // is refused, never forced.
            $result = app(\App\Services\OrderStatusService::class)->changeStatusLocked(
                (int) $order->id,
                $newEnum,
                'Status updated via RedX webhook (verified with RedX)',
                null,
                function (Order $locked) use ($newEnum) {
                    // Guarded: one sale credit per order, however many times the webhook arrives.
                    if ($newEnum === OrderStatusEnum::COMPLETED) {
                        \App\Helpers\FundHelper::creditSale(
                            $locked,
                            'Order complete via RedX webhook (#'.$locked->invoice_id.')',
                            1
                        );
                    }
                }
            );

            if ($result === 'applied') {
                $this->sendStatusUpdateSMS($order->fresh(), $newEnum);
            }

            Log::info('RedX Webhook: status change '.$result, [
                'order_id' => $order->id,
                'invoice_id' => $order->invoice_id,
                'tracking_id' => $trackingNumber,
                'redx_status' => $redxStatus,
                'new_status' => $newEnum->value,
            ]);

            return response()->json(['success' => true, 'message' => 'Webhook processed', 'result' => $result], 200);
        } catch (\Exception $e) {
            Log::error('RedX Webhook Error', [
                'error' => $e->getMessage(),
                'tracking_number' => (string) $request->input('tracking_number', ''),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    // NOTE (Phase 2.3/3.2): the private duplicate stock engine was REMOVED — stock
    // is handled by the ONE shared engine App\Services\OrderStatusService::
    // handleStatusChange (batch-tracked stockOut/stockIn + sale_return restock).

    /**
     * Send SMS notification when order status changes
     */
    private function sendStatusUpdateSMS(Order $order, OrderStatusEnum $newStatus)
    {
        try {
            $sms_gateway = SmsGateway::where('status', 1)->first();
            $site_setting = GeneralSetting::first();

            if ($sms_gateway && $order->customer) {
                $url  = $sms_gateway->url;
                $data = [
                    "api_key"  => $sms_gateway->api_key,
                    "number"   => $order->customer->phone,
                    "type"     => 'text',
                    "senderid" => $sms_gateway->serderid,
                    "message"  => "Dear {$order->customer->name},\r\n"
                        . "Your order (Order ID: {$order->invoice_id}) status has been updated to: "
                        . "{$newStatus->label()} via RedX Courier.\r\n"
                        . "Thank you for using {$site_setting->name}!",
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_exec($ch);
                curl_close($ch);
            }
        } catch (\Exception $e) {
            Log::error('RedX Webhook SMS sending failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
