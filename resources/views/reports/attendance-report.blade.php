<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle ?? 'Attendance Report' }}</title>

    @php
        $reportTitle = $reportTitle ?? 'Attendance Report';

        $generatedAt = $generatedAt
            ?? now('Asia/Manila')->format('F j, Y g:i A');

        $startDate = isset($startDate)
            ? \Carbon\Carbon::parse($startDate)
            : now('Asia/Manila')->startOfWeek();

        $endDate = isset($endDate)
            ? \Carbon\Carbon::parse($endDate)
            : now('Asia/Manila')->endOfWeek();

        $search = request('search', '');

        $status = request('status', '');

        $staffId = request('staff_id', '');

        $dateLabel =
            $startDate->isSameDay($endDate)
                ? $startDate->format('F j, Y')
                : $startDate->format('F j, Y')
                    . ' – '
                    . $endDate->format('F j, Y');

        $statusLabel = $status !== ''
            ? ucwords(str_replace('_', ' ', $status))
            : 'All statuses';

        $staffLabel = 'All staff';

        if ($staffId !== '' && isset($allStaff)) {
            $selectedStaff = collect($allStaff)
                ->firstWhere('id', $staffId);

            if ($selectedStaff) {
                $staffLabel =
                    $selectedStaff->full_name
                    ?? trim(
                        ($selectedStaff->first_name ?? '')
                        . ' '
                        . ($selectedStaff->last_name ?? '')
                    );
            }
        }

        $searchLabel = $search !== ''
            ? $search
            : 'None';

        $records = $attendances instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $attendances->getCollection()
            : collect($attendances ?? []);

        $totalAttendance = $records->count();

        $present = (int) ($summary['present'] ?? 0);

        $late = (int) ($summary['late'] ?? 0);

        $onLeave = (int) ($summary['on_leave'] ?? 0);

        $absent = (int) ($summary['absent'] ?? 0);

        $dayOff = (int) ($summary['day_off'] ?? 0);

        $holiday = (int) ($summary['holiday'] ?? 0);

        $workedHours = (float) ($summary['worked_hours'] ?? 0);

        $overtimeHours = (float) ($summary['overtime'] ?? 0);

        $preparedBy = $preparedBy
            ?? (
                auth()->check()
                    ? trim(
                        auth()->user()->first_name
                        . ' '
                        . auth()->user()->last_name
                    )
                    : 'System'
            );

        $checkedByName = !empty($checkedBy) ? $checkedBy : null;

        $absenceRows = collect($absenceRecords ?? []);
        $exceptionRows = collect($exceptionRecords ?? []);
        $overtimeSummaryRows = collect($staffOvertimeSummary ?? []);

        $generatedText = $generatedAt instanceof \Carbon\CarbonInterface
            ? $generatedAt->format('F j, Y g:i A')
            : (string) $generatedAt;
    @endphp

    <style>
        @page {
            size: A4 landscape;
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
        .attendance,
        .hours-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 8px;
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
            page-break-inside: avoid;
        }

        .summary-table th {
            background: #f3f4f6;
            color: #111111;
            border: 1px solid #d1d5db;
            border-bottom: 1.5px solid #111111;
            padding: 4px 3px;
            font-size: 6.8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            text-align: center;
        }

        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 5px 3px;
            font-size: 9.2px;
            font-weight: bold;
            text-align: center;
            color: #000000;
        }

        .attendance thead,
        .hours-table thead {
            display: table-header-group;
        }

        .attendance tr,
        .hours-table tr {
            page-break-inside: avoid;
        }

        .attendance th,
        .hours-table th {
            background: #f3f4f6;
            color: #111111;
            border: 1px solid #d1d5db;
            border-bottom: 1.5px solid #111111;
            padding: 5px 5px;
            font-size: 7.4px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
        }

        .attendance td,
        .hours-table td {
            border: 1px solid #d1d5db;
            padding: 5px 5px;
            font-size: 8px;
            color: #000000;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .attendance tbody tr:nth-child(even),
        .hours-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .hours-table tfoot td {
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
            font-size: 7.2px;
        }

        .status-text {
            font-weight: bold;
            font-size: 7.5px;
            text-transform: uppercase;
            color: #000000;
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
@include('partials.report_header', [
    'reportTitle' => $reportTitle,
    'dateRange' => $dateLabel,
    'referenceNumber' => $referenceNumber ?? null,
    'generatedAt' => $generatedText,
    'preparedBy' => $preparedBy,
])

<table class="filter-table">
    <tr>
        <td style="width: 30%;">
            <span class="filter-label">Date Range</span>
            <span class="filter-value">{{ $dateLabel }}</span>
        </td>
        <td style="width: 22%;">
            <span class="filter-label">Status Filter</span>
            <span class="filter-value">{{ $statusLabel }}</span>
        </td>
        <td style="width: 26%;">
            <span class="filter-label">Staff Filter</span>
            <span class="filter-value">{{ $staffLabel }}</span>
        </td>
        <td style="width: 22%;">
            <span class="filter-label">Search Query</span>
            <span class="filter-value">{{ $searchLabel }}</span>
        </td>
    </tr>
</table>

<table class="summary-table">
    <thead>
        <tr>
            <th>Present</th>
            <th>Late</th>
            <th>Absent</th>
            <th>On Leave</th>
            <th>Day Off</th>
            <th>Holiday</th>
            <th>Worked Hrs</th>
            <th>Overtime Hrs</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ number_format($present) }}</td>
            <td>{{ number_format($late) }}</td>
            <td>{{ number_format($absent) }}</td>
            <td>{{ number_format($onLeave) }}</td>
            <td>{{ number_format($dayOff) }}</td>
            <td>{{ number_format($holiday) }}</td>
            <td>{{ number_format($workedHours, 2) }}</td>
            <td>{{ number_format($overtimeHours, 2) }}</td>
        </tr>
    </tbody>
