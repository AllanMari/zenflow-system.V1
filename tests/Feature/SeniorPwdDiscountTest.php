<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\SalesAnalyticsService;
use App\Services\TherapistCommissionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SeniorPwdDiscountTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;
    private User $admin;
    private User $therapist1;
    private User $therapist2;
    private User $customerUser;
    private Customer $customer;
    private Service $service;
    private Service $extraService;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        $this->receptionist = User::factory()->create(['is_active' => true]);
        $this->receptionist->roles()->attach($receptionistRole);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole);

        $this->therapist1 = User::factory()->create(['first_name' => 'Elena', 'last_name' => 'Cruz', 'is_active' => true]);
        $this->therapist2 = User::factory()->create(['first_name' => 'Marco', 'last_name' => 'Reyes', 'is_active' => true]);
        $this->therapist1->roles()->attach($staffRole);
        $this->therapist2->roles()->attach($staffRole);

        $this->customerUser = User::factory()->create(['is_active' => true]);
        $this->customerUser->roles()->attach($customerRole);
        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'phone_number' => '09171234567',
        ]);

        $cat = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);
        $this->service = Service::create([
            'category_id' => $cat->id,
            'name' => 'Signature Massage',
            'duration_minutes' => 60,
            'price' => 2000,
            'therapists_required' => 1,
            'requires_room' => true,
            'is_active' => true,
        ]);
        $this->extraService = Service::create([
            'category_id' => $cat->id,
            'name' => 'Hot Stone Add-on',
            'duration_minutes' => 30,
            'price' => 500,
            'therapists_required' => 1,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $this->room = Room::create(['name' => 'Room 1', 'status' => 'occupied', 'is_active' => true]);
    }

    private function makeConfirmedAppointment(float $price = 2000.0, bool $twoTherapists = false): Appointment
    {
        $appt = Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->therapist1->id,
            'room_id' => $this->room->id,
            'appointment_date' => Carbon::today('Asia/Manila')->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => $price,
        ]);

        $appt->services()->attach($this->service->id, [
            'service_name' => $this->service->name,
            'service_duration' => 60,
            'price_at_booking' => $price,
        ]);

        if ($twoTherapists) {
            $appt->syncStaff($this->therapist1->id, [$this->therapist2->id]);
        } else {
            $appt->syncStaff($this->therapist1->id, []);
        }

        return $appt;
    }

    public function test_active_sessions_displays_discount_labels_without_voluntary(): void
    {
        $this->makeConfirmedAppointment();

        $res = $this->actingAs($this->receptionist)->get(route('receptionist.active'));
        $res->assertOk();
        $res->assertSee('Senior Citizen Discount — 20%');
        $res->assertSee('PWD Discount — 20%');
        $res->assertDontSee('voluntary', false);
        $res->assertDontSee('Voluntary', false);
    }

    public function test_complete_applies_senior_citizen_discount_and_preserves_original_prices(): void
    {
        $appt = $this->makeConfirmedAppointment(2000);

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.complete', $appt), [
                'complete_appointment_id' => $appt->id,
                'payment_method' => 'cash',
                'payment_type' => 'full',
                'discount_type' => 'senior_citizen',
                'discount_verified' => '1',
            ])
            ->assertRedirect(route('receptionist.active'))
            ->assertSessionHas('success');

        $appt->refresh();
        $this->assertEquals('completed', $appt->status);
        $this->assertEquals('senior_citizen', $appt->discount_type);
        $this->assertEquals('20.00', $appt->discount_percent);
        $this->assertEquals('400.00', $appt->discount_amount);
        $this->assertEquals('2000.00', $appt->original_total_price);
        $this->assertEquals('1600.00', $appt->total_price);
        $this->assertEquals($this->receptionist->id, $appt->discount_applied_by);
        $this->assertNotNull($appt->discount_applied_at);
        $this->assertEquals('Senior Citizen Discount — 20%', $appt->discount_label);
        $this->assertEquals('Senior 20%', $appt->short_discount_badge);

        // Original service pivot price untouched
        $this->assertEquals('2000.00', $appt->services()->first()->pivot->price_at_booking);

        // Payment recorded for discounted total
        $this->assertEquals(1600.00, (float) $appt->payments()->sum('amount'));
    }

    public function test_complete_applies_pwd_discount_including_extra_services(): void
    {
        $appt = $this->makeConfirmedAppointment(2000);
        $appt->services()->attach($this->extraService->id, [
            'service_name' => $this->extraService->name,
            'service_duration' => 30,
            'price_at_booking' => 500,
            'is_extra' => true,
        ]);
        $appt->update(['total_price' => 2500]);

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.complete', $appt), [
                'complete_appointment_id' => $appt->id,
                'payment_method' => 'gcash',
                'payment_type' => 'full',
                'discount_type' => 'pwd',
                'discount_verified' => '1',
            ])
            ->assertRedirect(route('receptionist.active'));

        $appt->refresh();
        $this->assertEquals('2500.00', $appt->original_total_price);
        $this->assertEquals('500.00', $appt->discount_amount);
        $this->assertEquals('2000.00', $appt->total_price);
        $this->assertEquals('PWD 20%', $appt->short_discount_badge);
        $this->assertEquals(2000.00, (float) $appt->payments()->sum('amount'));
    }

    public function test_complete_with_no_discount_preserves_original_total_price(): void
    {
        $appt = $this->makeConfirmedAppointment(2000);

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.complete', $appt), [
                'complete_appointment_id' => $appt->id,
                'payment_method' => 'cash',
                'payment_type' => 'full',
                'discount_type' => '',
            ])
            ->assertRedirect(route('receptionist.active'));

        $appt->refresh();
        $this->assertEquals('completed', $appt->status);
        $this->assertNull($appt->discount_type);
        $this->assertNull($appt->discount_percent);
        $this->assertEquals('0.00', $appt->discount_amount);
        $this->assertEquals('2000.00', $appt->original_total_price);
        $this->assertEquals('2000.00', $appt->total_price);
    }

    public function test_discount_requires_eligibility_verification_and_preserves_modal_input_on_error(): void
    {
        $appt = $this->makeConfirmedAppointment(2000);

        $res = $this->actingAs($this->receptionist)
            ->from(route('receptionist.active'))
            ->post(route('receptionist.complete', $appt), [
                'complete_appointment_id' => $appt->id,
                'payment_method' => 'cash',
                'payment_type' => 'full',
                'discount_type' => 'senior_citizen',
            ]);

        $res->assertRedirect(route('receptionist.active'))
            ->assertSessionHasErrors('discount_verified');
        $this->assertEquals($appt->id, session()->getOldInput('complete_appointment_id'));
        $this->assertEquals('senior_citizen', session()->getOldInput('discount_type'));
        $this->assertEquals('confirmed', $appt->fresh()->status);

        // Customer cannot complete appointment
        $this->actingAs($this->customerUser)
            ->post(route('receptionist.complete', $appt), [
                'payment_method' => 'cash',
                'payment_type' => 'full',
            ])
            ->assertStatus(403);
    }

    public function test_deposit_plus_discount_collects_remaining_balance_and_excess_deposit_blocks_completion(): void
    {
        // Case 1: ₱1,000 deposit on ₱2,000 -> Senior 20% (₱1,600) -> collects ₱600
        $appt1 = $this->makeConfirmedAppointment(2000);
        Payment::create([
            'appointment_id' => $appt1->id,
            'payment_method' => 'cash',
            'amount' => 1000,
            'type' => 'deposit',
            'paid_at' => now(),
        ]);

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.complete', $appt1), [
                'complete_appointment_id' => $appt1->id,
                'payment_method' => 'cash',
                'payment_type' => 'completion',
                'discount_type' => 'senior_citizen',
                'discount_verified' => '1',
            ])
            ->assertRedirect(route('receptionist.active'))
            ->assertSessionHas('success');

        $appt1->refresh();
        $this->assertEquals('completed', $appt1->status);
        $this->assertEquals('1600.00', $appt1->total_price);
        $this->assertEquals(1600.00, (float) $appt1->payments()->sum('amount'));

        // Case 2: ₱1,800 deposit on ₱2,000 -> Senior 20% (₱1,600) -> Excess ₱200 blocks completion
        $appt2 = $this->makeConfirmedAppointment(2000);
        Payment::create([
            'appointment_id' => $appt2->id,
            'payment_method' => 'cash',
            'amount' => 1800,
            'type' => 'deposit',
            'paid_at' => now(),
        ]);

        $res = $this->actingAs($this->receptionist)
            ->from(route('receptionist.active'))
            ->post(route('receptionist.complete', $appt2), [
                'complete_appointment_id' => $appt2->id,
                'payment_method' => 'cash',
                'payment_type' => 'full',
                'discount_type' => 'senior_citizen',
                'discount_verified' => '1',
            ]);

        $res->assertRedirect(route('receptionist.active'))
            ->assertSessionHas('error');

        $appt2->refresh();
        $this->assertEquals('confirmed', $appt2->status);
        $this->assertEquals('2000.00', $appt2->total_price);
        $this->assertNull($appt2->discount_type);
        $this->assertEquals(1, $appt2->payments()->count());
        $this->assertEquals(0, $appt2->payments()->where('type', 'refund')->count());
    }

    public function test_shared_commission_uses_discounted_amount_and_matches_across_analytics_and_staff_controller(): void
    {
        // ₱2,000 -> 20% discount -> ₱1,600 -> 30% pool = ₱480 -> 2 therapists = ₱240 each
        $appt = $this->makeConfirmedAppointment(2000, true);
        Payment::create([
            'appointment_id' => $appt->id,
            'payment_method' => 'cash',
            'amount' => 1000,
            'type' => 'deposit',
            'paid_at' => now(),
        ]);

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.complete', $appt), [
                'complete_appointment_id' => $appt->id,
                'payment_method' => 'cash',
                'payment_type' => 'completion',
                'discount_type' => 'senior_citizen',
                'discount_verified' => '1',
            ])
            ->assertRedirect(route('receptionist.active'));

        $appt->refresh()->load(['assignedStaff', 'staff', 'services', 'payments']);

        $calc = app(TherapistCommissionService::class)->calculateForAppointment($appt);
        $this->assertEquals(480.0, $calc['total_pool']);
        $this->assertEquals(24000, $calc['shares'][$this->therapist1->id]);
        $this->assertEquals(24000, $calc['shares'][$this->therapist2->id]);

        // SalesAnalyticsService staffPerformance & transactionLog must match
        $analytics = app(SalesAnalyticsService::class)->getDashboardData(new Request([
            'period' => 'today',
        ]));
        $staffRows = collect($analytics['staffPerformance']);
        $this->assertEquals(240.00, $staffRows->firstWhere('id', $this->therapist1->id)['commission']);
        $this->assertEquals(240.00, $staffRows->firstWhere('id', $this->therapist2->id)['commission']);

        $txRes = $this->actingAs($this->receptionist)
            ->get(route('receptionist.sales.tx-log', ['period' => 'today']))
            ->assertOk();
        $txRes->assertSee('Senior 20%')
            ->assertSee('2,000.00')
            ->assertSee('400.00')
            ->assertSee('1,600.00')
            ->assertSee('480.00');

        // StaffController commission page must match
        $this->actingAs($this->therapist1)
            ->get(route('staff.commissions', ['preset' => 'today']))
            ->assertOk()
            ->assertSee('240.00');
    }

    public function test_invoice_and_history_views_display_discount_details_and_badges(): void
    {
        $appt = $this->makeConfirmedAppointment(2000);

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.complete', $appt), [
                'complete_appointment_id' => $appt->id,
                'payment_method' => 'cash',
                'payment_type' => 'full',
                'discount_type' => 'pwd',
                'discount_verified' => '1',
            ]);

        $this->actingAs($this->receptionist)
            ->get(route('receptionist.invoice', $appt))
            ->assertOk()
            ->assertSee('Original Amount')
            ->assertSee('PWD Discount — 20%')
            ->assertSee('1,600.00');

        $this->actingAs($this->admin)
            ->get(route('admin.appointments'))
            ->assertOk()
            ->assertSee('PWD 20%');
    }

    public function test_transaction_log_grand_totals_reconcile_and_do_not_double_subtract_service_discount(): void
    {
        // Service has an existing discount_price (2000 vs 2500), booked at 2000, then Senior 20% applied -> 1600
        $this->service->update(['price' => 2500, 'discount_price' => 2000]);
        $appt1 = $this->makeConfirmedAppointment(2000);

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.complete', $appt1), [
                'complete_appointment_id' => $appt1->id,
                'payment_method' => 'cash',
                'payment_type' => 'full',
                'discount_type' => 'senior_citizen',
                'discount_verified' => '1',
            ])
            ->assertRedirect(route('receptionist.active'));

        // Non-discounted historical appointment (no discount metadata)
        $appt2 = Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->therapist1->id,
            'room_id' => $this->room->id,
            'appointment_date' => Carbon::today('Asia/Manila')->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '13:30:00',
            'status' => 'completed',
            'total_price' => 500,
        ]);
        $appt2->services()->attach($this->extraService->id, [
            'service_name' => $this->extraService->name,
            'service_duration' => 30,
            'price_at_booking' => 500,
        ]);
        $appt2->syncStaff($this->therapist1->id, []);
        Payment::create([
            'appointment_id' => $appt2->id,
            'payment_method' => 'cash',
            'amount' => 500,
            'type' => 'full',
            'paid_at' => now(),
        ]);

        $analytics = app(SalesAnalyticsService::class)->getDashboardData(new Request([
            'period' => 'today',
        ]));
        $txData = $analytics['txLogData'];

        // Rows and grand totals must both equal: Gross 2500, Discount 400, Net 2100, Com 630
        $rowSumNet = collect($txData['rows'])->sum(fn($r) => (float) str_replace(',', '', $r['netAmount']));
        $this->assertEquals(2100.00, $rowSumNet);
        $this->assertEquals(2500.00, (float) $txData['grandGross']);
        $this->assertEquals(2100.00, (float) $txData['grandNet']);
        $this->assertEquals(630.00, (float) $txData['grandCom']);
    }

    public function test_daily_sales_summary_and_exports_handle_full_and_deposit_discounted_payments(): void
    {
        // 1. Fully paid Senior 20%: 2000 -> 1600
        $appt1 = $this->makeConfirmedAppointment(2000);
        $this->actingAs($this->receptionist)
            ->post(route('receptionist.complete', $appt1), [
                'complete_appointment_id' => $appt1->id,
                'payment_method' => 'cash',
                'payment_type' => 'full',
                'discount_type' => 'senior_citizen',
                'discount_verified' => '1',
            ]);

        // 2. Deposit (1000) + Completion (600) PWD 20%: 2000 -> 1600
        $appt2 = $this->makeConfirmedAppointment(2000);
        Payment::create([
            'appointment_id' => $appt2->id,
            'payment_method' => 'cash',
            'amount' => 1000,
            'type' => 'deposit',
            'paid_at' => now(),
        ]);
        $this->actingAs($this->receptionist)
            ->post(route('receptionist.complete', $appt2), [
                'complete_appointment_id' => $appt2->id,
                'payment_method' => 'cash',
                'payment_type' => 'completion',
                'discount_type' => 'pwd',
                'discount_verified' => '1',
            ]);

        $analytics = app(SalesAnalyticsService::class)->getDashboardData(new Request(['period' => 'today']));
        $summaryRow = collect($analytics['printServiceSummary'])->firstWhere('service_name', 'Signature Massage');

        $this->assertNotNull($summaryRow);
        $this->assertEquals(4000.00, round((float) $summaryRow['gross'], 2));
        $this->assertEquals(800.00, round((float) $summaryRow['discount'], 2));
        $this->assertEquals(3200.00, round((float) $summaryRow['net'], 2));

        // Verify CSV, XLSX, and PDF endpoints succeed and CSV contains exact totals
        $csvRes = $this->actingAs($this->admin)
            ->get(route('admin.sales.daily-report-csv', ['period' => 'today']))
            ->assertOk();
        $csvContent = $csvRes->streamedContent();
        $this->assertStringContainsString('4000.00', $csvContent);
        $this->assertStringContainsString('800.00', $csvContent);
        $this->assertStringContainsString('3200.00', $csvContent);

        $this->actingAs($this->admin)
            ->get(route('admin.sales.daily-report-xlsx', ['period' => 'today']))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.sales.daily-report-pdf', ['period' => 'today']))
            ->assertOk();
    }

    public function test_multi_therapist_commission_remainder_is_consistent_across_analytics_and_staff_controller(): void
    {
        // Primary is higher-ID therapist2; assisting is lower-ID therapist1.
        // Total 100.10 * 0.30 = 30.03 (3003 cents): lower-ID therapist1 gets 15.02, therapist2 gets 15.01.
        $appt = Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->therapist2->id,
            'room_id' => $this->room->id,
            'appointment_date' => Carbon::today('Asia/Manila')->toDateString(),
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'status' => 'completed',
            'total_price' => 100.10,
        ]);
        $appt->services()->attach($this->service->id, [
            'service_name' => $this->service->name,
            'service_duration' => 60,
            'price_at_booking' => 100.10,
        ]);
        $appt->syncStaff($this->therapist2->id, [$this->therapist1->id]);

        $analytics = app(SalesAnalyticsService::class)->getDashboardData(new Request(['period' => 'today']));
        $staffRows = collect($analytics['staffPerformance']);

        $this->assertEquals(15.02, $staffRows->firstWhere('id', $this->therapist1->id)['commission']);
        $this->assertEquals(15.01, $staffRows->firstWhere('id', $this->therapist2->id)['commission']);

        $this->actingAs($this->therapist1)
            ->get(route('staff.commissions', ['preset' => 'today']))
            ->assertOk()
            ->assertSee('15.02');

        $this->actingAs($this->therapist2)
            ->get(route('staff.commissions', ['preset' => 'today']))
            ->assertOk()
            ->assertSee('15.01');
    }
}
