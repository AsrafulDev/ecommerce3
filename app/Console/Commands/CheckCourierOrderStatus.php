<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Courierapi;
use App\Models\CronJobSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\RedXService;

class CheckCourierOrderStatus extends Command
{
    protected $signature = 'courier:check-status {--limit=50 : Maximum orders to check} {--force : Run even if disabled in admin}';

    protected $description = 'Check order status from Pathao, Steadfast, and RedX courier APIs and update order status automatically';

    public function handle()
    {
        $setting = CronJobSetting::forKey('courier_status_sync');

        // Respect admin enable/disable unless --force passed
        if ($setting && !$setting->is_enabled && !$this->option('force')) {
            $this->warn("⏸  Courier status sync is DISABLED from admin panel. Use --force to override.");
            return Command::SUCCESS;
        }

        // Limit: option overrides DB, DB overrides default 50
        $limit = (int) $this->option('limit');
        if ($limit === 50 && $setting && $setting->order_limit > 0) {
            $limit = $setting->order_limit;
        }

        // Mark as running
        if ($setting) {
            $setting->update([
                'last_run_at'     => now(),
                'last_run_status' => 'running',
                'last_run_result' => 'চলছে...',
            ]);
        }

        $this->info("Courier status sync started — limit: {$limit}");

        $orders = Order::where('order_status', 5)
            ->whereNotNull('courier_type')
            ->whereNotNull('courier_tracking_id')
            ->whereIn('courier_type', ['pathao', 'steadfast', 'redx'])
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info("No pending orders found.");
            if ($setting) {
                $setting->update([
                    'last_run_status'    => 'success',
                    'last_run_result'    => 'চেক করার মতো কোনো অর্ডার নেই।',
                    'last_updated_count' => 0,
                    'last_failed_count'  => 0,
                ]);
            }
            return Command::SUCCESS;
        }

        $this->info("Found {$orders->count()} orders to check");

        $updated   = 0;
        $failed    = 0;
        $unchanged = 0;

        foreach ($orders as $order) {
            try {
                $status = $this->checkOrderStatus($order);

                if ($status === null) {
                    $unchanged++;
                    continue;
                }

                $oldStatus = $order->order_status;
                $order->order_status = $status;
                $order->save();

                if ($status == 11) {
                    \App\Helpers\ResellerOrderHelper::deductDeliveryChargeOnCancel($order);
                }

                $updated++;
                $this->info("Order #{$order->invoice_id} ({$order->courier_type}): {$oldStatus} → {$status}");

                Log::info("Courier order status auto-updated", [
                    'order_id'     => $order->id,
                    'invoice_id'   => $order->invoice_id,
                    'courier_type' => $order->courier_type,
                    'tracking_id'  => $order->courier_tracking_id,
                    'old_status'   => $oldStatus,
                    'new_status'   => $status,
                ]);

            } catch (\Exception $e) {
                $failed++;
                $this->error("Error #{$order->invoice_id}: " . $e->getMessage());

                Log::error("Courier status check failed", [
                    'order_id'     => $order->id,
                    'invoice_id'   => $order->invoice_id,
                    'courier_type' => $order->courier_type,
                    'error'        => $e->getMessage(),
                ]);
            }
        }

        $resultText = "মোট চেক: {$orders->count()} | আপডেট: {$updated} | অপরিবর্তিত: {$unchanged} | ব্যর্থ: {$failed}";
        $this->info($resultText);

        if ($setting) {
            $setting->update([
                'last_run_status'    => $failed > 0 && $updated === 0 ? 'failed' : 'success',
                'last_run_result'    => $resultText,
                'last_updated_count' => $updated,
                'last_failed_count'  => $failed,
            ]);
        }