</table>

<div class="section-title">Attendance Details ({{ number_format($totalAttendance) }} records)</div>
@if($records->isNotEmpty())
    <table class="attendance pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 20%;">Staff Member</th>
                <th style="width: 11%;">Status</th>
                <th style="width: 11%;">Time In</th>
                <th style="width: 11%;">Time Out</th>
                <th style="width: 10%;" class="text-right">Worked</th>
                <th style="width: 10%;" class="text-right">Overtime</th>
                <th style="width: 15%;">Marked By / Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $att)
                @php
                    $staffUser = $att->user ?? null;
                    $staffName = $staffUser
                        ? ($staffUser->full_name ?? trim(($staffUser->first_name ?? '') . ' ' . ($staffUser->last_name ?? '')))
                        : 'N/A';
                    $rolesText = $staffUser && $staffUser->roles
                        ? $staffUser->roles->pluck('name')->map(fn ($r) => ucfirst($r))->implode(', ')
                        : '';
                    $attDate = $att->date
                        ? \Carbon\Carbon::parse($att->date)->format('M j, Y')
                        : 'N/A';
                    $statusText = ucwords(str_replace('_', ' ', (string) ($att->status ?? 'unknown')));

                    $checkInText = '—';
                    if (!empty($att->check_in)) {
                        try {
                            $checkInText = \Carbon\Carbon::parse($att->check_in)->format('g:i A');
                        } catch (\Throwable $e) {
                            $checkInText = (string) $att->check_in;
                        }
                    }

                    $checkOutText = '—';
                    if (!empty($att->check_out)) {
                        try {
                            $checkOutText = \Carbon\Carbon::parse($att->check_out)->format('g:i A');
                        } catch (\Throwable $e) {
                            $checkOutText = (string) $att->check_out;
                        }
                    }

                    $rowWorked = (float) ($att->worked_hours ?? 0);
                    $rowOvertime = (float) ($att->overtime_hours ?? 0);
                    $markerName = $att->marker->full_name ?? ($att->marker->name ?? 'System');
                    $noteText = trim((string) ($att->notes ?? ''));
                @endphp
                <tr>
                    <td class="bold">{{ $attDate }}</td>
                    <td>
                        <div class="bold">{{ $staffName }}</div>
                        @if($rolesText !== '')
                            <div class="muted">{{ $rolesText }}</div>
                        @endif
                    </td>
                    <td><span class="status-text">{{ $statusText }}</span></td>
                    <td>{{ $checkInText }}</td>
                    <td>{{ $checkOutText }}</td>
                    <td class="text-right">{{ number_format($rowWorked, 2) }}h</td>
                    <td class="text-right">{{ number_format($rowOvertime, 2) }}h</td>
                    <td>
                        <div>{{ $markerName }}</div>
                        @if($noteText !== '')
                            <div class="muted">{{ $noteText }}</div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty-state">No attendance logs matched the selected filters.</div>
@endif

