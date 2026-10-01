<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>{{ $reportTitle }}</title>

    <style>
        @page {
            margin: 35px;
            size: A4 portrait;
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
            border-spacing: 5px;
            margin: 0 -5px 15px -5px;
        }

        .summary td {
            border: 1px solid #d1d5db;
            border-radius: 5px;
            padding: 9px;
            width: 16.66%;
            vertical-align: top;
        }

        .summary-label {
            font-size: 7px;
            color: #6b7280;
            text-transform: uppercase;
        }

        .summary-value {
            font-size: 15px;
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

        table.appointments {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.appointments thead {
            background: #0f766e;
            color: #ffffff;
        }

        table.appointments th {
            font-size: 8px;
            font-weight: bold;
            text-align: left;
            padding: 7px 5px;
            border: 1px solid #0f766e;
        }

        table.appointments td {
            font-size: 8px;
            padding: 6px 5px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        table.appointments tr:nth-child(even) {
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

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-no-show {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .payment-section {
            margin-top: 8px;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-table td {
            border: 1px solid #d1d5db;
            padding: 7px;
        }

        .payment-label {
            color: #6b7280;
        }

        .payment-value {
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
        }

        .page-break {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    @include('partials.report_header', [
        'reportTitle' => $reportTitle
    ])

    <div class="filter-box">
        <div class="filter-title">
            Report Filters
        </div>

        <table class="filter-table">
            <tr>
                <td>
                    <div class="filter-label">Date Range</div>
                    <div class="filter-value">
                        {{ $dateLabel }}
                    </div>
                </td>

                <td>
                    <div class="filter-label">Status</div>
                    <div class="filter-value">
                        {{ $statusLabel }}
                    </div>
                </td>

                <td>
                    <div class="filter-label">Staff</div>
                    <div class="filter-value">
                        {{ $staffLabel }}
                    </div>
                </td>

                <td>
                    <div class="filter-label">Search</div>
                    <div class="filter-value">
                        {{ $search !== '' ? $search : 'All Customers' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="summary-label">Total</div>
                <div class="summary-value">
                    {{ $totalAppointments }}
                </div>
            </td>

            <td>
                <div class="summary-label">Completed</div>
                <div class="summary-value">
                    {{ $completed }}
                </div>
            </td>

            <td>
                <div class="summary-label">Confirmed</div>
                <div class="summary-value">
                    {{ $confirmed }}
                </div>
            </td>

            <td>
                <div class="summary-label">Pending</div>
                <div class="summary-value">
                    {{ $pending }}
                </div>
            </td>

            <td>
                <div class="summary-label">Cancelled</div>
                <div class="summary-value">
                    {{ $cancelled }}
                </div>
            </td>

            <td>
                <div class="summary-label">No-Show</div>
                <div class="summary-value">
                    {{ $noShow }}
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">
        Appointment Details
    </div>

    @if($appointments->count())

        <table class="appointments">
            <thead>
                <tr>
                    <th style="width: 11%;">Date / Time</th>
                    <th style="width: 17%;">Customer</th>
                    <th style="width: 20%;">Service</th>
                    <th style="width: 15%;">Staff</th>
                    <th style="width: 10%;">Room</th>
                    <th style="width: 10%;">Payment</th>
                    <th style="width: 9%;">Status</th>
                </tr>
            </thead>

            <tbody>
                @foreach($appointments as $appointment)

                    @php
                        $appointmentStatus = $appointment->status;

                        if (
                            $appointmentStatus === 'cancelled' &&
                            $appointment->cancellation_reason === 'customer_no_show'
                        ) {
                            $appointmentStatus = 'no_show';
                        }

                        $statusClass = match ($appointmentStatus) {
                            'pending' => 'status-pending',
                            'confirmed' => 'status-confirmed',
                            'completed' => 'status-completed',
                            'cancelled' => 'status-cancelled',
                            'no_show' => 'status-no-show',
                            default => 'status-pending',
                        };

                        $statusText = ucwords(
                            str_replace('_', ' ', $appointmentStatus)
                        );

                        $customerName =
                            $appointment->customer->display_name
                            ?? $appointment->customer->full_name
                            ?? 'Walk-in';

                        $staffName =
                            $appointment->staff->full_name
                            ?? $appointment->staff->name
                            ?? 'Unassigned';

                        $roomName =
                            $appointment->room->name
                            ?? $appointment->room->room_name
                            ?? '—';

                        $paidAmount = (float) $appointment->payments->sum('amount');

                        $totalAmount = (float) ($appointment->total_price ?? 0);

                        $balance = max(
                            0,
                            $totalAmount - $paidAmount
                        );

                        $serviceNames = $appointment->services
                            ->pluck('name')
                            ->filter()
                            ->values()
                            ->all();

                        $serviceText = count($serviceNames)
                            ? implode(', ', $serviceNames)
                            : '—';

                        $appointmentDate = $appointment->appointment_date
                            ? \Carbon\Carbon::parse(
                                $appointment->appointment_date
                            )->format('M j, Y')
                            : '—';

                        $startTime = $appointment->start_time
                            ? \Carbon\Carbon::parse(
                                $appointment->start_time
                            )->format('g:i A')
                            : '—';

                        $endTime = $appointment->end_time
                            ? \Carbon\Carbon::parse(
                                $appointment->end_time
                            )->format('g:i A')
                            : null;
                    @endphp

                    <tr class="page-break">
                        <td>
                            <div class="date">
                                {{ $appointmentDate }}
                            </div>

                            <div class="muted">
                                {{ $startTime }}

                                @if($endTime)
                                    – {{ $endTime }}
                                @endif
                            </div>
                        </td>

                        <td>
                            <strong>
                                {{ $customerName }}
                            </strong>

                            @if($appointment->customer?->phone_number)
                                <div class="muted">
                                    {{ $appointment->customer->phone_number }}
                                </div>
                            @endif
                        </td>

                        <td>
                            {{ $serviceText }}
                        </td>

                        <td>
                            {{ $staffName }}
                        </td>

                        <td>
                            {{ $roomName }}
                        </td>

                        <td>
                            <strong>
                                ₱{{ number_format($totalAmount, 2) }}
                            </strong>

                            @if($paidAmount > 0)
                                <div class="muted">
                                    Paid:
                                    ₱{{ number_format($paidAmount, 2) }}
                                </div>
                            @endif

                            @if($balance > 0)
                                <div class="muted">
                                    Balance:
                                    ₱{{ number_format($balance, 2) }}
                                </div>
                            @endif
                        </td>

                        <td>
                            <span class="status {{ $statusClass }}">
                                {{ $statusText }}
                            </span>
                                @if($appointment->status === 'cancelled')
                                    <div class="muted" style="font-size: 8px; margin-top: 4px;">
                                        Reason: {{ $appointment->cancellation_reason ? ucwords(str_replace('_', ' ', $appointment->cancellation_reason)) : 'No reason provided' }}
                                    </div>
                                @endif

                                @if($appointment->status === 'no_show')
                                    <div class="muted" style="font-size: 8px; margin-top: 4px;">
                                        Reason: {{ $appointment->no_show_reason ? ucwords(str_replace('_', ' ', $appointment->no_show_reason)) : 'No reason provided' }}
                                    </div>
                                @endif
                        </td>
                    </tr>

                @endforeach
            </tbody>
        </table>

    @else

        <div class="empty">
            No appointments were found for the selected filters.
        </div>

    @endif

    <div class="section-title">
        Payment Snapshot
    </div>

    <table class="payment-table">
        <tr>
            <td class="payment-label">
                Recorded Appointment Value
            </td>

            <td class="payment-value">
                ₱{{ number_format($recordedValue, 2) }}
            </td>
        </tr>

        <tr>
            <td class="payment-label">
                Recorded Payments
            </td>

            <td class="payment-value">
                ₱{{ number_format($paidValue, 2) }}
            </td>
        </tr>

        <tr>
            <td class="payment-label">
                Outstanding Balance
            </td>

            <td class="payment-value">
                ₱{{ number_format($outstandingValue, 2) }}
            </td>
        </tr>
    </table>

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
