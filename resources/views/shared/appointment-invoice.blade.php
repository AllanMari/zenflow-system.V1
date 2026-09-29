@extends(
    auth()->user()?->roles->contains('name', 'admin')
        ? 'layouts.admin'
        : 'layouts.receptionist'
)

@section(
    'title',
    empty($appointment)
        ? 'Appointment Invoices'
        : 'Appointment Invoice'
)

@section('content')

@php
    $isAdmin = auth()->user()?->roles->contains('name', 'admin');

    $invoiceListRoute = $isAdmin
        ? 'admin.appointment-invoice'
        : 'receptionist.appointment-invoice';

    $invoiceViewRoute = $isAdmin
        ? 'admin.invoice'
        : 'receptionist.invoice';

    $previewRoute = $isAdmin
        ? 'admin.invoice.preview'
        : 'receptionist.invoice.preview';

    $downloadRoute = $isAdmin
        ? 'admin.invoice.download'
        : 'receptionist.invoice.download';
@endphp


<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"
>

<script
    src="https://unpkg.com/lucide@latest"
    defer
></script>

<script
    src="https://cdn.jsdelivr.net/npm/flatpickr"
    defer
></script>


@if(empty($appointment))

    {{-- ========================================================= --}}
    {{-- APPOINTMENT INVOICE LIST --}}
    {{-- ========================================================= --}}

    <div class="w-full max-w-7xl mx-auto px-0 sm:px-2 lg:px-4 -mt-6 pt-1 pb-6">

        <div
            class="flex flex-col lg:flex-row
                   lg:items-end lg:justify-between
                   gap-5 mb-6"
        >

            <div>

                <h1
                    class="text-xl sm:text-2xl
                           font-bold tracking-tight
                           text-gray-900 dark:text-white"
                >
                    Invoices
                </h1>

                <p
                    class="mt-1 text-sm
                           text-gray-500 dark:text-gray-400"
                >
                    Completed appointments and their individual invoices.
                </p>

            </div>


            <form
                method="GET"
                action="{{ route($invoiceListRoute) }}"
                class="flex flex-col sm:flex-row
                       items-stretch sm:items-end gap-2"
            >

                <div class="w-full sm:w-56">

                    <label
                        for="invoice-date"
                        class="block text-xs font-semibold
                               text-gray-500 dark:text-gray-400
                               mb-1.5"
                    >
                        Appointment Date
                    </label>

                    <div class="relative">

                        <div
                            class="absolute inset-y-0 start-0
                                   flex items-center
                                   pointer-events-none
                                   ps-3"
                        >
                            <i
                                data-lucide="calendar-days"
                                class="w-4 h-4 text-gray-400"
                            ></i>
                        </div>

                        <input
                            id="invoice-date"
                            name="date"
                            type="text"
                            value="{{ $selectedDate ?? '' }}"
                            placeholder="Select date"
                            autocomplete="off"
                            class="py-2.5 ps-10 block w-full
                                   border border-gray-200
                                   dark:border-gray-700
                                   rounded-xl
                                   bg-white dark:bg-gray-800
                                   text-sm
                                   text-gray-900
                                   dark:text-white
                                   placeholder:text-gray-400
                                   focus:border-brand-500
                                   focus:ring-brand-500
                                   shadow-sm"
                        >

                    </div>

                </div>


                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2
                           px-4 py-2.5 rounded-xl
                           bg-brand-600 hover:bg-brand-700
                           text-white text-sm font-semibold
                           shadow-sm transition"
                >
                    <i
                        data-lucide="filter"
                        class="w-4 h-4"
                    ></i>

                    Filter
                </button>


                @if(!empty($selectedDate))

                    <a
                        href="{{ route($invoiceListRoute) }}"
                        class="inline-flex items-center justify-center gap-2
                               px-4 py-2.5 rounded-xl
                               border border-gray-200
                               dark:border-gray-700
                               bg-white dark:bg-gray-800
                               text-gray-700
                               dark:text-gray-200
                               hover:bg-gray-50
                               dark:hover:bg-gray-700
                               text-sm font-semibold
                               shadow-sm transition"
                    >
                        <i
                            data-lucide="x"
                            class="w-4 h-4"
                        ></i>

                        Clear
                    </a>

                @endif

            </form>

        </div>


        <div
            class="bg-white dark:bg-[#111827]
                   border border-gray-200
                   dark:border-gray-700/70
                   rounded-2xl
                   shadow-sm
                   overflow-hidden"
        >

            <div
                class="px-5 sm:px-6 py-4
                       border-b border-gray-200
                       dark:border-gray-700"
            >

                <div
                    class="flex items-center
                           justify-between gap-4"
                >

                    <div>

                        <h2
                            class="text-sm font-bold
                                   text-gray-900 dark:text-white"
                        >
                            Completed Appointment Invoices
                        </h2>

                        <p
                            class="text-xs
                                   text-gray-500
                                   dark:text-gray-400
                                   mt-0.5"
                        >
                            Each appointment has its own invoice.
                        </p>

                    </div>

                    <span
                        class="hidden sm:inline-flex
                               items-center gap-1.5
                               px-2.5 py-1 rounded-full
                               bg-gray-100 dark:bg-gray-800
                               text-xs font-semibold
                               text-gray-600
                               dark:text-gray-300"
                    >
                        <i
                            data-lucide="files"
                            class="w-3.5 h-3.5"
                        ></i>

                        {{ $appointments->total() }}
                    </span>

                </div>

            </div>


            @if($appointments->count())

                <div class="overflow-x-auto">

                    <table class="w-full min-w-[940px]">

                        <thead
                            class="bg-gray-50
                                   dark:bg-gray-800/70"
                        >

                            <tr>

                                <th
                                    class="px-5 py-3.5 text-left
                                           text-[10px] font-bold
                                           uppercase tracking-wider
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Reference
                                </th>

                                <th
                                    class="px-5 py-3.5 text-left
                                           text-[10px] font-bold
                                           uppercase tracking-wider
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Customer
                                </th>

                                <th
                                    class="px-5 py-3.5 text-left
                                           text-[10px] font-bold
                                           uppercase tracking-wider
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Appointment
                                </th>

                                <th
                                    class="px-5 py-3.5 text-left
                                           text-[10px] font-bold
                                           uppercase tracking-wider
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Staff
                                </th>

                                <th
                                    class="px-5 py-3.5 text-right
                                           text-[10px] font-bold
                                           uppercase tracking-wider
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Total
                                </th>

                                <th
                                    class="px-5 py-3.5 text-right
                                           text-[10px] font-bold
                                           uppercase tracking-wider
                                           text-gray-500
                                           dark:text-gray-400"
                                >
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody
                            class="divide-y
                                   divide-gray-200
                                   dark:divide-gray-700"
                        >

                            @foreach($appointments as $invoiceAppointment)

                                @php
                                    $listCustomer =
                                        $invoiceAppointment->customer;

                                    $listCustomerName =
                                        $listCustomer
                                            ? trim(
                                                ($listCustomer->first_name ?? '') .
                                                ' ' .
                                                ($listCustomer->last_name ?? '')
                                            )
                                            : 'Walk-in Customer';

                                    $listCustomerName =
                                        $listCustomerName
                                        ?: 'Walk-in Customer';

                                    $listStaffName =
                                        $invoiceAppointment->staff
                                            ? trim(
                                                ($invoiceAppointment->staff->first_name ?? '') .
                                                ' ' .
                                                ($invoiceAppointment->staff->last_name ?? '')
                                            )
                                            : '—';

                                    $listStaffName =
                                        $listStaffName ?: '—';

                                    $listReferenceNumber =
                                        'ZF-' . str_pad(
                                            (string) $invoiceAppointment->id,
                                            6,
                                            '0',
                                            STR_PAD_LEFT
                                        );
                                @endphp


                                <tr
                                    class="hover:bg-gray-50/70
                                           dark:hover:bg-gray-800/30
                                           transition"
                                >

                                    <td class="px-5 py-4">

                                        <div
                                            class="flex items-center gap-3"
                                        >

                                            <span
                                                class="inline-flex
                                                       items-center
                                                       justify-center
                                                       w-9 h-9 rounded-xl
                                                       bg-brand-50
                                                       dark:bg-brand-900/20
                                                       text-brand-600
                                                       dark:text-brand-400"
                                            >
                                                <i
                                                    data-lucide="receipt"
                                                    class="w-4 h-4"
                                                ></i>
                                            </span>

                                            <div>

                                                <a
                                                    href="{{ route(
                                                        $invoiceViewRoute,
                                                        $invoiceAppointment
                                                    ) }}"
                                                    class="text-sm
                                                           font-bold
                                                           text-gray-900
                                                           dark:text-white
                                                           hover:text-brand-600
                                                           dark:hover:text-brand-400
                                                           transition"
                                                >
                                                    {{ $listReferenceNumber }}
                                                </a>

                                                <div
                                                    class="text-[10px]
                                                           text-gray-400
                                                           dark:text-gray-500
                                                           mt-0.5"
                                                >
                                                    Appointment #{{ $invoiceAppointment->id }}
                                                </div>

                                            </div>

                                        </div>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div
                                            class="text-sm font-semibold
                                                   text-gray-900
                                                   dark:text-white"
                                        >
                                            {{ $listCustomerName }}
                                        </div>

                                        <div
                                            class="text-xs
                                                   text-gray-500
                                                   dark:text-gray-400
                                                   mt-0.5"
                                        >
                                            {{ $listCustomer?->phone_number ?? '—' }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div
                                            class="flex items-center gap-2"
                                        >

                                            <i
                                                data-lucide="calendar-days"
                                                class="w-3.5 h-3.5
                                                       text-brand-600
                                                       dark:text-brand-400"
                                            ></i>

                                            <span
                                                class="text-sm
                                                       text-gray-800
                                                       dark:text-gray-200"
                                            >
                                                {{
                                                    $invoiceAppointment
                                                        ->appointment_date
                                                        ?->format('M d, Y')
                                                    ?? '—'
                                                }}
                                            </span>

                                        </div>


                                        @if(
                                            $invoiceAppointment->start_time &&
                                            $invoiceAppointment->end_time
                                        )

                                            <div
                                                class="flex items-center
                                                       gap-2 mt-1"
                                            >

                                                <i
                                                    data-lucide="clock-3"
                                                    class="w-3.5 h-3.5
                                                           text-gray-400"
                                                ></i>

                                                <span
                                                    class="text-xs
                                                           text-gray-500
                                                           dark:text-gray-400"
                                                >
                                                    {{
                                                        \Carbon\Carbon::parse(
                                                            $invoiceAppointment->start_time
                                                        )->format('g:i A')
                                                    }}

                                                    –

                                                    {{
                                                        \Carbon\Carbon::parse(
                                                            $invoiceAppointment->end_time
                                                        )->format('g:i A')
                                                    }}
                                                </span>

                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4">

                                        <span
                                            class="text-sm
                                                   text-gray-700
                                                   dark:text-gray-300"
                                        >
                                            {{ $listStaffName }}
                                        </span>

                                    </td>


                                    <td
                                        class="px-5 py-4
                                               text-right"
                                    >

                                        <span
                                            class="text-sm font-bold
                                                   text-gray-900
                                                   dark:text-white"
                                        >
                                            ₱{{ number_format(
                                                (float)
                                                $invoiceAppointment->total_price,
                                                2
                                            ) }}
                                        </span>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div
                                            class="flex items-center
                                                   justify-end gap-1.5"
                                        >

                                            <a
                                                href="{{ route(
                                                    $invoiceViewRoute,
                                                    $invoiceAppointment
                                                ) }}"
                                                title="View Invoice"
                                                class="inline-flex
                                                       items-center
                                                       justify-center
                                                       w-9 h-9 rounded-lg
                                                       border
                                                       border-gray-200
                                                       dark:border-gray-700
                                                       bg-white
                                                       dark:bg-gray-800
                                                       text-gray-600
                                                       dark:text-gray-300
                                                       hover:text-brand-600
                                                       dark:hover:text-brand-400
                                                       transition"
                                            >
                                                <i
                                                    data-lucide="eye"
                                                    class="w-4 h-4"
                                                ></i>
                                            </a>


                                            <a
                                                href="{{ route(
                                                    $previewRoute,
                                                    $invoiceAppointment
                                                ) }}"
                                                target="_blank"
                                                rel="noopener"
                                                title="Preview PDF"
                                                class="inline-flex
                                                       items-center
                                                       justify-center
                                                       w-9 h-9 rounded-lg
                                                       border
                                                       border-gray-200
                                                       dark:border-gray-700
                                                       bg-white
                                                       dark:bg-gray-800
                                                       text-gray-600
                                                       dark:text-gray-300
                                                       hover:text-brand-600
                                                       dark:hover:text-brand-400
                                                       transition"
                                            >
                                                <i
                                                    data-lucide="file-search"
                                                    class="w-4 h-4"
                                                ></i>
                                            </a>


                                            <a
                                                href="{{ route(
                                                    $downloadRoute,
                                                    $invoiceAppointment
                                                ) }}"
                                                title="Download PDF"
                                                class="inline-flex
                                                       items-center
                                                       justify-center
                                                       w-9 h-9 rounded-lg
                                                       bg-brand-600
                                                       hover:bg-brand-700
                                                       text-white
                                                       transition"
                                            >
                                                <i
                                                    data-lucide="download"
                                                    class="w-4 h-4"
                                                ></i>
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>


                @if($appointments->hasPages())

                    <div
                        class="px-5 sm:px-6 py-4
                               border-t border-gray-200
                               dark:border-gray-700"
                    >
                        {{ $appointments->links() }}
                    </div>

                @endif


            @else

                <div class="px-6 py-16 text-center">

                    <div
                        class="mx-auto flex items-center
                               justify-center
                               w-14 h-14 rounded-2xl
                               bg-gray-100 dark:bg-gray-800
                               text-gray-400"
                    >
                        <i
                            data-lucide="file-text"
                            class="w-7 h-7"
                        ></i>
                    </div>

                    <h3
                        class="mt-4 text-sm font-bold
                               text-gray-900 dark:text-white"
                    >
                        No completed appointments found
                    </h3>

                    <p
                        class="mt-1 text-sm
                               text-gray-500
                               dark:text-gray-400"
                    >
                        Completed appointment invoices will appear here.
                    </p>

                </div>

            @endif

        </div>

    </div>


