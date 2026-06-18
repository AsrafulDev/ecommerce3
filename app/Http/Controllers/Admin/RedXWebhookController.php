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

            // Map RedX status to order status
            $redxService = new RedXService();
            $newOrderStatus = $redxService->mapStatusToOrderStatus($status);

            if ($newOrderStatus !== null) {
                $oldStatus = (int) $order->order_status;
                $newOrderStatus = (int) $newOrderStatus;
                
                // Update order status
                $order->order_status = $newOrderStatus;
                $order->save();

                // Handle stock change (same logic as OrderController)
                $this->handleStockChange($order, $oldStatus, $newOrderStatus);

                // If order is delivered/completed (status = 6)
                if ($newOrderStatus == 6 && $oldStatus != 6) {
                    // Add money to fund
                    FundTransaction::create([
                        'direction'  => 'in',
                        'source'     => 'sale',
                        'source_id'  => $order->id,
                        'amount'     => $order->amount,
                        'note'       => 'Order complete via RedX webhook (#' . $order->invoice_id . ')',
                        'created_by' => 1, // System user
                    ]);
                }

                // Send SMS notification if configured
                $this->sendStatusUpdateSMS($order, $newOrderStatus);

                Log::info('RedX Webhook: Order status updated successfully', [
                    'order_id' => $order->id,
                    'invoice_id' => $order->invoice_id,
                    'tracking_id' => $trackingNumber,
                    'old_status' => $oldStatus,
                    'new_status' => $newOrderStatus,
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

    /**
     * Handle stock change when order status changes
     * Same logic as OrderController
     */
    private function handleStockChange(Order $order, int $oldStatus, int $newStatus)
    {
        $activeStatuses = [1, 2, 3, 5, 6, 8];

        // 1) প্রথমবার active status এ ঢুকলে স্টক কমবে
        if (in_array($newStatus, $activeStatuses) && !in_array($oldStatus, $activeStatuses)) {
            $details = OrderDetails::where('order_id', $order->id)
                ->with('product:id,stock')
                ->get();

            foreach ($details as $row) {
                if ($row->product) {
                    $row->product->stock = max(0, $row->product->stock - $row->qty);
                    $row->product->save();
                }
            }
        }

        // 2) cancel (11) হলে, যদি আগেরটা active group এ থাকে -> স্টক রিস্টোর
        if ($newStatus == 11 && in_array($oldStatus, $activeStatuses)) {
            $details = OrderDetails::where('order_id', $order->id)
                ->with('product:id,stock')
                ->get();

            foreach ($details as $row) {
                if ($row->product) {
                    $row->product->stock = $row->product->stock + $row->qty;
                    $row->product->save();
                }
            }
        }
    }

    /**
     * Send SMS notification when order status changes
     */
    private function sendStatusUpdateSMS(Order $order, int $newStatus)
    {
        try {
            $sms_gateway = SmsGateway::where('status', 1)->first();
            $site_setting = GeneralSetting::first();
            $orderStatus = OrderStatus::find($newStatus);

            if ($sms_gateway && $order->customer && $orderStatus) {
                $url  = $sms_gateway->url;
                $data = [
                    "api_key"  => $sms_gateway->api_key,
                    "number"   => $order->customer->phone,
                    "type"     => 'text',
                    "senderid" => $sms_gateway->serderid,
                    "message"  => "Dear {$order->customer->name},\r\n"
                        . "Your order (Order ID: {$order->invoice_id}) status has been updated to: "
                        . "{$orderStatus->name} via RedX Courier.\r\n"
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
