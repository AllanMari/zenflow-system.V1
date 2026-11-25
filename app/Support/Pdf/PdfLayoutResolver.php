<?php

namespace App\Support\Pdf;

use App\Models\PdfReportDefault;
use Illuminate\Http\Request;

class PdfLayoutResolver
{
    public function __construct(
        protected PdfPresetRegistry $registry
    ) {
    }

    /**
     * Extract validated layout overrides from an HTTP request and resolve config.
     */
    public function resolveFromRequest(
        string $reportKey,
        Request $request,
        ?array $savedDefaults = null
    ): PdfLayoutConfig {
        $overrides = [];

        if ($request->filled('pdf_preset')) {
            $preset = (string) $request->query('pdf_preset');
            $allowedPresets = $this->registry->getAllowedPresetsForReport($reportKey);
            if (array_key_exists($preset, $allowedPresets)) {
                $overrides['preset'] = $preset;
            }
        }

        if ($request->filled('pdf_paper')) {
            $overrides['paper'] = (string) $request->query('pdf_paper');
        }

        if ($request->filled('pdf_unit')) {
            $overrides['unit'] = (string) $request->query('pdf_unit');
        }

        if ($request->filled('pdf_width') && is_numeric($request->query('pdf_width'))) {
            $overrides['width'] = (float) $request->query('pdf_width');
        }

        if ($request->filled('pdf_height') && is_numeric($request->query('pdf_height'))) {
            $overrides['height'] = (float) $request->query('pdf_height');
        }

        if ($request->filled('pdf_orientation')) {
            $overrides['orientation'] = (string) $request->query('pdf_orientation');
        }

        $margins = [];
        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $param = "pdf_margin_{$side}";
            if ($request->has($param) && $request->query($param) !== '' && is_numeric($request->query($param))) {
                $margins[$side] = (float) $request->query($param);
            }
        }
        if ($margins !== []) {
            $overrides['margins_mm'] = $margins;
        }

        if ($request->filled('pdf_scale') && is_numeric($request->query('pdf_scale'))) {
            $overrides['scale_percent'] = (int) $request->query('pdf_scale');
        }

