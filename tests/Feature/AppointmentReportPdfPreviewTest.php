<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentReportPdfPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_admin_appointments_page_includes_shared_pdf_preview_modal(): void
    {
        $admin = $this->createUserWithRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.appointments'));

        $response->assertStatus(200);
        $response->assertSee('zenflowPdfPreviewModal', false);
        $response->assertSee('Preview PDF', false);
        $response->assertSee('Download PDF', false);
        $response->assertSee('appointment_report', false);
    }

    public function test_non_admin_cannot_access_appointment_report_pdf(): void
    {
        $receptionist = $this->createUserWithRole('receptionist');

        $this->actingAs($receptionist)
            ->get(route('admin.appointments.report-pdf', ['action' => 'stream']))
            ->assertStatus(403);
    }

    public function test_appointment_report_stream_uses_default_wide_landscape_preset_and_custom_overrides(): void
    {
        $admin = $this->createUserWithRole('admin');
        $customer = Customer::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria@example.com',
            'phone_number' => '09170001111',
        ]);

        Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 1200.00,
        ]);

        // 1. Default preview uses wide_report (A4 Landscape: 841.89 x 595.28 pt)
        $defaultResponse = $this->actingAs($admin)->get(
            route('admin.appointments.report-pdf', ['action' => 'stream'])
        );
        $defaultResponse->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $defaultResponse->headers->get('content-type'));
        $this->assertStringContainsString('inline;', (string) $defaultResponse->headers->get('content-disposition'));
        $defaultBytes = $defaultResponse->getContent();
        $this->assertStringStartsWith('%PDF-', $defaultBytes);
        [$w, $h] = $this->extractMediaBox($defaultBytes);
        $this->assertEqualsWithDelta(841.89, $w, 0.5);
        $this->assertEqualsWithDelta(595.28, $h, 0.5);

        // 2. Portrait override produces A4 Portrait (595.28 x 841.89 pt)
        $portraitResponse = $this->actingAs($admin)->get(
            route('admin.appointments.report-pdf', [
                'action' => 'stream',
                'pdf_preset' => 'standard_report',
                'pdf_paper' => 'a4',
                'pdf_orientation' => 'portrait',
            ])
        );
        $portraitResponse->assertStatus(200);
        [$pw, $ph] = $this->extractMediaBox($portraitResponse->getContent());
        $this->assertEqualsWithDelta(595.28, $pw, 0.5);
        $this->assertEqualsWithDelta(841.89, $ph, 0.5);

        // 3. Custom cm dimensions (15cm x 20cm -> 425.20 x 566.93 pt)
        $customResponse = $this->actingAs($admin)->get(
            route('admin.appointments.report-pdf', [
                'action' => 'stream',
                'pdf_preset' => 'custom',
                'pdf_paper' => 'custom',
                'pdf_unit' => 'cm',
                'pdf_width' => 15,
                'pdf_height' => 20,
                'pdf_orientation' => 'portrait',
                'pdf_margin_top' => 5,
                'pdf_margin_right' => 5,
                'pdf_margin_bottom' => 5,
                'pdf_margin_left' => 5,
                'pdf_scale' => 90,
            ])
        );
        $customResponse->assertStatus(200);
        [$cw, $ch] = $this->extractMediaBox($customResponse->getContent());
        $this->assertEqualsWithDelta(425.20, $cw, 0.5);
        $this->assertEqualsWithDelta(566.93, $ch, 0.5);
    }

    public function test_appointment_report_download_preserves_attachment_and_clamps_invalid_params(): void
    {
        $admin = $this->createUserWithRole('admin');

        $downloadResponse = $this->actingAs($admin)->get(
            route('admin.appointments.report-pdf', [
                'action' => 'download',
                'pdf_paper' => 'custom',
                'pdf_unit' => 'invalid_unit',
                'pdf_width' => 5, // clamped to 40mm = 113.39pt
                'pdf_height' => 9999, // clamped to 2000mm = 5669.29pt
                'pdf_scale' => 999, // clamped to 150
            ])
        );

        $downloadResponse->assertStatus(200);
        $this->assertStringContainsString('attachment;', (string) $downloadResponse->headers->get('content-disposition'));
        $bytes = $downloadResponse->getContent();
        $this->assertStringStartsWith('%PDF-', $bytes);
        [$w, $h] = $this->extractMediaBox($bytes);
        // appointment_report defaults to landscape orientation, so 40mm x 2000mm swaps to 2000mm x 40mm
        $this->assertEqualsWithDelta(5669.29, $w, 1.0);
        $this->assertEqualsWithDelta(113.39, $h, 0.5);
    }

    protected function extractMediaBox(string $pdfBytes): array
    {
        preg_match('/\/MediaBox\s*\[\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdfBytes, $m);
        $this->assertCount(5, $m, 'MediaBox not found in PDF output.');

        return [(float) $m[3], (float) $m[4]];
    }
}
