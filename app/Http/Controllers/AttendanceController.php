<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\ScheduleException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    /**
     * Grace period in minutes before marking as "late"
     */
    private const LATE_GRACE_MINUTES = 15;

    /**
     * Staff per page for the marking view
     */
    private const STAFF_PER_PAGE = 15;

    /**
     * Show today's attendance marking page with search & pagination.
     */
    public function today(Request $request)
    {
        $user = Auth::user();

        if (!$user->canMarkAttendance()) {
            abort(403, 'Unauthorized to mark attendance.');
        }

        $today = Carbon::today();
        $todayDow = $today->dayOfWeek();
        $now = Carbon::now();

        // Build base query for scheduled staff
        $staffQuery = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->whereHas('workSchedules', function ($q) use ($todayDow) {
                $q->where('day_of_week', $todayDow)->where('is_day_off', false);
            })
            ->with(['workSchedules' => fn($q) => $q->where('day_of_week', $todayDow)]);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $staffQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Status filter (show only absent, only present, etc.)
        if ($request->filled('filter_status')) {
            $filterStatus = $request->filter_status;
            $staffQuery->whereHas('attendances', function ($q) use ($today, $filterStatus) {
                $q->whereDate('date', $today)->where('status', $filterStatus);
            });
        }

        $scheduledStaff = $staffQuery->orderBy('first_name')->paginate(self::STAFF_PER_PAGE);

        // Get ALL attendances for today (for the paginated staff + any already marked)
        $allStaffIds = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->whereHas('workSchedules', function ($q) use ($todayDow) {
                $q->where('day_of_week', $todayDow)->where('is_day_off', false);
            })
            ->pluck('id');

        $attendances = Attendance::whereDate('date', $today)
            ->whereIn('user_id', $allStaffIds)
            ->with('marker')
            ->get()
            ->keyBy('user_id');

        $exceptions = ScheduleException::whereDate('exception_date', $today)
            ->whereIn('type', ['day_off', 'holiday', 'sick_leave', 'urgent_leave'])
            ->get()
            ->keyBy('user_id');

        // Quick stats for the header
        $stats = [
            'total' => $allStaffIds->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'on_leave' => $attendances->where('status', 'on_leave')->count(),
            'pending' => $allStaffIds->count() - $attendances->count(),
        ];

        $layout = $user->isAdmin() ? 'layouts.admin' : 'layouts.receptionist';

        return view('attendance.today', compact(
            'scheduledStaff', 'attendances', 'exceptions', 'today', 'layout', 'now', 'stats'
        ));
    }

    /**
     * Bulk mark attendance — optimized with single query for audit logs.
     */
    public function bulkMark(Request $request)
    {
        $user = Auth::user();

        if (!$user->canMarkAttendance()) {
            abort(403, 'Unauthorized to mark attendance.');
        }

        $today = Carbon::today();
        $todayDow = $today->dayOfWeek();

        // Get scheduled staff IDs (server-side validation)
        $scheduledStaffIds = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->whereHas('workSchedules', function ($q) use ($todayDow) {
                $q->where('day_of_week', $todayDow)->where('is_day_off', false);
            })
            ->pluck('id')
            ->toArray();

        $exceptions = ScheduleException::whereDate('exception_date', $today)
            ->whereIn('type', ['day_off', 'holiday', 'sick_leave', 'urgent_leave'])
            ->get()
            ->keyBy('user_id');

        // Get all schedules in one query
        $schedules = DB::table('work_schedules')
            ->where('day_of_week', $todayDow)
            ->where('is_day_off', false)
            ->get()
            ->keyBy('user_id');

        // Get existing attendances for comparison (single query)
        $existingAttendances = Attendance::whereDate('date', $today)
            ->whereIn('user_id', array_column($request->attendances ?? [], 'user_id'))
            ->get()
            ->keyBy('user_id');

        $request->validate([
            'attendances' => 'required|array',
            'attendances.*.user_id' => 'required|integer|in:' . implode(',', $scheduledStaffIds),
            'attendances.*.status' => 'required|in:present,absent,late,on_leave',
            'attendances.*.check_in' => 'nullable|date_format:H:i',
            'attendances.*.check_out' => 'nullable|date_format:H:i|after_or_equal:attendances.*.check_in',
            'attendances.*.notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $logs = [];
            $now = now();

            foreach ($request->attendances as $record) {
                $staffId = $record['user_id'];

                if (!in_array($staffId, $scheduledStaffIds)) {
                    continue;
                }

                $existing = $existingAttendances->get($staffId);

                $status = $record['status'];
                $checkIn = $record['check_in'] ?? null;
                $checkOut = $record['check_out'] ?? null;

                // Smart late detection with grace period
                if ($status === 'present' && $checkIn && isset($schedules[$staffId])) {
                    $scheduleStart = Carbon::parse($schedules[$staffId]->start_time);
                    $actualCheckIn = Carbon::parse($checkIn);
                    $graceEnd = $scheduleStart->copy()->addMinutes(self::LATE_GRACE_MINUTES);

                    if ($actualCheckIn->gt($graceEnd)) {
                        $status = 'late';
                    }
                }

                // Force on_leave for exceptions
                if (in_array($status, ['present', 'late']) && $exceptions->has($staffId)) {
                    $status = 'on_leave';
                }

                // Upsert attendance
                $attendance = Attendance::updateOrCreate(
                    ['user_id' => $staffId, 'date' => $today],
                    [
                        'status' => $status,
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'marked_by' => $user->id,
                        'notes' => $record['notes'] ?? null,
                    ]
                );

                // Build log entry for batch insert
                if (!$existing || $existing->status !== $status || $existing->check_in !== $checkIn || $existing->check_out !== $checkOut) {
                    $logs[] = [
                        'attendance_id' => $attendance->id,
                        'user_id' => $staffId,
                        'changed_by' => $user->id,
                        'old_status' => $existing?->status,
                        'new_status' => $status,
                        'old_check_in' => $existing?->check_in,
                        'new_check_in' => $checkIn,
                        'old_check_out' => $existing?->check_out,
                        'new_check_out' => $checkOut,
                        'reason' => $record['notes'] ?? null,
                        'changed_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // Batch insert all logs at once (much faster)
            if (!empty($logs)) {
                AttendanceLog::insert($logs);
            }

            DB::commit();

            // Preserve pagination and search on redirect
            $queryParams = [];
            if ($request->get('page')) $queryParams['page'] = $request->get('page');
            if ($request->get('search')) $queryParams['search'] = $request->get('search');
            if ($request->get('filter_status')) $queryParams['filter_status'] = $request->get('filter_status');

            $redirectUrl = route('attendance.today');
            if (!empty($queryParams)) {
                $redirectUrl .= '?' . http_build_query($queryParams);
            }

            return redirect($redirectUrl)->with('success', 'Attendance saved successfully. (' . count($logs) . ' changes logged)');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Attendance bulk mark failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to save attendance. Please try again.');
        }
    }

    /**
     * Quick check-in via AJAX (for the "Check In Now" button).
     */
    public function quickCheckIn(Request $request, User $staff)
    {
        $user = Auth::user();

        if (!$user->canMarkAttendance()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $today = Carbon::today();
        $todayDow = $today->dayOfWeek();
        $now = Carbon::now();

        // Verify staff is scheduled today
        $isScheduled = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->whereHas('workSchedules', function ($q) use ($todayDow) {
                $q->where('day_of_week', $todayDow)->where('is_day_off', false);
            })
            ->where('id', $staff->id)
            ->exists();

        if (!$isScheduled) {
            return response()->json(['error' => 'Staff not scheduled today'], 400);
        }

        // Check for exception
        $exception = ScheduleException::whereDate('exception_date', $today)
            ->where('user_id', $staff->id)
            ->whereIn('type', ['day_off', 'holiday', 'sick_leave', 'urgent_leave'])
            ->first();

        if ($exception) {
            return response()->json(['error' => 'Staff has ' . $exception->type], 400);
        }

        // Get schedule for late detection
        $schedule = DB::table('work_schedules')
            ->where('user_id', $staff->id)
            ->where('day_of_week', $todayDow)
            ->where('is_day_off', false)
            ->first();

        $status = 'present';
        if ($schedule) {
            $scheduleStart = Carbon::parse($schedule->start_time);
            $graceEnd = $scheduleStart->copy()->addMinutes(self::LATE_GRACE_MINUTES);
            if ($now->gt($graceEnd)) {
                $status = 'late';
            }
        }

        $existing = Attendance::where('user_id', $staff->id)->whereDate('date', $today)->first();

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $staff->id, 'date' => $today],
            [
                'status' => $status,
                'check_in' => $now->format('H:i:s'),
                'marked_by' => $user->id,
            ]
        );

        AttendanceLog::create([
            'attendance_id' => $attendance->id,
            'user_id' => $staff->id,
            'changed_by' => $user->id,
            'old_status' => $existing?->status,
            'new_status' => $status,
            'old_check_in' => $existing?->check_in,
            'new_check_in' => $now->format('H:i:s'),
            'changed_at' => $now,
        ]);

        return response()->json([
            'success' => true,
            'status' => $status,
            'check_in' => $now->format('g:i A'),
            'message' => $status === 'late' ? 'Checked in (Late)' : 'Checked in successfully',
        ]);
    }

    /**
     * Quick check-out via AJAX.
     */
    public function quickCheckOut(Request $request, User $staff)
    {
        $user = Auth::user();

        if (!$user->canMarkAttendance()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $today = Carbon::today();
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $staff->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json(['error' => 'No check-in found'], 400);
        }

        $oldCheckOut = $attendance->check_out;
        $attendance->update(['check_out' => $now->format('H:i:s')]);

        AttendanceLog::create([
            'attendance_id' => $attendance->id,
            'user_id' => $staff->id,
            'changed_by' => $user->id,
            'old_check_out' => $oldCheckOut,
            'new_check_out' => $now->format('H:i:s'),
            'changed_at' => $now,
        ]);

        return response()->json([
            'success' => true,
            'check_out' => $now->format('g:i A'),
            'message' => 'Checked out successfully',
        ]);
    }

    /**
     * Admin attendance report.
     */
    public function report(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }

        $startDate = $request->get('start_date') 
            ? Carbon::parse($request->start_date) 
            : Carbon::today()->startOfMonth();

        $endDate = $request->get('end_date') 
            ? Carbon::parse($request->end_date) 
            : Carbon::today();

        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy();
        }

        if ($startDate->diffInDays($endDate) > 92) {
            $startDate = $endDate->copy()->subDays(92);
        }

        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->with(['user', 'marker'])
            ->when($request->filled('staff_id'), fn($q) => $q->where('user_id', $request->staff_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50)
            ->withQueryString();

        $summary = Attendance::whereBetween('date', [$startDate, $endDate])
            ->when($request->filled('staff_id'), fn($q) => $q->where('user_id', $request->staff_id))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $allStaff = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $receptionists = User::whereHas('roles', fn($q) => $q->where('name', 'receptionist'))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'username', 'can_mark_attendance']);

        return view('admin.attendance-report', compact(
            'attendances', 'allStaff', 'summary', 'startDate', 'endDate', 'receptionists'
        ));
    }

    public function togglePermission(User $user)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }

        if (!$user->isReceptionist()) {
            return back()->with('error', 'User is not a receptionist.');
        }

        $user->can_mark_attendance = !$user->can_mark_attendance;
        $user->save();

        $status = $user->can_mark_attendance ? 'CAN MARK' : 'CANNOT MARK';

        return back()->with('success', "{$user->first_name} {$user->last_name} is now {$status} attendance.");
    }
}