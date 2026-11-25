<?php

namespace App\Services;

use App\Support\Pdf\PdfLayoutConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Http\Response;

class ReportPdfService
{
    public function generatePdf(
        string $view,
        array $data,
        string $filename,
        string $orientation = 'portrait',
        ?PdfLayoutConfig $layout = null
    ): Response {
        $pdf = $this->buildPdf($view, $data, $orientation, $layout);

        return $pdf->download($filename);
    }

    public function streamPdf(
        string $view,
        array $data,
        string $filename,
        string $orientation = 'portrait',
        ?PdfLayoutConfig $layout = null
    ): Response {
        $pdf = $this->buildPdf($view, $data, $orientation, $layout);

        return response(
            $pdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                    'inline; filename="' . $filename . '"',
            ]
        );
    }

    /**
     * Build a configured DomPDF instance.
     *
     * When $layout is null, preserves legacy A4 behavior untouched.
     */
    protected function buildPdf(
        string $view,
        array $data,
        string $orientation,
        ?PdfLayoutConfig $layout = null
    ): DomPdfWrapper {
        if ($layout !== null && in_array($layout->paper, ['roll_80', 'roll_58'], true)) {
            $layout = $this->fitThermalRollHeight($view, $data, $layout);
        }

        if ($layout !== null && !array_key_exists('pdfLayout', $data)) {
            $data['pdfLayout'] = $layout;
        } elseif ($layout !== null) {
            $data['pdfLayout'] = $layout;
        }

        $pdf = Pdf::loadView($view, $data);

        if ($layout !== null) {
            // widthPt/heightPt already encode orientation; pass 'portrait' so DomPDF does not double-swap.
            $pdf->setPaper($layout->paperPointsBox(), 'portrait');
        } else {
            $pdf->setPaper('a4', $orientation);
        }

        $pdf->setOption('defaultFont', 'DejaVu Sans');
        $pdf->setOption('margin-top', 0);
        $pdf->setOption('margin-right', 0);
        $pdf->setOption('margin-bottom', 0);
        $pdf->setOption('margin-left', 0);

        if ($layout !== null && ($layout->showPageNumbers || $layout->footerText)) {
            $this->applyPageFooterOverlay($pdf, $layout);
        }

        return $pdf;
    }

    /**
     * Two-pass thermal receipt height fitting:
     * 1) Render on a tall virtual roll (1200mm) to measure content height.
     * 2) Return an updated PdfLayoutConfig sized to the measured height.
     */
    protected function fitThermalRollHeight(
        string $view,
        array $data,
        PdfLayoutConfig $layout
    ): PdfLayoutConfig {
        $probeLayout = new PdfLayoutConfig(
            reportKey: $layout->reportKey,
            presetKey: $layout->presetKey,
            paper: $layout->paper,
            widthMm: $layout->widthMm,
            heightMm: 1200.0,
            unit: $layout->unit,
            orientation: 'portrait',
            marginsMm: $layout->marginsMm,
            contentFrame: $layout->contentFrame,
            scalePercent: $layout->scalePercent,
            baseFontSizePx: $layout->baseFontSizePx,
            fitToPage: false,
            repeatTableHeaders: false,
            showPageNumbers: false,
            footerText: null,
            schemaVersion: $layout->schemaVersion,
        );

        $probeData = $data;
        $probeData['pdfLayout'] = $probeLayout;

        $probePdf = Pdf::loadView($view, $probeData);
        $probePdf->setPaper($probeLayout->paperPointsBox(), 'portrait');
        $probePdf->setOption('defaultFont', 'DejaVu Sans');
        $probePdf->setOption('margin-top', 0);
        $probePdf->setOption('margin-right', 0);
        $probePdf->setOption('margin-bottom', 0);
        $probePdf->setOption('margin-left', 0);
        $maxBottomPt = 0.0;
        $dompdf = $probePdf->getDomPDF();
        $dompdf->setCallbacks([
            'measure_thermal_bottom' => [
                'event' => 'end_frame',
                'f' => function ($frame) use (&$maxBottomPt) {
                    $nodeName = $frame->get_node()->nodeName ?? '';
                    if (in_array($nodeName, ['html', 'body', '#text'], true)) {
                        return;
                    }
                    $box = $frame->get_border_box();
                    $y = (float) ($box['y'] ?? 0.0);
                    $h = (float) ($box['h'] ?? 0.0);
                    if ($h > 0.0 && ($y + $h) < 3390.0) {
                        $maxBottomPt = max($maxBottomPt, $y + $h);
                    }
                },
            ],
        ]);
        $probePdf->render();

        if ($dompdf->getCanvas()->get_page_count() > 1 || $maxBottomPt <= 0.0) {
            return $layout;
        }

        $bottomMarginMm = (float) ($layout->marginsMm['bottom'] ?? 4.0);
        $measuredMm = ($maxBottomPt / PdfLayoutConfig::PT_PER_MM) + $bottomMarginMm + 4.0;
        $fittedHeightMm = round(max(80.0, min(1200.0, $measuredMm)), 2);

        return new PdfLayoutConfig(
            reportKey: $layout->reportKey,
            presetKey: $layout->presetKey,
            paper: $layout->paper,
            widthMm: $layout->widthMm,
            heightMm: $fittedHeightMm,
            unit: $layout->unit,
            orientation: 'portrait',
            marginsMm: $layout->marginsMm,
            contentFrame: $layout->contentFrame,
            scalePercent: $layout->scalePercent,
            baseFontSizePx: $layout->baseFontSizePx,
            fitToPage: $layout->fitToPage,
            repeatTableHeaders: $layout->repeatTableHeaders,
            showPageNumbers: $layout->showPageNumbers,
            footerText: $layout->footerText,
            schemaVersion: $layout->schemaVersion,
        );
    }

    /**
     * Draw page numbers and/or custom footer text inside the bottom margin.
     */
    protected function applyPageFooterOverlay(DomPdfWrapper $pdf, PdfLayoutConfig $layout): void
    {
        $marginsPt = $layout->marginsPt();
        if ($marginsPt['bottom'] < 12.0) {
            return;
        }

        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $y = $layout->heightPt() - max(11.0, $marginsPt['bottom'] * 0.65);
        $color = [0.42, 0.45, 0.50];

        if ($layout->footerText) {
            $canvas->page_text($marginsPt['left'], $y, $layout->footerText, $font, 7.5, $color);
        }

        if ($layout->showPageNumbers) {
            $xRight = max($marginsPt['left'], $layout->widthPt() - $marginsPt['right'] - 56.0);
            $canvas->page_text($xRight, $y, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 7.5, $color);
        }
    }
}