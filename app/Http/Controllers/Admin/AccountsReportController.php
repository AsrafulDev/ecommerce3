<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionCategory;
use App\Helpers\FundHelper;
use App\Http\Controllers\Controller;
use App\Services\CogsCalculator;
use App\Models\Customer;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\StockAdjustment;
use App\Models\FixedAsset;
use App\Models\Loan;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\SupplierPayment;
use App\Models\StockBatch;
use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Read-only Finance & Reports pages (Balance and Ledger sections).
 *
 * Column layouts follow the reference ERP (see erp-finance-reports-pages.md):
 * balances are Opening + Debit − Credit, ledgers carry Code and Type. Every
 * figure is worked out from the existing funds ledger, orders, purchases and
 * expenses. Nothing here writes money. Reports whose data this system does not
 * hold are listed as "not built yet" rather than shown as empty tables.
 */
class AccountsReportController extends Controller
{
    private const NON_RECEIVABLE_STATUSES = ['cancelled', 'returned', 'return_approved', 'closed'];

    /**
     * Report registry. Entry: [title, status, description, route?].
     * status: available = built here · link = existing page · todo = not built yet.
     */
    public const REPORTS = [
        'balance' => [
            'cash-bank-balance'   => ['Cash Bank Balance', 'available', 'Cash in hand from the funds ledger, as at the date chosen.'],
            'customer-balance'    => ['Customer Balance', 'available', 'Sales, receipts and balance per customer.'],
            'supplier-balance'    => ['Supplier Balance', 'available', 'Purchases, payments and balance per supplier.'],
            'capital-balance'     => ['Capital Balance', 'available', 'Owner capital put in, less owner withdrawals.'],
            'general-expense-balance' => ['General Expense Balance', 'available', 'Expenses grouped by category.'],
            'daily-statement'     => ['Daily Statement', 'available', 'Every cash movement in the period, in a section per type.'],
            'net-profit-loss'     => ['Net Profit Loss', 'link', 'Existing profit & loss report.', 'admin.reports.profit_loss'],
            'customer-list'       => ['Customer List', 'link', 'Existing customer accounts list.', 'admin.accounts.customers'],
            'chart-of-accounts'   => ['Chart of Accounts', 'link', 'Accounts nested under their parent (advanced accounting).', 'admin.accounting.chart.index'],
            'balance-sheet'       => ['Balance Sheet', 'available', 'Cash, receivables and stock against supplier and owner balances, with the unexplained difference shown.'],
            'employee-loan-balance'           => ['Employee Loan Balance', 'available', 'Employee loans outstanding, per employee.'],
            'employee-expense-advance-balance' => ['Employee Expense Advance Balance', 'available', 'Expense advances given to employees, with the amount still to be settled.'],
            'employee-expense-balance'        => ['Employee Expense Balance', 'todo', 'Expenses are not linked to employees yet.'],
            'fixed-asset-balance'             => ['Fixed Asset Balance', 'available', 'Each fixed asset with its cost, depreciation and book value.'],
            'general-loan-balance'            => ['General Loan Balance', 'available', 'Loans taken and given to parties, with the amount still outstanding.'],
            'challan-report'                  => ['Challan Report', 'todo', 'Challans are not modelled in the reports yet.'],
            'product-profit-loss'             => ['Product Profit Loss', 'available', 'Sales, cost and profit per product for delivered orders.'],
            'party-profit-loss'               => ['Party Profit Loss', 'available', 'Sales, cost, dues and profit per customer for delivered orders.'],
        ],
        'ledger' => [
            'cash-bank-ledger'  => ['Cash Bank Ledger', 'available', 'Every cash movement with a running balance.'],
            'daily-ledger'      => ['Daily Ledger', 'available', 'Same as the cash ledger, for the dates chosen.'],
            'capital-ledger'    => ['Capital Ledger', 'available', 'Owner capital and withdrawals with a running balance.'],
            'general-expense-ledger' => ['General Expense Ledger', 'available', 'Each expense in the period with a running total.'],
            'customer-ledger'   => ['Customer Ledger', 'link', 'Existing per-customer ledger.', 'admin.accounts.customers'],
            'supplier-ledger'   => ['Supplier Ledger', 'link', 'Existing per-supplier ledger.', 'admin.accounts.suppliers'],
            'product-ledger'    => ['Product Ledger', 'available', 'Stock movements of one product: purchases, sales and adjustments, with a running quantity.'],
            'employee-expense-advance-ledger' => ['Employee Expense Advance Ledger', 'available', 'Each expense advance and settlement with the running balance.'],
            'employee-loan-ledger'            => ['Employee Loan Ledger', 'available', 'Each employee loan and repayment with the running outstanding.'],
            'employee-expense-ledger'         => ['Employee Expense Ledger', 'todo', 'Expenses are not linked to employees yet.'],
            'employee-salary-advance-ledger'  => ['Employee Salary Advance Ledger', 'available', 'Each salary advance and its settlement with the running balance.'],
            'fixed-asset-ledger'              => ['Fixed Asset Ledger', 'available', 'Purchases, depreciation, revaluations and disposals of fixed assets.'],
            'general-loan-ledger'             => ['General Loan Ledger', 'available', 'Each loan and repayment with the running outstanding.'],
        ],
        'record' => [
            'expense-record' => ['Expense Record', 'available', 'Every expense in the period, with its category.'],
            'receipt-record' => ['Receipt Record', 'available', 'Customer payments received against orders in the period.'],
            'payment-record' => ['Payment Record', 'available', 'Payments made to suppliers against purchases in the period.'],
        ],
    ];