        if ($request->has('pdf_repeat_headers')) {
            $overrides['repeat_table_headers'] = filter_var(
                $request->query('pdf_repeat_headers'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) ?? true;
        }

        if ($request->has('pdf_show_page_numbers')) {
            $overrides['show_page_numbers'] = filter_var(
                $request->query('pdf_show_page_numbers'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) ?? true;
        }

        if ($request->has('pdf_footer_text')) {
            $overrides['footer_text'] = (string) $request->query('pdf_footer_text', '');
        }

        if ($request->filled('pdf_base_font_size') && is_numeric($request->query('pdf_base_font_size'))) {
            $overrides['base_font_size_px'] = (int) $request->query('pdf_base_font_size');
        }

        if ($request->has('pdf_fit_to_page')) {
            $overrides['fit_to_page'] = filter_var(
                $request->query('pdf_fit_to_page'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) ?? false;
        }

        $contentFrame = [];
        if ($request->has('pdf_frame_enabled')) {
            $contentFrame['enabled'] = filter_var(
                $request->query('pdf_frame_enabled'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) ?? false;
        }
        if ($request->filled('pdf_frame_width') && is_numeric($request->query('pdf_frame_width'))) {
            $contentFrame['width_mm'] = (float) $request->query('pdf_frame_width');
        }
        if ($request->filled('pdf_frame_alignment')) {
            $contentFrame['alignment'] = (string) $request->query('pdf_frame_alignment');
        }
        if ($request->has('pdf_frame_offset_x') && is_numeric($request->query('pdf_frame_offset_x'))) {
            $contentFrame['offset_x_mm'] = (float) $request->query('pdf_frame_offset_x');
        }
        if ($request->has('pdf_frame_offset_y') && is_numeric($request->query('pdf_frame_offset_y'))) {
            $contentFrame['offset_y_mm'] = (float) $request->query('pdf_frame_offset_y');
        }
        if ($contentFrame !== []) {
            $overrides['content_frame'] = $contentFrame;
        }

        return $this->resolve($reportKey, $overrides, $savedDefaults);
    }

    /**
     * Build frontend metadata payload for the reusable PDF preview modal.
     */
    public function buildFrontendSchema(string $reportKey, ?array $savedDefaults = null): array
    {
        if ($savedDefaults === null) {
            $savedDefaults = PdfReportDefault::findSettingsForReport($reportKey);
        }

        $report = $this->registry->getReportDefinition($reportKey);
        $defaultConfig = $this->resolve($reportKey, [], $savedDefaults);
        $allowedPresets = $this->registry->getAllowedPresetsForReport($reportKey);

        $presetOptions = [];
        foreach ($allowedPresets as $key => $preset) {
            $presetOptions[$key] = [
                'key' => $key,
                'label' => $preset['label'] ?? $key,
                'paper' => $preset['paper'] ?? 'a4',
                'orientation' => $preset['orientation'] ?? 'portrait',
                'margins_mm' => $preset['margins_mm'] ?? config('pdf.default_margins_mm'),
                'content_frame' => $preset['content_frame'] ?? ['enabled' => false],
                'scale_percent' => $preset['scale_percent'] ?? 100,
                'base_font_size_px' => $preset['base_font_size_px'] ?? 10,
                'fit_to_page' => $preset['fit_to_page'] ?? false,
                'repeat_table_headers' => $preset['repeat_table_headers'] ?? true,
                'show_page_numbers' => $preset['show_page_numbers'] ?? true,
                'capabilities' => $preset['capabilities'] ?? [],
            ];
        }

        return [
            'reportKey' => $reportKey,
            'reportLabel' => $report['label'] ?? 'Document',
            'hasSavedDefault' => !empty($savedDefaults),
            'defaults' => $defaultConfig->toArray(),
            'presets' => $presetOptions,
            'papers' => $this->registry->getPapers(),
            'bounds' => config('pdf.bounds', []),
        ];
    }

    /**
     * Resolve a validated PdfLayoutConfig for a report.
     *
     * Layering order:
     * 1. Code-defined preset defaults for the report
     * 2. Saved admin defaults (when provided or loaded from DB)
     * 3. Temporary request overrides
     */
    public function resolve(
        string $reportKey,
        array $overrides = [],
        ?array $savedDefaults = null
    ): PdfLayoutConfig {
        if ($savedDefaults === null) {
            $savedDefaults = PdfReportDefault::findSettingsForReport($reportKey);
        }

        $merged = array_replace_recursive($savedDefaults ?? [], $overrides);

        $defaultPresetKey = $this->registry->getDefaultPresetKeyForReport($reportKey);
        $presetKey = $this->resolvePresetKey($reportKey, $merged['preset'] ?? $defaultPresetKey);
        $preset = $this->registry->getPreset($presetKey) ?? $this->registry->getPreset('standard_report');

        $paperKey = $this->resolvePaperKey($merged['paper'] ?? ($preset['paper'] ?? 'a4'));
        $unit = $this->resolveUnit($merged['unit'] ?? 'mm');

        $paperDef = $this->registry->getPaper($paperKey);
        $supportsOrientation = $paperDef ? ($paperDef['supports_orientation'] ?? true) : true;

        $orientation = $supportsOrientation
            ? $this->resolveOrientation($merged['orientation'] ?? ($preset['orientation'] ?? 'portrait'))
            : 'portrait';

        // Determine base (unoriented) dimensions in mm
        if ($paperKey === 'custom' || !$paperDef) {
            if (array_key_exists('width', $merged) && array_key_exists('height', $merged)) {
                $rawW = $this->toMm((float) $merged['width'], $unit);
                $rawH = $this->toMm((float) $merged['height'], $unit);
            } else {
                $rawW = (float) ($merged['width_mm'] ?? 210.0);
                $rawH = (float) ($merged['height_mm'] ?? 297.0);
            }
            $baseWidthMm = $this->clamp($rawW, 'width_mm');
            $baseHeightMm = $this->clamp($rawH, 'height_mm');
        } else {
            $baseWidthMm = (float) $paperDef['width_mm'];
            $baseHeightMm = (float) $paperDef['height_mm'];
        }

        // Apply orientation so widthMm/heightMm always reflect final page orientation
        if ($orientation === 'landscape' && $baseWidthMm < $baseHeightMm) {
            [$widthMm, $heightMm] = [$baseHeightMm, $baseWidthMm];
        } elseif ($orientation === 'portrait' && $baseWidthMm > $baseHeightMm) {
            [$widthMm, $heightMm] = [$baseHeightMm, $baseWidthMm];
        } else {
            [$widthMm, $heightMm] = [$baseWidthMm, $baseHeightMm];
        }

        $marginsMm = $this->resolveMargins(
            $merged['margins_mm'] ?? [],
            $preset['margins_mm'] ?? config('pdf.default_margins_mm', [])
        );

        $contentFrame = $this->resolveContentFrame(
            $merged['content_frame'] ?? [],
            $preset['content_frame'] ?? [],
            $widthMm,
            $marginsMm
        );

        $scalePercent = (int) $this->clamp(
            (float) ($merged['scale_percent'] ?? ($preset['scale_percent'] ?? 100)),
            'scale_percent'
        );

        $baseFontSizePx = (int) $this->clamp(
            (float) ($merged['base_font_size_px'] ?? ($preset['base_font_size_px'] ?? 10)),
            'font_size_px'
        );

        $fitToPage = (bool) ($merged['fit_to_page'] ?? ($preset['fit_to_page'] ?? false));
        $repeatTableHeaders = (bool) ($merged['repeat_table_headers'] ?? ($preset['repeat_table_headers'] ?? true));
        $showPageNumbers = (bool) ($merged['show_page_numbers'] ?? ($preset['show_page_numbers'] ?? true));

        $footerText = isset($merged['footer_text']) && is_string($merged['footer_text'])
            ? mb_substr(trim($merged['footer_text']), 0, 120)
            : null;

        return new PdfLayoutConfig(
            reportKey: $reportKey,
            presetKey: $presetKey,
            paper: $paperKey,
            widthMm: round($widthMm, 2),
            heightMm: round($heightMm, 2),
            unit: $unit,
            orientation: $orientation,
            marginsMm: $marginsMm,
            contentFrame: $contentFrame,
            scalePercent: $scalePercent,
            baseFontSizePx: $baseFontSizePx,
            fitToPage: $fitToPage,
            repeatTableHeaders: $repeatTableHeaders,
            showPageNumbers: $showPageNumbers,
            footerText: $footerText ?: null,
            schemaVersion: (int) config('pdf.schema_version', 1),
        );
    }

    public function toMm(float $value, string $unit): float
    {
        return match ($unit) {
            'cm' => $value * 10.0,
            'in' => $value * 25.4,
            default => $value,
        };
    }

    public function mmToPt(float $mm): float
    {
        return round($mm * PdfLayoutConfig::PT_PER_MM, 3);
    }

    protected function resolvePresetKey(string $reportKey, mixed $candidate): string
    {
        if (is_string($candidate) && $this->registry->getPreset($candidate) !== null) {
            return $candidate;
        }

        return $this->registry->getDefaultPresetKeyForReport($reportKey);
    }

    protected function resolvePaperKey(mixed $candidate): string
    {
        if ($candidate === 'custom') {
            return 'custom';
        }

        if (is_string($candidate) && $this->registry->getPaper($candidate) !== null) {
            return $candidate;
        }

        return 'a4';
    }

    protected function resolveUnit(mixed $candidate): string
    {
        return in_array($candidate, ['mm', 'cm', 'in'], true) ? $candidate : 'mm';
    }

    protected function resolveOrientation(mixed $candidate): string
    {
        return $candidate === 'landscape' ? 'landscape' : 'portrait';
    }

    protected function resolveMargins(array $overrideMargins, array $defaultMargins): array
    {
        $resolved = [];
        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $raw = $overrideMargins[$side] ?? $defaultMargins[$side] ?? 9.26;
            $resolved[$side] = round($this->clamp((float) $raw, 'margin_mm'), 2);
        }

        return $resolved;
    }

    protected function resolveContentFrame(
        array $overrideFrame,
        array $defaultFrame,
        float $pageWidthMm,
        array $marginsMm
    ): array {
        $enabled = (bool) ($overrideFrame['enabled'] ?? $defaultFrame['enabled'] ?? false);
        $maxPrintableWidth = max(20.0, $pageWidthMm - $marginsMm['left'] - $marginsMm['right']);

        $rawWidth = $overrideFrame['width_mm'] ?? $defaultFrame['width_mm'] ?? null;
        $widthMm = $rawWidth !== null
            ? round(min($maxPrintableWidth, max(30.0, (float) $rawWidth)), 2)
            : null;

        $rawHeight = $overrideFrame['height_mm'] ?? $defaultFrame['height_mm'] ?? null;
        $heightMm = $rawHeight !== null
            ? round(max(30.0, (float) $rawHeight), 2)
            : null;

        $allowedAlignments = ['top-left', 'top-center', 'top-right', 'center'];
        $alignment = $overrideFrame['alignment'] ?? $defaultFrame['alignment'] ?? 'top-center';
        if (!in_array($alignment, $allowedAlignments, true)) {
            $alignment = 'top-center';
        }

        return [
            'enabled' => $enabled,
            'width_mm' => $widthMm,
            'height_mm' => $heightMm,
            'alignment' => $alignment,
            'offset_x_mm' => round($this->clampFloat((float) ($overrideFrame['offset_x_mm'] ?? $defaultFrame['offset_x_mm'] ?? 0.0), -50.0, 50.0), 2),
            'offset_y_mm' => round($this->clampFloat((float) ($overrideFrame['offset_y_mm'] ?? $defaultFrame['offset_y_mm'] ?? 0.0), -50.0, 50.0), 2),
        ];
    }

    protected function clamp(float $value, string $boundKey): float
    {
        $min = (float) config("pdf.bounds.{$boundKey}.min", 0);
        $max = (float) config("pdf.bounds.{$boundKey}.max", 1000);

        return $this->clampFloat($value, $min, $max);
    }

    protected function clampFloat(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
