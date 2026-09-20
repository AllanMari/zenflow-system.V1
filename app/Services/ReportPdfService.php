<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReportPdfService
{
    public function generatePdf(
        string $view,
        array $data,
        string $filename,
        string $orientation = 'portrait'
    ): Response {
        $pdf = Pdf::loadView($view, $data);

        $pdf->setPaper('a4', $orientation);

        $pdf->setOption(
            'defaultFont',
            'DejaVu Sans'
        );

        $pdf->setOption('margin-top', 0);
        $pdf->setOption('margin-right', 0);
        $pdf->setOption('margin-bottom', 0);
        $pdf->setOption('margin-left', 0);

        return $pdf->download($filename);
    }

    public function streamPdf(
        string $view,
        array $data,
        string $filename,
        string $orientation = 'portrait'
    ): Response {
        $pdf = Pdf::loadView($view, $data);

        $pdf->setPaper('a4', $orientation);

        $pdf->setOption(
            'defaultFont',
            'DejaVu Sans'
        );

        $pdf->setOption('margin-top', 0);
        $pdf->setOption('margin-right', 0);
        $pdf->setOption('margin-bottom', 0);
        $pdf->setOption('margin-left', 0);

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
}