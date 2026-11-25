<?php

namespace App\Support\Pdf;

class PdfLayoutConfig
{
    public const PT_PER_MM = 72.0 / 25.4;

    public function __construct(
        public readonly string $reportKey,
        public readonly string $presetKey,
        public readonly string $paper,
        public readonly float $widthMm,
        public readonly float $heightMm,
        public readonly string $unit,
        public readonly string $orientation,
        public readonly array $marginsMm,
        public readonly array $contentFrame,
        public readonly int $scalePercent = 100,
        public readonly int $baseFontSizePx = 10,
        public readonly bool $fitToPage = false,
        public readonly bool $repeatTableHeaders = true,
        public readonly bool $showPageNumbers = true,
        public readonly ?string $footerText = null,
        public readonly int $schemaVersion = 1,
    ) {
    }

    /**
     * Final oriented page width in PDF points.
     */
    public function widthPt(): float
    {
        return round($this->widthMm * self::PT_PER_MM, 3);
    }

    /**
     * Final oriented page height in PDF points.
     */
    public function heightPt(): float
    {
        return round($this->heightMm * self::PT_PER_MM, 3);
    }

    /**
     * Page size array compatible with DomPDF::setPaper().
     */
    public function paperPointsBox(): array
    {
        return [0.0, 0.0, $this->widthPt(), $this->heightPt()];
    }

    /**
     * Margins converted to PDF points.
     */
    public function marginsPt(): array
    {
        return [
            'top' => round(($this->marginsMm['top'] ?? 0) * self::PT_PER_MM, 3),
            'right' => round(($this->marginsMm['right'] ?? 0) * self::PT_PER_MM, 3),
            'bottom' => round(($this->marginsMm['bottom'] ?? 0) * self::PT_PER_MM, 3),
            'left' => round(($this->marginsMm['left'] ?? 0) * self::PT_PER_MM, 3),
        ];
    }

    /**
     * Available printable width inside page margins (in mm).
     */
    public function printableWidthMm(): float
    {
        return max(
            10.0,
            $this->widthMm - ($this->marginsMm['left'] ?? 0) - ($this->marginsMm['right'] ?? 0)
        );
    }

    /**
     * Whether the printable width is compact/narrow (e.g., thermal roll or 100mm invoice).
     */
    public function isCompactWidth(): bool
    {
        $effectiveWidth = $this->hasContentFrame() && !empty($this->contentFrame['width_mm'])
            ? (float) $this->contentFrame['width_mm']
            : $this->widthMm;

        return $effectiveWidth <= 115.0;
    }

    public function hasContentFrame(): bool
    {
        return (bool) ($this->contentFrame['enabled'] ?? false);
    }

    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'report_key' => $this->reportKey,
            'preset' => $this->presetKey,
            'paper' => $this->paper,
            'width_mm' => $this->widthMm,
            'height_mm' => $this->heightMm,
            'unit' => $this->unit,
            'orientation' => $this->orientation,
            'margins_mm' => $this->marginsMm,
            'content_frame' => $this->contentFrame,
            'scale_percent' => $this->scalePercent,
            'base_font_size_px' => $this->baseFontSizePx,
            'fit_to_page' => $this->fitToPage,
            'repeat_table_headers' => $this->repeatTableHeaders,
            'show_page_numbers' => $this->showPageNumbers,
            'footer_text' => $this->footerText,
        ];
    }
}
