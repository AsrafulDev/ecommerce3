<?php

namespace Tests\Feature\Vat\Challan;

use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Shipping;
use App\Models\User;
use App\Services\Vat\Challan\ChallanEligibility;
use App\Services\Vat\Challan\ChallanIssuer;
use App\Services\Vat\Challan\ChallanRules;
use App\Services\Vat\VatCreditNoteService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\VatChallanFixtures;
use Tests\TestCase;

/**
 * Which sales can carry a Mushak 6.3 challan, and why the others cannot. Every blocker is checked on its own, so a
 * refused sale always has a reason that names the fix.
 */
class ChallanEligibilityTest extends TestCase
{
    use RefreshDatabase;
    use VatChallanFixtures;

    protected function tearDown(): void
    {
        // Printed copies are written to disk and the database rolls back, so remove the files this test issued.
        foreach (\App\Models\VatChallan::whereNotNull('pdf_path')->pluck('pdf_path') as $path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($path);
        }
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        $this->seedSeller();
    }

    private function eligible(Order $order): array
    {
        return app(ChallanEligibility::class)->inspect($order->fresh());
    }

    private function goodSale(string $invoice = 'INV-E-1'): Order
    {
        return $this->sale($invoice, [[$this->challanProduct('Shirt'), 2, 400.00, 60.00, 15]]);
    }

    public function test_a_delivered_sale_with_valid_bins_and_recorded_vat_is_eligible(): void
    {
        $inspection = $this->eligible($this->goodSale());

        $this->assertTrue($inspection['eligible'], implode(' | ', $inspection['blockers']));
        $this->assertSame([], $inspection['blockers']);
        $this->assertSame(400.0, $inspection['total_value']);
        $this->assertSame(60.0, $inspection['total_vat']);
        $this->assertSame(1, $inspection['lines']);
    }

