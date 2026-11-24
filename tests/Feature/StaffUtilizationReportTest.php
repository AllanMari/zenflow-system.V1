<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\SalesAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class StaffUtilizationReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $receptionist;

    protected User $therapist;

    protected User $secondTherapist;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $admin = User::firstOrCreate(
            ['username' => 'test_admin_staff_utilization'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Test',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        $this->admin = $admin;

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist'], ['label' => 'Receptionist']);
        $receptionist = User::firstOrCreate(
            ['username' => 'test_rec_staff_utilization'],
            [
                'first_name' => 'Rec',
                'last_name' => 'Test',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $receptionist->roles()->syncWithoutDetaching([$receptionistRole->id]);
        $this->receptionist = $receptionist;

        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['label' => 'Staff']);
        $this->therapist = User::factory()->create([
            'first_name' => 'Maya',
            'last_name' => 'Therapist',
            'is_active' => true,
        ]);
        $this->therapist->roles()->attach($staffRole);

        $this->secondTherapist = User::factory()->create([
            'first_name' => 'Ben',
            'last_name' => 'Therapist',
            'is_active' => true,
        ]);
        $this->secondTherapist->roles()->attach($staffRole);

        $customerRole = Role::firstOrCreate(['name' => 'customer'], ['label' => 'Customer']);
        $customerUser = User::factory()->create();
        $customerUser->roles()->attach($customerRole);

        $this->customer = Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone_number' => '09123456789',
            'customer_type' => 'regular',
        ]);
    }

    // =========================================================================
    // Access control
    // =========================================================================

    public function test_admin_can_open_staff_utilization_report_page()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.staff-utilization'));

        $response->assertStatus(200);
        $response->assertSee('Staff Utilization');
    }

    public function test_receptionist_can_open_receptionist_staff_utilization_report_page()
    {
        $response = $this->actingAs($this->receptionist)->get(route('receptionist.staff-utilization'));

        $response->assertStatus(200);
        $response->assertSee('Staff Utilization');
    }

    public function test_receptionist_cannot_access_admin_staff_utilization_report()
    {
        $this->actingAs($this->receptionist)
            ->get(route('admin.staff-utilization'))
            ->assertStatus(403);

        $this->actingAs($this->receptionist)
            ->get(route('admin.staff-utilization.pdf'))
            ->assertStatus(403);
    }

    public function test_customer_and_staff_cannot_access_staff_utilization_reports()
    {
        $customerRole = Role::firstOrCreate(['name' => 'customer'], ['label' => 'Customer']);
        $customer = User::factory()->create();
        $customer->roles()->attach($customerRole);

        $this->actingAs($customer)
            ->get(route('admin.staff-utilization'))
            ->assertStatus(403);
        $this->actingAs($customer)
            ->get(route('admin.staff-utilization.pdf'))
            ->assertStatus(403);

        $this->actingAs($this->therapist)
            ->get(route('admin.staff-utilization'))
            ->assertStatus(403);
        $this->actingAs($this->therapist)
            ->get(route('admin.staff-utilization.pdf'))
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_is_redirected_to_login_for_staff_utilization_reports()
    {
        $this->get(route('admin.staff-utilization'))->assertRedirect(route('login'));
        $this->get(route('admin.staff-utilization.pdf'))->assertRedirect(route('login'));
    }

    // =========================================================================
    // PDF preview and download
    // =========================================================================

    public function test_staff_utilization_pdf_stream_returns_inline_pdf()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.staff-utilization.pdf') . '?action=stream');

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'inline',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_staff_utilization_pdf_download_returns_attachment_pdf()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.staff-utilization.pdf') . '?action=download');

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('content-disposition')
        );
    }

    // =========================================================================
    // Calculations
    // =========================================================================

    public function test_staff_utilization_report_calculates_service_minutes_and_clocked_minutes()
    {
        $appointment = $this->createCompletedAppointment($this->therapist, '13:00:00', '14:00:00', 800);
        $this->createCompletedPayment($appointment, 800);
        $this->createAttendance($this->therapist, '13:00:00', '14:00:00');

        $data = $this->staffUtilizationData();

        $therapistRecord = collect($data['staff'])->firstWhere('id', $this->therapist->id);

        $this->assertNotNull($therapistRecord);
        $this->assertEquals(60, $therapistRecord['service_minutes']);
        $this->assertEquals(60, $therapistRecord['clocked_minutes']);
        $this->assertEquals(100.0, (float) $therapistRecord['utilization']);
    }

    public function test_staff_with_appointments_but_no_attendance_is_included_with_zero_utilization()
    {
        $appointment = $this->createCompletedAppointment($this->therapist, '13:00:00', '14:00:00', 800);

        $data = $this->staffUtilizationData();

        $therapistRecord = collect($data['staff'])->firstWhere('id', $this->therapist->id);

        $this->assertNotNull($therapistRecord);
        $this->assertEquals(1, (int) $therapistRecord['appointments']);
        $this->assertEquals(1, (int) $therapistRecord['completed']);
        $this->assertEquals(60, $therapistRecord['service_minutes']);
        $this->assertEquals(0, $therapistRecord['clocked_minutes']);
        $this->assertEquals(0.0, (float) $therapistRecord['utilization']);
        $this->assertFalse((bool) ($therapistRecord['has_attendance'] ?? false));
    }

    public function test_staff_with_attendance_but_no_appointments_is_included_in_report()
    {
        $this->createAttendance($this->secondTherapist, '09:00:00', '10:00:00');

        $data = $this->staffUtilizationData();

        $secondTherapistRecord = collect($data['staff'])->firstWhere('id', $this->secondTherapist->id);

        $this->assertNotNull($secondTherapistRecord);
        $this->assertEquals(0, (int) ($secondTherapistRecord['appointments'] ?? 0));
        $this->assertEquals(60, (int) $secondTherapistRecord['clocked_minutes']);
        $this->assertEquals(0, (int) $secondTherapistRecord['service_minutes']);
        $this->assertEquals(0.0, (float) ($secondTherapistRecord['utilization'] ?? 0));
    }

    public function test_overall_utilization_uses_aggregate_minutes_not_average_of_staff()
    {
        // Therapist A: 60 service minutes against 120 clocked minutes -> 50% utilization.
        $appointmentA = $this->createCompletedAppointment($this->therapist, '13:00:00', '14:00:00', 800);
        $this->createCompletedPayment($appointmentA, 800);
        $this->createAttendance($this->therapist, '13:00:00', '15:00:00');

        // Therapist B: 30 service minutes against 30 clocked minutes -> 100% utilization.
        $appointmentB = $this->createCompletedAppointment($this->secondTherapist, '15:00:00', '15:30:00', 800);
        $this->createCompletedPayment($appointmentB, 800);
        $this->createAttendance($this->secondTherapist, '15:00:00', '15:30:00');

        $data = $this->staffUtilizationData();
        $summary = $data['summary'];

        // Aggregate: (60 + 30) service minutes / (120 + 30) clocked minutes * 100 = 60%.
        // A simple average of the individual percentages would incorrectly produce 75%.
        $this->assertEquals(60.0, (float) $summary['overall_utilization']);
    }

    public function test_staff_utilization_revenue_is_split_among_assigned_therapists_without_losing_cents()
    {
        $serviceCategory = ServiceCategory::firstOrCreate(['name' => 'Massage'], ['is_active' => true]);
        $service = Service::create([
            'category_id' => $serviceCategory->id,
            'name' => 'Couples Massage',
            'duration_minutes' => 60,
            'price' => 3000,
            'therapists_required' => 2,
            'participants_required' => 2,
            'requires_room' => false,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->therapist->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'status' => 'completed',
            'total_price' => 3000,
            'client_name' => $this->customer->full_name,
            'client_phone' => $this->customer->phone_number,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => 3000]);
        $appointment->syncStaff($this->therapist->id, [$this->secondTherapist->id]);

        $this->createCompletedPayment($appointment, 3000);

        $data = $this->staffUtilizationData();

        $primaryRevenue = collect($data['staff'])->firstWhere('id', $this->therapist->id)['revenue'];
        $assistingRevenue = collect($data['staff'])->firstWhere('id', $this->secondTherapist->id)['revenue'];

        $this->assertEquals(1500.0, (float) $primaryRevenue);
        $this->assertEquals(1500.0, (float) $assistingRevenue);
    }

    // =========================================================================
    // Sales report cleanup
    // // =========================================================================

    public function test_sales_report_no_longer_includes_staff_utilization_section()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.sales'));

        $response->assertStatus(200);
        $response->assertDontSee('staff-efficiency');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function staffUtilizationData(): array
    {
        $service = app(SalesAnalyticsService::class);

        return $service->getStaffUtilizationReport(new Request(['period' => 'daily']));
    }

    private function createCompletedAppointment(
        User $therapist,
        string $startTime,
        string $endTime,
        float $price
    ): Appointment {
        $serviceCategory = ServiceCategory::firstOrCreate(['name' => 'Massage'], ['is_active' => true]);
        $service = Service::create([
            'category_id' => $serviceCategory->id,
            'name' => 'Swedish Massage',
            'duration_minutes' => (int) Carbon::parse($startTime)->diffInMinutes(Carbon::parse($endTime)),
            'price' => $price,
            'requires_room' => false,
            'requires_prepayment' => false,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'user_id' => $therapist->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'completed',
            'total_price' => $price,
            'client_name' => $this->customer->full_name,
            'client_phone' => $this->customer->phone_number,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => $price]);

        return $appointment;
    }

    private function createCompletedPayment(Appointment $appointment, float $amount): Payment
    {
        return Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => $amount,
            'type' => 'full',
            'payment_method' => 'cash',
            'paid_at' => Carbon::now(),
        ]);
    }

    private function createAttendance(User $therapist, string $checkIn, string $checkOut): Attendance
    {
        return Attendance::create([
            'user_id' => $therapist->id,
            'date' => Carbon::today()->toDateString(),
            'status' => 'present',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ]);
    }
}
