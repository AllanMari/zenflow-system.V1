<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Services\AppointmentOccupancyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Read-only-style audit assertions for resource release. No 15-minute action
 * is permitted to release staff or room capacity.
 */
class OccupancyReleaseTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;

    private User $staff;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Manila'));

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $this->receptionist = User::factory()->create(['is_active' => true]);
        $this->receptionist->roles()->attach($receptionistRole);

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $this->staff = User::factory()->create(['is_active' => true]);
        $this->staff->roles()->attach($staffRole);

        $this->room = Room::create(['name' => 'Occupancy Room', 'status' => 'occupied', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cancellation_releases_room_and_occupancy(): void
    {
        $appointment = $this->makeAppointment('09:00', '10:00');

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.cancel', $appointment), ['reason' => 'customer_cancelled'])
            ->assertRedirect();

        $appointment->refresh();
        $this->room->refresh();

        $this->assertSame('cancelled', $appointment->status);
        $this->assertSame('available', $this->room->status);
        $this->assertFalse($appointment->isOccupyingResource());
        $this->assertFalse(Appointment::occupyingResources()->whereKey($appointment->id)->exists());
        $this->assertCount(0, app(AppointmentOccupancyService::class)->intervalsFrom(
            collect([$appointment]),
            $this->staff->id,
            $this->room->id
        ));
    }

    public function test_no_show_releases_room_and_occupancy(): void
    {
        $appointment = $this->makeAppointment('09:00', '10:00');

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.no-show', $appointment), [
                'action' => 'forfeit',
                'no_show_reason' => 'Customer did not arrive',
            ])
            ->assertSessionHas('success');

        $appointment->refresh();
        $this->room->refresh();

        $this->assertSame('no_show', $appointment->status);
        $this->assertSame('available', $this->room->status);
        $this->assertFalse($appointment->isOccupyingResource());
        $this->assertFalse(Appointment::occupyingResources()->whereKey($appointment->id)->exists());
    }

    public function test_fifteen_minute_lateness_alone_does_not_release_resources(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:20:00', 'Asia/Manila'));
        $appointment = $this->makeAppointment('10:00', '11:00');

        $appointment->refresh();
        $this->room->refresh();

        $this->assertSame('confirmed', $appointment->status);
        $this->assertTrue($appointment->isOccupyingResource());
        $this->assertSame('occupied', $this->room->status);
        $this->assertCount(1, app(AppointmentOccupancyService::class)->intervalsFrom(
            collect([$appointment]),
            $this->staff->id,
            $this->room->id
        ));
        $this->assertNull($appointment->no_show_reason);
        $this->assertNull($appointment->cancellation_reason);
    }

    private function makeAppointment(string $start, string $end): Appointment
    {
        $customer = Customer::create([
            'user_id' => null,
            'first_name' => 'Occupancy',
            'last_name' => 'Guest',
            'email' => null,
            'phone_number' => '0917' . random_int(100000, 999999),
        ]);
        $approver = User::factory()->create();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $this->room->id,
            'appointment_date' => '2026-12-01',
            'start_time' => $start . ':00',
            'end_time' => $end . ':00',
            'status' => 'confirmed',
            'total_price' => 1000,
            'confirmed_at' => Carbon::now()->subDay(),
            'confirmed_by' => $approver->id,
        ]);
        $appointment->syncStaff($this->staff->id, []);

        return $appointment;
    }
}
