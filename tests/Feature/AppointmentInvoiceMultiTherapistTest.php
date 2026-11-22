<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ServiceCategory;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentInvoiceMultiTherapistTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $receptionist;
    protected User $therapist1;
    protected User $therapist2;
    protected Room $room;
    protected Service $coupleService;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        $this->admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'is_active' => true,
        ]);
        $this->admin->roles()->attach($adminRole);

        $this->receptionist = User::factory()->create([
            'first_name' => 'Reception',
            'last_name' => 'User',
            'is_active' => true,
        ]);
        $this->receptionist->roles()->attach($receptionistRole);

        $this->therapist1 = User::factory()->create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'is_active' => true,
        ]);
        $this->therapist1->roles()->attach($staffRole);

        $this->therapist2 = User::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'is_active' => true,
        ]);
        $this->therapist2->roles()->attach($staffRole);

        $category = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);

        $this->room = Room::create([
            'name' => 'Couples Suite 1',
            'capacity' => 2,
            'is_active' => true,
        ]);

        $this->coupleService = Service::create([
            'category_id' => $category->id,
            'name' => 'Signature Couple Massage',
            'price' => 3500.00,
            'duration' => 90,
            'therapists_required' => 2,
            'is_active' => true,
        ]);
    }

    public function test_invoice_list_and_details_show_both_therapists_and_room(): void
    {
        $customer = Customer::create([
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'phone_number' => '09171234567',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $this->therapist1->id,
            'room_id' => $this->room->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '14:00',
            'end_time' => '15:30',
            'total_price' => 3500.00,
            'status' => 'completed',
        ]);
        $appointment->services()->attach($this->coupleService->id, [
            'price_at_booking' => 3500.00,
            'service_name' => $this->coupleService->name,
            'service_duration' => 90,
            'is_extra' => false,
        ]);

        // Assign both therapists via syncStaff
        $appointment->syncStaff($this->therapist1->id, [$this->therapist2->id]);

        // 1. Test Invoice List Table shows both therapists
        $listResponse = $this->actingAs($this->receptionist)->get(route('receptionist.appointment-invoice'));
        $listResponse->assertOk();
        $listResponse->assertSee('Maria Santos');
        $listResponse->assertSee('Juan Dela Cruz');

        // 2. Test Individual Web Invoice shows both therapists and room
        $detailResponse = $this->actingAs($this->receptionist)->get(route('receptionist.invoice', $appointment));
        $detailResponse->assertOk();
        $detailResponse->assertSee('Maria Santos');
        $detailResponse->assertSee('Juan Dela Cruz');
        $detailResponse->assertSee('Couples Suite 1');
        $detailResponse->assertSee('Assigned Therapists');

        // 3. Test PDF Preview generates successfully with 200 and PDF Content-Type
        $pdfResponse = $this->actingAs($this->receptionist)->get(route('receptionist.invoice.preview', $appointment));
        $pdfResponse->assertOk();
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('content-type'));

        // 4. Test PDF template contains both therapists and room
        $pdfView = $this->view('reports.appointment-invoice-pdf', [
            'appointment' => $appointment->load(['customer', 'staff', 'assignedStaff', 'room', 'services', 'payments']),
            'customer' => $customer,
            'clientName' => $appointment->client_name,
            'clientPhone' => $appointment->client_phone,
            'staff' => $appointment->staff,
            'assignedStaff' => $appointment->assignedStaff,
            'staffNames' => 'Maria Santos, Juan Dela Cruz',
            'room' => $this->room,
            'services' => $appointment->services,
            'payments' => collect(),
            'paidPayments' => collect(),
            'refundPayments' => collect(),
            'total' => 3500.00,
            'totalPaid' => 3500.00,
            'totalRefunded' => 0.00,
            'netPaid' => 3500.00,
            'balance' => 0.00,
            'paymentMethods' => collect(['cash']),
            'referenceNumber' => '#SPA-' . $appointment->id,
            'reportTitle' => 'APPOINTMENT INVOICE',
            'dateRange' => null,
            'preparedBy' => 'Admin User',
            'generatedAt' => now('Asia/Manila'),
        ]);

        $pdfView->assertSee('Maria Santos, Juan Dela Cruz');
        $pdfView->assertSee('Couples Suite 1');
        $pdfView->assertSee('Assigned Therapists');
    }
}
