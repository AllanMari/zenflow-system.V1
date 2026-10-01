<div class="report-header" style="padding: 20px 25px 0 25px;">
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 0;">
        <tr>
            <td style="width: 105mm; vertical-align: top; padding: 0;">
                <div style="font-size: 18px; font-weight: 800; color: #0f766e; letter-spacing: .3px; text-transform: uppercase;">SPA ALEXANDRIA</div>
                <div style="margin-top: 2px; font-size: 7px; color: #6b7280; text-transform: uppercase; letter-spacing: .9px;">ZenFlow Appointment & Workforce System</div>
                <div style="margin-top: 2px; font-size: 7px; color: #9ca3af;">Bacolod City, Negros Occidental, Philippines</div>
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
