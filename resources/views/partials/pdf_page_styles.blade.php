@if(isset($pdfLayout) && $pdfLayout instanceof \App\Support\Pdf\PdfLayoutConfig)
@php
    $marginsPt = $pdfLayout->marginsPt();
    $frame = $pdfLayout->contentFrame;
    $frameEnabled = $pdfLayout->hasContentFrame() && !empty($frame['width_mm']);
    $frameWidthMm = $frameEnabled ? (float) $frame['width_mm'] : null;
    $frameAlignment = $frame['alignment'] ?? 'top-center';
    $offsetX = (float) ($frame['offset_x_mm'] ?? 0);
    $offsetY = (float) ($frame['offset_y_mm'] ?? 0);

    $marginLeftCss = match ($frameAlignment) {
        'top-left' => '0',
        'top-right' => 'auto',
        default => 'auto',
    };
    $marginRightCss = match ($frameAlignment) {
        'top-left' => 'auto',
        'top-right' => '0',
        default => 'auto',
    };
@endphp
<style>
    @page {
        size: {{ $pdfLayout->widthPt() }}pt {{ $pdfLayout->heightPt() }}pt;
        margin: {{ $marginsPt['top'] }}pt {{ $marginsPt['right'] }}pt {{ $marginsPt['bottom'] }}pt {{ $marginsPt['left'] }}pt;
    }

    @if($pdfLayout->scalePercent !== 100)
    @php $scaleFactor = $pdfLayout->scalePercent / 100; @endphp
    body {
        font-size: {{ round($pdfLayout->baseFontSizePx * $scaleFactor, 2) }}px;
    }
    table.appointments th,
    table.appointments td,
    table.main-table th,
    table.main-table td,
    table.report-table th,
    table.report-table td,
    table.data-table th,
    table.data-table td,
    table.attendance th,
    table.attendance td,
    table.schedule-table th,
    table.schedule-table td,
    table.items-table th,
    table.items-table td,
    .pdf-scalable-table th,
    .pdf-scalable-table td {
        font-size: {{ round(8.5 * $scaleFactor, 2) }}px;
        padding: {{ round(5 * $scaleFactor, 2) }}px {{ round(4.5 * $scaleFactor, 2) }}px;
    }
    @else
    body {
        font-size: {{ $pdfLayout->baseFontSizePx }}px;
    }
    @endif

    @if($pdfLayout->repeatTableHeaders)
    table thead {
        display: table-header-group;
    }
    @endif

    tr, .no-break {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    @if($frameEnabled)
    .pdf-content-frame {
        width: {{ $frameWidthMm }}mm;
        margin-left: {{ $marginLeftCss }};
        margin-right: {{ $marginRightCss }};
        @if($offsetX != 0.0)
        padding-left: {{ $offsetX > 0 ? $offsetX : 0 }}mm;
        padding-right: {{ $offsetX < 0 ? abs($offsetX) : 0 }}mm;
        @endif
        @if($offsetY > 0)
        margin-top: {{ $offsetY }}mm;
        @endif
    }
    @endif
</style>
@endif
