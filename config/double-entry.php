<?php

/*
|--------------------------------------------------------------------------
| Host accounting configuration — MUST stay package-free
|--------------------------------------------------------------------------
| This file is loaded at boot whether or not softmit/bd-double-entry is
| installed, so it must not reference any Softmit\DoubleEntry class (not even
| class constants): a missing class while a config file is being evaluated is
| a boot-time fatal that would make the optional module mandatory. Role values
| below are the plain strings of Softmit\DoubleEntry\Support\AccountRole —
| they are resolved to real accounts by App\Services\Accounting\ManualEntryService
| only when Advanced Accounting is enabled.
*/

return [
    /*
    |--------------------------------------------------------------------------
    | Advanced accounting switch
    |--------------------------------------------------------------------------
    | Advanced Accounting (double-entry) is optional; Lite Accounting always
    | runs. When this is false the whole application behaves exactly as it did
    | before the package existed: no business event is journalled, no package
    | class is touched. See App\Support\Accounting\AccountingAvailability, which
    | also requires the package to actually be installed before this can take
    | effect, so a missing package can never crash a commerce flow.
    */
    'enabled' => env('FULL_ACCOUNTING_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Journal numbering
    |--------------------------------------------------------------------------
    */
    'journal_prefix' => 'JV',

    /*
    |--------------------------------------------------------------------------
    | Accounting cutover
    |--------------------------------------------------------------------------
    | Business events dated before this day are not journalled automatically;
    | they are represented by the opening-balance journal instead. Manual and
    | opening journals may still be dated earlier, because those are exactly the
    | records a bookkeeper is allowed to backdate.
    */
    'cutover_date' => env('ACCOUNTING_CUTOVER_DATE', '2026-01-01'),

    /*
    |--------------------------------------------------------------------------
    | Funds
    |--------------------------------------------------------------------------
    | The fund used when a cash movement does not name one. The application
    | currently has a single virtual fund, hence a single default key.
    */
    'default_fund' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Actor guards
    |--------------------------------------------------------------------------
    | Who clicked is recorded from the first guard with an authenticated user.
    | Listed in priority order so an admin-panel action is attributed to the
    | admin, not to whatever the framework's default guard happens to be.
    */
    'actor_guards' => ['admin', 'web'],

    /*
    |--------------------------------------------------------------------------
    | Party model registry
    |--------------------------------------------------------------------------
    | Party types are a closed set (see PartyType enum). This map is the ONLY
    | place a party type is resolved to a class, so a request can never steer
    | the engine into instantiating an arbitrary class.
    |
    | The host application overrides this to point at its own models.
    */
    'parties' => [
        'customer' => \App\Models\Customer::class,
        'supplier' => \App\Models\Supplier::class,
        'employee' => \App\Models\Employee::class,
        // No owner/vendor/reseller/agent/courier records exist in this app yet:
        // owner capital and drawings are identified by account role instead, so a
        // null here means "no navigable party row", never "guess one".
        'owner'    => null,
        'vendor'   => null,
        'reseller' => null,
        'agent'    => null,
        'courier'  => null,
        'other'    => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Source deep links
    |--------------------------------------------------------------------------
    | source_type is a controlled key, never a class name. The host maps those
    | keys to route names so a journal can link back to the order/expense/purchase
    | that produced it, without the package importing a single host model.
    |
    | Intentionally empty: the admin panel has no primary-key "show" routes for
    | orders, expenses or purchases (only invoice/edit routes with their own
    | parameter shapes), so the journals list shows source type + id + reference as
    | text rather than guessing a URL. Add entries here when those routes exist.
    */
    'source_routes' => [
        // 'sale'     => 'admin.order.show',
        // 'expense'  => 'admin.expenses.show',
        // 'purchase' => 'purchases.show',
    ],

    /*
    |--------------------------------------------------------------------------
    | Manually entered money
    |--------------------------------------------------------------------------
    | The expense / money-in / withdrawal screens store a legacy row and then ask
    | for a journal. These are the accounts those screens are allowed to touch,
    | expressed as roles so renaming or renumbering the chart is a config change.
    |
    | Expense categories in this application are free text, so the map is keyed by
    | a normalised category (lowercased, non-alphanumerics collapsed to "_").
    | Anything unmapped lands on expense_default_role — never on "no entry", and
    | never on a guess derived from the account code range.
    */
    'manual' => [
        'fund_key' => env('ACCOUNTING_FUND_KEY', 'default'),

        'expense_roles' => [
            'rent'         => 'rent_expense',
            'office_rent'  => 'rent_expense',
            'house_rent'   => 'rent_expense',
            'salary'       => 'salary_expense',
            'payroll'      => 'salary_expense',
            'staff_salary' => 'salary_expense',
            'utility'      => 'utility_expense',
            'utilities'    => 'utility_expense',
            'electricity'  => 'utility_expense',
            'bill'         => 'utility_expense',
            'internet'     => 'utility_expense',
            'delivery'     => 'delivery_expense',
            'shipping'     => 'delivery_expense',
            'courier'      => 'delivery_expense',
            'discount'     => 'discount_expense',
            'refund'       => 'refund_expense',
        ],

        'expense_default_role' => 'general_expense',

        // Money in is either the owner's own capital (equity — never profit) or
        // genuine other income. The screen has to say which.
        'capital_role' => 'owner_capital',
        'income_role'  => 'other_income',

        // Money out to the owner is a drawing, not an expense.
        'drawings_role' => 'owner_drawings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Opening balances
    |--------------------------------------------------------------------------
    | Where the balancing figure of the opening worksheet goes. That figure is a
    | claim about what the owner has tied up in the business, so the worksheet
    | shows what it would be and only creates the line when the operator asks for
    | it — it is never added silently, because a silent plug hides every mistake
    | that made it necessary.
    */
    'opening' => [
        'balancing_role' => 'retained_earnings',
    ],
];
