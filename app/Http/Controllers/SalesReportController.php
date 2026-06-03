<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\OllamaInsightService;

class SalesReportController extends Controller
{
    /**
     * Single source of truth for service abbreviations.
     * Used by print reports and transaction log.
     */
    private const SERVICE_CODE_MAP = [
        'Alexandria Massage'        => 'AM',
        'Swedish Massage'           => 'SM',
        'Thai Massage'              => 'TM',
        'Therma Massage'            => 'TSM',
        'Ventosa Massage'           => 'VC',
        'Alexa\'s Stone Massage'   => 'AS',
        'The Little One\'s Massage' => 'TLC',
        'Body Scrub'                => 'BS',
        'Foot Spa'                  => 'FS',
        'Facial Cleansing'          => 'FC',
        'Restful Head Massage'      => 'RHM',
        'Relaxing Back Massage'     => 'RBM',
        'Refreshing Foot Massage'   => 'RFM',
        'Manicure'                  => 'MA',
        'Pedicure'                  => 'PE',
        'Foot Spa with Pedi'        => 'PEF',
        'Foot Spa Mani/Pedi'        => 'MAF',
        'Package 1'                 => 'P1',
        'Package 2'                 => 'P2',
        'Package 3'                 => 'P3',
        'Package 4'                 => 'P4',
        'Package 5'                 => 'P5',
        'Package 6'                 => 'P6',
        'Couple 1'                  => 'C1',
        'Couple 2'                  => 'C2',
        'Couple 3'                  => 'C3',
        'VIP Suite Package'         => 'VIP',
        'Home Service'              => 'HS',
    ];

