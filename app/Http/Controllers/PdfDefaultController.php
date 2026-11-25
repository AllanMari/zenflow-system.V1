<?php

namespace App\Http\Controllers;

use App\Models\PdfReportDefault;
use App\Support\Pdf\PdfLayoutResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PdfDefaultController extends Controller
{
    public function __construct(
        protected PdfLayoutResolver $resolver
    ) {
    }

    public function save(Request $request, string $reportKey): JsonResponse
    {
        $this->ensureValidReportKey($reportKey);
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'preset' => ['required', 'string', 'max:64'],
            'paper' => ['required', 'string', 'max:64'],
            'unit' => ['nullable', 'string', 'in:mm,cm,in'],
            'width' => ['nullable', 'numeric', 'min:1', 'max:3000'],
            'height' => ['nullable', 'numeric', 'min:1', 'max:5000'],
            'orientation' => ['nullable', 'string', 'in:portrait,landscape'],
            'margins_mm' => ['nullable', 'array'],
            'margins_mm.top' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'margins_mm.right' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'margins_mm.bottom' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'margins_mm.left' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'content_frame' => ['nullable', 'array'],
            'content_frame.enabled' => ['nullable', 'boolean'],
            'content_frame.width_mm' => ['nullable', 'numeric', 'min:30', 'max:300'],
            'content_frame.alignment' => ['nullable', 'string', 'in:top-left,top-center,top-right,center'],
            'content_frame.offset_x_mm' => ['nullable', 'numeric', 'min:-50', 'max:50'],
            'content_frame.offset_y_mm' => ['nullable', 'numeric', 'min:-50', 'max:50'],
            'scale_percent' => ['nullable', 'integer', 'min:50', 'max:150'],
            'base_font_size_px' => ['nullable', 'integer', 'min:6', 'max:18'],
            'fit_to_page' => ['nullable', 'boolean'],
            'repeat_table_headers' => ['nullable', 'boolean'],
            'show_page_numbers' => ['nullable', 'boolean'],
            'footer_text' => ['nullable', 'string', 'max:120'],
        ]);

        // Resolve via PdfLayoutResolver (passing [] as savedDefaults) to normalize & clamp cleanly
        $resolved = $this->resolver->resolve($reportKey, $validated, []);
        $payload = $resolved->toArray();

        PdfReportDefault::updateOrCreate(
            ['report_key' => $reportKey],
            [
                'schema_version' => (int) config('pdf.schema_version', 1),
                'settings' => $payload,
                'updated_by' => $request->user()->id,
            ]
        );

        return response()->json([
            'message' => 'Default PDF settings saved for this report.',
            'schema' => $this->resolver->buildFrontendSchema($reportKey, $payload),
        ]);
    }

    public function reset(Request $request, string $reportKey): JsonResponse
    {
        $this->ensureValidReportKey($reportKey);
        abort_unless($request->user()?->isAdmin(), 403);

        PdfReportDefault::query()->where('report_key', $reportKey)->delete();

        return response()->json([
            'message' => 'Report PDF settings reset to system default.',
            'schema' => $this->resolver->buildFrontendSchema($reportKey, []),
        ]);
    }

    protected function ensureValidReportKey(string $reportKey): void
    {
        $reports = config('pdf.reports', []);
        abort_unless(array_key_exists($reportKey, $reports), 404);
    }
}
