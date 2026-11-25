<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Services\ReportPdfService;
use App\Support\Pdf\PdfLayoutResolver;
use Illuminate\Http\Request;

class AppointmentInvoiceController extends Controller
{
    protected ReportPdfService $reportPdfService;

    public function __construct(ReportPdfService $reportPdfService)
    {
        $this->reportPdfService = $reportPdfService;
    }

    /**
     * Appointment Invoice page.
     *
     * No appointment ID:
     *     Show the completed appointment invoice list.
     *
     * With appointment ID:
     *     Show the individual invoice.
     */
    public function show(Request $request, ?Appointment $appointment = null)
    {
        /*
        |--------------------------------------------------------------------------
        | Invoice List
        |--------------------------------------------------------------------------
        */
        if (!$appointment) {

            $query = Appointment::query()
                ->where('status', 'completed')
                ->with([
                    'customer',
                    'staff',
                    'assignedStaff',
                    'room',
                ])
                ->latest('appointment_date')
                ->latest('id');

            /*
            |--------------------------------------------------------------------------
            | Optional Date Filter
            |--------------------------------------------------------------------------
            */
            $date = $request->query('date');

            if (
                $date &&
                preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            ) {
                $query->whereDate('appointment_date', $date);
            }

            $appointments = $query
                ->paginate(15)
                ->withQueryString();

            return view('shared.appointment-invoice', [
                'appointment' => null,
                'appointments' => $appointments,
                'selectedDate' => $date,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Individual Invoice
        |--------------------------------------------------------------------------
        */
        $appointment = $this->loadInvoiceAppointment($appointment);

        $data = $this->buildInvoiceData($appointment);

        $data['appointments'] = null;
        $data['selectedDate'] = null;

        return view('shared.appointment-invoice', $data);
    }

    /**
     * Preview individual invoice as PDF.
     */
    public function preview(Request $request, Appointment $appointment)
    {
        $appointment = $this->loadInvoiceAppointment($appointment);

        $data = $this->buildInvoiceData($appointment);

        $layout = app(PdfLayoutResolver::class)->resolveFromRequest('appointment_invoice', $request);

        return $this->reportPdfService->streamPdf(
            'reports.appointment-invoice-pdf',
            $data,
            $this->invoiceFilename($appointment),
            $layout->orientation,
            $layout
        );
    }

    /**
     * Download individual invoice as PDF.
     */
    public function download(Request $request, Appointment $appointment)
    {
        $appointment = $this->loadInvoiceAppointment($appointment);

        $data = $this->buildInvoiceData($appointment);

        $layout = app(PdfLayoutResolver::class)->resolveFromRequest('appointment_invoice', $request);

        return $this->reportPdfService->generatePdf(
            'reports.appointment-invoice-pdf',
            $data,
            $this->invoiceFilename($appointment),
            $layout->orientation,
            $layout
        );
    }

    /**
     * Load and validate an individual appointment invoice.
     */
    protected function loadInvoiceAppointment(Appointment $appointment): Appointment
    {
        abort_unless(
            $appointment->status === 'completed',
            404,
            'Invoice is only available for completed appointments.'
        );

        return $appointment->load([
            'customer',
            'staff',
            'assignedStaff',
            'room',
            'services',
            'packageSelections.choiceGroup',
            'packageSelections.selectedService',
            'payments',
        ]);
    }

    /**
     * Build all data required by the web invoice and PDF invoice.
     */
    protected function buildInvoiceData(Appointment $appointment): array
    {
        $payments = $appointment->payments
            ->sortBy(function ($payment) {
                return $payment->paid_at?->timestamp ?? 0;
            })
            ->values();

        $total = (float) $appointment->total_price;

        /*
        |--------------------------------------------------------------------------
        | Refunds
        |--------------------------------------------------------------------------
        */
        $refundPayments = $payments
            ->filter(function ($payment) {
                return $payment->type === 'refund'
                    || (float) $payment->amount < 0;
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Normal Payments
        |--------------------------------------------------------------------------
        */
        $paidPayments = $payments
            ->filter(function ($payment) {
                return $payment->type !== 'refund'
                    && (float) $payment->amount > 0;
            })
            ->values();

        $totalPaid = (float) $paidPayments->sum(function ($payment) {
            return (float) $payment->amount;
        });

        $totalRefunded = abs(
            (float) $refundPayments->sum(function ($payment) {
                return (float) $payment->amount;
            })
        );

        $netPaid = $totalPaid - $totalRefunded;

        $balance = max(
            0,
            $total - $netPaid
        );

        $paymentMethods = $paidPayments
            ->pluck('payment_method')
            ->filter()
            ->unique()
            ->values();

        $assignedStaffList = ($appointment->relationLoaded('assignedStaff') && $appointment->assignedStaff && $appointment->assignedStaff->isNotEmpty())
            ? $appointment->assignedStaff
                ->sortBy(fn ($member) => ($member->pivot->role ?? '') === 'primary' ? 0 : 1)
                ->unique('id')
                ->values()
            : ($appointment->staff ? collect([$appointment->staff]) : collect());

        $staffNames = $assignedStaffList->isNotEmpty()
            ? $assignedStaffList
                ->map(fn ($member) => trim((string) ($member->full_name ?? $member->name ?? '')))
                ->filter()
                ->unique()
                ->join(', ')
            : ($appointment->staff?->full_name ?? $appointment->staff?->name ?? 'Unassigned');

        $bookedServices = $appointment->services ?? collect();
        $packages = $bookedServices->filter(fn ($service) => (bool) $service->is_package)->values();
        $packageSelections = $appointment->relationLoaded('packageSelections')
            ? $appointment->packageSelections
            : collect();

        $includedServiceIds = $packages
            ->flatMap(function ($package) {
                $raw = $package->included_services;
                if (is_string($raw)) {
                    $raw = json_decode($raw, true) ?: [];
                }
                return is_array($raw) ? $raw : [];
            })
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $includedServiceNamesById = $includedServiceIds->isNotEmpty()
            ? Service::whereIn('id', $includedServiceIds)->pluck('name', 'id')
            : collect();

        $packageIncludedMap = [];
        foreach ($packages as $package) {
            $rawIds = $package->included_services;
            if (is_string($rawIds)) {
                $rawIds = json_decode($rawIds, true) ?: [];
            }
            $names = collect(is_array($rawIds) ? $rawIds : [])
                ->map(fn ($id) => $includedServiceNamesById->get((int) $id))
                ->filter()
                ->values()
                ->all();
            $packageIncludedMap[$package->id] = $names;
        }

        return [
            'appointment' => $appointment,
            'customer' => $appointment->customer,
            'clientName' => $appointment->client_name ?: 'Walk-in Customer',
            'clientPhone' => $appointment->client_phone,
            'staff' => $appointment->staff,
            'assignedStaff' => $assignedStaffList,
            'staffNames' => $staffNames ?: 'Unassigned',
            'room' => $appointment->room,
            'services' => $bookedServices,
            'packages' => $packages,
            'packageIncludedMap' => $packageIncludedMap,
            'packageSelections' => $packageSelections,

            'payments' => $payments,
            'paidPayments' => $paidPayments,
            'refundPayments' => $refundPayments,

            'total' => $total,
            'originalTotal' => $appointment->original_amount,
            'hasDiscount' => $appointment->hasDiscount(),
            'discountType' => $appointment->discount_type,
            'discountLabel' => $appointment->discount_label,
            'shortDiscountBadge' => $appointment->short_discount_badge,
            'discountPercent' => $appointment->discount_percent ? (float) $appointment->discount_percent : 0.0,
            'discountAmount' => (float) ($appointment->discount_amount ?? 0),
            'totalPaid' => $totalPaid,
            'totalRefunded' => $totalRefunded,
            'netPaid' => $netPaid,
            'balance' => $balance,

            'paymentMethods' => $paymentMethods,

            'referenceNumber' => '#SPA-' . $appointment->id,
            'reportTitle' => 'APPOINTMENT INVOICE',
            'dateRange' => null,
            'preparedBy' => auth()->user()?->full_name ?? auth()->user()?->name ?? 'System Administrator',

            'generatedAt' => now('Asia/Manila'),
        ];
    }

    /**
     * PDF filename for one appointment.
     */
    protected function invoiceFilename(Appointment $appointment): string
    {
        return 'appointment-invoice-' . $appointment->id . '.pdf';
    }
}