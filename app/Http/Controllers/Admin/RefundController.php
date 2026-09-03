<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\FundTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Brian2694\Toastr\Facades\Toastr;

class RefundController extends Controller
{
    /**
     * Display all refunds
     */
    public function index(Request $request)
    {
        $query = Refund::with(['order', 'customer', 'processedBy']);

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by order invoice
        if ($request->has('order_invoice') && $request->order_invoice != '') {
            $query->whereHas('order', function($q) use ($request) {
                $q->where('invoice_id', 'like', '%' . $request->order_invoice . '%');
            });
        }

        $data = $query->latest()->paginate(15);
        
        $statuses = ['pending', 'approved', 'rejected', 'processed'];
        
        return view('backEnd.refunds.index', compact('data', 'statuses'));
    }

    /**
     * Show refund details
     */
    public function show($id)
    {
        $refund = Refund::with(['order.orderdetails.product', 'customer', 'processedBy'])
            ->findOrFail($id);
        
        return view('backEnd.refunds.show', compact('refund'));
    }

    /**
     * Approve refund request (supports partial refund)
     */
    public function approve(Request $request, $id)
    {
        $refund = Refund::with(['order', 'customer'])->findOrFail($id);

        if ($refund->status !== 'pending') {
            Toastr::error('This refund has already been processed.', 'Error');
            return back();
        }

        $request->validate([
            'admin_note'       => 'nullable|string|max:2000',
            'customer_note'     => 'nullable|string|max:2000',
            'refund_amount'    => 'nullable|numeric|min:0',
            'include_shipping' => 'nullable|boolean',
        ]);

        // Determine the actual refund amount
        $includeShipping = $request->boolean('include_shipping', true);
        $customAmount = $request->filled('refund_amount') ? (float) $request->refund_amount : null;

        if ($customAmount !== null) {
            $totalRefundAmount = $customAmount;
        } else {
            $totalRefundAmount = (float) $refund->amount;
            if ($includeShipping) {
                $totalRefundAmount += (float) $refund->shipping_charge;
            }
        }

        // Check admin fund balance
        $adminFundBalance = \App\Helpers\FundHelper::balance();
        if ($adminFundBalance < $totalRefundAmount) {
            Toastr::error('Insufficient fund balance. Current balance: ৳' . number_format($adminFundBalance, 2), 'Error');
            return back();
        }

        DB::transaction(function () use ($refund, $request, $totalRefundAmount, $customAmount, $includeShipping) {
            $refund->status = 'approved';
            $refund->admin_note = $request->admin_note;
            $refund->customer_note = $request->customer_note;
            $refund->processed_by = Auth::id();

            // Save partial refund details
            if ($customAmount !== null) {
                $refund->refund_amount = $customAmount;
            }
            $refund->include_shipping = $includeShipping;
            $refund->save();

            // Deduct from admin fund
            FundTransaction::create([
                'direction'  => 'out',
                'source'     => 'refund',
                'source_id'  => $refund->id,
                'amount'     => $totalRefundAmount,
                'note'       => 'Refund approved for Order #' . $refund->order->invoice_id . ' - Refund ID: ' . $refund->refund_id . ($customAmount !== null ? ' (Partial: ৳' . number_format($customAmount, 2) . ')' : ''),
                'created_by' => Auth::id(),
            ]);
        });

        $msg = $customAmount !== null
            ? 'Partial refund of ৳' . number_format($totalRefundAmount, 2) . ' approved successfully.'
            : 'Refund approved successfully.';

        Toastr::success($msg, 'Success');
        return back();
    }

    /**
     * Reject refund request
     */
    public function reject(Request $request, $id)
    {
        $refund = Refund::findOrFail($id);

        if ($refund->status !== 'pending' && $refund->status !== 'approved') {
            Toastr::error('This refund has already been processed.', 'Error');
            return back();
        }

        $request->validate([
            'admin_note'    => 'required|string|max:2000',
            'customer_note' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($refund, $request) {
            $actualAmount = $refund->totalRefundAmount();

            // If refund was already approved, reverse the fund transaction
            if ($refund->status === 'approved') {
                // Find and delete the fund transaction
                $fundTransaction = FundTransaction::where('source', 'refund')
                    ->where('source_id', $refund->id)
                    ->where('direction', 'out')
                    ->first();

                if ($fundTransaction) {
                    // Reverse the transaction by creating an 'in' transaction
                    FundTransaction::create([
                        'direction'  => 'in',
                        'source'     => 'refund_reversal',
                        'source_id'  => $refund->id,
                        'amount'     => $actualAmount,
                        'note'       => 'Refund rejected - Reversal for Order #' . $refund->order->invoice_id . ' - Refund ID: ' . $refund->refund_id,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            $refund->status = 'rejected';
            $refund->admin_note = $request->admin_note;
            $refund->customer_note = $request->customer_note;
            $refund->processed_by = Auth::id();
            $refund->processed_at = now();
            $refund->save();
        });

        Toastr::success('Refund request rejected.', 'Success');
        return back();
    }

    /**
     * Process refund (mark as processed after payment)
     */
    public function process(Request $request, $id)
    {
        $refund = Refund::with(['order'])->findOrFail($id);

        if ($refund->status !== 'approved') {
            Toastr::error('Only approved refunds can be processed.', 'Error');
            return back();
        }

        $request->validate([
            'transaction_id'     => 'required|string|max:255',
            'refund_method'      => 'required|in:original_payment,bkash,nagad,bank,manual',
            'refund_account'     => 'required|string|max:255',
            'refund_account_name'=> 'nullable|string|max:255',
            'admin_note'         => 'nullable|string|max:2000',
            'customer_note'      => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($refund, $request) {
            $refund->status = 'processed';
            $refund->transaction_id = $request->transaction_id;
            $refund->refund_method = $request->refund_method;
            $refund->refund_account = $request->refund_account;
            $refund->refund_account_name = $request->refund_account_name;
            $refund->processed_at = now();

            // Append admin note if provided during processing
            if ($request->filled('admin_note')) {
                $existingNote = $refund->admin_note ?? '';
                $refund->admin_note = $existingNote
                    ? $existingNote . "\n\n[Processed]: " . $request->admin_note
                    : '[Processed]: ' . $request->admin_note;
            }

            // Save customer note if provided
            if ($request->filled('customer_note')) {
                $existingCustomerNote = $refund->customer_note ?? '';
                $refund->customer_note = $existingCustomerNote
                    ? $existingCustomerNote . "\n\n[Updated]: " . $request->customer_note
                    : $request->customer_note;
            }

            $refund->save();

            // Phase 2.1: stock restock belongs to the order CANCEL/RETURN path
            // (OrderStatusService::handleStatusChange → stockIn reference_type=sale_return)
            // ONLY — never to a refund. A refund is money-only. Restocking here
            // double-restored cancelled orders AND drifted products.stock (raw +=
            // with no stock_batches row), breaking the batch source-of-truth.
        });

        Toastr::success('Refund processed successfully.', 'Success');
        return back();
    }

    /**
     * Delete refund (only if pending)
     */
    public function destroy($id)
    {
        $refund = Refund::findOrFail($id);

        if ($refund->status !== 'pending') {
            Toastr::error('Only pending refunds can be deleted.', 'Error');
            return back();
        }

        $refund->delete();
        Toastr::success('Refund request deleted successfully.', 'Success');
        return back();
    }
}