@else

    {{-- ========================================================= --}}
    {{-- INDIVIDUAL APPOINTMENT INVOICE --}}
    {{-- ========================================================= --}}

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

    @endphp


    <div
        class="w-full max-w-6xl mx-auto
               px-0 sm:px-2 lg:px-4
               py-4 sm:py-6"
    >

        {{-- Actions --}}
        <div
            class="flex flex-col sm:flex-row
                   sm:items-center
                   sm:justify-between
                   gap-4 mb-5"
        >

            <div>

                <a
                    href="{{ route($invoiceListRoute) }}"
                    class="inline-flex
                           items-center gap-1.5
                           text-xs font-semibold
                           text-gray-500
                           dark:text-gray-400
                           hover:text-brand-600
                           dark:hover:text-brand-400
                           mb-2 transition"
                >
                    <i
                        data-lucide="arrow-left"
                        class="w-3.5 h-3.5"
                    ></i>

                    Back to Appointment Invoices
                </a>

                <h1
                    class="text-xl sm:text-2xl
                           font-bold
                           text-gray-900
                           dark:text-white"
                >
                    Appointment Invoice
                </h1>

                <p
                    class="text-sm
                           text-gray-500
                           dark:text-gray-400
                           mt-1"
                >
                    {{ $referenceNumber }}
                </p>

            </div>


            <div class="flex items-center gap-2">

                <a
                    href="{{ route(
                        $previewRoute,
                        $appointment
                    ) }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex
                           items-center gap-2
                           px-3.5 py-2.5
                           rounded-xl
                           border
                           border-gray-200
                           dark:border-gray-700
                           bg-white
                           dark:bg-gray-800
                           text-gray-700
                           dark:text-gray-200
                           text-sm
                           font-semibold
                           hover:bg-gray-50
                           dark:hover:bg-gray-700
                           transition"
                >
                    <i
                        data-lucide="file-search"
                        class="w-4 h-4"
                    ></i>

                    Preview PDF
                </a>


                <a
                    href="{{ route(
                        $downloadRoute,
                        $appointment
                    ) }}"
                    class="inline-flex
                           items-center gap-2
                           px-3.5 py-2.5
                           rounded-xl
                           bg-brand-600
                           hover:bg-brand-700
                           text-white
                           text-sm
                           font-semibold
                           transition"
                >
                    <i
                        data-lucide="download"
                        class="w-4 h-4"
                    ></i>

                    Download PDF
                </a>

            </div>

        </div>


        {{-- Invoice --}}
        <div
            class="bg-white
                   dark:bg-[#111827]
                   border
                   border-gray-200
                   dark:border-gray-700
                   rounded-2xl
                   shadow-sm
                   overflow-hidden"
        >

            {{-- Header --}}
            <div
                class="px-5 sm:px-8 py-6
                       border-b
                       border-gray-200
                       dark:border-gray-700"
            >

                <div
                    class="flex flex-col
                           sm:flex-row
                           sm:items-start
                           sm:justify-between
                           gap-5"
                >

                    <div>

                        <div
                            class="text-sm
                                   font-extrabold
                                   tracking-wide
                                   text-brand-700
                                   dark:text-brand-400"
                        >
                            SPA ALEXANDRIA
                        </div>

                        <div
                            class="text-xs
                                   text-gray-500
                                   dark:text-gray-400
                                   mt-1"
                        >
                            ZenFlow Appointment & Workforce System
                        </div>

                    </div>


                    <div class="sm:text-right">

                        <div
                            class="text-[10px]
                                   font-bold
                                   uppercase
                                   tracking-wider
                                   text-gray-400"
                        >
                            Appointment Reference
                        </div>

                        <div
                            class="text-base
                                   font-bold
                                   text-gray-900
                                   dark:text-white
                                   mt-1"
                        >
                            {{ $referenceNumber }}
                        </div>

                    </div>

                </div>

            </div>


            <div class="px-5 sm:px-8 py-6">

                {{-- Appointment Details --}}
                <div>

                    <div
                        class="flex items-center
                               gap-2 mb-4"
                    >

                        <span
                            class="inline-flex
                                   items-center
                                   justify-center
                                   w-8 h-8
                                   rounded-xl
                                   bg-gray-100
                                   dark:bg-gray-800
                                   text-gray-600
                                   dark:text-gray-300"
                        >
                            <i
                                data-lucide="calendar-days"
                                class="w-4 h-4"
                            ></i>
                        </span>

                        <h2
                            class="text-sm
                                   font-bold
                                   text-gray-900
                                   dark:text-white"
                        >
                            Appointment Details
                        </h2>

                    </div>


                    <div
                        class="grid
                               grid-cols-1
                               sm:grid-cols-2
                               lg:grid-cols-4
                               border
                               border-gray-200
                               dark:border-gray-700
                               rounded-2xl
                               overflow-hidden"
                    >

                        <div
                            class="p-4
                                   border-b
                                   sm:border-r
                                   border-gray-200
                                   dark:border-gray-700"
                        >

                            <p
                                class="text-[10px]
                                       font-bold
                                       uppercase
                                       tracking-wider
                                       text-gray-400"
                            >
                                Customer
                            </p>

                            <p
                                class="mt-1.5
                                       text-sm
                                       font-bold
                                       text-gray-900
                                       dark:text-white"
                            >
                                {{ $customerName }}
                            </p>

                        </div>


                        <div
                            class="p-4
                                   border-b
                                   lg:border-r
                                   border-gray-200
                                   dark:border-gray-700"
                        >

                            <p
                                class="text-[10px]
                                       font-bold
                                       uppercase
                                       tracking-wider
                                       text-gray-400"
                            >
                                Phone
                            </p>

                            <p
                                class="mt-1.5
                                       text-sm
                                       font-bold
                                       text-gray-900
                                       dark:text-white"
                            >
                                {{ $customer?->phone_number ?? '—' }}
                            </p>

                        </div>


                        <div
                            class="p-4
                                   border-b
                                   sm:border-r
                                   border-gray-200
                                   dark:border-gray-700"
                        >

                            <p
                                class="text-[10px]
                                       font-bold
                                       uppercase
                                       tracking-wider
                                       text-gray-400"
                            >
                                Appointment Date
                            </p>

                            <p
                                class="mt-1.5
                                       text-sm
                                       font-bold
                                       text-gray-900
                                       dark:text-white"
                            >
                                {{ $appointmentDate }}
                            </p>

                        </div>


                        <div
                            class="p-4
                                   border-b
                                   border-gray-200
                                   dark:border-gray-700"
                        >

                            <p
                                class="text-[10px]
                                       font-bold
                                       uppercase
                                       tracking-wider
                                       text-gray-400"
                            >
                                Appointment Time
                            </p>

                            <p
                                class="mt-1.5
                                       text-sm
                                       font-bold
                                       text-gray-900
                                       dark:text-white"
                            >
                                {{ $startTime }} – {{ $endTime }}
                            </p>

                        </div>

                    </div>

                </div>


                {{-- Services --}}
                <div class="mt-8">

                    <div
                        class="flex items-center
                               justify-between
                               mb-4"
                    >

                        <div
                            class="flex items-center
                                   gap-2"
                        >

                            <span
                                class="inline-flex
                                       items-center
                                       justify-center
                                       w-8 h-8
                                       rounded-xl
                                       bg-brand-50
                                       dark:bg-brand-900/20
                                       text-brand-600
                                       dark:text-brand-400"
                            >
                                <i
                                    data-lucide="sparkles"
                                    class="w-4 h-4"
                                ></i>
                            </span>

                            <h2
                                class="text-sm
                                       font-bold
                                       text-gray-900
                                       dark:text-white"
                            >
                                Services
                            </h2>

                        </div>


                        <span
                            class="text-xs
                                   text-gray-500
                                   dark:text-gray-400"
                        >
                            {{ $services->count() }}
                            item{{ $services->count() === 1 ? '' : 's' }}
                        </span>

                    </div>


                    <div
                        class="border
                               border-gray-200
                               dark:border-gray-700
                               rounded-2xl
                               overflow-hidden"
                    >

                        <div class="overflow-x-auto">

                            <table class="w-full min-w-[620px]">

                                <thead
                                    class="bg-gray-50
                                           dark:bg-gray-800/70"
                                >

                                    <tr>

                                        <th
                                            class="px-5 py-3
                                                   text-left
                                                   text-[10px]
                                                   font-bold
                                                   uppercase
                                                   tracking-wider
                                                   text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            Service
                                        </th>

                                        <th
                                            class="px-5 py-3
                                                   text-left
                                                   text-[10px]
                                                   font-bold
                                                   uppercase
                                                   tracking-wider
                                                   text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            Duration
                                        </th>

                                        <th
                                            class="px-5 py-3
                                                   text-right
                                                   text-[10px]
                                                   font-bold
                                                   uppercase
                                                   tracking-wider
                                                   text-gray-500
                                                   dark:text-gray-400"
                                        >
                                            Amount
                                        </th>

                                    </tr>

                                </thead>


                                <tbody
                                    class="divide-y
                                           divide-gray-200
                                           dark:divide-gray-700"
                                >

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

                                            $serviceDuration =
                                                $service->pivot->service_duration
                                                ?? $service->duration_minutes
                                                ?? null;

                                            $isExtra = (bool) (
                                                $service->pivot->is_extra ?? false
                                            );

                                        @endphp


                                        <tr>

                                            <td class="px-5 py-4">

                                                <div
                                                    class="flex flex-wrap
                                                           items-center
                                                           gap-2"
                                                >

                                                    <span
                                                        class="font-semibold
                                                               text-gray-900
                                                               dark:text-white"
                                                    >
                                                        {{ $serviceName }}
                                                    </span>


                                                    @if($isExtra)

                                                        <span
                                                            class="inline-flex
                                                                   px-2 py-0.5
                                                                   rounded-md
                                                                   bg-brand-50
                                                                   dark:bg-brand-900/20
                                                                   border
                                                                   border-brand-200
                                                                   dark:border-brand-800
                                                                   text-brand-700
                                                                   dark:text-brand-400
                                                                   text-[9px]
                                                                   font-bold
                                                                   uppercase"
                                                        >
                                                            Extra
                                                        </span>

                                                    @endif

                                                </div>

                                            </td>


                                            <td
                                                class="px-5 py-4
                                                       text-sm
                                                       text-gray-600
                                                       dark:text-gray-400"
                                            >

                                                @if(
                                                    $serviceDuration !== null &&
                                                    $serviceDuration !== ''
                                                )

                                                    {{ $serviceDuration }} min

                                                @else

                                                    —

                                                @endif

                                            </td>


                                            <td
                                                class="px-5 py-4
                                                       text-right
                                                       font-bold
                                                       text-gray-900
                                                       dark:text-white"
                                            >
                                                ₱{{ number_format(
                                                    (float) $servicePrice,
                                                    2
                                                ) }}
                                            </td>

                                        </tr>

                                    @empty

                                        <tr>

                                            <td
                                                colspan="3"
                                                class="px-5 py-10
                                                       text-center
                                                       text-sm
                                                       text-gray-500
                                                       dark:text-gray-400"
                                            >
                                                No service records found.
                                            </td>

                                        </tr>

                                    @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>


                    {{-- Total --}}
                    <div
                        class="flex justify-end
                               mt-5"
                    >

                        <div
                            class="w-full sm:w-[330px]
                                   border
                                   border-gray-200
                                   dark:border-gray-700
                                   rounded-2xl
                                   overflow-hidden"
                        >

                            <div
                                class="flex items-center
                                       justify-between
                                       px-5 py-4
                                       bg-gray-50
                                       dark:bg-gray-800/50"
                            >

                                <span
                                    class="font-extrabold
                                           text-gray-900
                                           dark:text-white"
                                >
                                    Total
                                </span>

                                <span
                                    class="text-xl
                                           font-extrabold
                                           text-brand-700
                                           dark:text-brand-400"
                                >
                                    ₱{{ number_format(
                                        $total,
                                        2
                                    ) }}
                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- Payment --}}
                <div class="mt-8">

                    <div
                        class="flex items-center
                               gap-2 mb-4"
                    >

                        <span
                            class="inline-flex
                                   items-center
                                   justify-center
                                   w-8 h-8
                                   rounded-xl
                                   bg-gray-100
                                   dark:bg-gray-800
                                   text-gray-600
                                   dark:text-gray-300"
                        >
                            <i
                                data-lucide="wallet-cards"
                                class="w-4 h-4"
                            ></i>
                        </span>

                        <h2
                            class="text-sm
                                   font-bold
                                   text-gray-900
                                   dark:text-white"
                        >
                            Payment
                        </h2>

                    </div>


                    <div
                        class="grid
                               grid-cols-1
                               sm:grid-cols-2
                               border
                               border-gray-200
                               dark:border-gray-700
                               rounded-2xl
                               overflow-hidden"
                    >

                        <div
                            class="p-5
                                   border-b
                                   sm:border-r
                                   border-gray-200
                                   dark:border-gray-700"
                        >

                            <p
                                class="text-[10px]
                                       font-bold
                                       uppercase
                                       tracking-wider
                                       text-gray-400"
                            >
                                Payment Method
                            </p>

                            <p
                                class="mt-1.5
                                       text-sm
                                       font-bold
                                       text-gray-900
                                       dark:text-white
                                       uppercase"
                            >

                                @if($paymentMethods->count())

                                    {{
                                        $paymentMethods
                                            ->map(
                                                fn ($method) =>
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $method
                                                    )
                                            )
                                            ->implode(', ')
                                    }}

                                @else

                                    —

                                @endif

                            </p>

                        </div>


                        <div class="p-5">

                            <p
                                class="text-[10px]
                                       font-bold
                                       uppercase
                                       tracking-wider
                                       text-gray-400"
                            >
                                Amount Paid
                            </p>

                            <p
                                class="mt-1.5
                                       text-sm
                                       font-bold
                                       text-emerald-600
                                       dark:text-emerald-400"
                            >
                                ₱{{ number_format(
                                    $totalPaid,
                                    2
                                ) }}
                            </p>

                        </div>

                    </div>

                </div>


                {{-- Footer --}}
                <div
                    class="mt-8 pt-5
                           border-t
                           border-gray-200
                           dark:border-gray-700"
                >

                    <div
                        class="flex flex-col
                               sm:flex-row
                               sm:items-center
                               sm:justify-between
                               gap-4"
                    >

                        <div>

                            <p
                                class="text-[9px]
                                       font-bold
                                       uppercase
                                       tracking-wider
                                       text-gray-400"
                            >
                                Generated
                            </p>

                            <p
                                class="text-xs
                                       font-semibold
                                       text-gray-700
                                       dark:text-gray-300
                                       mt-1"
                            >
                                {{ $generatedAt->format(
                                    'F d, Y \a\t g:i A'
                                ) }}
                            </p>

                        </div>


                        <div class="sm:text-right">

                            <p
                                class="text-[9px]
                                       font-bold
                                       uppercase
                                       tracking-wider
                                       text-gray-400"
                            >
                                Appointment Reference
                            </p>

                            <p
                                class="text-xs
                                       font-bold
                                       text-gray-700
                                       dark:text-gray-300
                                       mt-1"
                            >
                                {{ $referenceNumber }}
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endif


<script>

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            if (window.lucide) {
                window.lucide.createIcons();
            }

            const dateInput =
                document.getElementById('invoice-date');

            if (
                dateInput &&
                typeof flatpickr !== 'undefined'
            ) {
                flatpickr(
                    dateInput,
                    {
                        dateFormat: 'Y-m-d',
                        allowInput: false,
                        disableMobile: true,
                    }
                );
            }

            if (window.HSStaticMethods) {
                window.HSStaticMethods.autoInit();
            }

        }
    );

</script>

@endsection