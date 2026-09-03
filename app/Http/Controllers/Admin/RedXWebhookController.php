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
            // Log incoming webhook
            Log::info('RedX Webhook Received', [
                'payload' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Validate required fields
            $trackingNumber = $request->input('tracking_number');
            $status = $request->input('status');
            $invoiceNumber = $request->input('invoice_number');

            if (!$trackingNumber || !$status) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required fields: tracking_number or status'
                ], 400);
            }

            // Find order by tracking_id or invoice_id
            $order = Order::where('courier_tracking_id', $trackingNumber)
                ->orWhere('invoice_id', $invoiceNumber)
                ->first();

            if (!$order) {
                Log::warning('RedX Webhook: Order not found', [
                    'tracking_number' => $trackingNumber,
                    'invoice_number' => $invoiceNumber
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Map RedX status → OrderStatus enum (RedXService, Phase 3.1).
            $redxService = new RedXService();
            $newEnum = $redxService->mapStatusToOrderStatus($status);

            if ($newEnum !== null) {
                $current = $order->order_status;
                $oldEnum = is_numeric($current)
                    ? OrderStatusEnum::fromLegacyId((int) $current)     // legacy int rows
                    : (OrderStatusEnum::tryFrom($current) ?? OrderStatusEnum::PENDING);

                // Phase 2.3 — enum-driven transition (writes enum value + a system
                // order_note via Order::transitionTo). Never a raw int write.
                // No hardcoded user id (webhook is system-driven; FK-safe).
                $order->transitionTo($newEnum, 'Status updated via RedX webhook');

                // ONE shared stock engine (OrderStatusService) — never a private
                // direct-write copy that drifted products.stock.
                app(\App\Services\OrderStatusService::class)
                    ->handleStatusChange($order, $oldEnum->value, $newEnum->value);

                // Guarded fund credit — one 'in' sale row per order (Phase 4).
                if ($newEnum === OrderStatusEnum::COMPLETED && $oldEnum !== OrderStatusEnum::COMPLETED) {
                    \App\Helpers\FundHelper::creditSale(
                        $order,
                        'Order complete via RedX webhook (#' . $order->invoice_id . ')',
                        1
                    );
                }

                // Send SMS notification if configured
                $this->sendStatusUpdateSMS($order, $newEnum);

                Log::info('RedX Webhook: Order status updated successfully', [
                    'order_id' => $order->id,
                    'invoice_id' => $order->invoice_id,
                    'tracking_id' => $trackingNumber,
                    'old_status' => $oldEnum->value,
                    'new_status' => $newEnum->value,
                    'redx_status' => $status
                ]);
            } else {
                Log::warning('RedX Webhook: Status mapping not found', [
                    'order_id' => $order->id,
                    'redx_status' => $status
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('RedX Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
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
