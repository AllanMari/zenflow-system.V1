<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AppointmentOccupancyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PendingPreferredTherapistHoldTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;
    private User $therapistA;
    private User $therapistB;
    private User $therapistC;
    private Service $singleService;
    private Service $addonService;
    private Service $coupleService;
    private Room $room1;
    private Room $room2;
    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        $this->receptionist = User::factory()->create(['is_active' => true]);
        $this->receptionist->roles()->attach($receptionistRole);

        $this->therapistA = User::factory()->create(['first_name' => 'Elena', 'last_name' => 'Cruz', 'is_active' => true]);
        $this->therapistB = User::factory()->create(['first_name' => 'Marco', 'last_name' => 'Reyes', 'is_active' => true]);
        $this->therapistC = User::factory()->create(['first_name' => 'Sofia', 'last_name' => 'Lim', 'is_active' => true]);

        foreach ([$this->therapistA, $this->therapistB, $this->therapistC] as $staff) {
            $staff->roles()->attach($staffRole);
        }

        $this->date = Carbon::tomorrow('Asia/Manila')->toDateString();
        $dayOfWeek = Carbon::parse($this->date, 'Asia/Manila')->dayOfWeek;

        BusinessHour::updateOrCreate(
            ['day_of_week' => $dayOfWeek],
            ['is_open' => true, 'open_time' => '09:00', 'close_time' => '21:00']
        );

        foreach ([$this->therapistA, $this->therapistB, $this->therapistC] as $staff) {
            WorkSchedule::create([
                'user_id' => $staff->id,
                'day_of_week' => $dayOfWeek,
                'start_time' => '09:00:00',
                'end_time' => '21:00:00',
            ]);
        }

        $svcCat = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);
        $roomCat = RoomCategory::create(['name' => 'Suite', 'code' => 'SUITE']);

        $this->singleService = Service::create([
            'category_id' => $svcCat->id,
            'room_category_id' => $roomCat->id,
            'name' => 'Signature Massage',
            'duration_minutes' => 60,
            'price' => 1000,
            'therapists_required' => 1,
            'requires_room' => true,
            'is_active' => true,
            'deposit_required' => true,
            'deposit_percentage_min' => 50,
        ]);

        $this->addonService = Service::create([
            'category_id' => $svcCat->id,
            'room_category_id' => $roomCat->id,
            'name' => 'Foot Scrub',
            'duration_minutes' => 30,
            'price' => 500,
            'therapists_required' => 1,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $this->coupleService = Service::create([
            'category_id' => $svcCat->id,
            'room_category_id' => $roomCat->id,
            'name' => 'Couples Retreat',
            'duration_minutes' => 60,
            'price' => 2200,
            'therapists_required' => 2,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $this->room1 = Room::create(['name' => 'Room 1', 'status' => 'available', 'is_active' => true]);
        $this->room2 = Room::create(['name' => 'Room 2', 'status' => 'available', 'is_active' => true]);
        $this->room1->roomCategories()->attach($roomCat->id);
        $this->room2->roomCategories()->attach($roomCat->id);
    }

    private function guestPayload(array $overrides = []): array
    {
        return array_merge([
            'services' => [$this->singleService->id],
            'appointment_date' => $this->date,
            'start_time' => '10:00',
            'room_id' => $this->room1->id,
            'guest_first_name' => 'Guest One',
            'guest_phone' => '09170001111',
            'preferred_staff_ids' => [$this->therapistA->id],
            'privacy_consented' => '1',
        ], $overrides);
    }

    public function test_selecting_preferred_therapist_without_submitting_creates_no_hold(): void
    {
        $this->getJson(route('api.booking.preferred-therapists', [
            'date' => $this->date,
            'start_time' => '10:00',
            'duration' => 60,
            'services' => [$this->singleService->id],
        ]))->assertOk();

        $this->assertEquals(0, Appointment::count());
        $this->assertEquals(0, DB::table('appointment_preferred_staff')->count());
        $this->assertFalse(
            app(AppointmentOccupancyService::class)->hasStaffConflict($this->therapistA->id, $this->date, '10:00:00', '11:00:00')
        );
    }

    public function test_successful_pending_booking_creates_hold_and_blocks_overlapping_customer_booking(): void
    {
        $res1 = $this->postJson(route('booking.store'), $this->guestPayload());
        $res1->assertStatus(200);

        $appt = Appointment::latest('id')->first();
        $this->assertEquals('pending', $appt->status);
        $this->assertNull($appt->user_id);
        $this->assertEquals(0, DB::table('appointment_staff')->where('appointment_id', $appt->id)->count());
        $this->assertEquals([$this->therapistA->id], $appt->preferredStaff()->pluck('users.id')->all());

        // Endpoint must now show therapistA as unavailable for overlapping 10:30-11:30
        $avail = $this->getJson(route('api.booking.preferred-therapists', [
            'date' => $this->date,
            'start_time' => '10:30',
            'duration' => 60,
            'services' => [$this->singleService->id],
        ]))->assertOk();

        $elena = collect($avail->json('therapists'))->firstWhere('id', $this->therapistA->id);
        $this->assertEquals('occupied', $elena['status']);

        // Second customer cannot book overlapping slot with therapistA
        $res2 = $this->postJson(route('booking.store'), $this->guestPayload([
            'room_id' => $this->room2->id,
            'guest_first_name' => 'Guest Two',
            'guest_phone' => '09170002222',
            'start_time' => '10:30',
        ]));
        $res2->assertStatus(422);
        $this->assertEquals(1, Appointment::count());
    }

    public function test_non_overlapping_and_five_minute_preparation_buffer_rules_for_pending_holds(): void
    {
        // Multi-service booking: 60m + 30m = 90m (10:00 - 11:30). Buffer applies ONCE at 11:30 - 11:35.
        $this->postJson(route('booking.store'), $this->guestPayload([
            'services' => [$this->singleService->id, $this->addonService->id],
            'start_time' => '10:00',
        ]))->assertStatus(200);

        // Before appointment (09:00 - 09:30 + 5m prep = 09:35): allowed before 10:00
        $this->postJson(route('booking.store'), $this->guestPayload([
            'services' => [$this->addonService->id],
            'room_id' => $this->room2->id,
            'guest_first_name' => 'Before Guest',
            'guest_phone' => '09170003331',
            'start_time' => '09:00',
        ]))->assertStatus(200);

        // Inside 5-min buffer (11:33): rejected
        $this->postJson(route('booking.store'), $this->guestPayload([
            'room_id' => $this->room2->id,
            'guest_first_name' => 'Buffer Guest',
            'guest_phone' => '09170003332',
            'start_time' => '11:33',
        ]))->assertStatus(422);

        // Exact buffer end (11:35): allowed
        $this->postJson(route('booking.store'), $this->guestPayload([
            'room_id' => $this->room2->id,
            'guest_first_name' => 'After Buffer Guest',
            'guest_phone' => '09170003333',
            'start_time' => '11:35',
        ]))->assertStatus(200);
    }

    public function test_multiple_preferred_therapists_are_held_and_max_rule_preserved(): void
    {
        // Couple service (2) + Single service (1) => max = 2 (NOT sum = 3)
        $this->postJson(route('booking.store'), $this->guestPayload([
            'services' => [$this->coupleService->id, $this->singleService->id],
            'preferred_staff_ids' => [$this->therapistA->id, $this->therapistB->id, $this->therapistC->id],
        ]))->assertStatus(422);

        $this->postJson(route('booking.store'), $this->guestPayload([
            'services' => [$this->coupleService->id, $this->singleService->id],
            'preferred_staff_ids' => [$this->therapistA->id, $this->therapistB->id],
        ]))->assertStatus(200);

        $occupancy = app(AppointmentOccupancyService::class);
        $this->assertTrue($occupancy->hasStaffConflict($this->therapistA->id, $this->date, '10:00:00', '12:00:00'));
        $this->assertTrue($occupancy->hasStaffConflict($this->therapistB->id, $this->date, '10:00:00', '12:00:00'));
        $this->assertFalse($occupancy->hasStaffConflict($this->therapistC->id, $this->date, '10:00:00', '12:00:00'));
    }

    public function test_failed_submission_and_transaction_rollback_leave_no_orphaned_hold(): void
    {
        // Failed validation / inactive room submission creates no hold
        $this->room1->update(['is_active' => false]);
        $this->postJson(route('booking.store'), $this->guestPayload())->assertStatus(422);

        $this->assertEquals(0, Appointment::count());
        $this->assertEquals(0, DB::table('appointment_preferred_staff')->count());
        $this->room1->update(['is_active' => true]);

        // Explicit transaction rollback leaves no appointment or orphaned pivot row
        try {
            DB::transaction(function () {
                $customer = Customer::create(['first_name' => 'Roll', 'last_name' => 'Back', 'phone_number' => '09179998888']);
                $appt = Appointment::create([
                    'customer_id' => $customer->id,
                    'room_id' => $this->room1->id,
                    'appointment_date' => $this->date,
                    'start_time' => '14:00:00',
                    'end_time' => '15:00:00',
                    'status' => 'pending',
                    'total_price' => 1000,
                ]);
                $appt->preferredStaff()->sync([$this->therapistA->id]);
                throw new \RuntimeException('Simulated failure');
            });
        } catch (\RuntimeException) {
        }

        $this->assertEquals(0, Appointment::count());
        $this->assertEquals(0, DB::table('appointment_preferred_staff')->count());
    }

    public function test_cancellation_releases_pending_preferred_therapist_hold(): void
    {
        $this->postJson(route('booking.store'), $this->guestPayload())->assertStatus(200);
        $appt = Appointment::latest('id')->first();

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.cancel', $appt), ['reason' => 'customer_cancelled'])
            ->assertRedirect();

        $this->assertEquals('cancelled', $appt->fresh()->status);
        $this->assertFalse(
            app(AppointmentOccupancyService::class)->hasStaffConflict($this->therapistA->id, $this->date, '10:00:00', '11:00:00')
        );

        // Another customer can now book therapistA at 10:00
        $this->postJson(route('booking.store'), $this->guestPayload([
            'room_id' => $this->room2->id,
            'guest_first_name' => 'Next Guest',
            'guest_phone' => '09170004444',
        ]))->assertStatus(200);
    }

    public function test_expired_pending_holds_after_24h_or_midnight_stop_blocking_without_cleanup(): void
    {
        $customer = Customer::create(['first_name' => 'Exp', 'last_name' => 'Test', 'phone_number' => '09170005555']);

        // 1. Over 24h old (still status='pending' in DB, no cleanup run)
        $expired24h = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $this->room1->id,
            'appointment_date' => $this->date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 1000,
        ]);
        $expired24h->services()->attach($this->singleService->id, ['price_at_booking' => 1000]);
        $expired24h->preferredStaff()->attach($this->therapistA->id);
        DB::table('appointments')->where('id', $expired24h->id)->update(['created_at' => now()->subHours(25)]);

        $this->assertEquals('pending', $expired24h->fresh()->status);
        $this->assertFalse(
            app(AppointmentOccupancyService::class)->hasStaffConflict($this->therapistA->id, $this->date, '10:00:00', '11:00:00')
        );

        // 2. Midnight appointment-date expiration (yesterday's appointment still status='pending')
        $yesterday = Carbon::yesterday('Asia/Manila')->toDateString();
        $expiredMidnight = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $this->room2->id,
            'appointment_date' => $yesterday,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 1000,
        ]);
        $expiredMidnight->services()->attach($this->singleService->id, ['price_at_booking' => 1000]);
        $expiredMidnight->preferredStaff()->attach($this->therapistB->id);

        $this->assertEquals('pending', $expiredMidnight->fresh()->status);
        $this->assertFalse(
            app(AppointmentOccupancyService::class)->hasStaffConflict($this->therapistB->id, $yesterday, '10:00:00', '11:00:00')
        );
    }

    public function test_confirmation_can_assign_same_preferred_therapist_without_self_conflict(): void
    {
        $this->postJson(route('booking.store'), $this->guestPayload())->assertStatus(200);
        $appt = Appointment::latest('id')->first();

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.confirm', $appt), [
                'staff_id' => $this->therapistA->id,
                'room_id' => $this->room1->id,
                'payment_type' => 'deposit',
                'payment_method' => 'cash',
                'amount' => 500,
            ])
            ->assertRedirect();

        $appt->refresh();
        $this->assertEquals('confirmed', $appt->status);
        $this->assertEquals($this->therapistA->id, $appt->user_id);

        // Counted once (overlapping fails, non-overlapping at 11:05 succeeds)
        $occupancy = app(AppointmentOccupancyService::class);
        $this->assertTrue($occupancy->hasStaffConflict($this->therapistA->id, $this->date, '10:00:00', '11:00:00'));
        $this->assertFalse($occupancy->hasStaffConflict($this->therapistA->id, $this->date, '11:05:00', '12:05:00'));
    }

    public function test_confirmation_can_assign_different_therapist_and_release_old_preference_hold(): void
    {
        $this->postJson(route('booking.store'), $this->guestPayload())->assertStatus(200);
        $appt = Appointment::latest('id')->first();

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.confirm', $appt), [
                'staff_id' => $this->therapistB->id,
                'room_id' => $this->room1->id,
                'payment_type' => 'deposit',
                'payment_method' => 'cash',
                'amount' => 500,
            ])
            ->assertRedirect();

        $this->assertEquals('confirmed', $appt->fresh()->status);

        $occupancy = app(AppointmentOccupancyService::class);
        $this->assertFalse($occupancy->hasStaffConflict($this->therapistA->id, $this->date, '10:00:00', '11:00:00'));
        $this->assertTrue($occupancy->hasStaffConflict($this->therapistB->id, $this->date, '10:00:00', '11:00:00'));
    }

    public function test_failed_confirmation_preserves_pending_booking_and_valid_preference_hold(): void
    {
        $this->postJson(route('booking.store'), $this->guestPayload())->assertStatus(200);
        $appt = Appointment::latest('id')->first();

        // Unavailable therapist causes confirmation to fail before assignment
        $this->therapistB->update(['is_active' => false]);
        $this->actingAs($this->receptionist)
            ->post(route('receptionist.confirm', $appt), [
                'staff_id' => $this->therapistB->id,
                'room_id' => $this->room1->id,
                'payment_type' => 'deposit',
                'payment_method' => 'cash',
                'amount' => 500,
            ])
            ->assertSessionHas('error');

        $appt->refresh();
        $this->assertEquals('pending', $appt->status);
        $this->assertNull($appt->user_id);
        $this->assertTrue(
            app(AppointmentOccupancyService::class)->hasStaffConflict($this->therapistA->id, $this->date, '10:00:00', '11:00:00')
        );
    }

    public function test_invalid_payment_type_and_invalid_deposit_leave_appointment_pending_and_unassigned(): void
    {
        $this->postJson(route('booking.store'), $this->guestPayload())->assertStatus(200);
        $appt = Appointment::latest('id')->first();

        // 1. Invalid payment type (cash_on_site when deposit is required)
        $this->actingAs($this->receptionist)
            ->post(route('receptionist.confirm', $appt), [
                'staff_id' => $this->therapistB->id,
                'room_id' => $this->room1->id,
                'payment_type' => 'cash_on_site',
                'payment_method' => 'cash',
                'amount' => 0,
            ])
            ->assertSessionHasErrors('payment_type');

        $appt->refresh();
        $this->assertEquals('pending', $appt->status);
        $this->assertNull($appt->user_id);
        $this->assertEquals(0, DB::table('appointment_staff')->where('appointment_id', $appt->id)->count());
        $this->assertTrue(
            app(AppointmentOccupancyService::class)->hasStaffConflict($this->therapistA->id, $this->date, '10:00:00', '11:00:00')
        );

        // 2. Invalid deposit amount (below 50% minimum = 500) must NOT write user_id or appointment_staff
        $this->actingAs($this->receptionist)
            ->post(route('receptionist.confirm', $appt), [
                'staff_id' => $this->therapistB->id,
                'room_id' => $this->room1->id,
                'payment_type' => 'deposit',
                'payment_method' => 'cash',
                'amount' => 100,
            ])
            ->assertSessionHas('error');

        $appt->refresh();
        $this->assertEquals('pending', $appt->status);
        $this->assertNull($appt->user_id);
        $this->assertEquals(0, DB::table('appointment_staff')->where('appointment_id', $appt->id)->count());
        $this->assertTrue(
            app(AppointmentOccupancyService::class)->hasStaffConflict($this->therapistA->id, $this->date, '10:00:00', '11:00:00')
        );
    }

    public function test_pending_bookings_view_displays_singular_plural_held_therapists_and_preselection_data(): void
    {
        $customer = Customer::create([
            'first_name' => 'UI',
            'last_name' => 'Tester',
            'phone_number' => '09178880001',
        ]);

        // 1. Single held therapist
        $singleAppt = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $this->room1->id,
            'appointment_date' => $this->date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 1000,
        ]);
        $singleAppt->services()->attach($this->singleService->id, ['price_at_booking' => 1000]);
        $singleAppt->preferredStaff()->sync([$this->therapistA->id]);

        // 2. Multiple held therapists (couple service)
        $multiAppt = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $this->room2->id,
            'appointment_date' => $this->date,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'status' => 'pending',
            'total_price' => 2200,
        ]);
        $multiAppt->services()->attach($this->coupleService->id, ['price_at_booking' => 2200]);
        $multiAppt->preferredStaff()->sync([$this->therapistA->id, $this->therapistB->id]);

        // 3. No preference
        $noPrefAppt = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $this->room1->id,
            'appointment_date' => $this->date,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'pending',
            'total_price' => 1000,
        ]);
        $noPrefAppt->services()->attach($this->singleService->id, ['price_at_booking' => 1000]);

        $res = $this->actingAs($this->receptionist)->get(route('receptionist.pending'));
        $res->assertOk()
            ->assertSee('Temporarily Reserved Therapist:')
            ->assertSee('Temporarily Reserved Therapists:')
            ->assertSee('No preference')
            ->assertSee('preselectHeldTherapists')
            ->assertSee("data-preferred-staff-ids='[" . $this->therapistA->id . "]'", false)
            ->assertSee("data-preferred-staff-ids='[" . $this->therapistA->id . ',' . $this->therapistB->id . "]'", false)
            ->assertSee("data-preferred-staff-ids='[]'", false);

        // Viewing/opening pending UI must never assign user_id or appointment_staff
        $this->assertNull($singleAppt->fresh()->user_id);
        $this->assertNull($multiAppt->fresh()->user_id);
        $this->assertEquals(0, DB::table('appointment_staff')->whereIn('appointment_id', [$singleAppt->id, $multiAppt->id])->count());
    }
}