    public function test_a_completed_sale_is_eligible_as_well(): void
    {
        $order = $this->sale('INV-E-2', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]], 'completed');

        $this->assertTrue($this->eligible($order)['eligible']);
    }

    public function test_a_sale_that_is_not_delivered_yet_is_refused_with_its_status(): void
    {
        $order = $this->sale('INV-E-3', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]], 'pending');

        $inspection = $this->eligible($order);

        $this->assertFalse($inspection['eligible']);
        $this->assertStringContainsString('Pending', implode(' ', $inspection['blockers']));
    }

    public function test_a_cancelled_or_returned_sale_is_refused(): void
    {
        foreach (['cancelled', 'returned'] as $status) {
            $order = $this->sale('INV-E-'.$status, [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]], $status);

            $this->assertFalse($this->eligible($order)['eligible'], "a {$status} sale must not be challaned");
        }
    }

    public function test_a_missing_buyer_bin_is_refused_with_the_fix(): void
    {
        $order = $this->sale('INV-E-4', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]], 'delivered', ['buyer_bin' => null]);

        $inspection = $this->eligible($order);

        $this->assertFalse($inspection['eligible']);
        $this->assertContains("The buyer's BIN is missing or is not 12 digits. Enter it for this sale before issuing.", $inspection['blockers']);
    }

    /** @dataProvider invalidBins */
    public function test_a_buyer_bin_that_is_not_twelve_digits_is_refused(string $bin): void
    {
        $order = $this->sale('INV-E-5', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]], 'delivered', ['buyer_bin' => $bin]);

        $this->assertFalse($this->eligible($order)['eligible'], "BIN '{$bin}' must be refused");
    }

    public static function invalidBins(): array
    {
        return [['12345'], ['1234567890123'], ['12345678901A'], ['12345 678901'], ['abcdefghijkl']];
    }

    public function test_a_buyer_bin_of_twelve_digits_is_accepted(): void
    {
        $this->assertTrue(ChallanRules::validBin('987654321098'));
        $this->assertTrue(ChallanRules::validBin(' 987654321098 '));
        $this->assertFalse(ChallanRules::validBin(null));
    }

    public function test_a_missing_seller_bin_is_refused(): void
    {
        GeneralSetting::query()->update(['vat_bin' => null]);

        $inspection = $this->eligible($this->goodSale());

        $this->assertFalse($inspection['eligible']);
        $this->assertStringContainsString('Your VAT registration number (BIN) is missing', implode(' ', $inspection['blockers']));
    }

    public function test_the_business_name_and_address_are_needed_on_the_challan(): void
    {
        GeneralSetting::query()->update(['vat_business_address' => null]);

        $this->assertFalse($this->eligible($this->goodSale())['eligible']);
    }

    public function test_a_sale_is_refused_while_vat_is_switched_off(): void
    {
        GeneralSetting::query()->update(['vat_enabled' => false]);

        $inspection = $this->eligible($this->goodSale());

        $this->assertFalse($inspection['eligible']);
        $this->assertContains('VAT is switched off in VAT/Tax settings.', $inspection['blockers']);
    }

    public function test_a_line_without_recorded_vat_refuses_the_whole_sale(): void
    {
        // A product with no rate gives its line no VAT snapshot (the sale's creating hook takes the rate from the product).
        $socks = $this->challanProduct('Socks');
        $socks->update(['vat_rate' => null]);
        $order = $this->sale('INV-E-6', [
            [$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15],
            [$socks->fresh(), 1, 100.00, null, null],
        ]);

        $inspection = $this->eligible($order);

        $this->assertFalse($inspection['eligible']);
        $this->assertStringContainsString('"Socks": the VAT for this line is not recorded', implode(' ', $inspection['blockers']));
    }

    public function test_a_sale_already_reversed_by_a_credit_note_is_refused(): void
    {
        $order = $this->goodSale('INV-E-7');
        app(VatCreditNoteService::class)->forOrder($order, 'Returned after the sale');

        $inspection = $this->eligible($order);

        $this->assertFalse($inspection['eligible']);
        $this->assertStringContainsString('A VAT credit note already reverses this sale', implode(' ', $inspection['blockers']));
    }

    public function test_a_sale_without_a_delivery_address_is_refused(): void
    {
        $order = $this->sale('INV-E-8', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]], 'delivered', ['shipping' => ['address' => '']]);

        $this->assertFalse($this->eligible($order)['eligible']);
    }

    public function test_a_sale_already_challaned_is_refused_and_names_the_challan(): void
    {
        $order = $this->goodSale('INV-E-9');
        $challan = app(ChallanIssuer::class)->issue($order, null);

        $inspection = $this->eligible($order);

        $this->assertFalse($inspection['eligible']);
        $this->assertContains("Challan {$challan->challan_no} is already issued for this sale.", $inspection['blockers']);
    }

    public function test_delivery_charges_and_discounts_are_warnings_and_do_not_stop_the_issue(): void
    {
        $order = $this->sale('INV-E-10', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]], 'delivered', [
            'shipping_charge' => 60, 'discount' => 20,
        ]);

        $inspection = $this->eligible($order);

        $this->assertTrue($inspection['eligible']);
        $this->assertCount(2, array_filter($inspection['warnings'], fn ($w) => str_contains($w, 'Delivery charges') || str_contains($w, 'discount')));
    }

    public function test_a_sale_with_no_lines_is_refused(): void
    {
        $order = Order::create([
            'invoice_id' => 'INV-E-11', 'amount' => 0, 'discount' => 0, 'shipping_charge' => 0,
            'customer_id' => $this->challanBuyer(), 'order_status' => 'delivered', 'buyer_bin' => '987654321098',
            'created_at' => now(),
        ]);
        Shipping::create(['order_id' => $order->id, 'customer_id' => $order->customer_id, 'name' => 'x', 'phone' => '1', 'address' => 'Dhaka', 'area' => 'x']);

        $this->assertContains('The sale has no lines.', $this->eligible($order)['blockers']);
        $this->assertSame(0, OrderDetails::where('order_id', $order->id)->count());
    }
}
