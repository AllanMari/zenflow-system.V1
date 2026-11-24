<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentPackageSelection;
use App\Models\BusinessException;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\PackageChoiceGroup;
use App\Models\PackageChoiceOption;
use App\Models\Payment;
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

/**
 * Focused coverage for the shared dynamic-availability implementation used by
 * Quick Book Future, Reschedule, Reassign and Add Service.
 */
class DynamicSchedulingWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    protected User $receptionist;
    protected User $therapistA;
    protected User $therapistB;
    protected User $therapistC;
    protected Room $room;
    protected Room $otherRoom;
    protected Service $service60;
    protected Service $service30;
    protected Service $twoTherapistService;
    protected string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'customer']);

        $this->receptionist = User::factory()->create(['is_active' => true]);
        $this->receptionist->roles()->attach($receptionistRole);

        $this->therapistA = User::factory()->create(['is_active' => true]);
        $this->therapistB = User::factory()->create(['is_active' => true]);
        $this->therapistC = User::factory()->create(['is_active' => true]);
        foreach ([$this->therapistA, $this->therapistB, $this->therapistC] as $staff) {
            $staff->roles()->attach($staffRole);
        }

        $this->date = Carbon::today('Asia/Manila')->addDay()->toDateString();

        for ($i = 0; $i < 7; $i++) {
            BusinessHour::create([
                'day_of_week' => $i,
                'open_time' => '08:00:00',
                'close_time' => '20:00:00',
                'is_closed' => false,
            ]);
        }

        foreach ([$this->therapistA, $this->therapistB, $this->therapistC] as $staff) {
            WorkSchedule::create([
                'user_id' => $staff->id,
                'day_of_week' => Carbon::parse($this->date)->dayOfWeek,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'is_day_off' => false,
            ]);
        }

        $category = ServiceCategory::create(['name' => 'Dynamic Tests', 'is_active' => true]);
        $roomCategory = RoomCategory::create(['name' => 'Dynamic Test Room', 'code' => 'dyn_room']);

        $this->service60 = Service::create([
            'name' => '60 min', 'category_id' => $category->id,
            'room_category_id' => $roomCategory->id, 'duration_minutes' => 60,
            'price' => 1000, 'requires_room' => true, 'is_active' => true,
        ]);
        $this->service30 = Service::create([
            'name' => '30 min', 'category_id' => $category->id,
            'room_category_id' => $roomCategory->id, 'duration_minutes' => 30,
            'price' => 500, 'requires_room' => true, 'is_active' => true,
        ]);
        $this->twoTherapistService = Service::create([
            'name' => 'Four Hands', 'category_id' => $category->id,
            'room_category_id' => $roomCategory->id, 'duration_minutes' => 60,
            'price' => 2000, 'requires_room' => true, 'is_active' => true,
            'therapists_required' => 2,
        ]);

        $this->room = Room::create(['name' => 'Room A', 'status' => 'available', 'is_active' => true]);
        $this->otherRoom = Room::create(['name' => 'Room B', 'status' => 'available', 'is_active' => true]);
        $this->room->roomCategories()->attach($roomCategory->id);
        $this->otherRoom->roomCategories()->attach($roomCategory->id);
    }

    private function appointment(
        User $primary,
        Room $room,
        string $start,
        string $end,
        $services = null,
        array $assisting = [],
        string $status = 'confirmed',
        ?string $date = null
    ): Appointment {
        $customer = Customer::create([
            'first_name' => 'Dynamic', 'last_name' => 'Customer',
            'phone_number' => '0912345' . random_int(1000, 9999),
            'customer_type' => 'regular',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $primary->id,
            'room_id' => $room->id,
            'appointment_date' => $date ?: $this->date,
            'start_time' => $start . ':00',
            'end_time' => $end . ':00',
            'status' => $status,
            'total_price' => 1000,
        ]);

        foreach (($services ?: collect([$this->service60])) as $service) {
            $appointment->services()->attach($service->id, [
                'price_at_booking' => $service->price,
                'service_duration' => $service->duration_minutes,
            ]);
        }
        $appointment->syncStaff($primary->id, $assisting);

        return $appointment;
    }

    // ------------------------------------------------------------------ Quick Book

    public function test_quick_book_future_offers_grid_and_exact_release_times(): void
    {
        $this->appointment($this->therapistA, $this->room, '13:00', '14:00');

        $response = $this->actingAs($this->receptionist)->getJson(route('api.staff-gaps', [
            'date' => $this->date,
            'duration' => 60,
            'staff_id' => $this->therapistA->id,
            'services' => [$this->service60->id],
        ]));

        $response->assertOk();
        $times = collect($response->json('gaps'))->pluck('time')->all();

        $this->assertContains('09:00', $times);
        $this->assertContains('14:05', $times, 'Exact release after treatment + buffer must be offered.');
        $this->assertContains('14:30', $times);
        $this->assertNotContains('14:00', $times);
    }

    public function test_quick_book_hides_exact_time_with_less_than_ten_minute_gain(): void
    {
        $this->appointment($this->therapistA, $this->room, '13:20', '14:20');

        $times = collect($this->actingAs($this->receptionist)->getJson(route('api.staff-gaps', [
            'date' => $this->date,
            'duration' => 60,
            'staff_id' => $this->therapistA->id,
            'services' => [$this->service60->id],
        ]))->json('gaps'))->pluck('time')->all();

        $this->assertNotContains('14:25', $times);
        $this->assertContains('14:30', $times);
    }

    public function test_quick_book_validates_the_selected_therapist_team_and_room(): void
    {
        // Therapist B is busy, but A is free. A two-therapist service must not
        // offer slots from A alone.
        $this->appointment($this->therapistB, $this->otherRoom, '13:00', '14:00');

        $alone = collect($this->actingAs($this->receptionist)->getJson(route('api.staff-gaps', [
            'date' => $this->date,
            'duration' => 60,
            'staff_id' => $this->therapistA->id,
            'services' => [$this->twoTherapistService->id],
        ]))->json('gaps'))->pluck('time')->all();
        // A single selected therapist cannot satisfy a two-therapist service.
        $this->assertEmpty($alone);

        $team = collect($this->actingAs($this->receptionist)->getJson(route('api.staff-gaps', [
            'date' => $this->date,
            'duration' => 60,
            'staff_id' => $this->therapistA->id,
            'assisting_staff_ids' => [$this->therapistB->id],
            'services' => [$this->twoTherapistService->id],
        ]))->json('gaps'))->pluck('time')->all();
        $this->assertNotContains('13:00', $team);
        $this->assertContains('14:05', $team);
    }

    // ------------------------------------------------------------------ Reschedule

    public function test_reschedule_availability_disables_dates_without_valid_times(): void
    {
        BusinessException::create([
            'date' => Carbon::parse($this->date)->addDay()->toDateString(),
            'title' => 'Spa closed', 'type' => 'holiday', 'is_closed' => true,
        ]);

        $appointment = $this->appointment($this->therapistA, $this->room, '13:00', '14:00');
        $month = Carbon::parse($this->date)->format('Y-m');

        $response = $this->actingAs($this->receptionist)->getJson(
            route('receptionist.reschedule.availability', $appointment->id) . '?' . http_build_query([
                'month' => $month,
                'date' => $this->date,
            ])
        );

        $response->assertOk();
        $dates = $response->json('dates');
        $this->assertContains($this->date, $dates);
        $this->assertNotContains(Carbon::parse($this->date)->addDay()->toDateString(), $dates);
        $this->assertNotEmpty($response->json('times'));
    }

    public function test_reschedule_availability_excludes_current_appointment_and_uses_preparation_buffer(): void
    {
        $appointment = $this->appointment($this->therapistA, $this->room, '11:00', '12:00');
        $this->appointment($this->therapistA, $this->room, '13:00', '14:00');

        $times = collect($this->actingAs($this->receptionist)->getJson(
            route('receptionist.reschedule.availability', $appointment->id) . '?' . http_build_query([
                'date' => $this->date,
            ])
        )->json('times'))->pluck('time')->all();

        // The appointment's own 11:00 window is not a conflict for itself.
        $this->assertContains('11:00', $times);
        // The other appointment occupies the same therapist/room until 14:05.
        $this->assertNotContains('14:00', $times);
        $this->assertContains('14:05', $times);
    }

    public function test_reschedule_preserves_team_room_and_combined_duration(): void
    {
        $appointment = $this->appointment(
            $this->therapistA,
            $this->room,
            '13:00',
            '14:30',
            collect([$this->service60, $this->service30])
        );

        $response = $this->actingAs($this->receptionist)->post(
            route('receptionist.reschedule', $appointment->id),
            [
                'appointment_date' => $this->date,
                'start_time' => '15:00',
                'room_id' => $this->room->id,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $appointment->refresh();
        $this->assertEquals('15:00:00', $appointment->start_time);
        // 60 + 30 minutes; preparation is NOT added to the stored end time.
        $this->assertEquals('16:30:00', $appointment->end_time);
        $this->assertEquals($this->room->id, $appointment->room_id);
        $this->assertEquals($this->therapistA->id, $appointment->user_id);
        $this->assertCount(1, $appointment->assignedStaff);
    }

    public function test_reschedule_rejects_time_that_changes_required_team(): void
    {
        $appointment = $this->appointment(
            $this->therapistA,
            $this->room,
            '13:00',
            '14:00',
            collect([$this->twoTherapistService]),
            [$this->therapistB->id]
        );

        // A single therapist cannot cover a two-therapist appointment.
        $appointment->syncStaff($this->therapistA->id, []);

        $response = $this->actingAs($this->receptionist)->post(
            route('receptionist.reschedule', $appointment->id),
            ['appointment_date' => $this->date, 'start_time' => '15:00', 'room_id' => $this->room->id]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals('13:00:00', $appointment->fresh()->start_time);
    }

    // ------------------------------------------------------------------ Reassign

    public function test_reassign_supports_multiple_therapists_and_rejects_duplicates(): void
    {
        $appointment = $this->appointment(
            $this->therapistA,
            $this->room,
            '13:00',
            '14:00',
            collect([$this->twoTherapistService]),
            [$this->therapistB->id]
        );

        $duplicate = $this->actingAs($this->receptionist)->post(
            route('receptionist.reassign', $appointment->id),
            ['staff_id' => $this->therapistA->id, 'assisting_staff_ids' => [$this->therapistA->id]]
        );
        $duplicate->assertSessionHas('error');
        $this->assertEqualsCanonicalizing(
            [$this->therapistA->id, $this->therapistB->id],
            $appointment->fresh()->assignedStaff->pluck('id')->all()
        );

        $success = $this->actingAs($this->receptionist)->post(
            route('receptionist.reassign', $appointment->id),
            ['staff_id' => $this->therapistA->id, 'assisting_staff_ids' => [$this->therapistC->id]]
        );
        $success->assertSessionHas('success');
        $this->assertEqualsCanonicalizing(
            [$this->therapistA->id, $this->therapistC->id],
            $appointment->fresh()->assignedStaff->pluck('id')->all()
        );
        $this->assertEquals($this->room->id, $appointment->fresh()->room_id);
    }

    public function test_reassign_rejects_unavailable_therapist_without_partial_save(): void
    {
        $appointment = $this->appointment(
            $this->therapistA,
            $this->room,
            '13:00',
            '14:00',
            collect([$this->twoTherapistService]),
            [$this->therapistB->id]
        );
        $this->appointment($this->therapistC, $this->otherRoom, '13:00', '14:00');

        $response = $this->actingAs($this->receptionist)->post(
            route('receptionist.reassign', $appointment->id),
            ['staff_id' => $this->therapistA->id, 'assisting_staff_ids' => [$this->therapistC->id]]
        );

        $response->assertSessionHas('error');
        $this->assertEqualsCanonicalizing(
            [$this->therapistA->id, $this->therapistB->id],
            $appointment->fresh()->assignedStaff->pluck('id')->all()
        );
    }

    // ------------------------------------------------------------------ Add Service

    public function test_add_service_recalculates_duration_price_and_requires_additional_therapist(): void
    {
        $appointment = $this->appointment($this->therapistA, $this->room, '13:00', '14:00');
        Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => 1000, 'type' => 'full', 'payment_method' => 'cash', 'paid_at' => now(),
        ]);

        $rejected = $this->actingAs($this->receptionist)->post(
            route('receptionist.add-extra', $appointment->id),
            ['custom_service_id' => $this->twoTherapistService->id]
        );
        $rejected->assertSessionHas('error');
        $this->assertCount(1, $appointment->fresh()->services);
        $this->assertEquals('14:00:00', $appointment->fresh()->end_time);

        $accepted = $this->actingAs($this->receptionist)->post(
            route('receptionist.add-extra', $appointment->id),
            [
                'custom_service_id' => $this->twoTherapistService->id,
                'therapist_ids' => [$this->therapistA->id, $this->therapistB->id],
            ]
        );

        $accepted->assertSessionHas('success');
        $appointment->refresh();
        $this->assertEquals('15:00:00', $appointment->end_time, '60 + 60 minutes');
        $this->assertEquals(3000, (float) $appointment->total_price);
        $this->assertCount(2, $appointment->services);
        $this->assertEqualsCanonicalizing(
            [$this->therapistA->id, $this->therapistB->id],
            $appointment->assignedStaff->pluck('id')->all()
        );
        // Financial history is untouched.
        $this->assertCount(1, $appointment->payments);
        $this->assertEquals(1000, (float) $appointment->payments()->sum('amount'));
    }

    public function test_add_service_preserves_package_choice_without_adding_alternative_as_service(): void
    {
        $package = Service::create([
            'name' => 'Package with choices', 'category_id' => $this->service60->category_id,
            'room_category_id' => $this->room->roomCategories()->first()->id,
            'duration_minutes' => 60, 'price' => 1200, 'requires_room' => true,
            'is_active' => true, 'is_package' => true,
        ]);
        $group = PackageChoiceGroup::create([
            'service_id' => $package->id,
            'label' => 'Choose one',
            'is_required' => true,
        ]);
        $optionA = Service::create([
            'name' => 'Option A', 'category_id' => $this->service60->category_id,
            'duration_minutes' => 30, 'price' => 300, 'is_active' => true,
        ]);
        $optionB = Service::create([
            'name' => 'Option B', 'category_id' => $this->service60->category_id,
            'duration_minutes' => 30, 'price' => 400, 'is_active' => true,
        ]);
        PackageChoiceOption::create(['choice_group_id' => $group->id, 'service_id' => $optionA->id]);
        PackageChoiceOption::create(['choice_group_id' => $group->id, 'service_id' => $optionB->id]);

        $appointment = $this->appointment($this->therapistA, $this->room, '13:00', '14:00');

        $missing = $this->actingAs($this->receptionist)->post(
            route('receptionist.add-extra', $appointment->id),
            ['custom_service_id' => $package->id]
        );
        $missing->assertSessionHas('error');

        $chosen = $this->actingAs($this->receptionist)->post(
            route('receptionist.add-extra', $appointment->id),
            [
                'custom_service_id' => $package->id,
                'package_choices' => [$group->id => $optionB->id],
            ]
        );

        $chosen->assertSessionHas('success');
        $appointment->refresh();
        $this->assertCount(2, $appointment->services, 'Only the selected service is added.');
        $selection = AppointmentPackageSelection::where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertEquals($optionB->id, $selection->selected_service_id);
        $this->assertEquals('Choose one', $selection->group_label_snapshot);
    }

    public function test_add_service_rejects_incompatible_room_without_changing_appointment(): void
    {
        $incompatibleCategory = RoomCategory::create(['name' => 'Other', 'code' => 'other']);
        $service = Service::create([
            'name' => 'Special Room Service', 'category_id' => $this->service60->category_id,
            'room_category_id' => $incompatibleCategory->id, 'duration_minutes' => 30,
            'price' => 700, 'requires_room' => true, 'is_active' => true,
        ]);

        $appointment = $this->appointment($this->therapistA, $this->room, '13:00', '14:00');

        $response = $this->actingAs($this->receptionist)->post(
            route('receptionist.add-extra', $appointment->id),
            ['custom_service_id' => $service->id]
        );

        $response->assertSessionHas('error');
        $appointment->refresh();
        $this->assertCount(1, $appointment->services);
        $this->assertEquals('14:00:00', $appointment->end_time);
        $this->assertEquals(1000, (float) $appointment->total_price);
    }
}
