<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\RoomCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\SalesAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3DReportingAndFinancialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected Role $staffRole;
    protected ServiceCategory $serviceCat;
    protected RoomCategory $roomCat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->staffRole = Role::firstOrCreate(['name' => 'staff']);
        $this->serviceCat = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);
        $this->roomCat = RoomCategory::create([
            'name' => 'Standard Suite',
            'code' => 'STD-SUITE',
            'is_active' => true,
        ]);
    }

    protected function createAdmin(): User
    {
        $user = User::factory()->create([
            'is_active' => true,
            'password' => bcrypt('password'),
        ]);
        $user->roles()->attach($this->adminRole);
        return $user;
    }

    protected function createTherapist(string $name = 'Therapist'): User
    {
        $user = User::factory()->create([
            'first_name' => $name,
            'last_name' => 'Staff',
            'is_active' => true,
        ]);
        $user->roles()->attach($this->staffRole);

        for ($i = 0; $i <= 6; $i++) {
            WorkSchedule::create([
                'user_id' => $user->id,
                'day_of_week' => $i,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'is_day_off' => false,
            ]);
        }

        return $user;
    }

    protected function createCustomer(): Customer
    {
        $custRole = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($custRole);

        return Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone_number' => '09123456789',
            'customer_type' => 'regular',
        ]);
    }

    public function test_multi_therapist_appointment_retains_single_revenue_and_exact_30_percent_commission()
    {
        $primary = $this->createTherapist('Primary');
        $assisting = $this->createTherapist('Assisting');
        $customer = $this->createCustomer();

        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'name' => 'Signature Couple Treatment',
            'duration_minutes' => 90,
            'price' => 3000,
            'therapists_required' => 2,
            'participants_required' => 2,
            'requires_room' => false,
            'is_active' => true,
        ]);

        $date = Carbon::today();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $date,
            'start_time' => '13:00:00',
            'end_time' => '14:30:00',
            'status' => 'completed',
            'total_price' => 3000,
            'client_name' => $customer->full_name,
            'client_phone' => $customer->phone_number,
            'user_id' => $primary->id,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => 3000]);
        $appointment->syncStaff($primary->id, [$assisting->id]);

        // Single transaction of 3000
        $payment = Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => 3000,
            'type' => 'full',
            'payment_method' => 'cash',
            'paid_at' => Carbon::now(),
        ]);

        // Test SalesAnalyticsService staff breakdown
        $analyticsService = app(SalesAnalyticsService::class);
        $request = new \Illuminate\Http\Request(['status' => 'completed']);
        $dashboardData = $analyticsService->getDashboardData($request);
        $staffPerformance = $dashboardData['staffPerformance'];

        // Equal distribution: 30% commission pool divided equally among assigned therapists
        // Primary therapist: ₱1500 revenue (50%), ₱450 commission (15%)
        $primaryRecord = collect($staffPerformance)->firstWhere('id', $primary->id);
        $this->assertNotNull($primaryRecord);
        $this->assertEquals(1500, $primaryRecord['revenue']);
        $this->assertEquals(1, $primaryRecord['completed']);
        $this->assertEquals(450, $primaryRecord['commission']);

        // Assisting therapist: ₱1500 revenue (50%), ₱450 commission (15%)
        $assistingRecord = collect($staffPerformance)->firstWhere('id', $assisting->id);
        $this->assertNotNull($assistingRecord);
        $this->assertEquals(1500, $assistingRecord['revenue']);
        $this->assertEquals(1, $assistingRecord['completed']);
        $this->assertEquals(450, $assistingRecord['commission']);

        // Revenue is not duplicated: total across staff remains exactly ₱3000
        $totalRevenueAcrossStaff = array_sum(array_column($staffPerformance, 'revenue'));
        $this->assertEquals(3000, $totalRevenueAcrossStaff);
    }

    public function test_admin_appointment_report_filtering_by_staff_includes_assisting_without_duplication()
    {
        $admin = $this->createAdmin();
        $primary = $this->createTherapist('Primary');
        $assisting = $this->createTherapist('Assisting');
        $customer = $this->createCustomer();

        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'name' => 'Signature Treatment',
            'duration_minutes' => 60,
            'price' => 2000,
            'therapists_required' => 2,
            'participants_required' => 2,
            'requires_room' => false,
            'is_active' => true,
        ]);

        $date = Carbon::today('Asia/Manila')->toDateString();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $date,
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'status' => 'confirmed',
            'total_price' => 2000,
            'client_name' => $customer->full_name,
            'client_phone' => $customer->phone_number,
            'user_id' => $primary->id,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => 2000]);
        $appointment->syncStaff($primary->id, [$assisting->id]);

        // Filter by primary therapist
        $responsePrimary = $this->actingAs($admin)->get(route('admin.appointments', [
            'staff_id' => $primary->id,
        ]));
        $responsePrimary->assertOk();
        $responsePrimary->assertSee('Signature Treatment');
        $responsePrimary->assertSee('Assisting: Assisting Staff');

        // Filter by assisting therapist - must also match the appointment!
        $responseAssisting = $this->actingAs($admin)->get(route('admin.appointments', [
            'staff_id' => $assisting->id,
        ]));
        $responseAssisting->assertOk();
        $responseAssisting->assertSee('Signature Treatment');

        // Total appointments count in DB remains exactly 1
        $this->assertEquals(1, Appointment::count());
    }

    public function test_admin_delete_staff_blocked_when_assigned_as_assisting()
    {
        $admin = $this->createAdmin();
        $primary = $this->createTherapist('Primary');
        $assisting = $this->createTherapist('Assisting');
        $customer = $this->createCustomer();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today('Asia/Manila')->toDateString(),
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
            'status' => 'confirmed',
            'total_price' => 1500,
            'client_name' => $customer->full_name,
            'client_phone' => $customer->phone_number,
            'user_id' => $primary->id,
        ]);
        $appointment->syncStaff($primary->id, [$assisting->id]);

        // Attempt to delete assisting therapist
        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $assisting->id), [
            'admin_password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Assisting therapist still exists in database
        $this->assertDatabaseHas('users', ['id' => $assisting->id]);
    }
}
