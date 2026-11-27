<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Service;
use App\Services\ReportPdfService;
use App\Support\Pdf\PdfLayoutResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'birInvoiceRecorder',
        ]);
    }

    /**
     * Record or correct the BIR invoice number for a completed appointment.
     */
    public function updateBirInvoice(Request $request, Appointment $appointment)
    {
        $user = auth()->user();
        abort_unless(
            $user && ($user->roles->contains('name', 'admin') || $user->roles->contains('name', 'receptionist')),
            403,
            'Unauthorized to record BIR invoice numbers.'
        );

        abort_unless(
            $appointment->status === 'completed',
            404,
            'BIR invoice number can only be recorded for completed appointments.'
        );

        $request->validate([
            'bir_invoice_number' => 'required|string|max:50',
            'confirm_overwrite' => 'nullable|boolean',
        ], [
            'bir_invoice_number.required' => 'Please enter the official BIR invoice serial number.',
            'bir_invoice_number.max' => 'BIR invoice number must not exceed 50 characters.',
        ]);

        $birInvoiceNumber = trim((string) $request->input('bir_invoice_number'));
        if ($birInvoiceNumber === '') {
            return back()
                ->withInput()
                ->withErrors(['bir_invoice_number' => 'Please enter the official BIR invoice serial number.']);
        }

        return DB::transaction(function () use ($request, $appointment, $birInvoiceNumber, $user) {
            $lockedAppt = Appointment::lockForUpdate()->findOrFail($appointment->id);

            $previousNumber = $lockedAppt->bir_invoice_number;

            if ($previousNumber !== null && $previousNumber !== $birInvoiceNumber && !$request->boolean('confirm_overwrite')) {
                return back()
                    ->withInput()
                    ->withErrors(['bir_invoice_number' => 'A BIR invoice number (' . $previousNumber . ') is already recorded. Check "Confirm overwrite" to update it.']);
            }

            if ($previousNumber === $birInvoiceNumber) {
                return back()->with('info', 'BIR invoice number is unchanged.');
            }

            $duplicateExists = Appointment::where('bir_invoice_number', $birInvoiceNumber)
                ->where('id', '!=', $lockedAppt->id)
                ->exists();

            if ($duplicateExists) {
                return back()
                    ->withInput()
                    ->withErrors(['bir_invoice_number' => 'This BIR invoice number is already assigned to another appointment.']);
            }

            $lockedAppt->update([
                'bir_invoice_number' => $birInvoiceNumber,
                'bir_invoice_recorded_by' => $user->id,
                'bir_invoice_recorded_at' => now(),
            ]);

            ActivityLog::create([
                'user_id' => $user->id,
                'action' => $previousNumber === null ? 'bir_invoice_recorded' : 'bir_invoice_updated',
                'entity_type' => Appointment::class,
                'entity_id' => $lockedAppt->id,
                'details' => json_encode([
                    'internal_reference' => '#SPA-' . $lockedAppt->id,
                    'previous_bir_invoice_number' => $previousNumber,
                    'bir_invoice_number' => $birInvoiceNumber,
                    'recorded_during' => 'post_completion',
                ], JSON_UNESCAPED_UNICODE),
            ]);

            return back()->with('success', $previousNumber === null
                ? 'BIR invoice number recorded.'
                : 'BIR invoice number updated.');
        });
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
            'birInvoiceNumber' => $appointment->bir_invoice_number,
            'reportTitle' => 'APPOINTMENT AND PAYMENT SUMMARY',
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
        return 'appointment-payment-summary-' . $appointment->id . '.pdf';
    }
}