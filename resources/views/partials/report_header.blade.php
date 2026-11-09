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
@endphp
<div class="report-header" style="padding: 20px 25px 0 25px;">
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 0;">
        <tr>
            <td style="width: 105mm; vertical-align: top; padding: 0;">
                <table style="border-collapse: collapse; margin-bottom: 0;">
                    <tr>
                        @if($logoBase64)
                            <td style="vertical-align: middle; padding-right: 12px; width: 38px;">
                                <img src="{{ $logoBase64 }}" alt="Spa Alexandria" style="width: 36px; height: 36px; object-fit: contain;">
                            </td>
                        @endif
                        <td style="vertical-align: middle; padding: 0;">
                            <div style="font-size: 18px; font-weight: 800; color: #0f766e; letter-spacing: .3px; text-transform: uppercase;">SPA ALEXANDRIA</div>
                            <div style="margin-top: 2px; font-size: 7px; color: #6b7280; text-transform: uppercase; letter-spacing: .9px;">ZenFlow Appointment & Workforce System</div>
                            <div style="margin-top: 2px; font-size: 7px; color: #9ca3af;">Bacolod City, Negros Occidental, Philippines</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 75mm; vertical-align: top; text-align: right; padding: 0;">
                <div style="font-size: 15px; font-weight: 800; color: #111827; letter-spacing: .8px; text-transform: uppercase;">{{ $reportTitle ?? 'REPORT' }}</div>
                @if(!empty($referenceNumber))
                    <div style="margin-top: 3px; font-size: 7.5px; color: #6b7280;">Reference: <span style="font-weight: 700; color: #111827;">{{ $referenceNumber }}</span></div>
                @endif
                @if(!empty($dateRange))
                    <div style="margin-top: 3px; font-size: 7.5px; color: #6b7280;">Date Range: <span style="font-weight: 700; color: #111827;">{{ $dateRange }}</span></div>
                @endif
                @if(!empty($generatedAt))
                    <div style="margin-top: 3px; font-size: 7.5px; color: #6b7280;">Generated: <span style="font-weight: 700; color: #111827;">{{ $generatedAt }}</span></div>
                @endif
                @if(!empty($preparedBy))
                    <div style="margin-top: 3px; font-size: 7.5px; color: #6b7280;">Prepared By: <span style="font-weight: 700; color: #111827;">{{ $preparedBy }}</span></div>
                @endif
            </td>
        </tr>
    </table>
    <div style="width: 100%; height: 2px; background: #0f766e; margin-top: 14px; margin-bottom: 14px;"></div>
</div>
