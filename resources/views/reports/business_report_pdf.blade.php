<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle ?? 'Business & Revenue Report' }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 12mm 14mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.38;
            color: #000000;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #000000;
            margin: 12px 0 5px;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #111111;
            page-break-after: avoid;
        }

        .summary-table,
        .data-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 8px;
        }

        .summary-table {
            page-break-inside: avoid;
        }

        .summary-table th {
            background: #f3f4f6;
            color: #111111;
            border: 1px solid #d1d5db;
            border-bottom: 1.5px solid #111111;
            padding: 4px 6px;
            font-size: 7.2px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
        }

        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            font-size: 9.5px;
            font-weight: bold;
            color: #000000;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .metric-sub {
            display: block;
            margin-top: 2px;
            font-size: 7px;
            font-weight: normal;
            color: #4b5563;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table tr {
            page-break-inside: avoid;
        }

        .data-table th {
            background: #f3f4f6;
            color: #111111;
            border: 1px solid #d1d5db;
            border-bottom: 1.5px solid #111111;
            padding: 5px 6px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
        }

        .data-table td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            font-size: 8.2px;
            color: #000000;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .data-table tfoot td {
            background: #f3f4f6;
            border-top: 1.5px solid #111111;
            font-weight: bold;
            color: #000000;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .muted {
            color: #4b5563;
            font-size: 7.5px;
        }

        .peak-row {
            font-weight: bold;
        }

        .empty-state {
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            text-align: center;
            color: #4b5563;
            font-size: 8.2px;
            margin-bottom: 8px;
        }

        .signature-table {
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .signature-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 24px;
            border: 0;
        }

        .signature-label {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #4b5563;
            margin-bottom: 18px;
        }

        .signature-line {
            width: 82%;
            border-top: 1px solid #111111;
            padding-top: 3px;
            font-size: 8.5px;
            font-weight: bold;
            color: #000000;
        }

        .signature-role {
            font-size: 7.2px;
            color: #4b5563;
        }

        .doc-footer {
            margin-top: 12px;
            padding-top: 5px;
            border-top: 1px solid #d1d5db;
            font-size: 7.2px;
            color: #4b5563;
            text-align: right;
        }
    </style>

    @include('partials.pdf_page_styles')
</head>
<body>
@php
    $reportTitle = $reportTitle ?? 'Business & Revenue Report';
    $dateRangeText = $dateRange ?? ($dateLabel ?? 'Current Period');
    $preparedByName = $preparedBy ?? 'Administrator';
    $checkedByName = !empty($checkedBy) ? $checkedBy : null;
    $generatedText = $generatedAt instanceof \Carbon\CarbonInterface
        ? $generatedAt->format('F j, Y g:i A')
        : ($generatedAt ?? now()->format('F j, Y g:i A'));

    $totalRev = (float) ($safeTotalRevenue ?? ($totalRevenue ?? 0));
    $avgSale = (float) ($safeAvgSale ?? 0);
    $uniqueCustomers = (int) ($safeUniqueCustomers ?? 0);
    $depositsTotal = (float) ($safeDeposits ?? 0);
    $revChangePct = (float) ($safeRevenueChange ?? 0);
    $revChangeText = $revenueChangeLabel ?? (($revChangePct >= 0 ? '+' : '') . number_format($revChangePct, 1) . '% vs prior period');

    $apptTotal = (int) ($safeTotalAppts ?? ($safeTotalCount ?? 0));
    $apptCompleted = (int) ($safeCompleted ?? 0);
    $apptCancelled = (int) ($safeCancelled ?? 0);
    $apptNoShow = (int) ($safeNoShow ?? 0);
    $completionRate = (float) ($safeCompletionRate ?? 0);
    $cancellationRate = (float) ($safeCancellationRate ?? 0);
    $noShowRate = (float) ($safeNoShowRate ?? 0);

    $servicesList = collect($topServices ?? []);
    $staffPerfList = collect($staffPerformance ?? []);
    $fallbackTopStaff = collect($topStaff ?? []);
    $retentionList = collect($therapistRetentionData ?? []);
    $peakData = is_array($peakBusinessHours ?? null) ? $peakBusinessHours : [];
    $hourRows = collect($peakData['hours'] ?? [])->filter(fn ($row) => (int) ($row['count'] ?? 0) > 0)->values();
    $peakSummaryLabel = $peakData['peakLabel'] ?? 'N/A';
    $peakMaxCount = (int) ($peakData['peakCount'] ?? 0);
    $methodRows = collect($methodBreakdown ?? []);
    $methodGrandTotal = (float) $methodRows->sum(fn ($item) => (float) data_get($item, 'total', 0));
