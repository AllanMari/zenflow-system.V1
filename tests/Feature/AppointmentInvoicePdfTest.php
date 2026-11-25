<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentPackageSelection;
use App\Models\Customer;
use App\Models\PackageChoiceGroup;
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

    public function test_compact_invoice_margins_produce_distinct_content_insets_in_generated_pdfs(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($role);

        $appointment = $this->createCompletedAppointment(2);

        $marginCases = [
            'narrow' => 5.0,
            'normal' => 9.26,
            'wide' => 15.0,
        ];

        $measuredLeftPts = [];

        foreach ($marginCases as $label => $mm) {
            $params = [
                'appointment' => $appointment,
                'pdf_preset' => 'compact_100x150',
                'pdf_paper' => 'compact_100x150',
                'pdf_orientation' => 'portrait',
                'pdf_margin_top' => $mm,
                'pdf_margin_right' => $mm,
                'pdf_margin_bottom' => $mm,
                'pdf_margin_left' => $mm,
            ];

            $previewResp = $this->actingAs($admin)->get(route('admin.invoice.preview', $params));
            $downloadResp = $this->actingAs($admin)->get(route('admin.invoice.download', $params));

            $previewResp->assertStatus(200);
            $downloadResp->assertStatus(200);

            foreach ([$previewResp->getContent(), $downloadResp->getContent()] as $pdfBytes) {
                $this->assertStringStartsWith('%PDF-', $pdfBytes);
                preg_match('/\/MediaBox\s*\[\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdfBytes, $mb);
                $this->assertCount(5, $mb, "Missing MediaBox for {$label}");
                $this->assertEqualsWithDelta(283.46, (float) $mb[3], 0.5);
                $this->assertEqualsWithDelta(425.20, (float) $mb[4], 0.5);
            }

            $leftPt = $this->extractMinContentXPt($previewResp->getContent());
            $downloadLeftPt = $this->extractMinContentXPt($downloadResp->getContent());

            $this->assertNotNull($leftPt, "Unable to decode PDF stream coordinates for {$label}");
            $this->assertEqualsWithDelta($leftPt, $downloadLeftPt, 0.1, "Preview/download mismatch for {$label}");
            $this->assertEqualsWithDelta($mm * (72.0 / 25.4), $leftPt, 0.5, "Left margin inset mismatch for {$label}");

            $measuredLeftPts[$label] = $leftPt;
        }

        $this->assertLessThan($measuredLeftPts['normal'], $measuredLeftPts['narrow']);
        $this->assertLessThan($measuredLeftPts['wide'], $measuredLeftPts['normal']);
    }

    protected function extractMinContentXPt(string $pdfBytes): ?float
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdfBytes, $streams);
        $xs = [];

        foreach ($streams[1] as $raw) {
            $decoded = @gzuncompress($raw);
            if ($decoded === false) {
                continue;
            }
            if (preg_match_all('/([\d.-]+)\s+([\d.-]+)\s+([\d.-]+)\s+([\d.-]+)\s+re\b/', $decoded, $rects, PREG_SET_ORDER)) {
                foreach ($rects as $r) {
                    if ((float) $r[3] > 20.0 && (float) $r[1] >= 0.0) {
                        $xs[] = (float) $r[1];
                    }
                }
            }
            if (preg_match_all('/1\s+0\s+0\s+1\s+([\d.-]+)\s+([\d.-]+)\s+Tm\b/', $decoded, $tms, PREG_SET_ORDER)) {
                foreach ($tms as $t) {
                    $xs[] = (float) $t[1];
                }
            }
        }

        return $xs !== [] ? min($xs) : null;
    }

    public function test_invoice_renders_package_only_package_plus_service_multiple_packages_and_empty_appointments(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($role);

        $customer = Customer::create([
            'first_name' => 'Clara',
            'last_name' => 'Mendoza',
            'email' => 'clara@example.com',
            'phone_number' => '09172223333',
        ]);

        $room = Room::create([
            'name' => 'Package Suite',
            'status' => 'available',
            'is_active' => true,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Packages & Rituals',
            'is_active' => true,
        ]);

        $inc1 = Service::create([
            'category_id' => $category->id,
            'name' => 'Swedish Body Scrub',
            'duration_minutes' => 45,
            'price' => 900.00,
            'is_active' => true,
        ]);
        $inc2 = Service::create([
            'category_id' => $category->id,
            'name' => 'Aromatherapy Massage',
            'duration_minutes' => 60,
            'price' => 1200.00,
            'is_active' => true,
        ]);
        $extraSvc = Service::create([
            'category_id' => $category->id,
            'name' => 'Express Facial Add-On',
            'duration_minutes' => 30,
            'price' => 650.00,
            'is_active' => true,
        ]);

        $package1 = Service::create([
            'category_id' => $category->id,
            'name' => 'Zen Sanctuary Package',
            'duration_minutes' => 105,
            'price' => 1800.00,
            'is_package' => true,
            'included_services' => [$inc1->id, $inc2->id],
            'is_active' => true,
        ]);

        $package2 = Service::create([
            'category_id' => $category->id,
            'name' => 'Royal Renewal Package',
            'duration_minutes' => 90,
            'price' => 2400.00,
            'is_package' => true,
            'included_services' => [$inc2->id],
            'is_active' => true,
        ]);

        $choiceGroup = PackageChoiceGroup::create([
            'service_id' => $package1->id,
            'label' => 'Tea Pairing',
            'is_required' => false,
            'max_selections' => 1,
        ]);

        // 1. Package-only appointment (with included_services + packageSelections)
        $pkgOnlyAppt = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:45:00',
            'status' => 'completed',
            'total_price' => 1800.00,
        ]);
        $pkgOnlyAppt->services()->attach($package1->id, [
            'service_name' => $package1->name,
            'price_at_booking' => 1800.00,
            'is_extra' => false,
        ]);
        AppointmentPackageSelection::create([
            'appointment_id' => $pkgOnlyAppt->id,
            'choice_group_id' => $choiceGroup->id,
            'selected_service_id' => $inc1->id,
            'group_label_snapshot' => 'Tea Pairing',
            'selected_service_name_snapshot' => 'Ginger Lemongrass',
        ]);
        Payment::create([
            'appointment_id' => $pkgOnlyAppt->id,
            'amount' => 1800.00,
            'payment_method' => 'cash',
            'type' => 'full',
            'paid_at' => now(),
        ]);

        $pkgOnlyResp = $this->actingAs($admin)->get(route('admin.invoice', $pkgOnlyAppt));
        $pkgOnlyResp->assertStatus(200);

        $pkgOnlyHtml = view('reports.appointment-invoice-pdf', $this->buildControllerInvoiceData($pkgOnlyAppt))->render();
        $this->assertStringContainsString('Zen Sanctuary Package', $pkgOnlyHtml);
        $this->assertStringContainsString('[Package]', $pkgOnlyHtml);
        $this->assertStringContainsString('Includes: Swedish Body Scrub, Aromatherapy Massage', $pkgOnlyHtml);
        $this->assertStringContainsString('Selected: Tea Pairing: Ginger Lemongrass', $pkgOnlyHtml);
        $this->assertStringContainsString('₱1,800.00', $pkgOnlyHtml);
        $this->assertStringNotContainsString('No service records found.', $pkgOnlyHtml);
        // Bundled services must not be billed separately at their standalone catalog prices
        $this->assertStringNotContainsString('₱900.00', $pkgOnlyHtml);
        $this->assertStringNotContainsString('₱1,200.00', $pkgOnlyHtml);

        $pkgOnlyPdf = $this->actingAs($admin)->get(route('admin.invoice.preview', $pkgOnlyAppt));
        $pkgOnlyPdf->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pkgOnlyPdf->getContent());

        // 2. Individual-service-only appointment
        $svcOnlyAppt = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '12:30:00',
            'status' => 'completed',
            'total_price' => 650.00,
        ]);
        $svcOnlyAppt->services()->attach($extraSvc->id, [
            'service_name' => $extraSvc->name,
            'price_at_booking' => 650.00,
            'is_extra' => false,
        ]);
        $svcOnlyHtml = view('reports.appointment-invoice-pdf', $this->buildControllerInvoiceData($svcOnlyAppt))->render();
        $this->assertStringContainsString('Express Facial Add-On', $svcOnlyHtml);
        $this->assertStringContainsString('₱650.00', $svcOnlyHtml);
        $this->assertStringNotContainsString('[Package]', $svcOnlyHtml);

        // 3. Package + additional individual service
        $comboAppt = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:15:00',
            'status' => 'completed',
            'total_price' => 2450.00,
        ]);
        $comboAppt->services()->attach($package1->id, [
            'service_name' => $package1->name,
            'price_at_booking' => 1800.00,
            'is_extra' => false,
        ]);
        $comboAppt->services()->attach($extraSvc->id, [
            'service_name' => $extraSvc->name,
            'price_at_booking' => 650.00,
            'is_extra' => true,
        ]);
        $comboHtml = view('reports.appointment-invoice-pdf', $this->buildControllerInvoiceData($comboAppt))->render();
        $this->assertStringContainsString('Zen Sanctuary Package', $comboHtml);
        $this->assertStringContainsString('Express Facial Add-On', $comboHtml);
        $this->assertStringContainsString('[Extra]', $comboHtml);
        $this->assertStringContainsString('₱2,450.00', $comboHtml);
        $this->assertStringNotContainsString('₱900.00', $comboHtml);

        // 4. Multiple packages on one appointment
        $multiPkgAppt = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '15:30:00',
            'end_time' => '18:45:00',
            'status' => 'completed',
            'total_price' => 4200.00,
        ]);
        $multiPkgAppt->services()->attach($package1->id, [
            'service_name' => $package1->name,
            'price_at_booking' => 1800.00,
            'is_extra' => false,
        ]);
        $multiPkgAppt->services()->attach($package2->id, [
            'service_name' => $package2->name,
            'price_at_booking' => 2400.00,
            'is_extra' => false,
        ]);
        $multiPkgHtml = view('reports.appointment-invoice-pdf', $this->buildControllerInvoiceData($multiPkgAppt))->render();
        $this->assertStringContainsString('Zen Sanctuary Package', $multiPkgHtml);
        $this->assertStringContainsString('Royal Renewal Package', $multiPkgHtml);
        $this->assertStringContainsString('₱4,200.00', $multiPkgHtml);

        // 5. Empty appointment (neither services nor packages)
        $emptyAppt = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
            'status' => 'completed',
            'total_price' => 0.00,
        ]);
        $emptyHtml = view('reports.appointment-invoice-pdf', $this->buildControllerInvoiceData($emptyAppt))->render();
        $this->assertStringContainsString('No service records found.', $emptyHtml);
    }

    public function test_invoice_displays_all_assigned_therapists_for_duo_four_hands_and_multi_service_without_duplicates(): void
    {
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $therapists = collect(['Alpha One', 'Beta Two', 'Gamma Three', 'Delta Four'])->map(function ($name, $idx) use ($staffRole) {
            [$first, $last] = explode(' ', $name, 2);
            $user = User::factory()->create([
                'first_name' => $first,
                'last_name' => $last,
            ]);
            $user->roles()->attach($staffRole);

            return $user;
        });

        $customer = Customer::create([
            'first_name' => 'Marco',
            'last_name' => 'Cruz',
            'email' => 'marco@example.com',
            'phone_number' => '09173334444',
        ]);

        $room = Room::create([
            'name' => 'Royal Suite',
            'status' => 'available',
            'is_active' => true,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Specialty',
            'is_active' => true,
        ]);

        $duoService = Service::create([
            'category_id' => $category->id,
            'name' => 'Couples Duo Escape',
            'duration_minutes' => 90,
            'price' => 3000.00,
            'participants_required' => 2,
            'therapists_required' => 2,
            'is_active' => true,
        ]);

        $fourHandsService = Service::create([
            'category_id' => $category->id,
            'name' => 'Royal Four-Hands Ritual',
            'duration_minutes' => 90,
            'price' => 4500.00,
            'participants_required' => 1,
            'therapists_required' => 4,
            'is_active' => true,
        ]);

        // Duo appointment with 2 assigned therapists
        $duoAppt = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $therapists[0]->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'status' => 'completed',
            'total_price' => 3000.00,
        ]);
        $duoAppt->services()->attach($duoService->id, [
            'service_name' => $duoService->name,
            'price_at_booking' => 3000.00,
            'is_extra' => false,
        ]);
        $duoAppt->syncStaff($therapists[0]->id, [$therapists[1]->id]);

        $duoHtml = view('reports.appointment-invoice-pdf', $this->buildControllerInvoiceData($duoAppt))->render();
        $this->assertStringContainsString('Assigned Therapists', $duoHtml);
        $this->assertStringContainsString('Alpha One, Beta Two', $duoHtml);
        $this->assertStringContainsString('[Duo · 2 Guests · 2 Therapists]', $duoHtml);

        // Four-Hands appointment with 4 assigned therapists
        $fourHandsAppt = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $therapists[0]->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '14:30:00',
            'status' => 'completed',
            'total_price' => 4500.00,
        ]);
        $fourHandsAppt->services()->attach($fourHandsService->id, [
            'service_name' => $fourHandsService->name,
            'price_at_booking' => 4500.00,
            'is_extra' => false,
        ]);
        $fourHandsAppt->syncStaff($therapists[0]->id, [$therapists[1]->id, $therapists[2]->id, $therapists[3]->id]);

        $fourHandsHtml = view('reports.appointment-invoice-pdf', $this->buildControllerInvoiceData($fourHandsAppt))->render();
        $this->assertStringContainsString('Assigned Therapists', $fourHandsHtml);
        $this->assertStringContainsString('Alpha One, Beta Two, Gamma Three, Delta Four', $fourHandsHtml);
        $this->assertStringContainsString('[Four-Hands · 4 Therapists]', $fourHandsHtml);

        // Multi-service appointment with mixed therapist requirements + no duplicate therapist names
        $multiSvcAppt = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $therapists[0]->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '15:00:00',
            'end_time' => '18:00:00',
            'status' => 'completed',
            'total_price' => 7500.00,
        ]);
        $multiSvcAppt->services()->attach($duoService->id, [
            'service_name' => $duoService->name,
            'price_at_booking' => 3000.00,
            'is_extra' => false,
        ]);
        $multiSvcAppt->services()->attach($fourHandsService->id, [
            'service_name' => $fourHandsService->name,
            'price_at_booking' => 4500.00,
            'is_extra' => true,
        ]);
        $multiSvcAppt->syncStaff($therapists[0]->id, [$therapists[1]->id, $therapists[2]->id, $therapists[3]->id]);

        $multiSvcData = $this->buildControllerInvoiceData($multiSvcAppt);
        $this->assertSame('Alpha One, Beta Two, Gamma Three, Delta Four', $multiSvcData['staffNames']);
        $multiSvcHtml = view('reports.appointment-invoice-pdf', $multiSvcData)->render();
        $this->assertSame(1, substr_count($multiSvcHtml, 'Alpha One'));
        $this->assertSame(1, substr_count($multiSvcHtml, 'Delta Four'));
    }

    protected function buildControllerInvoiceData(Appointment $appointment): array
    {
        $controller = app(\App\Http\Controllers\AppointmentInvoiceController::class);
        $loadMethod = new \ReflectionMethod($controller, 'loadInvoiceAppointment');
        $loadMethod->setAccessible(true);
        $loaded = $loadMethod->invoke($controller, $appointment);

        $buildMethod = new \ReflectionMethod($controller, 'buildInvoiceData');
        $buildMethod->setAccessible(true);

        return $buildMethod->invoke($controller, $loaded);
    }
}
