<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RoomUtilizationService
{
    private const TZ = 'Asia/Manila';

    /**
     * Build unified room utilization analytics for a selected period/date range.
     *
     * Used identically by AdminController::roomsIndex(), roomReportPdf(),
     * roomReportCsv(), and roomReportXlsx() so UI and all exports match.
     */
    public function buildReport(Request $request): array
    {
        [$startDate, $endDate, $period, $periodLabel] = $this->resolveDateRange($request);
        $startStr = $startDate->toDateString();
        $endStr = $endDate->toDateString();

        $rooms = Room::with(['category', 'roomCategories'])
            ->orderBy('name')
            ->get();

        // Actual spa operating windows from BusinessHour + BusinessException
        $operatingWindows = BusinessScheduleService::getOperatingWindowsForRange($startStr, $endStr);
        $openDaysCount = 0;
        $operatingMinutesPerAvailableRoom = 0;

        foreach ($operatingWindows as $window) {
            if (!empty($window['is_open']) && !empty($window['start']) && !empty($window['end'])) {
                $openDt = Carbon::parse('2000-01-01 ' . $window['start'], self::TZ);
                $closeDt = Carbon::parse('2000-01-01 ' . $window['end'], self::TZ);
                if ($closeDt->equalTo($openDt)) {
                    continue;
                }
                $openDaysCount++;
                if ($closeDt->lessThan($openDt)) {
                    $closeDt->addDay();
                }
                $operatingMinutesPerAvailableRoom += (int) round($openDt->diffInMinutes($closeDt));
            }
        }

        // Load unique appointments in the date range that have a room assigned
        // Note: expires_at is a computed accessor on Appointment (not a DB column).
        $appointments = Appointment::query()
            ->whereNotNull('room_id')
            ->whereDate('appointment_date', '>=', $startStr)
            ->whereDate('appointment_date', '<=', $endStr)
            ->get([
                'id',
                'room_id',
                'appointment_date',
                'start_time',
                'end_time',
                'status',
                'cancellation_reason',
                'created_at',
            ])
            ->unique('id');

        $byRoom = $appointments->groupBy('room_id');
        $bufferPerBooking = AppointmentOccupancyService::PREPARATION_MINUTES;
        $now = Carbon::now(self::TZ);

        $roomRows = $rooms->map(function (Room $room) use (
            $byRoom,
            $operatingMinutesPerAvailableRoom,
            $bufferPerBooking
        ) {
            /** @var Collection<int, Appointment> $roomAppts */
            $roomAppts = $byRoom->get($room->id, collect());

            $completedCount = 0;
            $confirmedCount = 0;
            $pendingCount = 0;
            $cancelledOrNoShowCount = 0;

            $completedIntervals = [];
            $confirmedIntervals = [];
            $pendingIntervals = [];
            $scheduledIntervals = [];

            foreach ($roomAppts as $appt) {
                $isNoShow = $appt->status === 'no_show'
                    || ($appt->status === 'cancelled' && $appt->cancellation_reason === 'customer_no_show');
                $isCancelled = $appt->status === 'cancelled';

                if ($isNoShow || $isCancelled) {
                    $cancelledOrNoShowCount++;
                    continue;
                }

                // Reuse Appointment's authoritative pending expiration rule (created_at + 24h or midnight after appointment_date)
                if ($appt->status === 'pending' && $appt->is_expired) {
                    continue;
                }

                $interval = $this->appointmentInterval($appt);
                if ($interval === null) {
                    continue;
                }

                if ($appt->status === 'completed') {
                    $completedCount++;
                    $completedIntervals[] = $interval;
                    $scheduledIntervals[] = $interval;
                } elseif ($appt->status === 'confirmed') {
                    $confirmedCount++;
                    $confirmedIntervals[] = $interval;
                    $scheduledIntervals[] = $interval;
                } elseif ($appt->status === 'pending') {
                    $pendingCount++;
                    $pendingIntervals[] = $interval;
                    $scheduledIntervals[] = $interval;
                }
            }

            $completedMinutes = $this->mergedIntervalMinutes($completedIntervals);
            $confirmedMinutes = $this->mergedIntervalMinutes($confirmedIntervals);
            $pendingMinutes = $this->mergedIntervalMinutes($pendingIntervals);
            $scheduledOccupancyMinutes = $this->mergedIntervalMinutes($scheduledIntervals);

            $scheduledCount = $completedCount + $confirmedCount + $pendingCount;
            $bufferMinutes = $scheduledCount * $bufferPerBooking;

            // Current-state availability denominator: inactive or maintenance rooms
            // have 0 currently available capacity (historical maintenance is not logged).
            $isOperational = $room->is_active && $room->status !== 'maintenance';
            $availableMinutes = $isOperational ? $operatingMinutesPerAvailableRoom : 0;

            $utilizationPercent = $availableMinutes > 0
                ? round(($scheduledOccupancyMinutes / $availableMinutes) * 100, 1)
                : null;

            $fulfilledUtilizationPercent = $availableMinutes > 0
                ? round(($completedMinutes / $availableMinutes) * 100, 1)
                : null;

            return [
                'room_id' => $room->id,
                'name' => $room->name,
                'status' => $room->status,
                'is_active' => (bool) $room->is_active,
                'is_operational' => $isOperational,
                'categories' => $room->roomCategories->pluck('name')->implode(', ')
                    ?: ($room->category?->name ?? 'General'),
                'completed_count' => $completedCount,
                'confirmed_count' => $confirmedCount,
                'pending_count' => $pendingCount,
                'scheduled_count' => $scheduledCount,
                'cancelled_no_show_count' => $cancelledOrNoShowCount,
                'completed_minutes' => $completedMinutes,
                'confirmed_minutes' => $confirmedMinutes,
                'pending_minutes' => $pendingMinutes,
                'scheduled_occupancy_minutes' => $scheduledOccupancyMinutes,
                'completed_formatted' => $this->formatMinutes($completedMinutes),
                'scheduled_formatted' => $this->formatMinutes($scheduledOccupancyMinutes),
                'buffer_gaps_count' => $scheduledCount,
                'buffer_minutes' => $bufferMinutes,
                'available_minutes' => $availableMinutes,
                'available_formatted' => $availableMinutes > 0 ? $this->formatMinutes($availableMinutes) : 'N/A',
                'utilization_percent' => $utilizationPercent,
                'fulfilled_utilization_percent' => $fulfilledUtilizationPercent,
            ];
        })->values();

        $totalCompletedMinutes = (int) $roomRows->sum('completed_minutes');
        $totalConfirmedMinutes = (int) $roomRows->sum('confirmed_minutes');
        $totalPendingMinutes = (int) $roomRows->sum('pending_minutes');
        $totalScheduledMinutes = (int) $roomRows->sum('scheduled_occupancy_minutes');
        $totalBufferMinutes = (int) $roomRows->sum('buffer_minutes');
        $totalBufferGaps = (int) $roomRows->sum('buffer_gaps_count');
        $totalAvailableMinutes = (int) $roomRows->sum('available_minutes');

        // Align overall utilization numerator and denominator to the same set of
        // currently operational rooms so historical bookings on inactive/maintenance
        // rooms do not inflate operational utilization above 100%.
        $operationalRows = $roomRows->where('is_operational', true);
        $operationalScheduledMinutes = (int) $operationalRows->sum('scheduled_occupancy_minutes');
        $operationalCompletedMinutes = (int) $operationalRows->sum('completed_minutes');

        $overallUtilization = $totalAvailableMinutes > 0
            ? round(($operationalScheduledMinutes / $totalAvailableMinutes) * 100, 1)
            : null;

        $overallFulfilledUtilization = $totalAvailableMinutes > 0
            ? round(($operationalCompletedMinutes / $totalAvailableMinutes) * 100, 1)
            : null;

        $summary = [
            'total_rooms' => $rooms->count(),
            'active_rooms' => $rooms->where('is_active', true)->count(),
            'operational_rooms' => $roomRows->where('is_operational', true)->count(),
            'available_rooms' => $rooms->where('status', 'available')->count(),
            'occupied_rooms' => $rooms->where('status', 'occupied')->count(),
            'maintenance_rooms' => $rooms->where('status', 'maintenance')->count(),
            'inactive_rooms' => $rooms->where('is_active', false)->count(),
            'open_days_count' => $openDaysCount,
            'completed_count' => (int) $roomRows->sum('completed_count'),
            'confirmed_count' => (int) $roomRows->sum('confirmed_count'),
            'pending_count' => (int) $roomRows->sum('pending_count'),
            'scheduled_count' => (int) $roomRows->sum('scheduled_count'),
            'cancelled_no_show_count' => (int) $roomRows->sum('cancelled_no_show_count'),
            'completed_minutes' => $totalCompletedMinutes,
            'confirmed_minutes' => $totalConfirmedMinutes,
            'pending_minutes' => $totalPendingMinutes,
            'scheduled_occupancy_minutes' => $totalScheduledMinutes,
            'completed_formatted' => $this->formatMinutes($totalCompletedMinutes),
            'scheduled_formatted' => $this->formatMinutes($totalScheduledMinutes),
            'buffer_gaps_count' => $totalBufferGaps,
            'buffer_minutes' => $totalBufferMinutes,
            'available_minutes' => $totalAvailableMinutes,
            'available_formatted' => $totalAvailableMinutes > 0 ? $this->formatMinutes($totalAvailableMinutes) : 'N/A',
            'overall_utilization' => $overallUtilization,
            'overall_fulfilled_utilization' => $overallFulfilledUtilization,
        ];

        $bottlenecks = $this->buildDemandAndBottlenecks($roomRows, $appointments, $now);

        return [
            'period' => $period,
            'periodLabel' => $periodLabel,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dateRangeDisplay' => $startDate->format('M j, Y') . ' — ' . $endDate->format('M j, Y'),
            'rooms' => $rooms,
            'roomRows' => $roomRows,
            'summary' => $summary,
            'bottlenecks' => $bottlenecks,
        ];
    }

    private function resolveDateRange(Request $request): array
    {
        $today = Carbon::today(self::TZ);
        $period = (string) $request->input('period', 'monthly');

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $period = 'custom';
        }

        return match ($period) {
            'daily', 'today' => [
                $today->copy()->startOfDay(),
                $today->copy()->endOfDay(),
                'daily',
                'Today',
            ],
            'weekly' => [
                $today->copy()->startOfWeek(),
                $today->copy()->endOfWeek(),
                'weekly',
                'This Week',
            ],
            'yearly' => [
                $today->copy()->startOfYear(),
                $today->copy()->endOfYear(),
                'yearly',
                'This Year',
            ],
            'custom' => [
                $request->filled('start_date')
                    ? Carbon::parse($request->input('start_date'), self::TZ)->startOfDay()
                    : $today->copy()->startOfMonth(),
                $request->filled('end_date')
                    ? Carbon::parse($request->input('end_date'), self::TZ)->endOfDay()
                    : $today->copy()->endOfDay(),
                'custom',
                'Custom Range',
            ],
            default => [
                $today->copy()->startOfMonth(),
                $today->copy()->endOfMonth(),
                'monthly',
                'This Month',
            ],
        };
    }

    /**
     * @return array{0: int, 1: int}|null Unix timestamp pair [start, end]
     */
    private function appointmentInterval(Appointment $appt): ?array
    {
        if (!$appt->start_time || !$appt->end_time) {
            return null;
        }

        $dateStr = $appt->appointment_date
            ? Carbon::parse($appt->appointment_date)->toDateString()
            : '2000-01-01';

        $start = Carbon::parse($dateStr . ' ' . $appt->start_time, self::TZ);
        $end = Carbon::parse($dateStr . ' ' . $appt->end_time, self::TZ);

        if ($end->equalTo($start)) {
            return null;
        }

        if ($end->lessThan($start)) {
            $end->addDay();
        }

        return [$start->getTimestamp(), $end->getTimestamp()];
    }

    /**
     * Merge overlapping [start, end] timestamp intervals to prevent double-counting.
     *
     * @param array<int, array{0: int, 1: int}> $intervals
     */
    private function mergedIntervalMinutes(array $intervals): int
    {
        if (empty($intervals)) {
            return 0;
        }

        usort($intervals, fn (array $a, array $b) => $a[0] <=> $b[0]);

        $merged = [$intervals[0]];
        for ($i = 1, $len = count($intervals); $i < $len; $i++) {
            $lastIdx = count($merged) - 1;
            if ($intervals[$i][0] <= $merged[$lastIdx][1]) {
                $merged[$lastIdx][1] = max($merged[$lastIdx][1], $intervals[$i][1]);
            } else {
                $merged[] = $intervals[$i];
            }
        }

        $seconds = 0;
        foreach ($merged as [$s, $e]) {
            $seconds += max(0, $e - $s);
        }

        return (int) round($seconds / 60);
    }

    private function buildDemandAndBottlenecks(Collection $roomRows, Collection $appointments, Carbon $now): array
    {
        $operational = $roomRows->where('is_operational', true)->values();
        $candidates = $operational->isNotEmpty() ? $operational : $roomRows;

        $highestRoom = $candidates->sortByDesc('scheduled_occupancy_minutes')->first();
        $lowestRoom = $candidates->sortBy('scheduled_occupancy_minutes')->first();

        $activeRoomAppts = $appointments->filter(function (Appointment $appt) {
            if ($appt->status === 'cancelled' || $appt->status === 'no_show') {
                return false;
            }
            if ($appt->status === 'pending' && $appt->is_expired) {
                return false;
            }
            return in_array($appt->status, ['completed', 'confirmed', 'pending'], true);
        });

        $dayCounts = [];
        $hourCounts = [];
        foreach ($activeRoomAppts as $appt) {
            if ($appt->appointment_date) {
                $dayName = Carbon::parse($appt->appointment_date, self::TZ)->format('l');
                $dayCounts[$dayName] = ($dayCounts[$dayName] ?? 0) + 1;
            }
            if ($appt->start_time) {
                $hourLabel = Carbon::parse($appt->start_time, self::TZ)->format('g:00 A');
                $hourCounts[$hourLabel] = ($hourCounts[$hourLabel] ?? 0) + 1;
            }
        }

        arsort($dayCounts);
        arsort($hourCounts);

        $peakDay = !empty($dayCounts) ? array_key_first($dayCounts) : null;
        $peakHour = !empty($hourCounts) ? array_key_first($hourCounts) : null;

        $highUtilizationRooms = $roomRows
            ->filter(fn (array $r) => ($r['utilization_percent'] ?? 0) >= 75.0)
            ->pluck('name')
            ->values()
            ->all();

        return [
            'highest_room' => $highestRoom,
            'lowest_room' => $lowestRoom,
            'peak_day' => $peakDay,
            'peak_day_count' => $peakDay ? $dayCounts[$peakDay] : 0,
            'peak_hour' => $peakHour,
            'peak_hour_count' => $peakHour ? $hourCounts[$peakHour] : 0,
            'high_utilization_rooms' => $highUtilizationRooms,
        ];
    }

    public function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $rem = $minutes % 60;

        if ($hours > 0 && $rem > 0) {
            return "{$hours}h {$rem}m";
        }
        if ($hours > 0) {
            return "{$hours}h 0m";
        }

        return "{$rem}m";
    }
}
