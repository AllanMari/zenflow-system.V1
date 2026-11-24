<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ShiftTemplate;
use App\Models\ScheduleException;
use App\Models\WorkSchedule;
use App\Models\Attendance;
use App\Services\ReportPdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ScheduleTrait;

class ScheduleController extends Controller
{
    use ScheduleTrait;

    protected ReportPdfService $reportPdfService;

    public function __construct(ReportPdfService $reportPdfService)
    {
        $this->reportPdfService = $reportPdfService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->roles->contains('name', 'admin');
        $canEdit = $isAdmin || ($user->roles->contains('name', 'receptionist') && $user->can_manage_schedules);

        $view = $request->get('view', 'week');

        $todayManila = now('Asia/Manila');

        if ($request->has('week_start')) {
            $weekStart = Carbon::parse($request->get('week_start'), 'Asia/Manila')->startOfWeek(Carbon::SUNDAY);
            $todayWeekStart = $todayManila->copy()->startOfWeek(Carbon::SUNDAY)->toDateString();
            $date = $request->filled('date')
                ? Carbon::parse($request->get('date'), 'Asia/Manila')
                : ($weekStart->toDateString() === $todayWeekStart ? $todayManila->copy() : $weekStart->copy());
        } else {
            $date = $request->filled('date') ? Carbon::parse($request->get('date'), 'Asia/Manila') : $todayManila->copy();
            // FIX: Use startOfWeek(Carbon::SUNDAY) to align with 0-6 day_of_week indexing
            $weekStart = $date->copy()->startOfWeek(Carbon::SUNDAY);
        }

        $staff = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->where('is_active', true)
            ->with('workSchedules')
            ->orderBy('first_name')
            ->get();

        $templates = ShiftTemplate::where('is_active', true)->orderBy('name')->get();

        $viewData = [
            'isAdmin' => $isAdmin,
            'canEdit' => $canEdit,
            'staff' => $staff,
            'templates' => $templates,
            'view' => $view,
            'weekStart' => $weekStart->toDateString(),
            'date' => $date->toDateString(),
        ];

        if ($view === 'day') {
            $timeline = $this->schedBuildDayTimeline($staff, $date);

            return view('shared.schedule-management', array_merge($viewData, [
                'timeline' => $timeline,
                'dateLabel' => $date->format('l, F j, Y'),
                'prevDate' => $date->copy()->subDay()->toDateString(),
                'nextDate' => $date->copy()->addDay()->toDateString(),
                'days' => [],
                'staffStats' => [],
                'weekLabel' => null,
                'prevWeek' => null,
                'nextWeek' => null,
            ]));
        }

        [$days, $timeline, $staffStats] = $this->schedBuildTimeline($staff, $weekStart);

        return view('shared.schedule-management', array_merge($viewData, [
            'days' => $days,
            'timeline' => $timeline,
            'staffStats' => $staffStats,
            'weekLabel' => $weekStart->format('M j') . ' – ' . $weekStart->copy()->endOfWeek(Carbon::SATURDAY)->format('M j, Y'),
            'prevWeek' => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek' => $weekStart->copy()->addWeek()->toDateString(),
        ]));
    }

    /*
    |--------------------------------------------------------------------------
    | Printable Weekly Schedule
    |--------------------------------------------------------------------------
    */

    public function printSchedule(Request $request)
    {
        // FIX: Add authorization check for printing schedules
        $this->authorizeScheduleEdit();
        
        $data = $this->buildPrintableScheduleData($request);

        return $this->reportPdfService->streamPdf(
            'reports.staff-weekly-schedule-pdf',
            $data,
            $this->printableScheduleFilename($data),
            'landscape'
        );
    }

    public function downloadSchedule(Request $request)
    {
        $data = $this->buildPrintableScheduleData($request);

        return $this->reportPdfService->generatePdf(
            'reports.staff-weekly-schedule-pdf',
            $data,
            $this->printableScheduleFilename($data),
            'landscape'
        );
    }

