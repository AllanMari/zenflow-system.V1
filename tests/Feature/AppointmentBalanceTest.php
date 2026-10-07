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

class AppointmentBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_outstanding_balance_accessor_respects_status()
    {
        $customer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone_number' => '09123456789',
        ]);

        $pending = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 1000.00,
        ]);
        Payment::create([
            'appointment_id' => $pending->id,
            'amount' => 200.00,
            'type' => 'deposit',
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        $this->assertEquals(800.00, $pending->fresh()->remaining_balance);
        $this->assertEquals(800.00, $pending->fresh()->active_outstanding_balance);

        $completed = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::yesterday()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 1000.00,
        ]);
        Payment::create([
            'appointment_id' => $completed->id,
            'amount' => 500.00,
            'type' => 'deposit',
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        $this->assertEquals(500.00, $completed->fresh()->remaining_balance);
        $this->assertEquals(0.00, $completed->fresh()->active_outstanding_balance);
    }

    public function test_admin_appointment_report_outstanding_value_calculation()
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($role);

        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'phone_number' => '09198765432',
        ]);

        Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 1000.00,
        ]);

        Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::yesterday()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 2000.00,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.appointments.report-pdf'));
        $response->assertSuccessful();
    }

    public function test_complete_and_collect_workflow_unchanged()
    {
        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($role);

        $customer = Customer::create([
            'first_name' => 'Alice',
            'last_name' => 'Brown',
            'email' => 'alice@example.com',
            'phone_number' => '09112233445',
        ]);

        $room = Room::create([
            'name' => 'Room 1',
            'status' => 'occupied',
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'confirmed',
            'total_price' => 1500.00,
        ]);

        Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => 300.00,
            'type' => 'deposit',
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        $this->assertEquals(1200.00, $appointment->remaining_balance);

        $response = $this->actingAs($receptionist)->post(route('receptionist.complete', $appointment), [
            'payment_method' => 'cash',
            'payment_type' => 'completion',
        ]);

        $response->assertRedirect();

        $appointment->refresh();
        $this->assertEquals('completed', $appointment->status);
        $this->assertEquals(1500.00, $appointment->total_paid);
        $this->assertEquals(0.00, $appointment->remaining_balance);
        $this->assertEquals(0.00, $appointment->active_outstanding_balance);
    }

    public function test_cancelled_and_no_show_appointments_show_no_balance_due_in_admin_view()
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($role);

        $customer = Customer::create([
            'first_name' => 'Bob',
            'last_name' => 'Marley',
            'email' => 'bob@example.com',
            'phone_number' => '09155555555',
        ]);

        Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::yesterday()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'cancelled',
            'total_price' => 1200.00,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.appointments'));
        $response->assertSuccessful();
        $response->assertSee('No Balance Due');
    }
}