    public function index(Request $request)
    {
        $user = Auth::user();
        $period = $request->get('period', 'daily');
        $today = Carbon::today();
        $currentStatus = $request->get('status', 'completed');

        // Date range
        [$startDate, $endDate, $label] = $this->resolveDateRange($period, $request, $today);

        // Base query
        $baseQuery = Payment::with([
            'appointment.customer',
            'appointment.services',
            'appointment.staff',
            'appointment.room',
        ])
        ->whereHas('appointment', fn ($q) => $q->where('status', '!=', 'confirmed'))
        ->whereBetween('paid_at', [$startDate, $endDate]);

        // Paginated table query (filtered by status)
        $payments = $this->getFilteredPayments(clone $baseQuery, $currentStatus);

        // All payments for analytics
        $allPayments = (clone $baseQuery)->orderBy('paid_at', 'desc')->get();

        // Core metrics
        $totalRevenue = $allPayments->whereIn('type', ['completion', 'additional', 'full'])->sum('amount');
        $totalCount = $allPayments->count();
        $avgSale = $totalCount > 0 ? $totalRevenue / $totalCount : 0;
        $deposits = $allPayments->where('type', 'deposit')->sum('amount');
        $refunds = abs($allPayments->where('type', 'refund')->sum('amount'));

        // No-show analytics — FIXED: uses appointment_date not updated_at
        $noShowData = $this->getNoShowData($startDate, $endDate);

        // Breakdowns
        $methodBreakdown = $this->getMethodBreakdown($allPayments);
        $typeBreakdown = $this->getTypeBreakdown($allPayments);

        // Charts
        [$chartLabels, $chartValues] = $this->getChartData($allPayments, $period, $startDate, $endDate);

        // Business intelligence
        $serviceRevenue = [];
        $staffRevenue = [];
        $hourlyRevenue = array_fill(10, 11, 0);

        foreach ($allPayments as $payment) {
            $appt = $payment->appointment;
            if (!$appt) continue;

            $staffName = $appt->staff->full_name ?? 'Unassigned';
            $staffRevenue[$staffName] = ($staffRevenue[$staffName] ?? 0) + $payment->amount;

            $hour = (int) Carbon::parse($payment->paid_at)->format('H');
            if ($hour >= 10 && $hour <= 20) {
                $hourlyRevenue[$hour] = ($hourlyRevenue[$hour] ?? 0) + $payment->amount;
            }

            $svcCount = $appt->services->count();
            if ($svcCount > 0 && $payment->amount > 0) {
                $perService = $payment->amount / $svcCount;
                foreach ($appt->services as $svc) {
                    $serviceRevenue[$svc->name] = ($serviceRevenue[$svc->name] ?? 0) + $perService;
                }
            }
        }

        arsort($serviceRevenue);
        arsort($staffRevenue);
        $topServices = array_slice($serviceRevenue, 0, 5, true);
        $topStaff = array_slice($staffRevenue, 0, 5, true);

        $maxHourly = !empty($hourlyRevenue) ? max($hourlyRevenue) : 1;
        $maxSvc    = !empty($topServices)    ? max($topServices)    : 1;
        $maxStaff  = !empty($topStaff)       ? max($topStaff)       : 1;

        // Period-over-period comparison
        $periodDays = max(1, $startDate->diffInDays($endDate) + 1);
        $prevStart = $startDate->copy()->subDays($periodDays);
        $prevEnd = $endDate->copy()->subDays($periodDays);
        $prevRevenue = Payment::whereBetween('paid_at', [$prevStart, $prevEnd])
            ->whereIn('type', ['completion', 'additional', 'full'])
            ->sum('amount');
        $revenueChange = $prevRevenue > 0 ? (($totalRevenue - $prevRevenue) / $prevRevenue) * 100 : 0;
        $revenueChangeLabel = ($revenueChange >= 0 ? '+' : '') . number_format($revenueChange, 1);

        // Appointment health
        $periodAppts = Appointment::whereBetween('appointment_date', [$startDate, $endDate])->get();
        $totalApptsInPeriod = $periodAppts->count();
        $completedApptsInPeriod = $periodAppts->where('status', 'completed')->count();
        $cancelledApptsInPeriod = $periodAppts->where('status', 'cancelled')->count();
        $confirmedApptsInPeriod = $periodAppts->where('status', 'confirmed')->count();
        $pendingApptsInPeriod = $periodAppts->where('status', 'pending')->count();

        $completionRate = $totalApptsInPeriod > 0
            ? round(($completedApptsInPeriod / $totalApptsInPeriod) * 100, 1)
            : 0;
        $noShowRate = $totalApptsInPeriod > 0
            ? round(($noShowData['count'] / $totalApptsInPeriod) * 100, 1)
            : 0;
        $cancellationRate = $totalApptsInPeriod > 0
            ? round(($cancelledApptsInPeriod / $totalApptsInPeriod) * 100, 1)
            : 0;

        $uniqueCustomers = $allPayments->pluck('appointment.customer_id')->filter()->unique()->count();
        $revPerCompletedAppt = $completedApptsInPeriod > 0
            ? $totalRevenue / $completedApptsInPeriod
            : 0;
        $totalDecidable = $pendingApptsInPeriod + $confirmedApptsInPeriod + $completedApptsInPeriod;
        $conversionRate = $totalDecidable > 0
            ? round((($confirmedApptsInPeriod + $completedApptsInPeriod) / $totalDecidable) * 100, 1)
            : 0;

        $routeName = $user->isAdmin() ? 'admin.sales' : 'receptionist.sales';

        // Daily report data
        $dailyPayments = $allPayments;

        // Print report summaries
        $printServiceSummary = $this->buildPrintServiceSummary($dailyPayments);
        $reportTitle = match($period) {
            'daily'   => 'SUMMARY OF DAILY SALES REPORT',
            'weekly'  => 'SUMMARY OF WEEKLY SALES REPORT',
            'monthly' => 'SUMMARY OF MONTHLY SALES REPORT',
            'yearly'  => 'SUMMARY OF YEARLY SALES REPORT',
            'custom'  => 'SUMMARY OF CUSTOM SALES REPORT',
            default   => 'SUMMARY OF SALES REPORT',
        };
        $dateLabel = $period === 'daily' ? 'DATE:' : 'PERIOD:';
        $dateDisplay = match($period) {
            'daily'   => $startDate->format('n/j/Y'),
            'weekly'  => $startDate->format('M j') . ' — ' . $endDate->format('M j, Y'),
            'monthly' => $startDate->format('F Y'),
            'yearly'  => $startDate->format('Y'),
            'custom'  => $startDate->format('M j') . ' — ' . $endDate->format('M j, Y'),
            default   => $startDate->format('n/j/Y') . ' — ' . $endDate->format('n/j/Y'),
        };

        // Transaction rows
        $txLogData = $this->buildTransactionRows($payments, $currentStatus);

        // === OLLAMA AI INSIGHTS WITH 90-DAY HISTORY ===
        $history = $this->get90DayHistory($startDate, $endDate, $period);

        $ollama = new OllamaInsightService();
        $aiOnline = $ollama->healthCheck(); // <-- ADD THIS LINE
        $aiInsights = $ollama->getInsights([
            'period' => $period,
            'label' => $label,
            'startDate' => $startDate->format('M d, Y'),
            'endDate' => $endDate->format('M d, Y'),
            'totalRevenue' => number_format($totalRevenue, 2),
            'totalCount' => $totalCount,
            'avgSale' => number_format($avgSale, 2),
            'uniqueCustomers' => $uniqueCustomers,
            'completionRate' => $completionRate,
            'noShowRate' => $noShowRate,
            'cancellationRate' => $cancellationRate,
            'revenueChange' => $revenueChange,
            'conversionRate' => $conversionRate,
            'deposits' => number_format($deposits, 2),
            'revPerComp' => number_format($revPerCompletedAppt, 2),
            'topService' => array_key_first($topServices) ?: 'None',
            'topStaff' => array_key_first($topStaff) ?: 'None',
        ], $history);

        $suggestions = !empty($aiInsights)
            ? $aiInsights
            : $this->generateFallbackInsights(
                $completionRate, $noShowRate, $cancellationRate,
                $revenueChange, $avgSale, $revPerCompletedAppt,
                $deposits, $totalRevenue, $topStaff, $topServices,
                $totalCount, $uniqueCustomers, $conversionRate
            );

        // === MONTHLY CUSTOMER SPIKE ANALYTICS ===
        $monthlySpike = $this->getMonthlySpikeData();

        // === AI CHAT METRICS FOR VIEW ===
        $chatMetrics = [
            'label' => $label,
            'startDate' => $startDate->format('M d, Y'),
            'endDate' => $endDate->format('M d, Y'),
            'totalRevenue' => number_format($totalRevenue, 2),
            'totalCount' => $totalCount,
            'avgSale' => number_format($avgSale, 2),
            'uniqueCustomers' => $uniqueCustomers,
            'completionRate' => $completionRate,
            'noShowRate' => $noShowRate,
            'cancellationRate' => $cancellationRate,
            'revenueChange' => $revenueChange,
            'conversionRate' => $conversionRate,
            'topService' => array_key_first($topServices) ?: 'None',
            'topStaff' => array_key_first($topStaff) ?: 'None',
        ];

        return view('shared.sales', compact(
            'payments',
            'totalRevenue',
            'totalCount',
            'avgSale',
            'deposits',
            'refunds',
            'uniqueCustomers',
            'revPerCompletedAppt',
            'noShowData',
            'methodBreakdown',
            'typeBreakdown',
            'chartLabels',
            'chartValues',
            'period',
            'startDate',
            'endDate',
            'label',
            'today',
            'topServices',
            'topStaff',
            'hourlyRevenue',
            'maxHourly',
            'maxSvc',
            'maxStaff',
            'revenueChange',
            'revenueChangeLabel',
            'completionRate',
            'noShowRate',
            'cancellationRate',
            'totalApptsInPeriod',
            'completedApptsInPeriod',
            'cancelledApptsInPeriod',
            'confirmedApptsInPeriod',
            'pendingApptsInPeriod',
            'conversionRate',
            'routeName',
            'dailyPayments',
            'printServiceSummary',
            'reportTitle',
            'dateLabel',
            'dateDisplay',
            'txLogData',
            'currentStatus',
            'suggestions',
            'monthlySpike',
            'chatMetrics',
            'aiOnline',
        ));
    }

