<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Finance setup lists: account categories and groups, expense categories and
 * heads, balance adjustment heads and areas. Name-only lookups; creating, renaming
 * or deleting one never changes a figure in the books.
 */
class FinanceMasterController extends Controller
{
    /**
     * Master registry. `column` is the display/name column, `extra` any foreign key
     * the row needs, with its validation rules.
     */
    public const TYPES = [
        'account-category' => [
            'label' => 'Account Category', 'table' => 'finance_account_categories', 'column' => 'name', 'extra' => [],
        ],
        'account-group' => [
            'label' => 'Account Group', 'table' => 'finance_account_groups', 'column' => 'name',
            'extra' => ['account_category_id' => ['required', 'integer', 'exists:finance_account_categories,id']],
        ],
        'expense-category' => [
            'label' => 'Expense Category', 'table' => 'expense_categories', 'column' => 'name', 'extra' => [],
        ],
        'expense-head' => [
            'label' => 'Expense Head', 'table' => 'expense_heads', 'column' => 'title', 'extra' => [],
        ],
        'balance-adjustment-head' => [
            'label' => 'Balance Adjustment Head', 'table' => 'balance_adjustment_heads', 'column' => 'name', 'extra' => [],
        ],
        'area' => [
            'label' => 'Area', 'table' => 'finance_areas', 'column' => 'name', 'extra' => [],
        ],
    ];

    public function __construct()
    {
        $this->middleware('permission:fund-list', ['only' => ['index']]);
        $this->middleware('permission:fund-create', ['only' => ['store']]);
        $this->middleware('permission:fund-edit', ['only' => ['update', 'destroy']]);
    }

    public function index(string $type)
    {
        $config = $this->config($type);

        $query = DB::table($config['table'].' as m')->select('m.*');

        if ($type === 'account-group') {
            $query->join('finance_account_categories as c', 'c.id', '=', 'm.account_category_id')
                ->addSelect('c.name as category_name');
        }

        return view('backEnd.accounts.masters.index', [
            'type' => $type,
            'config' => $config,
            'types' => self::TYPES,
            'rows' => $query->orderBy('m.'.$config['column'])->get(),
            'categories' => $type === 'account-group'
                ? DB::table('finance_account_categories')->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function store(Request $request, string $type)
    {
        $config = $this->config($type);
        $data = $request->validate($this->rules($config, null));

        $now = now();
        DB::table($config['table'])->insert($data + ['created_at' => $now, 'updated_at' => $now]);

        log_activity('finance', 'create', "{$config['label']} added: {$data[$config['column']]}", null, $data);

        return redirect()->route('admin.finance.masters.index', $type)
            ->with('success', "{$config['label']} \"{$data[$config['column']]}\" added.");
    }

    public function update(Request $request, string $type, int $id)
    {
        $config = $this->config($type);
        $this->findRow($config, $id);

        $data = $request->validate($this->rules($config, $id));

        DB::table($config['table'])->where('id', $id)->update($data + ['updated_at' => now()]);

        log_activity('finance', 'update', "{$config['label']} updated: {$data[$config['column']]}", null, $data);

        return redirect()->route('admin.finance.masters.index', $type)
            ->with('success', "{$config['label']} updated.");
    }

    public function destroy(string $type, int $id)
    {
        $config = $this->config($type);
        $row = $this->findRow($config, $id);

        try {
            DB::table($config['table'])->where('id', $id)->delete();
        } catch (QueryException) {
            // An account category that still has groups is protected by its foreign key.
            return redirect()->route('admin.finance.masters.index', $type)
                ->with('error', "{$config['label']} \"{$row->{$config['column']}}\" is still in use and was not deleted.");
        }

        log_activity('finance', 'delete', "{$config['label']} deleted: {$row->{$config['column']}}", null, ['id' => $id]);

        return redirect()->route('admin.finance.masters.index', $type)
            ->with('success', "{$config['label']} \"{$row->{$config['column']}}\" deleted.");
    }

    /** @return array{label: string, table: string, column: string, extra: array<string, array<int, string>>} */
    protected function config(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }

    protected function findRow(array $config, int $id): object
    {
        $row = DB::table($config['table'])->where('id', $id)->first();

        abort_if($row === null, 404);

        return $row;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(array $config, ?int $ignoreId): array
    {
        $column = $config['column'];
        $unique = \Illuminate\Validation\Rule::unique($config['table'], $column)->ignore($ignoreId);

        return [$column => ['required', 'string', 'max:150', $unique]] + $config['extra'];
    }
}
