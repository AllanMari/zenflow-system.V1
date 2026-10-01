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

        if ($request->has('week_start')) {
            $weekStart = Carbon::parse($request->get('week_start'));
            $date = $weekStart->copy();
        } else {
            $date = $request->get('date') ? Carbon::parse($request->get('date')) : now();
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
            'weekLabel' => $weekStart->format('M j') . ' – ' . $weekStart->copy()->endOfWeek()->format('M j, Y'),
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

            'generatedAt' => now('Asia/Manila')->format('F d, Y g:i A'),
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

            return 'staff-schedule-'
                . $staff->id
                . '-'
                . $weekStart
                . '.pdf';
        }

        return 'staff-weekly-schedule-'
            . $weekStart
            . '.pdf';
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

        return response()->json([
            'success' => true,
            'message' => $count > 1
                ? "Exception applied across {$count} days"
                : 'Exception added successfully',
        ]);
    }

    public function storeExceptionBulk(Request $request)
    {
        $this->authorizeScheduleEdit();

        $request->validate([
            'exceptions' => 'required|array',
            'exceptions.*.user_id' =>
                'required|exists:users,id',
            'exceptions.*.date' => 'required|date',
            'exceptions.*.exception_type' =>
                'required|in:day_off,holiday,custom_hours',
            'exceptions.*.start_time' =>
                'nullable|required_if:exceptions.*.exception_type,custom_hours|date_format:H:i',
            'exceptions.*.end_time' =>
                'nullable|required_if:exceptions.*.exception_type,custom_hours|after:exceptions.*.start_time',
            'exceptions.*.reason' =>
                'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->exceptions as $item) {
                $this->persistException([
                    'user_id' => $item['user_id'],
                    'date' => $item['date'],
                    'exception_type' =>
                        $item['exception_type'],
                    'start_time' =>
                        $item['start_time'] ?? null,
                    'end_time' =>
                        $item['end_time'] ?? null,
                    'reason' =>
                        $item['reason'] ?? null,
                ]);
            }
        });

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