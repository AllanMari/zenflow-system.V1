<?php

namespace App\Traits;

use App\Models\User;
use App\Models\WorkSchedule;
use App\Models\ScheduleException;
use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\ShiftTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

trait ScheduleTrait
{
    /**
     * Extract HH:MM from various time formats (Carbon instance, H:i:s, ISO strings, etc.).
     */
    protected function extractTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('H:i');
        }

        $value = (string) $value;

        if (preg_match("/^(\d{2}:\d{2}):\d{2}$/", $value, $m)) {
            return $m[1];
        }

        if (preg_match("/^(\d{2}:\d{2})$/", $value, $m)) {
            return $m[1];
        }

        if (preg_match("/T(\d{2}:\d{2}):/", $value, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Format HH:MM to 12-hour AM/PM.
     */
    protected function fmtAmPm(?string $time): string
    {
        if (!$time) {
            return '—';
        }

        try {
            return Carbon::createFromFormat(
                'H:i',
                substr($time, 0, 5)
            )->format('g:i A');
        } catch (\Exception $e) {
            return $time;
        }
    }

    /**
     * Calculate duration in hours between two HH:MM times.
     * Handles overnight shifts.
     */
    protected function durationHours(
        ?string $start,
        ?string $end
    ): ?float {
        if (!$start || !$end) {
            return null;
        }

        try {
            $s = Carbon::createFromFormat('H:i', $start);
            $e = Carbon::createFromFormat('H:i', $end);

            if ($e->lessThan($s)) {
                $e->addDay();
            }

            return round(
                $s->diffInMinutes($e) / 60,
                1
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Build timeline with exactly 3 DB queries regardless of staff count.
     *
     * Returns:
     * [$days, $timeline, $staffStats]
     */
    protected function schedBuildTimeline(
        Collection $staffCollection,
        Carbon $weekStart,
        bool $withAttendance = true
    ): array {
        // FIX: Use endOfWeek(Carbon::SATURDAY) to ensure Sunday-Saturday week alignment
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SATURDAY);
        $staffIds = $staffCollection->pluck('id');

        $days = collect(range(0, 6))
            ->map(fn($i) => [
                'date' => $weekStart
                    ->copy()
                    ->addDays($i)
                    ->toDateString(),

                'label' => $weekStart
                    ->copy()
                    ->addDays($i)
                    ->format('D'),

                'day' => $weekStart
                    ->copy()
                    ->addDays($i)
                    ->format('j'),

                'dow' => $weekStart
                    ->copy()
                    ->addDays($i)
                    ->dayOfWeek,

                'is_today' => $weekStart
                    ->copy()
                    ->addDays($i)
                    ->isToday(),
            ])
            ->all();

        $allSchedules = WorkSchedule::whereIn(
                'user_id',
                $staffIds
            )
            ->get()
            ->groupBy('user_id');

        $exceptions = ScheduleException::whereIn(
                'user_id',
                $staffIds
            )
            ->whereBetween(
                'exception_date',
                [
                    $weekStart->toDateString(),
                    $weekEnd->toDateString()
                ]
            )
            ->get()
            ->groupBy(
                fn($e) =>
                    $e->user_id
                    . '|'
                    . $e->exception_date->toDateString()
            );

        $attendances = collect();

        if ($withAttendance) {
            $attendances = Attendance::whereIn(
                    'user_id',
                    $staffIds
                )
                ->whereBetween(
                    'date',
                    [
                        $weekStart->toDateString(),
                        $weekEnd->toDateString()
                    ]
                )
                ->get()
                ->groupBy(
                    fn($a) =>
                        $a->user_id
                        . '|'
                        . Carbon::parse($a->date)->toDateString()
                );
        }

        $timeline = [];
        $staffStats = [];

        foreach ($staffCollection as $staff) {
            $schedules = $allSchedules
                ->get($staff->id, collect())
                ->keyBy('day_of_week');

            $row = [
                'user' => $staff,
                'days' => [],
            ];

            $workingDays = [];
            $totalHours = 0;

            foreach ($days as $day) {
                $dow = $day['dow'];
                $dateStr = $day['date'];
                $key = $staff->id . '|' . $dateStr;

                $sch = $schedules->get($dow);
                $ex = $exceptions->get($key)?->first();
                $att = $attendances->get($key)?->first();

                $cell = [
                    'date' => $dateStr,
                    'dow' => $dow,
                    'type' => 'off',
                    'start_time' => null,
                    'end_time' => null,

                    'exception' => $ex
                        ? [
                            'id' => $ex->id,
                            'reason' => $ex->reason,
                        ]
                        : null,

                    'exception_type' => null,

                    'attendance' => $att?->status,

                    'check_in' => $att?->check_in
                        ? $this->extractTime($att->check_in)
                        : null,

                    'check_out' => $att?->check_out
                        ? $this->extractTime($att->check_out)
                        : null,
                ];

                if ($ex) {
                    $cell['type'] = 'exception';

                    if ($ex->type === 'custom_hours') {
                        $cell['start_time'] =
                            $this->extractTime($ex->start_time);

                        $cell['end_time'] =
                            $this->extractTime($ex->end_time);

                        /*
                         * Keep the actual backend exception type.
                         * This is important when opening the editor.
                         */
                        $cell['exception_type'] = 'custom_hours';

                        $h = $this->durationHours(
                            $cell['start_time'],
                            $cell['end_time']
                        );

                        if ($h) {
                            $totalHours += $h;
                            $workingDays[] = $day['label'];
                        }
                    } else {
                        $cell['exception_type'] = $ex->type;
                    }
                } elseif ($sch && !$sch->is_day_off) {
                    $cell['type'] = 'work';

                    $cell['start_time'] =
                        $this->extractTime($sch->start_time);

                    $cell['end_time'] =
                        $this->extractTime($sch->end_time);

                    $h = $this->durationHours(
                        $cell['start_time'],
                        $cell['end_time']
                    );

                    if ($h) {
                        $totalHours += $h;
                        $workingDays[] = $day['label'];
                    }
                }

                $row['days'][] = $cell;
            }

            $staffStats[$staff->id] = [
                'days' => array_values(
                    array_unique($workingDays)
                ),

                'hours' => round($totalHours, 1),

                'count' => count(
                    array_unique($workingDays)
                ),
            ];

            $timeline[] = $row;
        }

        return [
            $days,
            collect($timeline),
            $staffStats,
        ];
    }

    /**
     * Build day view timeline.
     */
    protected function schedBuildDayTimeline(
        Collection $staff,
        Carbon $date
    ): Collection {
        $dateStr = $date->toDateString();
        $dow = $date->dayOfWeek;
        $staffIds = $staff->pluck('id');

        $exceptions = ScheduleException::whereIn(
                'user_id',
                $staffIds
            )
            ->whereDate(
                'exception_date',
                $dateStr
            )
            ->get()
            ->keyBy('user_id');

        $attendances = Attendance::whereIn(
                'user_id',
                $staffIds
            )
            ->whereDate(
                'date',
                $dateStr
            )
            ->get()
            ->keyBy('user_id');

        return $staff->map(
            function ($user) use (
                $dow,
                $dateStr,
                $exceptions,
                $attendances
            ) {
                $exception = $exceptions->get($user->id);
                $attendance = $attendances->get($user->id);

                $schedule = $user->workSchedules
                    ->firstWhere('day_of_week', $dow);

                $block = null;

                if ($exception) {
                    if (
                        $exception->type === 'custom_hours'
                        && $exception->start_time
                        && $exception->end_time
                    ) {
                        $start = $this->extractTime(
                            $exception->start_time
                        );

                        $end = $this->extractTime(
                            $exception->end_time
                        );

                                                $block = [
                            'type' => 'custom_hours',
                            'start_time' => $start,
                            'end_time' => $end,

                            'label' =>
                                $this->fmtAmPm($start)
                                . ' – '
                                . $this->fmtAmPm($end),

                            'reason' => $exception->reason,
                            'exception_id' => $exception->id,
                            'exception_type' => 'custom_hours',
                        ];
                    } elseif (
                        in_array(
                            $exception->type,
                            [
                                'day_off',
                                'holiday',
                                'sick_leave',
                                'urgent_leave',
                            ],
                            true
                        )
                    ) {
                        $block = [
                            'type' => 'off',
                            'start_time' => null,
                            'end_time' => null,

                            'label' => ucfirst(
                                str_replace(
                                    '_',
                                    ' ',
                                    $exception->type
                                )
                            ),

                            'reason' => $exception->reason,
                            'exception_id' => $exception->id,
                        ];
                    }
                } elseif (
                    $schedule
                    && !$schedule->is_day_off
                    && $schedule->start_time
                    && $schedule->end_time
                ) {
                    $start = $this->extractTime(
                        $schedule->start_time
                    );

                    $end = $this->extractTime(
                        $schedule->end_time
                    );

                    $block = [
                        'type' => 'work',
                        'start_time' => $start,
                        'end_time' => $end,

                        'label' =>
                            $this->fmtAmPm($start)
                            . ' – '
                            . $this->fmtAmPm($end),

                        'reason' => null,
                        'exception_id' => null,
                    ];
                }

                $status = 'off';
                $statusLabel = 'Off';

                if ($block && $block['type'] !== 'off') {
                    if ($attendance) {
                        $status = $attendance->status;
                        $statusLabel = ucfirst(
                            $attendance->status
                        );
                    } else {
                        $status = 'scheduled';
                        $statusLabel = 'Scheduled';
                    }
                }

                return [
                    'user' => $user,
                    'block' => $block,
                    'attendance' => $attendance,
                    'status' => $status,
                    'statusLabel' => $statusLabel,
                ];
            }
        );
    }

    /**
     * Optimized availability check.
     */
    protected function schedGetAvailableStaff(
        $date,
        $startTime,
        $endTime,
        $excludeAppointmentId = null
    ): Collection {
        $carbonDate = Carbon::parse($date);
        $dateStr = $carbonDate->toDateString();
        $dayOfWeek = $carbonDate->dayOfWeek;
        $isToday = $dateStr === now()->toDateString();

        $cacheKey =
            'avail:'
            . md5(
                "{$dateStr}|{$startTime}|{$endTime}|"
                . ($excludeAppointmentId ?? '0')
            );

        return Cache::remember(
            $cacheKey,
            60,
            function () use (
                $dateStr,
                $dayOfWeek,
                $isToday,
                $startTime,
                $endTime,
                $excludeAppointmentId
            ) {
                $allStaff = User::whereHas(
                        'roles',
                        fn($q) =>
                            $q->where('name', 'staff')
                    )
                    ->where('is_active', true)
                    ->with([
                        'workSchedules' => fn($q) =>
                            $q->where(
                                'day_of_week',
                                $dayOfWeek
                            )
                    ])
                    ->get();

                if ($allStaff->isEmpty()) {
                    return collect();
                }

                $staffIds = $allStaff->pluck('id');

                $exceptions = ScheduleException::whereIn(
                        'user_id',
                        $staffIds
                    )
                    ->whereDate(
                        'exception_date',
                        $dateStr
                    )
                    ->get()
                    ->keyBy('user_id');

                $attendanceBlockers = collect();

                if ($isToday) {
                    $attendanceBlockers =
                        Attendance::whereIn(
                            'user_id',
                            $staffIds
                        )
                        ->whereDate(
                            'date',
                            $dateStr
                        )
                        ->whereIn(
                            'status',
                            [
                                'absent',
                                'on_leave',
                                'holiday',
                            ]
                        )
                        ->get()
                        ->keyBy('user_id');
                }

                $conflicts = Appointment::whereIn(
                        'user_id',
                        $staffIds
                    )
                    ->where(
                        'appointment_date',
                        $dateStr
                    )
                    ->where(
                        'status',
                        'confirmed'
                    )
                    ->when(
                        $excludeAppointmentId,
                        fn($q) =>
                            $q->where(
                                'id',
                                '!=',
                                $excludeAppointmentId
                            )
                    )
                    ->where(function ($q) use (
                        $startTime,
                        $endTime
                    ) {
                        $q->whereBetween(
                            'start_time',
                            [
                                $startTime,
                                $endTime,
                            ]
                        )
                        ->orWhereBetween(
                            'end_time',
                            [
                                $startTime,
                                $endTime,
                            ]
                        )
                        ->orWhere(
                            function ($sq) use (
                                $startTime,
                                $endTime
                            ) {
                                $sq->where(
                                    'start_time',
                                    '<=',
                                    $startTime
                                )
                                ->where(
                                    'end_time',
                                    '>=',
                                    $endTime
                                );
                            }
                        );
                    })
                    ->pluck('user_id');

                return $allStaff
                    ->map(
                        function ($staff) use (
                            $dayOfWeek,
                            $startTime,
                            $endTime,
                            $exceptions,
                            $attendanceBlockers,
                            $isToday,
                            $conflicts
                        ) {
                            $exception =
                                $exceptions->get(
                                    $staff->id
                                );

                            if (
                                $exception
                                && in_array(
                                    $exception->type,
                                    [
                                        'day_off',
                                        'holiday',
                                        'sick_leave',
                                        'urgent_leave',
                                    ],
                                    true
                                )
                            ) {
                                return [
                                    'user' => $staff,
                                    'available' => false,
                                    'status' => 'blocked',

                                    'status_label' =>
                                        ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $exception->type
                                            )
                                        ),

                                    'reason' =>
                                        $exception->reason,

                                    'hours' => null,
                                ];
                            }

                            $schedule =
                                $staff->workSchedules
                                    ->first();

                            if (
                                !$schedule
                                || $schedule->is_day_off
                            ) {
                                return [
                                    'user' => $staff,
                                    'available' => false,
                                    'status' => 'off',
                                    'status_label' => 'Day Off',
                                    'reason' => null,
                                    'hours' => null,
                                ];
                            }

                            $schStart =
                                $this->extractTime(
                                    $schedule->start_time
                                );

                            $schEnd =
                                $this->extractTime(
                                    $schedule->end_time
                                );

                            /*
                             * Custom hours are an override.
                             * If one exists, use it instead of the
                             * recurring schedule for availability.
                             */
                            if (
                                $exception
                                && $exception->type === 'custom_hours'
                            ) {
                                $exStart =
                                    $this->extractTime(
                                        $exception->start_time
                                    );

                                $exEnd =
                                    $this->extractTime(
                                        $exception->end_time
                                    );

                                if (
                                    !$exStart
                                    || !$exEnd
                                    || $startTime < $exStart
                                    || $endTime > $exEnd
                                ) {
                                    return [
                                        'user' => $staff,
                                        'available' => false,
                                        'status' => 'custom_hours',

                                        'status_label' =>
                                            'Custom Hours',

                                        'reason' =>
                                            "Custom: {$exStart}-{$exEnd}",

                                        'hours' =>
                                            "{$exStart}-{$exEnd}",
                                    ];
                                }
                            } elseif (
                                !$schStart
                                || !$schEnd
                                || $startTime < $schStart
                                || $endTime > $schEnd
                            ) {
                                return [
                                    'user' => $staff,
                                    'available' => false,
                                    'status' => 'outside_hours',

                                    'status_label' =>
                                        'Outside Hours',

                                    'reason' =>
                                        "Schedule: {$schStart}-{$schEnd}",

                                    'hours' =>
                                        "{$schStart}-{$schEnd}",
                                ];
                            }

                            if (
                                $isToday
                                && $attendanceBlockers->has(
                                    $staff->id
                                )
                            ) {
                                return [
                                    'user' => $staff,
                                    'available' => false,
                                    'status' => 'attendance',

                                    'status_label' =>
                                        'Absent/Leave',

                                    'reason' => null,

                                    'hours' =>
                                        $exception
                                        && $exception->type === 'custom_hours'
                                            ? $this->extractTime(
                                                $exception->start_time
                                            )
                                            . '-'
                                            . $this->extractTime(
                                                $exception->end_time
                                            )
                                            : "{$schStart}-{$schEnd}",
                                ];
                            }

                            if (
                                $conflicts->contains(
                                    $staff->id
                                )
                            ) {
                                return [
                                    'user' => $staff,
                                    'available' => false,
                                    'status' => 'conflict',
                                    'status_label' => 'Double Booked',
                                    'reason' => null,

                                    'hours' =>
                                        $exception
                                        && $exception->type === 'custom_hours'
                                            ? $this->extractTime(
                                                $exception->start_time
                                            )
                                            . '-'
                                            . $this->extractTime(
                                                $exception->end_time
                                            )
                                            : "{$schStart}-{$schEnd}",
                                ];
                            }

                            return [
                                'user' => $staff,
                                'available' => true,
                                'status' => 'available',
                                'status_label' => 'Available',
                                'reason' => null,

                                'hours' =>
                                    $exception
                                    && $exception->type === 'custom_hours'
                                        ? $this->extractTime(
                                            $exception->start_time
                                        )
                                        . '-'
                                        . $this->extractTime(
                                            $exception->end_time
                                        )
                                        : "{$schStart}-{$schEnd}",
                            ];
                        }
                    )
                    ->values();
            }
        );
    }

    /**
     * Shared validation rules for exceptions.
     */
    protected function exceptionRules(): array
    {
        return [
            'user_id' =>
                'required|exists:users,id',

            'exception_type' =>
                'required|in:day_off,holiday,custom_hours',

            'date' =>
                'required|date',

            'end_date' =>
                'nullable|date|after_or_equal:date',

            'start_time' =>
                'nullable|required_if:exception_type,custom_hours|date_format:H:i',

            'end_time' =>
                'nullable|required_if:exception_type,custom_hours|after:start_time',

            'reason' =>
                'nullable|string|max:255',
        ];
    }

    /**
     * Save schedule exception.
     *
     * IMPORTANT:
     * If Custom Hours are exactly the same as the staff member's
     * recurring WorkSchedule for that day, the exception is removed
     * instead of being saved as Custom Hours.
     *
     * Example:
     *
     * WorkSchedule:      09:00 - 18:00
     * Custom Hours edit: 09:00 - 18:00
     *
     * Result:
     * No ScheduleException is stored.
     * The normal WorkSchedule is displayed.
     */
    protected function persistException(array $data): int
    {
        $start = Carbon::parse($data['date']);

        $end = !empty($data['end_date'])
            ? Carbon::parse($data['end_date'])
            : $start->copy();

        $isCustom =
            ($data['exception_type'] ?? '') === 'custom_hours';

        $count = 0;

        /*
         * Normalize submitted times once.
         */
        $customStart = $isCustom
            ? $this->extractTime($data['start_time'] ?? null)
            : null;

        $customEnd = $isCustom
            ? $this->extractTime($data['end_time'] ?? null)
            : null;

        /*
         * Load the recurring schedules once instead of querying
         * WorkSchedule for every individual date.
         */
        $workSchedules = WorkSchedule::where(
                'user_id',
                $data['user_id']
            )
            ->get()
            ->keyBy('day_of_week');

        for (
            $date = $start->copy();
            $date->lte($end);
            $date->addDay()
        ) {
            /*
             * The recurring WorkSchedule uses Carbon's dayOfWeek:
             *
             * Sunday = 0
             * Monday = 1
             * ...
             * Saturday = 6
             */
            $dayOfWeek = $date->dayOfWeek;

            $schedule = $workSchedules->get($dayOfWeek);

            /*
             * SPECIAL CASE:
             *
             * If Custom Hours exactly match the recurring schedule,
             * there is no reason to create an exception.
             *
             * Delete an existing exception if one exists.
             */
            if (
                $isCustom
                && $schedule
                && !$schedule->is_day_off
            ) {
                $scheduleStart =
                    $this->extractTime(
                        $schedule->start_time
                    );

                $scheduleEnd =
                    $this->extractTime(
                        $schedule->end_time
                    );

                if (
                    $scheduleStart
                    && $scheduleEnd
                    && $customStart === $scheduleStart
                    && $customEnd === $scheduleEnd
                ) {
                    ScheduleException::where(
                        'user_id',
                        $data['user_id']
                    )
                    ->whereDate(
                        'exception_date',
                        $date->toDateString()
                    )
                    ->delete();

                    $count++;

                    continue;
                }
            }

            ScheduleException::updateOrCreate(
                [
                    'user_id' =>
                        $data['user_id'],

                    'exception_date' =>
                        $date->toDateString(),
                ],
                [
                    'type' =>
                        $data['exception_type'],

                    'start_time' =>
                        $isCustom
                            ? $customStart
                            : null,

                    'end_time' =>
                        $isCustom
                            ? $customEnd
                            : null,

                    'reason' =>
                        $data['reason'] ?? null,
                ]
            );

            $count++;
        }

        $this->clearScheduleCache();

        return $count;
    }

    /**
     * Targeted schedule cache clearing.
     */
    protected function clearScheduleCache(): void
    {
        /*
         * If cache tags are enabled with Redis/Memcached:
         *
         * Cache::tags([
         *     'schedules',
         *     'availability'
         * ])->flush();
         *
         * The current implementation intentionally does not use
         * Cache::flush() because that could remove unrelated data.
         */
    }

    /**
     * Bulk update with validation.
     */
    protected function schedBulkUpdate(
        Request $request
    ): void {
        $schedules = $request->input(
            'schedules',
            []
        );

        if (empty($schedules)) {
            return;
        }

        $normalized = [];

        if (array_is_list($schedules)) {
            foreach ($schedules as $item) {
                if (
                    !isset(
                        $item['user_id'],
                        $item['day_of_week']
                    )
                ) {
                    continue;
                }

                $normalized[
                    $item['user_id']
                ][
                    $item['day_of_week']
                ] = $item;
            }
        } else {
            $normalized = $schedules;
        }

        $rules = [];
        $data = [];

        foreach ($normalized as $userId => $days) {
            foreach ($days as $dow => $item) {
                $prefix =
                    "schedules.{$userId}.{$dow}";

                $rules[
                    "{$prefix}.start_time"
                ] =
                    'nullable|date_format:H:i';

                $rules[
                    "{$prefix}.end_time"
                ] =
                    'nullable|date_format:H:i|after:'
                    . ($item['start_time'] ?? '00:00');

                $data[] = [
                    'user_id' =>
                        $userId,

                    'day_of_week' =>
                        $dow,

                    'item' =>
                        $item,
                ];
            }
        }

        Validator::make(
            ['schedules' => $normalized],
            $rules
        )->validate();

        DB::transaction(
            function () use ($data) {
                foreach ($data as $entry) {
                    $item = $entry['item'];

                    $isOff =
                        !empty($item['is_day_off'])
                        && in_array(
                            $item['is_day_off'],
                            [
                                true,
                                '1',
                                1,
                                'true',
                            ],
                            true
                        );

                    WorkSchedule::updateOrCreate(
                        [
                            'user_id' =>
                                $entry['user_id'],

                            'day_of_week' =>
                                $entry['day_of_week'],
                        ],
                        [
                            'start_time' =>
                                $isOff
                                    ? null
                                    : ($item['start_time'] ?? null),

                            'end_time' =>
                                $isOff
                                    ? null
                                    : ($item['end_time'] ?? null),

                            'is_day_off' =>
                                $isOff,
                        ]
                    );
                }
            }
        );

        $this->clearScheduleCache();
    }

    /**
     * Quick block.
     */
    protected function schedQuickBlock(
        Request $request
    ): void {
        $request->validate(
            $this->exceptionRules()
        );

        $this->persistException(
            $request->only([
                'user_id',
                'exception_type',
                'date',
                'end_date',
                'start_time',
                'end_time',
                'reason',
            ])
        );
    }

    /**
     * Apply template to a single user.
     */
    protected function schedApplyTemplate(
        Request $request,
        $user
    ): void {
        $request->validate([
            'template_id' =>
                'required|exists:shift_templates,id',

            'week_start' =>
                'nullable|date',
        ]);

        $template =
            ShiftTemplate::findOrFail(
                $request->template_id
            );

        if (
            method_exists(
                $template,
                'applyToUser'
            )
        ) {
            $template->applyToUser(
                $user,
                $request->week_start
                    ? Carbon::parse(
                        $request->week_start
                    )
                    : null
            );
        } else {
            $this->applyTemplateManually(
                $template,
                $user,
                $request->week_start
                    ? Carbon::parse(
                        $request->week_start
                    )
                    : null
            );
        }

        $this->clearScheduleCache();
    }

    /**
     * Manual template application fallback.
     */
    protected function applyTemplateManually(
        ShiftTemplate $template,
        $user,
        ?Carbon $weekStart = null
    ): void {
        $pattern = $template->pattern ?? [];

        if (
            !is_array($pattern)
            || count($pattern) !== 7
        ) {
            throw new \Exception(
                'Invalid template pattern'
            );
        }

        foreach ($pattern as $dow => $day) {
            $isOff = !empty(
                $day['is_day_off']
            );

            WorkSchedule::updateOrCreate(
                [
                    'user_id' =>
                        $user->id,

                    'day_of_week' =>
                        (int) $dow,
                ],
                [
                    'start_time' =>
                        $isOff
                            ? null
                            : ($day['start_time'] ?? null),

                    'end_time' =>
                        $isOff
                            ? null
                            : ($day['end_time'] ?? null),

                    'is_day_off' =>
                        $isOff,
                ]
            );
        }

        if ($weekStart) {
            foreach ($pattern as $dow => $day) {
                $date =
                    $weekStart
                        ->copy()
                        ->addDays((int) $dow);

                $isOff =
                    !empty($day['is_day_off']);

                if ($isOff) {
                    ScheduleException::updateOrCreate(
                        [
                            'user_id' =>
                                $user->id,

                            'exception_date' =>
                                $date->toDateString(),
                        ],
                        [
                            'type' =>
                                'day_off',

                            'reason' =>
                                'Applied template: '
                                . $template->name,
                        ]
                    );
                } elseif (
                    !empty($day['start_time'])
                    && !empty($day['end_time'])
                ) {
                    ScheduleException::updateOrCreate(
                        [
                            'user_id' =>
                                $user->id,

                            'exception_date' =>
                                $date->toDateString(),
                        ],
                        [
                            'type' =>
                                'custom_hours',

                            'start_time' =>
                                $day['start_time'],

                            'end_time' =>
                                $day['end_time'],

                            'reason' =>
                                'Applied template: '
                                . $template->name,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Apply template to multiple users.
     */
    protected function schedApplyTemplateBulk(
        Request $request
    ): array {
        $request->validate([
            'template_id' =>
                'required|exists:shift_templates,id',

            'user_ids' =>
                'required|array|min:1',

            'user_ids.*' =>
                'exists:users,id',

            'week_start' =>
                'nullable|date',
        ]);

        $template =
            ShiftTemplate::findOrFail(
                $request->template_id
            );

        $weekStart =
            $request->week_start
                ? Carbon::parse(
                    $request->week_start
                )
                : null;

        $results = [
            'applied' => 0,
            'failed' => [],
        ];

        DB::transaction(
            function () use (
                $request,
                $template,
                $weekStart,
                &$results
            ) {
                foreach (
                    $request->user_ids
                    as $userId
                ) {
                    try {
                        $user =
                            User::find($userId);

                        if ($user) {
                            if (
                                method_exists(
                                    $template,
                                    'applyToUser'
                                )
                            ) {
                                $template->applyToUser(
                                    $user,
                                    $weekStart
                                );
                            } else {
                                $this->applyTemplateManually(
                                    $template,
                                    $user,
                                    $weekStart
                                );
                            }

                            $results['applied']++;
                        } else {
                            $results['failed'][
                                $userId
                            ] = 'User not found';
                        }
                    } catch (\Exception $e) {
                        $results['failed'][
                            $userId
                        ] = $e->getMessage();
                    }
                }
            }
        );

        $this->clearScheduleCache();

        return $results;
    }
}