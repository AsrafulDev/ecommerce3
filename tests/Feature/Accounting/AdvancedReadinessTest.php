<?php

namespace Tests\Feature\Accounting;

use App\Services\Accounting\AdvancedAccountingGateway;
use App\Services\Accounting\NullAdvancedAccountingGateway;
use Tests\TestCase;

class AdvancedReadinessTest extends AccountingTestCase
{
    public function test_advanced_is_not_ready_until_a_posted_balanced_opening_exists(): void
    {
        $gateway = app(AdvancedAccountingGateway::class);

        $this->assertTrue($gateway->enabled());
        $this->assertFalse($gateway->readyForLivePosting());

        $this->post(route('admin.accounting.opening.save'), [
            'as_at' => '2026-09-30',
            'lines' => [[
                'account_id' => $this->accountId(\Softmit\DoubleEntry\Support\AccountRole::CASH),
                'debit' => '100.00', 'credit' => '0.00', 'description' => 'Cash',
            ]],
        ]);

        $this->assertFalse($gateway->readyForLivePosting(), 'A draft must never enable live posting.');
    }

    public function test_disabled_advanced_accounting_is_never_ready(): void
    {
        config(['double-entry.enabled' => false]);
        $this->assertInstanceOf(NullAdvancedAccountingGateway::class, app(AdvancedAccountingGateway::class));
        $this->assertFalse(app(AdvancedAccountingGateway::class)->readyForLivePosting());
    }

    public function test_opening_post_makes_advanced_ready_and_is_idempotent(): void
    {
        $service = app(\App\Services\Accounting\OpeningBalanceService::class);
        $cash = $this->accountId(\Softmit\DoubleEntry\Support\AccountRole::CASH);
        $capital = $this->accountId(\Softmit\DoubleEntry\Support\AccountRole::OWNER_CAPITAL);
        $lines = [
            ['account_id' => $cash, 'debit' => '100.00', 'credit' => '0.00', 'description' => 'Cash'],
            ['account_id' => $capital, 'debit' => '0.00', 'credit' => '100.00', 'description' => 'Capital'],
        ];
        $service->saveDraft($lines, '2026-09-30', auth('admin')->id() ?: 1);
        $service->post($service->draft());

        $gateway = app(AdvancedAccountingGateway::class);
        $this->assertTrue($gateway->readyForLivePosting());
        $this->assertSame(1, \Softmit\DoubleEntry\Models\JournalEntry::where('posting_key', 'opening:balances')->count());
    }
}
