<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle ?? 'Appointment Report' }}</title>

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

        .filter-table,
        .summary-table,
        .appointments,
        .snapshot-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .filter-table {
            margin: 0 0 8px;
        }

        .filter-table td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            font-size: 8px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .filter-label {
            display: block;
            font-size: 6.8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #4b5563;
            margin-bottom: 1px;
        }

        .filter-value {
            font-weight: bold;
            color: #000000;
        }

        .summary-table {
            margin: 0 0 10px;
            page-break-inside: avoid;
        }

        .summary-table th {
            border: 1px solid #d1d5db;
            border-bottom: 1.5px solid #111111;
            background: #f3f4f6;
            padding: 4px 5px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: center;
            color: #111111;
        }

        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 5px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            color: #000000;
        }

        .appointments {
            margin-bottom: 10px;
        }

        .appointments thead {
            display: table-header-group;
        }

        .appointments tr {
            page-break-inside: avoid;
        }

        .appointments th {
            background: #f3f4f6;
            color: #111111;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
            padding: 5px 5px;
            border: 1px solid #d1d5db;
            border-bottom: 1.5px solid #111111;
        }

        .appointments td {
            padding: 5px 5px;
            font-size: 8px;
            border: 1px solid #d1d5db;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .appointments tbody tr:nth-child(even) {
            background: #f9fafb;
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
            font-size: 7.2px;
        }

        .status-text {
            font-weight: bold;
            font-size: 7.5px;
            text-transform: uppercase;
            color: #000000;
        }

        .snapshot-table {
            margin-top: 6px;
            page-break-inside: avoid;
        }

        .snapshot-table th {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-bottom: 1.5px solid #111111;
            padding: 5px 6px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: right;
            color: #111111;
        }

        .snapshot-table th:first-child {
            text-align: left;
        }

        .snapshot-table td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            font-size: 8.5px;
            font-weight: bold;
            text-align: right;
            color: #000000;
        }

        .snapshot-table td:first-child {
            text-align: left;
            font-weight: normal;
            color: #111111;
        }

        .empty-state {
            border: 1px solid #d1d5db;
            padding: 14px;
            text-align: center;
            color: #4b5563;
            font-size: 8.5px;
            margin-bottom: 10px;
        }

        .signature-table {
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .signature-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 24px;
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
    $reportTitle = $reportTitle ?? 'Appointment Report';
    $dateLabel = $dateLabel ?? 'All Dates';
    $statusLabel = $statusLabel ?? 'All Statuses';
    $staffLabel = $staffLabel ?? 'All Staff';
    $searchText = !empty($search) ? $search : 'None';

    $totalAppointments = (int) ($totalAppointments ?? 0);
    $completed = (int) ($completed ?? 0);
    $confirmed = (int) ($confirmed ?? 0);
    $pending = (int) ($pending ?? 0);
    $cancelled = (int) ($cancelled ?? 0);
    $noShow = (int) ($noShow ?? 0);

    $recordedValue = (float) ($recordedValue ?? 0);
    $paidValue = (float) ($paidValue ?? 0);
    $outstandingValue = (float) ($outstandingValue ?? 0);

    $preparedByName = $preparedBy ?? 'Administrator';
    $checkedByName = !empty($checkedBy) ? $checkedBy : null;
    $generatedText = $generatedAt instanceof \Carbon\CarbonInterface
        ? $generatedAt->format('F j, Y g:i A')
        : ($generatedAt ?? now()->format('F j, Y g:i A'));
@endphp

@include('partials.report_header', [
    'reportTitle' => $reportTitle,
    'dateRange' => $dateLabel,
    'generatedAt' => $generatedText,
    'preparedBy' => $preparedByName,
])

<table class="filter-table">
    <tr>
        <td style="width: 28%;">
            <span class="filter-label">Date Range</span>
            <span class="filter-value">{{ $dateLabel }}</span>
        </td>
        <td style="width: 22%;">
            <span class="filter-label">Status Filter</span>
            <span class="filter-value">{{ $statusLabel }}</span>
        </td>
        <td style="width: 25%;">
            <span class="filter-label">Staff Filter</span>
            <span class="filter-value">{{ $staffLabel }}</span>
        </td>
        <td style="width: 25%;">
            <span class="filter-label">Search Query</span>
            <span class="filter-value">{{ $searchText }}</span>
        </td>
    </tr>
</table>

<table class="summary-table">
    <thead>
        <tr>
            <th>Total</th>
            <th>Completed</th>
            <th>Confirmed</th>
            <th>Pending</th>
            <th>Cancelled</th>
            <th>No-Show</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ number_format($totalAppointments) }}</td>
            <td>{{ number_format($completed) }}</td>
            <td>{{ number_format($confirmed) }}</td>
            <td>{{ number_format($pending) }}</td>
            <td>{{ number_format($cancelled) }}</td>
            <td>{{ number_format($noShow) }}</td>
        </tr>
    </tbody>
</table>

<div class="section-title">Appointment Schedule &amp; Billing Register</div>