        return Command::SUCCESS;
    }

    /**
     * Check order status from courier API
     *
     * @param Order $order
     * @return int|null Returns new order_status or null if no change needed
     */
    private function checkOrderStatus(Order $order)
    {
        if ($order->courier_type === 'pathao') {
            return $this->checkPathaoStatus($order);
        } elseif ($order->courier_type === 'steadfast') {
            return $this->checkSteadfastStatus($order);
        } elseif ($order->courier_type === 'redx') {
            return $this->checkRedXStatus($order);
        }

        return null;
    }

    /**
     * Check Pathao order status
     *
     * @param Order $order
     * @return int|null
     */
    private function checkPathaoStatus(Order $order)
    {
        $pathao_info = Courierapi::where(['status' => 1, 'type' => 'pathao'])->first();

        if (!$pathao_info || empty($pathao_info->token)) {
            Log::warning("Pathao not configured or token missing");
            return null;
        }

        $consignmentId = $order->courier_tracking_id ?? $order->consignment_id;
        
        if (empty($consignmentId)) {
            Log::warning("Pathao consignment_id missing for order", ['order_id' => $order->id]);
            return null;
        }

        try {
            // Clean up URL
            $baseUrl = rtrim($pathao_info->url ?? 'https://api-hermes.pathao.com', '/');
            $baseUrl = preg_replace('#/aladdin/?$#', '', $baseUrl);

            // Pathao API: Get Order Info
            // Endpoint: /aladdin/api/v1/orders/{consignment_id}/info
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $pathao_info->token,
                'Accept' => 'application/json',
            ])->get($baseUrl . '/aladdin/api/v1/orders/' . $consignmentId . '/info');

            if (!$response->successful()) {
                // Token might be expired, try to refresh
                if ($response->status() === 401 && !empty($pathao_info->client_id) && !empty($pathao_info->client_secret)) {
                    Log::info("Pathao token expired, attempting refresh", ['order_id' => $order->id]);
                    
                    try {
                        $tokenResponse = $this->refreshPathaoToken($pathao_info);
                        if ($tokenResponse && isset($tokenResponse['access_token'])) {
                            $pathao_info->token = $tokenResponse['access_token'];
                            $pathao_info->save();
                            
                            // Retry the request with new token
                            $response = Http::withHeaders([
                                'Authorization' => 'Bearer ' . $pathao_info->token,
                                'Accept' => 'application/json',
                            ])->get($baseUrl . '/aladdin/api/v1/orders/' . $consignmentId . '/info');
                            
                            if (!$response->successful()) {
                                return null;
                            }
                        } else {
                            return null;
                        }
                    } catch (\Exception $e) {
                        Log::error("Pathao token refresh failed", ['error' => $e->getMessage()]);
                        return null;
                    }
                } else {
                    return null;
                }
            }

            $data = $response->json();
            
            if (!isset($data['data']['order_status_slug'])) {
                return null;
            }

            $pathaoStatus = strtolower($data['data']['order_status_slug'] ?? '');

            // Map Pathao status to our order status
            // Pathao statuses: Pending, Delivered, Cancelled, etc.
            if (in_array($pathaoStatus, ['delivered', 'completed'])) {
                return 6; // Completed/Delivered
            } elseif (in_array($pathaoStatus, ['cancelled', 'canceled'])) {
                return 11; // Cancelled
            }

            return null; // No status change needed

        } catch (\Exception $e) {
            Log::error("Pathao status check error", [
                'order_id' => $order->id,
                'consignment_id' => $consignmentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check Steadfast order status
     *
     * @param Order $order
     * @return int|null
     */
    private function checkSteadfastStatus(Order $order)
    {
        $steadfast_info = Courierapi::where(['status' => 1, 'type' => 'steadfast'])->first();

        if (!$steadfast_info || empty($steadfast_info->api_key) || empty($steadfast_info->secret_key)) {
            Log::warning("Steadfast not configured");
            return null;
        }

        // Steadfast supports checking by consignment_id, invoice, or tracking_code
        $consignmentId = $order->courier_tracking_id ?? $order->consignment_id;
        $invoiceId = $order->invoice_id;

        try {
            // Clean up URL
            $baseUrl = rtrim($steadfast_info->url ?? 'https://portal.packzy.com/api/v1', '/');

            // Try consignment_id first, then invoice
            $endpoint = null;
            $identifier = null;

            if (!empty($consignmentId)) {
                $endpoint = '/status_by_cid/' . $consignmentId;
                $identifier = $consignmentId;
            } elseif (!empty($invoiceId)) {
                $endpoint = '/status_by_invoice/' . $invoiceId;
                $identifier = $invoiceId;
            } else {
                Log::warning("Steadfast: No tracking ID or invoice ID found", ['order_id' => $order->id]);
                return null;
            }

            $response = Http::withHeaders([
                'Api-Key' => $steadfast_info->api_key,
                'Secret-Key' => $steadfast_info->secret_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->get($baseUrl . $endpoint);

            if (!$response->successful()) {
                Log::warning("Steadfast API error", [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            
            if (!isset($data['delivery_status'])) {
                return null;
            }

            $steadfastStatus = strtolower($data['delivery_status'] ?? '');

            // Map Steadfast status to our order status
            // Steadfast statuses: delivered, cancelled, partial_delivered, etc.
            if (in_array($steadfastStatus, ['delivered', 'delivered_approval_pending'])) {
                return 6; // Completed/Delivered
            } elseif (in_array($steadfastStatus, ['cancelled', 'cancelled_approval_pending'])) {
                return 11; // Cancelled
            }

            return null; // No status change needed

        } catch (\Exception $e) {
            Log::error("Steadfast status check error", [
                'order_id' => $order->id,
                'consignment_id' => $consignmentId,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Refresh Pathao access token using username/password
     *
     * @param Courierapi $pathao_info
     * @return array|null
     */
    private function refreshPathaoToken(Courierapi $pathao_info)
    {
        try {
            $baseUrl = rtrim($pathao_info->url ?? 'https://api-hermes.pathao.com', '/');
            $baseUrl = preg_replace('#/aladdin/?$#', '', $baseUrl);

            // Generate new token with username/password
            if (!empty($pathao_info->username) && !empty($pathao_info->password)) {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->post($baseUrl . '/aladdin/api/v1/issue-token', [
                    'client_id' => $pathao_info->client_id,
                    'client_secret' => $pathao_info->client_secret,
                    'grant_type' => 'password',
                    'username' => $pathao_info->username,
                    'password' => $pathao_info->password,
                ]);

                if ($response->successful()) {
                    return $response->json();
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Pathao token refresh error", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check RedX order status
     *
     * @param Order $order
     * @return int|null
     */
    private function checkRedXStatus(Order $order)
    {
        $redx_info = Courierapi::where(['status' => 1, 'type' => 'redx'])->first();

        if (!$redx_info || empty($redx_info->token)) {
            Log::warning("RedX not configured or token missing");
            return null;
        }

        $trackingId = $order->courier_tracking_id ?? $order->consignment_id;
        
        if (empty($trackingId)) {
            Log::warning("RedX tracking_id missing for order", ['order_id' => $order->id]);
            return null;
        }

        try {
            $redxService = new RedXService();
            $parcelDetails = $redxService->getParcelDetails($trackingId);
            
            if (!$parcelDetails || !isset($parcelDetails['parcel']['status'])) {
                return null;
            }

            $redxStatus = strtolower($parcelDetails['parcel']['status'] ?? '');
            
            // Map RedX status to our order status
            $orderStatus = $redxService->mapStatusToOrderStatus($redxStatus);
            
            return $orderStatus; // Returns null if no mapping found

        } catch (\Exception $e) {
            Log::error("RedX status check error", [
                'order_id' => $order->id,
                'tracking_id' => $trackingId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
