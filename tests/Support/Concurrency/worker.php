<?php

/*
 * One sales-reset or courier-webhook action, run in its own PHP process so it has its own database connection and
 * transaction. Used only by the concurrency tests. It refuses to run against any database other than the test database.
 *
 * Usage: php worker.php '{"action":"webhook","tracking":"RX-1","payload_status":"returned"}'
 *        php worker.php '{"action":"reset","order_id":1,"admin_id":1,"reason":"..."}'
 *        php worker.php '{"action":"admin-cancel","order_id":1}'
 *        php worker.php '{"action":"challan-issue","order_id":1}'
 */

use App\Models\Order;
use App\Models\User;
use App\Services\TransactionReset\SaleResetService;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

$job = json_decode($argv[1] ?? '{}', true, 512, JSON_THROW_ON_ERROR);

if (config('database.connections.mysql.database') !== 'ecommerce3_test') {
    fwrite(STDERR, 'refusing to run against '.config('database.connections.mysql.database')."\n");
    exit(2);
}

$started = microtime(true);
try {
    if ($job['action'] === 'webhook') {
        $request = Request::create('/api/redx/webhook', 'POST', [
            'tracking_number' => $job['tracking'],
            'status' => $job['payload_status'] ?? 'returned',
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $app->make(HttpKernel::class)->handle($request);
        $out = ['http' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true)];
    } elseif ($job['action'] === 'reset') {
        $order = Order::findOrFail($job['order_id']);
        $admin = User::findOrFail($job['admin_id']);
        $out = ['reset' => app(SaleResetService::class)->reverse($order, $admin, $job['reason'], '127.0.0.1')];
    } elseif ($job['action'] === 'admin-cancel') {
        // The admin page's cancel, called as the page calls it. It runs here so it has its own connection and transaction.
        $request = Request::create('/admin/order/action/cancel', 'POST', [
            'order_id' => $job['order_id'],
            'note' => 'Cancel from the admin page.',
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $app->make(\App\Http\Controllers\Admin\OrderController::class)->cancelOrder($request);
        $out = ['http' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true)];
    } elseif ($job['action'] === 'challan-issue') {
        // One Mushak 6.3 issue, in its own process, with its own connection and transaction.
        try {
            $challan = app(\App\Services\Vat\Challan\ChallanIssuer::class)->issue(Order::findOrFail($job['order_id']), null, '127.0.0.1');
            $out = ['issued' => $challan->challan_no];
        } catch (\App\Services\Vat\Challan\ChallanRefused $e) {
            $out = ['refused' => $e->blockers];
        }
    } else {
        $out = ['error' => 'unknown action'];
    }
} catch (\Throwable $e) {
    $out = ['error' => get_class($e), 'message' => $e->getMessage()];
}

$out['started'] = $started;
$out['finished'] = microtime(true);
echo json_encode($out, JSON_UNESCAPED_UNICODE);
