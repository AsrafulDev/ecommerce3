<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\FrontendController;
use App\Models\IncompleteOrder;
use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Tests\TestCase;

/**
 * Incomplete orders are grouped per CLIENT (session), not per attempt.
 *
 * Before this, `updateOrCreate` keyed on phone+address, so one visitor retrying
 * from the same browser with three different numbers produced three separate
 * leads. Now one session owns one row and every distinct number is kept in the
 * `attempts` history.
 *
 * The write path is driven through the controller with a session we control:
 * the test client's session cookie cannot be pinned for this app (EncryptCookies
 * fails to decrypt it and nulls the value), so going over HTTP would hand every
 * request a fresh session and mask the very bug under test.
 */
class IncompleteOrderSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
    }

    /** Session ids must be exactly 40 alphanumeric chars (Store::isValidId). */
    private function sessionId(string $seed): string
    {
        return hash('sha1', 'incomplete-lead-'.$seed); // sha1 → 40 hex chars
    }

    /**
     * Submit one abandoned-checkout lead as the given session.
     */
    private function submitLead(string $sessionId, string $phone, string $address = 'Mirpur, Dhaka'): void
    {
        $session = new Store('test_session', new ArraySessionHandler(120));
        $session->setId($sessionId);
        $session->start();

        $request = Request::create(route('incomplete.order.store'), 'POST', [
            'name'         => 'Abandoned Buyer',
            'phone'        => $phone,
            'address'      => $address,
            'items'        => [['id' => 1, 'name' => 'Some Product', 'qty' => 1, 'price' => 200]],
            'total_amount' => 200,
        ]);
        $request->setLaravelSession($session);

        $response = app(FrontendController::class)->storeIncompleteOrder($request);

        $this->assertSame(200, $response->getStatusCode(), 'lead should be accepted');

        $session->save();
    }

    public function test_same_session_trying_three_numbers_creates_one_row_with_three_attempts(): void
    {
        $session = $this->sessionId('three-numbers');

        $this->submitLead($session, '01711111111');
        $this->submitLead($session, '01722222222');
        $this->submitLead($session, '01733333333');

        $this->assertSame(1, IncompleteOrder::count(), 'one session must own exactly one row');

        $row = IncompleteOrder::first();
        $this->assertSame($session, $row->session_id, 'session id must be recorded');

        $numbers = collect($row->attempts)->pluck('phone')->all();
        $this->assertCount(3, $numbers, 'all three numbers must be retained');
        $this->assertEqualsCanonicalizing(['01711111111', '01722222222', '01733333333'], $numbers);

        // The row's own columns track the most recent attempt.
        $this->assertSame('01733333333', $row->phone);
    }

    public function test_repeating_the_same_number_does_not_duplicate_the_attempt(): void
    {
        $session = $this->sessionId('repeat-number');

        $this->submitLead($session, '01711111111');
        $this->submitLead($session, '01711111111');

        $this->assertSame(1, IncompleteOrder::count());
        $this->assertCount(
            1,
            IncompleteOrder::first()->attempts,
            'same phone+address must not be recorded twice'
        );
    }

    public function test_same_number_at_a_different_address_is_kept_as_its_own_attempt(): void
    {
        $session = $this->sessionId('two-addresses');

        $this->submitLead($session, '01711111111', 'Mirpur, Dhaka');
        $this->submitLead($session, '01711111111', 'Uttara, Dhaka');

        $this->assertSame(1, IncompleteOrder::count());
        $this->assertCount(2, IncompleteOrder::first()->attempts);
    }

    public function test_different_sessions_create_different_rows(): void
    {
        $this->submitLead($this->sessionId('client-a'), '01711111111');
        $this->submitLead($this->sessionId('client-b'), '01799999999');

        $this->assertSame(2, IncompleteOrder::count());
    }

    public function test_admin_list_shows_one_row_per_client_with_all_its_numbers(): void
    {
        $this->submitLead($this->sessionId('client-a'), '01711111111');
        $this->submitLead($this->sessionId('client-a'), '01722222222');
        $this->submitLead($this->sessionId('client-b'), '01799999999');

        $res = $this->get(route('admin.incomplete-orders.index'));
        $res->assertStatus(200);

        $html = $res->getContent();
        $this->assertStringContainsString('unique clients', $html);

        // The first client's other number must still be visible as a badge.
        $this->assertStringContainsString('01711111111', $html);
        $this->assertStringContainsString('01722222222', $html);
    }

    public function test_admin_can_filter_by_session(): void
    {
        $keep = $this->sessionId('session-keep');
        $this->submitLead($keep, '01711111111');

        $this->submitLead($this->sessionId('session-drop'), '01799999999');

        $this->assertSame(2, IncompleteOrder::count());

        $res = $this->get(route('admin.incomplete-orders.index', ['session' => $keep]));
        $res->assertStatus(200);

        $html = $res->getContent();
        $this->assertStringContainsString('Showing a single session', $html);
        $this->assertStringNotContainsString('01799999999', $html, 'the other session must be filtered out');
    }

    public function test_keyword_search_finds_a_client_by_phone(): void
    {
        $this->submitLead($this->sessionId('keyword-keep'), '01755555555');
        $this->submitLead($this->sessionId('keyword-drop'), '01766666666');

        $res = $this->get(route('admin.incomplete-orders.index', ['keyword' => '01755555555']));
        $res->assertStatus(200);

        $html = $res->getContent();
        $this->assertStringContainsString('01755555555', $html);
        $this->assertStringNotContainsString('01766666666', $html, 'non-matching client must be excluded');
    }

    public function test_legacy_rows_without_a_session_collapse_by_phone(): void
    {
        // Simulate rows saved before session tracking existed.
        foreach ([1, 2] as $i) {
            IncompleteOrder::create([
                'name' => 'Legacy', 'phone' => '01777777777', 'address' => 'Dhaka',
                'items' => [], 'total_amount' => 100, 'session_id' => null,
            ]);
        }

        $this->assertSame(2, IncompleteOrder::count());

        $res = $this->get(route('admin.incomplete-orders.index'));
        $res->assertStatus(200);

        // Grouped by the phone fallback → a single client row.
        $html = $res->getContent();
        $this->assertSame(
            1,
            substr_count($html, '01777777777'),
            'duplicate legacy rows must collapse to one client row'
        );
    }
}
