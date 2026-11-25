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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentInvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    protected function createCompletedAppointment(int $serviceCount = 2): Appointment
    {
        $customer = Customer::create([
            'first_name' => 'Elena',
            'last_name' => 'Reyes',
            'email' => 'elena@example.com',
            'phone_number' => '09171112222',
        ]);

        $room = Room::create([
            'name' => 'Suite 1',
            'status' => 'available',
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'status' => 'completed',
            'total_price' => $serviceCount * 500.00,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Massages',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= $serviceCount; $i++) {
            $service = Service::create([
                'category_id' => $category->id,
                'name' => "Signature Therapy {$i}",
                'duration_minutes' => 60,
                'price' => 500.00,
                'is_active' => true,
            ]);
            $appointment->services()->attach($service->id, [
                'service_name' => $service->name,
                'price_at_booking' => 500.00,
                'is_extra' => $i > 1,
            ]);
        }

        Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => $serviceCount * 500.00,
            'payment_method' => 'cash',
            'type' => 'full',
            'paid_at' => now(),
        ]);

        return $appointment;
    }

    public function test_appointment_invoice_presets_produce_exact_expected_dimensions(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($role);

        $appointment = $this->createCompletedAppointment(2);

        $cases = [
            // Default: compact 100 x 150 mm (283.46 x 425.20 pt)
            [[], 283.46, 425.20],
            [['pdf_preset' => 'compact_100x150'], 283.46, 425.20],
            // Receipt on A4: A4 page (595.28 x 841.89 pt) with 90mm content frame
            [['pdf_preset' => 'receipt_on_a4', 'pdf_frame_width' => 90, 'pdf_frame_alignment' => 'top-center'], 595.28, 841.89],
            // A5 Portrait (148 x 210 mm = 419.53 x 595.28 pt)
            [['pdf_preset' => 'a5_portrait'], 419.53, 595.28],
            // A4 Standard (210 x 297 mm = 595.28 x 841.89 pt)
            [['pdf_preset' => 'standard_report'], 595.28, 841.89],
            // Thermal 80mm & 58mm (width fixed, height dynamically fitted via two-pass rendering)
            [['pdf_preset' => 'thermal_80'], 226.77, null],
            [['pdf_preset' => 'thermal_58'], 164.41, null],
            // Custom 12cm x 18cm (340.16 x 510.24 pt)
            [['pdf_preset' => 'custom', 'pdf_paper' => 'custom', 'pdf_unit' => 'cm', 'pdf_width' => 12, 'pdf_height' => 18, 'pdf_orientation' => 'portrait'], 340.16, 510.24],
        ];

        foreach ($cases as [$params, $expectedW, $expectedH]) {
            $response = $this->actingAs($admin)->get(
                route('admin.invoice.preview', array_merge(['appointment' => $appointment], $params))
            );
            $response->assertStatus(200);
            $bytes = $response->getContent();
            $this->assertStringStartsWith('%PDF-', $bytes);
            preg_match('/\/MediaBox\s*\[\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\]/', $bytes, $m);
            $this->assertCount(5, $m);
            $this->assertEqualsWithDelta($expectedW, (float) $m[3], 0.8);
            if ($expectedH !== null) {
                $this->assertEqualsWithDelta($expectedH, (float) $m[4], 0.8);
            } else {
                $this->assertGreaterThanOrEqual(226.0, (float) $m[4]);
                $this->assertLessThan(841.89, (float) $m[4]);
            }
        }
    }

    public function test_long_compact_invoice_paginates_cleanly_without_losing_financial_data(): void
    {
        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($role);

        $appointment = $this->createCompletedAppointment(18);

        $html = view('reports.appointment-invoice-pdf', [
            'appointment' => $appointment->load(['customer', 'room', 'services', 'payments']),
            'customer' => $appointment->customer,
            'clientName' => 'Elena Reyes',
            'clientPhone' => '09171112222',
            'staff' => null,
            'assignedStaff' => collect([]),
            'staffNames' => 'Therapist A',
            'room' => $appointment->room,
            'services' => $appointment->services,
            'payments' => $appointment->payments,
            'paidPayments' => $appointment->payments,
            'refundPayments' => collect([]),
            'total' => 9000.0,
            'originalTotal' => 9000.0,
            'hasDiscount' => false,
            'discountType' => null,
            'discountLabel' => null,
            'shortDiscountBadge' => null,
            'discountPercent' => 0.0,
            'discountAmount' => 0.0,
            'totalPaid' => 9000.0,
            'totalRefunded' => 0.0,
            'netPaid' => 9000.0,
            'balance' => 0.0,
            'paymentMethods' => collect(['cash']),
            'referenceNumber' => '#SPA-' . $appointment->id,
            'reportTitle' => 'APPOINTMENT INVOICE',
            'dateRange' => null,
            'preparedBy' => 'Receptionist',
            'generatedAt' => now('Asia/Manila'),
            'pdfLayout' => app(\App\Support\Pdf\PdfLayoutResolver::class)->resolve('appointment_invoice', ['preset' => 'receipt_on_a4']),
        ])->render();

        $this->assertStringContainsString('pdf-content-frame', $html);
        $this->assertStringContainsString('₱9,000.00', $html);
        $this->assertStringContainsString('Signature Therapy 18', $html);

        $response = $this->actingAs($receptionist)->get(
            route('receptionist.invoice.download', ['appointment' => $appointment, 'pdf_preset' => 'compact_100x150'])
        );
        $response->assertStatus(200);
        $bytes = $response->getContent();
        preg_match_all('/\/Type\s*\/Page\b(?!s)/', $bytes, $pages);
        $this->assertGreaterThan(1, count($pages[0]));
    }
}
