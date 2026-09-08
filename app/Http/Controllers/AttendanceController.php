<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\ScheduleException;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Schedule exception types that mean the staff member
     * is NOT working today.
     */
    private const LEAVE_TYPES = [
        'day_off',
        'holiday',
        'sick_leave',
        'urgent_leave',
    ];

    /**
     * Staff can be considered PRESENT up to this many minutes
     * after their scheduled start time.
     *
     * Example:
     * 6:00 AM start
     * 6:00 AM - 6:30 AM = Present
     * After 6:30 AM = Late
     */
    private const ATTENDANCE_TOLERANCE_MINUTES = 30;

    // ============================================================
    // Daily operational page (front-desk view)
    // ============================================================
    public function today()
    {
        $user = auth()->user();

        abort_unless(
            $user->roles->contains('name', 'admin')
            || $user->roles->contains('name', 'receptionist'),
            403
        );

        $today = Carbon::today();
        $dayOfWeek = $today->dayOfWeek;

        $staff = User::whereHas(
                'roles',
                fn ($q) => $q->where('name', 'staff')
            )
            ->where('is_active', true)
            ->with('workSchedules')
            ->orderBy('first_name')
            ->get();

        $attendances = Attendance::whereDate('date', $today)
            ->get()
            ->keyBy('user_id');

        $exceptions = ScheduleException::whereDate('exception_date', $today)
            ->get()
            ->keyBy('user_id');

        $isAdmin = $user->roles->contains('name', 'admin');

        $canMark = $isAdmin
            || (
                $user->roles->contains('name', 'receptionist')
                && (bool) $user->can_mark_attendance
            );

        /*
         * Single source of truth for the page.
         * Both desktop and mobile layouts consume this state.
         */
        $initialState = [];

        foreach ($staff as $member) {
            $att = $attendances->get($member->id);
            $exc = $exceptions->get($member->id);

            $schedule = $member->workSchedules
                ->firstWhere('day_of_week', $dayOfWeek);

            $isOff = $exc
                && in_array($exc->type, self::LEAVE_TYPES);

            $offLabel = null;
            $shift = null;

            if ($isOff) {
                $display = 'off_leave';

                $offLabel = $exc->type === 'holiday'
                    ? 'Holiday'
                    : (
                        $exc->type === 'day_off'
                            ? 'Day Off'
                            : ucwords(
                                str_replace('_', ' ', $exc->type)
                            )
                    );
            } else {
                $customHours = $exc
                    && $exc->type === 'custom_hours'
                    && $exc->start_time;

                $working = $schedule
                    && !$schedule->is_day_off
                    && $schedule->start_time;

                if ($att && $att->check_out) {
                    $display = 'completed';
                } elseif ($att && $att->check_in) {
                    $display = $att->status === 'late'
                        ? 'late'
                        : 'checked_in';
                } elseif ($customHours || $working) {
                    $display = 'pending';
                } else {
                    $display = 'not_scheduled';
                }

                if ($customHours) {
                    $shift = Carbon::parse($exc->start_time)->format('g:i A')
                        . ' – '
                        . Carbon::parse($exc->end_time)->format('g:i A');
                } elseif ($working) {
                    $shift = Carbon::parse($schedule->start_time)->format('g:i A')
                        . ' – '
                        . Carbon::parse($schedule->end_time)->format('g:i A');
                }
            }

            $initialState[$member->id] = [
                'id' => $member->id,

                'name' => trim(
                    $member->first_name . ' ' . $member->last_name
                ),

                'initials' => strtoupper(
                    substr($member->first_name, 0, 1)
                    . substr($member->last_name, 0, 1)
                ),

                'username' => $member->username,

                'shift' => $shift,

                'display_status' => $display,

                'off' => $isOff,

                'off_label' => $offLabel,

                'reason' => $exc->reason ?? null,

                'check_in' => $att && $att->check_in
                    ? Carbon::parse($att->check_in)->format('g:i A')
                    : null,

                'check_out' => $att && $att->check_out
                    ? Carbon::parse($att->check_out)->format('g:i A')
                    : null,
            ];
        }

        return view('shared.attendance', [
            'today' => $today,
            'initialState' => $initialState,
            'isAdmin' => $isAdmin,
            'canMark' => $canMark,
        ]);
    }

    // ============================================================
    // Immediate actions
    // Server timestamp = source of truth
    // ============================================================
    public function quickCheckIn(Request $request, User $staff)
    {
        $this->authorizeMarking();

        $today = Carbon::today();
        $now = now();

        $exception = $this->todayException(
            $staff->id,
            $today
        );

        // --------------------------------------------------------
        // 1. Block leave / day-off / holiday
        // --------------------------------------------------------
        if (
            $exception
            && in_array($exception->type, self::LEAVE_TYPES)
        ) {
            return response()->json([
                'message' => $staff->first_name
                    . ' is on '
                    . strtolower(
                        str_replace('_', ' ', $exception->type)
                    )
                    . ' today and cannot check in.',
            ], 403);
        }

        // --------------------------------------------------------
        // 2. Determine today's working hours
        // --------------------------------------------------------
        $startTime = null;
        $endTime = null;

        /*
         * Custom hours override the normal work schedule.
         */
        if (
            $exception
            && $exception->type === 'custom_hours'
            && $exception->start_time
            && $exception->end_time
        ) {
            $startTime = $exception->start_time;
            $endTime = $exception->end_time;
        } else {
            $schedule = WorkSchedule::where(
                    'user_id',
                    $staff->id
                )
                ->where(
                    'day_of_week',
                    $today->dayOfWeek
                )
                ->first();

            if (
                !$schedule
                || $schedule->is_day_off
                || !$schedule->start_time
                || !$schedule->end_time
            ) {
                return response()->json([
                    'message' => $staff->first_name
                        . ' is not scheduled to work today.',
                ], 403);
            }

            $startTime = $schedule->start_time;
            $endTime = $schedule->end_time;
        }

        // --------------------------------------------------------
        // 3. Build today's shift start/end timestamps
        // --------------------------------------------------------
        $shiftStart = Carbon::parse(
            $today->toDateString()
            . ' '
            . $startTime
        );

        $shiftEnd = Carbon::parse(
            $today->toDateString()
            . ' '
            . $endTime
        );

        // --------------------------------------------------------
        // 4. EARLY CHECK-IN IS ALLOWED
        // --------------------------------------------------------
        /*
         * We intentionally DO NOT reject check-ins before
         * the scheduled start time.
         *
         * Example:
         *
         * Shift: 6:00 AM - 9:00 PM
         * Check-in: 5:30 AM
         *
         * Result: PRESENT
         *
         * This is useful for staff who arrive early to prepare
         * the spa, treatment rooms, equipment, etc.
         */

        // --------------------------------------------------------
        // 5. Block quick check-in AFTER the shift ends
        // --------------------------------------------------------
        if ($now->greaterThan($shiftEnd)) {
            return response()->json([
                'message' => $staff->first_name
                    . '\'s shift ended at '
                    . $shiftEnd->format('g:i A')
                    . '. Quick check-in is no longer available. '
                    . 'Use manual correction if attendance needs '
                    . 'to be recorded.',
            ], 422);
        }

        // --------------------------------------------------------
        // 6. Prevent duplicate check-in
        // --------------------------------------------------------
        $attendance = Attendance::where(
                'user_id',
                $staff->id
            )
            ->whereDate('date', $today)
            ->first();

        if ($attendance && $attendance->check_in) {
            return response()->json([
                'message' => $staff->first_name
                    . ' already checked in at '
                    . Carbon::parse(
                        $attendance->check_in
                    )->format('g:i A')
                    . '.',
            ], 409);
        }

        // --------------------------------------------------------
        // 7. Determine Present vs Late
        // --------------------------------------------------------
        $status = $this->deriveStatus(
            $staff,
            $today,
            $now,
            $exception
        );

        // --------------------------------------------------------
        // 8. Save attendance using SERVER time
        // --------------------------------------------------------
        $attendance = Attendance::updateOrCreate(
            [
                'user_id' => $staff->id,
                'date' => $today->toDateString(),
            ],
            [
                'status' => $status,
                'check_in' => $now->format('H:i:s'),
                'check_out' => null,
                'marked_by' => auth()->id(),
            ]
        );

        return response()->json([
            'success' => true,

            'status' => $status,

            'display_status' => $status === 'late'
                ? 'late'
                : 'checked_in',

            'check_in' => $now->format('g:i A'),

            'check_out' => null,

            'message' => $staff->first_name
                . ' checked in'
                . ($status === 'late' ? ' (late)' : '')
                . ' at '
                . $now->format('g:i A'),
        ]);
    }

    public function quickCheckOut(
        Request $request,
        User $staff
    ) {
        $this->authorizeMarking();

        $attendance = Attendance::todayFor($staff->id);

        if (!$attendance || !$attendance->check_in) {
            return response()->json([
                'message' => $staff->first_name
                    . ' has not checked in yet.',
            ], 409);
        }

        if ($attendance->check_out) {
            return response()->json([
                'message' => $staff->first_name
                    . ' already checked out at '
                    . Carbon::parse(
                        $attendance->check_out
                    )->format('g:i A')
                    . '.',
            ], 409);
        }

        $now = now();

        $attendance->update([
            'check_out' => $now->format('H:i:s'),
        ]);

        return response()->json([
            'success' => true,

            'display_status' => 'completed',

            'check_in' => Carbon::parse(
                $attendance->check_in
            )->format('g:i A'),

            'check_out' => $now->format('g:i A'),

            'message' => $staff->first_name
                . ' checked out at '
                . $now->format('g:i A'),
        ]);
    }

    // ============================================================
    // Manual correction
    // ALWAYS audited in attendance_logs
    // ============================================================
    public function correct(
        Request $request,
        User $staff
    ) {
        /*
         * Receptionists need can_mark_attendance permission.
         */
        $this->authorizeMarking(
            requirePermission: true
        );

        $data = $request->validate([
            'type' => 'required|in:check_in,check_out',
            'time' => 'required|date_format:H:i',
            'reason' => 'required|string|max:255',
        ]);

        $today = Carbon::today();

        $exception = $this->todayException(
            $staff->id,
            $today
        );

        // --------------------------------------------------------
        // Block corrections for leave/day-off/holiday
        // --------------------------------------------------------
        if (
            $exception
            && in_array($exception->type, self::LEAVE_TYPES)
        ) {
            return response()->json([
                'message' => $staff->first_name
                    . ' is marked '
                    . strtolower(
                        str_replace('_', ' ', $exception->type)
                    )
                    . ' today. Remove the schedule exception '
                    . 'before correcting attendance.',
            ], 422);
        }

        $attendance = Attendance::firstOrNew([
            'user_id' => $staff->id,
            'date' => $today->toDateString(),
        ]);

        $correctedTime = $data['time'] . ':00';

        // --------------------------------------------------------
        // Check-out validation
        // --------------------------------------------------------
        if ($data['type'] === 'check_out') {
            if (!$attendance->check_in) {
                return response()->json([
                    'message' => 'Cannot record a check-out for '
                        . $staff->first_name
                        . ' because there is no check-in yet.',
                ], 422);
            }

            if ($correctedTime <= $attendance->check_in) {
                return response()->json([
                    'message' => 'Check-out time must be after '
                        . 'the check-in time ('
                        . Carbon::parse(
                            $attendance->check_in
                        )->format('g:i A')
                        . ').',
                ], 422);
            }
        }

        // --------------------------------------------------------
        // Save correction + audit trail atomically
        // --------------------------------------------------------
        DB::transaction(
            function () use (
                $attendance,
                $staff,
                $data,
                $correctedTime,
                $today
            ) {
                $old = [
                    'status' => $attendance->status,
                    'check_in' => $attendance->check_in,
                    'check_out' => $attendance->check_out,
                ];

                if ($data['type'] === 'check_in') {
                    $attendance->check_in = $correctedTime;

                    /*
                     * Recalculate Present/Late based on the
                     * CORRECTED check-in time.
                     *
                     * This also uses the 30-minute tolerance.
                     */
                    $attendance->status = $this->deriveStatus(
                        $staff,
                        $today,
                        Carbon::parse(
                            $today->toDateString()
                            . ' '
                            . $correctedTime
                        ),
                        $this->todayException(
                            $staff->id,
                            $today
                        )
                    );
                } else {
                    $attendance->check_out = $correctedTime;
                }

                $attendance->marked_by = auth()->id();

                $stamp = '[Manual correction by '
                    . auth()->user()->first_name
                    . ' '
                    . auth()->user()->last_name
                    . ' — '
                    . now()->format('M j, Y g:i A')
                    . ': '
                    . $data['reason']
                    . ']';

                $attendance->notes = trim(
                    ($attendance->notes
                        ? $attendance->notes . "\n"
                        : '')
                    . $stamp
                );

                $attendance->save();

                /*
                 * Audit trail.
                 *
                 * This makes it clear that the attendance was
                 * manually corrected rather than punched in real time.
                 */
                AttendanceLog::create([
                    'attendance_id' => $attendance->id,

                    'user_id' => $staff->id,

                    'changed_by' => auth()->id(),

                    'old_status' => $old['status'],

                    'new_status' => $attendance->status,

                    'old_check_in' => $old['check_in'],

                    'new_check_in' => $attendance->check_in,

                    'old_check_out' => $old['check_out'],

                    'new_check_out' => $attendance->check_out,

                    'reason' => $data['reason'],

                    'changed_at' => now(),
                ]);
            }
        );

        $attendance->refresh();

        return response()->json([
            'success' => true,

            'check_in' => $attendance->check_in
                ? Carbon::parse(
                    $attendance->check_in
                )->format('g:i A')
                : null,

            'check_out' => $attendance->check_out
                ? Carbon::parse(
                    $attendance->check_out
                )->format('g:i A')
                : null,

            'display_status' => $attendance->check_out
                ? 'completed'
                : (
                    $attendance->status === 'late'
                        ? 'late'
                        : 'checked_in'
                ),

            'message' => 'Correction saved and logged for '
                . $staff->first_name
                . '.',
        ]);
    }

    // ============================================================
    // Reporting & admin
    // ============================================================
    public function report(Request $request)
    {
        abort_unless(
            auth()->user()->roles->contains('name', 'admin'),
            403
        );

        $startDate = $request->filled('start_date')
            ? Carbon::parse(
                $request->start_date
            )->startOfDay()
            : now()->startOfWeek();

        $endDate = $request->filled('end_date')
            ? Carbon::parse(
                $request->end_date
            )->endOfDay()
            : $startDate->copy()->endOfWeek();

        $allStaff = User::whereHas(
                'roles',
                fn ($q) => $q->where('name', 'staff')
            )
            ->where('is_active', true)
            ->with('workSchedules')
            ->orderBy('first_name')
            ->get();

        $staffId = $request->filled('staff_id')
            ? (int) $request->staff_id
            : null;

        // --------------------------------------------------------
        // Paginated attendance records
        // --------------------------------------------------------
        $attendances = Attendance::with([
                'user',
                'marker',
            ])
            ->whereBetween(
                'date',
                [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ]
            )
            ->when(
                $staffId,
                fn ($q) => $q->where(
                    'user_id',
                    $staffId
                )
            )
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where(
                    'status',
                    $request->status
                )
            )
            ->orderBy('date', 'desc')
            ->orderBy('user_id')
            ->paginate(20)
            ->appends($request->query());

        // --------------------------------------------------------
        // Summary
        // --------------------------------------------------------
        $base = Attendance::whereBetween(
                'date',
                [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ]
            )
            ->when(
                $staffId,
                fn ($q) => $q->where(
                    'user_id',
                    $staffId
                )
            );

        $present = (clone $base)
            ->where('status', 'present')
            ->count();

        $late = (clone $base)
            ->where('status', 'late')
            ->count();

        $onLeave = ScheduleException::whereIn(
                'type',
                self::LEAVE_TYPES
            )
            ->whereBetween(
                'exception_date',
                [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ]
            )
            ->when(
                $staffId,
                fn ($q) => $q->where(
                    'user_id',
                    $staffId
                )
            )
            ->count();

        // --------------------------------------------------------
        // Determine absences
        //
        // Absent =
        // scheduled working day
        // + no attendance record
        // + no leave exception
        // --------------------------------------------------------
        $exceptions = ScheduleException::whereBetween(
                'exception_date',
                [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ]
            )
            ->when(
                $staffId,
                fn ($q) => $q->where(
                    'user_id',
                    $staffId
                )
            )
            ->get()
            ->groupBy(
                fn ($e) =>
                    $e->user_id
                    . '|'
                    . $e->exception_date->toDateString()
            );

        $attendanceDates = Attendance::whereBetween(
                'date',
                [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ]
            )
            ->when(
                $staffId,
                fn ($q) => $q->where(
                    'user_id',
                    $staffId
                )
            )
            ->pluck('date')
            ->map(
                fn ($d) =>
                    Carbon::parse($d)->toDateString()
            );

        $absent = 0;

        $rangeStaff = $staffId
            ? $allStaff->where('id', $staffId)
            : $allStaff;

        foreach ($rangeStaff as $member) {
            $scheduleMap = $member->workSchedules
                ->keyBy('day_of_week');

            for (
                $d = $startDate->copy();
                $d->lte($endDate);
                $d->addDay()
            ) {
                $key = $member->id
                    . '|'
                    . $d->toDateString();

                $exc = $exceptions
                    ->get($key)
                    ?->first();

                // Leave/day off/holiday
                if (
                    $exc
                    && in_array(
                        $exc->type,
                        self::LEAVE_TYPES
                    )
                ) {
                    continue;
                }

                $sched = $scheduleMap->get(
                    $d->dayOfWeek
                );

                $working =
                    (
                        $exc
                        && $exc->type === 'custom_hours'
                        && $exc->start_time
                    )
                    ||
                    (
                        $sched
                        && !$sched->is_day_off
                        && $sched->start_time
                    );

                // Not scheduled
                if (!$working) {
                    continue;
                }

                // No attendance record
                if (
                    !$attendanceDates->contains(
                        $d->toDateString()
                    )
                ) {
                    $absent++;
                }
            }
        }

        $summary = [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'on_leave' => $onLeave,
        ];

        $receptionists = User::whereHas(
                'roles',
                fn ($q) =>
                    $q->where('name', 'receptionist')
            )
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        return view(
            'admin.attendance-report',
            compact(
                'attendances',
                'allStaff',
                'receptionists',
                'summary',
                'startDate',
                'endDate'
            )
        );
    }

    // ============================================================
    // Receptionist attendance permission
    // ============================================================
    public function togglePermission(User $user)
    {
        if (
            !$user->roles()
                ->where('name', 'receptionist')
                ->exists()
        ) {
            return back()->with(
                'error',
                'User is not a receptionist.'
            );
        }

        $user->update([
            'can_mark_attendance' =>
                !$user->can_mark_attendance,
        ]);

        return back()->with(
            'success',
            'Permission updated.'
        );
    }

    // ============================================================
    // Helpers
    // ============================================================
    private function todayException(
        int $userId,
        Carbon $today
    ): ?ScheduleException {
        return ScheduleException::where(
                'user_id',
                $userId
            )
            ->whereDate(
                'exception_date',
                $today
            )
            ->first();
    }

    /**
     * Determine Present vs Late.
     *
     * Rules:
     *
     * - Early arrival = Present
     * - On-time arrival = Present
     * - Up to 30 minutes after scheduled start = Present
     * - More than 30 minutes after scheduled start = Late
     *
     * The shift END time is NOT checked here.
     *
     * The shift END restriction belongs to quickCheckIn(),
     * because it determines whether a real-time punch is
     * still allowed at all.
     */
    private function deriveStatus(
        User $staff,
        Carbon $today,
        Carbon $time,
        ?ScheduleException $exception
    ): string {
        $expectedStart = null;

        // Custom hours override normal schedule
        if (
            $exception
            && $exception->type === 'custom_hours'
            && $exception->start_time
        ) {
            $expectedStart = Carbon::parse(
                $today->toDateString()
                . ' '
                . $exception->start_time
            );
        } else {
            $schedule = WorkSchedule::where(
                    'user_id',
                    $staff->id
                )
                ->where(
                    'day_of_week',
                    $today->dayOfWeek
                )
                ->first();

            if (
                $schedule
                && !$schedule->is_day_off
                && $schedule->start_time
            ) {
                $expectedStart = Carbon::parse(
                    $today->toDateString()
                    . ' '
                    . $schedule->start_time
                );
            }
        }

        /*
         * No schedule start found.
         *
         * This should normally be prevented by quickCheckIn(),
         * but returning Present keeps the method safe for
         * manual correction and other callers.
         */
        if (!$expectedStart) {
            return 'present';
        }

        /*
         * 30-minute attendance tolerance.
         *
         * Example:
         *
         * Shift starts 6:00 AM
         *
         * 5:30 AM  -> Present
         * 6:00 AM  -> Present
         * 6:30 AM  -> Present
         * 6:31 AM  -> Late
         */
        return $time->greaterThan(
            $expectedStart->copy()->addMinutes(
                self::ATTENDANCE_TOLERANCE_MINUTES
            )
        )
            ? 'late'
            : 'present';
    }

    private function authorizeMarking(
        bool $requirePermission = false
    ): void {
        $user = auth()->user();

        // Administrators can always mark/correct attendance.
        if (
            $user->roles->contains('name', 'admin')
        ) {
            return;
        }

        // Receptionists require permission when requested.
        if (
            $user->roles->contains('name', 'receptionist')
            && (
                !$requirePermission
                || $user->can_mark_attendance
            )
        ) {
            return;
        }

        abort(
            403,
            'You are not authorized to record or correct attendance.'
        );
    }
}