@if(isset($appointments) && count($appointments) > 0)
    <table class="appointments pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 12%;">Date / Time</th>
                <th style="width: 16%;">Client</th>
                <th style="width: 20%;">Services</th>
                <th style="width: 16%;">Staff &amp; Room</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 16%;" class="text-right">Billing</th>
                <th style="width: 10%;">Ref / Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($appointments as $appointment)
                @php
                    $aptDate = $appointment->appointment_date
                        ? \Carbon\Carbon::parse($appointment->appointment_date)->format('M j, Y')
                        : 'N/A';

                    $startText = $appointment->start_time
                        ? \Carbon\Carbon::parse($appointment->start_time)->format('g:i A')
                        : '';
                    $endText = $appointment->end_time
                        ? \Carbon\Carbon::parse($appointment->end_time)->format('g:i A')
                        : '';
                    $timeText = trim($startText . ($endText ? ' - ' . $endText : ''));

                    $clientName = $appointment->client_name
                        ?? ($appointment->customer->full_name ?? $appointment->guest_name ?? 'Guest');
                    $clientPhone = $appointment->customer->phone_number
                        ?? $appointment->guest_phone
                        ?? null;

                    $serviceNames = $appointment->services
                        ? $appointment->services->pluck('name')->filter()->implode(', ')
                        : '';

                    if ($appointment->assignedStaff && $appointment->assignedStaff->isNotEmpty()) {
                        $staffNames = $appointment->assignedStaff
                            ->map(fn ($member) => $member->full_name ?? $member->name)
                            ->filter()
                            ->implode(', ');
                    } else {
                        $staffNames = $appointment->staff
                            ? ($appointment->staff->full_name ?? $appointment->staff->name)
                            : 'Unassigned';
                    }

                    $roomName = $appointment->room->name ?? 'Unassigned';

                    $rawStatus = (string) ($appointment->status ?? '');
                    $isNoShow = $rawStatus === 'no_show'
                        || ($rawStatus === 'cancelled' && ($appointment->cancellation_reason ?? null) === 'customer_no_show');
                    $displayStatus = $isNoShow
                        ? 'No-Show'
                        : ucwords(str_replace('_', ' ', $rawStatus ?: 'Unknown'));

                    $rowTotal = (float) ($appointment->total_price ?? 0);
                    $rowPaid = $appointment->payments
                        ? (float) $appointment->payments->sum('amount')
                        : 0.0;
                    $rowBalance = (float) ($appointment->active_outstanding_balance ?? max(0, $rowTotal - $rowPaid));
                    $paymentLabel = $rowPaid >= $rowTotal && $rowTotal > 0
                        ? 'Paid'
                        : ($rowPaid > 0 ? 'Partial' : 'Unpaid');

                    $reasonText = $appointment->cancellation_reason
                        ? ucwords(str_replace('_', ' ', $appointment->cancellation_reason))
                        : ($appointment->no_show_notes ?? null);
                    $refText = '#' . ($appointment->reference_number ?? $appointment->id ?? '-');
                @endphp
                <tr>
                    <td>
                        <div class="bold">{{ $aptDate }}</div>
                        @if($timeText !== '')
                            <div class="muted">{{ $timeText }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="bold">{{ $clientName }}</div>
                        @if($clientPhone)
                            <div class="muted">{{ $clientPhone }}</div>
                        @endif
                    </td>
                    <td>{{ $serviceNames !== '' ? $serviceNames : '—' }}</td>
                    <td>
                        <div>{{ $staffNames }}</div>
                        <div class="muted">Room: {{ $roomName }}</div>
                    </td>
                    <td>
                        <div class="status-text">{{ $displayStatus }}</div>
                        @if($reasonText)
                            <div class="muted">{{ $reasonText }}</div>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="bold">&#8369;{{ number_format($rowTotal, 2) }}</div>
                        <div class="muted">Paid: &#8369;{{ number_format($rowPaid, 2) }}</div>
                        @if($rowBalance > 0.009)
                            <div class="muted">Bal: &#8369;{{ number_format($rowBalance, 2) }}</div>
                        @else
                            <div class="muted">{{ $paymentLabel }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="bold">{{ $refText }}</div>
                        @if(!empty($appointment->notes))
                            <div class="muted">{{ \Illuminate\Support\Str::limit($appointment->notes, 60) }}</div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty-state">
        No appointments matched the selected filters.
    </div>
@endif

<div class="section-title">Payment Snapshot</div>
<table class="snapshot-table">
    <thead>
        <tr>
            <th style="width: 40%;">Summary Metric</th>
            <th style="width: 20%;">Recorded Value</th>
            <th style="width: 20%;">Collected Payments</th>
            <th style="width: 20%;">Outstanding Balance</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="bold">Filtered Appointments Financial Totals ({{ number_format($totalAppointments) }} records)</td>
            <td>&#8369;{{ number_format($recordedValue, 2) }}</td>
            <td>&#8369;{{ number_format($paidValue, 2) }}</td>
            <td>&#8369;{{ number_format($outstandingValue, 2) }}</td>
        </tr>
    </tbody>
</table>

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
            <div class="signature-role">Manager / Supervisor</div>
        </td>
    </tr>
</table>

<div class="doc-footer">
    ZenFlow Spa Management System &bull; Appointment Report &bull; Generated {{ $generatedText }}
</div>
</body>
</html>
