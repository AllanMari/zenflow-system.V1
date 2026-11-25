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

class PdfAdvancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    protected function createAppointmentWithServices(int $count): Appointment
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Client',
            'email' => "client{$count}@example.com",
            'phone_number' => '09175551234',
        ]);
        $room = Room::create(['name' => 'Room A', 'status' => 'available', 'is_active' => true]);
        $category = ServiceCategory::create(['name' => 'Spa', 'is_active' => true]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'completed',
            'total_price' => $count * 500.00,
        ]);

        for ($i = 1; $i <= $count; $i++) {
            $service = Service::create([
                'category_id' => $category->id,
                'name' => "Thermal Service {$i}",
                'duration_minutes' => 60,
                'price' => 500.00,
                'is_active' => true,
            ]);
            $appointment->services()->attach($service->id, [
                'service_name' => $service->name,
                'price_at_booking' => 500.00,
                'is_extra' => false,
            ]);
        }

        Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => $count * 500.00,
            'payment_method' => 'cash',
            'type' => 'full',
            'paid_at' => now(),
        ]);

        return $appointment;
    }

    public function test_two_pass_thermal_receipt_adapts_page_height_to_content_length(): void
    {
        $admin = $this->createUserWithRole('admin');
        $shortAppt = $this->createAppointmentWithServices(1);
        $longAppt = $this->createAppointmentWithServices(18);

        $shortRes = $this->actingAs($admin)->get(
            route('admin.invoice.preview', ['appointment' => $shortAppt, 'pdf_preset' => 'thermal_80'])
        );
        $longRes = $this->actingAs($admin)->get(
            route('admin.invoice.preview', ['appointment' => $longAppt, 'pdf_preset' => 'thermal_80'])
        );

        $shortRes->assertStatus(200);
        $longRes->assertStatus(200);

        [$sw, $sh] = $this->extractMediaBox($shortRes->getContent());
        [$lw, $lh] = $this->extractMediaBox($longRes->getContent());

        // 80mm width = 226.77pt
        $this->assertEqualsWithDelta(226.77, $sw, 0.8);
        $this->assertEqualsWithDelta(226.77, $lw, 0.8);
        // Long receipt must produce a measurably taller roll page than short receipt
        $this->assertGreaterThan($sh + 100.0, $lh);
    }

    public function test_all_nine_report_endpoints_return_valid_pdf_with_expected_default_dimensions(): void
    {
        $admin = $this->createUserWithRole('admin');
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staffUser = User::factory()->create(['is_active' => true]);
        $staffUser->roles()->attach($staffRole);

        $appt = $this->createAppointmentWithServices(2);

        $endpoints = [
            [route('admin.invoice.preview', ['appointment' => $appt]), 283.46, 425.20],
            [route('admin.sales.daily-report-pdf', ['action' => 'stream']), 595.28, 841.89],
            [route('admin.sales.business-report-pdf', ['action' => 'stream']), 841.89, 595.28],
            [route('admin.appointments.report-pdf', ['action' => 'stream']), 841.89, 595.28],
            [route('attendance.report-pdf', ['action' => 'stream']), 841.89, 595.28],
            [route('admin.rooms.report-pdf', ['action' => 'stream']), 595.28, 841.89],
            [route('admin.service-popularity.pdf', ['action' => 'stream']), 595.28, 841.89],
            [route('admin.staff-utilization.pdf', ['action' => 'stream']), 841.89, 595.28],
            [route('admin.schedules.print'), 841.89, 595.28],
        ];

        foreach ($endpoints as [$url, $expectedW, $expectedH]) {
            $res = $this->actingAs($admin)->get($url);
            $res->assertStatus(200);
            $bytes = $res->getContent();
            $this->assertStringStartsWith('%PDF-', $bytes, $url);
            [$w, $h] = $this->extractMediaBox($bytes);
            $this->assertEqualsWithDelta($expectedW, $w, 0.8, "{$url} width");
            $this->assertEqualsWithDelta($expectedH, $h, 0.8, "{$url} height");
        }
    }

    public function test_multipage_appointment_report_renders_with_page_numbers_and_footer_text(): void
    {
        $admin = $this->createUserWithRole('admin');
        $customer = Customer::create([
            'first_name' => 'Bulk',
            'last_name' => 'Customer',
            'email' => 'bulk@example.com',
            'phone_number' => '09170009999',
        ]);

        for ($i = 0; $i < 35; $i++) {
            Appointment::create([
                'customer_id' => $customer->id,
                'appointment_date' => Carbon::today()->toDateString(),
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'status' => 'completed',
                'total_price' => 500.00,
            ]);
        }

        $res = $this->actingAs($admin)->get(
            route('admin.appointments.report-pdf', [
                'action' => 'stream',
                'pdf_preset' => 'wide_report',
                'pdf_show_page_numbers' => '1',
                'pdf_footer_text' => 'ZenFlow Multi-Page Check',
                'pdf_margin_bottom' => 14,
            ])
        );

        $res->assertStatus(200);
        $bytes = $res->getContent();
        preg_match_all('/\/Type\s*\/Page\b(?!s)/', $bytes, $pages);
        $this->assertGreaterThan(1, count($pages[0]));
    }

    protected function extractMediaBox(string $pdfBytes): array
    {
        preg_match('/\/MediaBox\s*\[\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdfBytes, $m);
        $this->assertCount(5, $m, 'MediaBox not found in PDF output.');

        return [(float) $m[3], (float) $m[4]];
    }
}