    /**
     * AJAX endpoint for transaction table fragment.
     */
    public function transactionLogFragment(Request $request)
    {
        $period = $request->get('period', 'daily');
        $today = Carbon::today();
        $currentStatus = $request->get('status', 'completed');

        [$startDate, $endDate, $label] = $this->resolveDateRange($period, $request, $today);

        $baseQuery = Payment::with([
            'appointment.customer',
            'appointment.services',
            'appointment.staff',
            'appointment.room',
        ])
        ->whereHas('appointment', fn ($q) => $q->where('status', '!=', 'confirmed'))
        ->whereBetween('paid_at', [$startDate, $endDate]);

        $payments = $this->getFilteredPayments($baseQuery, $currentStatus);
        $txLogData = $this->buildTransactionRows($payments, $currentStatus);

        return view('shared._transaction_log_table', [
            'txLogData' => $txLogData,
            'currentStatus' => $currentStatus,
            'currency' => '₱',
            'serviceCodeMap' => self::SERVICE_CODE_MAP,
        ]);
    }

    /**
     * AJAX endpoint for AI chat assistant.
     */
    public function aiChat(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:200',
        ]);

        $question = $request->input('question');
        $period = $request->get('period', 'daily');
        $today = Carbon::today();
        [$startDate, $endDate, $label] = $this->resolveDateRange($period, $request, $today);

        // Minimal metrics for chat context (fast query)
        $baseQuery = Payment::with('appointment')
            ->whereHas('appointment', fn ($q) => $q->where('status', '!=', 'confirmed'))
            ->whereBetween('paid_at', [$startDate, $endDate]);

        $allPayments = (clone $baseQuery)->get();
        $totalRevenue = $allPayments->whereIn('type', ['completion', 'additional', 'full'])->sum('amount');
        $totalCount = $allPayments->count();
        $avgSale = $totalCount > 0 ? $totalRevenue / $totalCount : 0;

        $periodAppts = Appointment::whereBetween('appointment_date', [$startDate, $endDate])->get();
        $totalAppts = $periodAppts->count();
        $completed = $periodAppts->where('status', 'completed')->count();
        $completionRate = $totalAppts > 0 ? round(($completed / $totalAppts) * 100, 1) : 0;

        $metrics = [
            'label' => $label,
            'startDate' => $startDate->format('M d, Y'),
            'endDate' => $endDate->format('M d, Y'),
            'totalRevenue' => number_format($totalRevenue, 2),
            'totalCount' => $totalCount,
            'avgSale' => number_format($avgSale, 2),
            'uniqueCustomers' => $allPayments->pluck('appointment.customer_id')->filter()->unique()->count(),
            'completionRate' => $completionRate,
            'noShowRate' => 0,
            'cancellationRate' => 0,
            'revenueChange' => 0,
            'conversionRate' => 0,
            'topService' => 'N/A',
            'topStaff' => 'N/A',
        ];

        $ollama = new OllamaInsightService();
        $response = $ollama->chat($question, $metrics);

        return response()->json($response);
    }

    // ==================== PRIVATE HELPERS ====================

    private function resolveDateRange(string $period, Request $request, Carbon $today): array
    {
        switch ($period) {
            case 'weekly':
                return [$today->copy()->startOfWeek(), $today->copy()->endOfWeek(), 'This Week'];
            case 'monthly':
                return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth(), 'This Month'];
            case 'yearly':
                return [$today->copy()->startOfYear(), $today->copy()->endOfYear(), 'This Year'];
            case 'custom':
                $start = $request->filled('start_date')
                    ? Carbon::parse($request->start_date)->startOfDay()
                    : $today->copy()->startOfDay();
                $end = $request->filled('end_date')
                    ? Carbon::parse($request->end_date)->endOfDay()
                    : $today->copy()->endOfDay();
                return [$start, $end, 'Custom Range'];
            default:
                return [$today->copy()->startOfDay(), $today->copy()->endOfDay(), 'Today'];
        }
    }

    private function getFilteredPayments($query, string $currentStatus)
    {
        if ($currentStatus !== 'all') {
            $query->whereHas('appointment', function ($q) use ($currentStatus) {
                if ($currentStatus === 'customer_no_show') {
                    $q->where('status', 'cancelled')
                      ->where('cancellation_reason', 'customer_no_show');
                } else {
                    $q->where('status', $currentStatus);
                }
            });
        }

        return $query->orderBy('paid_at', 'desc')->paginate(25)->withQueryString();
    }

    /**
     * FIXED: Uses appointment_date instead of updated_at for no-show detection.
     */
    private function getNoShowData(Carbon $startDate, Carbon $endDate): array
    {
        $markedNoShows = Appointment::whereBetween('appointment_date', [$startDate, $endDate])
            ->where('status', 'cancelled')
            ->where(function ($q) {
                $q->where('cancellation_reason', 'customer_no_show')
                  ->orWhereNull('cancellation_reason');
            })
            ->with(['payments', 'customer'])
            ->get();

        $refundedApptIds = Payment::whereBetween('paid_at', [$startDate, $endDate])
            ->where('type', 'refund')
            ->where('amount', '<', 0)
            ->pluck('appointment_id');

        $refundedNoShows = Appointment::whereIn('id', $refundedApptIds)
            ->where('status', 'cancelled')
            ->with(['payments', 'customer'])
            ->get();

        $periodNoShowAppointments = $markedNoShows->merge($refundedNoShows)->unique('id');

        $noShowForfeited = 0;
        $noShowRefunded = 0;
        $noShowList = [];

        foreach ($periodNoShowAppointments as $appt) {
            $deposit = $appt->payments->where('type', 'deposit')->sum('amount');
            $refund = abs($appt->payments->where('type', 'refund')->sum('amount'));
            $wasRefunded = $refund > 0;

            if ($wasRefunded) {
                $noShowRefunded += $refund;
            } else {
                $noShowForfeited += $deposit;
            }

            $phone = 'N/A';
            if ($appt->customer) {
                $phone = $appt->customer->phone_number
                    ?? ($appt->customer->user->phone_number ?? 'N/A');
            }
            if ($phone === 'N/A' && !empty($appt->guest_phone)) {
                $phone = $appt->guest_phone;
            }

            $noShowList[] = [
                'customer' => $appt->customer->full_name ?? trim(($appt->guest_first_name ?? '') . ' ' . ($appt->guest_last_name ?? '')) ?: 'Walk-in',
                'phone' => $phone,
                'date' => $appt->appointment_date,
                'marked_at' => $appt->updated_at,
                'deposit' => $deposit,
                'refund' => $refund,
                'status' => $wasRefunded ? 'Refunded' : 'Forfeited',
            ];
        }

        return [
            'count' => $periodNoShowAppointments->count(),
            'forfeited' => $noShowForfeited,
            'refunded' => $noShowRefunded,
            'list' => $noShowList,
        ];
    }

    private function getMethodBreakdown($payments)
    {
        return $payments
            ->groupBy(fn ($p) => str_replace('_', ' ', $p->payment_method))
            ->map(fn ($group) => ['count' => $group->count(), 'total' => $group->sum('amount')])
            ->sortByDesc('total');
    }

    private function getTypeBreakdown($payments)
    {
        return $payments
            ->groupBy('type')
            ->map(fn ($group) => ['count' => $group->count(), 'total' => $group->sum('amount')])
            ->sortByDesc('total');
    }

    private function getChartData($allPayments, string $period, Carbon $startDate, Carbon $endDate): array
    {
        $labels = [];
        $values = [];

        if ($period === 'yearly') {
            for ($m = 1; $m <= 12; $m++) {
                $monthStart = $startDate->copy()->month($m)->startOfMonth();
                $monthEnd = $startDate->copy()->month($m)->endOfMonth();
                if ($monthStart > $endDate) break;

                $monthTotal = $allPayments
                    ->filter(fn ($p) => Carbon::parse($p->paid_at)->between($monthStart, $monthEnd))
                    ->sum('amount');
                $labels[] = $monthStart->format('M');
                $values[] = $monthTotal;
            }
        } elseif ($period === 'monthly') {
            $weekCursor = $startDate->copy()->startOfWeek();
            while ($weekCursor <= $endDate) {
                $weekEnd = $weekCursor->copy()->endOfWeek();
                $weekTotal = $allPayments
                    ->filter(fn ($p) => Carbon::parse($p->paid_at)->between($weekCursor, $weekEnd))
                    ->sum('amount');
                $labels[] = $weekCursor->format('M d');
                $values[] = $weekTotal;
                $weekCursor->addWeek();
            }
        } else {
            $cursor = $startDate->copy();
            while ($cursor <= $endDate) {
                $dayTotal = $allPayments
                    ->filter(fn ($p) => Carbon::parse($p->paid_at)->isSameDay($cursor))
                    ->sum('amount');
                $labels[] = $cursor->format('M d');
                $values[] = $dayTotal;
                $cursor->addDay();
            }
        }

        return [$labels, $values];
    }

    private function buildPrintServiceSummary($dailyPayments): array
    {
        $summary = [];
        foreach ($dailyPayments as $payment) {
            $appt = $payment->appointment;
            if (!$appt) continue;

            foreach ($appt->services ?? [] as $svc) {
                $name = $svc->name;
                $code = self::SERVICE_CODE_MAP[$name] ?? strtoupper(substr($name, 0, 2));
                $originalPrice = floatval($svc->price);
                $bookingPrice = floatval($svc->pivot->price_at_booking ?? $originalPrice);

                if (!isset($summary[$name])) {
                    $summary[$name] = ['code' => $code, 'name' => $name, 'count' => 0, 'gross' => 0, 'discount' => 0, 'net' => 0];
                }
                $summary[$name]['count']++;
                $summary[$name]['gross'] += $originalPrice;
                $summary[$name]['net'] += $bookingPrice;
            }
        }

        foreach ($summary as &$r) {
            $r['discount'] = max(0, $r['gross'] - $r['net']);
        }
        unset($r);

        uasort($summary, fn ($a, $b) => $a['code'] <=> $b['code']);

        return $summary;
    }

    private function buildTransactionRows($payments, string $currentStatus): array
    {
        $rows = [];
        $seenApptIds = [];
        $rowNum = 0;
        $grandGross = 0;
        $grandNet = 0;
        $grandCom = 0;

        foreach ($payments->getCollection() as $payment) {
            $appt = $payment->appointment;
            if (!$appt) continue;

            $status = $appt->status ?? 'unknown';
            $reason = $appt->cancellation_reason ?? null;
            $filterKey = ($status === 'cancelled' && $reason === 'customer_no_show') ? 'customer_no_show' : $status;

            if ($currentStatus !== 'all' && $filterKey !== $currentStatus) continue;
            if (in_array($appt->id, $seenApptIds)) continue;
            $seenApptIds[] = $appt->id;
            $rowNum++;

            $customerName = $appt->customer->full_name ?? trim(($appt->guest_first_name ?? '') . ' ' . ($appt->guest_last_name ?? '')) ?: 'Walk-in';
            $staffName = $appt->staff->full_name ?? $appt->staff->name ?? 'Unassigned';
            $room = $appt->room->name ?? $appt->room_id ?? '-';

            $startTime = $appt->start_time ?? $appt->appointment_time ?? null;
            $endTime = $appt->end_time ?? null;
            $durationHrs = 1.0;
            if ($startTime && $endTime) {
                try {
                    $st = Carbon::parse($startTime);
                    $et = Carbon::parse($endTime);
                    $durationHrs = max(0.5, round($st->diffInMinutes($et) / 60, 2));
                } catch (\Exception $e) {
                    $durationHrs = 1.0;
                }
            }

            $serviceList = [];
            $grossAmount = 0;
            $netServiceAmount = 0;
            foreach ($appt->services ?? [] as $svc) {
                $originalPrice = floatval($svc->price);
                $bookingPrice = floatval($svc->pivot->price_at_booking ?? $originalPrice);
                $grossAmount += $originalPrice;
                $netServiceAmount += $bookingPrice;
                $serviceList[] = [
                    'name' => $svc->name,
                    'code' => self::SERVICE_CODE_MAP[$svc->name] ?? strtoupper(substr($svc->name, 0, 2)),
                ];
            }

            $netAmount = $appt->total_price > 0 ? floatval($appt->total_price) : $netServiceAmount;
            if ($grossAmount == 0 && $netAmount > 0) {
                $grossAmount = $netAmount;
            }
            $discountAmount = max(0, $grossAmount - $netAmount);

            $comPct = 25;
            if ($appt->staff && !empty($appt->staff->commission_rate)) {
                $comPct = floatval($appt->staff->commission_rate);
            }
            $therapistCom = $grossAmount * ($comPct / 100);

            $grandGross += $grossAmount;
            $grandNet += $netAmount;
            $grandCom += $therapistCom;

            $noteText = $appt->notes ?: ($appt->customer->medical_notes ?? null);

            $rows[] = [
                'rowNum' => $rowNum,
                'filterKey' => $filterKey,
                'customerName' => $customerName,
                'room' => $room,
                'startTime' => $startTime ? Carbon::parse($startTime)->format('g:i A') : '-',
                'endTime' => $endTime ? Carbon::parse($endTime)->format('g:i A') : '-',
                'staffName' => $staffName,
                'durationHrs' => number_format($durationHrs, 2),
                'serviceList' => $serviceList,
                'grossAmount' => number_format($grossAmount, 2),
                'discountAmount' => $discountAmount > 0 ? number_format($discountAmount, 2) : null,
                'netAmount' => number_format($netAmount, 2),
                'noteText' => $noteText,
                'comPct' => $comPct,
                'therapistCom' => number_format($therapistCom, 2),
            ];
        }

        return [
            'rows' => $rows,
            'grandGross' => $grandGross,
            'grandNet' => $grandNet,
            'grandCom' => $grandCom,
            'totalFiltered' => count($rows),
            'pagination' => $payments,
        ];
    }

    private function generateFallbackInsights(
        float $completionRate,
        float $noShowRate,
        float $cancellationRate,
        float $revenueChange,
        float $avgSale,
        float $revPerComp,
        float $deposits,
        float $totalRevenue,
        array $topStaff,
        array $topServices,
        int $totalCount,
        int $uniqueCustomers,
        float $conversionRate
    ): array {
        $suggestions = [];

        if ($completionRate < 60) {
            $suggestions[] = [
                'type' => 'danger', 'icon' => '⚠️', 'title' => 'Critical: Low Completion Rate',
                'text' => "Only {$completionRate}% of appointments are being completed. Review scheduling density and confirmation workflows immediately.",
                'meta' => 'Action Required',
                'bg' => 'bg-red-50 border-red-500 dark:bg-red-900/20',
                'iconBg' => 'bg-red-100 text-red-600 dark:bg-red-800 dark:text-red-200'
            ];
        } elseif ($completionRate < 75) {
            $suggestions[] = [
                'type' => 'warning', 'icon' => '📉', 'title' => 'Completion Rate Below Target',
                'text' => "Your completion rate is {$completionRate}%. Industry benchmark is 80%+. Consider SMS reminders and stricter deposit policies.",
                'meta' => 'Improvement Opportunity',
                'bg' => 'bg-amber-50 border-amber-500 dark:bg-amber-900/20',
                'iconBg' => 'bg-amber-100 text-amber-600 dark:bg-amber-800 dark:text-amber-200'
            ];
        } elseif ($completionRate >= 90) {
            $suggestions[] = [
                'type' => 'success', 'icon' => '🏆', 'title' => 'Excellent Completion Rate',
                'text' => "Outstanding {$completionRate}% completion rate! Consider capturing this success into staff training SOPs.",
                'meta' => 'Keep It Up',
                'bg' => 'bg-green-50 border-green-500 dark:bg-green-900/20',
                'iconBg' => 'bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-200'
            ];
        }

        if ($noShowRate > 15) {
            $suggestions[] = [
                'type' => 'danger', 'icon' => '🚫', 'title' => 'No-Show Rate Critical',
                'text' => "No-show rate at {$noShowRate}% is bleeding revenue. Enforce stricter deposits and penalty clauses.",
                'meta' => 'Revenue Leak',
                'bg' => 'bg-red-50 border-red-500 dark:bg-red-900/20',
                'iconBg' => 'bg-red-100 text-red-600 dark:bg-red-800 dark:text-red-200'
            ];
        } elseif ($noShowRate > 8) {
            $suggestions[] = [
                'type' => 'warning', 'icon' => '⏰', 'title' => 'No-Shows Above Normal',
                'text' => "{$noShowRate}% no-show rate detected. Enable automated reminders 2 hours before appointment.",
                'meta' => 'Optimization',
                'bg' => 'bg-amber-50 border-amber-500 dark:bg-amber-900/20',
                'iconBg' => 'bg-amber-100 text-amber-600 dark:bg-amber-800 dark:text-amber-200'
            ];
        }

        if ($cancellationRate > 20) {
            $suggestions[] = [
                'type' => 'danger', 'icon' => '❌', 'title' => 'Mass Cancellations Detected',
                'text' => "{$cancellationRate}% cancellation rate is abnormally high. Audit last-minute cancellation reasons.",
                'meta' => 'Urgent Review',
                'bg' => 'bg-red-50 border-red-500 dark:bg-red-900/20',
                'iconBg' => 'bg-red-100 text-red-600 dark:bg-red-800 dark:text-red-200'
            ];
        } elseif ($cancellationRate > 12) {
            $suggestions[] = [
                'type' => 'warning', 'icon' => '📋', 'title' => 'Elevated Cancellations',
                'text' => "Cancellation rate at {$cancellationRate}%. Review rescheduling policy.",
                'meta' => 'Policy Review',
                'bg' => 'bg-blue-50 border-blue-500 dark:bg-blue-900/20',
                'iconBg' => 'bg-blue-100 text-blue-600 dark:bg-blue-800 dark:text-blue-200'
            ];
        }

        if ($revenueChange < -20) {
            $suggestions[] = [
                'type' => 'danger', 'icon' => '💸', 'title' => 'Revenue Plummeting',
                'text' => "Revenue dropped " . abs($revenueChange) . "% vs previous period. Immediate action required.",
                'meta' => 'Critical Alert',
                'bg' => 'bg-red-50 border-red-500 dark:bg-red-900/20',
                'iconBg' => 'bg-red-100 text-red-600 dark:bg-red-800 dark:text-red-200'
            ];
        } elseif ($revenueChange < -5) {
            $suggestions[] = [
                'type' => 'warning', 'icon' => '📊', 'title' => 'Revenue Declining',
                'text' => "Down " . abs($revenueChange) . "% from last period. Push add-ons and upgrades.",
                'meta' => 'Trend Alert',
                'bg' => 'bg-amber-50 border-amber-500 dark:bg-amber-900/20',
                'iconBg' => 'bg-amber-100 text-amber-600 dark:bg-amber-800 dark:text-amber-200'
            ];
        } elseif ($revenueChange > 15) {
            $suggestions[] = [
                'type' => 'success', 'icon' => '🚀', 'title' => 'Revenue Surging',
                'text' => "Up {$revenueChange}%! Capitalize on this momentum.",
                'meta' => 'Growth Insight',
                'bg' => 'bg-green-50 border-green-500 dark:bg-green-900/20',
                'iconBg' => 'bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-200'
            ];
        }

        if ($avgSale < 800 && $revPerComp < 800) {
            $suggestions[] = [
                'type' => 'warning', 'icon' => '💰', 'title' => 'Low Average Ticket',
                'text' => "Avg ticket ₱" . number_format($avgSale, 0) . " is below optimal. Train staff on upselling.",
                'meta' => 'Sales Training',
                'bg' => 'bg-blue-50 border-blue-500 dark:bg-blue-900/20',
                'iconBg' => 'bg-blue-100 text-blue-600 dark:bg-blue-800 dark:text-blue-200'
            ];
        }

        $depRatio = $totalRevenue > 0 ? ($deposits / $totalRevenue) * 100 : 0;
        if ($depRatio > 30) {
            $suggestions[] = [
                'type' => 'warning', 'icon' => '🔒', 'title' => 'High Deposit Backlog',
                'text' => "₱" . number_format($deposits, 0) . " in deposits ({$depRatio}% of revenue) held but not realized.",
                'meta' => 'Cash Flow Watch',
                'bg' => 'bg-amber-50 border-amber-500 dark:bg-amber-900/20',
                'iconBg' => 'bg-amber-100 text-amber-600 dark:bg-amber-800 dark:text-amber-200'
            ];
        }

        if (!empty($topStaff) && count($topStaff) >= 2) {
            $staffVals = array_values($topStaff);
            $topStaffRev = floatval($staffVals[0] ?? 0);
            $bottomStaffRev = floatval($staffVals[count($staffVals) - 1] ?? 0);
            if ($topStaffRev > 0 && ($bottomStaffRev / $topStaffRev) < 0.4) {
                $suggestions[] = [
                    'type' => 'warning', 'icon' => '👥', 'title' => 'Staff Performance Gap',
                    'text' => "Significant revenue disparity between top and bottom performers.",
                    'meta' => 'HR Review',
                    'bg' => 'bg-purple-50 border-purple-500 dark:bg-purple-900/20',
                    'iconBg' => 'bg-purple-100 text-purple-600 dark:bg-purple-800 dark:text-purple-200'
                ];
            }
        }

        if (!empty($topServices) && count($topServices) >= 1) {
            $svcVals = array_values($topServices);
            $topSvcRev = floatval($svcVals[0] ?? 0);
            $svcTotal = array_sum($svcVals);
            $concentration = $svcTotal > 0 ? ($topSvcRev / $svcTotal) * 100 : 0;
            if ($concentration > 50) {
                $suggestions[] = [
                    'type' => 'warning', 'icon' => '🎯', 'title' => 'Service Concentration Risk',
                    'text' => round($concentration, 0) . "% of service revenue comes from one offering.",
                    'meta' => 'Portfolio Risk',
                    'bg' => 'bg-indigo-50 border-indigo-500 dark:bg-indigo-900/20',
                    'iconBg' => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-800 dark:text-indigo-200'
                ];
            }
        }

        $repeatRate = $totalCount > 0 ? (($totalCount - $uniqueCustomers) / $totalCount) * 100 : 0;
        if ($uniqueCustomers > 0 && $repeatRate < 10 && $totalCount > 5) {
            $suggestions[] = [
                'type' => 'info', 'icon' => '🔄', 'title' => 'Low Repeat Rate',
                'text' => "Most customers are first-timers. Launch a loyalty program.",
                'meta' => 'Retention',
                'bg' => 'bg-blue-50 border-blue-500 dark:bg-blue-900/20',
                'iconBg' => 'bg-blue-100 text-blue-600 dark:bg-blue-800 dark:text-blue-200'
            ];
        }

        if ($conversionRate < 40) {
            $suggestions[] = [
                'type' => 'warning', 'icon' => '🎣', 'title' => 'Low Inquiry Conversion',
                'text' => "Only {$conversionRate}% of inquiries convert to bookings. Audit your booking funnel.",
                'meta' => 'Funnel Fix',
                'bg' => 'bg-amber-50 border-amber-500 dark:bg-amber-900/20',
                'iconBg' => 'bg-amber-100 text-amber-600 dark:bg-amber-800 dark:text-amber-200'
            ];
        }

        if (empty($suggestions)) {
            $suggestions[] = [
                'type' => 'success', 'icon' => '✅', 'title' => 'All Metrics Healthy',
                'text' => "No critical issues detected. Continue monitoring weekly trends.",
                'meta' => 'Status OK',
                'bg' => 'bg-green-50 border-green-500 dark:bg-green-900/20',
                'iconBg' => 'bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-200'
            ];
        }

        usort($suggestions, fn ($a, $b) => match (true) {
            $a['type'] === 'danger' && $b['type'] !== 'danger' => -1,
            $a['type'] !== 'danger' && $b['type'] === 'danger' => 1,
            $a['type'] === 'warning' && $b['type'] === 'success' => -1,
            $a['type'] === 'warning' && $b['type'] === 'info' => -1,
            default => 0,
        });

        return array_slice($suggestions, 0, 6);
    }

    /**
     * Assemble 90-day historical snapshots for AI context.
     */
    private function get90DayHistory(Carbon $startDate, Carbon $endDate, string $period): array
    {
        $history = [];
        $intervalDays = max(1, $startDate->diffInDays($endDate) + 1);

        for ($i = 1; $i <= 3; $i++) {
            $prevStart = $startDate->copy()->subDays($intervalDays * $i);
            $prevEnd = $endDate->copy()->subDays($intervalDays * $i);

            $prevPayments = Payment::whereBetween('paid_at', [$prevStart, $prevEnd])
                ->whereIn('type', ['completion', 'additional', 'full'])
                ->get();

            $prevRevenue = $prevPayments->sum('amount');
            $prevCount = $prevPayments->count();
            $prevAvg = $prevCount > 0 ? $prevRevenue / $prevCount : 0;

            $prevAppts = Appointment::whereBetween('appointment_date', [$prevStart, $prevEnd])->get();
            $prevTotal = $prevAppts->count();
            $prevCompleted = $prevAppts->where('status', 'completed')->count();
            $prevCancelled = $prevAppts->where('status', 'cancelled')->count();

            // FIXED: Uses appointment_date not updated_at
            $prevNoShows = Appointment::whereBetween('appointment_date', [$prevStart, $prevEnd])
                ->where('status', 'cancelled')
                ->where(function ($q) {
                    $q->where('cancellation_reason', 'customer_no_show')
                    ->orWhereNull('cancellation_reason');
                })->count();

            $prevCompletion = $prevTotal > 0 ? round(($prevCompleted / $prevTotal) * 100, 1) : 0;
            $prevNoShowRate = $prevTotal > 0 ? round(($prevNoShows / $prevTotal) * 100, 1) : 0;
            $prevCancelRate = $prevTotal > 0 ? round(($prevCancelled / $prevTotal) * 100, 1) : 0;

            $prevServiceRev = [];
            $prevStaffRev = [];
            foreach ($prevPayments as $p) {
                $appt = $p->appointment;
                if (!$appt) continue;
                $staffName = $appt->staff->full_name ?? 'Unassigned';
                $prevStaffRev[$staffName] = ($prevStaffRev[$staffName] ?? 0) + $p->amount;

                $svcCount = $appt->services->count();
                if ($svcCount > 0 && $p->amount > 0) {
                    $perSvc = $p->amount / $svcCount;
                    foreach ($appt->services as $svc) {
                        $prevServiceRev[$svc->name] = ($prevServiceRev[$svc->name] ?? 0) + $perSvc;
                    }
                }
            }
            arsort($prevServiceRev);
            arsort($prevStaffRev);

            $history[] = [
                'label' => $prevStart->format('M d') . '–' . $prevEnd->format('M d'),
                'revenue' => number_format($prevRevenue, 2),
                'avgSale' => number_format($prevAvg, 2),
                'completionRate' => $prevCompletion,
                'noShowRate' => $prevNoShowRate,
                'cancellationRate' => $prevCancelRate,
                'topService' => array_key_first($prevServiceRev) ?: 'None',
                'topStaff' => array_key_first($prevStaffRev) ?: 'None',
            ];
        }

        return $history;
    }

    /**
     * 12-month customer booking spike analytics.
     */
    private function getMonthlySpikeData(): array
    {
        $now = Carbon::now();
        $months = [];
        $bookings = [];
        $revenues = [];
        $noShows = [];
        $completions = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $now->copy()->subMonths($i)->endOfMonth();
            $label = $monthStart->format('M Y');

            $monthAppts = Appointment::whereBetween('appointment_date', [$monthStart, $monthEnd])->get();
            $monthPayments = Payment::whereBetween('paid_at', [$monthStart, $monthEnd])
                ->whereIn('type', ['completion', 'additional', 'full'])
                ->get();

            $total = $monthAppts->count();
            $completed = $monthAppts->where('status', 'completed')->count();
            $ns = $monthAppts->where('status', 'cancelled')
                ->where(function ($q) {
                    $q->where('cancellation_reason', 'customer_no_show')
                      ->orWhereNull('cancellation_reason');
                })->count();

            $months[] = $label;
            $bookings[] = $total;
            $revenues[] = round($monthPayments->sum('amount'), 2);
            $noShows[] = $total > 0 ? round(($ns / $total) * 100, 1) : 0;
            $completions[] = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
        }

        $peakIndex = array_search(max($bookings), $bookings);
        $peakMonth = $months[$peakIndex] ?? 'N/A';
        $peakBookings = $bookings[$peakIndex] ?? 0;

        $positiveBookings = array_filter($bookings, fn($v) => $v > 0);
        $lowIndex = !empty($positiveBookings) 
            ? array_search(min($positiveBookings), $bookings) 
            : 0;
        $lowMonth = $months[$lowIndex] ?? 'N/A';

        $recentAvg = array_sum(array_slice($bookings, -3)) / 3;
        $previousSlice = array_slice($bookings, -6, 3);
        $previousAvg = !empty($previousSlice) ? array_sum($previousSlice) / count($previousSlice) : 0;
        $trend = $previousAvg > 0 ? round((($recentAvg - $previousAvg) / $previousAvg) * 100, 1) : 0;

        return [
            'labels' => $months,
            'bookings' => $bookings,
            'revenues' => $revenues,
            'noShowRates' => $noShows,
            'completionRates' => $completions,
            'peakMonth' => $peakMonth,
            'peakBookings' => $peakBookings,
            'lowMonth' => $lowMonth,
            'trendPercent' => $trend,
            'trendDirection' => $trend >= 0 ? 'up' : 'down',
        ];
    }
}