    public function __construct()
    {
        $this->middleware('permission:fund-list|fund-create|fund-edit');
    }

    public function index()
    {
        return view('backEnd.accounts.reports.index', ['reports' => self::REPORTS]);
    }

    public function show(Request $request, string $report)
    {
        $entry = $this->find($report);

        if (!$entry) {
            abort(404);
        }

        [, , $title, $status, $description, $routeName] = $entry;

        if ($status === 'link') {
            return redirect()->route($routeName);
        }

        if ($status === 'todo') {
            return view('backEnd.accounts.reports.show', [
                'title' => $title, 'description' => $description, 'todo' => true,
                'columns' => [], 'rows' => [], 'totals' => [], 'sections' => [], 'filters' => [], 'note' => null,
            ]);
        }

        $data = $this->build($report, $request);

        return view('backEnd.accounts.reports.show', $data + [
            'title' => $title,
            'description' => $description,
            'todo' => false,
            'sections' => [],
            'filters' => $this->filters($request),
            'products' => $report === 'product-ledger'
                ? Product::orderBy('name')->get(['id', 'name'])
                : collect(),
            'productId' => (int) $request->input('product_id', 0),
        ]);
    }

    /** @return array{0: string, 1: string, 2: string, 3: string, 4: string, 5: ?string}|null */
    protected function find(string $slug): ?array
    {
        foreach (self::REPORTS as $group => $reports) {
            if (isset($reports[$slug])) {
                $r = $reports[$slug];

                return [$group, $slug, $r[0], $r[1], $r[2], $r[3] ?? null];
            }
        }

        return null;
    }

    /**
     * Build the table for one available report.
     *
     * @return array{columns: array<int, array{0: string, 1: string, 2: string}>, rows: array<int, array<string, mixed>>, totals: array<string, mixed>, note: ?string, sections?: array<int, array<string, mixed>>}
     */
    protected function build(string $slug, Request $request): array
    {
        [$from, $to] = $this->window($request);
        $search = trim((string) $request->input('search', ''));

        return match ($slug) {
            'cash-bank-balance'       => $this->cashBankBalance($to),
            'customer-balance'        => $this->customerBalance($search),
            'general-loan-balance'    => $this->loanBalance(Loan::KIND_GENERAL),
            'employee-loan-balance'   => $this->loanBalance(Loan::KIND_EMPLOYEE),
            'general-loan-ledger'     => $this->loanLedger(Loan::KIND_GENERAL, $from, $to),
            'employee-loan-ledger'    => $this->loanLedger(Loan::KIND_EMPLOYEE, $from, $to),
            'employee-expense-advance-balance' => $this->loanBalance(Loan::KIND_EXPENSE_ADVANCE),
            'employee-expense-advance-ledger'  => $this->loanLedger(Loan::KIND_EXPENSE_ADVANCE, $from, $to),
            'employee-salary-advance-ledger'   => $this->loanLedger(Loan::KIND_SALARY_ADVANCE, $from, $to),
            'fixed-asset-balance'     => $this->fixedAssetBalance($to),
            'fixed-asset-ledger'      => $this->fixedAssetLedger($from, $to),
            'balance-sheet'           => $this->balanceSheet($to),
            'product-profit-loss'     => $this->productProfitLoss($from, $to),
            'party-profit-loss'       => $this->partyProfitLoss($from, $to),
            'supplier-balance'        => $this->supplierBalance($search),
            'capital-balance'         => $this->capitalBalance($to),
            'general-expense-balance' => $this->expenseByCategory($from, $to),
            'daily-statement'         => $this->dailyStatement($from, $to),
            'cash-bank-ledger', 'daily-ledger' => $this->cashLedger($from, $to, null),
            'product-ledger'          => $this->productLedger($request, $from, $to),
            'capital-ledger'          => $this->cashLedger($from, $to, [TransactionCategory::OWNER_CAPITAL, TransactionCategory::OWNER_WITHDRAWAL]),
            'general-expense-ledger'  => $this->expenseLedger($from, $to),
            'expense-record'          => $this->expenseRecord($from, $to, $search),
            'receipt-record'          => $this->receiptRecord($from, $to, $search),
            'payment-record'          => $this->paymentRecord($from, $to, $search),
            default                   => abort(404),
        };
    }

    /** @return array{0: ?Carbon, 1: ?Carbon} [from, to) — to is the day after the chosen end date. */
    protected function window(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->addDay()->startOfDay() : null;

        return [$from, $to];
    }

    protected function filters(Request $request): array
    {
        return [
            'from' => (string) $request->input('from', ''),
            'to' => (string) $request->input('to', ''),
            'search' => (string) $request->input('search', ''),
        ];
    }

    /** Opening + Debit − Credit, the reference's balance rule. */
    protected function balanceOf(float $opening, float $debit, float $credit): float
    {
        return round($opening + $debit - $credit, 2);
    }

