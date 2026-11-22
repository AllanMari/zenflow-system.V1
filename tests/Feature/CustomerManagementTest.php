<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $receptionist;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $recRole = Role::firstOrCreate(['name' => 'receptionist'], ['label' => 'Receptionist']);

        $this->admin = User::firstOrCreate(
            ['username' => 'test_admin_crm'],
            [
                'first_name' => 'Admin',
                'last_name' => 'CRM',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $this->admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $this->receptionist = User::firstOrCreate(
            ['username' => 'test_rec_crm'],
            [
                'first_name' => 'Receptionist',
                'last_name' => 'CRM',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $this->receptionist->roles()->syncWithoutDetaching([$recRole->id]);

        $this->customer = Customer::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'nickname' => 'Ria',
            'email' => 'maria@example.com',
            'phone_number' => '09171234567',
            'customer_type' => 'regular',
            'medical_notes' => 'Allergic to peppermint oil',
        ]);
    }

    public function test_admin_can_view_customers_directory()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.customers.index'));
        $response->assertStatus(200);
        $response->assertSee('Client Directory');
        $response->assertSee('Maria Santos');
        $response->assertSee('09171234567');
    }

    public function test_receptionist_can_view_customers_directory()
    {
        $response = $this->actingAs($this->receptionist)->get(route('receptionist.customers.index'));
        $response->assertStatus(200);
        $response->assertSee('Client Directory');
        $response->assertSee('Maria Santos');
    }

    public function test_search_and_filter_customers()
    {
        Customer::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'phone_number' => '09180000001',
            'email' => 'juan@example.com',
            'customer_type' => 'vip',
        ]);

        // Search by name
        $response = $this->actingAs($this->admin)->get(route('admin.customers.index', ['search' => 'Juan']));
        $response->assertStatus(200);
        $response->assertSee('Juan Dela Cruz');
        $response->assertDontSee('Maria Santos');

        // Filter by VIP
        $vipResponse = $this->actingAs($this->admin)->get(route('admin.customers.index', ['type' => 'vip']));
        $vipResponse->assertStatus(200);
        $vipResponse->assertSee('Juan Dela Cruz');
        $vipResponse->assertDontSee('Maria Santos');
    }

    public function test_show_endpoint_returns_customer_and_history_json()
    {
        $cat = ServiceCategory::create(['name' => 'Massage']);
        $service = Service::create([
            'name' => 'Swedish Massage',
            'duration_minutes' => 60,
            'price' => 700,
            'category_id' => $cat->id,
        ]);

        $appt = Appointment::create([
            'customer_id' => $this->customer->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '14:00',
            'end_time' => '15:00',
            'status' => 'completed',
            'total_price' => 700,
        ]);
        $appt->services()->attach($service->id, ['price_at_booking' => 700]);

        $response = $this->actingAs($this->admin)->get(route('admin.customers.show', $this->customer));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'customer' => [
                'id' => $this->customer->id,
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'customer_type' => 'regular',
                'medical_notes' => 'Allergic to peppermint oil',
            ],
        ]);
    }

    public function test_update_customer_profile_and_vip_status()
    {
        $response = $this->actingAs($this->admin)->put(route('admin.customers.update', $this->customer), [
            'first_name' => 'Maria Victoria',
            'last_name' => 'Santos-Cruz',
            'nickname' => 'Mavi',
            'phone_number' => '09189998888',
            'customer_type' => 'vip',
            'medical_notes' => 'Pregnancy trimester 2; avoid deep pressure',
        ]);

        $response->assertRedirect();
        $this->customer->refresh();

        $this->assertEquals('Maria Victoria', $this->customer->first_name);
        $this->assertEquals('Santos-Cruz', $this->customer->last_name);
        $this->assertEquals('vip', $this->customer->customer_type);
        $this->assertEquals('Pregnancy trimester 2; avoid deep pressure', $this->customer->medical_notes);
    }
}