@endphp

@include('partials.report_header', [
    'reportTitle' => $reportTitle,
    'dateRange' => $dateRangeText,
    'referenceNumber' => $referenceNumber ?? null,
    'generatedAt' => $generatedText,
    'preparedBy' => $preparedByName,
])

<div class="section-title">Key Metrics</div>
<table class="summary-table">
    <thead>
        <tr>
            <th style="width: 28%;">Net Revenue</th>
            <th style="width: 24%;">Average Sale</th>
            <th style="width: 24%;">Unique Clients</th>
            <th style="width: 24%;">Collected Deposits</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                &#8369;{{ number_format($totalRev, 2) }}
                <span class="metric-sub">{{ $revChangeText }}</span>
            </td>
            <td>
                &#8369;{{ number_format($avgSale, 2) }}
                <span class="metric-sub">Per completed booking</span>
            </td>
            <td>
                {{ number_format($uniqueCustomers) }}
                <span class="metric-sub">Served in period</span>
            </td>
            <td>
                &#8369;{{ number_format($depositsTotal, 2) }}
                <span class="metric-sub">Downpayments received</span>
            </td>
        </tr>
    </tbody>
</table>

<div class="section-title">Appointment Summary</div>
<table class="summary-table">
    <thead>
        <tr>
            <th style="width: 25%;">Total Scheduled</th>
            <th style="width: 25%;">Completed</th>
            <th style="width: 25%;">Cancelled</th>
            <th style="width: 25%;">No-Show</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                {{ number_format($apptTotal) }}
                <span class="metric-sub">All appointments in period</span>
            </td>
            <td>
                {{ number_format($apptCompleted) }}
                <span class="metric-sub">{{ number_format($completionRate, 1) }}% completion rate</span>
            </td>
            <td>
                {{ number_format($apptCancelled) }}
                <span class="metric-sub">{{ number_format($cancellationRate, 1) }}% cancellation rate</span>
            </td>
            <td>
                {{ number_format($apptNoShow) }}
                <span class="metric-sub">{{ number_format($noShowRate, 1) }}% no-show rate</span>
            </td>
        </tr>
    </tbody>
</table>

