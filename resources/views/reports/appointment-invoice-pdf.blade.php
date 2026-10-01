<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        Appointment Invoice - {{ $referenceNumber }}
    </title>

    <style>

        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 9px;
            line-height: 1.4;
            color: #1f2937;
            background: #ffffff;
        }

        .page {
            width: 180mm;
            margin-left: 15mm;
            margin-right: 15mm;
            padding-top: 14mm;
            padding-bottom: 10mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            table-layout: fixed;
        }

        td,
        th {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .muted {
            color: #6b7280;
        }

        /* ====================================================== */
        /* HEADER */
        /* ====================================================== */

        .business-cell {
            width: 105mm;
            vertical-align: top;
        }

        .meta-cell {
            width: 75mm;
            vertical-align: top;
            text-align: right;
        }

        .business-name {
            font-size: 18px;
            font-weight: 800;
            color: #0f766e;
            letter-spacing: .3px;
        }

        .business-subtitle {
            margin-top: 2px;
            font-size: 7px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .9px;
        }

        .business-location {
            margin-top: 2px;
            font-size: 7px;
            color: #9ca3af;
        }

        .invoice-title {
            font-size: 16px;
            font-weight: 800;
            color: #111827;
            letter-spacing: .8px;
        }

        .meta-line {
            margin-top: 3px;
            font-size: 7.5px;
            color: #6b7280;
        }

        .meta-value {
            font-weight: 700;
            color: #111827;
        }

        .header-line {
            width: 100%;
            height: 2px;
            background: #0f766e;
            margin-top: 14px;
        }

        /* ====================================================== */
        /* CUSTOMER / APPOINTMENT */
        /* ====================================================== */

        .details-section {
            margin-top: 18px;
        }

        .details-table {
            border: 1px solid #e5e7eb;
        }

        .details-table td {
            width: 50%;
            padding: 10px;
            vertical-align: top;
            border-bottom: 1px solid #e5e7eb;
        }

        .details-table td:first-child {
            border-right: 1px solid #e5e7eb;
        }

        .details-table tr:last-child td {
            border-bottom: none;
        }

        .field-label {
            font-size: 7px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .field-value {
            margin-top: 3px;
            font-size: 10px;
            font-weight: 700;
            color: #111827;
        }

        /* ====================================================== */
        /* SERVICES */
        /* ====================================================== */

        .services-section {
            margin-top: 20px;
        }

        .section-heading {
            margin-bottom: 7px;
            font-size: 8px;
            font-weight: 800;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: .9px;
        }

        .services-table {
            border: 1px solid #dfe4ea;
        }

        .services-table th {
            padding: 8px 9px;
            background: #f8fafc;
            border-bottom: 1px solid #d1d5db;
            font-size: 7px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .6px;
            text-align: left;
        }

        .services-table th:last-child {
            text-align: right;
        }

        .services-table td {
            padding: 9px;
            font-size: 9px;
            border-bottom: 1px solid #edf0f3;
            vertical-align: middle;
        }

        .services-table tr:last-child td {
            border-bottom: none;
        }

        .service-name {
            font-weight: 700;
            color: #111827;
        }

        .extra-badge {
            display: inline-block;
            margin-left: 5px;
            padding: 2px 5px;
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            color: #0f766e;
            font-size: 6px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .service-amount {
            text-align: right;
            color: #111827;
            font-weight: 700;
            white-space: nowrap;
        }

        /* ====================================================== */
        /* TOTAL */
        /* ====================================================== */

        .total-wrap {
            margin-top: 10px;
        }

        .total-table {
            width: 72mm;
            margin-left: auto;
        }

        .total-table td {
            padding: 8px 0;
            border-top: 2px solid #0f766e;
        }

        .total-label {
            width: 40mm;
            color: #111827;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .total-value {
            width: 32mm;
            text-align: right;
            color: #0f766e;
            font-size: 13px;
            font-weight: 800;
            white-space: nowrap;
        }

        /* ====================================================== */
        /* PAYMENT */
        /* ====================================================== */

        .payment-section {
            margin-top: 22px;
        }

        .payment-table {
            border: 1px solid #dfe4ea;
        }

        .payment-cell {
            width: 50%;
            padding: 9px 10px;
            vertical-align: top;
        }

        .payment-cell + .payment-cell {
            border-left: 1px solid #e5e7eb;
        }

        .payment-label {
            font-size: 7px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .payment-value {
            margin-top: 3px;
            font-size: 10px;
            font-weight: 700;
            color: #111827;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .amount-paid {
            color: #047857;
        }

        /* ====================================================== */
        /* FOOTER */
        /* ====================================================== */

        .footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #d1d5db;
        }

        .footer-left {
            width: 90mm;
            vertical-align: top;
        }

        .footer-right {
            width: 90mm;
            vertical-align: top;
            text-align: right;
        }

        .footer-label {
            font-size: 6.5px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .footer-value {
            margin-top: 2px;
            font-size: 7.5px;
            font-weight: 700;
            color: #4b5563;
        }

        .thank-you {
            margin-top: 12px;
            text-align: center;
            font-size: 7px;
            color: #9ca3af;
        }

    </style>

</head>

<body>

@php

    $customerName = $customer
        ? trim(
            ($customer->first_name ?? '') . ' ' .
            ($customer->last_name ?? '')
        )
        : 'Walk-in Customer';

    $customerName =
        $customerName ?: 'Walk-in Customer';


    $appointmentDate =
        $appointment->appointment_date
        ? $appointment->appointment_date->format('F d, Y')
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
        : '—';


    $paymentMethodText =
        $paymentMethods
            ->map(
                fn ($method) =>
                    str_replace(
                        '_',
                        ' ',
                        $method
                    )
            )
            ->implode(', ');

@endphp


<div class="page">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    @include('partials.report_header', [
        'reportTitle' => 'Appointment Invoice',
        'referenceNumber' => $referenceNumber,
        'dateRange' => $appointmentDate,
        'generatedAt' => $generatedAt->format('F d, Y h:i A'),
        'preparedBy' => $preparedBy ?? 'System Administrator'
    ])


    {{-- ========================================================= --}}
    {{-- CUSTOMER / APPOINTMENT --}}
    {{-- ========================================================= --}}

    <div class="details-section">

        <table class="details-table">

            <tr>

                <td>

                    <div class="field-label">
                        Customer
                    </div>

                    <div class="field-value">
                        {{ $customerName }}
                    </div>

                </td>


                <td>

                    <div class="field-label">
                        Phone
                    </div>

                    <div class="field-value">
                        {{ $customer->phone_number ?? '—' }}
                    </div>

                </td>

            </tr>


            <tr>

                <td>

                    <div class="field-label">
                        Appointment Date
                    </div>

                    <div class="field-value">
                        {{ $appointmentDate }}
                    </div>

                </td>


                <td>

                    <div class="field-label">
                        Appointment Time
                    </div>

                    <div class="field-value">
                        {{ $startTime }} – {{ $endTime }}
                    </div>

                </td>

            </tr>

        </table>

    </div>


    {{-- ========================================================= --}}
    {{-- SERVICES --}}
    {{-- ========================================================= --}}

    <div class="services-section">

        <div class="section-heading">
            Services
        </div>


        <table class="services-table">

            <colgroup>

                <col style="width:130mm;">

                <col style="width:50mm;">

            </colgroup>


            <thead>

                <tr>

                    <th>
                        Service
                    </th>

                    <th>
                        Amount
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($services as $service)

                    @php

                        $serviceName =
                            $service->pivot->service_name
                            ?? $service->name
                            ?? 'Service';

                        $servicePrice =
                            $service->pivot->price_at_booking
                            ?? $service->price
                            ?? 0;

                        $isExtra = (bool) (
                            $service->pivot->is_extra ?? false
                        );

                    @endphp


                    <tr>

                        <td>

                            <span class="service-name">
                                {{ $serviceName }}
                            </span>


                            @if($isExtra)

                                <span class="extra-badge">
                                    Extra
                                </span>

                            @endif

                        </td>


                        <td class="service-amount">

                            ₱{{ number_format(
                                (float) $servicePrice,
                                2
                            ) }}

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td
                            colspan="2"
                            class="center muted"
                        >
                            No service records found.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>


        <div class="total-wrap">

            <table class="total-table">

                <tr>

                    <td class="total-label">
                        Total
                    </td>

                    <td class="total-value">
                        ₱{{ number_format(
                            $total,
                            2
                        ) }}
                    </td>

                </tr>

            </table>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- PAYMENT --}}
    {{-- ========================================================= --}}

    <div class="payment-section">

        <div class="section-heading">
            Payment
        </div>


        <table class="payment-table">

            <tr>

                <td class="payment-cell">

                    <div class="payment-label">
                        Payment Method
                    </div>

                    <div class="payment-value">
                        {{ $paymentMethodText ?: '—' }}
                    </div>

                </td>


                <td class="payment-cell">

                    <div class="payment-label">
                        Amount Paid
                    </div>

                    <div class="payment-value amount-paid">
                        ₱{{ number_format(
                            $totalPaid,
                            2
                        ) }}
                    </div>

                </td>

            </tr>

        </table>

    </div>


    {{-- ========================================================= --}}
    {{-- FOOTER --}}
    {{-- ========================================================= --}}

    <div class="footer">

        <table>

            <tr>

                <td class="footer-left">

                    <div class="footer-label">
                        Appointment Reference
                    </div>

                    <div class="footer-value">
                        {{ $referenceNumber }}
                    </div>

                </td>


                <td class="footer-right">

                    <div class="footer-label">
                        Generated
                    </div>

                    <div class="footer-value">
                        {{ $generatedAt
                            ->timezone('Asia/Manila')
                            ->format(
                                'F d, Y \a\t g:i A'
                            ) }}
                    </div>

                </td>

            </tr>

        </table>


        <div class="thank-you">
            Thank you for choosing Spa Alexandria.
        </div>

    </div>

</div>

</body>

</html>
