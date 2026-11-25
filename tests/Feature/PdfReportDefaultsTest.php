<?php

namespace Tests\Feature;

use App\Models\PdfReportDefault;
use App\Models\Role;
use App\Models\User;
use App\Support\Pdf\PdfLayoutResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfReportDefaultsTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_admin_can_save_and_reset_report_defaults_and_temporary_overrides_do_not_mutate_them(): void
    {
        $admin = $this->createUserWithRole('admin');
        $receptionist = $this->createUserWithRole('receptionist');

        // 1. Save custom default for daily_sales (A5 Landscape, 15mm margins)
        $saveResponse = $this->actingAs($admin)->postJson(
            route('admin.pdf-defaults.save', ['reportKey' => 'daily_sales']),
            [
                'preset' => 'custom',
                'paper' => 'custom',
                'unit' => 'cm',
                'width' => 15,
                'height' => 22,
                'orientation' => 'portrait',
                'margins_mm' => ['top' => 12, 'right' => 12, 'bottom' => 12, 'left' => 12],
                'scale_percent' => 95,
                'base_font_size_px' => 9,
                'repeat_table_headers' => true,
                'show_page_numbers' => true,
                'footer_text' => 'Spa Alexandria Internal',
            ]
        );

        $saveResponse->assertStatus(200);
        $this->assertDatabaseHas('pdf_report_defaults', [
            'report_key' => 'daily_sales',
            'schema_version' => 1,
            'updated_by' => $admin->id,
        ]);

        // 2. Subsequent direct Download PDF (without query overrides) uses saved 15cm x 22cm (425.20 x 623.62 pt)
        $dlResponse = $this->actingAs($receptionist)->get(
            route('receptionist.sales.daily-report-pdf', ['action' => 'download'])
        );
        $dlResponse->assertStatus(200);
        [$w, $h] = $this->extractMediaBox($dlResponse->getContent());
        $this->assertEqualsWithDelta(425.20, $w, 0.8);
        $this->assertEqualsWithDelta(623.62, $h, 0.8);

        // 3. Temporary request override produces A4 Landscape without overwriting saved DB default
        $tempResponse = $this->actingAs($receptionist)->get(
            route('receptionist.sales.daily-report-pdf', [
                'action' => 'stream',
                'pdf_preset' => 'wide_report',
                'pdf_paper' => 'a4',
                'pdf_orientation' => 'landscape',
            ])
        );
        $tempResponse->assertStatus(200);
        [$tw, $th] = $this->extractMediaBox($tempResponse->getContent());
        $this->assertEqualsWithDelta(841.89, $tw, 0.8);
        $this->assertEqualsWithDelta(595.28, $th, 0.8);

        // Saved DB default remains 150 x 220 mm
        $resolvedAfter = app(PdfLayoutResolver::class)->resolve('daily_sales');
        $this->assertSame(150.0, $resolvedAfter->widthMm);
        $this->assertSame(220.0, $resolvedAfter->heightMm);

        // 4. Admin can reset to code-defined default (A4 Portrait)
        $resetResponse = $this->actingAs($admin)->deleteJson(
            route('admin.pdf-defaults.reset', ['reportKey' => 'daily_sales'])
        );
        $resetResponse->assertStatus(200);
        $this->assertDatabaseMissing('pdf_report_defaults', ['report_key' => 'daily_sales']);

        $afterReset = app(PdfLayoutResolver::class)->resolve('daily_sales');
        $this->assertSame('standard_report', $afterReset->presetKey);
        $this->assertSame(210.0, $afterReset->widthMm);
        $this->assertSame(297.0, $afterReset->heightMm);
    }

    public function test_non_admin_cannot_save_or_reset_defaults_and_unknown_report_returns_404(): void
    {
        $admin = $this->createUserWithRole('admin');
        $receptionist = $this->createUserWithRole('receptionist');

        $this->actingAs($receptionist)
            ->postJson(route('admin.pdf-defaults.save', ['reportKey' => 'daily_sales']), [
                'preset' => 'standard_report',
                'paper' => 'a4',
            ])
            ->assertStatus(403);

        $this->actingAs($receptionist)
            ->deleteJson(route('admin.pdf-defaults.reset', ['reportKey' => 'daily_sales']))
            ->assertStatus(403);

        $this->actingAs($admin)
            ->postJson(route('admin.pdf-defaults.save', ['reportKey' => 'non_existent_report']), [
                'preset' => 'standard_report',
                'paper' => 'a4',
            ])
            ->assertStatus(404);
    }

    public function test_incompatible_future_schema_version_safely_falls_back_to_code_defaults(): void
    {
        PdfReportDefault::create([
            'report_key' => 'room_report',
            'schema_version' => 999,
            'settings' => ['paper' => 'a3', 'orientation' => 'landscape'],
        ]);

        $resolved = app(PdfLayoutResolver::class)->resolve('room_report');
        $this->assertSame('standard_report', $resolved->presetKey);
        $this->assertSame(210.0, $resolved->widthMm);
        $this->assertSame(297.0, $resolved->heightMm);
    }

    protected function extractMediaBox(string $pdfBytes): array
    {
        preg_match('/\/MediaBox\s*\[\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdfBytes, $m);
        $this->assertCount(5, $m, 'MediaBox not found in PDF output.');

        return [(float) $m[3], (float) $m[4]];
    }
}