    protected function cashBankBalance(?Carbon $to): array
    {
        $in = FundHelper::income(null, $to);
        $out = FundHelper::spend(null, $to);

        return [
            'columns' => [['name', 'Account', 'start'], ['opening', 'Opening', 'end'], ['debit', 'Debit', 'end'], ['credit', 'Credit', 'end'], ['balance', 'Balance', 'end']],
            'rows' => [['name' => 'Cash (single funds ledger)', 'opening' => 0.0, 'debit' => round($in, 2), 'credit' => round($out, 2), 'balance' => $this->balanceOf(0, $in, $out)]],
            'totals' => ['debit' => round($in, 2), 'credit' => round($out, 2), 'balance' => $this->balanceOf(0, $in, $out)],
            'note' => 'The funds ledger is one till, so there is no split between cash, bank and mobile accounts. Uncollected cash-on-delivery sales are not counted.',
            'sections' => [],
        ];
    }

    protected function customerBalance(string $search): array
    {
        $customers = Customer::query()
            ->withSum(['orders as debit' => fn ($q) => $q->whereNotIn('order_status', self::NON_RECEIVABLE_STATUSES)], 'amount')
            ->withSum(['orders as due' => fn ($q) => $q->where('due_amount', '>', 0)
                ->whereNotIn('order_status', self::NON_RECEIVABLE_STATUSES)], 'due_amount')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get()
            ->filter(fn (Customer $c) => (float) $c->debit > 0)
            ->values();

        $rows = $customers->map(function (Customer $c) {
            $debit = round((float) $c->debit, 2);
            // Credit is what has been received: sales booked less what is still owed.
            $credit = round($debit - (float) $c->due, 2);

            return [
                'name' => $c->name,
                'address' => $c->address ?: '—',
                'phone' => $c->phone ?: '—',
                'opening' => 0.0,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $this->balanceOf(0, $debit, $credit),
            ];
        })->all();

        return [
            'columns' => [['name', 'Name', 'start'], ['address', 'Address', 'start'], ['phone', 'Contact No', 'start'], ['opening', 'Opening', 'end'], ['debit', 'Debit', 'end'], ['credit', 'Credit', 'end'], ['balance', 'Balance', 'end']],
            'rows' => $rows,
            'totals' => $this->sumOf($rows, ['opening', 'debit', 'credit', 'balance']),
            'note' => 'Debit = sales on orders not cancelled or returned. Credit = money received against them. Opening is 0 because customers carry no opening balance in this system.',
            'sections' => [],
        ];
    }

    protected function supplierBalance(string $search): array
    {
        $suppliers = Supplier::query()
            ->withSum('purchases as debit', 'grand_total')
            ->withSum('purchases as credit', 'paid_amount')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        $rows = $suppliers->map(fn (Supplier $s) => [
            'name' => $s->name,
            'address' => $s->address ?: '—',
            'phone' => $s->phone ?: '—',
            'opening' => round((float) $s->opening_balance, 2),
            'debit' => round((float) $s->debit, 2),
            'credit' => round((float) $s->credit, 2),
            'balance' => $this->balanceOf((float) $s->opening_balance, (float) $s->debit, (float) $s->credit),
        ])->filter(fn (array $r) => $r['debit'] != 0 || $r['opening'] != 0)->values()->all();

        return [
            'columns' => [['name', 'Name', 'start'], ['address', 'Address', 'start'], ['phone', 'Contact No', 'start'], ['opening', 'Opening', 'end'], ['debit', 'Debit', 'end'], ['credit', 'Credit', 'end'], ['balance', 'Balance', 'end']],
            'rows' => $rows,
            'totals' => $this->sumOf($rows, ['opening', 'debit', 'credit', 'balance']),
            'note' => 'Debit = purchase value. Credit = amount paid on those purchases. Suppliers with no purchases or opening balance are hidden.',
            'sections' => [],
        ];
    }

