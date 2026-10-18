<?php

namespace Tests\Feature;

use App\Helpers\FundHelper;
use App\Models\Category;
use App\Models\FundTransaction;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductWarrantyTier;
use App\Models\ShippingCharge;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarrantyChallan;
use App\Models\WarrantyClaim;
use App\Models\WarrantySale;
use App\Services\StockManagementService;
use Database\Seeders\DefaultDatabaseSeeder;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Warranty claim resolution through the real admin routes: approve → receive →
 * send to supplier → supplier return (with a supplier charge) → ready →
 * deliver (with a customer charge).
 *
 * Money must move exactly once per charge: the supplier charge is an expense
 * out of the till, the customer charge is income into it, and a repeated
 * step is refused rather than booked twice.
 */
class WarrantyResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
        config(['pricing.batch_wise' => false]);
    }

    /** Sells one warranty unit through POS and files a claim against it. */
    private function fileClaimOnSoldUnit(): array
    {
        $category = Category::create(['name' => 'Audio', 'slug' => 'audio-' . uniqid(), 'status' => 1, 'parent_id' => 0]);
        $product = Product::create([
            'name' => 'Resolution Speaker', 'slug' => 'res-speaker-' . uniqid(), 'category_id' => $category->id,
            'product_code' => 'RS-' . uniqid(), 'purchase_price' => 100, 'new_price' => 200, 'stock' => 0,
            'status' => 1, 'warranty_method' => 'active',
        ]);
        app(StockManagementService::class)->stockIn($product, ['quantity' => 5, 'unit_cost' => 100]);
        FundTransaction::create(['direction' => 'in', 'source' => 'manual_add', 'amount' => 10000, 'created_by' => 1]);

        $tier = ProductWarrantyTier::create([
            'product_id' => $product->id, 'tier_name' => 'Store 1 Year', 'warranty_type' => 'store_warranty',
            'warranty_days' => 365, 'price' => 50, 'is_active' => 1, 'sort_order' => 1,
        ]);
        $shipping = ShippingCharge::create(['name' => 'Inside Dhaka', 'amount' => 60, 'status' => '1']);
        Cart::instance('pos_shopping')->add([
            'id' => $product->id, 'name' => $product->name, 'qty' => 1, 'price' => 250,
            'options' => [
                'product_id' => $product->id, 'purchase_price' => 100, 'base_price' => 200,
                'warranty_tier_id' => $tier->id, 'warranty_adjustment' => 50,
            ],
        ]);
        $this->post('/admin/order/store', [
            'name' => 'Claim Customer', 'phone' => '01733333333', 'address' => 'Uttara, Dhaka',
            'area' => $shipping->id, 'payment_type' => 'paid', 'payment_method' => 'Cash',
            'paid_amount' => 310, 'save_as_pending' => 0,
        ]);

        $order = Order::latest('id')->firstOrFail();
        $sale = WarrantySale::where('order_id', $order->id)->firstOrFail();
        $this->post(route('admin.warranty.claims.file-for-customer'), [
            'warranty_sale_id' => $sale->id,
            'issue_description' => 'Speaker crackles at high volume',
            'issue_type' => 'defect',
        ])->assertSessionHasNoErrors();

        return [$product, WarrantyClaim::where('warranty_sale_id', $sale->id)->firstOrFail()];
    }

    public function test_claim_resolves_through_supplier_and_charges_are_booked_once(): void
    {
        [$product, $claim] = $this->fileClaimOnSoldUnit();
        $supplier = Supplier::create(['name' => 'Resolution Supplier']);
        $tillAfterSale = FundHelper::balance();
        $this->assertEqualsWithDelta(10000 + 310, $tillAfterSale, 0.01);

        // ── Approve the claim ─────────────────────────────────────────────
        // A claim must be reviewed before it can be approved.
        $this->post(route('admin.warranty.claims.action', [$claim->id, 'approve']))
            ->assertSessionHas('error');
        $this->assertSame('submitted', $this->statusOf($claim), 'approving an unreviewed claim is refused');

        $this->post(route('admin.warranty.claims.action', [$claim->id, 'review']))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->post(route('admin.warranty.claims.action', [$claim->id, 'approve']), ['note' => 'Valid defect'])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('approved', $this->statusOf($claim));

        // ── Receive the product from the customer (challan, no money) ─────
        $this->post(route('admin.warranty.claims.receive-product', $claim->id), [
            'condition' => 'Cone cracked, cabinet intact',
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, WarrantyChallan::where('warranty_claim_id', $claim->id)->where('challan_type', 'receive')->count());

        // A second receive is refused: one receive challan per claim.
        $this->post(route('admin.warranty.claims.receive-product', $claim->id), [
            'condition' => 'Again',
        ]);
        $this->assertSame(1, WarrantyChallan::where('warranty_claim_id', $claim->id)->where('challan_type', 'receive')->count());

        // ── Send to supplier (challan, no money) ──────────────────────────
        $this->post(route('admin.warranty.claims.send-to-supplier', $claim->id), [
            'supplier_id' => $supplier->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, WarrantyChallan::where('warranty_claim_id', $claim->id)->where('challan_type', 'send_to_supplier')->count());

        // ── Supplier returns it repaired, charging us 80 ──────────────────
        $this->post(route('admin.warranty.claims.supplier-return', $claim->id), [
            'return_type'    => 'repaired',
            'supplier_charge' => 80,
            'add_to_expenses' => 1,
        ])->assertSessionHasNoErrors();

        $expense = Expense::where('category', 'warranty')->firstOrFail();
        $this->assertEqualsWithDelta(80.0, (float) $expense->amount, 0.01, 'supplier charge is booked as an expense');
        $this->assertEqualsWithDelta($tillAfterSale - 80, FundHelper::balance(), 0.01, 'supplier charge leaves the till once');

        // Repeating the supplier return must not charge again.
        $this->post(route('admin.warranty.claims.supplier-return', $claim->id), [
            'return_type'    => 'repaired',
            'supplier_charge' => 80,
            'add_to_expenses' => 1,
        ]);
        $this->assertSame(1, Expense::where('category', 'warranty')->count(), 'a repeated supplier return must not book a second charge');
        $this->assertEqualsWithDelta($tillAfterSale - 80, FundHelper::balance(), 0.01);

        // ── Ready, then deliver with a customer charge of 40 ──────────────
        $this->post(route('admin.warranty.claims.ready-for-delivery', $claim->id))->assertSessionHasNoErrors();
        $this->assertSame('ready_for_delivery', $this->statusOf($claim));

        $this->post(route('admin.warranty.claims.deliver', $claim->id), [
            'delivery_method' => 'Hand delivery',
            'customer_charge' => 40,
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(
            $tillAfterSale - 80 + 40,
            FundHelper::balance(),
            0.01,
            'customer charge comes into the till once'
        );
        $this->assertSame(1, FundTransaction::where('source', 'warranty')->where('source_id', $claim->id)->count());

        // A repeated delivery must not collect the charge again.
        $this->post(route('admin.warranty.claims.deliver', $claim->id), [
            'customer_charge' => 40,
        ]);
        $this->assertSame(1, FundTransaction::where('source', 'warranty')->where('source_id', $claim->id)->count());
        $this->assertSame(1, WarrantyChallan::where('warranty_claim_id', $claim->id)->where('challan_type', 'delivery')->count());

        // ── Final till: capital + sale − supplier charge + customer charge ─
        $this->assertEqualsWithDelta(10000 + 310 - 80 + 40, FundHelper::balance(), 0.01, 'final till balances');
        $in  = (float) FundTransaction::where('direction', 'in')->sum('amount');
        $out = (float) FundTransaction::where('direction', 'out')->sum('amount');
        $this->assertEqualsWithDelta($in - $out, FundHelper::balance(), 0.01, 'till equals Σin − Σout');
    }

    private function statusOf(WarrantyClaim $claim): string
    {
        $status = $claim->fresh()->status;

        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }
}
