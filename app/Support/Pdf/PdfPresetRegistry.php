<?php

namespace App\Support\Pdf;

class PdfPresetRegistry
{
    public function getPapers(): array
    {
        return config('pdf.papers', []);
    }

    public function getPaper(string $paperKey): ?array
    {
        return config("pdf.papers.{$paperKey}");
    }

    public function getPresets(): array
    {
        return config('pdf.presets', []);
    }

    public function getPreset(string $presetKey): ?array
    {
        return config("pdf.presets.{$presetKey}");
    }

    public function getReportDefinition(string $reportKey): array
    {
        return config("pdf.reports.{$reportKey}", [
            'label' => 'Document',
            'default_preset' => 'standard_report',
            'allowed_presets' => ['standard_report', 'wide_report', 'custom'],
        ]);
    }

    public function getDefaultPresetKeyForReport(string $reportKey): string
    {
        $report = $this->getReportDefinition($reportKey);

        return $report['default_preset'] ?? 'standard_report';
    }

    public function getAllowedPresetsForReport(string $reportKey): array
    {
        $report = $this->getReportDefinition($reportKey);
        $allowed = $report['allowed_presets'] ?? ['standard_report', 'wide_report', 'custom'];
        $all = $this->getPresets();

        return array_intersect_key($all, array_flip($allowed));
    }
}