@if($absenceRows->isNotEmpty())
    <div class="section-title">Absence Records ({{ number_format($absenceRows->count()) }})</div>
    <table class="hours-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 18%;">Date</th>
                <th style="width: 34%;">Staff Member</th>
                <th style="width: 20%;">Status</th>
                <th style="width: 28%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($absenceRows as $row)
                @php
                    $rowUser = data_get($row, 'user');
                    $rowStaffName = $rowUser
                        ? ($rowUser->full_name ?? trim(($rowUser->first_name ?? '') . ' ' . ($rowUser->last_name ?? '')))
                        : 'N/A';
                    $rowDateVal = data_get($row, 'date');
                    $rowDateText = $rowDateVal
                        ? \Carbon\Carbon::parse($rowDateVal)->format('M j, Y (D)')
                        : 'N/A';
                    $rowLabel = data_get($row, 'label', 'Absent');
                    $rowReason = data_get($row, 'reason') ?: 'Scheduled shift with no check-in record';
                @endphp
                <tr>
                    <td class="bold">{{ $rowDateText }}</td>
                    <td>{{ $rowStaffName }}</td>
                    <td class="status-text">{{ $rowLabel }}</td>
                    <td class="muted">{{ $rowReason }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if($exceptionRows->isNotEmpty())
    <div class="section-title">Leave, Day-Off &amp; Holiday Records ({{ number_format($exceptionRows->count()) }})</div>
    <table class="hours-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 18%;">Date</th>
                <th style="width: 32%;">Staff Member</th>
                <th style="width: 20%;">Classification</th>
                <th style="width: 30%;">Reason / Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($exceptionRows as $row)
                @php
                    $rowUser = data_get($row, 'user');
                    $rowStaffName = $rowUser
                        ? ($rowUser->full_name ?? trim(($rowUser->first_name ?? '') . ' ' . ($rowUser->last_name ?? '')))
                        : 'N/A';
                    $rowDateVal = data_get($row, 'date');
                    $rowDateText = $rowDateVal
                        ? \Carbon\Carbon::parse($rowDateVal)->format('M j, Y (D)')
                        : 'N/A';
                    $rowLabel = data_get($row, 'label', 'Exception');
                    $rowReason = data_get($row, 'reason') ?: '—';
                @endphp
                <tr>
                    <td class="bold">{{ $rowDateText }}</td>
                    <td>{{ $rowStaffName }}</td>
                    <td class="status-text">{{ $rowLabel }}</td>
                    <td>{{ $rowReason }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if($overtimeSummaryRows->isNotEmpty())
    <div class="section-title">Staff Overtime &amp; Hours Breakdown</div>
    <table class="hours-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 36%;">Staff Member</th>
                <th style="width: 16%;" class="text-right">Days Worked</th>
                <th style="width: 16%;" class="text-right">Scheduled Hrs</th>
                <th style="width: 16%;" class="text-right">Worked Hrs</th>
                <th style="width: 16%;" class="text-right">Overtime Hrs</th>
            </tr>
        </thead>
        <tbody>
            @foreach($overtimeSummaryRows as $row)
                <tr>
                    <td class="bold">{{ data_get($row, 'name', 'N/A') }}</td>
                    <td class="text-right">{{ number_format((int) data_get($row, 'days_worked', 0)) }}</td>
                    <td class="text-right">{{ number_format((float) data_get($row, 'scheduled_hours', 0), 2) }}</td>
                    <td class="text-right">{{ number_format((float) data_get($row, 'worked_hours', 0), 2) }}</td>
                    <td class="text-right bold">{{ number_format((float) data_get($row, 'overtime_hours', 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total ({{ $overtimeSummaryRows->count() }} staff)</td>
                <td class="text-right">{{ number_format($overtimeSummaryRows->sum(fn ($r) => (int) data_get($r, 'days_worked', 0))) }}</td>
                <td class="text-right">{{ number_format($overtimeSummaryRows->sum(fn ($r) => (float) data_get($r, 'scheduled_hours', 0)), 2) }}</td>
                <td class="text-right">{{ number_format($overtimeSummaryRows->sum(fn ($r) => (float) data_get($r, 'worked_hours', 0)), 2) }}</td>
                <td class="text-right">{{ number_format($overtimeSummaryRows->sum(fn ($r) => (float) data_get($r, 'overtime_hours', 0)), 2) }}</td>
            </tr>
        </tfoot>
    </table>
@endif

<table class="signature-table">
    <tr>
        <td>
            <div class="signature-label">Prepared by</div>
            <div class="signature-line">{{ $preparedBy }}</div>
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
    ZenFlow Spa Management System &bull; {{ $reportTitle }} &bull; Generated {{ $generatedText }}
</div>
</body>
</html>
