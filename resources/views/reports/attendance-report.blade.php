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

        $absenceRows = collect(
            $absenceRecords ?? []
        );

        $exceptionRows = collect(
            $exceptionRecords ?? []
        );
    @endphp

    <style>
        @page {
            margin: 35px 35px 40px 35px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 10px;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #0f766e;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .brand {
            font-size: 18px;
            font-weight: bold;
            color: #0f766e;
            margin-bottom: 3px;
        }

        .spa-name {
            font-size: 11px;
            color: #4b5563;
        }

        .report-title {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
            margin-top: 14px;
            margin-bottom: 4px;
        }

        .generated {
            font-size: 9px;
            color: #6b7280;
        }

        .filter-box {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
        }

        .filter-title {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 7px;
            color: #374151;
        }

        .filter-table {
            width: 100%;
            border-collapse: collapse;
        }

        .filter-table td {
            width: 25%;
            padding: 3px 5px;
            vertical-align: top;
        }

        .filter-label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
        }

        .filter-value {
            font-size: 9px;
            font-weight: bold;
            color: #111827;
            margin-top: 2px;
        }

        .summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px;
            margin: 0 -4px 15px -4px;
        }

        .summary td {
            border: 1px solid #d1d5db;
            border-radius: 5px;
            padding: 8px;
            width: 12.5%;
            vertical-align: top;
        }

        .summary-label {
            font-size: 7px;
            color: #6b7280;
            text-transform: uppercase;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
            margin-top: 3px;
            color: #111827;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 12px 0 7px 0;
            color: #111827;
        }

        table.attendance {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.attendance thead {
            background: #0f766e;
            color: #ffffff;
        }

        table.attendance th {
            font-size: 8px;
            font-weight: bold;
            text-align: left;
            padding: 7px 5px;
            border: 1px solid #0f766e;
        }

        table.attendance td {
            font-size: 8px;
            padding: 6px 5px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        table.attendance tr:nth-child(even) {
            background: #f9fafb;
        }

        .date {
            font-weight: bold;
            color: #111827;
        }

        .muted {
            color: #6b7280;
        }

        .status {
            display: inline-block;
            padding: 3px 5px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
        }

        .status-present {
            background: #d1fae5;
            color: #065f46;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-checked-in {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-late {
            background: #fef3c7;
            color: #92400e;
        }

        .status-on-leave {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .status-absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-day-off {
            background: #e5e7eb;
            color: #374151;
        }

        .status-holiday {
            background: #cffafe;
            color: #155e75;
        }

        .status-default {
            background: #f3f4f6;
            color: #374151;
        }

        .hours-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .hours-table th {
            background: #0f766e;
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            text-align: left;
            padding: 7px;
            border: 1px solid #0f766e;
        }

        .hours-table td {
            border: 1px solid #d1d5db;
            padding: 7px;
            font-size: 8px;
        }

        .hours-table tr:nth-child(even) {
            background: #f9fafb;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            border: 1px solid #d1d5db;
            padding: 7px;
        }

        .total-label {
            color: #6b7280;
        }

        .total-value {
            text-align: right;
            font-weight: bold;
        }

        .footer {
            border-top: 1px solid #d1d5db;
            margin-top: 20px;
            padding-top: 8px;
            font-size: 8px;
            color: #6b7280;
        }

        .empty {
            text-align: center;
            padding: 25px;
            border: 1px solid #d1d5db;
            color: #6b7280;
            margin-bottom: 15px;
        }

        .page-break {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="header">

        <div class="brand">
            ZenFlow System
        </div>

        <div class="spa-name">
            Spa Alexandria
        </div>

        <div class="report-title">
            {{ $reportTitle }}
        </div>

        <div class="generated">
            Generated: {{ $generatedAt }}
        </div>

    </div>


    {{-- FILTERS --}}
    <div class="filter-box">

        <div class="filter-title">
            Report Filters
        </div>

        <table class="filter-table">

            <tr>

                <td>
                    <div class="filter-label">
                        Date Range
                    </div>

                    <div class="filter-value">
                        {{ $dateLabel }}
                    </div>
                </td>

                <td>
                    <div class="filter-label">
                        Status
                    </div>

                    <div class="filter-value">
                        {{ $statusLabel }}
                    </div>
                </td>

                <td>
                    <div class="filter-label">
                        Staff
                    </div>

                    <div class="filter-value">
                        {{ $staffLabel }}
                    </div>
                </td>

                <td>
                    <div class="filter-label">
                        Search
                    </div>

                    <div class="filter-value">
                        {{ $searchLabel }}
                    </div>
                </td>

            </tr>

        </table>

    </div>


    {{-- SUMMARY --}}
    <table class="summary">

        <tr>

            <td>
                <div class="summary-label">
                    Present
                </div>

                <div class="summary-value">
                    {{ number_format($present) }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Late
                </div>

                <div class="summary-value">
                    {{ number_format($late) }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Absent
                </div>

                <div class="summary-value">
                    {{ number_format($absent) }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Leave
                </div>

                <div class="summary-value">
                    {{ number_format($onLeave) }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Day Off
                </div>

                <div class="summary-value">
                    {{ number_format($dayOff) }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Holiday
                </div>

                <div class="summary-value">
                    {{ number_format($holiday) }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Worked Hours
                </div>

                <div class="summary-value">
                    {{ number_format($workedHours, 2) }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Overtime
                </div>

                <div class="summary-value">
                    {{ number_format($overtimeHours, 2) }}
                </div>
            </td>

        </tr>

    </table>


    {{-- ATTENDANCE DETAILS --}}
    <div class="section-title">
        Attendance Details
    </div>

    @if($records->count())

        <table class="attendance">

            <thead>

                <tr>

                    <th style="width: 10%;">
                        Date
                    </th>

                    <th style="width: 15%;">
                        Staff
                    </th>

                    <th style="width: 10%;">
                        Status
                    </th>

                    <th style="width: 10%;">
                        Check-in
                    </th>

                    <th style="width: 10%;">
                        Check-out
                    </th>

                    <th style="width: 10%;">
                        Sched. Hrs
                    </th>

                    <th style="width: 10%;">
                        Worked Hrs
                    </th>

                    <th style="width: 10%;">
                        Overtime
                    </th>

                    <th style="width: 15%;">
                        Marked By
                    </th>

                </tr>

            </thead>

            <tbody>

                @foreach($records as $attendance)

                    @php

                        $attendanceStatus =
                            strtolower(
                                (string) (
                                    $attendance->status
                                    ?? ''
                                )
                            );

                        $statusText = match ($attendanceStatus) {
                            'present' => 'Present',
                            'completed' => 'Present',
                            'checked_in' => 'Present',
                            'late' => 'Late',
                            default => ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    $attendanceStatus
                                )
                            ),
                        };

                        $statusClass = match ($attendanceStatus) {
                            'present' =>
                                'status-present',

                            'completed' =>
                                'status-completed',

                            'checked_in' =>
                                'status-checked-in',

                            'late' =>
                                'status-late',

                            'on_leave',
                            'leave' =>
                                'status-on-leave',

                            'absent' =>
                                'status-absent',

                            default =>
                                'status-default',
                        };

                        $staffName =
                            $attendance->user->full_name
                            ?? trim(
                                ($attendance->user->first_name ?? '')
                                . ' '
                                . ($attendance->user->last_name ?? '')
                            );

                        if (!$staffName) {
                            $staffName = 'Unknown Staff';
                        }

                        $markedByName = 'System';

                        if ($attendance->marker) {
                            $markedByName =
                                $attendance->marker->full_name
                                ?? trim(
                                    ($attendance->marker->first_name ?? '')
                                    . ' '
                                    . ($attendance->marker->last_name ?? '')
                                );

                            if (!$markedByName) {
                                $markedByName = 'System';
                            }
                        }

                        $attendanceDate = $attendance->date
                            ? \Carbon\Carbon::parse(
                                $attendance->date
                            )->format('M j, Y')
                            : '—';

                        $checkIn = $attendance->check_in
                            ? \Carbon\Carbon::parse(
                                $attendance->check_in
                            )->format('g:i A')
                            : '—';

                        $checkOut = $attendance->check_out
                            ? \Carbon\Carbon::parse(
                                $attendance->check_out
                            )->format('g:i A')
                            : '—';

                        $scheduledHours =
                            (float) (
                                $attendance->scheduled_hours
                                ?? 0
                            );

                        $workedHoursRecord =
                            (float) (
                                $attendance->worked_hours
                                ?? 0
                            );

                        $overtimeRecord =
                            (float) (
                                $attendance->overtime_hours
                                ?? 0
                            );

                    @endphp

                    <tr class="page-break">

                        <td>
                            <div class="date">
                                {{ $attendanceDate }}
                            </div>
                        </td>

                        <td>
                            <strong>
                                {{ $staffName }}
                            </strong>
                        </td>

                        <td>
                            <span class="status {{ $statusClass }}">
                                {{ $statusText ?: 'Unknown' }}
                            </span>
                        </td>

                        <td>
                            {{ $checkIn }}
                        </td>

                        <td>
                            {{ $checkOut }}
                        </td>

                        <td>
                            {{ number_format(
                                $scheduledHours,
                                2
                            ) }}
                        </td>

                        <td>
                            <strong>
                                {{ number_format(
                                    $workedHoursRecord,
                                    2
                                ) }}
                            </strong>
                        </td>

                        <td>
                            <strong>
                                {{ number_format(
                                    $overtimeRecord,
                                    2
                                ) }}
                            </strong>
                        </td>

                        <td>
                            {{ $markedByName }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    @else

        <div class="empty">
            No attendance records were found for the selected filters.
        </div>

    @endif


    {{-- ABSENCES --}}
    <div class="section-title">
        Absence Records
    </div>

    @if($absenceRows->count())

        <table class="attendance">

            <thead>

                <tr>

                    <th style="width: 20%;">
                        Date
                    </th>

                    <th style="width: 30%;">
                        Staff
                    </th>

                    <th style="width: 20%;">
                        Status
                    </th>

                    <th style="width: 30%;">
                        Reason
                    </th>

                </tr>

            </thead>

            <tbody>

                @foreach($absenceRows as $row)

                    @php
                        $staffName =
                            $row['user']->full_name
                            ?? trim(
                                ($row['user']->first_name ?? '')
                                . ' '
                                . ($row['user']->last_name ?? '')
                            );
                    @endphp

                    <tr class="page-break">

                        <td>
                            {{ $row['date']->format('M j, Y') }}
                        </td>

                        <td>
                            <strong>
                                {{ $staffName ?: 'Unknown Staff' }}
                            </strong>
                        </td>

                        <td>
                            <span class="status status-absent">
                                Absent
                            </span>
                        </td>

                        <td>
                            {{ $row['reason'] ?? 'No attendance recorded' }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    @else

        <div class="empty">
            No absences were recorded for the selected period.
        </div>

    @endif


    {{-- LEAVE / DAY OFF / HOLIDAY --}}
    <div class="section-title">
        Leave and Schedule Exceptions
    </div>

    @if($exceptionRows->count())

        <table class="attendance">

            <thead>

                <tr>

                    <th style="width: 20%;">
                        Date
                    </th>

                    <th style="width: 30%;">
                        Staff
                    </th>

                    <th style="width: 20%;">
                        Type
                    </th>

                    <th style="width: 30%;">
                        Reason
                    </th>

                </tr>

            </thead>

            <tbody>

                @foreach($exceptionRows as $row)

                    @php

                        $staffName =
                            $row['user']->full_name
                            ?? trim(
                                ($row['user']->first_name ?? '')
                                . ' '
                                . ($row['user']->last_name ?? '')
                            );

                        $exceptionClass = match ($row['type']) {
                            'day_off' =>
                                'status-day-off',

                            'holiday' =>
                                'status-holiday',

                            'sick_leave',
                            'urgent_leave' =>
                                'status-on-leave',

                            default =>
                                'status-default',
                        };

                    @endphp

                    <tr class="page-break">

                        <td>
                            {{ $row['date']->format('M j, Y') }}
                        </td>

                        <td>
                            <strong>
                                {{ $staffName ?: 'Unknown Staff' }}
                            </strong>
                        </td>

                        <td>

                            <span class="status {{ $exceptionClass }}">
                                {{ $row['label'] }}
                            </span>

                        </td>

                        <td>
                            {{ $row['reason'] ?? '—' }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    @else

        <div class="empty">
            No leave, day-off, or holiday records were found
            for the selected period.
        </div>

    @endif


    {{-- STAFF OVERTIME BREAKDOWN --}}
    <div class="section-title">
        Staff Overtime Breakdown
    </div>

    @if(isset($staffOvertimeSummary) && count($staffOvertimeSummary))

        <table class="hours-table">

            <thead>

                <tr>

                    <th style="width: 60%;">
                        Staff
                    </th>

                    <th style="width: 20%;">
                        Overtime Hours
                    </th>

                    <th style="width: 20%;">
                        Share of Overtime
                    </th>

                </tr>

            </thead>

            <tbody>

                @foreach($staffOvertimeSummary as $row)

                    @php

                        $rowName =
                            $row['staff_name']
                            ?? $row['name']
                            ?? 'Unknown Staff';

                        $rowOvertime =
                            (float) (
                                $row['overtime_hours']
                                ?? $row['overtime']
                                ?? 0
                            );

                        $share =
                            $overtimeHours > 0
                                ? (
                                    $rowOvertime
                                    / $overtimeHours
                                ) * 100
                                : 0;

                    @endphp

                    <tr class="page-break">

                        <td>
                            <strong>
                                {{ $rowName }}
                            </strong>
                        </td>

                        <td>
                            {{ number_format(
                                $rowOvertime,
                                2
                            ) }}
                            hrs
                        </td>

                        <td>
                            {{ number_format(
                                $share,
                                1
                            ) }}%
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    @else

        <div class="empty">
            No overtime records were found for the selected filters.
        </div>

    @endif


    {{-- ATTENDANCE TOTALS --}}
    <div class="section-title">
        Attendance Totals
    </div>

    <table class="totals-table">

        <tr>
            <td class="total-label">
                Present
            </td>

            <td class="total-value">
                {{ number_format($present) }}
            </td>
        </tr>

        <tr>
            <td class="total-label">
                Late
            </td>

            <td class="total-value">
                {{ number_format($late) }}
            </td>
        </tr>

        <tr>
            <td class="total-label">
                Absent
            </td>

            <td class="total-value">
                {{ number_format($absent) }}
            </td>
        </tr>

        <tr>
            <td class="total-label">
                Leave
            </td>

            <td class="total-value">
                {{ number_format($onLeave) }}
            </td>
        </tr>

        <tr>
            <td class="total-label">
                Day Off
            </td>

            <td class="total-value">
                {{ number_format($dayOff) }}
            </td>
        </tr>

        <tr>
            <td class="total-label">
                Holiday
            </td>

            <td class="total-value">
                {{ number_format($holiday) }}
            </td>
        </tr>

        <tr>
            <td class="total-label">
                Worked Hours
            </td>

            <td class="total-value">
                {{ number_format($workedHours, 2) }}
            </td>
        </tr>

        <tr>
            <td class="total-label">
                Overtime Hours
            </td>

            <td class="total-value">
                {{ number_format($overtimeHours, 2) }}
            </td>
        </tr>

    </table>


    {{-- FOOTER --}}
    <div class="footer">

        <div>
            Prepared by: {{ $preparedBy }}
        </div>

        <div>
            ZenFlow System — Spa Alexandria
        </div>

    </div>

</body>
</html>