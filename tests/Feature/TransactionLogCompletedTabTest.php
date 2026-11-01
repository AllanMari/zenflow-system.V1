<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\SalesAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Focused regression tests for the Completed Transaction Log.
 *
 * Verifies that buildTransactionRows() now produces exactly one row per
 * appointment, regardless of how many Payment records that appointment has.
 *
 * Audit finding: TXL-1, TXL-2, TXL-3, TXL-4
 */
class TransactionLogCompletedTabTest extends TestCase
{
    use RefreshDatabase;

    private SalesAnalyticsService $service;
    private Room $room;
    private Service $svc;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SalesAnalyticsService::class);

        $cat = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);

        $this->svc = Service::create([
            'category_id'      => $cat->id,
            'name'             => 'Swedish Massage',
            'price'            => 720.00,
            'duration_minutes' => 60,
            'is_active'        => true,
        ]);

        $this->room = Room::create([
            'name'      => 'A1',
            'status'    => 'available',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'first_name'   => 'Test',
            'last_name'    => 'Client',
            'phone_number' => '09100000001',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a completed appointment for $this->customer linked to $this->room
     * and attach $this->svc at the given price.
     */
    private function appt(float $price, string $date, ?int $userId = null): Appointment
    {
        $a = Appointment::create([
            'customer_id'      => $this->customer->id,
            'room_id'          => $this->room->id,
            'user_id'          => $userId,
            'appointment_date' => $date,
            'start_time'       => '14:00:00',
            'end_time'         => '16:00:00',
            'status'           => 'completed',
            'total_price'      => $price,
        ]);

        $a->services()->attach($this->svc->id, [
            'price_at_booking' => $price,
            'service_name'     => $this->svc->name,
            'service_duration' => $this->svc->duration_minutes,
            'is_extra'         => false,
        ]);

        return $a;
    }

    private function pay(Appointment $a, float $amount, string $type = 'full'): Payment
    {
        return Payment::create([
            'appointment_id' => $a->id,
            'amount'         => $amount,
            'type'           => $type,
            'payment_method' => 'cash',
            'paid_at'        => Carbon::today(),
        ]);
    }

    private function req(string $date, string $status = 'completed'): Request
    {
        return Request::create('/admin/sales', 'GET', [
            'period'     => 'daily',
            'start_date' => $date,
            'end_date'   => $date,
            'status'     => $status,
        ]);
    }

    private function txRows(string $date, string $status = 'completed'): array
    {
        return $this->service->getDashboardData($this->req($date, $status))['txLogData']['rows'];
    }

    private function txData(string $date, string $status = 'completed'): array
    {
        return $this->service->getDashboardData($this->req($date, $status))['txLogData'];
    }

    // -------------------------------------------------------------------------
    // Test: single payment → one row
    // -------------------------------------------------------------------------

    public function test_single_full_payment_produces_one_row(): void
    {
        $date = Carbon::today()->toDateString();
        $a    = $this->appt(720.00, $date);
        $this->pay($a, 720.00, 'full');

        $rows = $this->txRows($date);

        $this->assertCount(1, $rows, '1 appointment with 1 payment → 1 row');
        $this->assertEquals('720.00', $rows[0]['grossAmount']);
        $this->assertEquals('720.00', $rows[0]['netAmount']);
        // No staff assigned → commission = 0
        $this->assertEquals('0.00', $rows[0]['therapistCom']);
    }

    // -------------------------------------------------------------------------
    // Test: deposit + completion → still ONE row (TXL-1 regression)
    // -------------------------------------------------------------------------

    public function test_split_payment_deposit_plus_completion_produces_one_row(): void
    {
        $date = Carbon::today()->toDateString();
        $a    = $this->appt(1500.00, $date);
        $this->pay($a, 500.00, 'deposit');
        $this->pay($a, 1000.00, 'completion');

        $rows = $this->txRows($date);

        $this->assertCount(1, $rows,
            'TXL-1 REGRESSION: appointment with deposit + completion must produce exactly 1 row');
        $this->assertEquals('1,500.00', $rows[0]['grossAmount'],
            'Gross must equal the booked service price, not the sum of individual payments');
        $this->assertEquals('1,500.00', $rows[0]['netAmount']);
    }

    // -------------------------------------------------------------------------
    // Test: deposit + completion + additional → still ONE row
    // -------------------------------------------------------------------------

    public function test_three_payment_records_produces_one_row(): void
    {
        $date = Carbon::today()->toDateString();
        $a    = $this->appt(2000.00, $date);
        $this->pay($a, 400.00, 'deposit');
        $this->pay($a, 1500.00, 'completion');
        $this->pay($a, 100.00, 'additional');

        $rows = $this->txRows($date);

        $this->assertCount(1, $rows, '3 payments on 1 appointment must produce 1 row');
        $this->assertEquals('2,000.00', $rows[0]['grossAmount']);
    }

    // -------------------------------------------------------------------------
    // Test: two different appointments → two rows
    // -------------------------------------------------------------------------

    public function test_two_appointments_produce_two_rows(): void
    {
        $date = Carbon::today()->toDateString();

        $a1 = $this->appt(720.00, $date);
        $this->pay($a1, 720.00, 'full');

        // appt2 has 2 payments — must NOT inflate row count
        $a2 = $this->appt(720.00, $date);
        $this->pay($a2, 144.00, 'deposit');
        $this->pay($a2, 576.00, 'completion');

        $rows = $this->txRows($date);

        $this->assertCount(2, $rows,
            '2 appointments (1 with 1 payment, 1 with 2 payments) → exactly 2 rows');
    }

    // -------------------------------------------------------------------------
    // Test: totalFiltered and visible row count are consistent (TXL-3 regression)
    // -------------------------------------------------------------------------

    public function test_totalFiltered_equals_row_count_on_single_page(): void
    {
        $date = Carbon::today()->toDateString();

        for ($i = 0; $i < 3; $i++) {
            $a = $this->appt(720.00, $date);
            $this->pay($a, 720.00, 'full');
            if ($i === 1) {
                // Extra payment on the second appointment
                $this->pay($a, 100.00, 'additional');
            }
        }

        $data      = $this->txData($date);
        $rows      = $data['rows'];
        $totalFilt = $data['totalFiltered'];

        $this->assertCount(3, $rows,
            'TXL-3 REGRESSION: 3 appointments must show 3 rows regardless of payment count');
        $this->assertEquals(3, $totalFilt,
            'totalFiltered must equal appointment count, not payment count');
        $this->assertCount($totalFilt, $rows,
            '"Showing X of Y": visible row count and totalFiltered must agree on single page');
    }

    // -------------------------------------------------------------------------
    // Test: visible column sums equal footer totals (TXL-4 regression)
    // -------------------------------------------------------------------------

    public function test_grand_totals_match_visible_row_sums(): void
    {
        $date = Carbon::today()->toDateString();

        // appt1: single full payment
        $a1 = $this->appt(1000.00, $date);
        $this->pay($a1, 1000.00, 'full');

        // appt2: deposit + completion (2 payments)
        $a2 = $this->appt(1000.00, $date);
        $this->pay($a2, 200.00, 'deposit');
        $this->pay($a2, 800.00, 'completion');

        $data = $this->txData($date);
        $rows = $data['rows'];

        $this->assertCount(2, $rows);

        $sumGross = array_sum(array_map(fn ($r) => (float) str_replace(',', '', $r['grossAmount']), $rows));
        $sumNet   = array_sum(array_map(fn ($r) => (float) str_replace(',', '', $r['netAmount']), $rows));
        $sumCom   = array_sum(array_map(fn ($r) => (float) str_replace(',', '', $r['therapistCom']), $rows));

        $this->assertEqualsWithDelta($data['grandGross'], $sumGross, 0.01,
            'TXL-4 REGRESSION: visible Gross sum must equal footer grandGross');
        $this->assertEqualsWithDelta($data['grandNet'], $sumNet, 0.01,
            'TXL-4 REGRESSION: visible Net sum must equal footer grandNet');
        $this->assertEqualsWithDelta($data['grandCom'], $sumCom, 0.01,
            'TXL-4 REGRESSION: visible Commission sum must equal footer grandCom');
    }

    // -------------------------------------------------------------------------
    // Test: commission formula — net × 30% when staff is assigned
    // -------------------------------------------------------------------------

    public function test_commission_is_30_percent_of_net_when_staff_assigned(): void
    {
        $date  = Carbon::today()->toDateString();
        $staff = User::factory()->create();

        $a = $this->appt(720.00, $date, $staff->id);
        $this->pay($a, 720.00, 'full');

        $rows = $this->txRows($date);

        $this->assertCount(1, $rows);
        $this->assertEquals('30', $rows[0]['comPct']);
        $this->assertEquals('216.00', $rows[0]['therapistCom'],
            '₱720.00 × 30% = ₱216.00');
    }

    // -------------------------------------------------------------------------
    // Test: commission is zero when no staff is assigned
    // -------------------------------------------------------------------------

    public function test_commission_is_zero_when_no_staff_assigned(): void
    {
        $date = Carbon::today()->toDateString();
        $a    = $this->appt(720.00, $date); // no user_id
        $this->pay($a, 720.00, 'full');

        $rows = $this->txRows($date);

        $this->assertCount(1, $rows);
        $this->assertEquals('0.00', $rows[0]['therapistCom'],
            'No staff assigned → commission must be 0.00');
    }

    // -------------------------------------------------------------------------
    // Test: paginator->total() counts appointments, not payments (TXL-2)
    // -------------------------------------------------------------------------

    public function test_paginator_total_counts_appointments_not_payments(): void
    {
        $date = Carbon::today()->toDateString();

        // 3 appointments; first one has 2 payments
        for ($i = 0; $i < 3; $i++) {
            $a = $this->appt(500.00, $date);
            $this->pay($a, 500.00, 'full');
            if ($i === 0) {
                $this->pay($a, 50.00, 'additional');
            }
        }

        $data      = $this->txData($date);
        $paginator = $data['pagination'];

        $this->assertEquals(3, $paginator->total(),
            'TXL-2 REGRESSION: paginator->total() must count unique appointments, not payment records');
    }

    // -------------------------------------------------------------------------
    // Test: no_show tab returns expected structure (regression guard)
    // -------------------------------------------------------------------------

    public function test_no_show_tab_returns_expected_structure(): void
    {
        $date = Carbon::today()->toDateString();

        Appointment::create([
            'customer_id'      => $this->customer->id,
            'room_id'          => $this->room->id,
            'appointment_date' => $date,
            'start_time'       => '10:00:00',
            'end_time'         => '11:00:00',
            'status'           => 'no_show',
            'total_price'      => 500.00,
        ]);

        $data = $this->txData($date, 'no_show');

        $this->assertArrayHasKey('rows', $data);
        $this->assertArrayHasKey('grandGross', $data);
        $this->assertArrayHasKey('totalFiltered', $data);
        $this->assertArrayHasKey('pagination', $data);
    }
}
