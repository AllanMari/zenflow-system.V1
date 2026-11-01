<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SecurityAuditFixTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);
        return $user;
    }

    private function makeStaffWithSchedule(int $dayOfWeek, string $start = '08:00:00', string $end = '18:00:00'): User
    {
        $staff = $this->makeUserWithRole('staff');

        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => $start,
            'end_time' => $end,
            'is_day_off' => false,
        ]);

        return $staff;
    }

    private function makeCustomer(): Customer
    {
        $user = $this->makeUserWithRole('customer');
        return Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Alice',
            'last_name' => 'Security',
            'email' => $user->username . '@test.com',
            'phone_number' => '09123456789',
        ]);
    }

    private function makeAppointment(string $status = 'pending', ?string $date = null, ?User $staff = null): Appointment
    {
        $customer = $this->makeCustomer();
        $date = $date ?? Carbon::now('Asia/Manila')->addDays(2)->toDateString();

        $category = ServiceCategory::create(['name' => 'Body Care', 'is_active' => true]);
        $service = Service::create([
            'category_id' => $category->id,
            'name' => 'Swedish Massage',
            'duration_minutes' => 60,
            'price' => 800,
            'requires_room' => false,
            'requires_prepayment' => false,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $staff?->id,
            'appointment_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => $status,
            'total_price' => 800,
        ]);

        $appointment->services()->attach($service->id, ['price_at_booking' => 800]);

        return $appointment;
    }

    // =========================================================================
    // 1. Service Popularity Report Access Control
    // =========================================================================

    public function test_admin_can_access_admin_service_popularity_report_and_pdf()
    {
        $admin = $this->makeUserWithRole('admin');

        $resWeb = $this->actingAs($admin)->get(route('admin.service-popularity'));
        $resWeb->assertStatus(200);

        $resPdf = $this->actingAs($admin)->get(route('admin.service-popularity.pdf'));
        $resPdf->assertStatus(200);
    }

    public function test_receptionist_can_access_receptionist_service_popularity_report_and_pdf()
    {
        $receptionist = $this->makeUserWithRole('receptionist');

        $resWeb = $this->actingAs($receptionist)->get(route('receptionist.service-popularity'));
        $resWeb->assertStatus(200);

        $resPdf = $this->actingAs($receptionist)->get(route('receptionist.service-popularity.pdf'));
        $resPdf->assertStatus(200);
    }

    public function test_receptionist_cannot_access_admin_service_popularity_report()
    {
        $receptionist = $this->makeUserWithRole('receptionist');

        $resWeb = $this->actingAs($receptionist)->get(route('admin.service-popularity'));
        $resWeb->assertStatus(403);

        $resPdf = $this->actingAs($receptionist)->get(route('admin.service-popularity.pdf'));
        $resPdf->assertStatus(403);
    }

    public function test_customer_and_staff_cannot_access_service_popularity_reports()
    {
        $customer = $this->makeUserWithRole('customer');
        $staff = $this->makeUserWithRole('staff');

        // Customer attempts
        $this->actingAs($customer)->get(route('admin.service-popularity'))->assertStatus(403);
        $this->actingAs($customer)->get(route('admin.service-popularity.pdf'))->assertStatus(403);
        $this->actingAs($customer)->get(route('receptionist.service-popularity'))->assertStatus(403);
        $this->actingAs($customer)->get(route('receptionist.service-popularity.pdf'))->assertStatus(403);

        // Staff attempts
        $this->actingAs($staff)->get(route('admin.service-popularity'))->assertStatus(403);
        $this->actingAs($staff)->get(route('admin.service-popularity.pdf'))->assertStatus(403);
        $this->actingAs($staff)->get(route('receptionist.service-popularity'))->assertStatus(403);
        $this->actingAs($staff)->get(route('receptionist.service-popularity.pdf'))->assertStatus(403);
    }

    public function test_unauthenticated_user_redirected_to_login_on_service_popularity_reports()
    {
        $this->get(route('admin.service-popularity'))->assertRedirect(route('login'));
        $this->get(route('admin.service-popularity.pdf'))->assertRedirect(route('login'));
        $this->get(route('receptionist.service-popularity'))->assertRedirect(route('login'));
        $this->get(route('receptionist.service-popularity.pdf'))->assertRedirect(route('login'));
    }

    // =========================================================================
    // 2. Role Middleware HTTP Status Code Checks
    // =========================================================================

    public function test_role_middleware_returns_403_for_authenticated_user_lacking_role()
    {
        $customer = $this->makeUserWithRole('customer');

        // Receptionist route accessed by customer -> 403 Forbidden
        $response = $this->actingAs($customer)->get(route('receptionist.dashboard'));
        $response->assertStatus(403);
    }

    public function test_role_middleware_redirects_unauthenticated_guest_to_login()
    {
        $response = $this->get(route('receptionist.dashboard'));
        $response->assertRedirect(route('login'));
    }

    // =========================================================================
    // 3. Rescheduling & Reassignment Status Guards & Happy Path
    // =========================================================================

    public function test_reassign_staff_rejects_completed_cancelled_no_show_appointments()
    {
        $receptionist = $this->makeUserWithRole('receptionist');
        $staff = $this->makeUserWithRole('staff');

        foreach (['completed', 'cancelled', 'no_show'] as $invalidStatus) {
            $appointment = $this->makeAppointment($invalidStatus);

            $response = $this->actingAs($receptionist)->post(
                route('receptionist.reassign', ['appointmentId' => $appointment->id]),
                ['staff_id' => $staff->id]
            );

            $response->assertRedirect();
            $response->assertSessionHas('error');
            $this->assertSame($invalidStatus, $appointment->fresh()->status);
            $this->assertNull($appointment->fresh()->user_id);
        }
    }

    public function test_reschedule_rejects_completed_cancelled_no_show_appointments()
    {
        $receptionist = $this->makeUserWithRole('receptionist');
        $newDate = Carbon::now('Asia/Manila')->addDays(4)->toDateString();

        foreach (['completed', 'cancelled', 'no_show'] as $invalidStatus) {
            $appointment = $this->makeAppointment($invalidStatus);
            $originalDate = $appointment->appointment_date->toDateString();

            $response = $this->actingAs($receptionist)->post(
                route('receptionist.reschedule', ['appointmentId' => $appointment->id]),
                [
                    'appointment_date' => $newDate,
                    'start_time' => '13:00',
                ]
            );

            $response->assertRedirect();
            $response->assertSessionHas('error');
            $this->assertSame($invalidStatus, $appointment->fresh()->status);
            $this->assertSame($originalDate, $appointment->fresh()->appointment_date->toDateString());
        }
    }

    public function test_reassign_staff_succeeds_for_valid_pending_and_confirmed_appointments()
    {
        $receptionist = $this->makeUserWithRole('receptionist');

        $date = Carbon::now('Asia/Manila')->next(Carbon::FRIDAY)->toDateString();
        $dow = Carbon::parse($date)->dayOfWeek;

        $targetStaff = $this->makeStaffWithSchedule($dow, '08:00:00', '18:00:00');

        foreach (['pending', 'confirmed'] as $validStatus) {
            $appointment = $this->makeAppointment($validStatus, $date);

            Cache::flush();

            $response = $this->actingAs($receptionist)->post(
                route('receptionist.reassign', ['appointmentId' => $appointment->id]),
                ['staff_id' => $targetStaff->id]
            );

            $response->assertRedirect();
            $response->assertSessionHas('success');
            $this->assertSame($targetStaff->id, $appointment->fresh()->user_id);
            $this->assertSame($validStatus, $appointment->fresh()->status);
        }
    }

    public function test_reschedule_succeeds_for_valid_pending_and_confirmed_appointments()
    {
        $receptionist = $this->makeUserWithRole('receptionist');

        $origDate = Carbon::now('Asia/Manila')->next(Carbon::THURSDAY)->toDateString();
        $origDow = Carbon::parse($origDate)->dayOfWeek;

        $newDate = Carbon::now('Asia/Manila')->next(Carbon::FRIDAY)->toDateString();
        $newDow = Carbon::parse($newDate)->dayOfWeek;

        $staff = $this->makeUserWithRole('staff');
        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $origDow,
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
            'is_day_off' => false,
        ]);
        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $newDow,
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
            'is_day_off' => false,
        ]);

        foreach (['pending', 'confirmed'] as $validStatus) {
            $appointment = $this->makeAppointment($validStatus, $origDate, $staff);

            Cache::flush();

            $response = $this->actingAs($receptionist)->post(
                route('receptionist.reschedule', ['appointmentId' => $appointment->id]),
                [
                    'appointment_date' => $newDate,
                    'start_time' => '14:00',
                ]
            );

            $response->assertRedirect();
            $response->assertSessionHas('success');

            $fresh = $appointment->fresh();
            $this->assertSame($newDate, $fresh->appointment_date->toDateString());
            $this->assertStringStartsWith('14:00', $fresh->start_time);
            $this->assertSame($validStatus, $fresh->status);
        }
    }

    // =========================================================================
    // 4. Expiration Consistency & Same-Day Confirmation Tests
    // =========================================================================

    public function test_same_day_pending_appointment_is_not_expired_and_can_be_confirmed()
    {
        $receptionist = $this->makeUserWithRole('receptionist');
        $staff = $this->makeUserWithRole('staff');

        $nowManila = Carbon::now('Asia/Manila');
        $dow = $nowManila->dayOfWeek;

        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $dow,
            'start_time' => '08:00:00',
            'end_time' => '22:00:00',
            'is_day_off' => false,
        ]);

        $category = \App\Models\ServiceCategory::firstOrCreate(['name' => 'Massage']);

        $service = Service::create([
            'category_id' => $category->id,
            'name' => 'Swedish Massage',
            'price' => 500.00,
            'duration_minutes' => 60,
            'is_active' => true,
            'requires_room' => false,
        ]);

        $customer = Customer::create([
            'first_name' => 'SameDay',
            'last_name' => 'Customer',
            'phone_number' => '09123456789',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $staff->id,
            'appointment_date' => $nowManila->toDateString(),
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'status' => 'pending',
            'total_price' => 500.00,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => 500.00]);
        $appointment->created_at = $nowManila->copy()->subHours(2);
        $appointment->save();

        // 1. Expiration check: should not be expired
        $this->assertFalse($appointment->fresh()->is_expired);

        // 2. Query scope check: should be in pendingValid, not in pendingOverdue
        $this->assertTrue(Appointment::pendingValid()->where('id', $appointment->id)->exists());
        $this->assertFalse(Appointment::pendingOverdue()->where('id', $appointment->id)->exists());

        // 3. Receptionist confirmation should succeed
        Cache::flush();
        $response = $this->actingAs($receptionist)->post(
            route('receptionist.confirm', $appointment),
            [
                'staff_id' => $staff->id,
                'payment_type' => 'cash_on_site',
                'payment_method' => 'cash',
                'amount' => 0,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_pending_appointment_past_calendar_date_is_expired()
    {
        $nowManila = Carbon::now('Asia/Manila');

        $customer = Customer::create([
            'first_name' => 'PastDate',
            'last_name' => 'Customer',
            'phone_number' => '09123456788',
        ]);

        $yesterday = $nowManila->copy()->subDay();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $yesterday->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 500.00,
        ]);
        // Created yesterday
        $appointment->created_at = $yesterday->copy()->setTime(9, 0, 0);
        $appointment->save();

        $fresh = $appointment->fresh();
        $this->assertTrue($fresh->is_expired);
        $this->assertFalse(Appointment::pendingValid()->where('id', $fresh->id)->exists());
        $this->assertTrue(Appointment::pendingOverdue()->where('id', $fresh->id)->exists());
    }

    public function test_pending_appointment_older_than_24_hours_is_expired()
    {
        $nowManila = Carbon::now('Asia/Manila');

        $customer = Customer::create([
            'first_name' => 'OldPending',
            'last_name' => 'Customer',
            'phone_number' => '09123456787',
        ]);

        // Future appointment (next week), but submitted 26 hours ago
        $futureDate = $nowManila->copy()->addDays(7);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $futureDate->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 500.00,
        ]);
        $appointment->created_at = $nowManila->copy()->subHours(26);
        $appointment->save();

        $fresh = $appointment->fresh();
        $this->assertTrue($fresh->is_expired);
        $this->assertFalse(Appointment::pendingValid()->where('id', $fresh->id)->exists());
        $this->assertTrue(Appointment::pendingOverdue()->where('id', $fresh->id)->exists());
    }
}
