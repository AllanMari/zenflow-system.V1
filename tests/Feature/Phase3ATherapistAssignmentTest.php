<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\ScheduleException;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\TherapistAssignmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class Phase3ATherapistAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected Role $staffRole;
    protected ServiceCategory $serviceCat;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffRole = Role::firstOrCreate(['name' => 'staff']);
        $this->serviceCat = ServiceCategory::create(['name' => 'Massage Therapies', 'is_active' => true]);
        $this->customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'phone_number' => '09123456789',
            'customer_type' => 'regular',
        ]);
    }

    protected function createTherapist(string $name = 'Therapist', array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'first_name' => $name,
            'last_name' => 'Staff',
            'is_active' => true,
        ], $overrides));

        $user->roles()->attach($this->staffRole);

        // Standard schedule 09:00 - 18:00 all days
        for ($i = 0; $i <= 6; $i++) {
            WorkSchedule::create([
                'user_id' => $user->id,
                'day_of_week' => $i,
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'is_day_off' => false,
            ]);
        }

        return $user;
    }

    protected function createAppointment(int $therapistsRequired = 1, array $appointmentOverrides = []): Appointment
    {
        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'name' => 'Relaxing Treatment',
            'duration_minutes' => 60,
            'price' => 1000,
            'therapists_required' => $therapistsRequired,
            'participants_required' => 1,
            'requires_room' => false,
            'is_active' => true,
        ]);

        $date = Carbon::tomorrow()->toDateString();

        $appointment = Appointment::create(array_merge([
            'customer_id' => $this->customer->id,
            'appointment_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 1000,
        ], $appointmentOverrides));

        $appointment->services()->attach($service->id, ['price_at_booking' => 1000]);

        return $appointment;
    }

    public function test_assigns_single_primary_therapist_correctly(): void
    {
        $appointment = $this->createAppointment(1);
        $primary = $this->createTherapist('Primary');

        TherapistAssignmentService::assignStaff($appointment, $primary->id);

        $appointment->refresh();
        $this->assertEquals($primary->id, $appointment->user_id);
        $this->assertCount(1, $appointment->assignedStaff);
        $this->assertEquals('primary', $appointment->assignedStaff->first()->pivot->role);
        $this->assertCount(1, $appointment->primaryTherapist);
        $this->assertCount(0, $appointment->assistingTherapists);
    }

    public function test_assigns_primary_and_assisting_therapists_when_required(): void
    {
        $appointment = $this->createAppointment(2);
        $primary = $this->createTherapist('Lead');
        $assisting = $this->createTherapist('Assistant');

        TherapistAssignmentService::assignStaff($appointment, $primary->id, [$assisting->id]);

        $appointment->refresh();
        $this->assertEquals($primary->id, $appointment->user_id);
        $this->assertCount(2, $appointment->assignedStaff);
        $this->assertEquals($primary->id, $appointment->primaryTherapist->first()->id);
        $this->assertEquals($assisting->id, $appointment->assistingTherapists->first()->id);
        $this->assertEquals('assisting', $appointment->assistingTherapists->first()->pivot->role);
    }

    public function test_rejects_insufficient_therapist_assignment(): void
    {
        $appointment = $this->createAppointment(2);
        $primary = $this->createTherapist('Lead');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Appointment requires exactly 2 therapist(s), but 1 were provided.');

        TherapistAssignmentService::assignStaff($appointment, $primary->id);
    }

    public function test_rejects_surplus_therapist_assignment(): void
    {
        $appointment = $this->createAppointment(1);
        $primary = $this->createTherapist('Lead');
        $assistant = $this->createTherapist('Assistant');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Appointment requires exactly 1 therapist(s), but 2 were provided.');

        TherapistAssignmentService::assignStaff($appointment, $primary->id, [$assistant->id]);
    }

    public function test_rejects_duplicate_therapist_in_assisting(): void
    {
        $appointment = $this->createAppointment(3);
        $primary = $this->createTherapist('Lead');
        $assistant = $this->createTherapist('Assistant');

        $this->expectException(InvalidArgumentException::class);
        // Deduplication leaves 2 total, which doesn't match 3
        TherapistAssignmentService::assignStaff($appointment, $primary->id, [$assistant->id, $assistant->id]);
    }

    public function test_rejects_primary_therapist_also_assigned_as_assisting(): void
    {
        $appointment = $this->createAppointment(2);
        $primary = $this->createTherapist('Lead');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A primary therapist cannot also be assigned as an assisting therapist');

        TherapistAssignmentService::assignStaff($appointment, $primary->id, [$primary->id]);
    }

    public function test_rejects_inactive_therapist(): void
    {
        $appointment = $this->createAppointment(1);
        $inactiveStaff = $this->createTherapist('InactiveStaff', ['is_active' => false]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is inactive');

        TherapistAssignmentService::assignStaff($appointment, $inactiveStaff->id);
    }

    public function test_rejects_ineligible_user_without_staff_role(): void
    {
        $appointment = $this->createAppointment(1);
        $userWithoutStaffRole = User::factory()->create(['is_active' => true]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not hold an active staff/therapist role');

        TherapistAssignmentService::assignStaff($appointment, $userWithoutStaffRole->id);
    }

    public function test_rejects_therapist_outside_working_shift(): void
    {
        $appointment = $this->createAppointment(1, [
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
        ]);
        $staff = $this->createTherapist('EarlyBird'); // Shift is 09:00 - 18:00

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('falls outside');

        TherapistAssignmentService::assignStaff($appointment, $staff->id);
    }

    public function test_rejects_therapist_on_scheduled_day_off(): void
    {
        $appointment = $this->createAppointment(1);
        $dayOfWeek = Carbon::parse($appointment->appointment_date)->dayOfWeek;

        $staff = $this->createTherapist('OffStaff');
        WorkSchedule::where('user_id', $staff->id)
            ->where('day_of_week', $dayOfWeek)
            ->update(['is_day_off' => true]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('scheduled day off');

        TherapistAssignmentService::assignStaff($appointment, $staff->id);
    }

    public function test_rejects_therapist_on_leave_or_holiday_exception(): void
    {
        $appointment = $this->createAppointment(1);
        $staff = $this->createTherapist('LeaveStaff');

        ScheduleException::create([
            'user_id' => $staff->id,
            'exception_date' => $appointment->appointment_date,
            'type' => 'sick_leave',
            'reason' => 'Doctor appointment',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sick leave');

        TherapistAssignmentService::assignStaff($appointment, $staff->id);
    }

    public function test_rejects_therapist_with_conflicting_confirmed_appointment(): void
    {
        $primary = $this->createTherapist('BusyStaff');
        $date = Carbon::tomorrow()->toDateString();

        // Existing overlapping appointment
        $existing = Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $primary->id,
            'appointment_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 500,
        ]);
        $existing->syncStaff($primary->id);

        $newAppointment = $this->createAppointment(1, [
            'appointment_date' => $date,
            'start_time' => '10:30:00',
            'end_time' => '11:30:00',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('conflicting appointment');

        TherapistAssignmentService::assignStaff($newAppointment, $primary->id);
    }

    public function test_rejects_therapist_conflicting_as_assisting_on_another_appointment(): void
    {
        $primary = $this->createTherapist('OtherLead');
        $assistant = $this->createTherapist('SharedAssistant');
        $date = Carbon::tomorrow()->toDateString();

        // Assistant is assigned to another appointment as assisting
        $existing = Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $primary->id,
            'appointment_date' => $date,
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'status' => 'confirmed',
            'total_price' => 1200,
        ]);
        $existing->syncStaff($primary->id, [$assistant->id]);

        $newAppointment = $this->createAppointment(1, [
            'appointment_date' => $date,
            'start_time' => '14:30:00',
            'end_time' => '15:00:00',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('conflicting appointment');

        // Trying to assign the assistant as primary on the new overlapping appointment
        TherapistAssignmentService::assignStaff($newAppointment, $assistant->id);
    }

    public function test_successful_reassignment_updates_primary_and_assisting(): void
    {
        $appointment = $this->createAppointment(2);
        $lead1 = $this->createTherapist('Lead1');
        $asst1 = $this->createTherapist('Asst1');

        // Initial assignment
        TherapistAssignmentService::assignStaff($appointment, $lead1->id, [$asst1->id]);

        $appointment->refresh();
        $this->assertEquals($lead1->id, $appointment->user_id);
        $this->assertEquals($asst1->id, $appointment->assistingTherapists->first()->id);

        // Reassignment to new team
        $lead2 = $this->createTherapist('Lead2');
        $asst2 = $this->createTherapist('Asst2');

        TherapistAssignmentService::assignStaff($appointment, $lead2->id, [$asst2->id]);

        $appointment->refresh();
        $this->assertEquals($lead2->id, $appointment->user_id);
        $this->assertCount(2, $appointment->assignedStaff);
        $this->assertEquals($lead2->id, $appointment->primaryTherapist->first()->id);
        $this->assertEquals($asst2->id, $appointment->assistingTherapists->first()->id);
        $this->assertFalse($appointment->assignedStaff->contains('id', $lead1->id));
        $this->assertFalse($appointment->assignedStaff->contains('id', $asst1->id));
    }

    public function test_transaction_rollback_when_assignment_fails(): void
    {
        $appointment = $this->createAppointment(2);
        $lead1 = $this->createTherapist('InitialLead');
        $asst1 = $this->createTherapist('InitialAsst');

        // Initial valid state
        TherapistAssignmentService::assignStaff($appointment, $lead1->id, [$asst1->id]);

        $newLead = $this->createTherapist('NewLead');
        $unavailableAsst = $this->createTherapist('UnavailableAsst', ['is_active' => false]);

        try {
            TherapistAssignmentService::assignStaff($appointment, $newLead->id, [$unavailableAsst->id]);
            $this->fail('Expected exception was not thrown');
        } catch (InvalidArgumentException $e) {
            // Verify state rolled back to initial assignments
            $appointment->refresh();
            $this->assertEquals($lead1->id, $appointment->user_id);
            $this->assertEquals($asst1->id, $appointment->assistingTherapists->first()->id);
            $this->assertFalse($appointment->assignedStaff->contains('id', $newLead->id));
        }
    }

    public function test_room_locking_and_availability_verification(): void
    {
        $appointment = $this->createAppointment(1);
        $primary = $this->createTherapist('RoomLead');

        $room = Room::create([
            'name' => 'Suite 101',
            'status' => 'available',
            'is_active' => true,
        ]);

        // Valid room assignment
        TherapistAssignmentService::assignStaff($appointment, $primary->id, [], $room->id);
        $this->assertEquals($primary->id, $appointment->fresh()->user_id);

        // Occupied/conflicting room rejection
        $appointment2 = $this->createAppointment(1, [
            'appointment_date' => $appointment->appointment_date,
            'start_time' => $appointment->start_time,
            'end_time' => $appointment->end_time,
            'room_id' => $room->id,
        ]);
        $primary2 = $this->createTherapist('RoomLead2');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Room [Suite 101] is not available');

        // Appointment 1 already occupies the room at this time, so assignment 2 must fail
        TherapistAssignmentService::assignStaff($appointment2, $primary2->id, [], $room->id);
    }
}
