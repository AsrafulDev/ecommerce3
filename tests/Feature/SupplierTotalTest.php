<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Supplier;
use App\Models\SupplierPayment;

class SupplierTotalTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_total_paid_updates_on_payment_create_and_delete()
    {
        $supplier = Supplier::create(['name' => 'Acme', 'opening_balance' => 0, 'current_due' => 0, 'total_paid' => 0]);

        $payment = SupplierPayment::create([
            'supplier_id' => $supplier->id,
            'amount' => 150.75,
            'payment_date' => now(),
        ]);

        $supplier->refresh();
        $this->assertEquals(150.75, (float) $supplier->total_paid);

        $payment->delete();
        $supplier->refresh();
        $this->assertEquals(0.0, (float) $supplier->total_paid);
    }
}
