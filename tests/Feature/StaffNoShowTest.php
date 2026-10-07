<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffNoShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_mark_appointment_as_no_show_with_forfeit()
    {
        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($role);

        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
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
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1500.00,
        ]);

        $response = $this->actingAs($receptionist)->post(route('receptionist.no-show', $appointment), [
            'action' => 'forfeit',
            'no_show_reason' => 'Customer Did Not Arrive',
        ]);

        $response->assertRedirect();

        $appointment->refresh();
        $this->assertEquals('no_show', $appointment->status);
        $this->assertNull($appointment->cancellation_reason);
        $this->assertEquals('Customer Did Not Arrive', $appointment->no_show_reason);

        $room->refresh();
        $this->assertEquals('available', $room->status);
    }

    public function test_receptionist_can_select_predefined_reason_customer_did_not_arrive()
    {
        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($role);

        $customer = Customer::create(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'phone_number' => '1234567890']);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);

        $response = $this->actingAs($receptionist)->post(route('receptionist.no-show', $appointment), [
            'action' => 'forfeit',
            'no_show_reason' => 'Customer Did Not Arrive',
        ]);

        $response->assertRedirect();
        $appointment->refresh();
        $this->assertEquals('no_show', $appointment->status);
        $this->assertEquals('Customer Did Not Arrive', $appointment->no_show_reason);
    }

    public function test_receptionist_can_select_predefined_reason_customer_could_not_be_contacted()
    {
        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($role);

        $customer = Customer::create(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'phone_number' => '1234567890']);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);

        $response = $this->actingAs($receptionist)->post(route('receptionist.no-show', $appointment), [
            'action' => 'forfeit',
            'no_show_reason' => 'Customer could not be contacted',
        ]);

        $response->assertRedirect();
        $appointment->refresh();
        $this->assertEquals('no_show', $appointment->status);
        $this->assertEquals('Customer could not be contacted', $appointment->no_show_reason);
    }

    public function test_receptionist_can_select_predefined_reason_customer_arrived_too_late()
    {
        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($role);

        $customer = Customer::create(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'phone_number' => '1234567890']);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);

        $response = $this->actingAs($receptionist)->post(route('receptionist.no-show', $appointment), [
            'action' => 'forfeit',
            'no_show_reason' => 'Customer arrived too late to be served',
        ]);

        $response->assertRedirect();
        $appointment->refresh();
        $this->assertEquals('no_show', $appointment->status);
        $this->assertEquals('Customer arrived too late to be served', $appointment->no_show_reason);
    }

    public function test_receptionist_can_submit_custom_reason()
    {
        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($role);

        $customer = Customer::create(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'phone_number' => '1234567890']);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);

        $response = $this->actingAs($receptionist)->post(route('receptionist.no-show', $appointment), [
            'action' => 'forfeit',
            'no_show_reason' => 'Customer called to reschedule but failed to show up',
        ]);

        $response->assertRedirect();
        $appointment->refresh();
        $this->assertEquals('no_show', $appointment->status);
        $this->assertEquals('Customer called to reschedule but failed to show up', $appointment->no_show_reason);
    }

    public function test_empty_or_whitespace_no_show_reason_fails_validation()
    {
        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($role);

        $customer = Customer::create(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'phone_number' => '1234567890']);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);

        $response = $this->actingAs($receptionist)->post(route('receptionist.no-show', $appointment), [
            'action' => 'forfeit',
            'no_show_reason' => '   ',
        ]);

        $response->assertSessionHasErrors('no_show_reason');
    }

    public function test_admin_appointment_history_displays_no_show_reason()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $customer = Customer::create(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'phone_number' => '1234567890']);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'no_show',
            'no_show_reason' => 'Customer Did Not Arrive',
            'total_price' => 1000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.appointments'));
        $response->assertStatus(200);
        $response->assertSee('Customer Did Not Arrive');
    }

}
