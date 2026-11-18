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

class Phase4TreatmentNotesPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function setupBookingEnvironment($date)
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

    /**
     * Test 1: Treatment notes are saved to appointments.treatment_notes
     * and operational notes are saved to appointments.notes separately.
     */
    public function test_treatment_notes_saved_to_correct_appointment_field_and_operational_notes_separate(): void
    {
        $tomorrow = Carbon::tomorrow();
        $env = $this->setupBookingEnvironment($tomorrow);

        $response = $this->post(route('booking.store'), [
            'services' => [$env['service']->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'guest_first_name' => 'Alice Walker',
            'guest_phone' => '09181112233',
            'treatment_notes' => 'Client has lower back sensitivity, avoid deep pressure on lumbar.',
            'notes' => 'Customer requested quiet room near entrance.',
            'source' => 'public',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $appointment = Appointment::where('guest_phone', '09181112233')->latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('Client has lower back sensitivity, avoid deep pressure on lumbar.', $appointment->treatment_notes);
        $this->assertEquals('Customer requested quiet room near entrance.', $appointment->notes);
    }

    /**
     * Test 2: Medical notes submitted via the booking form are routed to treatment_notes,
     * not operational notes, correcting the previous misrouting bug.
     */
    public function test_medical_notes_from_booking_wizard_routes_to_treatment_notes_not_operational_notes(): void
    {
        $tomorrow = Carbon::tomorrow();
        $env = $this->setupBookingEnvironment($tomorrow);

        $response = $this->post(route('booking.store'), [
            'services' => [$env['service']->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '11:00',
            'guest_first_name' => 'Bob Marley',
            'guest_phone' => '09182223344',
            'medical_notes' => 'Allergic to eucalyptus and peppermint oils.',
            'source' => 'public',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $appointment = Appointment::where('guest_phone', '09182223344')->latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('Allergic to eucalyptus and peppermint oils.', $appointment->treatment_notes);
        $this->assertNull($appointment->notes);
    }

    /**
     * Test 3: Customer profile medical notes remain unchanged on subsequent bookings.
     * Appointment-specific notes must NEVER overwrite permanent customer medical notes.
     */
    public function test_customer_medical_notes_remain_unchanged_on_subsequent_booking(): void
    {
        $user = User::factory()->create();
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $user->roles()->attach($customerRole);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Claire',
            'last_name' => 'Danes',
            'phone_number' => '09183334455',
            'medical_notes' => 'Permanent Heart Condition: Pacemaker fitted 2022',
        ]);

        $tomorrow = Carbon::tomorrow();
        $env = $this->setupBookingEnvironment($tomorrow);

        $response = $this->actingAs($user)->post(route('booking.store'), [
            'services' => [$env['service']->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '14:00',
            'guest_first_name' => 'Claire',
            'guest_phone' => '09183334455',
            'treatment_notes' => 'Sore right shoulder after gym session yesterday',
            'source' => 'public',
        ]);

        $response->assertSessionHasNoErrors();

        $customer->refresh();
        $this->assertEquals('Permanent Heart Condition: Pacemaker fitted 2022', $customer->medical_notes);

        $appointment = Appointment::where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('Sore right shoulder after gym session yesterday', $appointment->treatment_notes);
    }

    /**
     * Test 4: Guest booking saves treatment notes to appointment,
     * and newly created guest Customer record does not mix appointment notes into profile.
     */
    public function test_guest_booking_saves_treatment_notes_and_keeps_guest_customer_medical_notes_clean(): void
    {
        $tomorrow = Carbon::tomorrow();
        $env = $this->setupBookingEnvironment($tomorrow);

        $response = $this->post(route('booking.store'), [
            'services' => [$env['service']->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '15:00',
            'guest_first_name' => 'David Guest',
            'guest_phone' => '09184445566',
            'treatment_notes' => 'Temporary neck stiffness',
            'source' => 'public',
        ]);

        $response->assertSessionHasNoErrors();

        $customer = Customer::where('phone_number', '09184445566')->first();
        $this->assertNotNull($customer);
        $this->assertNull($customer->medical_notes);

        $appointment = Appointment::where('guest_phone', '09184445566')->latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('Temporary neck stiffness', $appointment->treatment_notes);
    }

    /**
     * Test 5: Authorized primary and assisting therapists can access treatment notes.
     */
    public function test_primary_and_assisting_therapists_can_access_treatment_notes(): void
    {
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        $primaryStaff = User::factory()->create(['is_active' => true]);
        $primaryStaff->roles()->attach($staffRole);

        $assistingStaff = User::factory()->create(['is_active' => true]);
        $assistingStaff->roles()->attach($staffRole);

        $customer = Customer::create([
            'first_name' => 'Elena',
            'last_name' => 'Rostova',
            'phone_number' => '09185556677',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $primaryStaff->id,
            'appointment_date' => Carbon::today(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1200.00,
            'treatment_notes' => 'Sensitive spine, focus on foot reflexology.',
        ]);

        $appointment->syncStaff($primaryStaff->id, [$assistingStaff->id]);

        // Model helper check
        $this->assertTrue($appointment->canViewTreatmentNotes($primaryStaff));
        $this->assertTrue($appointment->canViewTreatmentNotes($assistingStaff));

        // Primary staff API access
        $this->actingAs($primaryStaff)
            ->getJson(route('staff.appointments.treatment-notes', $appointment))
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'treatment_notes' => 'Sensitive spine, focus on foot reflexology.',
            ]);

        // Assisting staff API access
        $this->actingAs($assistingStaff)
            ->getJson(route('staff.appointments.treatment-notes', $appointment))
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'treatment_notes' => 'Sensitive spine, focus on foot reflexology.',
            ]);
    }

    /**
     * Test 6: Unrelated staff members CANNOT view treatment notes.
     */
    public function test_unrelated_staff_cannot_access_treatment_notes(): void
    {
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        $primaryStaff = User::factory()->create(['is_active' => true]);
        $primaryStaff->roles()->attach($staffRole);

        $unrelatedStaff = User::factory()->create(['is_active' => true]);
        $unrelatedStaff->roles()->attach($staffRole);

        $customer = Customer::create([
            'first_name' => 'Frank',
            'last_name' => 'Sinatra',
            'phone_number' => '09186667788',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $primaryStaff->id,
            'appointment_date' => Carbon::today(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000.00,
            'treatment_notes' => 'Confidential health information',
        ]);

        $appointment->syncStaff($primaryStaff->id, []);

        $this->assertFalse($appointment->canViewTreatmentNotes($unrelatedStaff));

        $this->actingAs($unrelatedStaff)
            ->getJson(route('staff.appointments.treatment-notes', $appointment))
            ->assertStatus(403);
    }

    /**
     * Test 7: Customers and public visitors CANNOT access treatment notes endpoint.
     */
    public function test_customers_and_guests_cannot_access_treatment_notes(): void
    {
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $customerUser = User::factory()->create();
        $customerUser->roles()->attach($customerRole);

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $primaryStaff = User::factory()->create(['is_active' => true]);
        $primaryStaff->roles()->attach($staffRole);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Grace',
            'last_name' => 'Kelly',
            'phone_number' => '09187778899',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $primaryStaff->id,
            'appointment_date' => Carbon::today(),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'confirmed',
            'total_price' => 1500.00,
            'treatment_notes' => 'Client has mild scoliosis, light pressure only.',
        ]);

        $this->assertFalse($appointment->canViewTreatmentNotes($customerUser));

        // Customer user should be denied access (403 forbidden)
        $this->actingAs($customerUser)
            ->getJson(route('staff.appointments.treatment-notes', $appointment))
            ->assertStatus(403);

        // Guest visitor should be redirected / unauthenticated
        auth()->logout();
        $this->get(route('staff.appointments.treatment-notes', $appointment))
            ->assertRedirect();
    }

    /**
     * Test 8: Receptionist can record and update treatment notes during confirmation.
     */
    public function test_receptionist_can_record_treatment_notes_on_confirmation(): void
    {
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create(['is_active' => true]);
        $receptionist->roles()->attach($receptionistRole);

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staff = User::factory()->create(['is_active' => true]);
        $staff->roles()->attach($staffRole);

        $tomorrow = Carbon::tomorrow();
        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $tomorrow->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_day_off' => false,
        ]);

        for ($i = 0; $i < 7; $i++) {
            BusinessHour::create([
                'day_of_week' => $i,
                'open_time' => '08:00:00',
                'close_time' => '20:00:00',
                'is_closed' => false,
            ]);
        }

        $cat = ServiceCategory::create(['name' => 'Spa', 'is_active' => true]);
        $roomCat = RoomCategory::firstOrCreate(['name' => 'Spa Room', 'code' => 'spa_room']);
        $service = Service::create([
            'name' => 'Facial',
            'category_id' => $cat->id,
            'room_category_id' => $roomCat->id,
            'duration_minutes' => 60,
            'price' => 1200.00,
            'is_active' => true,
            'therapists_required' => 1,
            'participants_required' => 1,
        ]);
        $room = Room::create(['name' => 'Facial Room', 'status' => 'available', 'is_active' => true]);
        $room->roomCategories()->attach($roomCat->id);

        $customer = Customer::create([
            'first_name' => 'Hannah',
            'last_name' => 'Montana',
            'phone_number' => '09188889900',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 1200.00,
        ]);
        $appointment->services()->attach($service->id, [
            'price_at_booking' => 1200.00,
            'service_name' => 'Facial',
            'service_duration' => 60,
        ]);

        $response = $this->actingAs($receptionist)->post(route('receptionist.confirm', $appointment), [
            'staff_id' => $staff->id,
            'room_id' => $room->id,
            'payment_method' => 'cash',
            'payment_type' => 'full',
            'amount' => 1200.00,
            'notes' => 'Customer requested quiet room',
            'treatment_notes' => 'Extremely sensitive skin, use hypoallergenic cream only.',
        ]);

        $response->assertRedirect();

        $appointment->refresh();
        $this->assertEquals('confirmed', $appointment->status);
        $this->assertEquals('Extremely sensitive skin, use hypoallergenic cream only.', $appointment->treatment_notes);
        $this->assertStringContainsString('Customer requested quiet room', $appointment->notes);
    }

    /**
     * Test 9: Receptionist can update treatment notes via dedicated endpoint.
     */
    public function test_receptionist_can_update_treatment_notes_via_dedicated_endpoint(): void
    {
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create(['is_active' => true]);
        $receptionist->roles()->attach($receptionistRole);

        $customer = Customer::create([
            'first_name' => 'Ian',
            'last_name' => 'McKellen',
            'phone_number' => '09189990011',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 800.00,
            'treatment_notes' => 'Initial note',
        ]);

        $response = $this->actingAs($receptionist)->postJson(route('receptionist.update-treatment-notes', $appointment), [
            'treatment_notes' => 'Updated: avoid right shoulder after strain',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $appointment->refresh();
        $this->assertEquals('Updated: avoid right shoulder after strain', $appointment->treatment_notes);
    }

    /**
     * Test 10: Treatment notes are excluded from public confirmations, invoices, and reports.
     */
    public function test_treatment_notes_excluded_from_public_confirmations_and_invoices(): void
    {
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create(['is_active' => true]);
        $receptionist->roles()->attach($receptionistRole);

        $secretNote = 'CONFIDENTIAL_HEALTH_NOTE_ALLERGIC_TO_LAVENDER_XYZ';

        $customer = Customer::create([
            'first_name' => 'Julia',
            'last_name' => 'Roberts',
            'phone_number' => '09180001122',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 1500.00,
            'treatment_notes' => $secretNote,
        ]);

        // Confirmation page view
        session(["authorized_booking_{$appointment->id}" => true]);
        $this->get(route('booking.confirmation', $appointment))
            ->assertStatus(200)
            ->assertDontSee($secretNote);

        // Receptionist invoice view
        $this->actingAs($receptionist)
            ->get(route('receptionist.invoice', $appointment))
            ->assertStatus(200)
            ->assertDontSee($secretNote);
    }

    /**
     * Test 11: Treatment notes are hidden from model serialization to prevent API leaks.
     */
    public function test_treatment_notes_hidden_from_appointment_serialization(): void
    {
        $customer = Customer::create([
            'first_name' => 'Kevin',
            'last_name' => 'Bacon',
            'phone_number' => '09181113355',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000.00,
            'treatment_notes' => 'Confidential treatment details',
        ]);

        $array = $appointment->toArray();
        $this->assertArrayNotHasKey('treatment_notes', $array);

        $json = json_encode($appointment);
        $this->assertStringNotContainsString('Confidential treatment details', $json);
        $this->assertStringNotContainsString('treatment_notes', $json);

        // Direct property access remains available when needed
        $this->assertEquals('Confidential treatment details', $appointment->treatment_notes);
    }

    /**
     * Test 12: Existing cancellation workflow remains functional without affecting treatment notes.
     */
    public function test_cancellation_workflow_preserves_treatment_notes_and_separates_operational_reason(): void
    {
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($customerRole);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Laura',
            'last_name' => 'Croft',
            'phone_number' => '09182224466',
        ]);

        $tomorrow = Carbon::tomorrow();
        $env = $this->setupBookingEnvironment($tomorrow);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000.00,
            'treatment_notes' => 'Patient prefers firm pressure on calves.',
        ]);

        // Customer cancels
        $response = $this->actingAs($user)->post(route('customer.appointments.cancel', $appointment), [
            'reason' => 'Schedule conflict with work',
        ]);

        $response->assertRedirect();

        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertEquals('customer_cancelled', $appointment->cancellation_reason);
        // Treatment notes are preserved and not erased or polluted
        $this->assertEquals('Patient prefers firm pressure on calves.', $appointment->treatment_notes);
    }
}
