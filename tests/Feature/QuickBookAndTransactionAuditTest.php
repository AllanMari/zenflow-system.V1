<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\SalesAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class QuickBookAndTransactionAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin']);
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole);

        $recRole = Role::firstOrCreate(['name' => 'receptionist'], ['label' => 'Receptionist']);
        $this->receptionist = User::factory()->create(['is_active' => true]);
        $this->receptionist->roles()->attach($recRole);
    }

    public function test_quick_book_lookup_distinguishes_registered_and_guest_customers()
    {
        $regUser = User::factory()->create();
        $registered = Customer::create([
            'user_id' => $regUser->id,
            'first_name' => 'Registered',
            'last_name' => 'Client',
            'phone_number' => '09171000001',
        ]);

        $guest = Customer::create([
            'user_id' => null,
            'first_name' => 'Guest',
            'last_name' => 'Visitor',
            'phone_number' => '09171000002',
        ]);

        $response = $this->actingAs($this->receptionist)
            ->getJson(route('api.customers.lookup', ['phone' => '0917100000']));

        $response->assertStatus(200);
        $items = collect($response->json());

        $regItem = $items->firstWhere('id', $registered->id);
        $guestItem = $items->firstWhere('id', $guest->id);

        $this->assertNotNull($regItem);
        $this->assertTrue($regItem['is_registered']);

        $this->assertNotNull($guestItem);
        $this->assertFalse($guestItem['is_registered']);
    }

    public function test_quick_book_view_uses_is_registered_for_classification_labels()
    {
        $response = $this->actingAs($this->receptionist)
            ->get(route('receptionist.quick-book'));

        $response->assertStatus(200);
        $response->assertSee('Registered Customer');
        $response->assertSee('Guest Customer');
        $response->assertSee('customer.id && customer.is_registered', false);
    }

    public function test_client_management_distinguishes_and_filters_registered_and_guest_customers()
    {
        $regUser = User::factory()->create();
        $registered = Customer::create([
            'user_id' => $regUser->id,
            'first_name' => 'Alice',
            'last_name' => 'Registered',
            'phone_number' => '09172000001',
        ]);

        $guest = Customer::create([
            'user_id' => null,
            'first_name' => 'Bob',
            'last_name' => 'GuestOnly',
            'phone_number' => '09172000002',
        ]);

        $allResp = $this->actingAs($this->admin)->get(route('admin.customers.index'));
        $allResp->assertStatus(200);
        $allResp->assertSee('Alice Registered');
        $allResp->assertSee('Bob GuestOnly');

        $regResp = $this->actingAs($this->admin)->get(route('admin.customers.index', ['account_type' => 'registered']));
        $regResp->assertStatus(200);
        $regResp->assertSee('Alice Registered');
        $regResp->assertDontSee('Bob GuestOnly');

        $guestResp = $this->actingAs($this->admin)->get(route('admin.customers.index', ['account_type' => 'guest']));
        $guestResp->assertStatus(200);
        $guestResp->assertSee('Bob GuestOnly');
        $guestResp->assertDontSee('Alice Registered');
    }

    public function test_transaction_log_surfaces_discount_auditor_timestamp_and_bir_invoice()
    {
        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['label' => 'Staff']);
        $therapist = User::factory()->create(['is_active' => true]);
        $therapist->roles()->attach($staffRole);

        $customer = Customer::create([
            'first_name' => 'Senior',
            'last_name' => 'Patron',
            'phone_number' => '09173000001',
        ]);

        $cat = ServiceCategory::create(['name' => 'Spa', 'is_active' => true]);
        $service = Service::create([
            'category_id' => $cat->id,
            'name' => 'Signature Massage',
            'duration_minutes' => 60,
            'price' => 1000.00,
            'is_active' => true,
        ]);

        $appliedAt = Carbon::parse('2026-10-15 14:30:00', 'Asia/Manila');
        $appt = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $therapist->id,
            'appointment_date' => '2026-10-15',
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'status' => 'completed',
            'total_price' => 800.00,
            'original_total_price' => 1000.00,
            'discount_type' => 'senior_citizen',
            'discount_percent' => 20,
            'discount_amount' => 200.00,
            'discount_applied_by' => $this->receptionist->id,
            'discount_applied_at' => $appliedAt,
            'bir_invoice_number' => '000999',
        ]);
        $appt->services()->attach($service->id, [
            'service_name' => $service->name,
            'service_duration' => 60,
            'price_at_booking' => 1000.00,
        ]);
        Payment::create([
            'appointment_id' => $appt->id,
            'amount' => 800.00,
            'type' => 'full',
            'payment_method' => 'cash',
            'paid_at' => $appliedAt,
        ]);

        $analytics = app(SalesAnalyticsService::class);
        $data = $analytics->getDashboardData(new Request([
            'period' => 'custom',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'tx_status' => 'completed',
        ]));

        $row = collect($data['txLogData']['rows'])->firstWhere('internalRef', '#SPA-' . $appt->id);
        $this->assertNotNull($row);
        $this->assertEquals('000999', $row['birInvoiceNumber']);
        $this->assertEquals('senior_citizen', $row['discountType']);
        $this->assertEquals('200.00', $row['discountAmount']);
        $this->assertNotEmpty($row['discountAppliedByName']);
        $this->assertNotEmpty($row['discountAppliedAt']);

        $fragmentResp = $this->actingAs($this->admin)->get(route('admin.sales.tx-log', [
            'period' => 'custom',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'tx_status' => 'completed',
        ]));
        $fragmentResp->assertStatus(200);
        $fragmentResp->assertSee('#SPA-' . $appt->id);
        $fragmentResp->assertSee('000999');
        $fragmentResp->assertSee('By ' . $row['discountAppliedByName']);
    }
}