<div class="section-title">Top Performing Services</div>
@if($servicesList->isNotEmpty())
    <table class="data-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 10%;" class="text-center">Rank</th>
                <th style="width: 52%;">Service Name</th>
                <th style="width: 20%;" class="text-right">Revenue</th>
                <th style="width: 18%;" class="text-right">Revenue Share</th>
            </tr>
        </thead>
        <tbody>
            @php
                $topServicesSum = (float) $servicesList->sum(fn ($val) => is_array($val) ? (float) ($val['revenue'] ?? $val['total'] ?? 0) : (float) $val);
                $serviceRank = 1;
            @endphp
            @foreach($servicesList as $svcKey => $svcVal)
                @php
                    $svcName = is_array($svcVal) ? ($svcVal['name'] ?? $svcKey) : (string) $svcKey;
                    $svcRevenue = is_array($svcVal) ? (float) ($svcVal['revenue'] ?? $svcVal['total'] ?? 0) : (float) $svcVal;
                    $svcShareBase = $totalRev > 0 ? $totalRev : $topServicesSum;
                    $svcShare = $svcShareBase > 0 ? ($svcRevenue / $svcShareBase) * 100 : 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $serviceRank++ }}</td>
                    <td class="bold">{{ $svcName }}</td>
                    <td class="text-right">&#8369;{{ number_format($svcRevenue, 2) }}</td>
                    <td class="text-right">{{ number_format($svcShare, 1) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty-state">No service revenue data recorded for this period.</div>
@endif

<div class="section-title">Staff Performance</div>
@if($staffPerfList->isNotEmpty())
    <table class="data-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 7%;" class="text-center">Rank</th>
                <th style="width: 23%;">Staff Member</th>
                <th style="width: 10%;" class="text-right">Appts</th>
                <th style="width: 11%;" class="text-right">Completed</th>
                <th style="width: 10%;" class="text-right">Services</th>
                <th style="width: 11%;" class="text-right">Rate</th>
                <th style="width: 14%;" class="text-right">Revenue</th>
                <th style="width: 14%;" class="text-right">Commission</th>
            </tr>
        </thead>
        <tbody>
            @foreach($staffPerfList as $index => $member)
                @php
                    $mRank = data_get($member, 'rank', $index + 1);
                    $mName = data_get($member, 'name', 'Unknown');
                    $mAppts = (int) data_get($member, 'appointments', 0);
                    $mCompleted = (int) data_get($member, 'completed', 0);
                    $mServices = (int) data_get($member, 'services', 0);
                    $mRate = (float) data_get($member, 'completion_rate', 0);
                    $mRev = (float) data_get($member, 'revenue', 0);
                    $mComm = (float) data_get($member, 'commission', 0);
                @endphp
                <tr>
                    <td class="text-center">{{ $mRank }}</td>
                    <td class="bold">{{ $mName }}</td>
                    <td class="text-right">{{ number_format($mAppts) }}</td>
                    <td class="text-right">{{ number_format($mCompleted) }}</td>
                    <td class="text-right">{{ number_format($mServices) }}</td>
                    <td class="text-right">{{ number_format($mRate, 1) }}%</td>
                    <td class="text-right">&#8369;{{ number_format($mRev, 2) }}</td>
                    <td class="text-right">&#8369;{{ number_format($mComm, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total Staff ({{ $staffPerfList->count() }})</td>
                <td class="text-right">{{ number_format($staffPerfList->sum(fn ($m) => (int) data_get($m, 'appointments', 0))) }}</td>
                <td class="text-right">{{ number_format($staffPerfList->sum(fn ($m) => (int) data_get($m, 'completed', 0))) }}</td>
                <td class="text-right">{{ number_format($staffPerfList->sum(fn ($m) => (int) data_get($m, 'services', 0))) }}</td>
                <td class="text-right">—</td>
                <td class="text-right">&#8369;{{ number_format($staffPerfList->sum(fn ($m) => (float) data_get($m, 'revenue', 0)), 2) }}</td>
                <td class="text-right">&#8369;{{ number_format($staffPerfList->sum(fn ($m) => (float) data_get($m, 'commission', 0)), 2) }}</td>
            </tr>
        </tfoot>
    </table>
@elseif($fallbackTopStaff->isNotEmpty())
    <table class="data-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 12%;" class="text-center">Rank</th>
                <th style="width: 58%;">Staff Member</th>
                <th style="width: 30%;" class="text-right">Revenue</th>
            </tr>
        </thead>
        <tbody>
            @php $fallbackRank = 1; @endphp
            @foreach($fallbackTopStaff as $staffName => $staffRevenue)
                <tr>
                    <td class="text-center">{{ $fallbackRank++ }}</td>
                    <td class="bold">{{ $staffName }}</td>
                    <td class="text-right">&#8369;{{ number_format((float) $staffRevenue, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty-state">No staff performance data recorded for this period.</div>
@endif

<div class="section-title">Therapist Retention &amp; Client Loyalty</div>
@if($retentionList->isNotEmpty())
    <table class="data-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 28%;">Therapist</th>
                <th style="width: 15%;" class="text-right">Completed</th>
                <th style="width: 15%;" class="text-right">Unique Clients</th>
                <th style="width: 14%;" class="text-right">Repeat Clients</th>
                <th style="width: 14%;" class="text-right">New Clients</th>
                <th style="width: 14%;" class="text-right">Retention Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($retentionList as $row)
                <tr>
                    <td class="bold">{{ data_get($row, 'therapist_name', 'Unknown Staff') }}</td>
                    <td class="text-right">{{ number_format((int) data_get($row, 'total_appointments', 0)) }}</td>
                    <td class="text-right">{{ number_format((int) data_get($row, 'unique_clients', 0)) }}</td>
                    <td class="text-right">{{ number_format((int) data_get($row, 'repeat_clients', 0)) }}</td>
                    <td class="text-right">{{ number_format((int) data_get($row, 'new_clients', 0)) }}</td>
                    <td class="text-right bold">{{ number_format((float) data_get($row, 'retention_rate', 0), 1) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty-state">No therapist retention data available for this period.</div>
@endif

<div class="section-title">Peak Business Hours</div>
@if($hourRows->isNotEmpty())
    <table class="data-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 25%;">Hour Window</th>
                <th style="width: 25%;" class="text-right">Appointments</th>
                <th style="width: 25%;" class="text-right">Share of Total</th>
                <th style="width: 25%;">Classification</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalHourCount = max(1, (int) $hourRows->sum('count'));
            @endphp
            @foreach($hourRows as $hourRow)
                @php
                    $isPeak = !empty($hourRow['is_peak']);
                    $cnt = (int) ($hourRow['count'] ?? 0);
                    $sharePct = ($cnt / $totalHourCount) * 100;
                @endphp
                <tr class="{{ $isPeak ? 'peak-row' : '' }}">
                    <td>{{ $hourRow['label'] ?? '' }}</td>
                    <td class="text-right">{{ number_format($cnt) }}</td>
                    <td class="text-right">{{ number_format($sharePct, 1) }}%</td>
                    <td>{{ $isPeak ? 'Peak Hour' : 'Standard' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Peak Window(s): {{ $peakSummaryLabel }} ({{ number_format($peakMaxCount) }} appointments)</td>
            </tr>
        </tfoot>
    </table>
@else
    <div class="empty-state">No hourly booking distribution recorded for this period.</div>
@endif

<div class="section-title">Payment Method Breakdown</div>
@if($methodRows->isNotEmpty())
    <table class="data-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 40%;">Payment Method</th>
                <th style="width: 20%;" class="text-right">Transactions</th>
                <th style="width: 22%;" class="text-right">Total Amount</th>
                <th style="width: 18%;" class="text-right">Share</th>
            </tr>
        </thead>
        <tbody>
            @foreach($methodRows as $methodName => $methodData)
                @php
                    $mCount = (int) data_get($methodData, 'count', 0);
                    $mTotal = (float) data_get($methodData, 'total', 0);
                    $mShare = $methodGrandTotal > 0 ? ($mTotal / $methodGrandTotal) * 100 : 0;
                @endphp
                <tr>
                    <td class="bold">{{ ucwords(str_replace('_', ' ', (string) $methodName)) }}</td>
                    <td class="text-right">{{ number_format($mCount) }}</td>
                    <td class="text-right">&#8369;{{ number_format($mTotal, 2) }}</td>
                    <td class="text-right">{{ number_format($mShare, 1) }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total Collected</td>
                <td class="text-right">{{ number_format($methodRows->sum(fn ($item) => (int) data_get($item, 'count', 0))) }}</td>
                <td class="text-right">&#8369;{{ number_format($methodGrandTotal, 2) }}</td>
                <td class="text-right">100.0%</td>
            </tr>
        </tfoot>
    </table>
@else
    <div class="empty-state">No payment method breakdown recorded for this period.</div>
@endif

{{-- ============================================================
     Appointment Activity Heatmap: Top Demand Slots
     ============================================================ --}}
@php
    $heatSlots    = collect($heatmapTopSlots ?? []);
    $heatMaxCount = (int) ($heatmapDemandMax  ?? 0);
    $heatTotal    = (int) ($heatmapDemandTotal ?? 0);
    $heatDays     = is_array($heatmapDays ?? null) ? $heatmapDays : [];
    $heatHours    = is_array($heatmapHours ?? null) ? $heatmapHours : [];
    $heatMatrix   = is_array($heatmapMatrix ?? null) ? $heatmapMatrix : [];
@endphp
<div class="section-title">Appointment Demand Heatmap — Day &times; Hour Matrix &amp; Top Peak Slots</div>
@if(!empty($heatMatrix) && !empty($heatDays) && $heatTotal > 0)
    <table class="data-table" style="width: 100%; table-layout: fixed; margin-bottom: 4mm; page-break-inside: avoid;">
        <thead>
            <tr>
                <th style="width: 9%; font-size: 6.2pt; padding: 2px 3px;">Day \ Hr</th>
                @foreach($heatHours as $hIdx => $hLabel)
                    <th style="text-align: center; font-size: 5.8pt; padding: 2px 1px;">{{ substr((string) $hLabel, 0, 2) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($heatDays as $dIdx => $dName)
                <tr>
                    <td class="bold" style="font-size: 6.5pt; padding: 2px 3px; background: #f3f4f6;">{{ substr((string) $dName, 0, 3) }}</td>
                    @foreach($heatHours as $hIdx => $hLabel)
                        @php
                            $cnt = (int) ($heatMatrix[$dIdx][$hIdx] ?? 0);
                            $ratio = $heatMaxCount > 0 ? ($cnt / $heatMaxCount) : 0;
                            if ($cnt === 0) {
                                $bg = '#f9fafb';
                                $fg = '#9ca3af';
                                $fw = 'normal';
                            } elseif ($cnt === $heatMaxCount) {
                                $bg = '#0d9488';
                                $fg = '#ffffff';
                                $fw = 'bold';
                            } elseif ($ratio > 0.75) {
                                $bg = '#14b8a6';
                                $fg = '#ffffff';
                                $fw = 'bold';
                            } elseif ($ratio > 0.50) {
                                $bg = '#5eead4';
                                $fg = '#134e4a';
                                $fw = 'normal';
                            } elseif ($ratio > 0.25) {
                                $bg = '#99f6e4';
                                $fg = '#0f766e';
                                $fw = 'normal';
                            } else {
                                $bg = '#ccfbf1';
                                $fg = '#0f766e';
                                $fw = 'normal';
                            }
                        @endphp
                        <td style="text-align: center; font-size: 5.8pt; padding: 2px 1px; background-color: {{ $bg }}; color: {{ $fg }}; font-weight: {{ $fw }};">{{ $cnt > 0 ? $cnt : '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="font-size: 6.8pt; color: #4b5563; margin: 0 0 3mm 0;">
        <strong>Intensity Legend:</strong>
        <span style="display:inline-block;padding:1px 4px;background:#f9fafb;border:1px solid #d1d5db;color:#6b7280;">0</span>
        <span style="display:inline-block;padding:1px 4px;background:#ccfbf1;border:1px solid #d1d5db;color:#0f766e;">Low</span>
        <span style="display:inline-block;padding:1px 4px;background:#99f6e4;border:1px solid #d1d5db;color:#0f766e;">Mod</span>
        <span style="display:inline-block;padding:1px 4px;background:#5eead4;border:1px solid #d1d5db;color:#134e4a;">Med-High</span>
        <span style="display:inline-block;padding:1px 4px;background:#14b8a6;border:1px solid #d1d5db;color:#ffffff;">High</span>
        <span style="display:inline-block;padding:1px 4px;background:#0d9488;border:1px solid #d1d5db;color:#ffffff;font-weight:bold;">Peak ({{ number_format($heatMaxCount) }})</span>
        &nbsp;&bull;&nbsp; Hours in 24h format (Asia/Manila).
    </p>
@endif
@if($heatSlots->isNotEmpty())
    <table class="data-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 25%;">Day of Week</th>
                <th style="width: 25%;">Hour Window</th>
                <th style="width: 25%;" class="text-right">Appointments</th>
                <th style="width: 25%;" class="text-right">% of Period Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($heatSlots as $slot)
                @php
                    $slotShare = $heatTotal > 0 ? round(($slot['count'] / $heatTotal) * 100, 1) : 0;
                    $isPeak    = $heatMaxCount > 0 && $slot['count'] === $heatMaxCount;
                @endphp
                <tr class="{{ $isPeak ? 'peak-row' : '' }}">
                    <td class="bold">{{ $slot['day'] }}</td>
                    <td>{{ $slot['hour'] }}</td>
                    <td class="text-right">{{ number_format($slot['count']) }}</td>
                    <td class="text-right">{{ number_format($slotShare, 1) }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Period Total (all scheduled slots)</td>
                <td class="text-right">{{ number_format($heatTotal) }}</td>
                <td class="text-right">100.0%</td>
            </tr>
        </tfoot>
    </table>
    <p style="font-size:7pt;color:#6b7280;margin:2mm 0;">
        Heatmap counts completed, confirmed, and valid pending appointments by their scheduled day and start hour.
        Cancelled and expired pending appointments are excluded.
    </p>
@else
    <div class="empty-state">No demand heatmap data available for this period.</div>
@endif

{{-- ============================================================
     Customer Retention Summary
     ============================================================ --}}
@php
    $custRet = $customerRetention ?? [];
    $crServed       = (int) ($custRet['customers_served']            ?? 0);
    $crRegistered   = (int) ($custRet['registered_customers']        ?? 0);
    $crGuests       = (int) ($custRet['guest_customers']             ?? 0);
    $crUnidentified = (int) ($custRet['unidentified_guest_bookings'] ?? 0);
    $crReturning    = (int) ($custRet['returning_customers']         ?? 0);
    $crNew          = (int) ($custRet['new_customers']               ?? 0);
    $crRepeatVisits = (int) ($custRet['repeat_visits']               ?? 0);
    $crRate         = (float) ($custRet['retention_rate']            ?? 0);
    $crNewRate      = $crServed > 0 ? round(($crNew / $crServed) * 100, 1) : 0.0;
@endphp
<div class="section-title">Customer Retention Summary</div>
@if($crServed > 0 || $crUnidentified > 0)
    <table class="data-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 48%;">Metric</th>
                <th style="width: 26%;" class="text-right">Count</th>
                <th style="width: 26%;" class="text-right">Rate</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="bold">Total Distinct Identifiable Customers Served</td>
                <td class="text-right bold">{{ number_format($crServed) }}</td>
                <td class="text-right">—</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&bull; Registered Customers</td>
                <td class="text-right">{{ number_format($crRegistered) }}</td>
                <td class="text-right">{{ $crServed > 0 ? number_format(($crRegistered / $crServed) * 100, 1) : '0.0' }}%</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;&bull; Identified Guest Customers</td>
                <td class="text-right">{{ number_format($crGuests) }}</td>
                <td class="text-right">{{ $crServed > 0 ? number_format(($crGuests / $crServed) * 100, 1) : '0.0' }}%</td>
            </tr>
            <tr>
                <td>Returning Customers (2+ qualifying completed visits)</td>
                <td class="text-right">{{ number_format($crReturning) }}</td>
                <td class="text-right bold">{{ number_format($crRate, 1) }}%</td>
            </tr>
            <tr>
                <td>New / First-Time Customers</td>
                <td class="text-right">{{ number_format($crNew) }}</td>
                <td class="text-right">{{ number_format($crNewRate, 1) }}%</td>
            </tr>
            <tr>
                <td>Repeat Visits in Period</td>
                <td class="text-right">{{ number_format($crRepeatVisits) }}</td>
                <td class="text-right">—</td>
            </tr>
            @if($crUnidentified > 0)
                <tr>
                    <td>Unidentified Guest Bookings (excluded from rates)</td>
                    <td class="text-right">{{ number_format($crUnidentified) }}</td>
                    <td class="text-right">—</td>
                </tr>
            @endif
        </tbody>
    </table>
    <p style="font-size:7pt;color:#6b7280;margin:2mm 0;">
        Retention rate = returning identifiable customers &divide; total distinct identifiable customers served in period.
        Customer retention is calculated using normalized phone numbers as the primary customer identifier because Spa Alexandria treats phone numbers as unique. Registered customers are identified by their customer records. Guest bookings are grouped by normalized phone number. Guest bookings with missing or unusable phone numbers are reported separately.
    </p>
@else
    <div class="empty-state">No customer retention data available for this period.</div>
@endif

<table class="signature-table">
    <tr>
        <td>
            <div class="signature-label">Prepared by</div>
            <div class="signature-line">{{ $preparedByName }}</div>
            <div class="signature-role">Staff / Administrator</div>
        </td>
        <td>
            <div class="signature-label">Checked by</div>
            <div class="signature-line">{{ $checkedByName ?: '____________________________' }}</div>
            <div class="signature-role">Manager / Owner</div>
        </td>
    </tr>
</table>

<div class="doc-footer">
    ZenFlow Spa Management System &bull; {{ $reportTitle }} &bull; Generated {{ $generatedText }}
</div>
</body>
</html>
