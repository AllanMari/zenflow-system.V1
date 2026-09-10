<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Services\OllamaInsightService;

class SalesAnalyticsService
{
    private const COMMISSION_RATE = 0.30;

    /*
    |--------------------------------------------------------------------------
    | Payment types considered actual service revenue
    |--------------------------------------------------------------------------
    |
    | Deposits are not counted as completed service revenue.
    | They are tracked separately.
    |
    */
    private const REVENUE_PAYMENT_TYPES = [
        'completion',
        'additional',
        'full',
    ];

    private const SERVICE_CODE_MAP = [
        'Alexandria Massage'        => 'AM',
        'Swedish Massage'           => 'SM',
        'Thai Massage'              => 'TM',
        'Therma Massage'            => 'TSM',
        'Ventosa Massage'           => 'VC',
        'Alexa\'s Stone Massage'    => 'AS',
        'The Little One\'s Massage' => 'TLC',
        'Body Scrub'                => 'BS',
        'Foot Spa'                  => 'FS',
        'Facial Cleansing'          => 'FC',
        'Restful Head Massage'       => 'RHM',
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

    public function getDashboardData(Request $request): array
    {
        $period = $request->get('period', 'daily');
        $today  = Carbon::today();
        $status = $request->get('status', 'completed');

        [$startDate, $endDate, $label] = $this->resolveDateRange(
            $period,
            $request,
            $today
        );

        $baseQuery = $this->buildBaseQuery($startDate, $endDate);

        /*
        |--------------------------------------------------------------------------
        | Transaction log payments
        |--------------------------------------------------------------------------
        |
        | $payments:
        |   Paginated payments for the selected status.
        |
        | $filteredAllPayments:
        |   ALL payments for the selected status.
        |   Used for transaction-log totals and transaction count.
        |
        | $allPayments:
        |   ALL statuses.
        |   Still used for the general dashboard analytics.
        |
        */
        $payments = $this->getFilteredPayments(
            clone $baseQuery,
            $status
        );

        $filteredAllPayments = $this->getAllFilteredPayments(
            clone $baseQuery,
            $status
        );

        $allPayments = (clone $baseQuery)
            ->orderBy('paid_at', 'desc')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Appointments in selected period
        |--------------------------------------------------------------------------
        */
        $periodAppts = Appointment::with([
            'payments',
            'customer',
            'staff',
            'services',
            'room',
        ])
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->get();

        $totalApptsInPeriod = $periodAppts->count();

        $completedApptsInPeriod = $periodAppts
            ->where('status', 'completed')
            ->count();

        $cancelledApptsInPeriod = $periodAppts
            ->where('status', 'cancelled')
            ->where(function ($q) {
                return $q->where('cancellation_reason', '!=', 'customer_no_show')
                    ->orWhereNull('cancellation_reason');
            })
            ->count();

        $noShowApptsInPeriod = $periodAppts
            ->where('status', 'cancelled')
            ->where('cancellation_reason', 'customer_no_show')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | General revenue
        |--------------------------------------------------------------------------
        */
        $grossSales = $allPayments
            ->where('amount', '>', 0)
            ->sum('amount');

        $refundTotal = abs(
            $allPayments
                ->where('amount', '<', 0)
                ->sum('amount')
        );

        $totalRevenue = $grossSales - $refundTotal;

        $totalCount = $allPayments
            ->where('amount', '>', 0)
            ->count();

        $avgSale = $totalCount > 0
            ? $totalRevenue / $totalCount
            : 0;

        $deposits = $allPayments
            ->where('type', 'deposit')
            ->where('amount', '>', 0)
            ->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | No-shows
        |--------------------------------------------------------------------------
        */
        $noShowAppointments = $periodAppts
            ->where('status', 'cancelled')
            ->where('cancellation_reason', 'customer_no_show')
            ->values();

        $noShowData = $this->getNoShowData($noShowAppointments);

        /*
        |--------------------------------------------------------------------------
        | Existing analytics
        |--------------------------------------------------------------------------
        */
        $methodBreakdown = $this->getCollectionBreakdown(
            $allPayments,
            fn ($p) => str_replace('_', ' ', $p->payment_method)
        );

        $typeBreakdown = $this->getCollectionBreakdown(
            $allPayments,
            'type'
        );

        [$chartLabels, $chartValues] = $this->getChartData(
            $allPayments,
            $period,
            $startDate,
            $endDate
        );

        /*
        |--------------------------------------------------------------------------
        | Service analytics + hourly revenue
        |--------------------------------------------------------------------------
        */
        [$serviceRevenue, $staffRevenue, $hourlyRevenue] =
            $this->buildAnalytics(
                $allPayments,
                $noShowAppointments
            );

        /*
        |--------------------------------------------------------------------------
        | NEW: Staff Performance
        |--------------------------------------------------------------------------
        */
        $staffPerformance = $this->buildStaffPerformance(
            $periodAppts,
            $allPayments
        );

        /*
        |--------------------------------------------------------------------------
        | Top services / staff
        |--------------------------------------------------------------------------
        */
        $topServices = array_slice(
            $serviceRevenue,
            0,
            5,
            true
        );

        /*
        | Derive Top Staff from the proper staff-performance dataset.
        */
        $topStaff = [];

        foreach (array_slice($staffPerformance, 0, 5) as $staff) {
            $topStaff[$staff['name']] = $staff['revenue'];
        }

        /*
        |--------------------------------------------------------------------------
        | Peak Business Hours
        |--------------------------------------------------------------------------
        */
        $peakBusinessHours = $this->buildPeakBusinessHours(
            $periodAppts
        );

        /*
        |--------------------------------------------------------------------------
        | Period comparison
        |--------------------------------------------------------------------------
        */
        [$revenueChange, $revenueChangeLabel] =
            $this->calculatePeriodOverPeriod(
                $startDate,
                $endDate,
                $totalRevenue
            );

        /*
        |--------------------------------------------------------------------------
        | Appointment metrics
        |--------------------------------------------------------------------------
        */
        $completionRate = $totalApptsInPeriod > 0
            ? round(
                ($completedApptsInPeriod / $totalApptsInPeriod) * 100,
                1
            )
            : 0;

        $noShowRate = $totalApptsInPeriod > 0
            ? round(
                ($noShowApptsInPeriod / $totalApptsInPeriod) * 100,
                1
            )
            : 0;

        $cancellationRate = $totalApptsInPeriod > 0
            ? round(
                ($cancelledApptsInPeriod / $totalApptsInPeriod) * 100,
                1
            )
            : 0;

        $depositCount = $allPayments
            ->where('type', 'deposit')
            ->count();

        $completionCount = $allPayments
            ->whereIn('type', self::REVENUE_PAYMENT_TYPES)
            ->count();

        $conversionRate = $depositCount > 0
            ? round(
                ($completionCount / $depositCount) * 100,
                1
            )
            : 0;

        $uniqueCustomers = $allPayments
            ->pluck('appointment.customer_id')
            ->filter()
            ->unique()
            ->count();

        $revPerCompletedAppt = $completedApptsInPeriod > 0
            ? $totalRevenue / $completedApptsInPeriod
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Transaction log
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Use $filteredAllPayments here instead of $allPayments.
        |
        | This makes:
        |   Completed -> completed transaction count
        |   Cancelled -> cancelled transaction count
        |   No Show   -> customer no-show transaction count
        |
        | The rest of the dashboard still uses $allPayments.
        |
        */
        $txLogData = $this->buildTransactionRows(
            $payments,
            $filteredAllPayments,
            $status
        );

        $printServiceSummary = $this->buildPrintServiceSummary(
            $allPayments
        );

        /*
        |--------------------------------------------------------------------------
        | Historical data
        |--------------------------------------------------------------------------
        */
        $history = $this->get90DayHistory(
            $startDate,
            $endDate,
            $period
        );

        /*
        |--------------------------------------------------------------------------
        | AI insights
        |--------------------------------------------------------------------------
        */
        $ollama = new OllamaInsightService();

        $aiOnline = $ollama->healthCheck();

        $insightPayload = [
            'period'           => $period,
            'label'            => $label,
            'startDate'        => $startDate->format('M d, Y'),
            'endDate'          => $endDate->format('M d, Y'),
            'totalRevenue'     => number_format($totalRevenue, 2),
            'totalCount'       => $totalCount,
            'avgSale'          => number_format($avgSale, 2),
            'uniqueCustomers'  => $uniqueCustomers,
            'completionRate'   => $completionRate,
            'noShowRate'       => $noShowRate,
            'cancellationRate' => $cancellationRate,
            'revenueChange'    => $revenueChange,
            'conversionRate'   => $conversionRate,
            'deposits'         => number_format($deposits, 2),
            'revPerComp'       => number_format($revPerCompletedAppt, 2),
            'topService'       => array_key_first($topServices) ?: 'None',
            'topStaff'         => array_key_first($topStaff) ?: 'None',
            'peakHour'         => $peakBusinessHours['peakLabel'] ?? 'N/A',
        ];

        $aiInsights = $ollama->getInsights(
            $insightPayload,
            $history
        );

        $suggestions = !empty($aiInsights)
            ? $aiInsights
            : $this->generateFallbackInsights(
                $completionRate,
                $noShowRate,
                $cancellationRate,
                $revenueChange,
                $avgSale,
                $revPerCompletedAppt,
                $deposits,
                $totalRevenue,
                $topStaff,
                $topServices,
                $totalCount,
                $uniqueCustomers,
                $conversionRate
            );

        /*
        |--------------------------------------------------------------------------
        | Return dashboard data
        |--------------------------------------------------------------------------
        */
        return [
            'totalRevenue'           => $totalRevenue,
            'grossSales'             => $grossSales,
            'refundTotal'            => $refundTotal,
            'totalCount'             => $totalCount,
            'avgSale'                => $avgSale,
            'deposits'               => $deposits,
            'uniqueCustomers'        => $uniqueCustomers,
            'revPerCompletedAppt'    => $revPerCompletedAppt,

            'noShowData'             => $noShowData,
            'methodBreakdown'        => $methodBreakdown,
            'typeBreakdown'          => $typeBreakdown,

            'chartLabels'            => $chartLabels,
            'chartValues'            => $chartValues,

            'period'                 => $period,
            'startDate'              => $startDate,
            'endDate'                => $endDate,
            'label'                  => $label,
            'today'                  => $today,

            'topServices'            => $topServices,
            'topStaff'               => $topStaff,

            'staffPerformance'       => $staffPerformance,

            'hourlyRevenue'          => $hourlyRevenue,
            'maxHourly'              => !empty($hourlyRevenue)
                ? max($hourlyRevenue)
                : 1,

            'peakBusinessHours'      => $peakBusinessHours,

            'maxSvc'                 => !empty($topServices)
                ? max($topServices)
                : 1,

            'maxStaff'               => !empty($topStaff)
                ? max($topStaff)
                : 1,

            'revenueChange'          => $revenueChange,
            'revenueChangeLabel'     => $revenueChangeLabel,

            'completionRate'         => $completionRate,
            'noShowRate'             => $noShowRate,
            'cancellationRate'       => $cancellationRate,

            'totalApptsInPeriod'     => $totalApptsInPeriod,
            'completedApptsInPeriod' => $completedApptsInPeriod,
            'cancelledApptsInPeriod'=> $cancelledApptsInPeriod,
            'noShowApptsInPeriod'    => $noShowApptsInPeriod,

            'conversionRate'         => $conversionRate,

            'printServiceSummary'    => $printServiceSummary,
            'txLogData'              => $txLogData,
            'currentStatus'          => $status,

            'suggestions'            => $suggestions,

            'monthlySpike'           => $this->getMonthlySpikeData(),

            'aiOnline'               => $aiOnline,

            'serviceCodeMap'         => self::SERVICE_CODE_MAP,

            'insightPayload'         => $insightPayload,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Date range
    |--------------------------------------------------------------------------
    */
    private function resolveDateRange(
        string $period,
        Request $request,
        Carbon $today
    ): array {
        return match ($period) {
            'weekly' => [
                $today->copy()->startOfWeek(),
                $today->copy()->endOfWeek(),
                'This Week'
            ],

            'monthly' => [
                $today->copy()->startOfMonth(),
                $today->copy()->endOfMonth(),
                'This Month'
            ],

            'yearly' => [
                $today->copy()->startOfYear(),
                $today->copy()->endOfYear(),
                'This Year'
            ],

            'custom' => [
                $request->filled('start_date')
                    ? Carbon::parse($request->start_date)->startOfDay()
                    : $today->copy()->startOfDay(),

                $request->filled('end_date')
                    ? Carbon::parse($request->end_date)->endOfDay()
                    : $today->copy()->endOfDay(),

                'Custom Range',
            ],

            default => [
                $today->copy()->startOfDay(),
                $today->copy()->endOfDay(),
                'Today'
            ],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Base payment query
    |--------------------------------------------------------------------------
    */
    private function buildBaseQuery(
        Carbon $startDate,
        Carbon $endDate
    ) {
        return Payment::with([
            'appointment.customer',
            'appointment.services',
            'appointment.staff',
            'appointment.room',
            'appointment.payments',
        ])
            ->whereHas(
                'appointment',
                fn ($q) => $q->where('status', '!=', 'confirmed')
            )
            ->whereBetween(
                'paid_at',
                [$startDate, $endDate]
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Transaction status filter
    |--------------------------------------------------------------------------
    |
    | Centralized status filtering so both:
    |
    | 1. paginated transaction rows
    | 2. transaction-log totals/count
    |
    | use exactly the same status rules.
    |
    */
    private function applyTransactionStatusFilter(
        $query,
        string $currentStatus
    ) {
        if ($currentStatus === 'all') {
            return $query;
        }

        $query->whereHas(
            'appointment',
            function ($q) use ($currentStatus) {
                match ($currentStatus) {
                    'customer_no_show' =>
                        $q->where('status', 'cancelled')
                            ->where(
                                'cancellation_reason',
                                'customer_no_show'
                            ),

                    'cancelled' =>
                        $q->where('status', 'cancelled')
                            ->where(
                                function ($sq) {
                                    $sq->where(
                                        'cancellation_reason',
                                        '!=',
                                        'customer_no_show'
                                    )
                                    ->orWhereNull(
                                        'cancellation_reason'
                                    );
                                }
                            ),

                    default =>
                        $q->where(
                            'status',
                            $currentStatus
                        ),
                };
            }
        );

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Paginated transaction payments
    |--------------------------------------------------------------------------
    */
    private function getFilteredPayments(
        $query,
        string $currentStatus
    ) {
        $query = $this->applyTransactionStatusFilter(
            $query,
            $currentStatus
        );

        return $query
            ->orderBy('paid_at', 'desc')
            ->paginate(25)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | All transaction payments for selected status
    |--------------------------------------------------------------------------
    |
    | Unlike getFilteredPayments(), this does NOT paginate.
    |
    | It is used to calculate the total transaction count and
    | transaction-log grand totals for the selected status.
    |
    */
    private function getAllFilteredPayments(
        $query,
        string $currentStatus
    ): Collection {
        $query = $this->applyTransactionStatusFilter(
            $query,
            $currentStatus
        );

        return $query
            ->orderBy('paid_at', 'desc')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Existing analytics
    |--------------------------------------------------------------------------
    */
    private function buildAnalytics(
        Collection $allPayments,
        Collection $noShowAppointments
    ): array {
        $serviceRevenue = [];
        $staffRevenue = [];

        $hourlyRevenue = array_fill(
            10,
            11,
            0
        );

        foreach ($allPayments as $payment) {
            $appt = $payment->appointment;

            if (!$appt) {
                continue;
            }

            /*
            | Staff revenue:
            | Only actual service revenue payment types.
            */
            if (
                in_array(
                    $payment->type,
                    self::REVENUE_PAYMENT_TYPES,
                    true
                )
            ) {
                $staffName = $appt->staff->full_name
                    ?? 'Unassigned';

                $staffRevenue[$staffName] =
                    ($staffRevenue[$staffName] ?? 0)
                    + (float) $payment->amount;
            }

            /*
            | Hourly payment revenue.
            */
            $hour = (int) Carbon::parse(
                $payment->paid_at
            )->format('H');

            if ($hour >= 10 && $hour <= 20) {
                $hourlyRevenue[$hour] =
                    ($hourlyRevenue[$hour] ?? 0)
                    + (float) $payment->amount;
            }

            /*
            | Service revenue.
            |
            | IMPORTANT:
            | Uses pivot->price_at_booking.
            */
            $svcCount = $appt->services->count();

            if (
                $svcCount > 0
                && $payment->amount > 0
                && in_array(
                    $payment->type,
                    self::REVENUE_PAYMENT_TYPES,
                    true
                )
            ) {
                $perService =
                    $payment->amount / $svcCount;

                foreach ($appt->services as $svc) {
                    $name =
                        $svc->pivot->service_name
                        ?? $svc->name;

                    $serviceRevenue[$name] =
                        ($serviceRevenue[$name] ?? 0)
                        + $perService;
                }
            }
        }

        /*
        | No-show forfeited deposits remain revenue for
        | the business, but are not treated as therapist
        | service-performance revenue.
        */
        foreach ($noShowAppointments as $appt) {
            $deposit = $appt->payments
                ->where('type', 'deposit')
                ->sum('amount');

            $refund = abs(
                $appt->payments
                    ->where('type', 'refund')
                    ->sum('amount')
            );

            if ($deposit > 0 && $refund == 0) {
                $svcCount = $appt->services->count();

                if ($svcCount > 0) {
                    $perService =
                        $deposit / $svcCount;

                    foreach ($appt->services as $svc) {
                        $name =
                            $svc->pivot->service_name
                            ?? $svc->name;

                        $serviceRevenue[$name] =
                            ($serviceRevenue[$name] ?? 0)
                            + $perService;
                    }
                }
            }
        }

        arsort($serviceRevenue);
        arsort($staffRevenue);

        return [
            $serviceRevenue,
            $staffRevenue,
            $hourlyRevenue
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | NEW: Staff Performance
    |--------------------------------------------------------------------------
    */
    private function buildStaffPerformance(
        Collection $appointments,
        Collection $payments
    ): array {
        $staff = [];

        /*
        | First create staff records from appointments.
        */
        foreach ($appointments as $appt) {
            if (!$appt->staff) {
                continue;
            }

            $staffId = $appt->staff->id;
            $staffName = $appt->staff->full_name
                ?? $appt->staff->name
                ?? 'Unnamed Staff';

            if (!isset($staff[$staffId])) {
                $staff[$staffId] = [
                    'id' => $staffId,
                    'name' => $staffName,
                    'appointments' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                    'no_shows' => 0,
                    'services' => 0,
                    'revenue' => 0,
                    'commission' => 0,
                    'completion_rate' => 0,
                    'avg_revenue' => 0,
                ];
            }

            $staff[$staffId]['appointments']++;

            if ($appt->status === 'completed') {
                $staff[$staffId]['completed']++;
            }

            if ($appt->status === 'cancelled') {
                if (
                    $appt->cancellation_reason
                    === 'customer_no_show'
                ) {
                    $staff[$staffId]['no_shows']++;
                } else {
                    $staff[$staffId]['cancelled']++;
                }
            }

            /*
            | Services actually handled by the staff.
            | We count services on completed appointments.
            */
            if ($appt->status === 'completed') {
                $staff[$staffId]['services'] +=
                    $appt->services->count();
            }
        }

        /*
        | Add actual collected service revenue.
        */
        foreach ($payments as $payment) {
            if (
                !in_array(
                    $payment->type,
                    self::REVENUE_PAYMENT_TYPES,
                    true
                )
            ) {
                continue;
            }

            if ((float) $payment->amount <= 0) {
                continue;
            }

            $appt = $payment->appointment;

            if (!$appt || !$appt->staff) {
                continue;
            }

            /*
            | Only completed appointments contribute to
            | staff service revenue.
            */
            if ($appt->status !== 'completed') {
                continue;
            }

            $staffId = $appt->staff->id;

            /*
            | The appointment may fall within the selected
            | appointment period but the payment may be
            | outside the range. Only staff already represented
            | in the period are included.
            */
            if (!isset($staff[$staffId])) {
                continue;
            }

            $staff[$staffId]['revenue'] +=
                (float) $payment->amount;
        }

        /*
        | Calculate derived metrics.
        */
        foreach ($staff as &$record) {
            $record['completion_rate'] =
                $record['appointments'] > 0
                    ? round(
                        (
                            $record['completed']
                            / $record['appointments']
                        ) * 100,
                        1
                    )
                    : 0;

            $record['avg_revenue'] =
                $record['completed'] > 0
                    ? $record['revenue']
                        / $record['completed']
                    : 0;

            $record['commission'] =
                $record['revenue'] > 0
                    ? $record['revenue']
                        * self::COMMISSION_RATE
                    : 0;
        }

        unset($record);

        /*
        | Rank by completed-service revenue.
        */
        uasort(
            $staff,
            fn ($a, $b) =>
                $b['revenue'] <=> $a['revenue']
        );

        /*
        | Add ranking.
        */
        $rank = 1;

        foreach ($staff as &$record) {
            $record['rank'] = $rank++;
        }

        unset($record);

        return array_values($staff);
    }

    /*
    |--------------------------------------------------------------------------
    | NEW: Peak Business Hours
    |--------------------------------------------------------------------------
    |
    | Uses appointment START TIME, not payment time.
    | This is more meaningful for spa staffing and scheduling.
    |
    */
    private function buildPeakBusinessHours(
        Collection $appointments
    ): array {
        $hourCounts = [];

        for ($hour = 8; $hour <= 22; $hour++) {
            $hourCounts[$hour] = 0;
        }

        foreach ($appointments as $appt) {
            if (!$appt->start_time) {
                continue;
            }

            $hour = (int) Carbon::parse(
                $appt->start_time
            )->format('H');

            if (isset($hourCounts[$hour])) {
                $hourCounts[$hour]++;
            }
        }

        $peakCount = !empty($hourCounts)
            ? max($hourCounts)
            : 0;

        $peakHours = [];

        foreach ($hourCounts as $hour => $count) {
            if ($count === $peakCount && $count > 0) {
                $peakHours[] = $hour;
            }
        }

        $formatHour = function ($hour) {
            return Carbon::createFromTime(
                $hour,
                0
            )->format('g A');
        };

        $labels = [];

        foreach ($hourCounts as $hour => $count) {
            $labels[] = [
                'hour' => $hour,
                'label' => $formatHour($hour),
                'count' => $count,
                'is_peak' =>
                    in_array($hour, $peakHours, true),
            ];
        }

        $peakLabels = array_map(
            $formatHour,
            $peakHours
        );

        return [
            'hours' => $labels,
            'peakHours' => $peakHours,
            'peakLabels' => $peakLabels,
            'peakCount' => $peakCount,
            'peakLabel' => !empty($peakLabels)
                ? implode(', ', $peakLabels)
                : 'N/A',
        ];
    }

    private function getCollectionBreakdown(
        Collection $collection,
        $groupByField
    ) {
        return $collection
            ->groupBy($groupByField)
            ->map(
                fn ($group) => [
                    'count' => $group->count(),
                    'total' => $group->sum('amount')
                ]
            )
            ->sortByDesc('total');
    }

    private function getChartData(
        Collection $allPayments,
        string $period,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $labels = [];
        $values = [];

        if ($period === 'yearly') {
            for ($m = 0; $m < 12; $m++) {
                $monthStart = $startDate
                    ->copy()
                    ->addMonths($m)
                    ->startOfMonth();

                $monthEnd = $startDate
                    ->copy()
                    ->addMonths($m)
                    ->endOfMonth();

                if ($monthStart > $endDate) {
                    break;
                }

                $labels[] = $monthStart->format('M');

                $values[] = $allPayments
                    ->filter(
                        fn ($p) =>
                            Carbon::parse($p->paid_at)
                                ->between(
                                    $monthStart,
                                    $monthEnd
                                )
                    )
                    ->sum('amount');
            }
        } elseif ($period === 'monthly') {
            $weekCursor =
                $startDate->copy()->startOfWeek();

            while ($weekCursor <= $endDate) {
                $weekEnd =
                    $weekCursor->copy()->endOfWeek();

                $labels[] =
                    $weekCursor->format('M d');

                $values[] = $allPayments
                    ->filter(
                        fn ($p) =>
                            Carbon::parse($p->paid_at)
                                ->between(
                                    $weekCursor,
                                    $weekEnd
                                )
                    )
                    ->sum('amount');

                $weekCursor->addWeek();
            }
        } else {
            $cursor = $startDate->copy();

            while ($cursor <= $endDate) {
                $labels[] = $cursor->format('M d');

                $values[] = $allPayments
                    ->filter(
                        fn ($p) =>
                            Carbon::parse($p->paid_at)
                                ->isSameDay($cursor)
                    )
                    ->sum('amount');

                $cursor->addDay();
            }
        }

        return [$labels, $values];
    }

    private function calculatePeriodOverPeriod(
        Carbon $startDate,
        Carbon $endDate,
        float $currentRevenue
    ): array {
        $periodDays =
            max(
                1,
                $startDate->diffInDays($endDate) + 1
            );

        $prevStart =
            $startDate->copy()->subDays($periodDays);

        $prevEnd =
            $endDate->copy()->subDays($periodDays);

        $prevPayments = Payment::with(
            'appointment.payments'
        )
            ->whereBetween(
                'paid_at',
                [$prevStart, $prevEnd]
            )
            ->whereIn(
                'type',
                self::REVENUE_PAYMENT_TYPES
            )
            ->get();

        $prevRevenue =
            $prevPayments->sum('amount');

        $prevAppts = Appointment::with('payments')
            ->whereBetween(
                'appointment_date',
                [$prevStart, $prevEnd]
            )
            ->get();

        $prevNoShows = $prevAppts
            ->where('status', 'cancelled')
            ->where(
                'cancellation_reason',
                'customer_no_show'
            );

        foreach ($prevNoShows as $appt) {
            $deposit = $appt->payments
                ->where('type', 'deposit')
                ->sum('amount');

            $refund = abs(
                $appt->payments
                    ->where('type', 'refund')
                    ->sum('amount')
            );

            if ($deposit > 0 && $refund == 0) {
                $prevRevenue += $deposit;
            }
        }

        $change =
            $prevRevenue > 0
                ? (
                    ($currentRevenue - $prevRevenue)
                    / $prevRevenue
                ) * 100
                : 0;

        return [
            $change,
            ($change >= 0 ? '+' : '')
                . number_format($change, 1)
        ];
    }

    private function getNoShowData(
        Collection $noShowAppointments
    ): array {
        $forfeited = 0;
        $refunded = 0;
        $list = [];

        foreach ($noShowAppointments as $appt) {
            $deposit = $appt->payments
                ->where('type', 'deposit')
                ->sum('amount');

            $refund = abs(
                $appt->payments
                    ->where('type', 'refund')
                    ->sum('amount')
            );

            $wasRefunded = $refund > 0;

            if ($wasRefunded) {
                $refunded += $refund;
            } else {
                $forfeited += $deposit;
            }

            $phone =
                $appt->customer->phone_number
                ?? (
                    $appt->customer->user->phone_number
                    ?? 'N/A'
                );

            if (
                $phone === 'N/A'
                && !empty($appt->guest_phone)
            ) {
                $phone = $appt->guest_phone;
            }

            $list[] = [
                'customer' =>
                    $appt->customer->full_name
                    ?? trim(
                        ($appt->guest_first_name ?? '')
                        . ' '
                        . ($appt->guest_last_name ?? '')
                    )
                    ?: 'Walk-in',

                'phone' => $phone,

                'date' =>
                    $appt->appointment_date,

                'marked_at' =>
                    $appt->updated_at,

                'deposit' =>
                    $deposit,

                'refund' =>
                    $refund,

                'status' =>
                    $wasRefunded
                        ? 'Refunded'
                        : 'Forfeited',
            ];
        }

        return [
            'count' =>
                $noShowAppointments->count(),

            'forfeited' =>
                $forfeited,

            'refunded' =>
                $refunded,

            'list' =>
                $list,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Transaction log rows
    |--------------------------------------------------------------------------
    */
    private function buildTransactionRows(
        $paginatedPayments,
        $filteredPayments,
        string $filterStatus
    ): array {
        $rows = [];
        $rowNum = 1;

        foreach ($paginatedPayments as $payment) {
            $appt = $payment->appointment;

            if (!$appt) {
                continue;
            }

            $services = $appt->services;

            $serviceList = $services
                ->map(
                    fn ($svc) => [
                        'code' =>
                            $this->resolveServiceCode(
                                $svc->pivot->service_name
                                    ?? $svc->name,
                                $svc->code
                            ),

                        'name' =>
                            $svc->pivot->service_name
                            ?? $svc->name,
                    ]
                )
                ->toArray();

            /*
            | appointment_services uses price_at_booking.
            */
            $gross = $services->sum(
                fn ($s) =>
                    (float) (
                        $s->pivot->price_at_booking
                        ?? $s->price
                        ?? 0
                    )
            );

            $discount = 0;

            foreach ($services as $svc) {
                $price = (float) (
                    $svc->pivot->price_at_booking
                    ?? $svc->price
                    ?? 0
                );

                $discPrice = (float) (
                    $svc->pivot->discount_price
                    ?? $svc->discount_price
                    ?? 0
                );

                if (
                    $discPrice > 0
                    && $discPrice < $price
                ) {
                    $discount +=
                        $price - $discPrice;
                }
            }

            if (
                ($appt->discount_amount ?? 0) > 0
                && $gross > 0
            ) {
                $discount +=
                    $appt->discount_amount;
            } elseif (
                ($appt->discount_percent ?? 0) > 0
                && $gross > 0
            ) {
                $discount +=
                    $gross
                    * (
                        $appt->discount_percent
                        / 100
                    );
            }

            $net = max(0, $gross - $discount);

            $commission =
                $appt->staff && $net > 0
                    ? $net * self::COMMISSION_RATE
                    : 0;

            $durationMinutes = $services->sum(
                fn ($s) =>
                    $s->pivot->service_duration
                    ?? $s->duration_minutes
                    ?? 0
            );

            if ($durationMinutes <= 0) {
                $durationMinutes =
                    abs(
                        Carbon::parse(
                            $appt->end_time
                        )->diffInMinutes(
                            Carbon::parse(
                                $appt->start_time
                            )
                        )
                    );
            }

            $trueStatus =
                $appt->status === 'cancelled'
                && $appt->cancellation_reason
                    === 'customer_no_show'
                    ? 'customer_no_show'
                    : $appt->status;

            $rows[] = [
                'rowNum' =>
                    $rowNum++,

                'customerName' =>
                    $appt->customer->full_name
                    ?? trim(
                        ($appt->guest_first_name ?? '')
                        . ' '
                        . ($appt->guest_last_name ?? '')
                    )
                    ?: 'Walk-in',

                'room' =>
                    $appt->room->name ?? 'N/A',

                'startTime' =>
                    Carbon::parse(
                        $appt->start_time
                    )->format('g:i A'),

                'endTime' =>
                    Carbon::parse(
                        $appt->end_time
                    )->format('g:i A'),

                'staffName' =>
                    $appt->staff->full_name
                    ?? 'Unassigned',

                'durationHrs' =>
                    round(
                        $durationMinutes / 60,
                        1
                    ),

                'serviceList' =>
                    $serviceList,

                'grossAmount' =>
                    number_format(
                        $gross,
                        2
                    ),

                'discountAmount' =>
                    $discount > 0
                        ? number_format(
                            $discount,
                            2
                        )
                        : null,

                'discountPercent' =>
                    (
                        $gross > 0
                        && $discount > 0
                    )
                        ? round(
                            (
                                $discount / $gross
                            ) * 100,
                            1
                        )
                        : null,

                'netAmount' =>
                    number_format(
                        $net,
                        2
                    ),

                'noteText' =>
                    $appt->notes ?? '',

                'comPct' =>
                    (int) (
                        self::COMMISSION_RATE * 100
                    ),

                'therapistCom' =>
                    number_format(
                        $commission,
                        2
                    ),

                'filterKey' =>
                    $trueStatus,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Grand totals: unique appointments for SELECTED STATUS
        |--------------------------------------------------------------------------
        |
        | IMPORTANT FIX:
        |
        | Previously this used $allPayments, which contains every status.
        | Therefore the count stayed the same when changing the dropdown.
        |
        | It now uses $filteredPayments, which contains only the selected
        | transaction status.
        |
        */
        $grandGross = 0;
        $grandNet = 0;
        $grandCom = 0;

        $uniqueAppts =
            $filteredPayments
                ->pluck('appointment')
                ->filter()
                ->unique('id')
                ->values();

        foreach ($uniqueAppts as $appt) {
            $gross = $appt->services->sum(
                fn ($s) =>
                    (float) (
                        $s->pivot->price_at_booking
                        ?? $s->price
                        ?? 0
                    )
            );

            $discount = 0;

            foreach ($appt->services as $svc) {
                $price = (float) (
                    $svc->pivot->price_at_booking
                    ?? $svc->price
                    ?? 0
                );

                $discPrice = (float) (
                    $svc->pivot->discount_price
                    ?? $svc->discount_price
                    ?? 0
                );

                if (
                    $discPrice > 0
                    && $discPrice < $price
                ) {
                    $discount +=
                        $price - $discPrice;
                }
            }

            if (
                ($appt->discount_amount ?? 0) > 0
                && $gross > 0
            ) {
                $discount +=
                    $appt->discount_amount;
            } elseif (
                ($appt->discount_percent ?? 0) > 0
                && $gross > 0
            ) {
                $discount +=
                    $gross
                    * (
                        $appt->discount_percent
                        / 100
                    );
            }

            $net =
                max(
                    0,
                    $gross - $discount
                );

            $grandGross += $gross;
            $grandNet += $net;

            $grandCom +=
                (
                    $appt->staff
                    && $net > 0
                )
                    ? $net * self::COMMISSION_RATE
                    : 0;
        }

        return [
            'rows' =>
                $rows,

            'grandGross' =>
                $grandGross,

            'grandNet' =>
                $grandNet,

            'grandCom' =>
                $grandCom,

            /*
            | This now changes according to the selected status.
            */
            'totalFiltered' =>
                $uniqueAppts->count(),

            'pagination' =>
                $paginatedPayments,
        ];
    }

    private function resolveServiceCode(
        string $name,
        ?string $existingCode
    ): string {
        if (!empty($existingCode)) {
            return $existingCode;
        }

        if (
            isset(
                self::SERVICE_CODE_MAP[$name]
            )
        ) {
            return self::SERVICE_CODE_MAP[$name];
        }

        $words = array_filter(
            explode(
                ' ',
                preg_replace(
                    '/[^a-z0-9 ]/i',
                    '',
                    $name
                )
            )
        );

        $initials = '';

        foreach ($words as $word) {
            $initials .= strtoupper(
                substr($word, 0, 1)
            );
        }

        return substr(
            $initials,
            0,
            3
        ) ?: 'SVC';
    }

    private function buildPrintServiceSummary(
        Collection $payments
    ): array {
        $summary = [];

        foreach ($payments as $payment) {
            $appt = $payment->appointment;

            if (
                !$appt
                || $appt->services->isEmpty()
            ) {
                continue;
            }

            $svcCount =
                $appt->services->count();

            $grossAmount =
                $payment->amount;

            $totalServicePrice =
                $appt->services->sum(
                    fn ($s) =>
                        (float) (
                            $s->pivot->price_at_booking
                            ?? $s->price
                            ?? 0
                        )
                );

            $discount = 0;

            if (
                ($appt->discount_amount ?? 0) > 0
                && $totalServicePrice > 0
            ) {
                $discount =
                    $appt->discount_amount
                    / $svcCount;
            } elseif (
                ($appt->discount_percent ?? 0) > 0
                && $grossAmount > 0
            ) {
                $discount =
                    (
                        $grossAmount
                        * (
                            $appt->discount_percent
                            / 100
                        )
                    )
                    / $svcCount;
            }

            $perServiceGross =
                $grossAmount / $svcCount;

            $perServiceNet =
                $perServiceGross - $discount;

            foreach ($appt->services as $svc) {
                $name =
                    $svc->pivot->service_name
                    ?? $svc->name;

                $code =
                    $this->resolveServiceCode(
                        $name,
                        $svc->code
                    );

                if (!isset($summary[$name])) {
                    $summary[$name] = [
                        'code' =>
                            $code,

                        'name' =>
                            $name,

                        'count' =>
                            0,

                        'gross' =>
                            0,

                        'discount' =>
                            0,

                        'net' =>
                            0,
                    ];
                }

                $summary[$name]['count'] += 1;
                $summary[$name]['gross'] +=
                    $perServiceGross;

                $summary[$name]['discount'] +=
                    $discount;

                $summary[$name]['net'] +=
                    max(
                        0,
                        $perServiceNet
                    );
            }
        }

        uasort(
            $summary,
            fn ($a, $b) =>
                $a['code'] <=> $b['code']
        );

        return array_values($summary);
    }

    private function get90DayHistory(
        Carbon $startDate,
        Carbon $endDate,
        string $period
    ): array {
        $history = [];

        $intervalDays =
            max(
                1,
                $startDate->diffInDays($endDate) + 1
            );

        for ($i = 1; $i <= 3; $i++) {
            $prevStart =
                $startDate
                    ->copy()
                    ->subDays(
                        $intervalDays * $i
                    );

            $prevEnd =
                $endDate
                    ->copy()
                    ->subDays(
                        $intervalDays * $i
                    );

            $prevPayments = Payment::with([
                'appointment.services',
                'appointment.staff'
            ])
                ->whereBetween(
                    'paid_at',
                    [$prevStart, $prevEnd]
                )
                ->whereIn(
                    'type',
                    self::REVENUE_PAYMENT_TYPES
                )
                ->get();

            $prevAppts =
                Appointment::whereBetween(
                    'appointment_date',
                    [$prevStart, $prevEnd]
                )->get();

            $prevTotal =
                $prevAppts->count();

            $prevServiceRev = [];
            $prevStaffRev = [];

            foreach ($prevPayments as $p) {
                $appt = $p->appointment;

                if (!$appt) {
                    continue;
                }

                $staffName =
                    $appt->staff->full_name
                    ?? 'Unassigned';

                $prevStaffRev[$staffName] =
                    ($prevStaffRev[$staffName] ?? 0)
                    + $p->amount;

                $svcCount =
                    $appt->services->count();

                if (
                    $svcCount > 0
                    && $p->amount > 0
                ) {
                    $perSvc =
                        $p->amount / $svcCount;

                    foreach (
                        $appt->services
                        as $svc
                    ) {
                        $name =
                            $svc->pivot->service_name
                            ?? $svc->name;

                        $prevServiceRev[$name] =
                            ($prevServiceRev[$name] ?? 0)
                            + $perSvc;
                    }
                }
            }

            arsort($prevServiceRev);
            arsort($prevStaffRev);

            $history[] = [
                'label' =>
                    $prevStart->format('M d')
                    . '–'
                    . $prevEnd->format('M d'),

                'revenue' =>
                    number_format(
                        $prevPayments->sum('amount'),
                        2
                    ),

                'avgSale' =>
                    number_format(
                        $prevPayments->count() > 0
                            ? $prevPayments->sum('amount')
                                / $prevPayments->count()
                            : 0,
                        2
                    ),

                'completionRate' =>
                    $prevTotal > 0
                        ? round(
                            (
                                $prevAppts
                                    ->where(
                                        'status',
                                        'completed'
                                    )
                                    ->count()
                                / $prevTotal
                            ) * 100,
                            1
                        )
                        : 0,

                'noShowRate' =>
                    $prevTotal > 0
                        ? round(
                            (
                                $prevAppts
                                    ->where(
                                        'status',
                                        'cancelled'
                                    )
                                    ->where(
                                        'cancellation_reason',
                                        'customer_no_show'
                                    )
                                    ->count()
                                / $prevTotal
                            ) * 100,
                            1
                        )
                        : 0,

                'cancellationRate' =>
                    $prevTotal > 0
                        ? round(
                            (
                                $prevAppts
                                    ->where(
                                        'status',
                                        'cancelled'
                                    )
                                    ->count()
                                / $prevTotal
                            ) * 100,
                            1
                        )
                        : 0,

                'topService' =>
                    array_key_first(
                        $prevServiceRev
                    ) ?: 'None',

                'topStaff' =>
                    array_key_first(
                        $prevStaffRev
                    ) ?: 'None',
            ];
        }

        return $history;
    }

    private function getMonthlySpikeData(): array
    {
        $now = Carbon::now();

        $months = [];
        $bookings = [];
        $revenues = [];
        $noShows = [];
        $completions = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthStart =
                $now
                    ->copy()
                    ->subMonths($i)
                    ->startOfMonth();

            $monthEnd =
                $now
                    ->copy()
                    ->subMonths($i)
                    ->endOfMonth();

            $monthAppts =
                Appointment::whereBetween(
                    'appointment_date',
                    [$monthStart, $monthEnd]
                )->get();

            $total =
                $monthAppts->count();

            $months[] =
                $monthStart->format('M Y');

            $bookings[] =
                $total;

            $revenues[] =
                round(
                    Payment::whereBetween(
                        'paid_at',
                        [$monthStart, $monthEnd]
                    )
                        ->whereIn(
                            'type',
                            self::REVENUE_PAYMENT_TYPES
                        )
                        ->sum('amount'),
                    2
                );

            $noShows[] =
                $total > 0
                    ? round(
                        (
                            $monthAppts
                                ->where(
                                    'status',
                                    'cancelled'
                                )
                                ->where(
                                    'cancellation_reason',
                                    'customer_no_show'
                                )
                                ->count()
                            / $total
                        ) * 100,
                        1
                    )
                    : 0;

            $completions[] =
                $total > 0
                    ? round(
                        (
                            $monthAppts
                                ->where(
                                    'status',
                                    'completed'
                                )
                                ->count()
                            / $total
                        ) * 100,
                        1
                    )
                    : 0;
        }

        $peakIndex =
            array_search(
                max($bookings),
                $bookings
            );

        $recentAvg =
            array_sum(
                array_slice(
                    $bookings,
                    -3
                )
            ) / 3;

        $previousSlice =
            array_slice(
                $bookings,
                -6,
                3
            );

        $previousAvg =
            !empty($previousSlice)
                ? array_sum($previousSlice)
                    / count($previousSlice)
                : 0;

        $trend =
            $previousAvg > 0
                ? round(
                    (
                        (
                            $recentAvg
                            - $previousAvg
                        )
                        / $previousAvg
                    ) * 100,
                    1
                )
                : 0;

        $positiveBookings =
            array_filter(
                $bookings,
                fn ($v) => $v > 0
            );

        $lowMonth = 'N/A';

        if (!empty($positiveBookings)) {
            $lowest =
                min($positiveBookings);

            $lowIndex =
                array_search(
                    $lowest,
                    $bookings
                );

            $lowMonth =
                $months[$lowIndex] ?? 'N/A';
        }

        return [
            'labels' =>
                $months,

            'bookings' =>
                $bookings,

            'revenues' =>
                $revenues,

            'noShowRates' =>
                $noShows,

            'completionRates' =>
                $completions,

            'peakMonth' =>
                $months[$peakIndex] ?? 'N/A',

            'peakBookings' =>
                $bookings[$peakIndex] ?? 0,

            'lowMonth' =>
                $lowMonth,

            'trendPercent' =>
                $trend,

            'trendDirection' =>
                $trend >= 0
                    ? 'up'
                    : 'down',
        ];
    }

    private function generateFallbackInsights(
        $completionRate,
        $noShowRate,
        $cancellationRate,
        $revenueChange,
        $avgSale,
        $revPerComp,
        $deposits,
        $totalRevenue,
        $topStaff,
        $topServices,
        $totalCount,
        $uniqueCustomers,
        $conversionRate
    ): array {
        $suggestions = [];

        if ($completionRate < 60) {
            $suggestions[] = [
                'type' => 'danger',
                'icon' => '⚠️',
                'title' =>
                    'Critical: Low Completion Rate',
                'text' =>
                    "Only {$completionRate}% of appointments are being completed. Review scheduling density and confirmation workflows immediately.",
                'meta' =>
                    'Action Required',
                'bg' =>
                    'bg-red-50 border-red-500 dark:bg-red-900/20',
                'iconBg' =>
                    'bg-red-100 text-red-600 dark:bg-red-800 dark:text-red-200',
            ];
        } elseif ($completionRate < 75) {
            $suggestions[] = [
                'type' => 'warning',
                'icon' => '📉',
                'title' =>
                    'Completion Rate Below Target',
                'text' =>
                    "Your completion rate is {$completionRate}%. Consider reviewing confirmation and scheduling workflows.",
                'meta' =>
                    'Improvement Opportunity',
                'bg' =>
                    'bg-amber-50 border-amber-500 dark:bg-amber-900/20',
                'iconBg' =>
                    'bg-amber-100 text-amber-600 dark:bg-amber-800 dark:text-amber-200',
            ];
        } elseif ($completionRate >= 90) {
            $suggestions[] = [
                'type' => 'success',
                'icon' => '🏆',
                'title' =>
                    'Excellent Completion Rate',
                'text' =>
                    "Outstanding {$completionRate}% completion rate.",
                'meta' =>
                    'Keep It Up',
                'bg' =>
                    'bg-green-50 border-green-500 dark:bg-green-900/20',
                'iconBg' =>
                    'bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-200',
            ];
        }

        if ($noShowRate > 15) {
            $suggestions[] = [
                'type' => 'danger',
                'icon' => '🚫',
                'title' =>
                    'No-Show Rate Critical',
                'text' =>
                    "No-show rate at {$noShowRate}% requires attention.",
                'meta' =>
                    'Revenue Leak',
                'bg' =>
                    'bg-red-50 border-red-500 dark:bg-red-900/20',
                'iconBg' =>
                    'bg-red-100 text-red-600 dark:bg-red-800 dark:text-red-200',
            ];
        } elseif ($noShowRate > 8) {
            $suggestions[] = [
                'type' => 'warning',
                'icon' => '⏰',
                'title' =>
                    'No-Shows Above Normal',
                'text' =>
                    "{$noShowRate}% no-show rate detected.",
                'meta' =>
                    'Review Required',
                'bg' =>
                    'bg-amber-50 border-amber-500 dark:bg-amber-900/20',
                'iconBg' =>
                    'bg-amber-100 text-amber-600 dark:bg-amber-800 dark:text-amber-200',
            ];
        }

        if ($cancellationRate > 20) {
            $suggestions[] = [
                'type' => 'danger',
                'icon' => '❌',
                'title' =>
                    'High Cancellation Rate',
                'text' =>
                    "{$cancellationRate}% cancellation rate detected.",
                'meta' =>
                    'Review Required',
                'bg' =>
                    'bg-red-50 border-red-500 dark:bg-red-900/20',
                'iconBg' =>
                    'bg-red-100 text-red-600 dark:bg-red-800 dark:text-red-200',
            ];
        }

        if ($revenueChange < -20) {
            $suggestions[] = [
                'type' => 'danger',
                'icon' => '💸',
                'title' =>
                    'Revenue Declining',
                'text' =>
                    "Revenue dropped " . abs($revenueChange) . "% compared with the previous period.",
                'meta' =>
                    'Critical Alert',
                'bg' =>
                    'bg-red-50 border-red-500 dark:bg-red-900/20',
                'iconBg' =>
                    'bg-red-100 text-red-600 dark:bg-red-800 dark:text-red-200',
            ];
        } elseif ($revenueChange < -5) {
            $suggestions[] = [
                'type' => 'warning',
                'icon' => '📊',
                'title' =>
                    'Revenue Declining',
                'text' =>
                    "Revenue is down " . abs($revenueChange) . "% from the previous period.",
                'meta' =>
                    'Trend Alert',
                'bg' =>
                    'bg-amber-50 border-amber-500 dark:bg-amber-900/20',
                'iconBg' =>
                    'bg-amber-100 text-amber-600 dark:bg-amber-800 dark:text-amber-200',
            ];
        } elseif ($revenueChange > 15) {
            $suggestions[] = [
                'type' => 'success',
                'icon' => '🚀',
                'title' =>
                    'Revenue Growing',
                'text' =>
                    "Revenue increased by {$revenueChange}%.",
                'meta' =>
                    'Growth Insight',
                'bg' =>
                    'bg-green-50 border-green-500 dark:bg-green-900/20',
                'iconBg' =>
                    'bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-200',
            ];
        }

        if (empty($suggestions)) {
            $suggestions[] = [
                'type' => 'success',
                'icon' => '✅',
                'title' =>
                    'All Metrics Healthy',
                'text' =>
                    'No critical issues detected. Continue monitoring business trends.',
                'meta' =>
                    'Status OK',
                'bg' =>
                    'bg-green-50 border-green-500 dark:bg-green-900/20',
                'iconBg' =>
                    'bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-200',
            ];
        }

        usort(
            $suggestions,
            fn ($a, $b) =>
                match (true) {
                    $a['type'] === 'danger'
                        && $b['type'] !== 'danger'
                        => -1,

                    $a['type'] !== 'danger'
                        && $b['type'] === 'danger'
                        => 1,

                    $a['type'] === 'warning'
                        && $b['type'] === 'success'
                        => -1,

                    default => 0,
                }
        );

        return array_slice(
            $suggestions,
            0,
            6
        );
    }
}