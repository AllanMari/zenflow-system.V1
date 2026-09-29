```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Staff Weekly Schedule</title>

    <style>

        /*
        |--------------------------------------------------------------------------
        | PAGE
        |--------------------------------------------------------------------------
        */

        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #111111;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        body {
            font-size: 8px;
            line-height: 1.15;
        }

        table {
            border-spacing: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | VISIBLE DOCUMENT MARGIN
        |--------------------------------------------------------------------------
        */

        .page {
            width: 92%;
            margin: 0 auto;
            padding: 1mm 0 0;
        }


        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .header {
            width: 100%;
            margin-bottom: 3.5mm;
        }

        .header-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .header-table td {
            padding: 0;
            border: 0;
            vertical-align: middle;
        }

        .header-left {
            width: 28%;
            text-align: left;
        }

        .header-center {
            width: 44%;
            text-align: center;
        }

        .header-right {
            width: 28%;
            text-align: right;
        }

        .brand {
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .organization {
            margin-top: 0.7mm;
            font-size: 7.5px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .document-title {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .week-label {
            margin-top: 0.7mm;
            font-size: 8.2px;
            font-weight: 700;
        }

        .document-meta {
            font-size: 6.5px;
            line-height: 1.35;
            color: #444444;
            text-transform: uppercase;
        }


        /*
        |--------------------------------------------------------------------------
        | SECTION TITLE
        |--------------------------------------------------------------------------
        */

        .section-title {
            margin: 0 0 1.8mm 0;
            padding-left: 1.7mm;

            border-left: 3px solid #111111;

            font-size: 8.5px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.55px;

            line-height: 1.1;
        }


        /*
        |--------------------------------------------------------------------------
        | STAFF INFORMATION
        |--------------------------------------------------------------------------
        */

        .staff-info {
            width: 100%;
            margin: 0 0 2.8mm 0;
            table-layout: fixed;

            border-collapse: collapse;
            border: 1.8px solid #111111;
        }

        .staff-info td {
            border: 1.2px solid #111111;
            padding: 1.9mm 2.1mm;
            vertical-align: middle;
        }

        .staff-info .label {
            width: 12%;

            background: #e7e7e7;

            font-size: 7px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.25px;

            white-space: nowrap;
        }

        .staff-info .value {
            width: 38%;

            font-size: 9px;
            font-weight: 700;
        }


        /*
        |--------------------------------------------------------------------------
        | DAY COLUMN COLORS
        |--------------------------------------------------------------------------
        |
        | Monday    = WHITE
        | Tuesday   = GRAY
        | Wednesday = WHITE
        | Thursday  = GRAY
        | Friday    = WHITE
        | Saturday  = GRAY
        | Sunday    = WHITE
        |
        */

        .day-white {
            background: #ffffff !important;
        }

        .day-gray {
            background: #eeeeee !important;
        }


        /*
        |--------------------------------------------------------------------------
        | INDIVIDUAL TIMETABLE
        |--------------------------------------------------------------------------
        */

        .schedule-table {
            width: 100%;
            table-layout: fixed;

            border-collapse: collapse;
            border-spacing: 0;

            border: 1.8px solid #111111;

            page-break-inside: avoid;
        }


        /*
        |--------------------------------------------------------------------------
        | TABLE HEADER
        |--------------------------------------------------------------------------
        */

        .schedule-table thead th {
            height: 8.8mm;

            padding: 1.2mm 0.7mm;

            background: #e4e4e4;

            font-size: 8px;
            font-weight: 700;

            text-align: center;
            vertical-align: middle;

            text-transform: uppercase;

            line-height: 1.05;

            border: 1.3px solid #111111;
        }

        .schedule-table thead th.time-column {
            width: 13%;
            background: #d7d7d7;
        }


        /*
        |--------------------------------------------------------------------------
        | BODY GRID
        |--------------------------------------------------------------------------
        */

        .schedule-table tbody tr {
            page-break-inside: avoid;
        }

        .schedule-table tbody td {
            padding: 0;

            border: 1.35px solid #111111;

            text-align: center;
            vertical-align: middle;

            line-height: 1.1;
        }


        /*
        |--------------------------------------------------------------------------
        | TIME COLUMN
        |--------------------------------------------------------------------------
        */

        .schedule-table td.time-cell {
            width: 13%;

            padding: 0.65mm 0.8mm;

            background: #f0f0f0 !important;

            font-size: 8.2px;
            font-weight: 700;

            white-space: nowrap;

            text-align: center;
            vertical-align: middle;

            line-height: 1.05;
        }


        /*
        |--------------------------------------------------------------------------
        | SCHEDULE BLOCKS
        |--------------------------------------------------------------------------
        */

        .schedule-block {
            width: 100%;

            padding: 1.8mm 1.2mm;

            text-align: center;
            vertical-align: middle;

            line-height: 1.1;
        }

        .schedule-block.work {
            background: transparent;
        }

        .schedule-block.off {
            background: transparent;
            color: #555555;
        }

        .schedule-block.custom {
            background: #dddddd;
        }

        .schedule-block.leave {
            background: #cecece;
        }

        .block-title {
            font-size: 9px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.2px;

            line-height: 1.05;
        }

        .block-time {
            margin-top: 0.8mm;

            font-size: 8.3px;
            font-weight: 700;

            line-height: 1.05;
            white-space: nowrap;
        }

        .block-duration {
            margin-top: 0.5mm;

            font-size: 7px;
            font-weight: 700;

            text-transform: uppercase;

            line-height: 1;
        }

        .block-reason {
            margin-top: 0.6mm;

            font-size: 6px;

            line-height: 1.05;
        }


        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        .summary-table {
            width: 100%;
            margin-top: 2.2mm;

            table-layout: fixed;

            border-collapse: collapse;
            border: 1.7px solid #111111;
        }

        .summary-table td {
            border: 1.2px solid #111111;

            padding: 1.45mm 1.8mm;

            vertical-align: middle;
        }

        .summary-label {
            width: 14%;

            background: #e7e7e7;

            font-size: 6.5px;
            font-weight: 700;

            text-transform: uppercase;
        }

        .summary-value {
            width: 14%;

            font-size: 7px;
            font-weight: 700;
        }

        .summary-days {
            width: 30%;

            font-size: 6.5px;
            font-weight: 700;
        }


        /*
        |--------------------------------------------------------------------------
        | ALL STAFF TABLE
        |--------------------------------------------------------------------------
        */

        .combined-table {
            width: 100%;
            table-layout: fixed;

            border-collapse: collapse;
            border-spacing: 0;

            border: 1.8px solid #111111;

            page-break-inside: avoid;
        }

        .combined-table th {
            padding: 1.25mm 0.65mm;

            background: #e4e4e4;

            font-size: 6.5px;
            font-weight: 700;

            text-align: center;
            vertical-align: middle;

            text-transform: uppercase;

            line-height: 1.05;

            border: 1.25px solid #111111;
        }

        .combined-table th.staff-column {
            width: 19%;

            background: #d7d7d7;

            text-align: left;
        }

        .combined-table th.role-column {
            width: 10%;

            background: #d7d7d7;
        }

        .combined-table th.day-column {
            width: 9.4%;
        }

        .combined-table th.weekend-column {
            width: 13.5%;
        }

        .combined-table td {
            padding: 0;

            border: 1.25px solid #111111;

            text-align: center;
            vertical-align: middle;
        }

        .combined-table td.staff-name {
            padding: 1.3mm;

            background: #dddddd !important;

            text-align: left;
            vertical-align: middle;
        }

        .combined-name {
            font-size: 6.5px;
            font-weight: 700;

            line-height: 1.05;
        }

        .combined-role {
            padding: 1mm 0.7mm;

            font-size: 5.3px;
            font-weight: 700;

            text-transform: uppercase;

            line-height: 1.05;
        }


        /*
        |--------------------------------------------------------------------------
        | ALL STAFF DAY COLORS
        |--------------------------------------------------------------------------
        */

        .combined-day-white {
            background: #ffffff !important;
        }

        .combined-day-gray {
            background: #eeeeee !important;
        }

        .compact-day {
            min-height: 7mm;

            padding: 0.85mm 0.55mm;

            text-align: center;
            vertical-align: middle;

            line-height: 1;
        }

        .compact-day.work,
        .compact-day.off {
            background: transparent;
        }

        .compact-day.off {
            color: #555555;
        }

        .compact-day.custom {
            background: #dddddd;
        }

        .compact-day.leave {
            background: #cecece;
        }

        .compact-title {
            font-size: 5.5px;
            font-weight: 700;

            text-transform: uppercase;
            line-height: 1;
        }

        .compact-time {
            margin-top: 0.45mm;

            font-size: 5px;
            font-weight: 700;

            line-height: 1;
        }

        .compact-duration {
            margin-top: 0.3mm;

            font-size: 4.2px;
            font-weight: 700;

            text-transform: uppercase;
            line-height: 1;
        }

        .compact-reason {
            margin-top: 0.35mm;

            font-size: 3.8px;
            line-height: 1;
        }


        /*
        |--------------------------------------------------------------------------
        | WEEKEND
        |--------------------------------------------------------------------------
        */

        .weekend-wrapper {
            width: 100%;
        }

        .weekend-day {
            padding: 0.7mm;
        }

        .weekend-day + .weekend-day {
            border-top: 1.25px solid #111111;
        }

        .weekend-label {
            margin-bottom: 0.3mm;

            font-size: 4.5px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.15px;
        }


        /*
        |--------------------------------------------------------------------------
        | LARGE STAFF COUNT
        |--------------------------------------------------------------------------
        */

        .combined-table.tight th {
            padding-top: 0.8mm;
            padding-bottom: 0.8mm;

            font-size: 5.2px;
        }

        .combined-table.tight .combined-name {
            font-size: 5.4px;
        }

        .combined-table.tight .combined-role {
            font-size: 4.3px;
        }

        .combined-table.tight .compact-day {
            min-height: 5.8mm;

            padding-top: 0.5mm;
            padding-bottom: 0.5mm;
        }

        .combined-table.tight .compact-title {
            font-size: 4.2px;
        }

        .combined-table.tight .compact-time {
            font-size: 3.8px;
        }

        .combined-table.tight .compact-duration {
            font-size: 3.1px;
        }

        .combined-table.tight .compact-reason {
            font-size: 2.9px;
        }


        /*
        |--------------------------------------------------------------------------
        | LEGEND
        |--------------------------------------------------------------------------
        */

        .legend {
            width: 100%;

            margin-top: 1.6mm;

            text-align: center;

            font-size: 5.5px;

            color: #222222;

            white-space: nowrap;
        }

        .legend-item {
            display: inline-block;

            margin: 0 3.5mm;
        }

        .legend-box {
            display: inline-block;

            width: 7px;
            height: 7px;

            margin-right: 0.65mm;

            vertical-align: -1px;

            border: 1.1px solid #111111;
        }

        .legend-box.work {
            background: #ffffff;
        }

        .legend-box.custom {
            background: #dddddd;
        }

        .legend-box.leave {
            background: #cecece;
        }

        .legend-box.off {
            background: #ffffff;
            border-style: dashed;
        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        .empty-state {
            width: 100%;

            padding: 9mm 10mm;

            border: 1.7px solid #111111;

            text-align: center;

            font-size: 7px;
        }


        /*
        |--------------------------------------------------------------------------
        | SIGNATURES
        |--------------------------------------------------------------------------
        */

        .signature-table {
            width: 100%;

            margin-top: 2.3mm;

            table-layout: fixed;

            border-collapse: collapse;
        }

        .signature-table td {
            width: 50%;

            padding-right: 16mm;

            vertical-align: top;

            border: 0;
        }

        .signature-heading {
            font-size: 5.5px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.15px;
        }

        .signature-line {
            width: 75%;

            height: 4.5mm;

            border-bottom: 1px solid #111111;
        }

        .signature-name {
            margin-top: 0.4mm;

            font-size: 5.5px;
            font-weight: 700;
        }

        .signature-caption {
            margin-top: 0.2mm;

            font-size: 4.5px;
            color: #333333;
        }


        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .footer {
            width: 100%;

            margin-top: 1mm;

            text-align: right;

            font-size: 4px;

            color: #444444;
        }


        /*
        |--------------------------------------------------------------------------
        | PRINT SAFETY
        |--------------------------------------------------------------------------
        */

        .header,
        .staff-info,
        .schedule-table,
        .summary-table,
        .combined-table,
        .signature-table {
            page-break-inside: avoid;
        }

    </style>
</head>


<body>

@php

    /*
    |--------------------------------------------------------------------------
    | FORMAT TIME
    |--------------------------------------------------------------------------
    */

    $formatTime = function ($time) {

        if (!$time) {
            return '';
        }

        try {

            return \Carbon\Carbon::createFromFormat(
                'H:i',
                substr($time, 0, 5)
            )->format('g:i A');

        } catch (\Throwable $e) {

            return $time;

        }
    };


    /*
    |--------------------------------------------------------------------------
    | TIME TO MINUTES
    |--------------------------------------------------------------------------
    */

    $timeToMinutes = function ($time) {

        if ($time === null || $time === '') {
            return null;
        }

        if (is_numeric($time)) {
            return (int) $time;
        }

        try {

            $parsed =
                \Carbon\Carbon::createFromFormat(
                    'H:i',
                    substr(
                        (string) $time,
                        0,
                        5
                    )
                );

            return
                ($parsed->hour * 60)
                + $parsed->minute;

        } catch (\Throwable $e) {

            return null;

        }
    };


    /*
    |--------------------------------------------------------------------------
    | DURATION
    |--------------------------------------------------------------------------
    */

    $durationHours =
        function ($start, $end)
        use ($timeToMinutes) {

            $startMinutes =
                $timeToMinutes($start);

            $endMinutes =
                $timeToMinutes($end);

            if (
                $startMinutes === null
                || $endMinutes === null
            ) {
                return null;
            }

            if ($endMinutes <= $startMinutes) {
                $endMinutes += 1440;
            }

            return round(
                (
                    $endMinutes
                    - $startMinutes
                ) / 60,
                1
            );
        };


    $durationLabel =
        function ($start, $end)
        use ($durationHours) {

            $hours =
                $durationHours(
                    $start,
                    $end
                );

            if ($hours === null) {
                return '';
            }

            return
                number_format(
                    $hours,
                    1
                )
                . ' HRS';
        };


    /*
    |--------------------------------------------------------------------------
    | EXCEPTION LABEL
    |--------------------------------------------------------------------------
    */

    $exceptionLabel = function ($type) {

        return match ($type) {

            'day_off' =>
                'DAY OFF',

            'holiday' =>
                'HOLIDAY',

            'sick_leave' =>
                'SICK LEAVE',

            'urgent_leave' =>
                'URGENT LEAVE',

            'custom' =>
                'CUSTOM HOURS',

            default =>
                'EXCEPTION',
        };
    };


    /*
    |--------------------------------------------------------------------------
    | TIMELINE
    |--------------------------------------------------------------------------
    */

    $timelineRows =
        $timeline->values();


    /*
    |--------------------------------------------------------------------------
    | FULL PRINTABLE TIME GRID
    |--------------------------------------------------------------------------
    |
    | 9:00 AM through 9:00 PM
    | 30-minute intervals
    |
    | The final row is 8:30 PM - 9:00 PM.
    |
    */

    $gridStartMinutes =
        9 * 60;


    $gridEndMinutes =
        21 * 60;


    $gridInterval =
        30;


    $timeRows = [];


    for (
        $minutes = $gridStartMinutes;
        $minutes < $gridEndMinutes;
        $minutes += $gridInterval
    ) {

        $endMinutes =
            min(
                $minutes + $gridInterval,
                $gridEndMinutes
            );


        $timeRows[] = [

            'start_time' =>
                sprintf(
                    '%02d:%02d',
                    intdiv($minutes, 60),
                    $minutes % 60
                ),

            'end_time' =>
                sprintf(
                    '%02d:%02d',
                    intdiv($endMinutes, 60),
                    $endMinutes % 60
                ),

            'label' =>
                \Carbon\Carbon::createFromTime(
                    intdiv($minutes, 60),
                    $minutes % 60,
                    0
                )->format('g:i A'),

        ];
    }


    $timeRowCount =
        max(
            count($timeRows),
            1
        );


    /*
    |--------------------------------------------------------------------------
    | DYNAMIC ROW HEIGHT
    |--------------------------------------------------------------------------
    |
    | 24 rows for 9:00 AM - 9:00 PM.
    | Keep the timetable readable while preserving one-page output.
    |
    */

    $rowHeightMm =
        min(
            5.0,
            max(
                4.8,
                118 / $timeRowCount
            )
        );


    /*
    |--------------------------------------------------------------------------
    | ALL STAFF COMPRESSION
    |--------------------------------------------------------------------------
    */

    $staffCount =
        $timelineRows->count();


    $combinedClass =
        $staffCount >= 15
            ? 'tight'
            : '';


    /*
    |--------------------------------------------------------------------------
    | SELECTED STAFF ROLE
    |--------------------------------------------------------------------------
    */

    $selectedStaffRole =
        'Staff';


    if (
        isset($selectedStaff)
        && $selectedStaff
            ->relationLoaded('roles')
        && $selectedStaff
            ->roles
            ->isNotEmpty()
    ) {

        $roleName =
            $selectedStaff
                ->roles
                ->first()
                ->name
                ?? null;

        if ($roleName) {

            $selectedStaffRole =
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $roleName
                    )
                );
        }
    }

@endphp


<div class="page">


    {{-- =========================================================
         HEADER
    ========================================================== --}}

    <div class="header">

        <table class="header-table">

            <tr>

                <td class="header-left">

                    <div class="brand">
                        ZenFlow System
                    </div>

                    <div class="organization">
                        Spa Alexandria
                    </div>

                </td>


                <td class="header-center">

                    <div class="document-title">
                        Staff Weekly Schedule
                    </div>

                    <div class="week-label">
                        Week of {{ $weekLabel }}
                    </div>

                </td>


                <td class="header-right">

                    <div class="document-meta">
                        Schedule Management
                        <br>
                        Posting Copy
                    </div>

                </td>

            </tr>

        </table>

    </div>



    {{-- =========================================================
         INDIVIDUAL STAFF
    ========================================================== --}}

    @if($mode === 'individual')

        @php

            $staffMember =
                $selectedStaff;


            $stats =
                $staffStats[
                    $staffMember->id
                ]
                ?? [
                    'days' => [],
                    'hours' => 0,
                    'count' => 0,
                ];


            /*
            |--------------------------------------------------------------------------
            | BUILD ONE DISPLAY BLOCK PER DAY
            |--------------------------------------------------------------------------
            */

            $blocks = [];


            foreach (
                $days
                as $dayIndex => $day
            ) {

                $cell =
                    $timelineRows[0]['days']
                    [$dayIndex]
                    ?? null;


                /*
                |--------------------------------------------------------------------------
                | DEFAULT DAY OFF
                |--------------------------------------------------------------------------
                */

                $block = [

                    'row' =>
                        0,

                    'rowspan' =>
                        max(
                            count($timeRows),
                            1
                        ),

                    'type' =>
                        'off',

                    'title' =>
                        'DAY OFF',

                    'time' =>
                        null,

                    'duration' =>
                        null,

                    'reason' =>
                        null,
                ];


                if (!$cell) {

                    $blocks[$dayIndex] =
                        $block;

                    continue;
                }


                $hasWorkingTime =
                    !empty(
                        $cell['start_time']
                    )
                    && !empty(
                        $cell['end_time']
                    );


                /*
                |--------------------------------------------------------------------------
                | WORK / CUSTOM HOURS
                |--------------------------------------------------------------------------
                */

                if (
                    $hasWorkingTime
                    && in_array(
                        $cell['type'],
                        [
                            'work',
                            'exception',
                        ],
                        true
                    )
                ) {

                    $startMinutes =
                        $timeToMinutes(
                            $cell['start_time']
                        );


                    $endMinutes =
                        $timeToMinutes(
                            $cell['end_time']
                        );


                    if (
                        $startMinutes !== null
                        && $endMinutes !== null
                    ) {

                        if (
                            $endMinutes
                            <= $startMinutes
                        ) {

                            $endMinutes += 1440;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | ALWAYS POSITION AGAINST 9:00 AM
                        |--------------------------------------------------------------------------
                        */

                        $axisStart =
                            $gridStartMinutes;


                        /*
                        |--------------------------------------------------------------------------
                        | POSITION ON 30-MINUTE GRID
                        |--------------------------------------------------------------------------
                        */

                        $rowStart =
                            (int) floor(
                                (
                                    $startMinutes
                                    - $axisStart
                                ) / $gridInterval
                            );


                        $rowSpan =
                            (int) ceil(
                                (
                                    $endMinutes
                                    - $startMinutes
                                ) / $gridInterval
                            ) + 1;


                        $rowStart =
                            max(
                                0,
                                $rowStart
                            );


                        $rowSpan =
                            max(
                                1,
                                $rowSpan
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | KEEP BLOCK INSIDE THE 9 AM - 9 PM AXIS
                        |--------------------------------------------------------------------------
                        */

                        if (
                            count($timeRows)
                        ) {

                            $rowStart =
                                min(
                                    $rowStart,
                                    count($timeRows)
                                    - 1
                                );


                            $rowSpan =
                                min(
                                    $rowSpan,
                                    count($timeRows)
                                    - $rowStart
                                );
                        }


                        $isCustom =
                            $cell['type']
                            === 'exception'
                            && (
                                $cell[
                                    'exception_type'
                                ] ?? null
                            ) === 'custom';


                        $block['row'] =
                            $rowStart;


                        $block['rowspan'] =
                            max(
                                1,
                                $rowSpan
                            );


                        $block['type'] =
                            $isCustom
                                ? 'custom'
                                : 'work';


                        $block['title'] =
                            $isCustom
                                ? 'CUSTOM HOURS'
                                : 'WORK SHIFT';


                        $block['time'] =
                            $formatTime(
                                $cell[
                                    'start_time'
                                ]
                            )
                            . ' - '
                            . $formatTime(
                                $cell[
                                    'end_time'
                                ]
                            );


                        $block['duration'] =
                            $durationLabel(
                                $cell[
                                    'start_time'
                                ],
                                $cell[
                                    'end_time'
                                ]
                            );


                        $block['reason'] =
                            $cell[
                                'exception'
                            ]['reason']
                            ?? null;
                    }


                /*
                |--------------------------------------------------------------------------
                | LEAVE / EXCEPTION
                |--------------------------------------------------------------------------
                */

                } elseif (
                    $cell['type']
                    === 'exception'
                ) {

                    $block['row'] =
                        0;

                    $block['rowspan'] =
                        max(
                            count($timeRows),
                            1
                        );

                    $block['type'] =
                        'leave';

                    $block['title'] =
                        $exceptionLabel(
                            $cell[
                                'exception_type'
                            ] ?? null
                        );

                    $block['reason'] =
                        $cell[
                            'exception'
                        ]['reason']
                        ?? null;
                }


                $blocks[$dayIndex] =
                    $block;
            }

        @endphp



        {{-- =====================================================
             STAFF INFORMATION
        ====================================================== --}}

        <table class="staff-info">

            <tr>

                <td class="label">
                    Staff
                </td>

                <td class="value">
                    {{ $staffMember->full_name }}
                </td>


                <td class="label">
                    Role
                </td>

                <td class="value">
                    {{ $selectedStaffRole }}
                </td>

            </tr>

        </table>



        <div class="section-title">
            Weekly Timetable
        </div>



        @if(count($timeRows))

            <table class="schedule-table">

                <thead>

                    <tr>

                        <th class="time-column">
                            TIME
                        </th>


                        @foreach(
                            $days
                            as $dayIndex => $day
                        )

                            <th
                                class="
                                    {{
                                        $dayIndex % 2 === 0
                                            ? 'day-white'
                                            : 'day-gray'
                                    }}
                                "
                            >

                                {{
                                    strtoupper(
                                        $day['label']
                                    )
                                }}

                                <br>

                                {{ $day['day'] }}

                            </th>

                        @endforeach

                    </tr>

                </thead>


                <tbody>

                    @foreach(
                        $timeRows
                        as $rowIndex => $timeRow
                    )

                        <tr
                            class="schedule-row"
                            style="
                                height:
                                {{
                                    number_format(
                                        $rowHeightMm,
                                        2
                                    )
                                }}mm;
                            "
                        >

                            <td class="time-cell">

                                {{ $timeRow['label'] }}

                            </td>


                            @foreach(
                                $days
                                as $dayIndex => $day
                            )

                                @php

                                    $block =
                                        $blocks[
                                            $dayIndex
                                        ]
                                        ?? null;


                                    $blockStart =
                                        $block[
                                            'row'
                                        ]
                                        ?? 0;


                                    $blockEnd =
                                        $block
                                            ? (
                                                $blockStart
                                                + $block[
                                                    'rowspan'
                                                ]
                                                - 1
                                            )
                                            : -1;


                                    $dayClass =
                                        $dayIndex % 2 === 0
                                            ? 'day-white'
                                            : 'day-gray';

                                @endphp


                                @if(
                                    $block
                                    && $rowIndex
                                        === $blockStart
                                )

                                    <td
                                        rowspan="{{ $block['rowspan'] }}"
                                        class="{{ $dayClass }}"
                                    >

                                        <div
                                            class="
                                                schedule-block
                                                {{ $block['type'] }}
                                            "
                                        >

                                            <div class="block-title">

                                                {{
                                                    $block['title']
                                                }}

                                            </div>


                                            @if(
                                                $block['time']
                                            )

                                                <div class="block-time">

                                                    {{
                                                        $block['time']
                                                    }}

                                                </div>

                                            @endif


                                            @if(
                                                $block['duration']
                                            )

                                                <div
                                                    class="block-duration"
                                                >

                                                    {{
                                                        $block[
                                                            'duration'
                                                        ]
                                                    }}

                                                </div>

                                            @endif


                                            @if(
                                                $block['reason']
                                            )

                                                <div class="block-reason">

                                                    {{
                                                        $block[
                                                            'reason'
                                                        ]
                                                    }}

                                                </div>

                                            @endif

                                        </div>

                                    </td>


                                @elseif(
                                    !$block
                                    || $rowIndex
                                        < $blockStart
                                    || $rowIndex
                                        > $blockEnd
                                )

                                    <td
                                        class="{{ $dayClass }}"
                                    ></td>

                                @endif

                            @endforeach

                        </tr>

                    @endforeach

                </tbody>

            </table>



            {{-- =================================================
                 SUMMARY
            ================================================== --}}

            <table class="summary-table">

                <tr>

                    <td class="summary-label">
                        Scheduled Days
                    </td>

                    <td class="summary-value">
                        {{ $stats['count'] }}
                    </td>


                    <td class="summary-label">
                        Scheduled Hours
                    </td>

                    <td class="summary-value">

                        {{
                            number_format(
                                $stats['hours'],
                                1
                            )
                        }}
                        hours

                    </td>


                    <td class="summary-label">
                        Working Days
                    </td>

                    <td class="summary-days">

                        {{
                            implode(
                                ', ',
                                $stats['days']
                            )
                            ?: '-'
                        }}

                    </td>

                </tr>

            </table>


        @else

            <div class="empty-state">

                No scheduled working hours are available
                for this staff member during this week.

            </div>

        @endif



        {{-- =====================================================
             LEGEND
        ====================================================== --}}

        <div class="legend">

            <span class="legend-item">

                <span
                    class="legend-box work"
                ></span>

                Work Shift

            </span>


            <span class="legend-item">

                <span
                    class="legend-box custom"
                ></span>

                Custom Hours

            </span>


            <span class="legend-item">

                <span
                    class="legend-box leave"
                ></span>

                Leave / Exception

            </span>


            <span class="legend-item">

                <span
                    class="legend-box off"
                ></span>

                Day Off

            </span>

        </div>



    @else

        {{-- =====================================================
             ALL STAFF
        ====================================================== --}}

        <div class="section-title">
            All Staff Weekly Schedule
        </div>


        @if($timelineRows->count())

            <table
                class="
                    combined-table
                    {{ $combinedClass }}
                "
            >

                <thead>

                    <tr>

                        <th class="staff-column">
                            Staff Member
                        </th>


                        <th class="role-column">
                            Role
                        </th>


                        @foreach(
                            array_slice(
                                $days,
                                0,
                                5
                            )
                            as $dayIndex => $day
                        )

                            <th
                                class="
                                    day-column
                                    {{
                                        $dayIndex % 2 === 0
                                            ? 'combined-day-white'
                                            : 'combined-day-gray'
                                    }}
                                "
                            >

                                {{
                                    strtoupper(
                                        $day['label']
                                    )
                                }}

                                <br>

                                {{ $day['day'] }}

                            </th>

                        @endforeach


                        <th class="weekend-column">
                            Weekend
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach(
                        $timelineRows
                        as $row
                    )

                        @php

                            $staffMember =
                                $row['user'];


                            $roleLabel =
                                'Staff';


                            if (
                                $staffMember
                                    ->relationLoaded(
                                        'roles'
                                    )
                                && $staffMember
                                    ->roles
                                    ->isNotEmpty()
                            ) {

                                $roleName =
                                    $staffMember
                                        ->roles
                                        ->first()
                                        ->name
                                        ?? null;


                                if ($roleName) {

                                    $roleLabel =
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $roleName
                                            )
                                        );
                                }
                            }


                            $staffDays =
                                $row['days'];


                            $weekendDays =
                                array_slice(
                                    $staffDays,
                                    5,
                                    2
                                );

                        @endphp


                        <tr>

                            <td class="staff-name">

                                <div class="combined-name">

                                    {{
                                        $staffMember
                                            ->full_name
                                    }}

                                </div>

                            </td>


                            <td class="combined-role">

                                {{ $roleLabel }}

                            </td>


                            {{-- MONDAY - FRIDAY --}}

                            @foreach(
                                array_slice(
                                    $staffDays,
                                    0,
                                    5
                                ) as $cellIndex => $cell
                            )

                                @php

                                    $isWork =
                                        $cell['type']
                                        === 'work';


                                    $isException =
                                        $cell['type']
                                        === 'exception';


                                    $isCustom =
                                        $isException
                                        && (
                                            $cell[
                                                'exception_type'
                                            ] ?? null
                                        ) === 'custom';


                                    $reason =
                                        $cell[
                                            'exception'
                                        ]['reason']
                                        ?? null;


                                    $dayClass =
                                        $cellIndex % 2 === 0
                                            ? 'combined-day-white'
                                            : 'combined-day-gray';


                                    if ($isWork) {

                                        $contentClass =
                                            'work';

                                    } elseif ($isCustom) {

                                        $contentClass =
                                            'custom';

                                    } elseif ($isException) {

                                        $contentClass =
                                            'leave';

                                    } else {

                                        $contentClass =
                                            'off';
                                    }

                                @endphp


                                <td
                                    class="{{ $dayClass }}"
                                >

                                    <div
                                        class="
                                            compact-day
                                            {{ $contentClass }}
                                        "
                                    >

                                        @if($isWork)

                                            <div class="compact-title">
                                                WORK
                                            </div>


                                            <div class="compact-time">

                                                {{
                                                    $formatTime(
                                                        $cell[
                                                            'start_time'
                                                        ]
                                                    )
                                                }}

                                                -

                                                {{
                                                    $formatTime(
                                                        $cell[
                                                            'end_time'
                                                        ]
                                                    )
                                                }}

                                            </div>


                                            <div
                                                class="compact-duration"
                                            >

                                                {{
                                                    $durationLabel(
                                                        $cell[
                                                            'start_time'
                                                        ],
                                                        $cell[
                                                            'end_time'
                                                        ]
                                                    )
                                                }}

                                            </div>


                                        @elseif($isCustom)

                                            <div class="compact-title">
                                                CUSTOM
                                            </div>


                                            <div class="compact-time">

                                                {{
                                                    $formatTime(
                                                        $cell[
                                                            'start_time'
                                                        ]
                                                    )
                                                }}

                                                -

                                                {{
                                                    $formatTime(
                                                        $cell[
                                                            'end_time'
                                                        ]
                                                    )
                                                }}

                                            </div>


                                            <div
                                                class="compact-duration"
                                            >

                                                {{
                                                    $durationLabel(
                                                        $cell[
                                                            'start_time'
                                                        ],
                                                        $cell[
                                                            'end_time'
                                                        ]
                                                    )
                                                }}

                                            </div>


                                            @if($reason)

                                                <div
                                                    class="compact-reason"
                                                >
                                                    {{ $reason }}
                                                </div>

                                            @endif


                                        @elseif($isException)

                                            <div class="compact-title">

                                                {{
                                                    $exceptionLabel(
                                                        $cell[
                                                            'exception_type'
                                                        ] ?? null
                                                    )
                                                }}

                                            </div>


                                            @if($reason)

                                                <div
                                                    class="compact-reason"
                                                >
                                                    {{ $reason }}
                                                </div>

                                            @endif


                                        @else

                                            <div class="compact-title">
                                                OFF
                                            </div>

                                        @endif

                                    </div>

                                </td>

                            @endforeach


                            {{-- WEEKEND --}}

                            <td>

                                <div class="weekend-wrapper">

                                    @foreach(
                                        $weekendDays
                                        as $weekendIndex => $cell
                                    )

                                        @php

                                            $actualDayIndex =
                                                5
                                                + $weekendIndex;


                                            $dayName =
                                                $days[
                                                    $actualDayIndex
                                                ]['label']
                                                ?? '';


                                            $isWork =
                                                $cell['type']
                                                === 'work';


                                            $isException =
                                                $cell['type']
                                                === 'exception';


                                            $isCustom =
                                                $isException
                                                && (
                                                    $cell[
                                                        'exception_type'
                                                    ] ?? null
                                                ) === 'custom';


                                            $reason =
                                                $cell[
                                                    'exception'
                                                ]['reason']
                                                ?? null;


                                            $dayClass =
                                                $actualDayIndex % 2 === 0
                                                    ? 'combined-day-white'
                                                    : 'combined-day-gray';


                                            if ($isWork) {

                                                $contentClass =
                                                    'work';

                                            } elseif ($isCustom) {

                                                $contentClass =
                                                    'custom';

                                            } elseif ($isException) {

                                                $contentClass =
                                                    'leave';

                                            } else {

                                                $contentClass =
                                                    'off';
                                            }

                                        @endphp


                                        <div class="weekend-day">

                                            <div
                                                class="weekend-label"
                                            >

                                                {{
                                                    strtoupper(
                                                        $dayName
                                                    )
                                                }}

                                            </div>


                                            <div
                                                class="
                                                    compact-day
                                                    {{ $dayClass }}
                                                    {{ $contentClass }}
                                                "
                                            >

                                                @if($isWork)

                                                    <div
                                                        class="compact-title"
                                                    >
                                                        WORK
                                                    </div>


                                                    <div
                                                        class="compact-time"
                                                    >

                                                        {{
                                                            $formatTime(
                                                                $cell[
                                                                    'start_time'
                                                                ]
                                                            )
                                                        }}

                                                        -

                                                        {{
                                                            $formatTime(
                                                                $cell[
                                                                    'end_time'
                                                                ]
                                                            )
                                                        }}

                                                    </div>


                                                    <div
                                                        class="compact-duration"
                                                    >

                                                        {{
                                                            $durationLabel(
                                                                $cell[
                                                                    'start_time'
                                                                ],
                                                                $cell[
                                                                    'end_time'
                                                                ]
                                                            )
                                                        }}

                                                    </div>


                                                @elseif($isCustom)

                                                    <div
                                                        class="compact-title"
                                                    >
                                                        CUSTOM
                                                    </div>


                                                    <div
                                                        class="compact-time"
                                                    >

                                                        {{
                                                            $formatTime(
                                                                $cell[
                                                                    'start_time'
                                                                ]
                                                            )
                                                        }}

                                                        -

                                                        {{
                                                            $formatTime(
                                                                $cell[
                                                                    'end_time'
                                                                ]
                                                            )
                                                        }}

                                                    </div>


                                                    <div
                                                        class="compact-duration"
                                                    >

                                                        {{
                                                            $durationLabel(
                                                                $cell[
                                                                    'start_time'
                                                                ],
                                                                $cell[
                                                                    'end_time'
                                                                ]
                                                            )
                                                        }}

                                                    </div>


                                                    @if($reason)

                                                        <div
                                                            class="compact-reason"
                                                        >

                                                            {{
                                                                $reason
                                                            }}

                                                        </div>

                                                    @endif


                                                @elseif($isException)

                                                    <div
                                                        class="compact-title"
                                                    >

                                                        {{
                                                            $exceptionLabel(
                                                                $cell[
                                                                    'exception_type'
                                                                ] ?? null
                                                            )
                                                        }}

                                                    </div>


                                                    @if($reason)

                                                        <div
                                                            class="compact-reason"
                                                        >

                                                            {{
                                                                $reason
                                                            }}

                                                        </div>

                                                    @endif


                                                @else

                                                    <div
                                                        class="compact-title"
                                                    >
                                                        OFF
                                                    </div>

                                                @endif

                                            </div>

                                        </div>

                                    @endforeach

                                </div>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>



            {{-- =================================================
                 LEGEND
            ================================================== --}}

            <div class="legend">

                <span class="legend-item">

                    <span
                        class="legend-box work"
                    ></span>

                    Work Shift

                </span>


                <span class="legend-item">

                    <span
                        class="legend-box custom"
                    ></span>

                    Custom Hours

                </span>


                <span class="legend-item">

                    <span
                        class="legend-box leave"
                    ></span>

                    Leave / Exception

                </span>


                <span class="legend-item">

                    <span
                        class="legend-box off"
                    ></span>

                    Day Off

                </span>

            </div>


        @else

            <div class="empty-state">

                No active staff schedules are available
                for this week.

            </div>

        @endif

    @endif



    {{-- =========================================================
         SIGNATURES
    ========================================================== --}}

    <table class="signature-table">

        <tr>

            <td>

                <div class="signature-heading">
                    Prepared by
                </div>

                <div class="signature-line"></div>

                <div class="signature-name">
                    {{ $preparedBy }}
                </div>

                <div class="signature-caption">
                    Schedule prepared by
                </div>

            </td>


            <td>

                <div class="signature-heading">
                    Approved by
                </div>

                <div class="signature-line"></div>

                <div class="signature-caption">
                    Authorized approval
                </div>

            </td>

        </tr>

    </table>



    {{-- =========================================================
         FOOTER
    ========================================================== --}}

    <div class="footer">

        Generated
        {{ $generatedAt->format(
            'F j, Y g:i A'
        ) }}

        &nbsp;|&nbsp;

        ZenFlow System

    </div>

</div>

</body>
</html>
