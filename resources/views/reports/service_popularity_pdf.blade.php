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


        /* =====================================================
           HEADER
        ====================================================== */

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


        /* =====================================================
           FILTERS
        ====================================================== */

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
            width: 50%;
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


        /* =====================================================
           SUMMARY
        ====================================================== */

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


        /* =====================================================
           SECTIONS
        ====================================================== */

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 12px 0 7px 0;
            color: #111827;
        }


        /* =====================================================
           TABLES
        ====================================================== */

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.report-table thead {
            background: #0f766e;
            color: #ffffff;
        }

        table.report-table th {
            font-size: 8px;
            font-weight: bold;
            text-align: left;
            padding: 7px 5px;
            border: 1px solid #0f766e;
        }

        table.report-table td {
            font-size: 8px;
            padding: 6px 5px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        table.report-table tr:nth-child(even) {
            background: #f9fafb;
        }


        /* =====================================================
           SERVICE / PACKAGE / CATEGORY
        ====================================================== */

        .service-name {
            font-weight: bold;
            color: #111827;
        }

        .code {
            font-size: 7px;
            color: #6b7280;
            margin-top: 2px;
        }

        .muted {
            color: #6b7280;
        }

        .number {
            text-align: center;
            font-weight: bold;
        }

        .money {
            text-align: right;
            font-weight: bold;
            color: #111827;
        }

        .percentage {
            text-align: right;
            font-weight: bold;
        }


        /* =====================================================
           BADGES
        ====================================================== */

        .badge {
            display: inline-block;
            padding: 3px 5px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
        }

        .badge-teal {
            background: #ccfbf1;
            color: #115e59;
        }

        .badge-purple {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .badge-blue {
            background: #dbeafe;
            color: #1e40af;
        }


        /* =====================================================
           EMPTY STATE
        ====================================================== */

        .empty {
            text-align: center;
            padding: 25px;
            border: 1px solid #d1d5db;
            color: #6b7280;
        }


        /* =====================================================
           INCLUDED SERVICES
        ====================================================== */

        .included-service {
            display: inline-block;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 3px;
            padding: 3px 5px;
            margin: 1px 2px 1px 0;
            font-size: 7px;
            color: #374151;
        }


        /* =====================================================
           FOOTER
        ====================================================== */

        .footer {
            border-top: 1px solid #d1d5db;
            margin-top: 20px;
            padding-top: 8px;
            font-size: 8px;
            color: #6b7280;
        }


        /* =====================================================
           PAGE BREAK
        ====================================================== */

        .page-break {
            page-break-inside: avoid;
        }

    </style>

</head>


<body>


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    @include('partials.report_header', [
        'reportTitle' => $reportTitle ?? 'Service & Package Popularity'
    ])


    {{-- =====================================================
         FILTERS
    ====================================================== --}}

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

                        {{ $dateDisplay }}

                    </div>

                </td>


                <td>

                    <div class="filter-label">
                        Category
                    </div>

                    <div class="filter-value">

                        {{ $selectedCategoryName ?: 'All Categories' }}

                    </div>

                </td>

            </tr>

        </table>

    </div>


    {{-- =====================================================
         SUMMARY
    ====================================================== --}}

    <table class="summary">

        <tr>

            <td>

                <div class="summary-label">
                    Completed Appointments
                </div>

                <div class="summary-value">
                    {{ number_format($summary['completed_appointments']) }}
                </div>

            </td>


            <td>

                <div class="summary-label">
                    Services Completed
                </div>

                <div class="summary-value">
                    {{ number_format($summary['services_completed']) }}
                </div>

            </td>


            <td>

                <div class="summary-label">
                    Services Represented
                </div>

                <div class="summary-value">
                    {{ number_format($summary['services_represented']) }}
                </div>

            </td>


            <td>

                <div class="summary-label">
                    Packages Booked
                </div>

                <div class="summary-value">
                    {{ number_format($summary['packages_booked']) }}
                </div>

            </td>


            <td>

                <div class="summary-label">
                    Package Revenue
                </div>

                <div class="summary-value">
                    ₱{{ number_format($summary['package_revenue'], 2) }}
                </div>

            </td>


            <td>

                <div class="summary-label">
                    Service Revenue
                </div>

                <div class="summary-value">
                    ₱{{ number_format($summary['service_revenue'], 2) }}
                </div>

            </td>

        </tr>

    </table>


    {{-- =====================================================
         INDIVIDUAL SERVICE POPULARITY
    ====================================================== --}}

    <div class="section-title">
        Individual Service Popularity
    </div>


    @if(count($serviceBreakdown))

        <table class="report-table">

            <thead>

                <tr>

                    <th style="width: 6%;">
                        #
                    </th>

                    <th style="width: 29%;">
                        Service
                    </th>

                    <th style="width: 20%;">
                        Category
                    </th>

                    <th style="width: 12%; text-align: center;">
                        Completed
                    </th>

                    <th style="width: 13%; text-align: right;">
                        Share
                    </th>

                    <th style="width: 20%; text-align: right;">
                        Revenue
                    </th>

                </tr>

            </thead>


            <tbody>

                @foreach($serviceBreakdown as $index => $service)

                    <tr class="page-break">

                        <td class="number">
                            {{ $index + 1 }}
                        </td>


                        <td>

                            <div class="service-name">
                                {{ $service['name'] }}
                            </div>

                            @if(!empty($service['code']))

                                <div class="code">
                                    {{ $service['code'] }}
                                </div>

                            @endif

                        </td>


                        <td>
                            {{ $service['category'] }}
                        </td>


                        <td class="number">

                            <span class="badge badge-teal">
                                {{ number_format($service['completed_count']) }}
                            </span>

                        </td>


                        <td class="percentage">

                            {{ number_format($service['share'], 2) }}%

                        </td>


                        <td class="money">

                            ₱{{ number_format($service['revenue'], 2) }}

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    @else

        <div class="empty">
            No completed services were found for the selected filters.
        </div>

    @endif


    {{-- =====================================================
         PACKAGE BREAKDOWN
    ====================================================== --}}

    <div class="section-title">
        Package Breakdown
    </div>


    @if(count($packageBreakdown))

        <table class="report-table">

            <thead>

                <tr>

                    <th style="width: 25%;">
                        Package
                    </th>

                    <th style="width: 18%;">
                        Category
                    </th>

                    <th style="width: 35%;">
                        Included Services
                    </th>

                    <th style="width: 10%; text-align: center;">
                        Completed
                    </th>

                    <th style="width: 12%; text-align: right;">
                        Revenue
                    </th>

                </tr>

            </thead>


            <tbody>

                @foreach($packageBreakdown as $package)

                    <tr class="page-break">

                        <td>

                            <div class="service-name">
                                {{ $package['name'] }}
                            </div>

                            @if(!empty($package['code']))

                                <div class="code">
                                    {{ $package['code'] }}
                                </div>

                            @endif

                        </td>


                        <td>
                            {{ $package['category'] }}
                        </td>


                        <td>

                            @forelse($package['included_services'] as $included)

                                <span class="included-service">
                                    {{ $included['name'] }}
                                </span>

                            @empty

                                <span class="muted">
                                    None listed
                                </span>

                            @endforelse

                        </td>


                        <td class="number">

                            <span class="badge badge-purple">
                                {{ number_format($package['completed_count']) }}
                            </span>

                        </td>


                        <td class="money">

                            ₱{{ number_format($package['revenue'], 2) }}

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    @else

        <div class="empty">
            No completed packages were found for the selected filters.
        </div>

    @endif


    {{-- =====================================================
         CATEGORY BREAKDOWN
    ====================================================== --}}

    <div class="section-title">
        Category Breakdown
    </div>


    @if(count($categoryBreakdown))

        <table class="report-table">

            <thead>

                <tr>

                    <th style="width: 55%;">
                        Category
                    </th>

                    <th style="width: 20%; text-align: center;">
                        Services Completed
                    </th>

                    <th style="width: 25%; text-align: right;">
                        Revenue
                    </th>

                </tr>

            </thead>


            <tbody>

                @foreach($categoryBreakdown as $category)

                    <tr class="page-break">

                        <td>

                            <div class="service-name">
                                {{ $category['name'] }}
                            </div>

                        </td>


                        <td class="number">

                            <span class="badge badge-blue">
                                {{ number_format($category['completed_count']) }}
                            </span>

                        </td>


                        <td class="money">

                            ₱{{ number_format($category['revenue'], 2) }}

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    @else

        <div class="empty">
            No category activity was found for the selected filters.
        </div>

    @endif


    {{-- =====================================================
         FOOTER
    ====================================================== --}}

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
```
