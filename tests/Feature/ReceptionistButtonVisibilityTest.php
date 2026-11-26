<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceConfirmationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Session-card button visibility matrix for the four consolidated tabs.
 * Backend authorization is unchanged; these assertions only cover UI scoping.
 */
class ReceptionistButtonVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Manila'));

        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $this->receptionist = User::factory()->create(['is_active' => true]);
        $this->receptionist->roles()->attach($role);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_active_today_card_shows_management_contact_and_resolution_actions(): void
    {
        $appointment = $this->makeAppointment('10:00', '11:00');
        $card = $this->card($this->page(), $appointment->id);

        $this->assertStringContainsString('Add Service', $card);
        $this->assertStringContainsString('openReassign(', $card);
        $this->assertStringContainsString('Notes', $card);
        $this->assertStringContainsString('openReschedule(', $card);
        $this->assertStringContainsString('openAttendance(', $card);
        $this->assertStringContainsString('openNoShow(', $card);
        $this->assertStringContainsString('openComplete(', $card);
    }

    public function test_future_confirmed_card_shows_only_pre_appointment_management(): void
    {
        $appointment = $this->makeAppointment('10:00', '11:00', '2026-12-05');
        $card = $this->card($this->page(), $appointment->id);

        $this->assertStringContainsString('Add Service', $card);
        $this->assertStringContainsString('openReassign(', $card);
        $this->assertStringContainsString('Notes', $card);
        $this->assertStringContainsString('openReschedule(', $card);
        $this->assertStringNotContainsString('openAttendance(', $card);
        $this->assertStringNotContainsString('openNoShow(', $card);
        $this->assertStringNotContainsString('openComplete(', $card);
    }

    public function test_past_unresolved_card_shows_contact_and_resolution_actions(): void
    {
        $appointment = $this->makeAppointment('09:00', '09:30');
        $card = $this->card($this->page(), $appointment->id);

        $this->assertStringNotContainsString('Add Service', $card);
        $this->assertStringNotContainsString('openReassign(', $card);
        $this->assertStringContainsString('Notes', $card);
        $this->assertStringNotContainsString('openReschedule(', $card);
        $this->assertStringContainsString('openAttendance(', $card);
        $this->assertStringContainsString('openNoShow(', $card);
        $this->assertStringContainsString('openComplete(', $card);
    }

    public function test_attendance_confirmed_card_has_no_redundant_contact_actions(): void
    {
        $appointment = $this->makeAppointment('10:00', '11:00');
        app(AttendanceConfirmationService::class)->confirmByReceptionist($appointment, $this->receptionist);

        $card = $this->card($this->page(), $appointment->id);

        $this->assertStringNotContainsString('openAttendance(', $card);
        $this->assertStringContainsString('Attendance confirmed', $card);
    }

    public function test_backend_authorization_for_attendance_actions_is_unchanged(): void
    {
        $appointment = $this->makeAppointment('10:00', '11:00');
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $customer = User::factory()->create();
        $customer->roles()->attach($customerRole);

        $this->actingAs($customer)
            ->post(route('receptionist.attendance.confirm', $appointment))
            ->assertStatus(403);
    }

    private function page(): string
    {
        return $this->actingAs($this->receptionist)->get(route('receptionist.active'))->assertOk()->getContent();
    }

    private function card(string $content, int $appointmentId): string
    {
        preg_match('/<article\s+id="appointment-' . $appointmentId . '"[\s\S]*?<\/article>/', $content, $matches);

        return $matches[0] ?? '';
    }

    private function customerUser(): User
    {
        $user = User::factory()->create(['first_name' => 'Cora', 'last_name' => 'Customer']);
        $user->roles()->attach(Role::firstOrCreate(['name' => 'customer']));

        Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Cora',
            'last_name' => 'Customer',
            'email' => $user->email,
            'phone_number' => '0917' . random_int(100000, 999999),
        ]);

        return $user;
    }

    private function makeAppointment(string $start, string $end, string $date = '2026-12-01'): Appointment
    {
        $customer = Customer::create([
            'user_id' => null,
            'first_name' => 'Button',
            'last_name' => 'Customer',
            'email' => null,
            'phone_number' => '0917' . random_int(100000, 999999),
        ]);
        $approver = User::factory()->create();

        return Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $date,
            'start_time' => $start . ':00',
            'end_time' => $end . ':00',
            'status' => 'confirmed',
            'total_price' => 1000,
            'confirmed_at' => Carbon::now()->subDay(),
            'confirmed_by' => $approver->id,
        ]);
    }
}
