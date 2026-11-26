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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestPrivacyConsentTest extends TestCase
{
    use RefreshDatabase;

    private function setupBookingEnvironment(Carbon $date): array
    {
        for ($i = 0; $i < 7; $i++) {
            BusinessHour::create([
                'day_of_week' => $i,
                'open_time' => '08:00:00',
                'close_time' => '20:00:00',
                'is_closed' => false,
            ]);
        }

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staff = User::factory()->create(['is_active' => true]);
        $staff->roles()->attach($staffRole);

        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_day_off' => false,
        ]);

        $cat = ServiceCategory::create(['name' => 'Massages', 'is_active' => true]);
        $roomCat = RoomCategory::firstOrCreate(['name' => 'Standard Room', 'code' => 'std_room']);
        $service = Service::create([
            'name' => 'Swedish Massage',
            'category_id' => $cat->id,
            'room_category_id' => $roomCat->id,
            'duration_minutes' => 60,
            'price' => 1000.00,
            'is_active' => true,
            'requires_room' => true,
            'therapists_required' => 1,
            'participants_required' => 1,
        ]);

        $room = Room::create(['name' => 'Room 1', 'status' => 'available', 'is_active' => true]);
        $room->roomCategories()->attach($roomCat->id);

        return ['service' => $service, 'staff' => $staff, 'room' => $room];
    }

    public function test_guest_booking_wizard_displays_unchecked_privacy_consent_checkbox_and_policy_link(): void
    {
        $tomorrow = Carbon::tomorrow();
        $this->setupBookingEnvironment($tomorrow);

        $response = $this->get(route('booking.wizard'));

        $response->assertOk();
        $response->assertSee('id="privacy_consented"', false);
        $response->assertSee(route('privacy'), false);
        $response->assertSee('privacyConsented: false', false);
    }

    public function test_guest_booking_without_consent_is_rejected_on_direct_http_post(): void
    {
        $tomorrow = Carbon::tomorrow();
        $env = $this->setupBookingEnvironment($tomorrow);

        // Missing privacy_consented
        $this->post(route('booking.store'), [
            'services' => [$env['service']->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'guest_first_name' => 'Guest One',
            'guest_phone' => '09171234567',
            'source' => 'public',
        ])->assertSessionHasErrors('privacy_consented');

        $this->assertDatabaseCount('appointments', 0);

        // False / '0' / forged values
        foreach (['0', false, 'false', 'no', 'accepted_bypass'] as $invalidValue) {
            $this->post(route('booking.store'), [
                'services' => [$env['service']->id],
                'appointment_date' => $tomorrow->toDateString(),
                'start_time' => '10:00',
                'guest_first_name' => 'Guest One',
                'guest_phone' => '09171234567',
                'privacy_consented' => $invalidValue,
                'source' => 'public',
            ])->assertSessionHasErrors('privacy_consented');
        }

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_guest_booking_with_accepted_consent_persists_timestamp_and_policy_version_per_booking(): void
    {
        $tomorrow = Carbon::tomorrow();
        $dayAfter = Carbon::tomorrow()->addDay();
        $env = $this->setupBookingEnvironment($tomorrow);

        WorkSchedule::create([
            'user_id' => $env['staff']->id,
            'day_of_week' => $dayAfter->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_day_off' => false,
        ]);

        // First guest booking
        $this->travelTo(now()->startOfMinute());
        $firstTime = now()->copy();

        $this->post(route('booking.store'), [
            'services' => [$env['service']->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'guest_first_name' => 'Repeat Guest',
            'guest_phone' => '09179998877',
            'privacy_consented' => '1',
            'source' => 'public',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $firstAppt = Appointment::firstOrFail();
        $this->assertNotNull($firstAppt->privacy_consented_at);
        $this->assertSame(config('app.privacy_policy_version'), $firstAppt->privacy_policy_version);
        $this->assertSame($firstTime->toDateTimeString(), $firstAppt->privacy_consented_at->toDateTimeString());

        // Repeat guest booking 2 hours later
        $this->travel(2)->hours();
        $secondTime = now()->copy();

        $this->post(route('booking.store'), [
            'services' => [$env['service']->id],
            'appointment_date' => $dayAfter->toDateString(),
            'start_time' => '11:00',
            'guest_first_name' => 'Repeat Guest',
            'guest_phone' => '09179998877',
            'privacy_consented' => '1',
            'source' => 'public',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseCount('appointments', 2);
        $secondAppt = Appointment::orderByDesc('id')->firstOrFail();

        $this->assertSame($firstAppt->customer_id, $secondAppt->customer_id);
        $this->assertNotNull($secondAppt->privacy_consented_at);
        $this->assertSame(config('app.privacy_policy_version'), $secondAppt->privacy_policy_version);
        $this->assertSame($secondTime->toDateTimeString(), $secondAppt->privacy_consented_at->toDateTimeString());

        // First appointment's consent history must remain untouched
        $this->assertSame($firstTime->toDateTimeString(), $firstAppt->fresh()->privacy_consented_at->toDateTimeString());
    }

    public function test_registered_customer_and_receptionist_booking_workflows_remain_unchanged(): void
    {
        $tomorrow = Carbon::tomorrow();
        $env = $this->setupBookingEnvironment($tomorrow);

        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $customerUser = User::factory()->create(['first_name' => 'Reg', 'last_name' => 'User']);
        $customerUser->roles()->attach($customerRole);
        Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Reg',
            'last_name' => 'User',
            'phone_number' => '09175551111',
            'customer_type' => 'regular',
        ]);

        // Registered customer does not require guest privacy_consented
        $this->actingAs($customerUser)
            ->post(route('booking.store'), [
                'services' => [$env['service']->id],
                'appointment_date' => $tomorrow->toDateString(),
                'start_time' => '09:00',
                'guest_first_name' => 'Reg User',
                'guest_phone' => '09175551111',
                'source' => 'public',
            ])->assertSessionHasNoErrors()->assertRedirect();

        $regAppt = Appointment::latest('id')->firstOrFail();
        $this->assertNull($regAppt->privacy_consented_at);
        $this->assertNull($regAppt->privacy_policy_version);

        // Receptionist booking does not require guest privacy_consented
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($receptionistRole);

        $walkInCustomer = Customer::create([
            'first_name' => 'Walk',
            'last_name' => 'In',
            'phone_number' => '09175552222',
            'customer_type' => 'regular',
        ]);

        $this->actingAs($receptionist)
            ->post(route('booking.store'), [
                'customer_id' => $walkInCustomer->id,
                'services' => [$env['service']->id],
                'appointment_date' => $tomorrow->toDateString(),
                'start_time' => '13:00',
                'guest_first_name' => 'Walk In',
                'guest_phone' => '09175552222',
                'staff_id' => $env['staff']->id,
                'room_id' => $env['room']->id,
                'source' => 'receptionist',
            ])->assertSessionHasNoErrors()->assertOk();

        $recAppt = Appointment::latest('id')->firstOrFail();
        $this->assertNull($recAppt->privacy_consented_at);
        $this->assertNull($recAppt->privacy_policy_version);
    }
}