    protected function capitalBalance(?Carbon $to): array
    {
        $in = FundTransaction::where('direction', 'in')
            ->where('transaction_category', TransactionCategory::OWNER_CAPITAL)
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))
            ->sum('amount');
        $out = FundTransaction::where('direction', 'out')
            ->where('transaction_category', TransactionCategory::OWNER_WITHDRAWAL)
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))
            ->sum('amount');

        $balance = $this->balanceOf(0, (float) $in, (float) $out);

        return [
            'columns' => [['name', 'Account', 'start'], ['opening', 'Opening', 'end'], ['debit', 'Capital in', 'end'], ['credit', 'Withdrawn', 'end'], ['balance', 'Balance', 'end']],
            'rows' => [['name' => 'Owner capital', 'opening' => 0.0, 'debit' => round((float) $in, 2), 'credit' => round((float) $out, 2), 'balance' => $balance]],
            'totals' => ['debit' => round((float) $in, 2), 'credit' => round((float) $out, 2), 'balance' => $balance],
            'note' => null,
            'sections' => [],
        ];
    }

    protected function expenseByCategory(?Carbon $from, ?Carbon $to): array
    {
        $rows = Expense::query()
            ->when($from, fn ($q) => $q->where('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('expense_date', '<', $to))
            ->selectRaw('category, SUM(amount) as total')->groupBy('category')->orderByDesc('total')->get()
            ->map(fn ($r) => ['category' => $r->category ?: 'Uncategorised', 'amount' => round((float) $r->total, 2)])
            ->all();

        return [
            'columns' => [['category', 'Category', 'start'], ['amount', 'Expense Amount', 'end']],
            'rows' => $rows,
            'totals' => ['amount' => round(array_sum(array_column($rows, 'amount')), 2)],
            'note' => null,
            'sections' => [],
        ];
    }

    /**
     * One section per type of cash movement, the way the reference ERP prints it
     * (Sales Paid Amount, Purchases Payment Amount, …).
     */
    protected function dailyStatement(?Carbon $from, ?Carbon $to): array
    {
        $sections = $this->fundRows($from, $to, null)
            ->groupBy(fn (FundTransaction $t) => $t->categoryLabel() ?: 'Other')
            ->map(function ($group, string $title) {
                $rows = $group->sortBy('created_at')->values()->map(fn (FundTransaction $t) => [
                    'date' => $t->created_at->format('d M Y'),
                    'particular' => $t->note ?: ($t->categoryLabel() ?: $t->source),
                    'voucher' => $t->source_id ? "{$t->source} #{$t->source_id}" : $t->source,
                    'amount' => round((float) $t->amount, 2),
                ])->all();

                return [
                    'title' => $title.' Amount',
                    'columns' => [['date', 'Date', 'start'], ['particular', 'Particular', 'start'], ['voucher', 'VCH NO', 'start'], ['amount', 'Amount', 'end']],
                    'rows' => $rows,
                    'total' => round(array_sum(array_column($rows, 'amount')), 2),
                ];
            })
            ->values()->all();

        return [
            'columns' => [],
            'rows' => [],
            'totals' => ['total' => round(array_sum(array_column($sections, 'total')), 2)],
            'note' => 'Every cash movement in the period, grouped by type. Use the cash ledger to see the running balance.',
            'sections' => $sections,
        ];
    }

    /**
     * @param  TransactionCategory[]|null  $categories
     */
    protected function cashLedger(?Carbon $from, ?Carbon $to, ?array $categories): array
    {
        $opening = $from
            ? FundHelper::income(null, $from) - FundHelper::spend(null, $from)
            : 0.0;

        $running = $opening;
        $rows = [['date' => '', 'particular' => 'Opening balance', 'code' => '', 'type' => '', 'debit' => null, 'credit' => null, 'balance' => round($opening, 2)]];

        foreach ($this->fundRows($from, $to, $categories) as $t) {
            $amount = round((float) $t->amount, 2);
            $isIn = $t->direction === 'in';
            $running += $isIn ? $amount : -$amount;

            $rows[] = [
                'date' => $t->created_at->format('d M Y'),
                'particular' => $t->note ?: ($t->categoryLabel() ?: $t->source),
                'code' => $t->source_id ? (string) $t->source_id : '—',
                'type' => $t->categoryLabel() ?: ucfirst(str_replace('_', ' ', (string) $t->source)),
                'debit' => $isIn ? $amount : null,
                'credit' => $isIn ? null : $amount,
                'balance' => round($running, 2),
            ];
        }

        $rows[] = ['date' => '', 'particular' => 'Closing balance', 'code' => '', 'type' => '', 'debit' => null, 'credit' => null, 'balance' => round($running, 2)];

        return [
            'columns' => [['date', 'Date', 'start'], ['particular', 'Particular', 'start'], ['code', 'Code', 'start'], ['type', 'Type', 'start'], ['debit', 'Debit', 'end'], ['credit', 'Credit', 'end'], ['balance', 'Balance', 'end']],
            'rows' => $rows,
            'totals' => [
                'debit' => round(array_sum(array_column($rows, 'debit')), 2),
                'credit' => round(array_sum(array_column($rows, 'credit')), 2),
            ],
            'note' => 'Lists the funds ledger as recorded. The balance here can differ from the Cash Bank Balance report by any uncollected cash-on-delivery sales.',
            'sections' => [],
        ];
    }

    protected function expenseLedger(?Carbon $from, ?Carbon $to): array
    {
        $running = 0.0;
        $rows = [];

        $expenses = Expense::query()
            ->when($from, fn ($q) => $q->where('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('expense_date', '<', $to))
            ->orderBy('expense_date')->orderBy('id')->get();

        foreach ($expenses as $e) {
            $amount = round((float) $e->amount, 2);
            $running += $amount;
            $rows[] = [
                'date' => Carbon::parse($e->expense_date)->format('d M Y'),
                'particular' => $e->title,
                'category' => $e->category ?: 'Uncategorised',
                'amount' => $amount,
                'balance' => round($running, 2),
            ];
        }

        return [
            'columns' => [['date', 'Date', 'start'], ['particular', 'Expense', 'start'], ['category', 'Category', 'start'], ['amount', 'Amount', 'end'], ['balance', 'Running total', 'end']],
            'rows' => $rows,
            'totals' => ['amount' => round($running, 2)],
            'note' => null,
            'sections' => [],
        ];
    }

    /**
     * Cash, receivables and stock on one side; supplier payables and owner capital
     * on the other. What is left over is shown as an explicit difference rather
     * than being forced into a line, because retained profit is not tracked here.
     */
    protected function balanceSheet(?Carbon $to): array
    {
        $cash = round(FundHelper::income(null, $to) - FundHelper::spend(null, $to), 2);

        $receivable = round((float) Order::query()
            ->where('due_amount', '>', 0)
            ->whereNotIn('order_status', self::NON_RECEIVABLE_STATUSES)
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))
            ->sum('due_amount'), 2);

        $stock = round((float) StockBatch::query()
            ->where('remaining_qty', '>', 0)
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))
            ->sum(DB::raw('remaining_qty * unit_cost')), 2);

        $payable = round((float) Supplier::query()
            ->withSum('purchases as debit', 'grand_total')
            ->withSum('purchases as credit', 'paid_amount')
            ->get()
            ->sum(fn (Supplier $s) => (float) $s->opening_balance + (float) $s->debit - (float) $s->credit), 2);

        $capital = round((float) FundTransaction::where('direction', 'in')
            ->where('transaction_category', TransactionCategory::OWNER_CAPITAL)
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))->sum('amount')
            - (float) FundTransaction::where('direction', 'out')
            ->where('transaction_category', TransactionCategory::OWNER_WITHDRAWAL)
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))->sum('amount'), 2);

        $loansGiven = round((float) Loan::where('direction', Loan::GIVEN)->get()->sum(fn (Loan $l) => $l->outstanding()), 2);
        $loansTaken = round((float) Loan::where('direction', Loan::TAKEN)->get()->sum(fn (Loan $l) => $l->outstanding()), 2);
        $fixedAssets = round((float) FixedAsset::where('status', FixedAsset::ACTIVE)->get()->sum(fn (FixedAsset $a) => $a->bookValue()), 2);

        $assets = [
            ['name' => 'Cash (single funds ledger)', 'amount' => $cash],
            ['name' => 'Customer receivables', 'amount' => $receivable],
            ['name' => 'Loans given (outstanding)', 'amount' => $loansGiven],
            ['name' => 'Fixed assets (book value)', 'amount' => $fixedAssets],
            ['name' => 'Stock on hand at cost', 'amount' => $stock],
        ];
        $liabilities = [
            ['name' => 'Supplier payables', 'amount' => $payable],
            ['name' => 'Loans taken (outstanding)', 'amount' => $loansTaken],
            ['name' => 'Owner capital (net of withdrawals)', 'amount' => $capital],
        ];

        $totalAssets = round(array_sum(array_column($assets, 'amount')), 2);
        $totalLiabilities = round(array_sum(array_column($liabilities, 'amount')), 2);
        $difference = round($totalAssets - $totalLiabilities, 2);

        $columns = [['name', 'Particular', 'start'], ['amount', 'Amount', 'end']];

        return [
            'columns' => [],
            'rows' => [],
            'totals' => ['assets' => $totalAssets, 'liabilities' => $totalLiabilities, 'difference' => $difference],
            'note' => "Difference of ".number_format($difference, 2)." is profit kept in the business plus anything not recorded in this system (fixed assets, loans, bank balances outside the funds ledger). It is not forced into a line.",
            'sections' => [
                ['title' => 'Assets', 'columns' => $columns, 'rows' => $assets, 'total' => $totalAssets],
                ['title' => 'Liabilities and capital', 'columns' => $columns, 'rows' => $liabilities, 'total' => $totalLiabilities],
            ],
        ];
    }

    /**
     * Sales, cost and profit per product for delivered orders in the period.
     * Sales are sale price × quantity less the line discount; cost uses the same
     * rule as the profit report (realised COGS, else purchase price × quantity).
     */
    protected function productProfitLoss(?Carbon $from, ?Carbon $to): array
    {
        $cogs = app(CogsCalculator::class);
        $rows = OrderDetails::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereIn('orders.order_status', CogsCalculator::RECOGNIZED_STATUSES)
            ->when($from, fn ($q) => $q->where('orders.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('orders.created_at', '<', $to))
            ->select('order_details.*')
            ->get()
            ->groupBy(fn (OrderDetails $d) => $d->product_name ?: 'Product #'.$d->product_id)
            ->map(function ($lines, string $name) use ($cogs) {
                $qty = (int) $lines->sum('qty');
                $sales = round((float) $lines->sum(fn ($d) => ((float) $d->sale_price * (int) $d->qty) - (float) ($d->product_discount ?? 0)), 2);
                $cost = round((float) $lines->sum(fn ($d) => $cogs->lineCogs($d)), 2);

                return [
                    'name' => $name,
                    'qty' => $qty,
                    'sales' => $sales,
                    'cost' => $cost,
                    'profit' => round($sales - $cost, 2),
                ];
            })
            ->sortByDesc('profit')->values()->all();

        return [
            'columns' => [['name', 'Product', 'start'], ['qty', 'Sold Qty', 'end'], ['sales', 'Sold Amount', 'end'], ['cost', 'Cost Amount', 'end'], ['profit', 'Profit / Loss', 'end']],
            'rows' => $rows,
            'totals' => $this->sumOf($rows, ['sales', 'cost', 'profit']),
            'note' => 'Returns and refunds are not netted off here. Use the Net Profit Loss report for the business-wide figure.',
            'sections' => [],
        ];
    }

    /**
     * Sales, cost, dues and profit per customer for delivered orders in the period.
     */
    protected function partyProfitLoss(?Carbon $from, ?Carbon $to): array
    {
        $cogs = app(CogsCalculator::class);
        $orders = Order::query()
            ->whereIn('order_status', CogsCalculator::RECOGNIZED_STATUSES)
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))
            ->with('customer:id,name')
            ->get();

        $rows = $orders->groupBy(fn (Order $o) => $o->customer_id ?: 0)
            ->map(function ($group) use ($cogs) {
                $bill = round((float) $group->sum('amount'), 2);
                $due = round((float) $group->sum(fn (Order $o) => max(0, (float) $o->due_amount)), 2);
                $cost = $cogs->cogsForOrders($group);
                $name = optional($group->first()->customer)->name ?: 'Guest / walk-in';

                return [
                    'name' => $name,
                    'bill' => $bill,
                    'cost' => round($cost, 2),
                    'due' => $due,
                    'paid' => round($bill - $due, 2),
                    'profit' => round($bill - $cost, 2),
                ];
            })
            ->sortByDesc('profit')->values()->all();

        return [
            'columns' => [['name', 'Party', 'start'], ['bill', 'Bill Amount', 'end'], ['cost', 'Cost', 'end'], ['due', 'Due', 'end'], ['paid', 'Paid', 'end'], ['profit', 'Profit / Loss', 'end']],
            'rows' => $rows,
            'totals' => $this->sumOf($rows, ['bill', 'cost', 'due', 'paid', 'profit']),
            'note' => 'Guest orders are grouped as one party. Paid = bill less the amount still due on those orders.',
            'sections' => [],
        ];
    }

    protected function expenseRecord(?Carbon $from, ?Carbon $to, string $search): array
    {
        $rows = Expense::query()
            ->when($from, fn ($q) => $q->where('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('expense_date', '<', $to))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', "%{$search}%")->orWhere('category', 'like', "%{$search}%")))
            ->orderByDesc('expense_date')->orderByDesc('id')
            ->get()
            ->map(fn (Expense $e) => [
                'date' => Carbon::parse($e->expense_date)->format('d M Y'),
                'code' => (string) $e->id,
                'title' => $e->title,
                'category' => $e->category ?: 'Uncategorised',
                'note' => $e->note ?: '—',
                'amount' => round((float) $e->amount, 2),
            ])->all();

        return [
            'columns' => [['date', 'Date', 'start'], ['code', 'Expense Code', 'start'], ['title', 'Expense', 'start'], ['category', 'Category', 'start'], ['note', 'Note', 'start'], ['amount', 'Amount', 'end']],
            'rows' => $rows,
            'totals' => ['amount' => round(array_sum(array_column($rows, 'amount')), 2)],
            'note' => 'Edit or delete expenses on the Expenses screen; this page is a record only.',
            'sections' => [],
        ];
    }

    protected function paymentRecord(?Carbon $from, ?Carbon $to, string $search): array
    {
        $rows = SupplierPayment::query()
            ->with(['supplier:id,name', 'purchase:id,invoice_no'])
            ->when($from, fn ($q) => $q->where('payment_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('payment_date', '<', $to))
            ->when($search !== '', fn ($q) => $q->whereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%")))
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->get()
            ->map(fn (SupplierPayment $p) => [
                'date' => Carbon::parse($p->payment_date)->format('d M Y'),
                'code' => (string) $p->id,
                'party' => optional($p->supplier)->name ?: '—',
                'invoice' => optional($p->purchase)->invoice_no ?: ('#'.$p->purchase_id),
                'method' => $p->method ?: '—',
                'note' => $p->note ?: '—',
                'amount' => round((float) $p->amount, 2),
            ])->all();

        return [
            'columns' => [['date', 'Date', 'start'], ['code', 'Payment Code', 'start'], ['party', 'Party', 'start'], ['invoice', 'Purchase', 'start'], ['method', 'Method', 'start'], ['note', 'Note', 'start'], ['amount', 'Amount', 'end']],
            'rows' => $rows,
            'totals' => ['amount' => round(array_sum(array_column($rows, 'amount')), 2)],
            'note' => 'Payments made against purchases. Payments are entered on the Payment Entry screen or the purchase page.',
            'sections' => [],
        ];
    }

    protected function receiptRecord(?Carbon $from, ?Carbon $to, string $search): array
    {
        $rows = OrderPayment::query()
            ->with(['customer:id,name', 'order:id,invoice_id'])
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))
            ->when($search !== '', fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->get()
            ->map(fn (OrderPayment $p) => [
                'date' => $p->created_at->format('d M Y'),
                'code' => (string) $p->id,
                'party' => optional($p->customer)->name ?: 'Guest',
                'invoice' => optional($p->order)->invoice_id ?: ('#'.$p->order_id),
                'method' => $p->payment_method ?: '—',
                'note' => $p->trx_note ?: '—',
                'amount' => round((float) $p->amount, 2),
            ])->all();

        return [
            'columns' => [['date', 'Date', 'start'], ['code', 'Receipt Code', 'start'], ['party', 'Party', 'start'], ['invoice', 'Invoice', 'start'], ['method', 'Method', 'start'], ['note', 'Note', 'start'], ['amount', 'Amount', 'end']],
            'rows' => $rows,
            'totals' => ['amount' => round(array_sum(array_column($rows, 'amount')), 2)],
            'note' => 'Payments recorded against orders. Money received without an order is on the Cash Bank Ledger.',
            'sections' => [],
        ];
    }

    protected function loanBalance(string $kind): array
    {
        $rows = Loan::with('repayments')->where('kind', $kind)->orderBy('loan_date')->get()
            ->map(fn (Loan $l) => [
                'name' => $l->party_name,
                'direction' => $l->direction === Loan::TAKEN ? 'Borrowed' : 'Lent',
                'amount' => round((float) $l->amount, 2),
                'repaid' => round((float) $l->repayments->sum('amount'), 2),
                'outstanding' => $l->outstanding(),
            ])->all();

        return [
            'columns' => [['name', 'Party', 'start'], ['direction', 'Direction', 'start'], ['amount', 'Amount', 'end'], ['repaid', 'Repaid', 'end'], ['outstanding', 'Outstanding', 'end']],
            'rows' => $rows,
            'totals' => $this->sumOf($rows, ['amount', 'repaid', 'outstanding']),
            'note' => 'Outstanding = amount less repayments. Lent amounts are owed to us; borrowed amounts are owed by us.',
            'sections' => [],
        ];
    }

    protected function loanLedger(string $kind, ?Carbon $from, ?Carbon $to): array
    {
        $rows = [];
        $loans = Loan::with('repayments')->where('kind', $kind)->get();
        $events = collect();

        foreach ($loans as $loan) {
            $events->push(['at' => $loan->loan_date, 'particular' => $loan->party_name.' — '.($loan->direction === Loan::TAKEN ? 'borrowed' : 'lent'), 'code' => $loan->code, 'amount' => (float) $loan->amount, 'loan' => $loan->id, 'kind' => 'open']);
            foreach ($loan->repayments as $r) {
                $events->push(['at' => $r->paid_on, 'particular' => $loan->party_name.' — repayment', 'code' => $loan->code, 'amount' => (float) $r->amount, 'loan' => $loan->id, 'kind' => 'repay']);
            }
        }

        $running = [];
        foreach ($events->sortBy(fn ($e) => $e['at']->format('Y-m-d')) as $e) {
            $running[$e['loan']] = ($running[$e['loan']] ?? 0) + ($e['kind'] === 'open' ? $e['amount'] : -$e['amount']);
            $day = $e['at']->format('Y-m-d');
            if ($from && $day < $from->format('Y-m-d')) continue;
            if ($to && $day >= $to->format('Y-m-d')) continue;
            $rows[] = [
                'date' => $e['at']->format('d M Y'),
                'particular' => $e['particular'],
                'code' => $e['code'],
                'amount' => round($e['amount'], 2),
                'balance' => round($running[$e['loan']], 2),
            ];
        }

        return [
            'columns' => [['date', 'Date', 'start'], ['particular', 'Particular', 'start'], ['code', 'Code', 'start'], ['amount', 'Amount', 'end'], ['balance', 'Outstanding', 'end']],
            'rows' => $rows,
            'totals' => ['amount' => round(array_sum(array_column($rows, 'amount')), 2)],
            'note' => null,
            'sections' => [],
        ];
    }

    protected function fixedAssetBalance(?Carbon $to): array
    {
        $rows = FixedAsset::with('events')->when($to, fn ($q) => $q->where('purchase_date', '<', $to))->orderBy('purchase_date')->get()
            ->map(fn (FixedAsset $a) => [
                'name' => $a->name,
                'purchased' => $a->purchase_date->format('d M Y'),
                'cost' => round((float) $a->cost, 2),
                'depreciation' => round((float) $a->accumulated_depreciation, 2),
                'book' => $a->status === FixedAsset::DISPOSED ? 0.0 : $a->bookValue(),
                'status' => $a->status === FixedAsset::DISPOSED ? 'Disposed' : 'Active',
            ])->all();

        return [
            'columns' => [['name', 'Asset', 'start'], ['purchased', 'Purchased', 'start'], ['cost', 'Cost', 'end'], ['depreciation', 'Depreciation', 'end'], ['book', 'Book value', 'end'], ['status', 'Status', 'start']],
            'rows' => $rows,
            'totals' => $this->sumOf($rows, ['cost', 'depreciation', 'book']),
            'note' => 'Book value is the revalued amount when one is recorded, otherwise cost less depreciation. Disposed assets show 0.',
            'sections' => [],
        ];
    }

    protected function fixedAssetLedger(?Carbon $from, ?Carbon $to): array
    {
        $events = collect();
        foreach (FixedAsset::with('events')->get() as $asset) {
            $events->push(['at' => $asset->purchase_date, 'asset' => $asset->name, 'event' => 'Purchase', 'amount' => (float) $asset->cost, 'note' => '—']);
            foreach ($asset->events as $e) {
                $events->push(['at' => $e->event_date, 'asset' => $asset->name, 'event' => ucfirst($e->type), 'amount' => (float) $e->amount, 'note' => $e->note ?: '—']);
            }
            if ($asset->status === FixedAsset::DISPOSED) {
                $events->push(['at' => $asset->disposal_date, 'asset' => $asset->name, 'event' => 'Disposal', 'amount' => (float) $asset->disposal_value, 'note' => $asset->disposal_reason ?: '—']);
            }
        }

        $rows = $events->filter(function ($e) use ($from, $to) {
            $day = $e['at']->format('Y-m-d');

            return (!$from || $day >= $from->format('Y-m-d')) && (!$to || $day < $to->format('Y-m-d'));
        })->sortBy(fn ($e) => $e['at']->format('Y-m-d'))->values()
            ->map(fn ($e) => ['date' => $e['at']->format('d M Y'), 'asset' => $e['asset'], 'event' => $e['event'], 'amount' => round($e['amount'], 2), 'note' => $e['note']])
            ->all();

        return [
            'columns' => [['date', 'Date', 'start'], ['asset', 'Asset', 'start'], ['event', 'Event', 'start'], ['amount', 'Amount', 'end'], ['note', 'Note', 'start']],
            'rows' => $rows,
            'totals' => ['amount' => round(array_sum(array_column($rows, 'amount')), 2)],
            'note' => 'Amounts are cost, depreciation, revalued value, or sale value, as the event describes.',
            'sections' => [],
        ];
    }

    /**
     * One product's stock movements: purchases in, sales out, and manual adjustments
     * as the change from the stock before to the stock after. The running quantity
     * starts from the movements in the recorded history, not from a counted opening
     * stock, so the first figure is only as good as the history behind it.
     */
    protected function productLedger(Request $request, ?Carbon $from, ?Carbon $to): array
    {
        $productId = (int) $request->input('product_id', 0);
        $product = $productId ? Product::find($productId) : null;

        if (!$product) {
            return [
                'columns' => [['date', 'Date', 'start'], ['particular', 'Particular', 'start'], ['code', 'Code', 'start'], ['in', 'In Quantity', 'end'], ['out', 'Out Quantity', 'end'], ['balance', 'Current', 'end']],
                'rows' => [], 'totals' => [], 'note' => 'Choose a product to see its movements.', 'sections' => [],
            ];
        }

        $moves = collect();

        PurchaseItem::query()->with('purchase:id,invoice_no,purchase_date')
            ->where('product_id', $product->id)
            ->get()
            ->each(function (PurchaseItem $i) use ($moves) {
                if (!$i->purchase) {
                    return;
                }
                $moves->push(['at' => Carbon::parse($i->purchase->purchase_date), 'particular' => 'Purchase', 'code' => $i->purchase->invoice_no ?: '#'.$i->purchase_id, 'in' => (int) $i->qty, 'out' => 0]);
            });

        OrderDetails::query()->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereIn('orders.order_status', CogsCalculator::RECOGNIZED_STATUSES)
            ->where('order_details.product_id', $product->id)
            ->select('order_details.qty', 'orders.created_at', 'orders.invoice_id', 'orders.id as order_no')
            ->get()
            ->each(function ($row) use ($moves) {
                $moves->push(['at' => Carbon::parse($row->created_at), 'particular' => 'Sale', 'code' => $row->invoice_id ?: '#'.$row->order_no, 'in' => 0, 'out' => (int) $row->qty]);
            });

        StockAdjustment::where('product_id', $product->id)->get()
            ->each(function (StockAdjustment $a) use ($moves) {
                $change = (int) $a->new_stock - (int) $a->current_stock;
                $moves->push(['at' => $a->created_at, 'particular' => 'Adjustment'.($a->reason ? ': '.$a->reason : ''), 'code' => '#'.$a->id, 'in' => max($change, 0), 'out' => max(-$change, 0)]);
            });

        $sorted = $moves->sortBy(fn ($m) => $m['at']->format('Y-m-d H:i:s'))->values();

        $running = 0;
        $rows = [];
        foreach ($sorted as $m) {
            $running += $m['in'] - $m['out'];
            $day = $m['at']->format('Y-m-d');
            if (($from && $day < $from->format('Y-m-d')) || ($to && $day >= $to->format('Y-m-d'))) {
                continue;
            }
            $rows[] = ['date' => $m['at']->format('d M Y'), 'particular' => $m['particular'], 'code' => $m['code'], 'in' => $m['in'], 'out' => $m['out'], 'balance' => $running];
        }

        return [
            'columns' => [['date', 'Date', 'start'], ['particular', 'Particular', 'start'], ['code', 'Code', 'start'], ['in', 'In Quantity', 'end'], ['out', 'Out Quantity', 'end'], ['balance', 'Current', 'end']],
            'rows' => $rows,
            'totals' => ['in' => array_sum(array_column($rows, 'in')), 'out' => array_sum(array_column($rows, 'out'))],
            'note' => "{$product->name}: the current figure is built from the movement history only. Stock held before the first recorded movement is not counted.",
            'sections' => [],
        ];
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, float>
     */
    protected function sumOf(array $rows, array $keys): array
    {
        $totals = [];
        foreach ($keys as $key) {
            $totals[$key] = round(array_sum(array_column($rows, $key)), 2);
        }

        return $totals;
    }

    /**
     * @param  TransactionCategory[]|null  $categories
     * @return \Illuminate\Support\Collection<int, FundTransaction>
     */
    protected function fundRows(?Carbon $from, ?Carbon $to, ?array $categories)
    {
        return FundTransaction::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))
            ->when($categories, fn ($q) => $q->whereIn('transaction_category', $categories))
            ->orderBy('created_at')->orderBy('id')
            ->get();
    }
}
