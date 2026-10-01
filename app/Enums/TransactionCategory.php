<?php

namespace App\Enums;

enum TransactionCategory: string
{
    case SALE = 'sale';
    case CUSTOMER_PAYMENT = 'customer_payment';
    case CUSTOMER_REFUND = 'customer_refund';
    case REFUND_REVERSAL = 'refund_reversal';
    case EXPENSE = 'expense';
    case SUPPLIER_PAYMENT = 'supplier_payment';
    case SALARY = 'salary';
    case BONUS = 'bonus';
    case OWNER_WITHDRAWAL = 'owner_withdrawal';
    case OWNER_CAPITAL = 'owner_capital';
    case OTHER_INCOME = 'other_income';
    case WARRANTY_INCOME = 'warranty_income';

    public function label(): string
    {
        return match ($this) {
            self::SALE => 'Sale',
            self::CUSTOMER_PAYMENT => 'Customer Payment',
            self::CUSTOMER_REFUND => 'Customer Refund',
            self::REFUND_REVERSAL => 'Refund Reversal',
            self::EXPENSE => 'Expense',
            self::SUPPLIER_PAYMENT => 'Supplier Payment',
            self::SALARY => 'Salary',
            self::BONUS => 'Bonus',
            self::OWNER_WITHDRAWAL => 'Owner Withdrawal',
            self::OWNER_CAPITAL => 'Owner Capital',
            self::OTHER_INCOME => 'Other Income',
            self::WARRANTY_INCOME => 'Warranty Income',
        };
    }

    public static function fromSource(?string $source): ?self
    {
        return match ($source) {
            'sale' => self::SALE,
            'refund', 'order_refund' => self::CUSTOMER_REFUND,
            'refund_reversal' => self::REFUND_REVERSAL,
            'expense' => self::EXPENSE,
            'supplier_payment' => self::SUPPLIER_PAYMENT,
            'employee_salary' => self::SALARY,
            'employee_bonus' => self::BONUS,
            'withdraw' => self::OWNER_WITHDRAWAL,
            'warranty', 'warranty_resell' => self::WARRANTY_INCOME,
            default => null,
        };
    }
}
