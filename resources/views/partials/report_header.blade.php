@php
    $logoFile = public_path('uploads/branding/spa-alexandria-logo.png');
    $logoBase64 = null;
    // DomPDF's CPDF adapter requires the PHP GD extension (imagecreatefrompng) to embed PNGs into PDF files.
    // When GD is enabled (standard on Hostinger PHP), embed the logo as Base64.
    // If GD is not loaded (e.g. minimal local CLI), cleanly fall back to typography so report generation never crashes.
    if (extension_loaded('gd') && file_exists($logoFile) && filesize($logoFile) > 0) {
        $logoData = @file_get_contents($logoFile);
        if ($logoData !== false) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }
    }
    $hasPdfLayout = isset($pdfLayout)
        && $pdfLayout instanceof \App\Support\Pdf\PdfLayoutConfig;
    $isCompactHeader = $hasPdfLayout && $pdfLayout->isCompactWidth();
    $leftHeaderWidth = $hasPdfLayout ? '58%' : '105mm';
    $rightHeaderWidth = $hasPdfLayout ? '42%' : '75mm';
@endphp
@if($isCompactHeader)
<div class="report-header report-header--compact" style="padding: 0; text-align: center; color: #000000;">
    @if($logoBase64)
        <div style="margin-bottom: 3px;">
            <img src="{{ $logoBase64 }}" alt="Spa Alexandria" style="width: 24px; height: 24px; object-fit: contain;">
        </div>
    @endif
    <div style="font-size: 11px; font-weight: 700; color: #000000; letter-spacing: .4px; text-transform: uppercase;">SPA ALEXANDRIA</div>
    <div style="margin-top: 1px; font-size: 6.5px; color: #4b5563;">Bacolod City, Negros Occidental, Philippines</div>
    <div style="margin-top: 4px; font-size: 10px; font-weight: 700; color: #000000; letter-spacing: .5px; text-transform: uppercase;">{{ $reportTitle ?? 'REPORT' }}</div>
    @if(!empty($referenceNumber) || !empty($dateRange))
        <div style="margin-top: 2px; font-size: 7px; color: #333333;">
            @if(!empty($referenceNumber))
                Ref: <strong style="color: #000000;">{{ $referenceNumber }}</strong>
            @endif
            @if(!empty($referenceNumber) && !empty($dateRange))
                &nbsp;&bull;&nbsp;
            @endif
            @if(!empty($dateRange))
                <span>{{ $dateRange }}</span>
            @endif
        </div>
    @endif
    <div style="width: 100%; border-bottom: 1.5px solid #111111; margin-top: 5px; margin-bottom: 6px;"></div>
</div>
@else
<div class="report-header" style="{{ $hasPdfLayout ? 'padding: 0;' : 'padding: 20px 25px 0 25px;' }} color: #000000;">
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 0;">
        <tr>
            <td style="width: {{ $leftHeaderWidth }}; vertical-align: top; padding: 0;">
                <table style="border-collapse: collapse; margin-bottom: 0;">
                    <tr>
                        @if($logoBase64)
                            <td style="vertical-align: middle; padding-right: 10px; width: 46px;">
                                <img src="{{ $logoBase64 }}" alt="Spa Alexandria" style="width: 38px; height: 38px; object-fit: contain;">
                            </td>
                        @endif
                        <td style="vertical-align: middle; padding: 0;">
                            <div style="font-size: 13px; font-weight: 700; color: #000000; letter-spacing: .3px; text-transform: uppercase;">SPA ALEXANDRIA</div>
                            <div style="margin-top: 2px; font-size: 7.5px; color: #4b5563; text-transform: uppercase; letter-spacing: .6px;">ZenFlow Appointment &amp; Workforce System</div>
                            <div style="margin-top: 1px; font-size: 7.5px; color: #4b5563;">Bacolod City, Negros Occidental, Philippines</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: {{ $rightHeaderWidth }}; vertical-align: top; text-align: right; padding: 0;">
                <div style="font-size: 14px; font-weight: 700; color: #000000; letter-spacing: .5px; text-transform: uppercase;">{{ $reportTitle ?? 'REPORT' }}</div>
                @if(!empty($referenceNumber))
                    <div style="margin-top: 2px; font-size: 7.5px; color: #4b5563;">Reference: <span style="font-weight: 700; color: #000000;">{{ $referenceNumber }}</span></div>
                @endif
                @if(!empty($dateRange))
                    <div style="margin-top: 2px; font-size: 7.5px; color: #4b5563;">Date Range: <span style="font-weight: 700; color: #000000;">{{ $dateRange }}</span></div>
                @endif
                @if(!empty($generatedAt))
                    <div style="margin-top: 2px; font-size: 7.5px; color: #4b5563;">Generated: <span style="font-weight: 700; color: #000000;">{{ $generatedAt }}</span></div>
                @endif
                @if(!empty($preparedBy))
                    <div style="margin-top: 2px; font-size: 7.5px; color: #4b5563;">Prepared By: <span style="font-weight: 700; color: #000000;">{{ $preparedBy }}</span></div>
                @endif
            </td>
        </tr>
    </table>
    <div style="width: 100%; border-bottom: 1.5px solid #111111; margin-top: 10px; margin-bottom: 12px;"></div>
</div>
@endif
