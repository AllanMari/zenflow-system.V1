<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffCommissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffA;
    protected User $staffB;
    protected User $admin;
    protected Customer $customer;
    protected ServiceCategory $category;
    protected Service $serviceSingle;
    protected Service $serviceCouple;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $this->staffA = User::factory()->create([
            'username' => 'therapist_ana',
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'is_active' => true,
        ]);
        $this->staffA->roles()->attach($staffRole);

        $this->staffB = User::factory()->create([
            'username' => 'therapist_maria',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'is_active' => true,
        ]);
        $this->staffB->roles()->attach($staffRole);

        $this->admin = User::factory()->create([
            'username' => 'admin_test',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'is_active' => true,
        ]);
        $this->admin->roles()->attach($adminRole);

        $this->customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Client',
            'phone_number' => '09171234567',
        ]);

        $this->category = ServiceCategory::create([
            'name' => 'Massage Therapy',
            'color' => '#14776a',
            'is_active' => true,
        ]);

        $this->serviceSingle = Service::create([
            'name' => 'Signature Swedish Massage',
            'category_id' => $this->category->id,
            'price' => 1000.00,
            'duration_minutes' => 60,
            'is_active' => true,
            'participants_required' => 1,
            'therapists_required' => 1,
        ]);

        $this->serviceCouple = Service::create([
            'name' => 'Couple Aromatherapy Package',
            'category_id' => $this->category->id,
            'price' => 2000.00,
            'duration_minutes' => 90,
            'is_active' => true,
            'participants_required' => 2,
            'therapists_required' => 2,
        ]);

        $this->room = Room::create([
            'name' => 'Room 1',
            'status' => 'available',
            'is_active' => true,
        ]);
    }

    public function test_staff_can_view_commissions_page(): void
    {
        $response = $this->actingAs($this->staffA)->get(route('staff.commissions'));

        $response->assertStatus(200);
        $response->assertSee('My Earnings', false);
        $response->assertSee('Total Service Volume');
        $response->assertSee('Your Total Commission');
    }

    public function test_unauthenticated_or_non_staff_cannot_access_staff_commissions(): void
    {
        $guestResponse = $this->get(route('staff.commissions'));
        $guestResponse->assertRedirect('/login');

        // Customer cannot access
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $customerUser = User::factory()->create();
        $customerUser->roles()->attach($customerRole);

        $customerResponse = $this->actingAs($customerUser)->get(route('staff.commissions'));
        $customerResponse->assertStatus(403);
    }

    public function test_single_therapist_earns_full_30_percent_of_net_amount(): void
    {
        $today = Carbon::today('Asia/Manila')->toDateString();

        // Single completed appointment for Staff A: PHP 1,000 net -> 30% = PHP 300.00
        $appt = Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->staffA->id,
            'room_id' => $this->room->id,
            'appointment_date' => $today,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 1000.00,
            'guest_first_name' => 'Juan Dela Cruz',
        ]);
        $appt->services()->attach($this->serviceSingle->id, ['price_at_booking' => 1000.00]);
        $appt->syncStaff($this->staffA->id, []);

        Payment::create([
            'appointment_id' => $appt->id,
            'amount' => 1000.00,
            'type' => 'full',
            'payment_method' => 'cash',
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->staffA)->get(route('staff.commissions', ['range' => 'today']));

        $response->assertStatus(200);
        $response->assertSee('₱300.00'); // 30% of 1,000
        $response->assertSee('₱1,000.00'); // Net amount
        $response->assertSee('Solo (100% of pool)');
        $response->assertSee('#' . $appt->id);
    }

    public function test_multi_therapist_appointment_splits_30_percent_commission_equally(): void
    {
        $today = Carbon::today('Asia/Manila')->toDateString();

        // 2 Therapists on PHP 2,000 net appointment
        // Total 30% pool = PHP 600.00 -> Each therapist receives PHP 300.00 (50% share)
        $appt = Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->staffA->id,
            'room_id' => $this->room->id,
            'appointment_date' => $today,
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'status' => 'completed',
            'total_price' => 2000.00,
            'guest_first_name' => 'Couple VIP Guest',
        ]);
        $appt->services()->attach($this->serviceCouple->id, ['price_at_booking' => 2000.00]);
        $appt->syncStaff($this->staffA->id, [$this->staffB->id]);

        Payment::create([
            'appointment_id' => $appt->id,
            'amount' => 2000.00,
            'type' => 'full',
            'payment_method' => 'cash',
            'status' => 'paid',
        ]);

        // Staff A view
        $responseA = $this->actingAs($this->staffA)->get(route('staff.commissions', ['range' => 'today']));
        $responseA->assertStatus(200);
        $responseA->assertSee('₱300.00'); // Staff A's 50% share of 600
        $responseA->assertSee('₱600.00'); // Total 30% Pool
        $responseA->assertSee('Shared (50% split)');
        $responseA->assertSee('Shared with Maria Santos');

        // Staff B view
        $responseB = $this->actingAs($this->staffB)->get(route('staff.commissions', ['range' => 'today']));
        $responseB->assertStatus(200);
        $responseB->assertSee('₱300.00'); // Staff B's 50% share of 600
        $responseB->assertSee('₱600.00'); // Total 30% Pool
        $responseB->assertSee('Shared (50% split)');
        $responseB->assertSee('Shared with Ana Reyes');
    }

    public function test_strict_isolation_staff_can_only_see_their_own_commissions(): void
    {
        $today = Carbon::today('Asia/Manila')->toDateString();

        // Appointment 1: Only Staff A assigned
        $apptA = Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->staffA->id,
            'room_id' => $this->room->id,
            'appointment_date' => $today,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'completed',
            'total_price' => 1000.00,
            'guest_first_name' => 'Client for Ana Only',
        ]);
        $apptA->services()->attach($this->serviceSingle->id, ['price_at_booking' => 1000.00]);
        $apptA->syncStaff($this->staffA->id, []);

        // Appointment 2: Only Staff B assigned
        $apptB = Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->staffB->id,
            'room_id' => $this->room->id,
            'appointment_date' => $today,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => 'completed',
            'total_price' => 1500.00,
            'guest_first_name' => 'Client for Maria Only',
        ]);
        $apptB->services()->attach($this->serviceSingle->id, ['price_at_booking' => 1500.00]);
        $apptB->syncStaff($this->staffB->id, []);

        // Staff A inspects commissions
        $responseA = $this->actingAs($this->staffA)->get(route('staff.commissions', ['range' => 'today']));
        $responseA->assertStatus(200);
        $responseA->assertSee('Client for Ana Only');
        $responseA->assertDontSee('Client for Maria Only');

        // Staff B inspects commissions
        $responseB = $this->actingAs($this->staffB)->get(route('staff.commissions', ['range' => 'today']));
        $responseB->assertStatus(200);
        $responseB->assertSee('Client for Maria Only');
        $responseB->assertDontSee('Client for Ana Only');
    }

    public function test_cancelled_and_no_show_appointments_yield_zero_commission(): void
    {
        $today = Carbon::today('Asia/Manila')->toDateString();

        // Cancelled appointment
        Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->staffA->id,
            'room_id' => $this->room->id,
            'appointment_date' => $today,
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'status' => 'cancelled',
            'total_price' => 1000.00,
            'guest_first_name' => 'Cancelled Client',
        ]);

        // No-show appointment
        Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->staffA->id,
            'room_id' => $this->room->id,
            'appointment_date' => $today,
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'status' => 'no_show',
            'total_price' => 1000.00,
            'guest_first_name' => 'No Show Client',
        ]);

        $response = $this->actingAs($this->staffA)->get(route('staff.commissions', ['range' => 'today']));

        $response->assertStatus(200);
        $response->assertDontSee('Cancelled Client');
        $response->assertDontSee('No Show Client');
        $response->assertSee('No Commission Records Found');
    }

    public function test_staff_can_export_their_own_commission_csv(): void
    {
        $today = Carbon::today('Asia/Manila')->toDateString();

        $appt = Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->staffA->id,
            'room_id' => $this->room->id,
            'appointment_date' => $today,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 1000.00,
            'guest_first_name' => 'CSV Guest Test',
        ]);
        $appt->services()->attach($this->serviceSingle->id, ['price_at_booking' => 1000.00]);
        $appt->syncStaff($this->staffA->id, []);

        $response = $this->actingAs($this->staffA)->get(route('staff.commissions.csv', ['range' => 'today']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();

        $this->assertStringContainsString('THERAPIST COMMISSION STATEMENT', $content);
        $this->assertStringContainsString('Ana Reyes', $content);
        $this->assertStringContainsString('CSV Guest Test', $content);
        $this->assertStringContainsString('300.00', $content);
    }

    public function test_daily_weekly_monthly_yearly_and_custom_date_filtering(): void
    {
        $today = Carbon::today('Asia/Manila');
        $todayStr = $today->toDateString();
        $pastMonthStr = $today->copy()->subMonths(2)->startOfMonth()->toDateString();

        // Appointment today
        $apptToday = Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->staffA->id,
            'room_id' => $this->room->id,
            'appointment_date' => $todayStr,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 1000.00,
            'guest_first_name' => 'Session Today',
        ]);
        $apptToday->services()->attach($this->serviceSingle->id, ['price_at_booking' => 1000.00]);
        $apptToday->syncStaff($this->staffA->id, []);

        // Appointment 2 months ago
        $apptPast = Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->staffA->id,
            'room_id' => $this->room->id,
            'appointment_date' => $pastMonthStr,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 1000.00,
            'guest_first_name' => 'Session Past Month',
        ]);
        $apptPast->services()->attach($this->serviceSingle->id, ['price_at_booking' => 1000.00]);
        $apptPast->syncStaff($this->staffA->id, []);

        // Filter: Today
        $resToday = $this->actingAs($this->staffA)->get(route('staff.commissions', ['range' => 'today']));
        $resToday->assertStatus(200);
        $resToday->assertSee('Session Today');
        $resToday->assertDontSee('Session Past Month');

        // Filter: This Month
        $resMonth = $this->actingAs($this->staffA)->get(route('staff.commissions', ['range' => 'this_month']));
        $resMonth->assertStatus(200);
        $resMonth->assertSee('Session Today');
        $resMonth->assertDontSee('Session Past Month');

        // Filter: This Year (both are in current year if within same year)
        $resYear = $this->actingAs($this->staffA)->get(route('staff.commissions', ['range' => 'this_year']));
        $resYear->assertStatus(200);
        $resYear->assertSee('Session Today');

        // Filter: Custom Flatpickr Range matching past month
        $resCustom = $this->actingAs($this->staffA)->get(route('staff.commissions', [
            'date_from' => $pastMonthStr,
            'date_to' => $pastMonthStr,
        ]));
        $resCustom->assertStatus(200);
        $resCustom->assertSee('Session Past Month');
        $resCustom->assertDontSee('Session Today');
    }
}
