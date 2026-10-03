<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\ReportPdfService;
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
    public function preview(Appointment $appointment)
    {
        $appointment = $this->loadInvoiceAppointment($appointment);

        $data = $this->buildInvoiceData($appointment);

        return $this->reportPdfService->streamPdf(
            'reports.appointment-invoice-pdf',
            $data,
            $this->invoiceFilename($appointment),
            'portrait'
        );
    }

    /**
     * Download individual invoice as PDF.
     */
    public function download(Appointment $appointment)
    {
        $appointment = $this->loadInvoiceAppointment($appointment);

        $data = $this->buildInvoiceData($appointment);

        return $this->reportPdfService->generatePdf(
            'reports.appointment-invoice-pdf',
            $data,
            $this->invoiceFilename($appointment),
            'portrait'
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
            'room',
            'services',
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

        return [
            'appointment' => $appointment,
            'customer' => $appointment->customer,
            'staff' => $appointment->staff,
            'room' => $appointment->room,
            'services' => $appointment->services,

            'payments' => $payments,
            'paidPayments' => $paidPayments,
            'refundPayments' => $refundPayments,

            'total' => $total,
            'totalPaid' => $totalPaid,
            'totalRefunded' => $totalRefunded,
            'netPaid' => $netPaid,
            'balance' => $balance,

            'paymentMethods' => $paymentMethods,

            'referenceNumber' => 'ZF-' . str_pad(
                (string) $appointment->id,
                6,
                '0',
                STR_PAD_LEFT
            ),
            'reportTitle' => 'APPOINTMENT INVOICE',
            'dateRange' => null,
            'preparedBy' => auth()->user(),

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