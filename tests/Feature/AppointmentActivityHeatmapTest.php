<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\SalesAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Focused coverage for the Appointment Activity Heatmap.
 *
 * The heatmap exposes two 7x24 matrices built from the same reporting period:
 *  - demand:  scheduled appointments (completed, confirmed, valid pending)
 *  - booking: booking creation timestamps (all statuses)
 */
class AppointmentActivityHeatmapTest extends TestCase
{
    use RefreshDatabase;

    private SalesAnalyticsService $service;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Wednesday, 18 November 2026, 12:00 Asia/Manila.
        Carbon::setTestNow(Carbon::parse('2026-11-18 12:00:00', 'Asia/Manila'));

        config(['app.timezone' => 'Asia/Manila']);

        $this->service = app(SalesAnalyticsService::class);

        $this->customer = Customer::create([
            'first_name'   => 'Heatmap',
            'last_name'    => 'Client',
            'phone_number' => '09170000001',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeAppointment(array $attributes = [], ?Carbon $createdAt = null): Appointment
    {
        $appointment = Appointment::create(array_merge([
            'customer_id'      => $this->customer->id,
            'appointment_date' => '2026-11-16',
            'start_time'       => '09:00:00',
            'end_time'         => '10:00:00',
            'status'           => 'confirmed',
            'total_price'      => 500.00,
        ], $attributes));

        if ($createdAt !== null) {
            $appointment->created_at = $createdAt;
            $appointment->save();
        }

        return $appointment;
    }

    private function heatmap(string $start = '2026-11-16', string $end = '2026-11-22'): array
    {
        return $this->service->getAppointmentActivityHeatmap(
            Carbon::parse($start, 'Asia/Manila')->startOfDay(),
            Carbon::parse($end, 'Asia/Manila')->endOfDay()
        );
    }

    private function at(string $dateTime): Carbon
    {
        return Carbon::parse($dateTime, 'Asia/Manila');
    }

    // -------------------------------------------------------------------------
    // Demand filtering
    // -------------------------------------------------------------------------

    public function test_demand_includes_completed_confirmed_and_valid_pending(): void
    {
        $this->makeAppointment([
            'appointment_date' => '2026-11-16',
            'start_time'       => '09:00:00',
            'status'           => 'confirmed',
        ], $this->at('2026-11-16 08:00:00'));

        $this->makeAppointment([
            'appointment_date' => '2026-11-18',
            'start_time'       => '14:00:00',
            'status'           => 'completed',
        ], $this->at('2026-11-17 08:00:00'));

        $this->makeAppointment([
            'appointment_date' => '2026-11-20',
            'start_time'       => '10:00:00',
            'status'           => 'pending',
        ], $this->at('2026-11-18 11:00:00'));

        $heatmap = $this->heatmap();

        $this->assertSame(1, $heatmap['demand']['cells'][0][9], 'Monday 09:00 confirmed');
        $this->assertSame(1, $heatmap['demand']['cells'][2][14], 'Wednesday 14:00 completed');
        $this->assertSame(1, $heatmap['demand']['cells'][4][10], 'Friday 10:00 valid pending');
        $this->assertSame(3, $heatmap['demand']['total']);
    }

    public function test_demand_excludes_cancelled_no_show_and_expired_pending(): void
    {
        // Cancelled today.
        $this->makeAppointment([
            'appointment_date' => '2026-11-18',
            'start_time'       => '11:00:00',
            'status'           => 'cancelled',
            'cancellation_reason' => 'guest_request',
        ], $this->at('2026-11-17 11:00:00'));

        // Explicit no-show status.
        $this->makeAppointment([
            'appointment_date' => '2026-11-18',
            'start_time'       => '12:00:00',
            'status'           => 'no_show',
            'no_show_reason'   => 'Client did not arrive',
        ], $this->at('2026-11-17 12:00:00'));

        // Customer no-show stored as a cancelled appointment.
        $this->makeAppointment([
            'appointment_date' => '2026-11-18',
            'start_time'       => '13:00:00',
            'status'           => 'cancelled',
            'cancellation_reason' => 'customer_no_show',
        ], $this->at('2026-11-17 13:00:00'));

        // Pending whose 24-hour submission window has passed.
        $this->makeAppointment([
            'appointment_date' => '2026-11-18',
            'start_time'       => '15:00:00',
            'status'           => 'pending',
        ], $this->at('2026-11-17 10:00:00'));

        // Pending whose scheduled date has already passed.
        $this->makeAppointment([
            'appointment_date' => '2026-11-17',
            'start_time'       => '16:00:00',
            'status'           => 'pending',
        ], $this->at('2026-11-18 11:00:00'));

        // Valid pending outside the selected range: must not leak through the
        // OR condition around the pending scope.
        $this->makeAppointment([
            'appointment_date' => '2026-11-30',
            'start_time'       => '09:00:00',
            'status'           => 'pending',
        ], $this->at('2026-11-18 11:00:00'));

        // Control: this one must be counted.
        $this->makeAppointment([
            'appointment_date' => '2026-11-18',
            'start_time'       => '11:00:00',
            'status'           => 'confirmed',
        ], $this->at('2026-11-18 08:00:00'));

        $heatmap = $this->heatmap();

        $this->assertSame(1, $heatmap['demand']['cells'][2][11]);
        $this->assertSame(1, $heatmap['demand']['total'], 'Only the confirmed control should remain');
    }

    public function test_demand_preserves_existing_pending_expiration_rules(): void
    {
        // Today at 09:00, created one hour ago: start_time must not expire it.
        $this->makeAppointment([
            'appointment_date' => '2026-11-18',
            'start_time'       => '09:00:00',
            'status'           => 'pending',
        ], $this->at('2026-11-18 11:00:00'));

        // Today but created 25 hours ago: 24-hour deadline has passed.
        $this->makeAppointment([
            'appointment_date' => '2026-11-18',
            'start_time'       => '10:00:00',
            'status'           => 'pending',
        ], $this->at('2026-11-17 11:00:00'));

        // Tomorrow, created one hour ago: still valid.
        $this->makeAppointment([
            'appointment_date' => '2026-11-19',
            'start_time'       => '11:00:00',
            'status'           => 'pending',
        ], $this->at('2026-11-18 11:00:00'));

        // Tomorrow but created 25 hours ago: 24-hour deadline wins.
        $this->makeAppointment([
            'appointment_date' => '2026-11-19',
            'start_time'       => '12:00:00',
            'status'           => 'pending',
        ], $this->at('2026-11-17 11:00:00'));

        // Appointment date already passed, even though created recently.
        $this->makeAppointment([
            'appointment_date' => '2026-11-17',
            'start_time'       => '13:00:00',
            'status'           => 'pending',
        ], $this->at('2026-11-18 11:00:00'));

        $heatmap = $this->heatmap();

        $this->assertSame(1, $heatmap['demand']['cells'][2][9]);
        $this->assertSame(1, $heatmap['demand']['cells'][3][11]);
        $this->assertSame(2, $heatmap['demand']['total']);
    }

    // -------------------------------------------------------------------------
    // Booking activity
    // -------------------------------------------------------------------------

    public function test_booking_uses_created_at_and_includes_every_status(): void
    {
        // Scheduled far outside the report range; only creation time matters.
        $inFuture = ['appointment_date' => '2027-01-15'];

        $this->makeAppointment(array_merge($inFuture, [
            'start_time' => '09:00:00',
            'status'     => 'confirmed',
        ]), $this->at('2026-11-16 09:00:00'));

        $this->makeAppointment(array_merge($inFuture, [
            'start_time' => '10:00:00',
            'status'     => 'cancelled',
            'cancellation_reason' => 'guest_request',
        ]), $this->at('2026-11-16 10:00:00'));

        $this->makeAppointment(array_merge($inFuture, [
            'start_time' => '11:00:00',
            'status'     => 'no_show',
            'no_show_reason' => 'Client did not arrive',
        ]), $this->at('2026-11-16 11:00:00'));

        // Expired pending, still counted as a booking.
        $this->makeAppointment(array_merge($inFuture, [
            'start_time' => '12:00:00',
            'status'     => 'pending',
        ]), $this->at('2026-11-17 06:00:00'));

        // Valid pending.
        $this->makeAppointment(array_merge($inFuture, [
            'start_time' => '13:00:00',
            'status'     => 'pending',
        ]), $this->at('2026-11-18 08:00:00'));

        // Scheduled inside the range but created before it: demand only.
        $this->makeAppointment([
            'appointment_date' => '2026-11-18',
            'start_time'       => '14:00:00',
            'status'           => 'confirmed',
        ], $this->at('2026-11-10 09:00:00'));

        $heatmap = $this->heatmap();

        $this->assertSame(5, $heatmap['booking']['total']);
        $this->assertSame(1, $heatmap['booking']['cells'][0][9]);
        $this->assertSame(1, $heatmap['booking']['cells'][0][10]);
        $this->assertSame(1, $heatmap['booking']['cells'][0][11]);
        $this->assertSame(1, $heatmap['booking']['cells'][1][6]);
        $this->assertSame(1, $heatmap['booking']['cells'][2][8]);

        // The in-range scheduled appointment appears in demand, not booking.
        $this->assertSame(1, $heatmap['demand']['cells'][2][14]);
        $this->assertSame(1, $heatmap['demand']['total']);
    }

    public function test_booking_boundaries_follow_manila_midnight(): void
    {
        // Only Monday 16 November 2026 is selected.
        $this->makeAppointment([
            'appointment_date' => '2026-12-01',
            'status'           => 'confirmed',
        ], $this->at('2026-11-15 23:30:00')); // Sunday, before range

        $this->makeAppointment([
            'appointment_date' => '2026-12-01',
            'status'           => 'confirmed',
        ], $this->at('2026-11-16 00:30:00')); // Monday 00:30

        $this->makeAppointment([
            'appointment_date' => '2026-12-01',
            'status'           => 'confirmed',
        ], $this->at('2026-11-16 23:30:00')); // Monday 23:30

        $this->makeAppointment([
            'appointment_date' => '2026-12-01',
            'status'           => 'confirmed',
        ], $this->at('2026-11-17 00:00:00')); // Tuesday, exactly at exclusive end

        $heatmap = $this->heatmap('2026-11-16', '2026-11-16');

        $this->assertSame(2, $heatmap['booking']['total']);
        $this->assertSame(1, $heatmap['booking']['cells'][0][0]);
        $this->assertSame(1, $heatmap['booking']['cells'][0][23]);
        $this->assertSame(0, $heatmap['booking']['cells'][6][23]);
        $this->assertSame(0, $heatmap['booking']['cells'][1][0]);
    }

    // -------------------------------------------------------------------------
    // Shape and labels
    // -------------------------------------------------------------------------

    public function test_heatmap_has_seven_by_twenty_four_dimensions_and_labels(): void
    {
        $heatmap = $this->heatmap();

        $this->assertSame([
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
        ], $heatmap['days']);

        $this->assertCount(24, $heatmap['hours']);
        $this->assertSame('00:00', $heatmap['hours'][0]);
        $this->assertSame('23:00', $heatmap['hours'][23]);
        $this->assertSame('Asia/Manila', $heatmap['timezone']);

        foreach (['demand', 'booking'] as $key) {
            $this->assertCount(7, $heatmap[$key]['cells'], "{$key} rows");

            foreach ($heatmap[$key]['cells'] as $row) {
                $this->assertCount(24, $row, "{$key} columns");
            }

            $sum = array_sum(array_map('array_sum', $heatmap[$key]['cells']));
            $max = 0;

            foreach ($heatmap[$key]['cells'] as $row) {
                $max = max($max, max($row));
            }

            $this->assertSame($sum, $heatmap[$key]['total']);
            $this->assertSame($max, $heatmap[$key]['max']);
        }

        $this->assertSame('appointment', $heatmap['demand']['singular']);
        $this->assertSame('appointments', $heatmap['demand']['plural']);
        $this->assertSame('booking', $heatmap['booking']['singular']);
        $this->assertSame('bookings', $heatmap['booking']['plural']);
    }

    // -------------------------------------------------------------------------
    // Rendered page wiring
    // -------------------------------------------------------------------------

    public function test_analytics_tab_renders_both_datasets_and_toggle_wiring(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);

        $admin = User::firstOrCreate(
            ['username' => 'heatmap_admin'],
            [
                'first_name' => 'Heatmap',
                'last_name'  => 'Admin',
                'password'   => bcrypt('password'),
                'is_active'  => true,
            ]
        );

        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $response = $this->actingAs($admin)->get(route('admin.sales', [
            'period'     => 'custom',
            'start_date' => '2026-11-16',
            'end_date'   => '2026-11-22',
        ]));

        $response->assertOk();

        $response->assertViewHas(
            'appointmentActivityHeatmap',
            fn ($heatmap) => isset($heatmap['demand'], $heatmap['booking'])
                && count($heatmap['demand']['cells']) === 7
                && count($heatmap['booking']['cells']) === 7
        );

        $response->assertSee('id="activity-heatmap"', false);
        $response->assertSee('Appointment Demand');
        $response->assertSee('Booking Activity');
        $response->assertSee("heatmapMode: 'demand'", false);
        $response->assertSee("heatmapMode = 'booking'", false);
        $response->assertSee('heatmapCellClass(dayIndex, hourIndex)', false);
        $response->assertSee('heatmapTooltip(dayIndex, hourIndex)', false);
        $response->assertSee('heatmapSummary()', false);
    }
}
