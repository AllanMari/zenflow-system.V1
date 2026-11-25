<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BusinessException;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\ScheduleException;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PreferredTherapistBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;
    private User $customerUser;
    private Customer $customerProfile;
    private User $therapistAvailable;
    private User $therapistDayOff;
    private User $therapistOnLeave;
    private User $therapistNotScheduled;
    private User $therapistOccupied;
    private Service $singleService;
    private Service $coupleService;
    private Room $room;
    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        $this->receptionist = User::factory()->create(['is_active' => true, 'can_manage_schedules' => true]);
        $this->receptionist->roles()->attach($receptionistRole);

        $this->customerUser = User::factory()->create(['first_name' => 'Maria', 'last_name' => 'Santos', 'is_active' => true]);
        $this->customerUser->roles()->attach($customerRole);
        $this->customerProfile = Customer::create([
            'user_id' => $this->customerUser->id,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'phone_number' => '09171110001',
        ]);

        $this->therapistAvailable = User::factory()->create(['first_name' => 'Elena', 'last_name' => 'PrivateLast', 'is_active' => true]);
        $this->therapistDayOff = User::factory()->create(['first_name' => 'Marco', 'last_name' => 'HiddenLast', 'is_active' => true]);
        $this->therapistOnLeave = User::factory()->create(['first_name' => 'Sofia', 'last_name' => 'SecretLast', 'is_active' => true]);
        $this->therapistNotScheduled = User::factory()->create(['first_name' => 'Liam', 'last_name' => 'NoSchedLast', 'is_active' => true]);
        $this->therapistOccupied = User::factory()->create(['first_name' => 'Noah', 'last_name' => 'BusyLast', 'is_active' => true]);

        foreach ([
            $this->therapistAvailable,
            $this->therapistDayOff,
            $this->therapistOnLeave,
            $this->therapistNotScheduled,
            $this->therapistOccupied,
        ] as $t) {
            $t->roles()->attach($staffRole);
        }

        $this->date = Carbon::today('Asia/Manila')->addDays(2)->toDateString();
        $dow = Carbon::parse($this->date, 'Asia/Manila')->dayOfWeek;

        for ($d = 0; $d <= 6; $d++) {
            BusinessHour::create([
                'day_of_week' => $d,
                'open_time' => '08:00:00',
                'close_time' => '20:00:00',
                'is_closed' => false,
            ]);
        }

        WorkSchedule::create(['user_id' => $this->therapistAvailable->id, 'day_of_week' => $dow, 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_day_off' => false]);
        WorkSchedule::create(['user_id' => $this->therapistDayOff->id, 'day_of_week' => $dow, 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_day_off' => true]);
        WorkSchedule::create(['user_id' => $this->therapistOnLeave->id, 'day_of_week' => $dow, 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_day_off' => false]);
        WorkSchedule::create(['user_id' => $this->therapistOccupied->id, 'day_of_week' => $dow, 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_day_off' => false]);
        // $this->therapistNotScheduled intentionally has ZERO WorkSchedule rows

        ScheduleException::create([
            'user_id' => $this->therapistOnLeave->id,
            'exception_date' => $this->date,
            'type' => 'sick_leave',
            'reason' => 'Confidential medical surgery',
        ]);

        $cat = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);
        $roomCat = RoomCategory::create(['name' => 'Standard Room', 'code' => 'std_pref']);
        $this->singleService = Service::create([
            'name' => 'Swedish 60',
            'category_id' => $cat->id,
            'room_category_id' => $roomCat->id,
            'duration_minutes' => 60,
            'price' => 1000,
            'therapists_required' => 1,
            'requires_room' => true,
            'is_active' => true,
        ]);
        $this->coupleService = Service::create([
            'name' => 'Couples 60',
            'category_id' => $cat->id,
            'room_category_id' => $roomCat->id,
            'duration_minutes' => 60,
            'price' => 2000,
            'therapists_required' => 2,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $this->room = Room::create(['name' => 'Suite 1', 'status' => 'available', 'is_active' => true]);
        $this->room->roomCategories()->attach($roomCat->id);

        // Existing appointment for $therapistOccupied from 10:00 to 11:00 -> occupied until 11:05 (5-min buffer)
        $otherCust = Customer::create(['first_name' => 'Other', 'last_name' => 'Client', 'phone_number' => '09170009999']);
        $busyAppt = Appointment::create([
            'customer_id' => $otherCust->id,
            'user_id' => $this->therapistOccupied->id,
            'room_id' => $this->room->id,
            'appointment_date' => $this->date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);
        $busyAppt->services()->attach($this->singleService->id, ['service_duration' => 60, 'price_at_booking' => 1000]);
        $busyAppt->assignedStaff()->sync([$this->therapistOccupied->id => ['role' => 'primary']]);
    }

    public function test_preferred_therapists_endpoint_returns_sanitized_statuses_buffer_and_custom_hours(): void
    {
        // Request at 11:00 overlaps 10:00-11:05 (5-min preparation buffer) for Noah
        $res = $this->getJson(route('api.booking.preferred-therapists', [
            'date' => $this->date,
            'start_time' => '11:00',
            'services' => [$this->singleService->id],
        ]))->assertOk();

        $res->assertJsonPath('required_count', 1);
        $byId = collect($res->json('therapists'))->keyBy('id');

        $this->assertEquals('Available', $byId[$this->therapistAvailable->id]['label']);
        $this->assertEquals('Elena', $byId[$this->therapistAvailable->id]['first_name']);
        $this->assertEquals('Day Off', $byId[$this->therapistDayOff->id]['label']);
        $this->assertEquals('On Leave', $byId[$this->therapistOnLeave->id]['label']);
        $this->assertEquals('Not Scheduled', $byId[$this->therapistNotScheduled->id]['label']);
        $this->assertEquals('Occupied', $byId[$this->therapistOccupied->id]['label']);

        // Never expose last names or private leave reasons
        $raw = $res->getContent();
        $this->assertStringNotContainsString('PrivateLast', $raw);
        $this->assertStringNotContainsString('SecretLast', $raw);
        $this->assertStringNotContainsString('Confidential medical surgery', $raw);

        // Custom hours when WorkSchedule rows are absent makes Liam Available at 11:00
        ScheduleException::create([
            'user_id' => $this->therapistNotScheduled->id,
            'exception_date' => $this->date,
            'type' => 'custom_hours',
            'start_time' => '10:00:00',
            'end_time' => '16:00:00',
        ]);

        $resCustom = $this->getJson(route('api.booking.preferred-therapists', [
            'date' => $this->date,
            'start_time' => '11:00',
            'services' => [$this->singleService->id],
        ]))->assertOk();
        $byIdCustom = collect($resCustom->json('therapists'))->keyBy('id');
        $this->assertEquals('Available', $byIdCustom[$this->therapistNotScheduled->id]['label']);

        // Business closure marks all therapists Not Scheduled
        $closure = BusinessException::create([
            'date' => $this->date,
            'title' => 'Holiday',
            'type' => 'holiday',
            'is_closed' => true,
        ]);
        $resClosed = $this->getJson(route('api.booking.preferred-therapists', [
            'date' => $this->date,
            'start_time' => '11:00',
            'services' => [$this->singleService->id],
        ]))->assertOk();
        $byIdClosed = collect($resClosed->json('therapists'))->keyBy('id');
        $this->assertEquals('Not Scheduled', $byIdClosed[$this->therapistAvailable->id]['label']);
        $closure->delete();

        // Special opening overrides closed BusinessHour
        $dow = Carbon::parse($this->date, 'Asia/Manila')->dayOfWeek;
        BusinessHour::where('day_of_week', $dow)->update(['is_closed' => true]);
        BusinessException::create([
            'date' => $this->date,
            'title' => 'Special Open',
            'type' => 'special_hours',
            'is_closed' => false,
            'open_time' => '09:00:00',
            'close_time' => '18:00:00',
        ]);
        $resSpecial = $this->getJson(route('api.booking.preferred-therapists', [
            'date' => $this->date,
            'start_time' => '11:00',
            'services' => [$this->singleService->id],
        ]))->assertOk();
        $byIdSpecial = collect($resSpecial->json('therapists'))->keyBy('id');
        $this->assertEquals('Available', $byIdSpecial[$this->therapistAvailable->id]['label']);
    }

    public function test_required_count_follows_max_and_rejects_excess_or_duplicates(): void
    {
        // max(1, 2) = 2 (NOT 1 + 2 = 3)
        $res = $this->getJson(route('api.booking.preferred-therapists', [
            'date' => $this->date,
            'start_time' => '13:00',
            'services' => [$this->singleService->id, $this->coupleService->id],
        ]))->assertOk();
        $res->assertJsonPath('required_count', 2);

        // Single-therapist service rejects 2 preferences
        $this->post(route('booking.store'), [
            'services' => [$this->singleService->id],
            'appointment_date' => $this->date,
            'start_time' => '13:00',
            'guest_first_name' => 'Guest One',
            'guest_phone' => '09171234567',
            'preferred_staff_ids' => [$this->therapistAvailable->id, $this->therapistOccupied->id],
        ])->assertSessionHas('error');

        // Duplicate IDs rejected even when required_count is 2
        $this->post(route('booking.store'), [
            'services' => [$this->coupleService->id],
            'appointment_date' => $this->date,
            'start_time' => '13:00',
            'guest_first_name' => 'Guest Two',
            'guest_phone' => '09171234568',
            'preferred_staff_ids' => [$this->therapistAvailable->id, $this->therapistAvailable->id],
        ])->assertSessionHas('error');
    }

    public function test_guest_and_registered_customer_bookings_persist_preferences_without_reserving_therapists(): void
    {
        // 1. Guest booking with 1 preferred therapist and room preference
        $this->post(route('booking.store'), [
            'services' => [$this->singleService->id],
            'appointment_date' => $this->date,
            'start_time' => '13:00',
            'room_id' => $this->room->id,
            'guest_first_name' => 'Guest Client',
            'guest_phone' => '09178887777',
            'preferred_staff_ids' => [$this->therapistAvailable->id],
        ])->assertRedirect();

        $guestAppt = Appointment::where('guest_first_name', 'Guest Client')->firstOrFail();
        $this->assertEquals('pending', $guestAppt->status);
        $this->assertNull($guestAppt->user_id);
        $this->assertEquals($this->room->id, $guestAppt->requested_room_id);
        $this->assertEquals(0, DB::table('appointment_staff')->where('appointment_id', $guestAppt->id)->count());
        $this->assertEquals([$this->therapistAvailable->id], $guestAppt->preferredStaff()->pluck('users.id')->all());

        // 2. Registered customer booking with 2 preferred therapists for couple service
        $this->actingAs($this->customerUser)->post(route('booking.store'), [
            'services' => [$this->coupleService->id],
            'appointment_date' => $this->date,
            'start_time' => '15:00',
            'guest_first_name' => 'Maria Santos',
            'guest_phone' => '09171110001',
            'preferred_staff_ids' => [$this->therapistAvailable->id, $this->therapistOccupied->id],
        ])->assertRedirect();

        $custAppt = Appointment::where('customer_id', $this->customerProfile->id)->firstOrFail();
        $this->assertEquals('pending', $custAppt->status);
        $this->assertNull($custAppt->user_id);
        $this->assertEquals(0, DB::table('appointment_staff')->where('appointment_id', $custAppt->id)->count());
        $this->assertEqualsCanonicalizing(
            [$this->therapistAvailable->id, $this->therapistOccupied->id],
            $custAppt->preferredStaff()->pluck('users.id')->all()
        );

        // 3. Unavailable preferred therapist is rejected
        $this->post(route('booking.store'), [
            'services' => [$this->singleService->id],
            'appointment_date' => $this->date,
            'start_time' => '13:00',
            'guest_first_name' => 'Bad Pref',
            'guest_phone' => '09179998888',
            'preferred_staff_ids' => [$this->therapistOnLeave->id],
        ])->assertSessionHas('error');
        $this->assertNull(Appointment::where('guest_first_name', 'Bad Pref')->first());
    }

    public function test_receptionist_pending_view_displays_preferences_and_confirmation_assigns_independently(): void
    {
        $room2 = Room::create(['name' => 'Suite 2', 'status' => 'available', 'is_active' => true]);
        $roomCat = RoomCategory::first();
        $room2->roomCategories()->attach($roomCat->id);

        $appt = Appointment::create([
            'customer_id' => $this->customerProfile->id,
            'user_id' => null,
            'room_id' => $room2->id,
            'requested_room_id' => $room2->id,
            'appointment_date' => $this->date,
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'status' => 'pending',
            'total_price' => 1000,
        ]);
        $appt->services()->attach($this->singleService->id, ['service_duration' => 60, 'price_at_booking' => 1000]);
        $appt->preferredStaff()->sync([$this->therapistAvailable->id]);

        $this->actingAs($this->receptionist)->get(route('receptionist.pending'))
            ->assertOk()
            ->assertSee('Temporarily Reserved Therapist')
            ->assertSee('Elena')
            ->assertSee('(Available)');

        // Receptionist can confirm using another eligible therapist (e.g. Noah)
        $this->actingAs($this->receptionist)->post(route('receptionist.confirm', $appt), [
            'staff_id' => $this->therapistOccupied->id,
            'room_id' => $room2->id,
            'payment_method' => 'cash',
            'payment_type' => 'full',
            'amount' => 1000,
        ])->assertRedirect();

        $appt->refresh();
        $this->assertEquals('confirmed', $appt->status);
        $this->assertEquals($this->therapistOccupied->id, $appt->user_id);
        // Original preference remains intact for auditability
        $this->assertEquals([$this->therapistAvailable->id], $appt->preferredStaff()->pluck('users.id')->all());
    }

    public function test_wizard_renders_notices_and_pending_bookings_sort_by_booking_date(): void
    {
        $this->get(route('booking.wizard'))
            ->assertOk()
            ->assertSee('Room Reservation')
            ->assertSee('Your selected room is temporarily reserved while your booking is pending. This reservation ends if your booking expires or is cancelled.')
            ->assertSee('Preferred Therapist Request')
            ->assertSee('This therapist is currently shown as available for your selected schedule. Your selection is a preference request, not a confirmed assignment. The receptionist will finalize the therapist assignment when confirming your booking.')
            ->assertSee('Your selected room will be temporarily reserved once your booking is submitted, subject to confirmation and expiration.')
            ->assertSee('Preference only; final assignment is made by the receptionist during confirmation.')
            ->assertSee('data-slot-anchor', false);

        // Create two pending appointments with distinct created_at timestamps
        $older = Appointment::create([
            'customer_id' => $this->customerProfile->id,
            'user_id' => null,
            'room_id' => $this->room->id,
            'appointment_date' => $this->date,
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'status' => 'pending',
            'total_price' => 1000,
            'guest_first_name' => 'FirstSubmitted',
            'guest_phone' => '09171110011',
        ]);
        $older->forceFill(['created_at' => now()->subHours(2)])->save();
        $older->services()->attach($this->singleService->id, ['service_duration' => 60, 'price_at_booking' => 1000]);

        $newer = Appointment::create([
            'customer_id' => $this->customerProfile->id,
            'user_id' => null,
            'room_id' => $this->room->id,
            'appointment_date' => $this->date,
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'status' => 'pending',
            'total_price' => 1000,
            'guest_first_name' => 'SecondSubmitted',
            'guest_phone' => '09171110022',
        ]);
        $newer->forceFill(['created_at' => now()->subMinute()])->save();
        $newer->services()->attach($this->singleService->id, ['service_duration' => 60, 'price_at_booking' => 1000]);

        // Equal-timestamp tie-breaker uses appointment ID deterministically
        $tieTime = now()->subMinutes(10);
        $tieFirst = Appointment::create([
            'customer_id' => $this->customerProfile->id,
            'user_id' => null,
            'room_id' => $this->room->id,
            'appointment_date' => $this->date,
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
            'status' => 'pending',
            'total_price' => 1000,
            'guest_first_name' => 'TieLowerId',
            'guest_phone' => '09171110033',
        ]);
        $tieFirst->forceFill(['created_at' => $tieTime])->save();
        $tieFirst->services()->attach($this->singleService->id, ['service_duration' => 60, 'price_at_booking' => 1000]);

        $tieSecond = Appointment::create([
            'customer_id' => $this->customerProfile->id,
            'user_id' => null,
            'room_id' => $this->room->id,
            'appointment_date' => $this->date,
            'start_time' => '17:00:00',
            'end_time' => '18:00:00',
            'status' => 'pending',
            'total_price' => 1000,
            'guest_first_name' => 'TieHigherId',
            'guest_phone' => '09171110044',
        ]);
        $tieSecond->forceFill(['created_at' => $tieTime])->save();
        $tieSecond->services()->attach($this->singleService->id, ['service_duration' => 60, 'price_at_booking' => 1000]);

        // Default (Earliest Request First)
        $this->actingAs($this->receptionist)->get(route('receptionist.pending'))
            ->assertOk()
            ->assertSee('Sort by Request Date')
            ->assertSee('Earliest Request First')
            ->assertSee('Latest Request First')
            ->assertSee('Customers who submitted their booking requests earlier appear at the top.')
            ->assertSee('The newest booking requests appear at the top.')
            ->assertSeeInOrder(['FirstSubmitted', 'TieLowerId', 'TieHigherId', 'SecondSubmitted']);

        // Latest Request First
        $this->actingAs($this->receptionist)->get(route('receptionist.pending', ['sort' => 'newest']))
            ->assertOk()
            ->assertSeeInOrder(['SecondSubmitted', 'TieHigherId', 'TieLowerId', 'FirstSubmitted']);
    }

    public function test_silent_availability_polling_does_not_toggle_loading_slots(): void
    {
        $this->get(route('booking.wizard'))
            ->assertOk()
            ->assertSee('if (!silent) {', false);
    }
}
