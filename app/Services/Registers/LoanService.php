<?php

namespace App\Services\Registers;

use App\Enums\TransactionCategory;
use App\Helpers\FundHelper;
use App\Models\FundTransaction;
use App\Models\Loan;
use App\Models\LoanRepayment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Loans taken and given, and their repayments. Every amount that changes hands is
 * one fund row, so the cash balance includes loans. A payment out is refused when
 * the fund cannot cover it, the same rule the purchase screen applies.
 */
class LoanService
{
    public function open(array $data, ?int $userId): Loan
    {
        $amount = round((float) $data['amount'], 2);
        $taken = $data['direction'] === Loan::TAKEN;

        // Lending draws cash out, so the check and the write run under the till lock.
        $loan = $taken
            ? $this->createLoan($data, $amount, $taken, $userId)
            : FundHelper::debitIfCovered($amount, fn () => $this->createLoan($data, $amount, $taken, $userId));

        if ($loan === null) {
            throw new RuntimeException('Not enough balance in fund to lend this amount.');
        }

        app(\App\Services\Accounting\AdvancedAccountingGateway::class)->recordLoan($loan);

        return $loan;
    }

    private function createLoan(array $data, float $amount, bool $taken, ?int $userId): Loan
    {
        return DB::transaction(function () use ($data, $amount, $taken, $userId) {
            $loan = Loan::create([
                'code' => 'TMP-'.uniqid(),
                'kind' => $data['kind'],
                'direction' => $data['direction'],
                'party_name' => $data['party_name'],
                'employee_id' => $data['employee_id'] ?? null,
                'amount' => $amount,
                'loan_date' => $data['loan_date'],
                'note' => $data['note'] ?? null,
                'created_by' => $userId,
            ]);
            $loan->update(['code' => 'LN-'.str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT)]);

            $fund = FundTransaction::create([
                'direction' => $taken ? 'in' : 'out',
                'source' => 'loan',
                'source_id' => $loan->id,
                'transaction_category' => $taken ? TransactionCategory::LOAN_TAKEN : TransactionCategory::LOAN_GIVEN,
                'amount' => $amount,
                'note' => $loan->code.' — '.$loan->party_name.($loan->note ? ': '.$loan->note : ''),
                'created_by' => $userId ?? 1,
            ]);
            $loan->update(['fund_transaction_id' => $fund->id]);

            return $loan;
        });
    }

    public function repay(Loan $loan, float $amount, string $paidOn, ?string $note, ?int $userId): LoanRepayment
    {
        $amount = round($amount, 2);
        $outstanding = $loan->outstanding();

        if ($amount <= 0 || $amount > $outstanding) {
            throw new RuntimeException("Repayment must be more than 0 and no more than the outstanding {$outstanding}.");
        }

        $taken = $loan->direction === Loan::TAKEN;

        // Repaying a loan we took draws cash out, so check and write under the till lock.
        $repayment = $taken
            ? FundHelper::debitIfCovered($amount, fn () => $this->createRepayment($loan, $amount, $paidOn, $note, $userId, $taken))
            : $this->createRepayment($loan, $amount, $paidOn, $note, $userId, $taken);

        if ($repayment === null) {
            throw new RuntimeException('Not enough balance in fund to repay this loan.');
        }

        app(\App\Services\Accounting\AdvancedAccountingGateway::class)->recordLoanRepayment($repayment, $loan);

        return $repayment;
    }

    private function createRepayment(Loan $loan, float $amount, string $paidOn, ?string $note, ?int $userId, bool $taken): LoanRepayment
    {
        return DB::transaction(function () use ($loan, $amount, $paidOn, $note, $userId, $taken) {
            $repayment = LoanRepayment::create([
                'loan_id' => $loan->id,
                'amount' => $amount,
                'paid_on' => $paidOn,
                'note' => $note,
                'created_by' => $userId,
            ]);

            $fund = FundTransaction::create([
                'direction' => $taken ? 'out' : 'in',
                'source' => 'loan_repayment',
                'source_id' => $repayment->id,
                'transaction_category' => $taken ? TransactionCategory::LOAN_REPAID : TransactionCategory::LOAN_COLLECTED,
                'amount' => $amount,
                'note' => $loan->code.' repayment — '.$loan->party_name,
                'created_by' => $userId ?? 1,
            ]);
            $repayment->update(['fund_transaction_id' => $fund->id]);

            return $repayment;
        });
    }
}
