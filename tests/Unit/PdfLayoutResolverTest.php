<?php

namespace Tests\Unit;

use App\Support\Pdf\PdfLayoutConfig;
use App\Support\Pdf\PdfLayoutResolver;
use App\Support\Pdf\PdfPresetRegistry;
use Tests\TestCase;

class PdfLayoutResolverTest extends TestCase
{
    protected PdfLayoutResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new PdfLayoutResolver(new PdfPresetRegistry());
    }

    public function test_resolves_default_presets_for_all_nine_reports(): void
    {
        $expected = [
            'appointment_invoice' => ['compact_100x150', 100.0, 150.0, 'portrait'],
            'daily_sales' => ['standard_report', 210.0, 297.0, 'portrait'],
            'appointment_report' => ['wide_report', 297.0, 210.0, 'landscape'],
            'attendance_report' => ['wide_report', 297.0, 210.0, 'landscape'],
            'room_report' => ['standard_report', 210.0, 297.0, 'portrait'],
            'service_popularity' => ['standard_report', 210.0, 297.0, 'portrait'],
            'staff_utilization' => ['wide_report', 297.0, 210.0, 'landscape'],
            'weekly_schedule' => ['wide_report', 297.0, 210.0, 'landscape'],
            'business_report' => ['wide_report', 297.0, 210.0, 'landscape'],
        ];

        foreach ($expected as $reportKey => [$preset, $w, $h, $orient]) {
            $config = $this->resolver->resolve($reportKey);
            $this->assertSame($preset, $config->presetKey, $reportKey);
            $this->assertSame($w, $config->widthMm, $reportKey);
            $this->assertSame($h, $config->heightMm, $reportKey);
            $this->assertSame($orient, $config->orientation, $reportKey);
        }
    }

    public function test_converts_custom_cm_and_in_dimensions_to_mm_and_points(): void
    {
        $cmConfig = $this->resolver->resolve('daily_sales', [
            'preset' => 'custom',
            'paper' => 'custom',
            'unit' => 'cm',
            'width' => 15,
            'height' => 20,
            'orientation' => 'portrait',
        ]);

        $this->assertSame(150.0, $cmConfig->widthMm);
        $this->assertSame(200.0, $cmConfig->heightMm);
        $this->assertEqualsWithDelta(425.197, $cmConfig->widthPt(), 0.01);
        $this->assertEqualsWithDelta(566.929, $cmConfig->heightPt(), 0.01);

        $inConfig = $this->resolver->resolve('daily_sales', [
            'preset' => 'custom',
            'paper' => 'custom',
            'unit' => 'in',
            'width' => 8.5,
            'height' => 11,
            'orientation' => 'landscape',
        ]);

        // Landscape swaps 215.9 x 279.4 -> 279.4 x 215.9
        $this->assertSame(279.4, $inConfig->widthMm);
        $this->assertSame(215.9, $inConfig->heightMm);
    }

    public function test_clamps_out_of_bounds_dimensions_and_ignores_invalid_values(): void
    {
        $config = $this->resolver->resolve('daily_sales', [
            'preset' => 'non_existent_preset',
            'paper' => 'custom',
            'unit' => 'px', // invalid unit falls back to mm
            'width' => 5, // below min 40mm
            'height' => 9999, // above max 2000mm
            'margins_mm' => ['top' => -10, 'left' => 500],
            'scale_percent' => 300,
        ]);

        $this->assertSame('mm', $config->unit);
        $this->assertSame(40.0, $config->widthMm);
        $this->assertSame(2000.0, $config->heightMm);
        $this->assertSame(0.0, $config->marginsMm['top']);
        $this->assertSame(60.0, $config->marginsMm['left']);
        $this->assertSame(150, $config->scalePercent);
    }

    public function test_thermal_roll_locks_orientation_to_portrait(): void
    {
        $config = $this->resolver->resolve('appointment_invoice', [
            'preset' => 'thermal_80',
            'paper' => 'roll_80',
            'orientation' => 'landscape',
        ]);

        $this->assertSame('portrait', $config->orientation);
        $this->assertSame(80.0, $config->widthMm);
        $this->assertTrue($config->isCompactWidth());
    }

    public function test_receipt_on_a4_preserves_a4_page_and_content_frame_width(): void
    {
        $config = $this->resolver->resolve('appointment_invoice', [
            'preset' => 'receipt_on_a4',
        ]);

        $this->assertSame(210.0, $config->widthMm);
        $this->assertSame(297.0, $config->heightMm);
        $this->assertTrue($config->hasContentFrame());
        $this->assertSame(90.0, $config->contentFrame['width_mm']);
        $this->assertTrue($config->isCompactWidth());
    }
}
