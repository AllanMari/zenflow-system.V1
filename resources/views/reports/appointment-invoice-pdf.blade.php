<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment and Payment Summary - {{ $referenceNumber }}</title>
    <style>
        @page {
            margin: 10mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #000000;
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 8px;
            line-height: 1.3;
        }

        .page {
            width: 100%;
            margin: 0;
            padding: 0;
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

        .right { text-align: right; }
        .center { text-align: center; }
        .muted { color: #4b5563; }

        /* Compact 2-column info block with thin horizontal rules */
        .meta-block {
            width: 100%;
            border-bottom: 1px solid #d1d5db;
            margin-bottom: 6px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .meta-block td {
            padding: 2.5px 0;
            vertical-align: top;
            font-size: 7.5px;
        }

        .meta-label {
            font-size: 6.5px;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .meta-val {
            font-size: 8px;
            font-weight: 700;
            color: #000000;
        }

        .section-heading {
            margin: 5px 0 3px 0;
            font-size: 7.5px;
            font-weight: 700;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Itemized Services Table */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        table.items-table thead th {
            padding: 4px 2px;
            border-top: 1px solid #111111;
            border-bottom: 1px solid #111111;
            background: #f3f4f6;
            font-size: 7px;
            font-weight: 700;
            color: #000000;
            text-transform: uppercase;
            text-align: left;
        }

        table.items-table thead th:last-child {
            text-align: right;
        }

        table.items-table tbody td {
            padding: 4px 2px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 8px;
            color: #000000;
            vertical-align: top;
        }

        .service-tag {
            font-size: 6.5px;
            color: #333333;
            font-weight: 700;
        }

        .service-amount {
            text-align: right;
            font-weight: 700;
            white-space: nowrap;
        }

        /* Financial Reconciliation */
        .reconciliation {
            width: 100%;
            border-top: 1px solid #111111;
            padding-top: 4px;
            margin-top: 2px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .recon-table {
            width: 100%;
        }

        .recon-table td {
            padding: 2px 0;
            font-size: 7.8px;
            color: #333333;
        }

        .recon-table td.val {
            text-align: right;
            font-weight: 700;
            color: #000000;
            white-space: nowrap;
        }

        .recon-table tr.row-total td {
            padding: 4px 0;
            border-top: 1px solid #111111;
            border-bottom: 1px solid #d1d5db;
            font-size: 9.5px;
            font-weight: 700;
            color: #000000;
        }

        .recon-table tr.row-balance td {
            padding: 4px 0;
            border-top: 1px solid #111111;
            border-bottom: 1.5px solid #111111;
            font-size: 9.5px;
            font-weight: 700;
            color: #000000;
        }

        .payment-meta {
            margin-top: 5px;
            padding-top: 4px;
            border-top: 1px solid #d1d5db;
            font-size: 7.2px;
            color: #333333;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .invoice-footer {
            margin-top: 7px;
            padding-top: 4px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: 6.5px;
            color: #4b5563;
            page-break-inside: avoid;
            break-inside: avoid;
        }
    </style>
    @include('partials.pdf_page_styles')
    @if(isset($pdfLayout) && $pdfLayout instanceof \App\Support\Pdf\PdfLayoutConfig)
        @php
            $effectiveInvoiceWidthMm = $pdfLayout->hasContentFrame() && !empty($pdfLayout->contentFrame['width_mm'])
                ? (float) $pdfLayout->contentFrame['width_mm']
                : $pdfLayout->printableWidthMm();
            $isNarrowThermal = in_array($pdfLayout->paper, ['roll_58', 'roll_80'], true)
                || $effectiveInvoiceWidthMm < 65.0;
        @endphp
        <style>
            @if($pdfLayout->hasContentFrame())
            .page.pdf-content-frame {
                width: {{ (float) $pdfLayout->contentFrame['width_mm'] }}mm;
                border: 1px solid #111111;
                padding: 4mm;
            }
            @endif
            @if(!$pdfLayout->isCompactWidth())
            body { font-size: 9px; line-height: 1.38; }
            .meta-label { font-size: 7px; }
            .meta-val { font-size: 8.8px; }
            table.items-table thead th { font-size: 7.5px; padding: 5px 4px; }
            table.items-table tbody td { font-size: 8.8px; padding: 5px 4px; }
            .recon-table td { font-size: 8.5px; padding: 2.5px 0; }
            .recon-table tr.row-total td,
            .recon-table tr.row-balance td { font-size: 10.5px; }
            @endif
            @if($isNarrowThermal)
            body { font-size: 7.3px; }
            .meta-block td { display: block; width: 100% !important; padding: 1.5px 0; }
            .meta-val { font-size: 7.5px; }
            table.items-table thead th { font-size: 6.5px; padding: 3px 1px; }
            table.items-table tbody td { font-size: 7.3px; padding: 3px 1px; }
            .recon-table td { font-size: 7.2px; }
            .recon-table tr.row-total td,
            .recon-table tr.row-balance td { font-size: 8.5px; }
            @endif
        </style>
    @endif
</head>
<body>
@php
    $customerName = !empty($clientName)
        ? $clientName
        : ($appointment->client_name ?? ($customerName ?? 'Walk-in Customer'));

    $customerPhone = !empty($clientPhone)
        ? $clientPhone
        : ($appointment->client_phone ?? ($customer->phone_number ?? '—'));

    $appointmentDate = $appointment->appointment_date
        ? $appointment->appointment_date->format('M d, Y')
        : '—';

    $startTime = $appointment->start_time
        ? \Carbon\Carbon::parse($appointment->start_time)->format('g:i A')
        : '—';

    $endTime = $appointment->end_time
        ? \Carbon\Carbon::parse($appointment->end_time)->format('g:i A')
        : '—';

    $paymentMethodText = $paymentMethods
        ->map(fn ($method) => ucwords(str_replace('_', ' ', (string) $method)))
        ->implode(', ');

    $assignedStaffCollection = collect($assignedStaff ?? [])->filter()->unique('id')->values();
    $staffDisplay = !empty($staffNames)
        ? $staffNames
        : ($assignedStaffCollection->isNotEmpty()
            ? $assignedStaffCollection
                ->map(fn ($member) => trim((string) ($member->full_name ?? $member->name ?? '')))
                ->filter()
                ->unique()
                ->join(', ')
            : ($staff?->full_name ?? $staff?->name ?? ($appointment->staff?->full_name ?? $appointment->staff?->name ?? 'Unassigned')));
    $hasMultipleTherapists = $assignedStaffCollection->count() > 1 || substr_count((string) $staffDisplay, ',') >= 1;

    $bookedServiceItems = collect($services ?? []);
    $standalonePackageItems = collect($packages ?? []);
    $displayItems = $bookedServiceItems->isNotEmpty()
        ? $bookedServiceItems
        : $standalonePackageItems;
    $packageSelectionItems = collect($packageSelections ?? []);
    $packageCountInItems = $displayItems->filter(fn ($item) => (bool) ($item->is_package ?? false))->count();
    $resolvedPackageIncludedMap = is_array($packageIncludedMap ?? null) ? $packageIncludedMap : [];

    // Fallback: if rendered directly without controller-built $packageIncludedMap, resolve existing catalog names once
    if ($resolvedPackageIncludedMap === [] && $packageCountInItems > 0) {
        $fallbackIncludedIds = $displayItems
            ->filter(fn ($item) => (bool) ($item->is_package ?? false))
            ->flatMap(function ($item) {
                $raw = $item->included_services ?? [];
                if (is_string($raw)) {
                    $raw = json_decode($raw, true) ?: [];
                }
                return is_array($raw) ? $raw : [];
            })
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($fallbackIncludedIds->isNotEmpty()) {
            $fallbackNamesById = \App\Models\Service::whereIn('id', $fallbackIncludedIds)->pluck('name', 'id');
            foreach ($displayItems as $item) {
                if (!empty($item->is_package) && !empty($item->id)) {
                    $raw = is_string($item->included_services ?? null)
                        ? (json_decode($item->included_services, true) ?: [])
                        : ($item->included_services ?? []);
                    $resolvedPackageIncludedMap[$item->id] = collect(is_array($raw) ? $raw : [])
                        ->map(fn ($id) => $fallbackNamesById->get((int) $id))
                        ->filter()
                        ->values()
                        ->all();
                }
            }
        }
    }

    $roomDisplay = $room?->name ?? ($appointment->room?->name ?? '—');

    $paidAmount = (float) ($totalPaid ?? 0);
    $refundedAmount = (float) ($totalRefunded ?? 0);
    $netPaidAmount = isset($netPaid) ? (float) $netPaid : ($paidAmount - $refundedAmount);
    $balanceDue = isset($balance) ? (float) $balance : max(0.0, (float) $total - $netPaidAmount);

    if ($balanceDue <= 0.009 && $netPaidAmount > 0) {
        $paymentStatus = 'Paid in Full';
    } elseif ($netPaidAmount > 0 && $balanceDue > 0.009) {
        $paymentStatus = 'Partial Payment / Balance Due';
    } else {
        $paymentStatus = 'Unpaid / Balance Due';
    }
@endphp

<div class="page {{ (isset($pdfLayout) && $pdfLayout instanceof \App\Support\Pdf\PdfLayoutConfig && $pdfLayout->hasContentFrame()) ? 'pdf-content-frame' : '' }}">
    @include('partials.report_header', [
        'reportTitle' => 'Appointment and Payment Summary',
        'referenceNumber' => $referenceNumber,
        'dateRange' => $appointmentDate . ' (' . $startTime . '–' . $endTime . ')',
    ])

    {{-- Reference & BIR Invoice Summary --}}
    <table class="meta-block">
        <tr>
            <td style="width: 52%; padding-right: 4px;">
                <div class="meta-label">Internal Transaction Reference</div>
                <div class="meta-val">{{ $referenceNumber }}</div>
            </td>
            <td style="width: 48%; padding-left: 4px;">
                <div class="meta-label">BIR Invoice Number</div>
                <div class="meta-val">{{ !empty($birInvoiceNumber) ? $birInvoiceNumber : 'Not Recorded' }}</div>
            </td>
        </tr>
    </table>

    {{-- Customer & Appointment Details --}}
    <table class="meta-block">
        <tr>
            <td style="width: 52%; padding-right: 4px;">
                <div class="meta-label">Customer</div>
                <div class="meta-val">{{ $customerName }}</div>
                <div class="muted" style="font-size: 7px;">{{ $customerPhone ?: '—' }}</div>
            </td>
            <td style="width: 48%; padding-left: 4px;">
                <div class="meta-label">{{ $hasMultipleTherapists ? 'Assigned Therapists' : 'Therapist' }} / Room</div>
                <div class="meta-val">{{ $staffDisplay }}</div>
                <div class="muted" style="font-size: 7px;">Room: {{ $roomDisplay }}</div>
            </td>
        </tr>
    </table>

    {{-- Itemized Services & Packages --}}
    <div class="section-heading">{{ $packageCountInItems > 0 ? 'Services & Packages' : 'Services' }}</div>
    <table class="items-table pdf-scalable-table">
        <colgroup>
            <col style="width: 70%;">
            <col style="width: 30%;">
        </colgroup>
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @if($displayItems->isNotEmpty())
                @php $renderedSelectionIds = []; @endphp
                @foreach($displayItems as $service)
                    @php
                        $serviceName = $service->pivot->service_name ?? $service->name ?? 'Service';
                        $servicePrice = $service->pivot->price_at_booking ?? $service->price ?? 0;
                        $isExtra = (bool) ($service->pivot->is_extra ?? false);
                        $isPackage = (bool) ($service->is_package ?? false);
                        $participantsReq = (int) ($service->participants_required ?? 1);
                        $therapistsReq = (int) ($service->therapists_required ?? 1);
                        $isDuo = $participantsReq >= 2 || stripos($serviceName, 'couple') !== false;
                        $isFourHands = !$isDuo && $therapistsReq >= 2;

                        $includedNames = $isPackage && !empty($service->id)
                            ? ($resolvedPackageIncludedMap[$service->id] ?? [])
                            : [];

                        $matchingSelections = collect();
                        if ($isPackage && $packageSelectionItems->isNotEmpty()) {
                            $matchingSelections = $packageSelectionItems->filter(function ($sel) use ($service, $packageCountInItems) {
                                $groupServiceId = $sel->choiceGroup?->service_id;
                                if ($groupServiceId !== null && !empty($service->id)) {
                                    return (int) $groupServiceId === (int) $service->id;
                                }
                                return $packageCountInItems === 1;
                            })->values();
                            foreach ($matchingSelections as $sel) {
                                if (!empty($sel->id)) {
                                    $renderedSelectionIds[] = $sel->id;
                                }
                            }
                        }
                    @endphp
                    <tr>
                        <td>
                            <span style="font-weight: 700;">{{ $serviceName }}</span>
                            @if($isPackage)
                                <span class="service-tag">[Package]</span>
                            @endif
                            @if($isDuo)
                                <span class="service-tag">[Duo · 2 Guests · {{ max(2, $therapistsReq) }} Therapists]</span>
                            @elseif($isFourHands)
                                <span class="service-tag">[Four-Hands · {{ $therapistsReq }} Therapists]</span>
                            @endif
                            @if($isExtra)
                                <span class="service-tag">[Extra]</span>
                            @endif
                            @if(!empty($includedNames))
                                <div class="muted" style="font-size: 6.8px; margin-top: 1.5px;">
                                    Includes: {{ implode(', ', $includedNames) }}
                                </div>
                            @endif
                            @if($matchingSelections->isNotEmpty())
                                <div class="muted" style="font-size: 6.8px; margin-top: 1px;">
                                    Selected: {{ $matchingSelections->map(fn ($sel) => $sel->choice_group_name . ': ' . $sel->selected_name)->join('; ') }}
                                </div>
                            @endif
                        </td>
                        <td class="service-amount">₱{{ number_format((float) $servicePrice, 2) }}</td>
                    </tr>
                @endforeach
                @php
                    $remainingSelections = $packageSelectionItems->filter(
                        fn ($sel) => empty($sel->id) || !in_array($sel->id, $renderedSelectionIds, true)
                    );
                @endphp
                @if($remainingSelections->isNotEmpty())
                    <tr>
                        <td colspan="2" class="muted" style="font-size: 6.8px;">
                            Package Selections: {{ $remainingSelections->map(fn ($sel) => $sel->choice_group_name . ': ' . $sel->selected_name)->join('; ') }}
                        </td>
                    </tr>
                @endif
            @elseif($packageSelectionItems->isNotEmpty())
                <tr>
                    <td>
                        <span style="font-weight: 700;">Package Booking</span>
                        <span class="service-tag">[Package]</span>
                        <div class="muted" style="font-size: 6.8px; margin-top: 1.5px;">
                            Selected: {{ $packageSelectionItems->map(fn ($sel) => $sel->choice_group_name . ': ' . $sel->selected_name)->join('; ') }}
                        </div>
                    </td>
                    <td class="service-amount">₱{{ number_format((float) $total, 2) }}</td>
                </tr>
            @else
                <tr>
                    <td colspan="2" class="center muted">No service records found.</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- Financial Reconciliation --}}
    <div class="reconciliation">
        <table class="recon-table">
            <colgroup>
                <col style="width: 60%;">
                <col style="width: 40%;">
            </colgroup>
            @if(!empty($hasDiscount))
                <tr>
                    <td>Original Amount</td>
                    <td class="val">₱{{ number_format((float) $originalTotal, 2) }}</td>
                </tr>
                <tr>
                    <td>{{ $discountLabel ?: 'Discount' }}</td>
                    <td class="val">-₱{{ number_format((float) $discountAmount, 2) }}</td>
                </tr>
            @endif
            <tr class="row-total">
                <td>TOTAL</td>
                <td class="val">₱{{ number_format((float) $total, 2) }}</td>
            </tr>
            <tr>
                <td>Amount Paid</td>
                <td class="val">₱{{ number_format($paidAmount, 2) }}</td>
            </tr>
            @if($refundedAmount > 0)
                <tr>
                    <td>Refunded</td>
                    <td class="val">-₱{{ number_format($refundedAmount, 2) }}</td>
                </tr>
                <tr>
                    <td>Net Paid</td>
                    <td class="val">₱{{ number_format($netPaidAmount, 2) }}</td>
                </tr>
            @endif
            <tr class="row-balance">
                <td>BALANCE DUE</td>
                <td class="val">₱{{ number_format($balanceDue, 2) }}</td>
            </tr>
        </table>

        <table class="payment-meta">
            <tr>
                <td style="width: 55%;">
                    <span class="muted">Method:</span>
                    <strong style="color: #000000;">{{ $paymentMethodText ?: '—' }}</strong>
                </td>
                <td style="width: 45%; text-align: right;">
                    <span class="muted">Status:</span>
                    <strong style="color: #000000;">{{ $paymentStatus }}</strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="invoice-footer">
        <div>Prepared by: {{ $preparedBy ?? 'System Administrator' }} &bull; {{ $generatedAt->timezone('Asia/Manila')->format('M d, Y g:i A') }}</div>
        <div style="margin-top: 2px;">Thank you for choosing Spa Alexandria.</div>
        <div style="margin-top: 4px; font-size: 6px; color: #4b5563;">
            This document is a summary generated by the ZenFlow system for your reference. It is not an official invoice or receipt issued under BIR regulations. Any official invoice for this transaction is issued separately by Spa Alexandria.
        </div>
    </div>
</div>
</body>
</html>
