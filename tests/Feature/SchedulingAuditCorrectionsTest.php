<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentPackageSelection;
use App\Models\Attendance;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\PackageChoiceGroup;
use App\Models\PackageChoiceOption;
use App\Models\Payment;
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
use Tests\TestCase;

/**
 * Regression coverage for the six findings raised by the pre-commit audit.
 */
class SchedulingAuditCorrectionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $receptionist;
    protected User $therapistA;
    protected User $therapistB;
    protected User $therapistC;
    protected Room $roomA;
    protected Room $roomB;
    protected Service $service60;
    protected Service $twoTherapistService;
    protected string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'customer']);
        Role::firstOrCreate(['name' => 'admin']);

        $this->receptionist = User::factory()->create(['is_active' => true]);
        $this->receptionist->roles()->attach($receptionistRole);

        $this->therapistA = User::factory()->create(['is_active' => true]);
        $this->therapistB = User::factory()->create(['is_active' => true]);
        $this->therapistC = User::factory()->create(['is_active' => true]);
        foreach ([$this->therapistA, $this->therapistB, $this->therapistC] as $staff) {
            $staff->roles()->attach($staffRole);
        }

        $this->date = Carbon::today('Asia/Manila')->addDay()->toDateString();

        for ($day = 0; $day <= 6; $day++) {
            BusinessHour::create([
                'day_of_week' => $day,
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

        $category = ServiceCategory::create(['name' => 'Audit', 'is_active' => true]);
        $roomCategory = RoomCategory::create(['name' => 'Audit Room', 'code' => 'audit_room']);

        $this->service60 = Service::create([
            'name' => 'Audit 60', 'category_id' => $category->id,
            'room_category_id' => $roomCategory->id, 'duration_minutes' => 60,
            'price' => 1000, 'requires_room' => true, 'is_active' => true,
        ]);
        $this->twoTherapistService = Service::create([
            'name' => 'Audit Four Hands', 'category_id' => $category->id,
            'room_category_id' => $roomCategory->id, 'duration_minutes' => 60,
            'price' => 2000, 'requires_room' => true, 'is_active' => true,
            'therapists_required' => 2,
        ]);

        $this->roomA = Room::create(['name' => 'Audit Room A', 'status' => 'available', 'is_active' => true]);
        $this->roomB = Room::create(['name' => 'Audit Room B', 'status' => 'available', 'is_active' => true]);
        $this->roomA->roomCategories()->attach($roomCategory->id);
        $this->roomB->roomCategories()->attach($roomCategory->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function customer(): Customer
    {
        return Customer::create([
            'first_name' => 'Audit', 'last_name' => 'Customer',
            'phone_number' => '0912345' . random_int(1000, 9999),
            'customer_type' => 'regular',
        ]);
    }

    private function appointment(
        User $primary,
        Room $room,
        string $start,
        string $end,
        Service $service,
        array $assisting = [],
        string $status = 'confirmed',
        ?string $date = null
    ): Appointment {
        $appointment = Appointment::create([
            'customer_id' => $this->customer()->id,
            'user_id' => $primary->id,
            'room_id' => $room->id,
            'appointment_date' => $date ?: $this->date,
            'start_time' => $start . ':00',
            'end_time' => $end . ':00',
            'status' => $status,
            'total_price' => $service->price,
        ]);
        $appointment->services()->attach($service->id, [
            'price_at_booking' => $service->price,
            'service_name' => $service->name,
            'service_duration' => $service->duration_minutes,
        ]);
        $appointment->syncStaff($primary->id, $assisting);

        return $appointment;
    }

    private function quickBookPayload(
        int $primary,
        array $assisting,
        ?int $serviceId = null,
        ?string $start = null,
        ?int $roomId = null
    ): array {
        return [
            'source' => 'receptionist',
            'guest_first_name' => 'Audit Guest',
            'guest_phone' => '09123456789',
            'services' => [$serviceId ?: $this->twoTherapistService->id],
            'appointment_date' => $this->date,
            'start_time' => $start ?: '13:00',
            'end_time' => '14:00',
            'staff_id' => $primary,
            'assisting_staff_ids' => $assisting,
            'room_id' => $roomId ?: $this->roomA->id,
            'payment_method' => 'cash',
            'payment_amount' => 0,
            'payment_type' => 'full',
        ];
    }

    // ---------------------------------------------------------- Finding 1

    public function test_only_one_of_two_competing_bookings_for_the_same_therapist_can_succeed(): void
    {
        $payload = $this->quickBookPayload($this->therapistA->id, [$this->therapistB->id]);

        $first = $this->actingAs($this->receptionist)->postJson(route('booking.store'), $payload);
        $second = $this->actingAs($this->receptionist)->postJson(route('booking.store'), $payload);

        $first->assertOk();
        $second->assertStatus(422);
        $this->assertEquals(1, Appointment::whereDate('appointment_date', $this->date)
            ->where('start_time', '13:00:00')->count());
    }

    public function test_only_one_of_two_competing_bookings_for_the_same_room_can_succeed(): void
    {
        $first = $this->actingAs($this->receptionist)->postJson(route('booking.store'),
            $this->quickBookPayload($this->therapistA->id, [$this->therapistB->id], null, '13:00', $this->roomA->id));
        $second = $this->actingAs($this->receptionist)->postJson(route('booking.store'),
            $this->quickBookPayload($this->therapistC->id, [$this->therapistB->id], null, '13:00', $this->roomA->id));

        $first->assertOk();
        $second->assertStatus(422);
        $this->assertEquals(1, Appointment::where('room_id', $this->roomA->id)
            ->where('start_time', '13:00:00')->count());
    }

    // ---------------------------------------------------------- Finding 2

    public function test_inactive_assisting_therapist_is_rejected_without_partial_assignment(): void
    {
        $this->therapistB->update(['is_active' => false]);

        $response = $this->actingAs($this->receptionist)->postJson(route('booking.store'),
            $this->quickBookPayload($this->therapistA->id, [$this->therapistB->id]));

        $response->assertStatus(422);
        $this->assertEquals(0, Appointment::whereDate('appointment_date', $this->date)
            ->where('start_time', '13:00:00')->count());
        $this->assertEquals(0, \Illuminate\Support\Facades\DB::table('appointment_staff')
            ->where('user_id', $this->therapistB->id)->count());
    }

    public function test_non_staff_assisting_user_is_rejected(): void
    {
        $customerRoleUser = User::factory()->create(['is_active' => true]);
        $customerRoleUser->roles()->attach(Role::where('name', 'customer')->first());

        $response = $this->actingAs($this->receptionist)->postJson(route('booking.store'),
            $this->quickBookPayload($this->therapistA->id, [$customerRoleUser->id]));

        $response->assertStatus(422);
        $this->assertEquals(0, Appointment::where('start_time', '13:00:00')->count());
    }

    public function test_assisting_therapist_on_leave_is_rejected(): void
    {
        ScheduleException::create([
            'user_id' => $this->therapistB->id,
            'exception_date' => $this->date,
            'type' => 'sick_leave',
            'reason' => 'Sick',
        ]);

        $response = $this->actingAs($this->receptionist)->postJson(route('booking.store'),
            $this->quickBookPayload($this->therapistA->id, [$this->therapistB->id]));

        $response->assertStatus(422);
        $this->assertEquals(0, Appointment::where('start_time', '13:00:00')->count());
    }

    public function test_assisting_therapist_outside_shift_is_rejected(): void
    {
        WorkSchedule::where('user_id', $this->therapistB->id)
            ->where('day_of_week', Carbon::parse($this->date)->dayOfWeek)
            ->update(['start_time' => '15:00:00', 'end_time' => '20:00:00']);

        $response = $this->actingAs($this->receptionist)->postJson(route('booking.store'),
            $this->quickBookPayload($this->therapistA->id, [$this->therapistB->id]));

        $response->assertStatus(422);
        $this->assertEquals(0, Appointment::where('start_time', '13:00:00')->count());
    }

    public function test_occupied_assisting_therapist_is_rejected_and_valid_team_succeeds(): void
    {
        $this->appointment($this->therapistB, $this->roomB, '13:00', '14:00', $this->service60);

        $busy = $this->actingAs($this->receptionist)->postJson(route('booking.store'),
            $this->quickBookPayload($this->therapistA->id, [$this->therapistB->id]));
        $busy->assertStatus(422);
        $this->assertEquals(0, Appointment::where('start_time', '13:00:00')
            ->where('user_id', $this->therapistA->id)->count());

        $ok = $this->actingAs($this->receptionist)->postJson(route('booking.store'),
            $this->quickBookPayload($this->therapistA->id, [$this->therapistC->id]));
        $ok->assertOk();
        $appointment = Appointment::where('user_id', $this->therapistA->id)
            ->where('start_time', '13:00:00')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$this->therapistA->id, $this->therapistC->id],
            $appointment->assignedStaff->pluck('id')->all()
        );
    }

    // ---------------------------------------------------------- Finding 3

    public function test_two_eligible_therapists_produce_slots_and_one_unavailable_therapist_blocks_them(): void
    {
        $params = [
            'date' => $this->date,
            'duration' => 60,
            'staff_id' => $this->therapistA->id,
            'assisting_staff_ids' => [$this->therapistB->id],
            'services' => [$this->twoTherapistService->id],
        ];

        $team = $this->actingAs($this->receptionist)->getJson(route('api.staff-gaps', $params));
        $team->assertOk();
        $this->assertContains('13:00', collect($team->json('gaps'))->pluck('time')->all());

        $this->appointment($this->therapistB, $this->roomB, '13:00', '14:00', $this->service60);
        $busy = $this->actingAs($this->receptionist)->getJson(route('api.staff-gaps', $params));
        $this->assertNotContains('13:00', collect($busy->json('gaps'))->pluck('time')->all());
    }

    public function test_multiple_services_use_maximum_concurrent_requirement_not_the_sum(): void
    {
        $service30 = Service::create([
            'name' => 'Audit 30', 'category_id' => $this->service60->category_id,
            'room_category_id' => $this->roomA->roomCategories()->first()->id,
            'duration_minutes' => 30, 'price' => 500, 'requires_room' => true, 'is_active' => true,
        ]);

        $params = [
            'date' => $this->date,
            'duration' => 90,
            'staff_id' => $this->therapistA->id,
            'assisting_staff_ids' => [$this->therapistB->id],
            'services' => [$service30->id, $this->twoTherapistService->id],
        ];

        $times = collect($this->actingAs($this->receptionist)
            ->getJson(route('api.staff-gaps', $params))->json('gaps'))->pluck('time')->all();
        $this->assertContains('13:00', $times, '1 + 2 therapists must resolve to 2, not 3.');

        $understaffed = array_merge($params, ['assisting_staff_ids' => []]);
        $this->assertEmpty($this->actingAs($this->receptionist)
            ->getJson(route('api.staff-gaps', $understaffed))->json('gaps'));
    }

    public function test_package_alternatives_do_not_inflate_required_therapist_count(): void
    {
        $package = Service::create([
            'name' => 'Audit Package', 'category_id' => $this->service60->category_id,
            'room_category_id' => $this->roomA->roomCategories()->first()->id,
            'duration_minutes' => 60, 'price' => 1500, 'requires_room' => true,
            'is_active' => true, 'is_package' => true,
        ]);
        $group = PackageChoiceGroup::create([
            'service_id' => $package->id, 'label' => 'Pick one', 'is_required' => true,
        ]);
        foreach ([1, 2, 3] as $index) {
            $option = Service::create([
                'name' => 'Option ' . $index, 'category_id' => $package->category_id,
                'duration_minutes' => 30, 'price' => 300, 'is_active' => true,
                'therapists_required' => 3,
            ]);
            PackageChoiceOption::create(['choice_group_id' => $group->id, 'service_id' => $option->id]);
        }

        // Only the selected PACKAGE is sent; alternatives are never added as
        // simultaneous services, so the package's own requirement applies.
        $times = collect($this->actingAs($this->receptionist)->getJson(route('api.staff-gaps', [
            'date' => $this->date,
            'duration' => 60,
            'staff_id' => $this->therapistA->id,
            'services' => [$package->id],
        ]))->json('gaps'))->pluck('time')->all();

        $this->assertContains('13:00', $times);
    }

    // ---------------------------------------------------------- Finding 4

    public function test_same_day_availability_uses_strict_shift_for_reschedule_but_right_now_keeps_extension(): void
    {
        $today = Carbon::today('Asia/Manila')->toDateString();
        Carbon::setTestNow(Carbon::parse($today . ' 14:30:00', 'Asia/Manila'));

        WorkSchedule::updateOrCreate(
            [
                'user_id' => $this->therapistA->id,
                'day_of_week' => Carbon::parse($today)->dayOfWeek,
            ],
            [
                'start_time' => '09:00:00',
                'end_time' => '15:00:00',
                'is_day_off' => false,
            ]
        );

        Attendance::create([
            'user_id' => $this->therapistA->id,
            'date' => $today,
            'status' => 'present',
            'check_in' => '09:00:00',
            'check_out' => null,
        ]);

        $appointment = $this->appointment(
            $this->therapistA,
            $this->roomA,
            '10:00',
            '11:00',
            $this->service60,
            [],
            'confirmed',
            $today
        );

        // Reschedule availability uses the real shift: 14:00 would finish at
        // 15:00 and keep the room/therapist until 15:05, so it is not offered.
        $times = collect($this->actingAs($this->receptionist)->getJson(
            route('receptionist.reschedule.availability', $appointment->id) . '?' . http_build_query([
                'date' => $today,
                'room_id' => $this->roomA->id,
            ])
        )->json('times'))->pluck('time')->all();
        $this->assertNotContains('14:00', $times);

        // Right Now intentionally still allows a checked-in therapist to
        // continue past the scheduled shift end.
        $walkIn = $this->actingAs($this->receptionist)->getJson(route('api.next-slots', [
            'date' => $today,
            'duration' => 60,
            'buffer_minutes' => 0,
            'services' => [$this->service60->id],
        ]));
        $walkIn->assertOk();
        $this->assertNotEmpty($walkIn->json('slots'));
    }

    // ---------------------------------------------------------- Finding 5

    public function test_confirmation_uses_reconstructed_treatment_end_when_stored_end_is_too_short(): void
    {
        $customer = $this->customer();
        $pending = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $this->roomA->id,
            'appointment_date' => $this->date,
            'start_time' => '13:00:00',
            'end_time' => '13:30:00', // deliberately stale/short
            'status' => 'pending',
            'total_price' => 1000,
        ]);
        $pending->services()->attach($this->service60->id, [
            'price_at_booking' => 1000,
            'service_duration' => 60,
        ]);

        // Conflicting appointment for the SAME therapist starts exactly at the
        // reconstructed 14:00 end; the 5-minute buffer must block confirmation.
        $this->appointment($this->therapistA, $this->roomB, '14:00', '15:00', $this->service60);

        $response = $this->actingAs($this->receptionist)->post(
            route('receptionist.confirm', $pending->id),
            [
                'staff_id' => $this->therapistA->id,
                'room_id' => $this->roomA->id,
                'payment_method' => 'cash',
                'payment_type' => 'cash_on_site',
                'amount' => 0,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $pending->refresh();
        $this->assertEquals('pending', $pending->status);
        $this->assertNull($pending->user_id);
        $this->assertCount(0, $pending->assignedStaff);
    }

    public function test_reassignment_uses_reconstructed_treatment_end_when_stored_end_is_too_short(): void
    {
        $appointment = $this->appointment($this->therapistA, $this->roomA, '13:00', '13:30', $this->service60);
        $this->appointment($this->therapistB, $this->roomB, '14:00', '15:00', $this->service60);

        $response = $this->actingAs($this->receptionist)->post(
            route('receptionist.reassign', $appointment->id),
            ['staff_id' => $this->therapistB->id]
        );

        $response->assertSessionHas('error');
        $this->assertEquals($this->therapistA->id, $appointment->fresh()->user_id);
    }

    // ---------------------------------------------------------- Finding 6

    public function test_saved_package_choice_is_restored_and_can_be_changed_without_duplicate_services(): void
    {
        $package = Service::create([
            'name' => 'Audit Choice Package', 'category_id' => $this->service60->category_id,
            'room_category_id' => $this->roomA->roomCategories()->first()->id,
            'duration_minutes' => 60, 'price' => 1800, 'requires_room' => true,
            'is_active' => true, 'is_package' => true,
        ]);
        $group = PackageChoiceGroup::create([
            'service_id' => $package->id, 'label' => 'Pick one', 'is_required' => true,
        ]);
        $optionA = Service::create([
            'name' => 'Choice A', 'category_id' => $package->category_id,
            'duration_minutes' => 30, 'price' => 300, 'is_active' => true,
        ]);
        $optionB = Service::create([
            'name' => 'Choice B', 'category_id' => $package->category_id,
            'duration_minutes' => 30, 'price' => 400, 'is_active' => true,
        ]);
        PackageChoiceOption::create(['choice_group_id' => $group->id, 'service_id' => $optionA->id]);
        PackageChoiceOption::create(['choice_group_id' => $group->id, 'service_id' => $optionB->id]);

        $appointment = $this->appointment($this->therapistA, $this->roomA, '13:00', '14:00', $package);
        AppointmentPackageSelection::create([
            'appointment_id' => $appointment->id,
            'choice_group_id' => $group->id,
            'selected_service_id' => $optionA->id,
            'group_label_snapshot' => 'Pick one',
            'selected_service_name_snapshot' => 'Choice A',
        ]);
        Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => 1800, 'type' => 'full', 'payment_method' => 'cash', 'paid_at' => now(),
        ]);

        $page = $this->actingAs($this->receptionist)->get(route('receptionist.active'));
        $page->assertOk();
        $page->assertSee('\u0022selected_service_id\u0022:' . $optionA->id, false);

        // Reopening Add Service with a different alternative updates only the
        // saved selection; it must not attach a second service row.
        $response = $this->actingAs($this->receptionist)->post(
            route('receptionist.add-extra', $appointment->id),
            [
                'service_id' => $package->id,
                'package_choices' => [$group->id => $optionB->id],
            ]
        );

        $response->assertSessionHas('success');
        $appointment->refresh();
        $this->assertEquals($optionB->id,
            AppointmentPackageSelection::where('appointment_id', $appointment->id)->firstOrFail()->selected_service_id);
        $this->assertCount(1, $appointment->services);
        $this->assertEquals('14:00:00', $appointment->end_time);
        $this->assertEquals(1800, (float) $appointment->total_price);
        $this->assertCount(1, $appointment->payments);
        $this->assertEquals(1800, (float) $appointment->payments()->sum('amount'));
    }
}
