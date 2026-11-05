<?php

namespace Tests\Feature\Vat\Challan;

use App\Models\Order;
use App\Models\User;
use App\Models\VatChallan;
use App\Models\VatChallanEvent;
use App\Models\VatCreditNote;
use App\Services\Vat\Challan\ChallanBuyer;
use App\Services\Vat\Challan\ChallanIssuer;
use App\Services\Vat\Challan\ChallanNumberer;
use App\Services\Vat\Challan\ChallanPdf;
use App\Services\Vat\Challan\ChallanRefused;
use App\Services\Vat\Challan\ChallanRules;
use App\Services\Vat\Mushak91Draft;
use App\Services\Vat\VatReportService;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\VatChallanFixtures;
use Tests\TestCase;

/**
 * Issuing, numbering, totals, the stored PDF, the printed fields, voids, the buyer BIN, the audit trail and the pages.
 */
class ChallanIssueTest extends TestCase
{
    use RefreshDatabase;
    use VatChallanFixtures;

    private User $admin;

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
        $this->admin = User::first();
        $this->actingAs($this->admin, 'admin');
        $this->seedSeller();
    }

    private function year(): int
    {
        return now()->year;
    }

    /** Two lines: 2 x 200 at 15% (400 / 60) and 1 x 100 at 15% (100 / 15). */
    private function twoLineSale(string $invoice = 'INV-I-1'): Order
    {
        return $this->sale($invoice, [
            [$this->challanProduct('Tailored Shirt', 'Pcs'), 2, 400.00, 60.00, 15],
            [$this->challanProduct('Silk Tie', 'Pcs'), 1, 100.00, 15.00, 15],
        ]);
    }

    public function test_an_issued_challan_carries_its_number_lines_and_totals(): void
    {
        $challan = app(ChallanIssuer::class)->issue($this->twoLineSale(), $this->admin->id, '10.0.0.1');

        $this->assertSame('CH-'.$this->year().'-000001', $challan->challan_no);
        $this->assertSame(VatChallan::ISSUED, $challan->status);
        $this->assertSame('INV-I-1', $challan->invoice_no);
        $this->assertSame($this->admin->id, (int) $challan->issued_by);

        $this->assertCount(2, $challan->lines);
        [$first, $second] = $challan->lines->all();
        $this->assertSame(1, $first->serial);
        $this->assertSame('Tailored Shirt', $first->description);
        $this->assertSame('Pcs', $first->unit);
        $this->assertSame(2.0, (float) $first->quantity);
        $this->assertSame(200.0, (float) $first->unit_value);
        $this->assertSame(400.0, (float) $first->line_value);
        $this->assertSame(15.0, (float) $first->vat_rate);
        $this->assertSame(60.0, (float) $first->vat_amount);
        $this->assertSame(460.0, (float) $first->line_total);
        $this->assertNull($first->sd_amount, 'supplementary duty is not applicable, so it is not printed as zero');
        $this->assertSame(2, $second->serial);
    }

    public function test_totals_are_the_sum_of_the_lines_with_vat_worked_out_exactly(): void
    {
        $challan = app(ChallanIssuer::class)->issue($this->twoLineSale('INV-I-2'), null);

        $this->assertSame('500.00', number_format((float) $challan->total_value, 2, '.', ''));
        $this->assertSame('75.00', number_format((float) $challan->total_vat, 2, '.', ''));
        $this->assertSame('575.00', number_format((float) $challan->total_with_tax, 2, '.', ''));
        $this->assertSame(round($challan->lines->sum('line_value'), 2), (float) $challan->total_value);
        $this->assertSame(round($challan->lines->sum('vat_amount'), 2), (float) $challan->total_vat);
    }

    public function test_the_brand_name_is_part_of_the_description_where_the_product_has_one(): void
    {
        $brand = \App\Models\Brand::create(['name' => 'Aarong', 'slug' => 'aarong-'.uniqid(), 'status' => 1]);
        $product = $this->challanProduct('Cotton Panjabi');
        $product->update(['brand_id' => $brand->id]);
        $order = $this->sale('INV-I-3', [[$product->fresh(), 1, 500.00, 75.00, 15]]);

        $challan = app(ChallanIssuer::class)->issue($order, null);

        $this->assertSame('Cotton Panjabi (Aarong)', $challan->lines->first()->description);
    }

    public function test_the_stored_pdf_matches_the_hash_recorded_at_issue(): void
    {
        $challan = app(ChallanIssuer::class)->issue($this->twoLineSale('INV-I-4'), null);

        $this->assertNotNull($challan->pdf_path);
        $this->assertSame(64, strlen((string) $challan->pdf_sha256));
        $bytes = app(ChallanPdf::class)->stored($challan);
        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertSame($challan->pdf_sha256, hash('sha256', $bytes));
    }

    public function test_a_stored_pdf_that_no_longer_matches_is_refused(): void
    {
        $challan = app(ChallanIssuer::class)->issue($this->twoLineSale('INV-I-5'), null);
        \Illuminate\Support\Facades\Storage::disk('local')->put($challan->pdf_path, '%PDF-tampered');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('does not match the hash recorded at issue');
        app(ChallanPdf::class)->stored($challan);
    }

    public function test_the_printed_fields_and_totals_are_the_recorded_ones(): void
    {
        $challan = app(ChallanIssuer::class)->issue($this->twoLineSale('INV-I-6'), null);

        $html = view('backEnd.vat.challans.pdf', ['challan' => $challan->fresh('lines')])->render();

        foreach ([
            'মূসক-৬.৩', 'কর চালানপত্র', 'নিবন্ধিত ব্যক্তির নাম:', 'নিবন্ধিত ব্যক্তির বিআইএন:', 'চালানপত্র ইস্যুর ঠিকানা:',
            'ক্রেতার নাম:', 'ক্রেতার বিআইএন:', 'সরবরাহের গন্তব্যস্থল:', 'চালানপত্র নম্বর:', 'ইস্যুর তারিখ:', 'ইস্যুর সময়:',
            'সর্বমোট', 'প্রতিষ্ঠান কর্তৃপক্ষের দায়িত্বপ্রাপ্ত ব্যক্তির নাম:', 'পদবী :', 'স্বাক্ষর:', 'সীল:',
        ] as $label) {
            $this->assertStringContainsString($label, $html, "the form label '{$label}' is printed");
        }

        $this->assertStringContainsString($challan->challan_no, $html);
        $this->assertStringContainsString('Curl Bazar Ltd', $html);
        $this->assertStringContainsString('123456789012', $html, 'seller BIN');
        $this->assertStringContainsString('987654321098', $html, 'buyer BIN');
        $this->assertStringContainsString('Gulshan 2, Dhaka', $html, 'destination of supply');
        $this->assertStringContainsString('Tailored Shirt', $html);
        $this->assertStringContainsString('Silk Tie', $html);
        $this->assertStringContainsString('400.00', $html);
        $this->assertStringContainsString('575.00', $html, 'grand total incl. VAT');
        $this->assertStringContainsString('75.00', $html, 'total VAT');
        $this->assertStringContainsString('সকল প্রকার কর ব্যতীত মূল্য', $html, 'footnote 1');
    }

    public function test_a_second_issue_for_the_same_sale_is_refused_and_writes_nothing(): void
    {
        $order = $this->twoLineSale('INV-I-7');
        $first = app(ChallanIssuer::class)->issue($order, null);

        try {
            app(ChallanIssuer::class)->issue($order, null);
            $this->fail('a second challan for the same sale must be refused');
        } catch (ChallanRefused $e) {
            $this->assertStringContainsString($first->challan_no, $e->getMessage());
        }

        $this->assertSame(1, VatChallan::where('order_id', $order->id)->count());
        $this->assertSame(1, (int) DB::table('vat_document_sequences')->where('series', ChallanRules::SERIES)->value('last_number'));
    }

    public function test_the_database_itself_refuses_a_second_issued_challan_for_one_sale(): void
    {
        $order = $this->twoLineSale('INV-I-8');
        $first = app(ChallanIssuer::class)->issue($order, null);

        $this->expectException(QueryException::class);
        VatChallan::create([
            'challan_no' => 'CH-X-000999', 'status' => VatChallan::ISSUED, 'order_id' => $order->id,
            'issued_order_id' => $order->id, 'issue_date' => now()->toDateString(), 'issue_time' => '10:00:00',
            'issued_at' => now(), 'seller_bin' => $first->seller_bin, 'buyer_bin' => $first->buyer_bin,
        ]);
    }

    public function test_a_refused_issue_takes_no_number_so_the_next_challan_has_no_gap(): void
    {
        $bad = $this->sale('INV-I-9', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]], 'pending');
        try {
            app(ChallanIssuer::class)->issue($bad, null);
            $this->fail('a pending sale must be refused');
        } catch (ChallanRefused) {
            // expected
        }

        $challan = app(ChallanIssuer::class)->issue($this->twoLineSale('INV-I-10'), null);

        $this->assertSame('CH-'.$this->year().'-000001', $challan->challan_no);
    }

    public function test_voiding_keeps_the_number_and_lets_the_sale_be_challaned_again_under_a_new_number(): void
    {
        $order = $this->twoLineSale('INV-I-11');
        $first = app(ChallanIssuer::class)->issue($order, null);

        $voided = app(ChallanIssuer::class)->void($first, 'Printed with the wrong buyer name', $this->admin->id);

        $this->assertSame(VatChallan::VOIDED, $voided->status);
        $this->assertNull($voided->issued_order_id);
        $this->assertSame('Printed with the wrong buyer name', $voided->void_reason);
        $this->assertSame($first->challan_no, VatChallan::where('id', $first->id)->value('challan_no'), 'the voided number stays on record');

        $second = app(ChallanIssuer::class)->issue($order->fresh(), null);
        $this->assertSame('CH-'.$this->year().'-000002', $second->challan_no, 'a voided number is never reused');
    }

    public function test_a_void_needs_a_reason_and_applies_once(): void
    {
        $challan = app(ChallanIssuer::class)->issue($this->twoLineSale('INV-I-12'), null);

        try {
            app(ChallanIssuer::class)->void($challan, '   ', null);
            $this->fail('a void without a reason must be refused');
        } catch (\InvalidArgumentException) {
            // expected
        }

        app(ChallanIssuer::class)->void($challan, 'Duplicate', null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('already voided');
        app(ChallanIssuer::class)->void($challan->fresh(), 'Again', null);
    }

    public function test_the_buyer_bin_is_validated_and_every_change_is_audited(): void
    {
        $order = $this->sale('INV-I-13', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]], 'delivered', ['buyer_bin' => null]);

        try {
            app(ChallanBuyer::class)->setBin($order, '12345', $this->admin->id);
            $this->fail('a short BIN must be refused');
        } catch (\InvalidArgumentException) {
            // expected
        }
        $this->assertNull($order->fresh()->buyer_bin);

        app(ChallanBuyer::class)->setBin($order, '987654321098', $this->admin->id, '10.0.0.2');

        $this->assertSame('987654321098', $order->fresh()->buyer_bin);
        $event = VatChallanEvent::where('order_id', $order->id)->where('event', 'buyer_bin_set')->firstOrFail();
        $this->assertNull($event->details['from']);
        $this->assertSame('987654321098', $event->details['to']);
        $this->assertSame($this->admin->id, (int) $event->user_id);
        $this->assertSame('10.0.0.2', $event->ip);
    }

    public function test_the_buyer_bin_cannot_change_once_a_challan_is_issued_for_the_sale(): void
    {
        $order = $this->twoLineSale('INV-I-14');
        app(ChallanIssuer::class)->issue($order, null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('its buyer BIN cannot change');
        app(ChallanBuyer::class)->setBin($order->fresh(), '111111111111', null);
    }

    public function test_the_audit_history_records_the_issue_and_each_printed_copy(): void
    {
        $challan = app(ChallanIssuer::class)->issue($this->twoLineSale('INV-I-15'), $this->admin->id, '10.0.0.3');

        $issued = VatChallanEvent::where('vat_challan_id', $challan->id)->where('event', 'issued')->firstOrFail();
        $this->assertSame($challan->challan_no, $issued->details['challan_no']);
        $this->assertSame($challan->pdf_sha256, $issued->details['pdf_sha256']);
        $this->assertSame($this->admin->id, (int) $issued->user_id);

        $this->get(route('admin.vat.challans.pdf', $challan))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $downloaded = VatChallanEvent::where('vat_challan_id', $challan->id)->where('event', 'pdf_downloaded')->firstOrFail();
        $this->assertSame($challan->pdf_sha256, $downloaded->details['pdf_sha256']);
    }

    public function test_numbers_run_in_order_within_the_year(): void
    {
        $numbers = [];
        foreach (['INV-I-16', 'INV-I-17', 'INV-I-18'] as $invoice) {
            $numbers[] = app(ChallanIssuer::class)->issue($this->twoLineSale($invoice), null)->challan_no;
        }

        $this->assertSame([
            'CH-'.$this->year().'-000001', 'CH-'.$this->year().'-000002', 'CH-'.$this->year().'-000003',
        ], $numbers);
        $this->assertSame(ChallanNumberer::format($this->year(), 4), 'CH-'.$this->year().'-000004');
    }

    public function test_a_challan_for_a_sale_that_is_later_returned_stays_issued_and_says_so(): void
    {
        $order = $this->twoLineSale('INV-I-19');
        $challan = app(ChallanIssuer::class)->issue($order, null);
        Order::whereKey($order->id)->update(['order_status' => 'returned']);

        $this->assertSame(VatChallan::ISSUED, $challan->fresh()->status);

        $this->get(route('admin.vat.challans.show', $challan))
            ->assertOk()
            ->assertSee('The sale is now')
            ->assertSee('stays as it was issued');
    }

    public function test_a_cancelled_sale_is_refused_when_it_is_challaned_by_the_route(): void
    {
        $order = $this->sale('INV-I-20', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]], 'cancelled');

        $this->post(route('admin.vat.challans.issue', $order))->assertRedirect();

        $this->assertSame(0, VatChallan::where('order_id', $order->id)->count());
    }

    public function test_the_challan_pages_list_the_sales_and_the_issued_challans(): void
    {
        $issuedOrder = $this->twoLineSale('INV-I-21');
        $challan = app(ChallanIssuer::class)->issue($issuedOrder, null);
        $readyOrder = $this->sale('INV-I-22', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]]);
        $blockedOrder = $this->sale('INV-I-23', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]], 'delivered', ['buyer_bin' => null]);

        $this->get(route('admin.vat.challans.index'))
            ->assertOk()
            ->assertSee('Mushak 6.3 challans')
            ->assertSee('Not yet approved for filing')
            ->assertSee($challan->challan_no)
            ->assertSee('Already challaned')
            ->assertSee('Issue challan')
            ->assertSee("The buyer's BIN is missing or is not 12 digits")
            ->assertSee('INV-I-22')
            ->assertSee('INV-I-23');

        $this->get(route('admin.vat.challans.show', $challan))
            ->assertOk()
            ->assertSee($challan->challan_no)
            ->assertSee('Printed copy SHA-256')
            ->assertSee('issued');
    }

    public function test_issuing_from_the_list_creates_the_challan_and_redirects_to_it(): void
    {
        $order = $this->twoLineSale('INV-I-24');

        $response = $this->post(route('admin.vat.challans.issue', $order));

        $challan = VatChallan::where('order_id', $order->id)->firstOrFail();
        $response->assertRedirect(route('admin.vat.challans.show', $challan));
    }

    public function test_bulk_issue_issues_each_eligible_sale_and_reports_the_refused_ones(): void
    {
        $ok = $this->sale('INV-I-25', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]]);
        $bad = $this->sale('INV-I-26', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]], 'pending');

        $this->post(route('admin.vat.challans.issue_selected'), ['order_ids' => [$ok->id, $bad->id]])
            ->assertRedirect();

        $this->assertSame(1, VatChallan::where('order_id', $ok->id)->count());
        $this->assertSame(0, VatChallan::where('order_id', $bad->id)->count());
    }

    public function test_a_user_who_can_only_view_cannot_issue_or_void(): void
    {
        $viewer = $this->userWith(['setting-list'], 'vat-viewer');
        $order = $this->twoLineSale('INV-I-27');
        $challan = app(ChallanIssuer::class)->issue($order, null);
        $this->actingAs($viewer, 'admin');

        $this->get(route('admin.vat.challans.index'))->assertOk();
        $this->post(route('admin.vat.challans.issue', $this->sale('INV-I-28', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]])))
            ->assertForbidden();
        $this->post(route('admin.vat.challans.void', $challan), ['reason' => 'no'])->assertForbidden();

        $this->assertSame(VatChallan::ISSUED, $challan->fresh()->status);
    }

    public function test_the_issued_challans_reconcile_with_the_vat_on_the_same_sales(): void
    {
        $this->twoLineSale('INV-I-29');
        app(ChallanIssuer::class)->issue(Order::where('invoice_id', 'INV-I-29')->firstOrFail(), null);

        $reports = app(VatReportService::class);
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();
        $checks = $reports->reconciliation($from, $to, (new Mushak91Draft())->build($from, $to, $reports));

        $challanCheck = collect($checks)->firstWhere('label', 'Mushak 6.3 challans: challan VAT = VAT on the same sales');
        $this->assertNotNull($challanCheck);
        $this->assertTrue($challanCheck['agrees']);
        $this->assertSame(75.0, $challanCheck['left']);
    }

    public function test_the_vat_reports_page_links_to_the_challans(): void
    {
        $this->get(route('admin.vat.reports'))
            ->assertOk()
            ->assertSee(route('admin.vat.challans.index'))
            ->assertSee('Mushak 6.3 challans');
    }

    public function test_the_csv_register_lists_issued_and_voided_challans_with_their_totals(): void
    {
        $challan = app(ChallanIssuer::class)->issue($this->twoLineSale('INV-I-30'), null);
        $voided = app(ChallanIssuer::class)->issue($this->sale('INV-I-31', [[$this->challanProduct('Shirt'), 1, 200.00, 30.00, 15]]), null);
        app(ChallanIssuer::class)->void($voided, 'Test void', null);

        $response = $this->get(route('admin.vat.challans.export', [
            'from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString(),
        ]))->assertOk();

        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $lines = array_values(array_filter(explode("\n", trim($response->getContent()))));
        $this->assertSame(['Challan No', 'Status', 'Issue Date', 'Invoice No', 'Buyer', 'Buyer BIN', 'Taxable Value', 'Output VAT', 'Total incl. VAT', 'Void Reason'], str_getcsv($lines[0]));
        $this->assertCount(3, $lines);
        $this->assertStringContainsString($challan->challan_no, $lines[1]);
        $issuedRow = str_getcsv($lines[1]);
        $this->assertSame($challan->challan_no, $issuedRow[0]);
        $this->assertSame('issued', $issuedRow[1]);
        $this->assertSame(['500.00', '75.00', '575.00'], array_slice($issuedRow, 6, 3));
        $this->assertStringContainsString('voided', $lines[2]);
        $this->assertStringContainsString('Test void', $lines[2]);
    }

    private function userWith(array $permissions, string $roleName): User
    {
        $user = User::forceCreate([
            'id' => max(2, (int) User::max('id') + 1),
            'name' => $roleName.' user',
            'email' => uniqid($roleName).'@test.local',
            'password' => bcrypt('secret'),
            'status' => 1,
        ]);

        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'admin']);
        $role->syncPermissions(array_map(
            fn (string $name) => Permission::where('name', $name)->where('guard_name', 'admin')->firstOrFail(),
            $permissions,
        ));
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->unsetRelation('roles');

        return $user;
    }
}
