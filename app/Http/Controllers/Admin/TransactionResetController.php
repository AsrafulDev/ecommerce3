<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\TransactionReset\SaleResetService;
use App\Services\TransactionReset\SaleReversalEligibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The hidden reset page: sales can be cancelled (the default, with everything linked reversed) or completely
 * deleted (only when nothing else refers to them). Not linked from the menu. Every action needs the current
 * admin password, and every change is written to the activity log.
 */
class TransactionResetController extends Controller
{
    public function __construct(private SaleResetService $sales, private SaleReversalEligibility $eligibility)
    {
        $this->middleware('permission:purge-financial-transactions');
    }

    public function index()
    {
        $orders = Order::query()
            ->with('shipping')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (Order $order) => [
                'order' => $order,
                'buyer' => optional($order->shipping)->name,
                'reverse' => $this->eligibility->inspect($order),
                'blockers' => $this->sales->completeDeleteBlockers($order),
            ]);

        return view('backEnd.transaction-reset.index', compact('orders'));
    }

    /** Cancel or completely delete one sale, or several at once. */
    public function act(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:cancel,delete',
            'order_ids' => 'required|array|min:1|max:200',
            'order_ids.*' => 'integer',
            'reason' => 'required|string|min:10|max:500',
            'password' => 'required|string',
            'confirmation' => 'required_if:action,delete|nullable|string',
        ]);

        $actor = $request->user('admin');
        $key = 'sale-reset:'.$actor->getAuthIdentifier().'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['password' => 'Too many attempts. Try again in a minute.']);
        }

        if (! Hash::check($validated['password'], $actor->password)) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['password' => 'Current password is incorrect.'])->withInput($request->except('password', 'confirmation'));
        }

        if ($validated['action'] === 'delete' && trim((string) $validated['confirmation']) !== 'DELETE') {
            return back()->withErrors(['confirmation' => 'Type DELETE to confirm a complete delete.'])->withInput($request->except('password', 'confirmation'));
        }

        RateLimiter::clear($key);

        $done = [];
        $failed = [];
        $outcomes = [];
        foreach (array_unique($validated['order_ids']) as $id) {
            $order = Order::find($id);
            if (! $order) {
                $failed[] = "#{$id}: not found";

                continue;
            }

            try {
                if ($validated['action'] === 'cancel') {
                    $result = $this->sales->reverse($order, $actor, $validated['reason'], $request->ip());
                    $outcomes[] = ($order->invoice_id ?: '#'.$id).': accounts '.$result['accounting']['status'];
                } else {
                    $this->sales->completeDelete($order, $actor, $validated['reason'], $request->ip());
                }
                $done[] = $order->invoice_id ?: '#'.$id;
            } catch (\DomainException $e) {
                $failed[] = ($order->invoice_id ?: '#'.$id).': '.$e->getMessage();
            }
        }

        $verb = $validated['action'] === 'cancel' ? 'reversed' : 'deleted';
        $message = count($done).' sale(s) '.$verb.'.'.($outcomes !== [] ? ' '.implode('; ', $outcomes).'.' : '');

        if ($failed !== []) {
            return back()->with('success', $message)->withErrors(['reset' => $failed]);
        }

        return back()->with('success', $message);
    }
}