    protected function buildPrintableScheduleData(Request $request): array
    {
        $request->validate([
            'week_start' => ['nullable', 'date'],
            'staff_id' => ['nullable', 'integer'],
            'staff_ids' => ['nullable', 'string'],
            'checked_by' => ['nullable', 'string', 'max:255'],
        ]);

        $weekStart = $request->filled('week_start')
            ? Carbon::parse($request->week_start)->startOfWeek()
            : now('Asia/Manila')->startOfWeek();

        /*
         * Selection logic:
         *
         * staff_ids = comma-separated selected staff IDs
         * staff_id  = single selected staff ID
         * neither   = all active staff
         */
        $selectedIds = collect();

        if ($request->filled('staff_ids')) {
            $selectedIds = collect(
                explode(',', (string) $request->staff_ids)
            )
                ->map(fn($id) => (int) trim($id))
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->values();
        } elseif ($request->filled('staff_id')) {
            $selectedIds = collect([(int) $request->staff_id]);
        }

        $staffQuery = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->where('is_active', true)
            ->with('workSchedules')
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($selectedIds->isNotEmpty()) {
            $staffQuery->whereIn('id', $selectedIds);
        }

        $staff = $staffQuery->get();

        abort_if(
            $staff->isEmpty(),
            404,
            'No active staff schedule was found for the selected staff.'
        );

        /*
         * Use the SAME scheduling engine as Schedule Management.
         *
         * Attendance is intentionally disabled because this PDF is a
         * schedule document, not an attendance report.
         */
        [$days, $timeline, $staffStats] = $this->schedBuildTimeline(
            $staff,
            $weekStart,
            false
        );

        $timeRange = $this->buildPrintableTimeRange($timeline);

        $mode = $staff->count() === 1
            ? 'individual'
            : 'combined';

        return [
            'mode' => $mode,

            'staff' => $staff,
            'selectedStaff' => $staff->count() === 1 ? $staff->first() : null,

            'days' => $days,
            'timeline' => $timeline,
            'staffStats' => $staffStats,

            'weekStart' => $weekStart,
            'weekEnd' => $weekStart->copy()->endOfWeek(),

            'weekLabel' =>
                $weekStart->format('F j, Y')
                . ' - '
                . $weekStart->copy()->endOfWeek()->format('F j, Y'),

            'referenceNumber' => 'SCH-' . $weekStart->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),

            'timeRange' => $timeRange,

            'preparedBy' => auth()->user()?->full_name ?? 'System User',
            'checkedBy' => $request->input('checked_by'),

            'generatedAt' => now('Asia/Manila'),
        ];
    }

    /**
     * Determine the vertical timetable range from actual schedule data.
     */
    protected function buildPrintableTimeRange($timeline): array
    {
        $minimum = null;
        $maximum = null;

        foreach ($timeline as $row) {
            foreach ($row['days'] as $cell) {
                if (
                    empty($cell['start_time']) ||
                    empty($cell['end_time'])
                ) {
                    continue;
                }

                $start = $this->printableTimeToMinutes(
                    $cell['start_time']
                );

                $end = $this->printableTimeToMinutes(
                    $cell['end_time']
                );

                /*
                 * Existing ScheduleTrait already supports overnight shifts.
                 * Keep the same interpretation here.
                 */
                if ($end <= $start) {
                    $end += 1440;
                }

                $minimum = $minimum === null
                    ? $start
                    : min($minimum, $start);

                $maximum = $maximum === null
                    ? $end
                    : max($maximum, $end);
            }
        }

        if ($minimum === null || $maximum === null) {
            return [
                'start' => null,
                'end' => null,
                'rows' => [],
            ];
        }

        /*
         * Schedule Management's time selector uses 30-minute increments,
         * so the printable timetable uses the same interval.
         */
        $minimum = (int) (floor($minimum / 30) * 30);
        $maximum = (int) (ceil($maximum / 30) * 30);

        if ($maximum <= $minimum) {
            $maximum = $minimum + 30;
        }

        $rows = [];

        for ($minute = $minimum; $minute < $maximum; $minute += 30) {
            $rows[] = [
                'minute' => $minute,
                'label' => $this->formatPrintableMinute($minute),
            ];
        }

        return [
            'start' => $minimum,
            'end' => $maximum,
            'rows' => $rows,
        ];
    }

    protected function printableTimeToMinutes(?string $time): int
    {
        $time = substr((string) $time, 0, 5);

        [$hour, $minute] = array_map(
            'intval',
            explode(':', $time)
        );

        return ($hour * 60) + $minute;
    }

    protected function formatPrintableMinute(int $minutes): string
    {
        $dayOffset = intdiv($minutes, 1440);

        $minuteOfDay = $minutes % 1440;

        $hour = intdiv($minuteOfDay, 60);
        $minute = $minuteOfDay % 60;

        $label = Carbon::createFromTime(
            $hour,
            $minute
        )->format('g:i A');

        return $dayOffset > 0
            ? $label . ' (+1)'
            : $label;
    }

    protected function printableScheduleFilename(array $data): string
    {
        $weekStart = $data['weekStart']->format('Y-m-d');

        if (
            $data['mode'] === 'individual' &&
            $data['selectedStaff']
        ) {
            $staff = $data['selectedStaff'];

            return 'staff-schedule-' . $staff->id . '.pdf';
        }

        return 'staff-weekly-schedule.pdf';
    }

    public function moveShift(Request $request)
    {
        $this->authorizeScheduleEdit();

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'original_user_id' => 'nullable|exists:users,id',
        ]);

        $date = Carbon::parse($request->date)->toDateString();

        DB::transaction(function () use ($request, $date) {
            ScheduleException::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                    'exception_date' => $date
                ],
                [
                    'type' => 'custom_hours',
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'reason' => 'Modified via timeline drag-and-drop',
                ]
            );

            if (
                $request->filled('original_user_id')
                && $request->original_user_id != $request->user_id
            ) {
                $originalUser = User::find($request->original_user_id);

                ScheduleException::updateOrCreate(
                    [
                        'user_id' => $request->original_user_id,
                        'exception_date' => $date
                    ],
                    [
                        'type' => 'day_off',
                        'reason' =>
                            'Reassigned via drag-and-drop to '
                            . ($originalUser
                                ? $originalUser->full_name
                                : 'another staff'),
                    ]
                );
            }
        });

        return response()->json(['success' => true]);
    }

    public function bulkUpdate(Request $request)
    {
        $this->authorizeScheduleEdit();

        $this->schedBulkUpdate($request);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Schedule updated'
            ]);
        }

        return back()->with('success', 'Schedule updated');
    }

    public function quickBlock(Request $request)
    {
        $this->authorizeScheduleEdit();

        $this->schedQuickBlock($request);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Block added successfully'
            ]);
        }

        return back()->with('success', 'Block added successfully');
    }

    public function applyTemplate(Request $request, User $user)
    {
        $this->authorizeScheduleEdit();

        $this->schedApplyTemplate($request, $user);

        return response()->json(['success' => true]);
    }

    public function applyTemplateBulk(Request $request)
    {
        $this->authorizeScheduleEdit();

        $results = $this->schedApplyTemplateBulk($request);

        return response()->json($results);
    }

    public function deleteException(
        Request $request,
        ScheduleException $exception
    ) {
        $this->authorizeScheduleEdit();

        $exception->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Exception removed'
            ]);
        }

        return back()->with('success', 'Exception removed');
    }

    public function toggleReceptionistPermission(User $user)
    {
        $this->authorizeAdmin();

        if (!$user->roles()->where('name', 'receptionist')->exists()) {
            return back()->with('error', 'User is not a receptionist');
        }

        $user->update([
            'can_manage_schedules' => !$user->can_manage_schedules
        ]);

        $status = $user->can_manage_schedules
            ? 'CAN EDIT'
            : 'VIEW ONLY';

        return back()->with(
            'success',
            $user->first_name
            . ' '
            . $user->last_name
            . ' is now '
            . $status
        );
    }

    public function templates(Request $request)
    {
        $this->authorizeScheduleEdit();

        $user = auth()->user();

        $isAdmin = $user->roles->contains('name', 'admin');

        $staff = User::whereHas(
                'roles',
                fn($q) => $q->where('name', 'staff')
            )
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get([
                'id',
                'first_name',
                'last_name'
            ]);

        $templates = ShiftTemplate::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('shared.shift-templates', [
            'isAdmin' => $isAdmin,
            'staff' => $staff,
            'templates' => $templates,
        ]);
    }

    public function staffScheduleApi(
        Request $request,
        User $staff
    ) {
        $user = auth()->user();

        $isAdmin = $user->roles->contains('name', 'admin');

        $isSelf = $user->id === $staff->id;

        $isReceptionist =
            $user->roles->contains('name', 'receptionist')
            && $user->can_manage_schedules;

        if (!$isAdmin && !$isSelf && !$isReceptionist) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        $schedules = WorkSchedule::where(
                'user_id',
                $staff->id
            )
            ->get()
            ->keyBy('day_of_week')
            ->map(fn($s) => [
                'start_time' => $s->start_time
                    ? Carbon::parse($s->start_time)->format('H:i')
                    : null,
                'end_time' => $s->end_time
                    ? Carbon::parse($s->end_time)->format('H:i')
                    : null,
                'is_day_off' => (bool) $s->is_day_off,
            ]);

        $exceptions = ScheduleException::where(
                'user_id',
                $staff->id
            )
            ->whereDate(
                'exception_date',
                '>=',
                today()
            )
            ->orderBy('exception_date')
            ->get([
                'id',
                'exception_date',
                'type',
                'start_time',
                'end_time',
                'reason'
            ])
            ->map(fn($e) => [
                'id' => $e->id,
                'exception_date' =>
                    $e->exception_date->toDateString(),
                'type' => $e->type,
                'start_time' => $e->start_time
                    ? Carbon::parse($e->start_time)->format('H:i')
                    : null,
                'end_time' => $e->end_time
                    ? Carbon::parse($e->end_time)->format('H:i')
                    : null,
                'reason' => $e->reason,
            ]);

        return response()->json([
            'schedules' => $schedules,
            'exceptions' => $exceptions,
        ]);
    }

    public function updateTemplate(Request $request)
    {
        $this->authorizeScheduleEdit();

        $request->validate([
            'schedules' => 'required|array',
            'schedules.*' => 'required|array',
            'schedules.*.*.start_time' =>
                'nullable|date_format:H:i',
            'schedules.*.*.end_time' =>
                'nullable|date_format:H:i|after_or_equal:schedules.*.*.start_time',
            'schedules.*.*.is_day_off' =>
                'required|in:0,1,true,false',
        ]);

        foreach ($request->schedules as $userId => $days) {
            foreach ($days as $dow => $data) {
                $isOff = filter_var(
                    $data['is_day_off'],
                    FILTER_VALIDATE_BOOLEAN
                );

                WorkSchedule::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'day_of_week' => (int) $dow
                    ],
                    [
                        'start_time' =>
                            $isOff
                                ? null
                                : ($data['start_time'] ?? null),
                        'end_time' =>
                            $isOff
                                ? null
                                : ($data['end_time'] ?? null),
                        'is_day_off' => $isOff,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Template updated'
        ]);
    }

    public function storeException(Request $request)
    {
        $this->authorizeScheduleEdit();

        $request->validate($this->exceptionRules());

        $userId = (int) $request->input('user_id');
        $start = Carbon::parse($request->input('date'));
        $end = $request->filled('end_date') ? Carbon::parse($request->input('end_date')) : $start->copy();
        $itemsToCheck = [];
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $itemsToCheck[] = [
                'user_id' => $userId,
                'date' => $cursor->toDateString(),
                'exception_type' => $request->input('exception_type'),
                'start_time' => $request->input('start_time'),
                'end_time' => $request->input('end_time'),
            ];
        }

        $result = DB::transaction(function () use ($request, $userId, $itemsToCheck) {
            if (!$this->lockEligibleTherapists([$userId])) {
                return ['error' => 'The selected user must be an eligible therapist.'];
            }

            if ($conflict = $this->validateExceptionAppointmentConflict($itemsToCheck)) {
                return ['error' => $conflict];
            }

            $count = $this->persistException(
                $request->only([
                    'user_id',
                    'exception_type',
                    'date',
                    'end_date',
                    'start_time',
                    'end_time',
                    'reason'
                ])
            );

            return ['count' => $count];
        });

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        $count = $result['count'];

        return response()->json([
            'success' => true,
            'message' => $count > 1
                ? "Exception applied across {$count} days"
                : 'Exception added successfully',
        ]);
    }

    /**
     * Lock affected therapist user rows in ascending ID order (matching
     * TherapistAssignmentService::assignStaff()) and verify all remain
     * eligible staff accounts.
     */
    private function lockEligibleTherapists(array $therapistIds): bool
    {
        $ids = array_values(array_unique(array_map('intval', $therapistIds)));
        if (empty($ids)) {
            return false;
        }

        sort($ids, SORT_NUMERIC);

        $lockedUsers = User::whereIn('id', $ids)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        if ($lockedUsers->count() !== count($ids)) {
            return false;
        }

        $eligibleCount = User::whereIn('id', $ids)
            ->whereHas('roles', fn ($q) => $q->where('name', 'staff'))
            ->count();

        return $eligibleCount === count($ids);
    }

    public function storeExceptionBulk(Request $request)
    {
        $this->authorizeScheduleEdit();

        $timeIntervalRule = function ($attribute, $value, $fail) {
            if ($value && !in_array((int) substr($value, -2), [0, 30], true)) {
                $fail('Time must use the established 30-minute interval.');
            }
        };

        $staffUserRule = function ($attribute, $value, $fail) {
            if (!$value) {
                return;
            }
            $isStaff = User::where('id', $value)
                ->whereHas('roles', fn ($q) => $q->where('name', 'staff'))
                ->exists();
            if (!$isStaff) {
                $fail('The selected user must be an eligible therapist.');
            }
        };

        $isWholeTherapist = $request->has('whole_therapist_ids') || $request->has('date_range');

        if ($isWholeTherapist) {
            $request->validate([
                'whole_therapist_ids' => 'required|array|min:1',
                'whole_therapist_ids.*' => ['required', 'integer', 'exists:users,id', $staffUserRule],
                'date_range' => 'required|array',
                'date_range.start_date' => 'required|date',
                'date_range.end_date' => 'required|date|after_or_equal:date_range.start_date',
                'exception_type' => 'required|in:day_off,holiday,custom_hours',
                'start_time' => [
                    'nullable',
                    'required_if:exception_type,custom_hours',
                    'date_format:H:i',
                    $timeIntervalRule,
                ],
                'end_time' => [
                    'nullable',
                    'required_if:exception_type,custom_hours',
                    'date_format:H:i',
                    'after:start_time',
                    $timeIntervalRule,
                ],
                'reason' => 'nullable|string|max:255',
            ]);

            $therapistIds = array_values(array_unique(array_map('intval', $request->input('whole_therapist_ids'))));
        } else {
            $request->validate([
                'exceptions' => 'required|array|min:1',
                'exceptions.*.user_id' => ['required', 'exists:users,id', $staffUserRule],
                'exceptions.*.date' => 'required|date',
                'exceptions.*.exception_type' => 'required|in:day_off,holiday,custom_hours',
                'exceptions.*.start_time' => [
                    'nullable',
                    'required_if:exceptions.*.exception_type,custom_hours',
                    'date_format:H:i',
                    $timeIntervalRule,
                ],
                'exceptions.*.end_time' => [
                    'nullable',
                    'required_if:exceptions.*.exception_type,custom_hours',
                    'date_format:H:i',
                    'after:exceptions.*.start_time',
                    $timeIntervalRule,
                ],
                'exceptions.*.reason' => 'nullable|string|max:255',
            ]);

            $therapistIds = array_values(array_unique(array_map(
                fn ($item) => (int) $item['user_id'],
                $request->exceptions
            )));
        }

        $result = DB::transaction(function () use ($request, $isWholeTherapist, $therapistIds) {
            if (!$this->lockEligibleTherapists($therapistIds)) {
                return ['error' => 'One or more selected users are not eligible therapists.'];
            }

            $items = [];

            if ($isWholeTherapist) {
                $today = Carbon::today('Asia/Manila');
                $rangeStart = Carbon::parse($request->input('date_range.start_date'), 'Asia/Manila')->startOfDay();
                $rangeEnd = Carbon::parse($request->input('date_range.end_date'), 'Asia/Manila')->startOfDay();
                $effectiveStart = $rangeStart->lt($today) ? $today->copy() : $rangeStart;

                $allSchedulesByUser = WorkSchedule::whereIn('user_id', $therapistIds)
                    ->get()
                    ->groupBy('user_id');

                $isCustomHours = $request->input('exception_type') === 'custom_hours';
                $therapistRules = [];
                foreach ($therapistIds as $uid) {
                    $userSchedules = $allSchedulesByUser->get($uid);
                    $hasRecurringRows = $userSchedules && $userSchedules->isNotEmpty();
                    $therapistRules[$uid] = [
                        'fallback_all_dates' => !$hasRecurringRows && $isCustomHours,
                        'working_dows' => $hasRecurringRows
                            ? $userSchedules->where('is_day_off', false)->pluck('day_of_week')->map(fn ($d) => (int) $d)->all()
                            : [],
                    ];
                }

                $hasFallbackTherapist = collect($therapistRules)->contains(fn ($r) => $r['fallback_all_dates']);
                $operatingWindows = ($hasFallbackTherapist && $effectiveStart->lte($rangeEnd))
                    ? \App\Services\BusinessScheduleService::getOperatingWindowsForRange($effectiveStart, $rangeEnd)
                    : [];

                if ($effectiveStart->lte($rangeEnd)) {
                    for ($cursor = $effectiveStart->copy(); $cursor->lte($rangeEnd); $cursor->addDay()) {
                        $dow = $cursor->dayOfWeek;
                        $dateStr = $cursor->toDateString();
                        $isBusinessOpen = (bool) ($operatingWindows[$dateStr]['is_open'] ?? true);

                        foreach ($therapistIds as $uid) {
                            $rule = $therapistRules[$uid];
                            $matchesFallback = $rule['fallback_all_dates'] && $isBusinessOpen;
                            $matchesRecurring = in_array($dow, $rule['working_dows'], true);

                            if ($matchesFallback || $matchesRecurring) {
                                $items[] = [
                                    'user_id' => $uid,
                                    'date' => $dateStr,
                                    'exception_type' => $request->input('exception_type'),
                                    'start_time' => $request->input('start_time'),
                                    'end_time' => $request->input('end_time'),
                                    'reason' => $request->input('reason'),
                                ];
                            }
                        }
                    }
                }

                if (empty($items)) {
                    return ['error' => 'No applicable future schedule dates found in the selected date range.'];
                }
            } else {
                $items = $request->exceptions;
            }

            // Never overwrite protected exceptions (leave/day-off/holiday) when a
            // multi-cell edit is saved. Single-cell editing behaviour is unchanged.
            foreach ($items as $item) {
                $protected = ScheduleException::where('user_id', $item['user_id'])
                    ->whereDate('exception_date', $item['date'])
                    ->whereIn('type', ['day_off', 'holiday', 'sick_leave', 'urgent_leave'])
                    ->first();

                if ($protected && $protected->type !== $item['exception_type']) {
                    return [
                        'error' => 'A protected exception exists for therapist ' . $item['user_id']
                            . ' on ' . $item['date'] . ' and was not changed.',
                    ];
                }
            }

            if ($conflict = $this->validateExceptionAppointmentConflict($items)) {
                return ['error' => $conflict];
            }

            foreach ($items as $item) {
                $this->persistException([
                    'user_id' => $item['user_id'],
                    'date' => $item['date'],
                    'exception_type' => $item['exception_type'],
                    'start_time' => $item['start_time'] ?? null,
                    'end_time' => $item['end_time'] ?? null,
                    'reason' => $item['reason'] ?? null,
                ]);
            }

            return ['success' => true];
        });

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Exceptions saved'
        ]);
    }

    public function storeShiftTemplate(Request $request)
    {
        $this->authorizeScheduleEdit();

        $request->validate([
            'name' => 'required|string|max:255',
            'pattern' => 'required|array|size:7',
            'pattern.*.start_time' =>
                'nullable|date_format:H:i',
            'pattern.*.end_time' =>
                'nullable|date_format:H:i',
            'pattern.*.is_day_off' =>
                'required|boolean',
        ]);

        // FIX: Validate that end_time is after start_time when both are provided
        foreach ($request->pattern as $dayIndex => $day) {
            $isDayOff = !empty($day['is_day_off']);
            if (!$isDayOff && !empty($day['start_time']) && !empty($day['end_time'])) {
                try {
                    $start = Carbon::createFromFormat('H:i', $day['start_time']);
                    $end = Carbon::createFromFormat('H:i', $day['end_time']);
                    
                    if ($end->lte($start)) {
                        return response()->json([
                            'success' => false,
                            'message' => "End time must be after start time for day " . ($dayIndex + 1) . "."
                        ], 422);
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => "Invalid time format for day " . ($dayIndex + 1) . "."
                    ], 422);
                }
            }
        }

        $template = ShiftTemplate::create([
            'name' => $request->name,
            'is_active' => true,
            'pattern' => $request->pattern,
        ]);

        return response()->json([
            'success' => true,
            'template' => $template,
            'message' => 'Template created',
        ]);
    }

    public function updateShiftTemplate(
        Request $request,
        ShiftTemplate $template
    ) {
        $this->authorizeScheduleEdit();

        $request->validate([
            'name' => 'required|string|max:255',
            'pattern' => 'required|array|size:7',
            'pattern.*.start_time' =>
                'nullable|date_format:H:i',
            'pattern.*.end_time' =>
                'nullable|date_format:H:i',
            'pattern.*.is_day_off' =>
                'required|boolean',
        ]);

        // FIX: Validate that end_time is after start_time when both are provided
        foreach ($request->pattern as $dayIndex => $day) {
            $isDayOff = !empty($day['is_day_off']);
            if (!$isDayOff && !empty($day['start_time']) && !empty($day['end_time'])) {
                try {
                    $start = Carbon::createFromFormat('H:i', $day['start_time']);
                    $end = Carbon::createFromFormat('H:i', $day['end_time']);
                    
                    if ($end->lte($start)) {
                        return response()->json([
                            'success' => false,
                            'message' => "End time must be after start time for day " . ($dayIndex + 1) . "."
                        ], 422);
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => "Invalid time format for day " . ($dayIndex + 1) . "."
                    ], 422);
                }
            }
        }

        $template->update([
            'name' => $request->name,
            'pattern' => $request->pattern,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template updated'
        ]);
    }

    public function destroyShiftTemplate(
        ShiftTemplate $template
    ) {
        $this->authorizeScheduleEdit();

        $template->update([
            'is_active' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template deleted'
        ]);
    }

    private function authorizeScheduleEdit(): void
    {
        $user = auth()->user();

        if ($user->roles->contains('name', 'admin')) {
            return;
        }

        if (
            $user->roles->contains('name', 'receptionist')
            && $user->can_manage_schedules
        ) {
            return;
        }

        abort(403, 'Unauthorized to edit schedules');
    }

    private function authorizeAdmin(): void
    {
        if (
            !auth()->user()
                ->roles
                ->contains('name', 'admin')
        ) {
            abort(403, 'Admin only');
        }
    }
}