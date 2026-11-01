<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAppointmentCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_own_upcoming_appointment()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => $user->email,
            'phone_number' => '1234567890',
        ]);

        $room = Room::create([
            'name' => 'VIP Room 1',
            'status' => 'occupied',
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1500.00,
            'notes' => 'Initial note',
        ]);

        Payment::create([
            'appointment_id' => $appointment->id,
            'payment_method' => 'cash',
            'amount' => 300,
            'type' => 'deposit',
        ]);

        $response = $this->actingAs($user)->post(route('customer.appointments.cancel', $appointment), [
            'cancellation_note' => 'Schedule conflict',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertEquals('customer_cancelled', $appointment->cancellation_reason);
        $this->assertStringContainsString('Schedule conflict', $appointment->notes);

        $room->refresh();
        $this->assertEquals('available', $room->status);
        $this->assertDatabaseHas('payments', [
            'appointment_id' => $appointment->id,
            'payment_method' => 'cash',
            'amount' => 300,
            'type' => 'deposit',
        ]);
    }

    public function test_cancelled_or_completed_appointments_cannot_be_cancelled_again()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => $user->email,
            'phone_number' => '1234567890',
        ]);

        foreach (['cancelled', 'completed'] as $status) {
            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'appointment_date' => Carbon::tomorrow()->toDateString(),
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'status' => $status,
                'cancellation_reason' => $status === 'cancelled' ? 'existing_reason' : null,
                'total_price' => 1500,
            ]);

            $this->actingAs($user)
                ->post(route('customer.appointments.cancel', $appointment), ['cancellation_note' => 'No mutation'])
                ->assertRedirect();

            $appointment->refresh();
            $this->assertSame($status, $appointment->status);
            $this->assertSame($status === 'cancelled' ? 'existing_reason' : null, $appointment->cancellation_reason);
            $this->assertStringNotContainsString('No mutation', $appointment->notes ?? '');
        }

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_room_remains_occupied_when_another_active_appointment_still_needs_it()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => $user->email,
            'phone_number' => '1234567890',
        ]);
        $room = Room::create(['name' => 'Shared Room', 'status' => 'occupied', 'is_active' => true]);

        $toCancel = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);
        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);

        $this->actingAs($user)->post(route('customer.appointments.cancel', $toCancel));

        $this->assertSame('occupied', $room->fresh()->status);
        $this->assertSame('cancelled', $toCancel->fresh()->status);
    }

    public function test_customer_cannot_cancel_other_customer_appointment()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        
        $user1 = User::factory()->create();
        $user1->roles()->attach($role);
        $customer1 = Customer::create([
            'user_id' => $user1->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => $user1->email,
            'phone_number' => '1234567890',
        ]);

        $user2 = User::factory()->create();
        $user2->roles()->attach($role);
        $customer2 = Customer::create([
            'user_id' => $user2->id,
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => $user2->email,
            'phone_number' => '0987654321',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer2->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1500.00,
        ]);

        $response = $this->actingAs($user1)->post(route('customer.appointments.cancel', $appointment));

        $response->assertStatus(403);
    }

    public function test_customer_cancellation_appears_in_reports_and_does_not_generate_fake_financial_payment()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Alice',
            'last_name' => 'Walker',
            'email' => $user->email,
            'phone_number' => '09123456789',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'pending',
            'total_price' => 1200.00,
        ]);

        $this->actingAs($user)->post(route('customer.appointments.cancel', $appointment), [
            'cancellation_note' => 'Unable to make it',
        ]);

        $appointment->refresh();
        $this->assertSame('cancelled', $appointment->status);
        $this->assertSame('customer_cancelled', $appointment->cancellation_reason);
        $this->assertStringContainsString('Unable to make it', $appointment->notes);

        // Verify zero fake payments were created
        $this->assertCount(0, Payment::where('appointment_id', $appointment->id)->get());

        // Verify it appears in Admin report filtered by cancelled
        $response = $this->actingAs($admin)->get(route('admin.appointments', [
            'status' => 'cancelled',
            'date_from' => Carbon::tomorrow()->toDateString(),
            'date_to' => Carbon::tomorrow()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertSee('Alice');
        $response->assertSee('Walker');
        $response->assertSee('Customer Cancelled');

        // Verify it appears in Customer Cancelled specific filter
        $responseSpecific = $this->actingAs($admin)->get(route('admin.appointments', [
            'status' => 'cancelled_customer',
            'date_from' => Carbon::tomorrow()->toDateString(),
            'date_to' => Carbon::tomorrow()->toDateString(),
        ]));

        $responseSpecific->assertStatus(200);
        $responseSpecific->assertSee('Alice');
    }

    public function test_repeated_cancellation_attempt_does_not_duplicate_or_corrupt()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Bob',
            'last_name' => 'Marley',
            'email' => $user->email,
            'phone_number' => '09123456780',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 800.00,
        ]);

        $this->actingAs($user)->post(route('customer.appointments.cancel', $appointment));
        $this->assertSame('cancelled', $appointment->fresh()->status);

        // Second attempt
        $res2 = $this->actingAs($user)->post(route('customer.appointments.cancel', $appointment));
        $res2->assertSessionHas('error');
        $this->assertSame('cancelled', $appointment->fresh()->status);
    }

    public function test_pending_appointment_older_than_24_hours_expires_via_artisan_command()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Charlie',
            'last_name' => 'Brown',
            'email' => $user->email,
            'phone_number' => '09123456781',
        ]);

        // Created 25 hours ago for next week
        $oldPending = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::now('Asia/Manila')->addDays(5)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 500.00,
        ]);
        $oldPending->created_at = Carbon::now('Asia/Manila')->subHours(25);
        $oldPending->save();

        // Created 1 hour ago for next week
        $recentPending = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::now('Asia/Manila')->addDays(5)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 500.00,
        ]);
        $recentPending->created_at = Carbon::now('Asia/Manila')->subHours(1);
        $recentPending->save();

        // Confirmed booking created 30 hours ago
        $confirmed = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::now('Asia/Manila')->addDays(2)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 500.00,
        ]);
        $confirmed->created_at = Carbon::now('Asia/Manila')->subHours(30);
        $confirmed->save();

        $this->artisan('appointments:expire-pending')->assertSuccessful();

        $this->assertSame('cancelled', $oldPending->fresh()->status);
        $this->assertSame('expired', $oldPending->fresh()->cancellation_reason);

        // Recent pending must NOT expire
        $this->assertSame('pending', $recentPending->fresh()->status);

        // Confirmed must NOT expire
        $this->assertSame('confirmed', $confirmed->fresh()->status);
    }

    /**
     * A pending appointment whose scheduled start time has passed but whose date is still
     * today must NOT be expired. Expiry fires at midnight on the appointment date, not
     * at the moment the start_time arrives. This replaces the previous test which
     * incorrectly asserted start-time-based expiry.
     */
    public function test_pending_appointment_start_time_passed_but_date_still_today_does_not_expire()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'David',
            'last_name' => 'Tennant',
            'email' => $user->email,
            'phone_number' => '09123456782',
        ]);

        $nowManila = Carbon::now('Asia/Manila');

        // Created 2 hours ago, start_time 30 minutes in the past, but TODAY is still
        // the appointment_date â€” under the corrected rule this must NOT expire.
        $pastStartPending = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $nowManila->toDateString(),
            'start_time' => $nowManila->copy()->subMinutes(30)->format('H:i:s'),
            'end_time' => $nowManila->copy()->addMinutes(30)->format('H:i:s'),
            'status' => 'pending',
            'total_price' => 600.00,
            'created_at' => $nowManila->copy()->subHours(2),
        ]);

        $this->artisan('appointments:expire-pending')->assertSuccessful();

        // Must still be pending â€” the calendar date has not yet passed.
        $this->assertSame('pending', $pastStartPending->fresh()->status);
    }

    public function test_room_is_currently_occupied_reconciles_active_vs_stale_status()
    {
        $room = Room::create([
            'name' => 'Executive Suite',
            'status' => 'occupied',
            'is_active' => true,
        ]);

        // Fix the reference time to mid-day so subMinutes/addMinutes does not wrap across midnight
        $nowManila = Carbon::parse('2026-10-27 14:00:00', 'Asia/Manila');

        // Without any confirmed appointment right now, isCurrentlyOccupied returns false
        $this->assertFalse($room->isCurrentlyOccupied($nowManila));

        // Create an active in-progress confirmed appointment
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Eve',
            'last_name' => 'Polastri',
            'email' => $user->email,
            'phone_number' => '09123456783',
        ]);

        $activeAppt = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => $nowManila->toDateString(),
            'start_time' => $nowManila->copy()->subMinutes(15)->format('H:i:s'),
            'end_time' => $nowManila->copy()->addMinutes(45)->format('H:i:s'),
            'status' => 'confirmed',
            'total_price' => 1000.00,
        ]);

        $this->assertTrue($room->isCurrentlyOccupied($nowManila));
    }

    public function test_authoritative_expiration_deadline_calculation_and_attributes()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Fiona',
            'last_name' => 'Gallagher',
            'email' => $user->email,
            'phone_number' => '09123456784',
        ]);

        $nowManila = Carbon::now('Asia/Manila');

        // Case A: Booked for 5 days from now. Expiration should be created_at + 24 hours
        $apptFar = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $nowManila->copy()->addDays(5)->toDateString(),
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'status' => 'pending',
            'total_price' => 500.00,
        ]);
        $apptFar->created_at = $nowManila;
        $apptFar->save();

        $this->assertNotNull($apptFar->expires_at);
        $this->assertEquals(
            $nowManila->copy()->addHours(24)->toDateTimeString(),
            $apptFar->expires_at->toDateTimeString()
        );
        $this->assertFalse($apptFar->is_expired);

        // Case B: Booked for a future date (e.g. tomorrow). The date deadline is midnight
        // at the start of the following day after the scheduled appointment date
        // (i.e. midnight at the start of day + 2).
        // If created_at + 24 hours is earlier than that date deadline, created_at + 24h wins.
        // If created_at is 3 days before the appointment, created_at + 24h wins.
        // For tomorrow's booking submitted now, created_at + 24h expires tomorrow at this exact time,
        // which is earlier than midnight following tomorrow (which would be 48 hours away).
        $scheduledStart = $nowManila->copy()->addHours(2);
        $apptDate = $nowManila->copy()->addDay();
        $dateDeadline = $apptDate->copy()->addDay()->startOfDay();
        $apptNear = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $apptDate->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 500.00,
        ]);
        $apptNear->created_at = $nowManila;
        $apptNear->save();

        // The effective deadline is min(created_at+24h, dateDeadline).
        // Since created_at is NOW and dateDeadline is midnight following tomorrow,
        // created_at + 24 hours is earlier and becomes expires_at.
        $expectedDeadline = $nowManila->copy()->addHours(24)->lt($dateDeadline)
            ? $nowManila->copy()->addHours(24)
            : $dateDeadline;

        $this->assertEquals(
            $expectedDeadline->toDateTimeString(),
            $apptNear->expires_at->toDateTimeString()
        );
        // is_expired: deadline is still in the future right now, so false.
        $this->assertFalse($apptNear->is_expired);

        // Case C: Confirmed appointment must return null for expires_at
        $apptConfirmed = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $nowManila->copy()->addDays(1)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 500.00,
        ]);
        $this->assertNull($apptConfirmed->expires_at);
        $this->assertFalse($apptConfirmed->is_expired);
    }

    public function test_receptionist_pending_view_renders_countdown_badges_for_pending_appointments()
    {
        $recRole = Role::firstOrCreate(['name' => 'receptionist']);
        $custRole = Role::firstOrCreate(['name' => 'customer']);

        $recUser = User::factory()->create();
        $recUser->roles()->attach($recRole);

        $custUser = User::factory()->create();
        $custUser->roles()->attach($custRole);

        $customer = Customer::create([
            'user_id' => $custUser->id,
            'first_name' => 'George',
            'last_name' => 'Costanza',
            'email' => $custUser->email,
            'phone_number' => '09123456785',
        ]);

        $nowManila = Carbon::now('Asia/Manila');

        $pendingAppt = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $nowManila->copy()->addDays(3)->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'pending',
            'total_price' => 700.00,
        ]);

        $response = $this->actingAs($recUser)->get(route('receptionist.pending'));

        $response->assertStatus(200);
        $response->assertSee('pending-countdown-badge');
        $response->assertSee($pendingAppt->expires_at->toISOString());
        $response->assertSee('George Costanza');
    }
    // =========================================================================
    // Transaction Log Ã¢â‚¬â€ Cancelled tab regression tests
    // =========================================================================

    /**
     * An unpaid customer-cancelled appointment (no row in payments table)
     * must appear in the /sales?status=cancelled Transaction Log for both
     * Admin and Receptionist dashboards.
     */
    public function test_unpaid_customer_cancelled_appointment_appears_in_transaction_log_cancelled_tab()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $recRole   = Role::firstOrCreate(['name' => 'receptionist']);
        $custRole  = Role::firstOrCreate(['name' => 'customer']);

        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole);

        $recUser = User::factory()->create();
        $recUser->roles()->attach($recRole);

        $custUser = User::factory()->create();
        $custUser->roles()->attach($custRole);

        $customer = Customer::create([
            'user_id'      => $custUser->id,
            'first_name'   => 'Hannah',
            'last_name'    => 'Montana',
            'email'        => $custUser->email,
            'phone_number' => '09191234567',
        ]);

        // Use Carbon::today() so the date aligns with the service's date range (app timezone)
        $appt = Appointment::create([
            'customer_id'         => $customer->id,
            'appointment_date'    => Carbon::today()->toDateString(),
            'start_time'          => '09:00:00',
            'end_time'            => '10:00:00',
            'status'              => 'cancelled',
            'cancellation_reason' => 'customer_cancelled',
            'total_price'         => 800.00,
            'notes'               => 'Customer changed plans',
        ]);

        // Confirm there is genuinely no payment record
        $this->assertCount(0, Payment::where('appointment_id', $appt->id)->get());

        // Admin tx-log fragment for status=cancelled should include this appointment
        $adminResponse = $this->actingAs($adminUser)->get(
            route('admin.sales.tx-log', ['status' => 'cancelled'])
        );
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Hannah Montana');

        // Receptionist tx-log fragment for status=cancelled should include it too
        $recResponse = $this->actingAs($recUser)->get(
            route('receptionist.sales.tx-log', ['status' => 'cancelled'])
        );
        $recResponse->assertStatus(200);
        $recResponse->assertSee('Hannah Montana');
    }

    /**
     * A paid customer-cancelled appointment (deposit was taken)
     * must also appear in the Cancelled tab.
     */
    public function test_paid_customer_cancelled_appointment_appears_in_transaction_log_cancelled_tab()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $custRole  = Role::firstOrCreate(['name' => 'customer']);

        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole);

        $custUser = User::factory()->create();
        $custUser->roles()->attach($custRole);

        $customer = Customer::create([
            'user_id'      => $custUser->id,
            'first_name'   => 'Oliver',
            'last_name'    => 'Twist',
            'email'        => $custUser->email,
            'phone_number' => '09197654321',
        ]);

        $appt = Appointment::create([
            'customer_id'         => $customer->id,
            'appointment_date'    => Carbon::today()->toDateString(),
            'start_time'          => '11:00:00',
            'end_time'            => '12:00:00',
            'status'              => 'cancelled',
            'cancellation_reason' => 'customer_cancelled',
            'total_price'         => 1200.00,
        ]);

        // This appointment has a deposit payment
        Payment::create([
            'appointment_id' => $appt->id,
            'payment_method' => 'gcash',
            'amount'         => 300,
            'type'           => 'deposit',
            'paid_at'        => now(),
        ]);

        $response = $this->actingAs($adminUser)->get(
            route('admin.sales.tx-log', ['status' => 'cancelled'])
        );
        $response->assertStatus(200);
        $response->assertSee('Oliver Twist');
    }

    /**
     * The Completed tab must NOT contain customer-cancelled appointments,
     * and the No-Show tab must NOT contain customer_cancelled reason records.
     */
    public function test_completed_and_no_show_tabs_do_not_contain_customer_cancelled_appointments()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $custRole  = Role::firstOrCreate(['name' => 'customer']);

        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole);

        $custUser = User::factory()->create();
        $custUser->roles()->attach($custRole);

        $customer = Customer::create([
            'user_id'      => $custUser->id,
            'first_name'   => 'Penelope',
            'last_name'    => 'Cruz',
            'email'        => $custUser->email,
            'phone_number' => '09195555555',
        ]);

        // Unpaid customer cancellation today
        Appointment::create([
            'customer_id'         => $customer->id,
            'appointment_date'    => Carbon::today()->toDateString(),
            'start_time'          => '13:00:00',
            'end_time'            => '14:00:00',
            'status'              => 'cancelled',
            'cancellation_reason' => 'customer_cancelled',
            'total_price'         => 600.00,
        ]);

        // Completed tab must NOT show this name
        $completedResponse = $this->actingAs($adminUser)->get(
            route('admin.sales.tx-log', ['status' => 'completed'])
        );
        $completedResponse->assertStatus(200);
        $completedResponse->assertDontSee('Penelope Cruz');

        // No-show tab must NOT show this name
        $noShowResponse = $this->actingAs($adminUser)->get(
            route('admin.sales.tx-log', ['status' => 'no_show'])
        );
        $noShowResponse->assertStatus(200);
        $noShowResponse->assertDontSee('Penelope Cruz');
    }

    /**
     * Receptionist-cancelled appointments (non-customer_cancelled reason)
     * must appear in the Cancelled tab.
     * Uses guest_first_name to display a walk-in name via the client_name accessor.
     */
    public function test_receptionist_cancelled_appointment_appears_in_cancelled_tab()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $custRole  = Role::firstOrCreate(['name' => 'customer']);

        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole);

        // customer_id is NOT NULL in the appointments schema, so a real customer is required.
        // guest_first_name overrides the client_name accessor to show a walk-in name.
        $custUser = User::factory()->create();
        $custUser->roles()->attach($custRole);

        $customer = Customer::create([
            'user_id'      => $custUser->id,
            'first_name'   => 'Regular',
            'last_name'    => 'Customer',
            'email'        => $custUser->email,
            'phone_number' => '09190000000',
        ]);

        Appointment::create([
            'customer_id'         => $customer->id,
            'guest_first_name'    => 'Walk-in Guest',
            'appointment_date'    => Carbon::today()->toDateString(),
            'start_time'          => '14:00:00',
            'end_time'            => '15:00:00',
            'status'              => 'cancelled',
            'cancellation_reason' => 'staff_unavailable',
            'total_price'         => 500.00,
        ]);

        $response = $this->actingAs($adminUser)->get(
            route('admin.sales.tx-log', ['status' => 'cancelled'])
        );
        $response->assertStatus(200);
        $response->assertSee('Walk-in Guest');
    }

    // =========================================================================
    // Transaction Log Ã¢â‚¬â€ No-Show tab regression tests
    // =========================================================================

    /**
     * An unpaid appointment with status = 'no_show' must appear in the No-Show tab
     * without generating fake payments, revenue, or commission.
     */
    public function test_unpaid_no_show_appointment_appears_in_no_show_tab()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $custRole  = Role::firstOrCreate(['name' => 'customer']);

        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole);

        $custUser = User::factory()->create();
        $custUser->roles()->attach($custRole);

        $customer = Customer::create([
            'user_id'      => $custUser->id,
            'first_name'   => 'Sherlock',
            'last_name'    => 'Holmes',
            'email'        => $custUser->email,
            'phone_number' => '09191112233',
        ]);

        $appt = Appointment::create([
            'customer_id'         => $customer->id,
            'appointment_date'    => Carbon::today()->toDateString(),
            'start_time'          => '10:00:00',
            'end_time'            => '11:00:00',
            'status'              => 'no_show',
            'no_show_reason'      => 'Client did not arrive',
            'total_price'         => 1500.00,
        ]);

        $this->assertCount(0, Payment::where('appointment_id', $appt->id)->get());

        // Admin tx-log for status=no_show should include this appointment
        $response = $this->actingAs($adminUser)->get(
            route('admin.sales.tx-log', ['status' => 'no_show'])
        );
        $response->assertStatus(200);
        $response->assertSee('Sherlock Holmes');

        // Cancelled tab must NOT show this no-show
        $cancelledResponse = $this->actingAs($adminUser)->get(
            route('admin.sales.tx-log', ['status' => 'cancelled'])
        );
        $cancelledResponse->assertStatus(200);
        $cancelledResponse->assertDontSee('Sherlock Holmes');

        // Completed tab must NOT show this no-show
        $completedResponse = $this->actingAs($adminUser)->get(
            route('admin.sales.tx-log', ['status' => 'completed'])
        );
        $completedResponse->assertStatus(200);
        $completedResponse->assertDontSee('Sherlock Holmes');
    }

    /**
     * An appointment with status = 'cancelled' and cancellation_reason = 'customer_no_show'
     * must appear in the No-Show tab and NOT in the Cancelled tab.
     */
    public function test_cancelled_customer_no_show_appears_in_no_show_tab_not_cancelled_tab()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $custRole  = Role::firstOrCreate(['name' => 'customer']);

        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole);

        $custUser = User::factory()->create();
        $custUser->roles()->attach($custRole);

        $customer = Customer::create([
            'user_id'      => $custUser->id,
            'first_name'   => 'John',
            'last_name'    => 'Watson',
            'email'        => $custUser->email,
            'phone_number' => '09194445566',
        ]);

        $appt = Appointment::create([
            'customer_id'         => $customer->id,
            'appointment_date'    => Carbon::today()->toDateString(),
            'start_time'          => '11:00:00',
            'end_time'            => '12:00:00',
            'status'              => 'cancelled',
            'cancellation_reason' => 'customer_no_show',
            'total_price'         => 900.00,
        ]);

        // No-show tab must see John Watson
        $noShowResponse = $this->actingAs($adminUser)->get(
            route('admin.sales.tx-log', ['status' => 'no_show'])
        );
        $noShowResponse->assertStatus(200);
        $noShowResponse->assertSee('John Watson');

        // Cancelled tab must NOT see John Watson
        $cancelledResponse = $this->actingAs($adminUser)->get(
            route('admin.sales.tx-log', ['status' => 'cancelled'])
        );
        $cancelledResponse->assertStatus(200);
        $cancelledResponse->assertDontSee('John Watson');
    }

    /**
     * A paid no-show (deposit taken and forfeited) appears in the No-Show tab
     * with the collected deposit counted.
     */
    public function test_paid_no_show_appointment_appears_in_no_show_tab()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $custRole  = Role::firstOrCreate(['name' => 'customer']);

        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole);

        $custUser = User::factory()->create();
        $custUser->roles()->attach($custRole);

        $customer = Customer::create([
            'user_id'      => $custUser->id,
            'first_name'   => 'Mycroft',
            'last_name'    => 'Holmes',
            'email'        => $custUser->email,
            'phone_number' => '09197778899',
        ]);

        $appt = Appointment::create([
            'customer_id'         => $customer->id,
            'appointment_date'    => Carbon::today()->toDateString(),
            'start_time'          => '13:00:00',
            'end_time'            => '14:00:00',
            'status'              => 'no_show',
            'total_price'         => 2000.00,
        ]);

        Payment::create([
            'appointment_id' => $appt->id,
            'payment_method' => 'cash',
            'amount'         => 400,
            'type'           => 'deposit',
            'paid_at'        => now(),
        ]);

        $response = $this->actingAs($adminUser)->get(
            route('admin.sales.tx-log', ['status' => 'no_show'])
        );
        $response->assertStatus(200);
        $response->assertSee('Mycroft Holmes');
    }

    /**
     * Ordinary customer cancellations and expired appointments must NOT leak into No-Show.
     */
    public function test_ordinary_and_expired_cancellations_do_not_appear_in_no_show_tab()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $custRole  = Role::firstOrCreate(['name' => 'customer']);

        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole);

        $custUser = User::factory()->create();
        $custUser->roles()->attach($custRole);

        $customer1 = Customer::create([
            'user_id'      => $custUser->id,
            'first_name'   => 'Irene',
            'last_name'    => 'Adler',
            'email'        => 'irene@example.com',
            'phone_number' => '09191010101',
        ]);

        // Customer cancelled
        Appointment::create([
            'customer_id'         => $customer1->id,
            'appointment_date'    => Carbon::today()->toDateString(),
            'start_time'          => '15:00:00',
            'end_time'            => '16:00:00',
            'status'              => 'cancelled',
            'cancellation_reason' => 'customer_cancelled',
            'total_price'         => 1000.00,
        ]);

        // Expired
        Appointment::create([
            'customer_id'         => $customer1->id,
            'guest_first_name'    => 'Professor Moriarty',
            'appointment_date'    => Carbon::today()->toDateString(),
            'start_time'          => '16:00:00',
            'end_time'            => '17:00:00',
            'status'              => 'cancelled',
            'cancellation_reason' => 'expired',
            'total_price'         => 1200.00,
        ]);

        $response = $this->actingAs($adminUser)->get(
            route('admin.sales.tx-log', ['status' => 'no_show'])
        );
        $response->assertStatus(200);
        $response->assertDontSee('Irene Adler');
        $response->assertDontSee('Professor Moriarty');
    }

    // =========================================================================
    // Expiration rule â€” date-midnight correctness tests
    // =========================================================================

    /**
     * An appointment whose appointment_date has already passed (yesterday) must expire
     * via the Artisan command regardless of start_time.
     */
    public function test_pending_appointment_with_past_date_expires_via_artisan_command()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $customer = Customer::create([
            'user_id'      => $user->id,
            'first_name'   => 'Past',
            'last_name'    => 'Date',
            'email'        => $user->email,
            'phone_number' => '09300000001',
        ]);

        $nowManila = Carbon::now('Asia/Manila');

        // appointment_date is yesterday â€” calendar date has passed
        $appt = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => $nowManila->copy()->subDay()->toDateString(),
            'start_time'       => '23:59:00',   // start_time late in the day â€” irrelevant
            'end_time'         => '23:59:59',
            'status'           => 'pending',
            'total_price'      => 500.00,
            'created_at'       => $nowManila->copy()->subHours(2), // within 24h
        ]);

        $this->artisan('appointments:expire-pending')->assertSuccessful();

        $this->assertSame('cancelled', $appt->fresh()->status);
        $this->assertSame('expired',   $appt->fresh()->cancellation_reason);
    }

    /**
     * An appointment created just under 24 hours ago for a future date must NOT expire.
     */
    public function test_pending_appointment_under_24h_old_for_future_date_does_not_expire()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $customer = Customer::create([
            'user_id'      => $user->id,
            'first_name'   => 'Future',
            'last_name'    => 'Safe',
            'email'        => $user->email,
            'phone_number' => '09300000002',
        ]);

        $nowManila = Carbon::now('Asia/Manila');

        $appt = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => $nowManila->copy()->addDays(3)->toDateString(),
            'start_time'       => '10:00:00',
            'end_time'         => '11:00:00',
            'status'           => 'pending',
            'total_price'      => 500.00,
            'created_at'       => $nowManila->copy()->subMinutes(60 * 23 + 59), // 23h59m ago
        ]);

        $this->artisan('appointments:expire-pending')->assertSuccessful();

        $this->assertSame('pending', $appt->fresh()->status);
    }

    /**
     * An appointment submitted less than 24 hours before its scheduled date should use
     * midnight on appointment_date as the effective expiration deadline (deadline b is
     * earlier than deadline a).
     */
    public function test_expires_at_uses_midnight_when_submitted_less_than_24h_before_appointment_date()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $customer = Customer::create([
            'user_id'      => $user->id,
            'first_name'   => 'Midnight',
            'last_name'    => 'Test',
            'email'        => $user->email,
            'phone_number' => '09300000003',
        ]);

        $nowManila = Carbon::now('Asia/Manila');

        // Booked NOW for today — 24h from now is further than midnight at start of next day.
        // Deadline (b) is midnight at the start of following day (tomorrow 00:00:00).
        // Since created_at + 24h is 24 hours away and tomorrow 00:00:00 is less than 24 hours away,
        // deadline (b) = midnight at the start of tomorrow wins.
        $tomorrowMidnight = $nowManila->copy()->addDay()->startOfDay();

        $appt = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => $nowManila->toDateString(),
            'start_time'       => '09:00:00',
            'end_time'         => '10:00:00',
            'status'           => 'pending',
            'total_price'      => 600.00,
        ]);
        $appt->created_at = $nowManila;
        $appt->save();

        $appt->refresh();
        $expiresAt = $appt->expires_at;
        $this->assertNotNull($expiresAt);

        // expires_at must be whichever is earlier: created_at+24h or midnight at start of tomorrow
        $creationDeadline = $nowManila->copy()->addHours(24);
        $expected = $creationDeadline->lt($tomorrowMidnight) ? $creationDeadline : $tomorrowMidnight;

        $this->assertEquals(
            $expected->toDateTimeString(),
            $expiresAt->toDateTimeString()
        );

        // start_time must not appear in expires_at
        $this->assertNotEquals('09:00:00', $expiresAt->format('H:i:s'));
    }

    // =========================================================================
    // Receptionist confirm() â€” status guard tests
    // =========================================================================

    /**
     * Attempting to confirm an already-cancelled appointment must fail with an error
     * and must not overwrite the cancelled status.
     */
    public function test_receptionist_cannot_confirm_already_cancelled_appointment()
    {
        $recRole  = Role::firstOrCreate(['name' => 'receptionist']);
        $custRole = Role::firstOrCreate(['name' => 'customer']);

        $recUser  = User::factory()->create();
        $recUser->roles()->attach($recRole);

        $custUser = User::factory()->create();
        $custUser->roles()->attach($custRole);

        $customer = Customer::create([
            'user_id'      => $custUser->id,
            'first_name'   => 'Already',
            'last_name'    => 'Cancelled',
            'email'        => $custUser->email,
            'phone_number' => '09300000004',
        ]);

        $appt = Appointment::create([
            'customer_id'         => $customer->id,
            'appointment_date'    => Carbon::tomorrow()->toDateString(),
            'start_time'          => '10:00:00',
            'end_time'            => '11:00:00',
            'status'              => 'cancelled',
            'cancellation_reason' => 'expired',
            'total_price'         => 800.00,
        ]);

        $response = $this->actingAs($recUser)->post(route('receptionist.confirm', $appt), [
            'staff_id'       => $recUser->id,
            'payment_method' => 'cash',
            'payment_type'   => 'cash_on_site',
            'amount'         => 0,
        ]);

        // Must redirect back with error, not confirm
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertSame('cancelled', $appt->fresh()->status);
        $this->assertSame('expired',   $appt->fresh()->cancellation_reason);
    }

    /**
     * Attempting to confirm an expired pending appointment must fail.
     * An appointment is expired when its effective deadline (created_at+24h or midnight
     * of appointment_date) has passed.
     */
    public function test_receptionist_cannot_confirm_expired_pending_appointment()
    {
        $recRole  = Role::firstOrCreate(['name' => 'receptionist']);
        $custRole = Role::firstOrCreate(['name' => 'customer']);

        $recUser  = User::factory()->create();
        $recUser->roles()->attach($recRole);

        $custUser = User::factory()->create();
        $custUser->roles()->attach($custRole);

        $customer = Customer::create([
            'user_id'      => $custUser->id,
            'first_name'   => 'Expired',
            'last_name'    => 'Pending',
            'email'        => $custUser->email,
            'phone_number' => '09300000005',
        ]);

        $nowManila = Carbon::now('Asia/Manila');

        // Created 25 hours ago â€” past the 24h deadline â€” still technically 'pending'
        // (the sweep hasn't run yet), but is_expired = true.
        $appt = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => $nowManila->copy()->addDays(3)->toDateString(),
            'start_time'       => '10:00:00',
            'end_time'         => '11:00:00',
            'status'           => 'pending',
            'total_price'      => 1000.00,
        ]);
        $appt->created_at = $nowManila->copy()->subHours(25);
        $appt->save();

        // Verify the model considers this appointment expired
        $appt->refresh();
        $this->assertTrue($appt->is_expired, 'Pre-condition: appointment should be expired');
        $this->assertSame('pending', $appt->status, 'Pre-condition: status still pending (sweep not run)');

        $response = $this->actingAs($recUser)->post(route('receptionist.confirm', $appt), [
            'staff_id'       => $recUser->id,
            'payment_method' => 'cash',
            'payment_type'   => 'cash_on_site',
            'amount'         => 0,
        ]);

        // Must redirect back with an error
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Status must NOT have been changed to confirmed
        $this->assertNotSame('confirmed', $appt->fresh()->status);
    }
}
