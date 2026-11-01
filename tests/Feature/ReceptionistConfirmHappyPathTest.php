<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Happy-path tests for ReceptionistController::confirm().
 *
 * These tests exercise the successful pending → confirmed transition through
 * the real application route, satisfying every validation layer:
 *
 *   1. Status + expiry guard (added in expiration fix)
 *   2. Staff availability (schedGetAvailableStaff — requires WorkSchedule)
 *   3. Staff conflict check (no overlapping confirmed appointments)
 *   4. Room check (skipped when service has requires_room = false)
 *   5. Payment validation (cash_on_site when no deposit is required)
 */
class ReceptionistConfirmHappyPathTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a receptionist user with the 'receptionist' role.
     */
    private function makeReceptionist(): User
    {
        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);
        return $user;
    }

    /**
     * Create a staff user with the 'staff' role and a WorkSchedule covering
     * the given day-of-week and time window.
     *
     * @param  int    $dayOfWeek  Carbon day-of-week (0 = Sunday … 6 = Saturday)
     * @param  string $schStart   Schedule start time, e.g. '08:00:00'
     * @param  string $schEnd     Schedule end time, e.g.   '18:00:00'
     */
    private function makeStaffWithSchedule(int $dayOfWeek, string $schStart, string $schEnd): User
    {
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staff = User::factory()->create(['is_active' => true]);
        $staff->roles()->attach($staffRole);

        WorkSchedule::create([
            'user_id'    => $staff->id,
            'day_of_week'=> $dayOfWeek,
            'start_time' => $schStart,
            'end_time'   => $schEnd,
            'is_day_off' => false,
        ]);

        return $staff;
    }

    /**
     * Create a customer + linked user.
     */
    private function makeCustomer(): Customer
    {
        $custRole = Role::firstOrCreate(['name' => 'customer']);
        $custUser = User::factory()->create();
        $custUser->roles()->attach($custRole);

        return Customer::create([
            'user_id'      => $custUser->id,
            'first_name'   => 'Happy',
            'last_name'    => 'Path',
            'email'        => $custUser->username . '@test.com',
            'phone_number' => '09400000001',
        ]);
    }

    /**
     * Create a service that:
     *  - does NOT require a room  (skips room validation)
     *  - does NOT require prepayment / deposit  (enables cash_on_site)
     */
    private function makeSimpleService(): Service
    {
        $category = ServiceCategory::create([
            'name'      => 'Test Category',
            'is_active' => true,
        ]);

        return Service::create([
            'category_id'          => $category->id,
            'name'                 => 'Basic Massage',
            'duration_minutes'     => 60,
            'price'                => 500.00,
            'requires_prepayment'  => false,
            'deposit_percentage_min' => null,
            'requires_room'        => false,
            'is_active'            => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    /**
     * A valid pending appointment confirmed by a receptionist with cash_on_site
     * (no deposit required) must:
     *   - redirect to receptionist.active with a success flash
     *   - persist status = 'confirmed'
     *   - NOT create a Payment record (cash_on_site skips Payment::create)
     */
    public function test_receptionist_can_confirm_valid_pending_appointment_cash_on_site()
    {
        // Pick a deterministic future date — use next Monday to ensure
        // day_of_week = Carbon::MONDAY (1) is predictable regardless of when
        // the test runs.
        $nowManila     = Carbon::now('Asia/Manila');
        $appointmentDate = $nowManila->copy()->next(Carbon::MONDAY)->toDateString();
        $dow             = Carbon::parse($appointmentDate)->dayOfWeek; // should be 1

        $receptionist = $this->makeReceptionist();
        $staff        = $this->makeStaffWithSchedule($dow, '08:00:00', '18:00:00');
        $customer     = $this->makeCustomer();
        $service      = $this->makeSimpleService();

        $appointment = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => $appointmentDate,
            'start_time'       => '10:00:00',
            'end_time'         => '11:00:00',
            'status'           => 'pending',
            'total_price'      => 500.00,
        ]);

        // Attach service with pivot data
        $appointment->services()->attach($service->id, [
            'price_at_booking' => 500.00,
        ]);

        // Flush the array cache so schedGetAvailableStaff re-queries
        Cache::flush();

        $response = $this->actingAs($receptionist)->post(
            route('receptionist.confirm', $appointment),
            [
                'staff_id'       => $staff->id,
                'payment_method' => 'cash',
                'payment_type'   => 'cash_on_site',
                'amount'         => 0,
            ]
        );

        // Must redirect to receptionist.active with success
        $response->assertRedirect(route('receptionist.active'));
        $response->assertSessionHas('success');

        // Appointment must now be confirmed in the DB
        $appointment->refresh();
        $this->assertSame('confirmed', $appointment->status);
        $this->assertSame($staff->id,  $appointment->user_id);
        $this->assertNotNull($appointment->confirmed_at);
        $this->assertSame($receptionist->id, $appointment->confirmed_by);

        // cash_on_site does NOT create a Payment record
        $this->assertDatabaseCount('payments', 0);
    }

    /**
     * Confirming with a deposit payment must:
     *   - set status = 'confirmed'
     *   - create exactly one Payment record of type 'deposit'
     *   - redirect with success
     */
    public function test_receptionist_can_confirm_valid_pending_appointment_with_deposit()
    {
        $nowManila       = Carbon::now('Asia/Manila');
        $appointmentDate = $nowManila->copy()->next(Carbon::TUESDAY)->toDateString();
        $dow             = Carbon::parse($appointmentDate)->dayOfWeek;

        $receptionist = $this->makeReceptionist();
        $staff        = $this->makeStaffWithSchedule($dow, '08:00:00', '18:00:00');
        $customer     = $this->makeCustomer();
        $service      = $this->makeSimpleService();

        $appointment = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => $appointmentDate,
            'start_time'       => '14:00:00',
            'end_time'         => '15:00:00',
            'status'           => 'pending',
            'total_price'      => 500.00,
        ]);

        $appointment->services()->attach($service->id, [
            'price_at_booking' => 500.00,
        ]);

        Cache::flush();

        $response = $this->actingAs($receptionist)->post(
            route('receptionist.confirm', $appointment),
            [
                'staff_id'       => $staff->id,
                'payment_method' => 'gcash',
                'payment_type'   => 'deposit',
                'amount'         => 200, // deposit amount (no min % on service)
            ]
        );

        $response->assertRedirect(route('receptionist.active'));
        $response->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame('confirmed', $appointment->status);

        // One deposit Payment record must exist
        $this->assertDatabaseHas('payments', [
            'appointment_id' => $appointment->id,
            'payment_method' => 'gcash',
            'type'           => 'deposit',
            'amount'         => 200,
        ]);
        $this->assertDatabaseCount('payments', 1);
    }

    /**
     * An already-confirmed appointment must not be confirmed again — the status
     * guard introduced by the expiration fix should reject it.
     */
    public function test_confirming_already_confirmed_appointment_is_rejected()
    {
        $nowManila       = Carbon::now('Asia/Manila');
        $appointmentDate = $nowManila->copy()->next(Carbon::WEDNESDAY)->toDateString();
        $dow             = Carbon::parse($appointmentDate)->dayOfWeek;

        $receptionist = $this->makeReceptionist();
        $staff        = $this->makeStaffWithSchedule($dow, '08:00:00', '18:00:00');
        $customer     = $this->makeCustomer();
        $service      = $this->makeSimpleService();

        $appointment = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => $appointmentDate,
            'start_time'       => '09:00:00',
            'end_time'         => '10:00:00',
            'status'           => 'confirmed', // already confirmed
            'total_price'      => 500.00,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => 500.00]);

        Cache::flush();

        $response = $this->actingAs($receptionist)->post(
            route('receptionist.confirm', $appointment),
            [
                'staff_id'       => $staff->id,
                'payment_method' => 'cash',
                'payment_type'   => 'cash_on_site',
                'amount'         => 0,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Status must remain confirmed, not double-confirmed
        $this->assertSame('confirmed', $appointment->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
    }

    /**
     * Staff availability check must reject a confirmation when the requested
     * staff member has no WorkSchedule for the appointment day.
     */
    public function test_confirmation_fails_when_staff_has_no_schedule_for_appointment_day()
    {
        $nowManila       = Carbon::now('Asia/Manila');
        $appointmentDate = $nowManila->copy()->next(Carbon::THURSDAY)->toDateString();
        $dow             = Carbon::parse($appointmentDate)->dayOfWeek;

        $receptionist = $this->makeReceptionist();
        $customer     = $this->makeCustomer();
        $service      = $this->makeSimpleService();

        // Staff with NO schedule for this day_of_week
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staff     = User::factory()->create(['is_active' => true]);
        $staff->roles()->attach($staffRole);
        // WorkSchedule is intentionally NOT created

        $appointment = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => $appointmentDate,
            'start_time'       => '10:00:00',
            'end_time'         => '11:00:00',
            'status'           => 'pending',
            'total_price'      => 500.00,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => 500.00]);

        Cache::flush();

        $response = $this->actingAs($receptionist)->post(
            route('receptionist.confirm', $appointment),
            [
                'staff_id'       => $staff->id,
                'payment_method' => 'cash',
                'payment_type'   => 'cash_on_site',
                'amount'         => 0,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Appointment must remain pending
        $this->assertSame('pending', $appointment->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
    }
}
