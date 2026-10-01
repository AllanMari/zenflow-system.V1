<?php

namespace App\Http\Controllers;

use App\Services\ReportPdfService;
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
    private const LEAVE_TYPES = [
        'sick_leave',
        'urgent_leave',
    ];

    private const NON_WORKING_TYPES = [
        'day_off',
        'holiday',
    ];

    private const ATTENDANCE_TOLERANCE_MINUTES = 30;

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

        $exceptions = ScheduleException::whereDate(
                'exception_date',
                $today
            )
            ->get()
            ->keyBy('user_id');

        $isAdmin = $user->roles->contains('name', 'admin');

        $canMark = $isAdmin
            || (
                $user->roles->contains('name', 'receptionist')
                && (bool) $user->can_mark_attendance
            );

        $initialState = [];

        foreach ($staff as $member) {
            $att = $attendances->get($member->id);
            $exc = $exceptions->get($member->id);

            $schedule = $member->workSchedules
                ->firstWhere('day_of_week', $dayOfWeek);

            $isOff = $exc
                && (
                    in_array($exc->type, self::LEAVE_TYPES)
                    || in_array($exc->type, self::NON_WORKING_TYPES)
                );

            $offLabel = null;
            $shift = null;

            if ($isOff) {
                $display = 'off_leave';

                $offLabel = match ($exc->type) {
                    'holiday' => 'Holiday',
                    'day_off' => 'Day Off',
                    default => ucwords(
                        str_replace('_', ' ', $exc->type)
                    ),
                };
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
                    $shift =
                        Carbon::parse($exc->start_time)->format('g:i A')
                        . ' – '
                        . Carbon::parse($exc->end_time)->format('g:i A');
                } elseif ($working) {
                    $shift =
                        Carbon::parse($schedule->start_time)->format('g:i A')
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

    public function quickCheckIn(Request $request, User $staff)
    {
        $this->authorizeMarking();

        $today = Carbon::today();
        $now = now();

        $exception = $this->todayException(
            $staff->id,
            $today
        );

        if (
            $exception
            && (
                in_array($exception->type, self::LEAVE_TYPES)
                || in_array($exception->type, self::NON_WORKING_TYPES)
            )
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

        $startTime = null;
        $endTime = null;

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

        $status = $this->deriveStatus(
            $staff,
            $today,
            $now,
            $exception
        );

        Attendance::updateOrCreate(
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

    public function correct(
        Request $request,
        User $staff
    ) {
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

        if (
            $exception
            && (
                in_array($exception->type, self::LEAVE_TYPES)
                || in_array($exception->type, self::NON_WORKING_TYPES)
            )
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
    // Attendance Report View
    // ============================================================
    public function report(Request $request)
    {
        abort_unless(
            auth()->user()->roles->contains('name', 'admin'),
            403
        );

        $data = $this->buildReportData(
            $request,
            true
        );

        return view(
            'reports.attendance-report',
            $data
        );
    }

    // ============================================================
    // Attendance Report PDF
    // ============================================================
    public function attendanceReportPdf(
        Request $request,
        ReportPdfService $pdfService
    ) {
        abort_unless(
            auth()->user()->roles->contains('name', 'admin'),
            403
        );

        $data = $this->buildReportData(
            $request,
            false
        );

        $data['reportTitle'] = 'Attendance Report';
        $data['referenceNumber'] = 'ATT-' . now()->format('YmdHis');
        $data['dateRange'] = ($data['startDate'] ? $data['startDate']->format('M d, Y') : '') . ' - ' . ($data['endDate'] ? $data['endDate']->format('M d, Y') : '');

        $data['generatedAt'] =
            now('Asia/Manila')->format(
                'M j, Y g:i A'
            );

        $data['preparedBy'] =
            trim(
                auth()->user()->first_name
                . ' '
                . auth()->user()->last_name
            );

        $filename =
            'attendance-report-'
            . (
                $data['startDate']
                    ? $data['startDate']->format('Y-m-d')
                    : now('Asia/Manila')->format('Y-m-d')
            )
            . '.pdf';

        if ($request->input('action') === 'download') {
            return $pdfService->generatePdf(
                'reports.attendance-report',
                $data,
                $filename,
                'landscape'
            );
        }

        return $pdfService->streamPdf(
            'reports.attendance-report',
            $data,
            $filename,
            'landscape'
        );
    }

    // ============================================================
    // Build Attendance Report Data
    // ============================================================
    private function buildReportData(
        Request $request,
        bool $paginate
    ): array {
        $timezone = 'Asia/Manila';

$range = $request->input('range', 'week');

$now = now($timezone);

switch ($range) {
    case 'today':
        $startDate = $now->copy()->startOfDay();
        $endDate = $now->copy()->endOfDay();
        break;

    case 'week':
        $startDate = $now->copy()->startOfWeek();
        $endDate = $now->copy()->endOfWeek();
        break;

    case 'month':
        $startDate = $now->copy()->startOfMonth();
        $endDate = $now->copy()->endOfMonth();
        break;

    case 'year':
        $startDate = $now->copy()->startOfYear();
        $endDate = $now->copy()->endOfYear();
        break;

    case 'custom':
        $startDate = $request->filled('start_date')
            ? Carbon::parse(
                $request->input('start_date'),
                $timezone
            )->startOfDay()
            : $now->copy()->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse(
                $request->input('end_date'),
                $timezone
            )->endOfDay()
            : $startDate->copy()->endOfDay();
        break;

    default:
        $startDate = $now->copy()->startOfWeek();
        $endDate = $now->copy()->endOfWeek();
        break;
}

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [
                $endDate->copy()->startOfDay(),
                $startDate->copy()->endOfDay(),
            ];
        }

        $staffId = $request->filled('staff_id')
            ? (int) $request->input('staff_id')
            : null;

        $search = trim(
            (string) $request->input('search', '')
        );

        $statusFilter = trim(
            (string) $request->input('status', '')
        );

        $allStaff = User::whereHas(
                'roles',
                fn ($q) => $q->where('name', 'staff')
            )
            ->where('is_active', true)
            ->with('workSchedules')
            ->orderBy('first_name')
            ->get();

        $searchStaffIds = $allStaff
            ->filter(function ($staff) use ($search) {
                if ($search === '') {
                    return true;
                }

                $needle = mb_strtolower($search);

                $name = mb_strtolower(
                    trim(
                        $staff->first_name
                        . ' '
                        . $staff->last_name
                    )
                );

                $username = mb_strtolower(
                    (string) $staff->username
                );

                return str_contains($name, $needle)
                    || str_contains($username, $needle);
            })
            ->pluck('id')
            ->values();

        if ($staffId) {
            $searchStaffIds = $searchStaffIds
                ->filter(
                    fn ($id) => (int) $id === $staffId
                )
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | Attendance records
        |--------------------------------------------------------------------------
        */
        $attendanceQuery = Attendance::with([
                'user',
                'user.workSchedules',
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
                $search !== '',
                function ($q) use ($search) {
                    $needle = '%' . $search . '%';

                    $q->whereHas(
                        'user',
                        function ($userQuery) use ($needle) {
                            $userQuery
                                ->where(
                                    'first_name',
                                    'like',
                                    $needle
                                )
                                ->orWhere(
                                    'last_name',
                                    'like',
                                    $needle
                                )
                                ->orWhere(
                                    'username',
                                    'like',
                                    $needle
                                );
                        }
                    );
                }
            )
            ->when(
                $statusFilter !== ''
                && !in_array(
                    $statusFilter,
                    [
                        'absent',
                        'on_leave',
                        'day_off',
                        'holiday',
                    ]
                ),
                fn ($q) => $q->where(
                    'status',
                    $statusFilter
                )
            )
            ->orderBy('date', 'desc')
            ->orderBy('user_id');

        $attendances = $paginate
            ? $attendanceQuery
                ->paginate(20)
                ->appends($request->query())
            : $attendanceQuery->get();

        /*
        |--------------------------------------------------------------------------
        | Base attendance query
        |--------------------------------------------------------------------------
        */
        $base = Attendance::query()
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
                $search !== '',
                fn ($q) => $q->whereIn(
                    'user_id',
                    $searchStaffIds
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Schedule exceptions
        |--------------------------------------------------------------------------
        */
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
            ->when(
                $search !== '',
                fn ($q) => $q->whereIn(
                    'user_id',
                    $searchStaffIds
                )
            )
            ->orderBy('exception_date')
            ->get()
            ->groupBy(
                fn ($e) =>
                    $e->user_id
                    . '|'
                    . Carbon::parse(
                        $e->exception_date
                    )->toDateString()
            );

        /*
        |--------------------------------------------------------------------------
        | Attendance status counts
        |--------------------------------------------------------------------------
        */
        $present = (clone $base)
            ->whereIn(
                'status',
                [
                    'present',
                    'completed',
                    'checked_in',
                ]
            )
            ->count();

        $late = (clone $base)
            ->where('status', 'late')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Exception counts
        |--------------------------------------------------------------------------
        */
        $leave = 0;
        $dayOff = 0;
        $holiday = 0;

        foreach ($exceptions as $group) {
            $exception = $group->first();

            if (!$exception) {
                continue;
            }

            if (in_array(
                $exception->type,
                self::LEAVE_TYPES
            )) {
                $leave++;
            } elseif ($exception->type === 'day_off') {
                $dayOff++;
            } elseif ($exception->type === 'holiday') {
                $holiday++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Determine absences
        |--------------------------------------------------------------------------
        */
        $rangeStaff = $allStaff;

        if ($staffId) {
            $rangeStaff = $rangeStaff->where(
                'id',
                $staffId
            );
        }

        if ($search !== '') {
            $rangeStaff = $rangeStaff->whereIn(
                'id',
                $searchStaffIds
            );
        }

        $attendanceMap = (clone $base)
            ->get()
            ->keyBy(
                fn ($attendance) =>
                    $attendance->user_id
                    . '|'
                    . Carbon::parse(
                        $attendance->date
                    )->toDateString()
            );

        $absenceRecords = collect();

        $absent = 0;

        foreach ($rangeStaff as $member) {
            $scheduleMap = $member->workSchedules
                ->keyBy('day_of_week');

            for (
                $d = $startDate->copy()->startOfDay();
                $d->lte($endDate);
                $d->addDay()
            ) {
                $key =
                    $member->id
                    . '|'
                    . $d->toDateString();

                $exc = $exceptions
                    ->get($key)
                    ?->first();

                /*
                 * Leave, day off, and holiday are not absences.
                 */
                if (
                    $exc
                    && (
                        in_array(
                            $exc->type,
                            self::LEAVE_TYPES
                        )
                        || in_array(
                            $exc->type,
                            self::NON_WORKING_TYPES
                        )
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

                if (!$working) {
                    continue;
                }

                $attendance = $attendanceMap->get($key);

                if (!$attendance) {
                    $absent++;

                    $absenceRecords->push([
                        'user' => $member,
                        'date' => $d->copy(),
                        'type' => 'absent',
                        'label' => 'Absent',
                        'reason' => null,
                    ]);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Build exception records for report
        |--------------------------------------------------------------------------
        */
        $exceptionRecords = collect();

        foreach ($exceptions as $group) {
            $exception = $group->first();

            if (!$exception) {
                continue;
            }

            $member = $rangeStaff->firstWhere(
                'id',
                $exception->user_id
            );

            if (!$member) {
                continue;
            }

            $label = match ($exception->type) {
                'day_off' => 'Day Off',
                'holiday' => 'Holiday',
                'sick_leave' => 'Sick Leave',
                'urgent_leave' => 'Urgent Leave',
                default => ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $exception->type
                    )
                ),
            };

            if (
                in_array(
                    $exception->type,
                    [
                        ...self::LEAVE_TYPES,
                        ...self::NON_WORKING_TYPES,
                    ]
                )
            ) {
                $exceptionRecords->push([
                    'user' => $member,
                    'date' => Carbon::parse(
                        $exception->exception_date
                    ),
                    'type' => $exception->type,
                    'label' => $label,
                    'reason' => $exception->reason,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Filter synthetic status records if status filter is used
        |--------------------------------------------------------------------------
        */
        if ($statusFilter === 'absent') {
            $exceptionRecords = collect();
        }

        if ($statusFilter === 'on_leave') {
            $absenceRecords = collect();

            $exceptionRecords = $exceptionRecords
                ->filter(
                    fn ($row) =>
                        in_array(
                            $row['type'],
                            self::LEAVE_TYPES
                        )
                )
                ->values();
        }

        if ($statusFilter === 'day_off') {
            $absenceRecords = collect();

            $exceptionRecords = $exceptionRecords
                ->filter(
                    fn ($row) =>
                        $row['type'] === 'day_off'
                )
                ->values();
        }

        if ($statusFilter === 'holiday') {
            $absenceRecords = collect();

            $exceptionRecords = $exceptionRecords
                ->filter(
                    fn ($row) =>
                        $row['type'] === 'holiday'
                )
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | Worked hours and overtime
        |--------------------------------------------------------------------------
        */
        $totalWorkedHours = 0;
        $totalOvertime = 0;
        $staffOvertimeSummary = [];

        $allAttendances = (clone $base)->get();

        foreach ($allAttendances as $att) {
            if (
                !$att->check_in
                || !$att->check_out
            ) {
                continue;
            }

            $cIn = Carbon::parse(
                $att->check_in
            );

            $cOut = Carbon::parse(
                $att->check_out
            );

            if ($cOut->lessThanOrEqualTo($cIn)) {
                $cOut->addDay();
            }

            $worked = round(
                $cIn->diffInMinutes($cOut) / 60,
                2
            );

            $totalWorkedHours += $worked;

            $key =
                $att->user_id
                . '|'
                . Carbon::parse(
                    $att->date
                )->toDateString();

            $exc = $exceptions
                ->get($key)
                ?->first();

            $staffMember =
                $allStaff->firstWhere(
                    'id',
                    $att->user_id
                );

            $sched = $staffMember
                ?->workSchedules
                ->firstWhere(
                    'day_of_week',
                    Carbon::parse(
                        $att->date
                    )->dayOfWeek
                );

            $scheduledHrs = 0;

            if (
                $exc
                && $exc->type === 'custom_hours'
                && $exc->start_time
                && $exc->end_time
            ) {
                $s = Carbon::parse(
                    $exc->start_time
                );

                $e = Carbon::parse(
                    $exc->end_time
                );

                if ($e->lessThanOrEqualTo($s)) {
                    $e->addDay();
                }

                $scheduledHrs = round(
                    $s->diffInMinutes($e) / 60,
                    2
                );
            } elseif (
                $sched
                && !$sched->is_day_off
                && $sched->start_time
                && $sched->end_time
            ) {
                $s = Carbon::parse(
                    $sched->start_time
                );

                $e = Carbon::parse(
                    $sched->end_time
                );

                if ($e->lessThanOrEqualTo($s)) {
                    $e->addDay();
                }

                $scheduledHrs = round(
                    $s->diffInMinutes($e) / 60,
                    2
                );
            }

            $ot = max(
                0,
                $worked - $scheduledHrs
            );

            $totalOvertime += $ot;

            $uid = $att->user_id;

            if (!isset(
                $staffOvertimeSummary[$uid]
            )) {
                $staffOvertimeSummary[$uid] = [
                    'user_id' => $uid,

                    'name' =>
                        trim(
                            (
                                $staffMember->first_name
                                ?? '?'
                            )
                            . ' '
                            .
                            (
                                $staffMember->last_name
                                ?? ''
                            )
                        ),

                    'days_worked' => 0,

                    'scheduled_hours' => 0,

                    'worked_hours' => 0,

                    'overtime_hours' => 0,
                ];
            }

            $staffOvertimeSummary[$uid]['days_worked']++;

            $staffOvertimeSummary[$uid]['scheduled_hours'] +=
                $scheduledHrs;

            $staffOvertimeSummary[$uid]['worked_hours'] +=
                $worked;

            $staffOvertimeSummary[$uid]['overtime_hours'] +=
                $ot;
        }

        foreach (
            $staffOvertimeSummary as &$row
        ) {
            $row['scheduled_hours'] =
                round(
                    $row['scheduled_hours'],
                    2
                );

            $row['worked_hours'] =
                round(
                    $row['worked_hours'],
                    2
                );

            $row['overtime_hours'] =
                round(
                    $row['overtime_hours'],
                    2
                );
        }

        unset($row);

        usort(
            $staffOvertimeSummary,
            fn ($a, $b) =>
                $b['overtime_hours']
                <=>
                $a['overtime_hours']
        );

        /*
        |--------------------------------------------------------------------------
        | Add calculated values to attendance rows
        |--------------------------------------------------------------------------
        */
        $this->decorateAttendanceRecords(
            $attendances,
            $exceptions,
            $allStaff
        );

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */
        $summary = [
            'present' =>
                $present,

            'absent' =>
                $absent,

            'late' =>
                $late,

            'on_leave' =>
                $leave,

            'day_off' =>
                $dayOff,

            'holiday' =>
                $holiday,

            'worked_hours' =>
                round(
                    $totalWorkedHours,
                    2
                ),

            'overtime' =>
                round(
                    $totalOvertime,
                    2
                ),
        ];

        $receptionists = User::whereHas(
                'roles',
                fn ($q) =>
                    $q->where(
                        'name',
                        'receptionist'
                    )
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy('first_name')
            ->get();

        return [
            'attendances' =>
                $attendances,

            'allStaff' =>
                $allStaff,

            'receptionists' =>
                $receptionists,

            'summary' =>
                $summary,

            'startDate' =>
                $startDate,

            'endDate' =>
                $endDate,

            'staffOvertimeSummary' =>
                $staffOvertimeSummary,

            'absenceRecords' =>
                $absenceRecords,

            'exceptionRecords' =>
                $exceptionRecords,
        ];
    }

    // ============================================================
    // Add calculated hours to attendance records
    // ============================================================
    private function decorateAttendanceRecords(
        $attendances,
        $exceptions,
        $allStaff
    ): void {
        foreach ($attendances as $att) {
            $att->worked_hours = 0;
            $att->overtime_hours = 0;
            $att->scheduled_hours = 0;

            if (
                !$att->check_in
                || !$att->check_out
            ) {
                continue;
            }

            $cIn = Carbon::parse(
                $att->check_in
            );

            $cOut = Carbon::parse(
                $att->check_out
            );

            if ($cOut->lessThanOrEqualTo($cIn)) {
                $cOut->addDay();
            }

            $worked = round(
                $cIn->diffInMinutes($cOut) / 60,
                2
            );

            $att->worked_hours = $worked;

            $key =
                $att->user_id
                . '|'
                . Carbon::parse(
                    $att->date
                )->toDateString();

            $exc = $exceptions
                ->get($key)
                ?->first();

            $staffMember =
                $allStaff->firstWhere(
                    'id',
                    $att->user_id
                );

            $userWorkSchedules =
                $staffMember
                    ?->workSchedules
                    ??
                $att->user
                    ?->workSchedules()
                    ->get();

            $sched =
                $userWorkSchedules
                    ?->firstWhere(
                        'day_of_week',
                        Carbon::parse(
                            $att->date
                        )->dayOfWeek
                    );

            $scheduledHrs = 0;

            if (
                $exc
                && $exc->type === 'custom_hours'
                && $exc->start_time
                && $exc->end_time
            ) {
                $s = Carbon::parse(
                    $exc->start_time
                );

                $e = Carbon::parse(
                    $exc->end_time
                );

                if ($e->lessThanOrEqualTo($s)) {
                    $e->addDay();
                }

                $scheduledHrs =
                    round(
                        $s->diffInMinutes($e) / 60,
                        2
                    );
            } elseif (
                $sched
                && !$sched->is_day_off
                && $sched->start_time
                && $sched->end_time
            ) {
                $s = Carbon::parse(
                    $sched->start_time
                );

                $e = Carbon::parse(
                    $sched->end_time
                );

                if ($e->lessThanOrEqualTo($s)) {
                    $e->addDay();
                }

                $scheduledHrs =
                    round(
                        $s->diffInMinutes($e) / 60,
                        2
                    );
            }

            $att->scheduled_hours =
                $scheduledHrs;

            $att->overtime_hours =
                round(
                    max(
                        0,
                        $worked - $scheduledHrs
                    ),
                    2
                );
        }
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

    private function deriveStatus(
        User $staff,
        Carbon $today,
        Carbon $time,
        ?ScheduleException $exception
    ): string {
        $expectedStart = null;

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

        if (!$expectedStart) {
            return 'present';
        }

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

        if (
            $user->roles->contains('name', 'admin')
        ) {
            return;
        }

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