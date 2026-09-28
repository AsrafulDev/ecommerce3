<?php

namespace Tests\Unit\Accounting;

use PHPUnit\Framework\TestCase;
use Softmit\DoubleEntry\Support\Money;

/**
 * Money is decimal strings at scale 2, computed with bcmath. These are the
 * arithmetic guarantees every journal, ledger and report rests on.
 */
class MoneyTest extends TestCase
{
    public function test_floats_are_normalised_at_the_boundary_so_binary_rounding_never_reaches_a_journal(): void
    {
        $this->assertSame('0.30', Money::of(0.1 + 0.2));
        $this->assertSame('1000.00', Money::of(1000));
        $this->assertSame('1234.56', Money::of('1234.567'));
        $this->assertSame('0.00', Money::of(null));
        $this->assertSame('0.00', Money::of(''));
    }

    public function test_sums_are_exact_over_many_small_amounts(): void
    {
        $pennies = array_fill(0, 1000, '0.01');

        $this->assertSame('10.00', Money::sum($pennies));
    }

    public function test_a_repeated_third_of_a_whole_still_reconciles(): void
    {
        $lines = ['33.33', '33.33', '33.34'];

        $this->assertSame('100.00', Money::sum($lines));
        $this->assertTrue(Money::equals(Money::sum($lines), '100.00'));
    }

    public function test_subtraction_and_negation_keep_the_sign(): void
    {
        $this->assertSame('-0.01', Money::subtract('0.00', '0.01'));
        $this->assertSame('-500.00', Money::negate('500.00'));
        $this->assertTrue(Money::isNegative(Money::subtract('1.00', '2.00')));
    }

    public function test_comparison_is_by_value_not_by_string_shape(): void
    {
        $this->assertTrue(Money::equals('1000.00', '1000.0'));
        $this->assertTrue(Money::isZero('0.00'));
        $this->assertTrue(Money::isPositive('0.01'));
        $this->assertSame(-1, Money::compare('0.10', '0.20'));
    }

    public static function invalid_amounts(): array
    {
        return [
            'letters'            => ['abc'],
            'thousands comma'    => ['1,000.00'],
            'double decimal'     => ['12.3.4'],
            'hex'                => ['0x1A'],
            'percent'            => ['10%'],
            'currency symbol'    => ['৳500'],
            'double minus'       => ['--5'],
        ];
    }

    /**
     * A malformed amount must never become 0.00: silently posting zero is how a
     * ledger ends up balanced but wrong.
     *
     * @dataProvider invalid_amounts
     */
    public function test_non_numeric_input_is_refused_rather_than_treated_as_zero(string $bad): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::of($bad);
    }

    public function test_surrounding_whitespace_from_a_form_field_is_tolerated(): void
    {
        $this->assertSame('10.00', Money::of('  10  '));
    }

    public function test_formatting_is_only_for_display(): void
    {
        $this->assertSame('1,234,567.89', Money::format('1234567.89'));
    }
}
