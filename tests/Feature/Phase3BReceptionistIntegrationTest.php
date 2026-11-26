<?php

namespace Tests\Feature;

use App\Models\Appointment;
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
use Tests\TestCase;

class Phase3BReceptionistIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected Role $receptionistRole;
    protected Role $staffRole;
    protected Role $customerRole;
    protected ServiceCategory $serviceCat;
    protected RoomCategory $roomCat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $this->staffRole = Role::firstOrCreate(['name' => 'staff']);
        $this->customerRole = Role::firstOrCreate(['name' => 'customer']);

        $this->serviceCat = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);
        $this->roomCat = RoomCategory::create([
            'name' => 'Standard Suite',
            'code' => 'STD-SUITE',
            'is_active' => true,
        ]);
    }

    protected function createReceptionist(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($this->receptionistRole);
        return $user;
    }

    protected function createTherapist(string $name = 'Therapist'): User
    {
        $user = User::factory()->create([
            'first_name' => $name,
            'last_name' => 'Staff',
            'is_active' => true,
        ]);
        $user->roles()->attach($this->staffRole);

        for ($i = 0; $i <= 6; $i++) {
            WorkSchedule::create([
                'user_id' => $user->id,
                'day_of_week' => $i,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'is_day_off' => false,
            ]);
        }

        return $user;
    }

    protected function createCustomer(): Customer
    {
        $user = User::factory()->create();
        $user->roles()->attach($this->customerRole);

        return Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone_number' => '09123456789',
            'customer_type' => 'regular',
        ]);
    }

    protected function createRoom(string $name = 'Room 101'): Room
    {
        $room = Room::create([
            'name' => $name,
            'is_active' => true,
        ]);
        $room->roomCategories()->attach($this->roomCat->id);
        return $room;
    }

    public function test_receptionist_store_booking_with_multiple_therapists()
    {
        $receptionist = $this->createReceptionist();
        $primary = $this->createTherapist('Primary');
        $assisting = $this->createTherapist('Assisting');
        $customer = $this->createCustomer();
        $room = $this->createRoom();

        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => $this->roomCat->id,
            'name' => 'Couples Massage',
            'duration_minutes' => 60,
            'price' => 2000,
            'therapists_required' => 2,
            'participants_required' => 2,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $date = Carbon::now('Asia/Manila')->addDays(2)->toDateString();

        $response = $this->actingAs($receptionist)->post(route('booking.store'), [
            'source' => 'receptionist',
            'guest_first_name' => 'Walkin',
            'guest_phone' => '09998887777',
            'appointment_date' => $date,
            'start_time' => '10:00:00',
            'services' => [$service->id],
            'room_id' => $room->id,
            'staff_id' => $primary->id,
            'assisting_staff_ids' => [$assisting->id],
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));

        $appointmentId = $response->json('appointment_id');
        $appointment = Appointment::find($appointmentId);

        $this->assertNotNull($appointment);
        $this->assertEquals($primary->id, $appointment->user_id);
        $this->assertEquals(2, $appointment->assignedStaff()->count());
        $this->assertEquals(1, $appointment->assistingTherapists()->count());
        $this->assertEquals($assisting->id, $appointment->assistingTherapists()->first()->id);
    }

    public function test_receptionist_confirm_rejects_insufficient_therapists()
    {
        $receptionist = $this->createReceptionist();
        $primary = $this->createTherapist('Primary');
        $customer = $this->createCustomer();
        $room = $this->createRoom();

        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => $this->roomCat->id,
            'name' => 'Couples Massage',
            'duration_minutes' => 60,
            'price' => 2000,
            'therapists_required' => 2,
            'participants_required' => 2,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $date = Carbon::now('Asia/Manila')->addDays(3)->toDateString();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $date,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => 'pending',
            'total_price' => 2000,
            'client_name' => $customer->full_name,
            'client_phone' => $customer->phone_number,
            'room_id' => $room->id,
            'user_id' => $primary->id,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => 2000]);

        // Attempt confirm with only 1 therapist when 2 are required
        $response = $this->actingAs($receptionist)->post(route('receptionist.confirm', $appointment->id), [
            'staff_id' => $primary->id,
            'assisting_staff_ids' => [], // missing assisting
            'room_id' => $room->id,
            'payment_method' => 'cash',
            'payment_type' => 'cash_on_site',
            'amount' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $appointment->refresh();
        $this->assertEquals('pending', $appointment->status);
    }

    public function test_receptionist_confirm_succeeds_with_exact_therapist_count()
    {
        $receptionist = $this->createReceptionist();
        $primary = $this->createTherapist('Primary');
        $assisting = $this->createTherapist('Assisting');
        $customer = $this->createCustomer();
        $room = $this->createRoom();

        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => $this->roomCat->id,
            'name' => 'Couples Massage',
            'duration_minutes' => 60,
            'price' => 2000,
            'therapists_required' => 2,
            'participants_required' => 2,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $date = Carbon::now('Asia/Manila')->addDays(3)->toDateString();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $date,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => 'pending',
            'total_price' => 2000,
            'client_name' => $customer->full_name,
            'client_phone' => $customer->phone_number,
            'room_id' => $room->id,
            'user_id' => null,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => 2000]);

        $response = $this->actingAs($receptionist)->post(route('receptionist.confirm', $appointment->id), [
            'staff_id' => $primary->id,
            'assisting_staff_ids' => [$assisting->id],
            'room_id' => $room->id,
            'payment_method' => 'cash',
            'payment_type' => 'cash_on_site',
            'amount' => 0,
        ]);

        $response->assertRedirect(route('receptionist.active'));

        $appointment->refresh();
        $this->assertEquals('confirmed', $appointment->status);
        $this->assertEquals($primary->id, $appointment->user_id);
        $this->assertEquals(2, $appointment->assignedStaff()->count());
        $this->assertEquals($assisting->id, $appointment->assistingTherapists()->first()->id);
    }

    public function test_receptionist_reassign_staff_updates_primary_and_assisting()
    {
        $receptionist = $this->createReceptionist();
        $origPrimary = $this->createTherapist('OrigPrimary');
        $origAssisting = $this->createTherapist('OrigAssisting');
        $newPrimary = $this->createTherapist('NewPrimary');
        $newAssisting = $this->createTherapist('NewAssisting');
        $customer = $this->createCustomer();
        $room = $this->createRoom();

        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => $this->roomCat->id,
            'name' => 'Couples Massage',
            'duration_minutes' => 60,
            'price' => 2000,
            'therapists_required' => 2,
            'participants_required' => 2,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $date = Carbon::now('Asia/Manila')->addDays(4)->toDateString();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $date,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'confirmed',
            'total_price' => 2000,
            'client_name' => $customer->full_name,
            'client_phone' => $customer->phone_number,
            'room_id' => $room->id,
            'user_id' => $origPrimary->id,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => 2000]);
        $appointment->syncStaff($origPrimary->id, [$origAssisting->id]);

        $response = $this->actingAs($receptionist)->post(route('receptionist.reassign', $appointment->id), [
            'staff_id' => $newPrimary->id,
            'assisting_staff_ids' => [$newAssisting->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $appointment->refresh();
        $this->assertEquals($newPrimary->id, $appointment->user_id);
        $this->assertEquals(2, $appointment->assignedStaff()->count());
        $this->assertEquals($newAssisting->id, $appointment->assistingTherapists()->first()->id);
    }

    public function test_receptionist_reschedule_validates_all_assigned_staff()
    {
        $receptionist = $this->createReceptionist();
        $primary = $this->createTherapist('Primary');
        $assisting = $this->createTherapist('Assisting');
        $customer = $this->createCustomer();
        $room = $this->createRoom();

        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => $this->roomCat->id,
            'name' => 'Couples Massage',
            'duration_minutes' => 60,
            'price' => 2000,
            'therapists_required' => 2,
            'participants_required' => 2,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $origDate = Carbon::now('Asia/Manila')->addDays(4)->toDateString();
        $newDate = Carbon::now('Asia/Manila')->addDays(5)->toDateString();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $origDate,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'confirmed',
            'total_price' => 2000,
            'client_name' => $customer->full_name,
            'client_phone' => $customer->phone_number,
            'room_id' => $room->id,
            'user_id' => $primary->id,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => 2000]);
        $appointment->syncStaff($primary->id, [$assisting->id]);

        // Place a leave exception for the ASSISTING therapist on newDate
        ScheduleException::create([
            'user_id' => $assisting->id,
            'exception_date' => $newDate,
            'type' => 'sick_leave',
            'reason' => 'Doctor appointment',
        ]);

        // Reschedule to newDate should fail because assisting therapist is unavailable
        $response = $this->actingAs($receptionist)->post(route('receptionist.reschedule', $appointment->id), [
            'appointment_date' => $newDate,
            'start_time' => '14:00',
            'room_id' => $room->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $appointment->refresh();
        $this->assertEquals($origDate, Carbon::parse($appointment->appointment_date)->toDateString());
    }

    public function test_available_rooms_uses_room_categories_pivot()
    {
        $receptionist = $this->createReceptionist();
        $customer = $this->createCustomer();
        $room = $this->createRoom('VIP Suite Room');

        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => $this->roomCat->id,
            'name' => 'Suite Treatment',
            'duration_minutes' => 60,
            'price' => 1500,
            'therapists_required' => 1,
            'participants_required' => 1,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $date = Carbon::now('Asia/Manila')->addDays(3)->toDateString();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $date,
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'status' => 'pending',
            'total_price' => 1500,
            'client_name' => $customer->full_name,
            'client_phone' => $customer->phone_number,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => 2000]);

        $response = $this->actingAs($receptionist)->get(route('receptionist.rooms.available', [
            'appointment' => $appointment->id,
            'date' => $date,
            'time' => '15:00:00',
        ]));

        $response->assertOk();
        $json = $response->json();
        $this->assertTrue($json['requires_room']);
        $this->assertNotEmpty($json['rooms']);
        $this->assertEquals('Standard Suite', $json['rooms'][0]['category']);
    }

    public function test_public_customer_cannot_select_therapists()
    {
        $primary = $this->createTherapist('Primary');
        $assisting = $this->createTherapist('Assisting');
        $room = $this->createRoom();

        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => $this->roomCat->id,
            'name' => 'Massage',
            'duration_minutes' => 60,
            'price' => 1000,
            'therapists_required' => 1,
            'participants_required' => 1,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $date = Carbon::now('Asia/Manila')->addDays(3)->toDateString();

        // Public booking submission attempting to pass staff_id and assisting_staff_ids
        $response = $this->post(route('booking.store'), [
            'source' => 'public',
            'privacy_consented' => '1',
            'guest_first_name' => 'Online Customer',
            'guest_phone' => '09123456789',
            'appointment_date' => $date,
            'start_time' => '11:00:00',
            'services' => [$service->id],
            'room_id' => $room->id,
            'staff_id' => $primary->id,
            'assisting_staff_ids' => [$assisting->id],
        ]);

        $response->assertRedirect();

        $appointment = Appointment::whereDate('appointment_date', $date)->first();

        $this->assertNotNull($appointment);
        // Public booking must ignore customer therapist selection (remains null/pending)
        $this->assertNull($appointment->user_id);
        $this->assertEquals(0, $appointment->assignedStaff()->count());
    }
}
