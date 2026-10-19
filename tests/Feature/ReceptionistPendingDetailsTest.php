<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionistPendingDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_pending_view_displays_customer_phone_number_and_tel_link()
    {
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($receptionistRole);

        $cat = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);
        $srv = Service::create([
            'name' => 'Swedish Massage',
            'category_id' => $cat->id,
            'duration_minutes' => 60,
            'price' => 120,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone_number' => '09123456789',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 120,
        ]);
        $appointment->services()->attach($srv->id, ['price_at_booking' => 120]);

        $response = $this->actingAs($receptionist)->get(route('receptionist.pending'));

        $response->assertStatus(200);
        $response->assertSee('09123456789');
        $response->assertSee('href="tel:09123456789"', false);
    }

    public function test_receptionist_pending_view_handles_missing_phone_gracefully()
    {
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($receptionistRole);

        $cat = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);
        $srv = Service::create([
            'name' => 'Aromatherapy',
            'category_id' => $cat->id,
            'duration_minutes' => 60,
            'price' => 150,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'first_name' => 'Alex',
            'last_name' => 'Smith',
            'phone_number' => 'N/A',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'pending',
            'total_price' => 150,
        ]);
        $appointment->services()->attach($srv->id, ['price_at_booking' => 150]);

        $response = $this->actingAs($receptionist)->get(route('receptionist.pending'));

        $response->assertStatus(200);
        $response->assertSee('N/A');
        $response->assertDontSee('href="tel:N/A"', false);
    }

    public function test_unauthorized_users_cannot_access_receptionist_pending_view()
    {
        // 1. Guest redirected to login
        $guestRes = $this->get(route('receptionist.pending'));
        $guestRes->assertRedirect(route('login'));

        // 2. User without receptionist role receives 401 Unauthorized via RoleMiddleware
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $customerUser = User::factory()->create();
        $customerUser->roles()->attach($customerRole);

        $customerRes = $this->actingAs($customerUser)->get(route('receptionist.pending'));
        $customerRes->assertStatus(401);
    }
}
