<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Room;
use App\Models\User;
use App\Models\LandingSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\WorkSchedule;
use App\Models\ScheduleException;
use App\Services\AppointmentOccupancyService;

class BookingController extends Controller
{
    private function timeToMinutes(string $time): int
    {
        [$h, $m] = explode(':', $time);

        return (int) $h * 60 + (int) $m;
    }

    private function isSlotValid(
        $start,
        $end,
        int $duration,
        string $date,
        bool $requiresRoom,
        $services
    ): bool {
        if ($start instanceof Carbon) {
            $slotStart = $start->copy();
        } else {
            $slotStart = Carbon::parse($date . ' ' . $start);
        }

        $slotEnd = $slotStart->copy()->addMinutes($duration);

        if ($end instanceof Carbon) {
            $windowEnd = $end->copy();
        } else {
            $windowEnd = Carbon::parse($date . ' ' . $end);
        }

        return $slotEnd->lte($windowEnd);
    }

    private function buildSlot(
        Carbon $startTime,
        int $duration,
        string $date,
        $staff,
        bool $requiresRoom,
        $services
    ): ?array {
        $endTime = $startTime->copy()->addMinutes($duration);

        $freeRooms = [];

        if ($requiresRoom) {
            $availableRooms = \App\Services\RoomCompatibilityService::findPhysicallyFreeForServices(
                $services,
                $date,
                $startTime->format('H:i:s'),
                $endTime->format('H:i:s')
            );

            foreach ($availableRooms as $room) {
                $freeRooms[] = [
                    'id' => $room->id,
                    'name' => $room->name,
                ];
            }

            if (empty($freeRooms)) {
                return null;
            }
        }

        return [
            'time' => $startTime->format('H:i'),
            'end_time' => $endTime->format('H:i'),
            'display' => $startTime->format('g:i A')
                . ' – '
                . $endTime->format('g:i A'),
            'staff_id' => $staff->id,
            'staff_name' => $staff->full_name
                ?? trim($staff->first_name . ' ' . $staff->last_name),
            'free_rooms' => $freeRooms,
        ];
    }

    // ==================== LANDING PAGE ====================

    public function landing()
    {
        $hero = (object) [
            'title' => LandingSetting::where('key', 'hero_title')
                ->value('value')
                ?? 'Spa Alexandria',
            'subtitle' => LandingSetting::where('key', 'hero_subtitle')
                ->value('value')
                ?? '',
            'image' => LandingSetting::where('key', 'hero_image')
                ->value('value')
                ?? null,
        ];

        $benefits = [
            [
                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                'title' => 'Faster Booking',
                'desc' => 'Save your details and book in seconds.',
            ],
            [
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'title' => 'Track History',
                'desc' => 'View past appointments and preferences.',
            ],
            [
                'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
                'title' => 'Exclusive Offers',
                'desc' => 'Members get special discounts and perks.',
            ],
            [
                'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
                'title' => 'Personalized Care',
                'desc' => 'We remember your favorite treatments.',
            ],
        ];

        $categories = ServiceCategory::with([
            'services' => fn($q) => $q->orderBy('name'),
        ])
            ->orderBy('name')
            ->get();

        $activeNotice = \App\Services\BusinessScheduleService::getActiveNotice();
        $upcomingNotices = \App\Services\BusinessScheduleService::getUpcomingNotices(7);

        return view('landing', compact('hero', 'benefits', 'categories', 'activeNotice', 'upcomingNotices'));
    }

    // ==================== PUBLIC WIZARD ====================

    public function wizard(Request $request)
    {
        $categories = ServiceCategory::where('is_active', true)
            ->whereHas(
                'services',
                fn($q) => $q->where('is_active', true)
            )
            ->with([
                'services' => fn($q) => $q->where('is_active', true)
                    ->select(
                        'id',
                        'category_id',
                        'name',
                        'price',
                        'discount_price',
                        'duration_minutes',
                        'is_package',
                        'included_services',
                        'deposit_percentage_min',
                        'deposit_percentage_max',
                        'description',
                        'image',
                        'landing_description',
                        'requires_room',
                        'room_category_id',
                        'participants_required',
                        'therapists_required'
                    )
                    ->with(['choiceGroups.options.service', 'roomCategory']),
            ])
            ->get();

        $categoriesJson = $categories->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'color' => $cat->color,
                'services' => $cat->services->map(function ($s) use ($cat) {
                    $included = null;

                    if ($s->included_services) {
                        $ids = is_string($s->included_services)
                            ? json_decode($s->included_services, true)
                            : $s->included_services;

                        if ($ids) {
                            $included = Service::whereIn('id', $ids)
                                ->select(
                                    'id',
                                    'name',
                                    'duration_minutes',
                                    'price'
                                )
                                ->get()
                                ->toArray();
                        }
                    }

                    $choiceGroups = [];
                    if ($s->is_package && $s->choiceGroups) {
                        $choiceGroups = $s->choiceGroups->map(function ($cg) {
                            return [
                                'id' => $cg->id,
                                'label' => $cg->label,
                                'name' => $cg->label,
                                'is_required' => (bool) $cg->is_required,
                                'max_selections' => (int) $cg->max_selections,
                                'options' => $cg->options->map(function ($opt) {
                                    return [
                                        'id' => $opt->id,
                                        'service_id' => $opt->service_id,
                                        'name' => $opt->service ? $opt->service->name : '',
                                        'service_name' => $opt->service ? $opt->service->name : '',
                                        'service_duration' => $opt->service ? $opt->service->duration_minutes : null,
                                        'service_price' => $opt->service ? (float) $opt->service->price : 0,
                                    ];
                                })->values()->toArray(),
                            ];
                        })->values()->toArray();
                    }

                    $depMin = $s->deposit_percentage_min
                        ?? $cat->deposit_percentage_min
                        ?? 0;

                    $depMax = $s->deposit_percentage_max
                        ?? $cat->deposit_percentage_max
                        ?? 0;

                    return [
                        'id' => $s->id,
                        'name' => $s->name,
                        'price' => $s->price,
                        'discount_price' => $s->discount_price,
                        'duration_minutes' => $s->duration_minutes,
                        'is_package' => $s->is_package,
                        'included_services' => $included,
                        'choice_groups' => $choiceGroups,
                        'deposit_percentage_min' => (float) $depMin,
                        'deposit_percentage_max' => (float) $depMax,
                        'description' => $s->description,
                        'landing_description' => $s->landing_description,
                        'image' => $s->image_url,
                        'requires_room' => $s->requires_room,
                        'room_category_id' => $s->room_category_id,
                        'room_category_name' => $s->roomCategory ? $s->roomCategory->name : null,
                        'participants_required' => (int) ($s->participants_required ?? 1),
                        'therapists_required' => (int) ($s->therapists_required ?? 1),
                    ];
                }),
            ];
        })->toJson();

        $preselectedIds = [];
        $defaultName = '';
        $defaultPhone = '';
        $customerMedicalNotes = '';

        $user = auth()->user();

        if (
            $user &&
            $user->roles()->where('name', 'customer')->exists()
        ) {
            $customer = Customer::where('user_id', $user->id)->first();

            if ($customer) {
                $defaultName = $customer->nickname
                    ?: $customer->full_name
                    ?: $user->first_name
                    ?: '';

                $defaultPhone = $customer->phone_number ?? '';
                $customerMedicalNotes = $customer->medical_notes ?? '';
            } else {
                $defaultName = $user->first_name ?? '';
            }
        }

        if ($request->has('rebook_from')) {
            $query = Appointment::with('services')
                ->where('id', $request->rebook_from);

            if (
                $user &&
                $user->roles()->where('name', 'customer')->exists()
            ) {
                $query->whereHas(
                    'customer',
                    fn($q) => $q->where('user_id', $user->id)
                );
            } elseif ($user) {
                $query->where('created_by', $user->id);
            }

            $rebook = $query->first();

            if ($rebook) {
                $preselectedIds = $rebook->services
                    ->pluck('id')
                    ->toArray();
            }
        }

        $activeNotice = \App\Services\BusinessScheduleService::getActiveNotice();
        $upcomingNotices = \App\Services\BusinessScheduleService::getUpcomingNotices(7);
        $businessHours = \App\Models\BusinessHour::all();
        $businessExceptions = \App\Models\BusinessException::whereDate('date', '>=', now('Asia/Manila')->startOfDay()->toDateString())->get();

        return view(
            'booking.wizard',
            compact(
                'categoriesJson',
                'preselectedIds',
                'defaultName',
                'defaultPhone',
                'customerMedicalNotes',
                'activeNotice',
                'upcomingNotices',
                'businessHours',
                'businessExceptions'
            )
        );
    }

    // ==================== RECEPTIONIST QUICK BOOK ====================

    public function quickBook(Request $request)
    {
        $user = auth()->user();

        if (
            !$user ||
            !$user->roles()->where('name', 'receptionist')->exists()
        ) {
            abort(403);
        }

        $categories = ServiceCategory::where('is_active', true)
            ->whereHas(
                'services',
                fn($q) => $q->where('is_active', true)
            )
            ->with([
                'services' => fn($q) => $q->where('is_active', true)
                    ->select(
                        'id',
                        'category_id',
                        'name',
                        'price',
                        'discount_price',
                        'duration_minutes',
                        'is_package',
                        'requires_room',
                        'room_category_id',
                        'therapists_required',
                        'participants_required'
                    )
                    ->with(['choiceGroups.options.service']),
            ])
            ->get();

        $staff = User::whereHas(
            'roles',
            fn($q) => $q->where('name', 'staff')
        )
            ->where('is_active', true)
            ->get();

        $rooms = Room::active()
            ->where('status', '!=', 'maintenance')
            ->get();

        $today = Carbon::today('Asia/Manila')->format('Y-m-d');

        return view(
            'receptionist.quick-book',
            compact('categories', 'staff', 'rooms', 'today')
        );
    }

    // ==================== API: SLOTS ====================

    public function getSlots(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
            'duration' => 'nullable|integer|min:15',
            'services' => 'nullable|array',
            'services.*' => 'exists:services,id',
        ]);

        $duration = (int) $request->get('duration', 60);
        $serviceIds = $request->get('services', []);
        $specificDate = $request->get('date');

        $services = empty($serviceIds)
            ? collect()
            : Service::whereIn('id', $serviceIds)->get();

        $requiresRoom = $services->contains(
            fn($s) => $s->requires_room
        );

        $rooms = $requiresRoom ? Room::findCompatibleForServices($services) : collect();
        $roomAppointments = collect();

        $tz = 'Asia/Manila';
        $now = Carbon::now($tz);
        $slots = [];

        if ($specificDate) {
            $startDate = Carbon::parse($specificDate, $tz)->startOfDay();
            $endDate = $startDate->copy();
        } else {
            $startDate = Carbon::today($tz);
            $endDate = $startDate->copy()->addDays(14);
        }

        /*
         * Only load active staff because the available booking
         * windows are now derived from actual staff schedules.
         */
        $staffMembers = User::whereHas(
            'roles',
            fn($q) => $q->where('name', 'staff')
        )
            ->where('is_active', true)
            ->get();

        $dateList = [];
        for (
            $d = $startDate->copy();
            $d->lte($endDate);
            $d->addDay()
        ) {
            $dateList[] = $d->format('Y-m-d');
        }

        /*
         * Load appointments ONCE for the whole requested range. Occupancy
         * intervals are derived in memory by AppointmentOccupancyService.
         */
        $rangeAppointments = collect();
        if (!empty($dateList)) {
            $rangeAppointments = Appointment::whereDate('appointment_date', '>=', $startDate->toDateString())
                ->whereDate('appointment_date', '<=', $endDate->toDateString())
                ->whereIn('status', ['confirmed', 'completed', 'pending'])
                ->with(['services', 'assignedStaff'])
                ->get();
        }

        $appointmentsByDate = $rangeAppointments->groupBy(
            fn($appointment) => $appointment->appointment_date->toDateString()
        );

        for (
            $date = $startDate->copy();
            $date->lte($endDate);
            $date->addDay()
        ) {
            $dateStr = $date->format('Y-m-d');

            /*
             * Business operating hours check (outer boundary gatekeeper)
             */
            $bizWindow = \App\Services\BusinessScheduleService::getOperatingWindow($dateStr);
            if (!$bizWindow['is_open']) {
                continue;
            }

            $staffWindows = [];
            foreach ($staffMembers as $staff) {
                $window = $this->getStaffWorkWindow($staff->id, $dateStr);
                if ($window) {
                    $staffWindows[$staff->id] = $window;
                }
            }

            if (empty($staffWindows)) {
                continue;
            }

            // The wizard does not choose therapists: a time is valid when the
            // required number of staff members (derived from the services) is
            // simultaneously free, including the preparation window.
            $minLeadTime = $request->boolean('receptionist') ? 0 : 30;
            $minStart = $dateStr === $now->format('Y-m-d')
                ? $now->copy()->addMinutes($minLeadTime)
                : null;

            $daySlots = app(\App\Services\AppointmentAvailabilityService::class)->availableTimes([
                'date' => $dateStr,
                'services' => $services,
                'duration' => $duration,
                'staff_ids' => array_keys($staffWindows),
                'staff_windows' => $staffWindows,
                'team_size' => max(1, (int) $services->max('therapists_required')),
                'any_staff' => true,
                'appointments' => $appointmentsByDate->get($dateStr, collect()),
                'biz_window' => $bizWindow,
                'min_start' => $minStart,
                'compatible_rooms' => $rooms,
                // The public wizard still shows times with a clear
                // "no compatible room" state so diagnostics can explain why.
                'allow_no_room' => true,
            ]);

            foreach ($daySlots as $slot) {
                $slot['date'] = $dateStr;
                $slot['compatible_rooms'] = $slot['free_rooms'];
                $slots[] = $slot;
            }
        }

        /*
         * Prevent duplicate times when multiple staff schedules
         * overlap. Keep the first generated room information.
         */
        $uniqueSlots = [];

        foreach ($slots as $slot) {
            $key = $slot['date'] . '|' . $slot['time'];

            if (!isset($uniqueSlots[$key])) {
                $uniqueSlots[$key] = $slot;
            } elseif (
                !$uniqueSlots[$key]['room_available'] &&
                $slot['room_available']
            ) {
                $uniqueSlots[$key] = $slot;
            }
        }

        $slots = array_values($uniqueSlots);

        usort(
            $slots,
            function ($a, $b) {
                $dateCompare = strcmp(
                    $a['date'],
                    $b['date']
                );

                if ($dateCompare !== 0) {
                    return $dateCompare;
                }

                return strcmp(
                    $a['time'],
                    $b['time']
                );
            }
        );

        $diagnostics = \App\Services\BookingDiagnosticService::diagnose(
            $services,
            $specificDate,
            $duration,
            $slots
        );

        return response()->json([
            'slots' => $slots,
            'requires_room' => $requiresRoom,
            'room_count' => $rooms->count(),
            'diagnostics' => $diagnostics,
        ]);
    }

    // ==================== API: CUSTOMER AUTOCOMPLETE ====================

    public function customerLookup(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:6',
        ]);

        $customers = Customer::where(
            'phone_number',
            'like',
            '%' . $request->phone . '%'
        )
            ->limit(5)
            ->get([
                'id',
                'first_name',
                'last_name',
                'phone_number',
                'nickname',
                'medical_notes',
                'user_id',
            ]);

        return response()->json(
            $customers->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->nickname
                        ?: $customer->full_name,
                    'real_name' => $customer->full_name,
                    'nickname' => $customer->nickname,
                    'phone' => $customer->phone_number,
                    'medical_notes' => $customer->medical_notes ?? '',
                    'is_registered' => !is_null(
                        $customer->user_id
                    ),
                ];
            })
        );
    }

    // ==================== API: NEXT AVAILABLE SLOT ====================

    public function nextAvailableSlot(Request $request)
    {
        $request->validate([
            'duration' => 'required|integer|min:15',
            'services' => 'required|array',
            'services.*' => 'exists:services,id',
        ]);

        $duration = (int) $request->duration;
        $serviceIds = $request->services;

        $slotsRes = $this->getSlotsInternal(
            $duration,
            $serviceIds,
            true
        );

        $slots = $slotsRes['slots'];

        $nextSlot = collect($slots)
            ->first(fn($s) => $s['room_available']);

        if (!$nextSlot) {
            return response()->json([
                'slot' => null,
                'message' => 'No slots available',
            ]);
        }

        $date = $nextSlot['date'];
        $time = $nextSlot['time'];
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        $availableStaff = User::whereHas(
            'roles',
            fn($q) => $q->where('name', 'staff')
        )
            ->where('is_active', true)
            ->get()
            ->filter(function ($staff) use (
                $date,
                $dayOfWeek,
                $time,
                $duration
            ) {
                /*
                 * Use the same schedule/exception logic as the
                 * rest of the booking system.
                 */
                $window = $this->getStaffWorkWindow(
                    $staff->id,
                    $date
                );

                if (!$window) {
                    return false;
                }

                $slotEndTime = Carbon::parse($time)
                    ->addMinutes($duration)
                    ->format('H:i');

                if (
                    $time < $window['start'] ||
                    $slotEndTime > $window['end']
                ) {
                    return false;
                }

                $conflict = app(AppointmentOccupancyService::class)->hasStaffConflict(
                    (int) $staff->id,
                    $date,
                    $time,
                    $slotEndTime
                );

                return !$conflict;
            })
            ->map(
                fn($s) => [
                    'id' => $s->id,
                    'name' => $s->full_name,
                ]
            )
            ->values();

        return response()->json([
            'slot' => $nextSlot,
            'available_staff' => $availableStaff,
        ]);
    }

    private function getSlotsInternal(
        int $duration,
        array $serviceIds,
        bool $isReceptionist = false
    ) {
        /*
         * FIX:
         * The previous code used whereIn($serviceIds), which is
         * missing the column name.
         */
        $services = empty($serviceIds)
            ? collect()
            : Service::whereIn('id', $serviceIds)->get();

        $requiresRoom = $services->contains(
            fn($s) => $s->requires_room
        );

        $rooms = $requiresRoom ? Room::findCompatibleForServices($services) : collect();
        $roomAppointments = collect();

        $tz = 'Asia/Manila';
        $now = Carbon::now($tz);
        $slots = [];

        $startDate = Carbon::today($tz);
        $endDate = $startDate->copy()->addDays(14);

        /*
         * Active staff schedules are now the source of the
         * available booking windows.
         */
        $staffMembers = User::whereHas(
            'roles',
            fn($q) => $q->where('name', 'staff')
        )
            ->where('is_active', true)
            ->get();

        if ($requiresRoom) {
            $dateList = [];

            for (
                $d = $startDate->copy();
                $d->lte($endDate);
                $d->addDay()
            ) {
                $dateList[] = $d->format('Y-m-d');
            }

            if (!empty($dateList)) {
                $roomAppointments = Appointment::whereIn(
                    'appointment_date',
                    $dateList
                )
                    ->whereIn(
                        'status',
                        ['confirmed', 'completed', 'pending']
                    )
                    ->whereNotNull('room_id')
                    ->get([
                        'appointment_date',
                        'room_id',
                        'start_time',
                        'end_time',
                    ])
                    ->groupBy('appointment_date')
                    ->map(
                        fn($group) => $group->groupBy('room_id')
                    );
            }
        }

        for (
            $date = $startDate->copy();
            $date->lte($endDate);
            $date->addDay()
        ) {
            $dateStr = $date->format('Y-m-d');

            /*
             * Business operating hours check (outer boundary gatekeeper)
             */
            $bizWindow = \App\Services\BusinessScheduleService::getOperatingWindow($dateStr);
            if (!$bizWindow['is_open']) {
                continue;
            }

            /*
             * Collect all staff schedule windows for this date.
             */
            $windows = [];

            foreach ($staffMembers as $staff) {
                $window = $this->getStaffWorkWindow(
                    $staff->id,
                    $dateStr
                );

                if (!$window) {
                    continue;
                }

                $windows[] = [
                    'start' => $window['start'],
                    'end' => $window['end'],
                ];
            }

            if (empty($windows)) {
                continue;
            }

            /*
             * Merge overlapping/touching windows.
             */
            usort(
                $windows,
                fn($a, $b) => strcmp($a['start'], $b['start'])
            );

            $mergedWindows = [];

            foreach ($windows as $window) {
                if (empty($mergedWindows)) {
                    $mergedWindows[] = $window;
                    continue;
                }

                $lastIndex = count($mergedWindows) - 1;
                $last = $mergedWindows[$lastIndex];

                if ($window['start'] <= $last['end']) {
                    if ($window['end'] > $last['end']) {
                        $mergedWindows[$lastIndex]['end'] =
                            $window['end'];
                    }
                } else {
                    $mergedWindows[] = $window;
                }
            }

            $dayAppointments =
                $roomAppointments[$dateStr] ?? null;

            foreach ($mergedWindows as $window) {
                $wStart = max($window['start'], $bizWindow['start'] ?? '00:00');
                $wEnd = min($window['end'], $bizWindow['end'] ?? '23:59');

                if ($wStart >= $wEnd) {
                    continue;
                }

                $open = Carbon::parse(
                    $dateStr . ' ' . $wStart,
                    $tz
                );

                $close = Carbon::parse(
                    $dateStr . ' ' . $wEnd,
                    $tz
                );

                while ($open->lte($close)) {
                    $slotEnd =
                        $open->copy()->addMinutes($duration);

                    $minLeadTime =
                        $isReceptionist ? 0 : 30;

                    $minimumStart = $now
                        ->copy()
                        ->addMinutes($minLeadTime);

                    $isToday =
                        $dateStr === $now->format('Y-m-d');

                    $timeIsValid =
                        !$isToday ||
                        $open->gt($minimumStart);

                    if (
                        $slotEnd->lte($close) &&
                        $timeIsValid
                    ) {
                        $roomAvailable = true;
                        $reviewRequired = false;
                        $freeRooms = [];

                        // A slot is only bookable when at least one therapist is free
                        // for the whole treatment plus preparation window.
                        $slotStartStr = $open->format('H:i:s');
                        $slotEndStr = $slotEnd->format('H:i:s');
                        $hasFreeStaff = $staffMembers->contains(function ($staff) use ($dateStr, $slotStartStr, $slotEndStr) {
                            return !app(AppointmentOccupancyService::class)->hasStaffConflict(
                                (int) $staff->id,
                                $dateStr,
                                $slotStartStr,
                                $slotEndStr
                            );
                        });

                        if (!$hasFreeStaff) {
                            $roomAvailable = false;
                        }

                        if ($requiresRoom && $hasFreeStaff) {
                            $eval = \App\Services\RoomCompatibilityService::evaluateSlot($services, $dateStr, $slotStartStr, $slotEndStr);
                            
                            $roomAvailable = $eval['room_available'];
                            $freeRooms = $eval['compatible_rooms']->map(fn($r) => [
                                'id' => $r->id,
                                'name' => $r->name,
                            ])->toArray();
                        }

                        $slots[] = [
                            'date' => $dateStr,
                            'time' => $open->format('H:i'),
                            'display' =>
                                $open->format('g:i A'),
                            'room_available' =>
                                $roomAvailable,
                            'compatible_rooms' =>
                                $freeRooms,
                            'free_rooms' =>
                                $freeRooms,
                        ];
                    }

                    $open->addMinutes(30);
                }
            }
        }

        /*
         * Remove duplicate date/time entries caused by
         * overlapping staff schedules.
         */
        $uniqueSlots = [];

        foreach ($slots as $slot) {
            $key =
                $slot['date'] .
                '|' .
                $slot['time'];

            if (!isset($uniqueSlots[$key])) {
                $uniqueSlots[$key] = $slot;
            } elseif (
                !$uniqueSlots[$key]['room_available'] &&
                $slot['room_available']
            ) {
                $uniqueSlots[$key] = $slot;
            }
        }

        $slots = array_values($uniqueSlots);

        usort(
            $slots,
            function ($a, $b) {
                $dateCompare = strcmp(
                    $a['date'],
                    $b['date']
                );

                if ($dateCompare !== 0) {
                    return $dateCompare;
                }

                return strcmp(
                    $a['time'],
                    $b['time']
                );
            }
        );

        return [
            'slots' => $slots,
            'requires_room' => $requiresRoom,
            'room_count' => $rooms->count(),
        ];
    }

    // ==================== AVAILABILITY / CONFLICT HELPERS ====================

    private function validateBusinessHours(
        string $date,
        Carbon $startTime,
        Carbon $endTime
    ): ?string {
        if ($endTime->lte($startTime)) {
            return 'The appointment end time must be later than the start time.';
        }

        $bizWindow = \App\Services\BusinessScheduleService::getOperatingWindow($date);
        if (!$bizWindow['is_open']) {
            return $bizWindow['reason'] ?: 'The spa is closed on this date.';
        }

        if ($bizWindow['start'] && $bizWindow['end']) {
            $bizOpen = Carbon::parse($date . ' ' . $bizWindow['start']);
            $bizClose = Carbon::parse($date . ' ' . $bizWindow['end']);

            if ($startTime->lt($bizOpen) || $endTime->gt($bizClose)) {
                return 'The selected time (' . $startTime->format('g:i A') . ' - ' . $endTime->format('g:i A') . ') falls outside the business operating hours (' . $bizOpen->format('g:i A') . ' - ' . $bizClose->format('g:i A') . ').';
            }
        }

        return null;
    }

    private function getStaffWorkWindow(
        int $staffId,
        string $date,
        bool $allowCheckedInExtension = false
    ): ?array {
        // Shared with reschedule/reassign/add-service availability. The
        // post-shift extension is enabled only by Right Now callers.
        return app(\App\Services\ScheduleWindowService::class)->workWindow(
            $staffId,
            $date,
            $allowCheckedInExtension
        );
    }

    private function isStaffAvailable(
        int $staffId,
        string $date,
        string $startTime,
        string $endTime
    ): bool {
        $window = $this->getStaffWorkWindow(
            $staffId,
            $date
        );

        if (!$window) {
            return false;
        }

        $start = substr($startTime, 0, 5);
        $end = substr($endTime, 0, 5);

        if (
            $start < $window['start'] ||
            $end > $window['end']
        ) {
            return false;
        }

        return !app(AppointmentOccupancyService::class)->hasStaffConflict(
            $staffId,
            $date,
            $start,
            $end
        );
    }

    /**
     * Authoritative validation of one receptionist-selected therapist.
     * Called inside the booking transaction after row locks are held; the
     * conflict check uses a current (locking) read.
     */
    private function validateReceptionistTherapist(
        User $therapist,
        string $date,
        string $startTime,
        string $endTime,
        bool $walkInNow
    ): ?string {
        $check = \App\Services\TherapistAssignmentService::checkTherapistAvailability(
            $therapist,
            $date,
            $startTime,
            $endTime,
            null,
            true
        );

        if (!$check['available']) {
            return $check['reason'] ?? 'unavailable';
        }

        // Right Now keeps its existing check-in requirement.
        if ($walkInNow
            && $date === Carbon::today('Asia/Manila')->format('Y-m-d')
            && !$this->isStaffCurrentlyCheckedIn((int) $therapist->id, $date)) {
            return 'not currently checked in.';
        }

        return null;
    }

    /**
     * Determine whether the staff member is currently checked in.
     *
     * This is used only by Receptionist Quick Book / Right Now.
     */
    private function isStaffCurrentlyCheckedIn(
        int $staffId,
        string $date
    ): bool {
        return app(\App\Services\ScheduleWindowService::class)->isCheckedIn($staffId, $date);
    }

    /**
     * Quick Book / Right Now staff availability.
     *
     * TODAY:
     * - Staff must be scheduled.
     * - Staff must have checked in.
     * - Staff must not have checked out.
     * - Leave/day-off/custom schedule rules remain respected.
     * - Staff may continue receiving a Quick Book after the
     *   scheduled shift has ended while they remain checked in.
     * - Appointment conflicts still block the slot.
     *
     * FUTURE:
     * - Normal schedule-based availability is used.
     *
     * This helper does NOT affect staffGaps().
     */
    private function isQuickBookStaffAvailable(
        int $staffId,
        string $date,
        string $startTime,
        string $endTime
    ): bool {
        $tz = 'Asia/Manila';

        $dateCarbon = Carbon::parse($date, $tz);
        $today = Carbon::today($tz);

        $isToday = $dateCarbon->isSameDay($today);

        /*
         * Future bookings continue using the normal schedule.
         */
        if (!$isToday) {
            return $this->isStaffAvailable(
                $staffId,
                $date,
                $startTime,
                $endTime
            );
        }

        /*
         * A Quick Book / Right Now appointment requires
         * an active check-in with no checkout.
         */
        if (!$this->isStaffCurrentlyCheckedIn($staffId, $date)) {
            return false;
        }

        /*
         * getStaffWorkWindow() also handles:
         * - day off
         * - holiday
         * - sick leave
         * - urgent leave
         * - custom hours
         */
        $window = $this->getStaffWorkWindow(
            $staffId,
            $date,
            true // Right Now may continue after shift end while checked in.
        );

        if (!$window) {
            return false;
        }

        /*
         * Once the staff member is checked in, their scheduled
         * end does not independently block a Right Now booking.
         */
        $start = substr($startTime, 0, 5);
        $end = substr($endTime, 0, 5);

        /*
         * Do not allow a Quick Book appointment in the past.
         */
        $now = Carbon::now($tz);

        $appointmentStart = Carbon::parse(
            $date . ' ' . $start,
            $tz
        );

        if ($appointmentStart->lt($now)) {
            return false;
        }

        /*
         * Existing appointment conflicts remain blocking.
         */
        $hasConflict = app(AppointmentOccupancyService::class)->hasStaffConflict(
            $staffId,
            $date,
            $start,
            $end
        );

        return !$hasConflict;
    }

    private function findFreeRoom(
        $services,
        string $date,
        string $startTime,
        string $endTime
    ): ?Room {
        $compatibleRooms = Room::findCompatibleForServices($services);
        if ($compatibleRooms->isEmpty()) {
            return null;
        }

        return Room::lockForUpdate()
            ->whereIn('id', $compatibleRooms->pluck('id'))
            ->get()
            ->first(
                fn($room) => $room->isAvailableFor(
                    $date,
                    $startTime,
                    $endTime
                )
            );
    }

    /**
     * Internal therapist availability statuses for the receptionist dropdowns.
     * Uses the same schedule, attendance, exception and occupancy rules as the
     * actual booking validation so the UI can never contradict the backend.
     */
    public function preferredTherapists(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'services' => 'required|array|min:1',
            'services.*' => 'integer|exists:services,id',
        ]);

        $services = Service::whereIn('id', $validated['services'])
            ->where('is_active', true)
            ->get();

        if ($services->isEmpty()) {
            return response()->json(['message' => 'Selected services are invalid.'], 422);
        }

        $duration = max(1, (int) $services->sum('duration_minutes'));
        $requiredCount = max(1, (int) $services->max('therapists_required'));

        $therapists = \App\Services\TherapistAssignmentService::customerAvailability(
            $validated['date'],
            $validated['start_time'],
            $duration
        );

        return response()->json([
            'required_count' => $requiredCount,
            'therapists' => $therapists,
        ]);
    }

    public function therapistStatuses(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'duration' => 'nullable|integer|min:1',
            'start_time' => 'nullable|date_format:H:i',
            'services' => 'nullable|array',
            'services.*' => 'integer|exists:services,id',
            'exclude_appointment_id' => 'nullable|integer',
        ]);

        $tz = 'Asia/Manila';

        // When an appointment is supplied, derive the requested window from it
        // and exclude it from its own conflict check. No customer data is returned.
        $occupancy = app(AppointmentOccupancyService::class);
        $requiredCount = null;

        if (!empty($validated['appointment_id'])) {
            $appointment = Appointment::with('services')->findOrFail($validated['appointment_id']);
            $validated['date'] = $appointment->appointment_date->toDateString();
            $validated['start_time'] = substr((string) $appointment->start_time, 0, 5);
            // Snapshot durations (including added services) are authoritative.
            $duration = $occupancy->treatmentEnd($appointment)
                ->diffInMinutes($occupancy->appointmentStart($appointment));
            if ($duration <= 0) {
                $duration = max(1, Carbon::parse($appointment->end_time)
                    ->diffInMinutes(Carbon::parse($appointment->start_time)));
            }
            $validated['duration'] = $duration;
            $validated['exclude_appointment_id'] = $appointment->id;
            $requiredCount = \App\Services\TherapistAssignmentService::resolveRequiredCount($appointment);
        }

        if (empty($validated['date'])) {
            return response()->json(['message' => 'A date or appointment is required.'], 422);
        }

        $date = Carbon::parse($validated['date'], $tz)->format('Y-m-d');
        $duration = (int) ($validated['duration'] ?? 60);
        $isToday = $date === Carbon::today($tz)->format('Y-m-d');
        $excludeId = $validated['exclude_appointment_id'] ?? null;

        $staffMembers = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->where('is_active', true)
            ->get();

        $statuses = $staffMembers->map(function ($staff) use ($date, $duration, $isToday, $excludeId, $occupancy, $validated) {
            $base = [
                'staff_id' => (int) $staff->id,
                'status' => 'available',
                'label' => 'Available',
                'reason' => null,
                'hours' => null,
                'existing_start' => null,
                'existing_end' => null,
            ];

            $exception = ScheduleException::where('user_id', $staff->id)
                ->whereDate('exception_date', $date)
                ->first();

            if ($exception && in_array($exception->type, ['sick_leave', 'urgent_leave'], true)) {
                return array_merge($base, [
                    'status' => 'on_leave',
                    'label' => 'On Leave',
                    'reason' => $exception->reason ?: 'Approved leave',
                ]);
            }

            if ($exception && in_array($exception->type, ['day_off', 'holiday'], true)) {
                return array_merge($base, [
                    'status' => 'day_off',
                    'label' => $exception->type === 'holiday' ? 'Holiday' : 'Day Off',
                    'reason' => $exception->reason,
                ]);
            }

            $window = $this->getStaffWorkWindow($staff->id, $date);
            $hours = $exception && $exception->type === 'custom_hours'
                ? $this->extractTime($exception->start_time) . '-' . $this->extractTime($exception->end_time)
                : ($window ? $window['start'] . '-' . $window['end'] : null);

            if (!$window) {
                return array_merge($base, [
                    'status' => 'day_off',
                    'label' => 'Day Off',
                    'reason' => 'No shift scheduled for this date.',
                    'hours' => null,
                ]);
            }

            if ($isToday && !$this->isStaffCurrentlyCheckedIn($staff->id, $date)) {
                return array_merge($base, [
                    'status' => 'not_checked_in',
                    'label' => 'Not Checked In',
                    'reason' => 'Scheduled today but not yet checked in.',
                    'hours' => $hours,
                ]);
            }

            if ($isToday) {
                $attendanceBlocker = Attendance::where('user_id', $staff->id)
                    ->whereDate('date', $date)
                    ->whereIn('status', ['absent', 'on_leave', 'holiday'])
                    ->first();
                if ($attendanceBlocker) {
                    return array_merge($base, [
                        'status' => 'on_leave',
                        'label' => $attendanceBlocker->status === 'absent' ? 'Absent' : 'On Leave',
                        'reason' => 'Attendance: ' . $attendanceBlocker->status,
                        'hours' => $hours,
                    ]);
                }
            }

            $requestedStart = $validated['start_time'] ?? $window['start'];
            $treatmentEnd = Carbon::parse($date . ' ' . $requestedStart)->addMinutes($duration);
            $requestedEnd = $treatmentEnd->format('H:i');
            $resourceEnd = $treatmentEnd
                ->addMinutes(AppointmentOccupancyService::PREPARATION_MINUTES)
                ->format('H:i');

            // The therapist is occupied during preparation as well.
            if ($requestedStart < $window['start'] || $resourceEnd > $window['end']) {
                return array_merge($base, [
                    'status' => 'outside_shift',
                    'label' => 'Outside Shift',
                    'reason' => 'Scheduled hours: ' . $hours,
                    'hours' => $hours,
                ]);
            }

            $conflicts = $occupancy->staffConflicts(
                (int) $staff->id,
                $date,
                $requestedStart,
                $requestedEnd,
                $excludeId ? (int) $excludeId : null
            );

            if ($conflicts->isNotEmpty()) {
                $existing = $conflicts->first();

                return array_merge($base, [
                    'status' => 'busy',
                    'label' => 'Busy',
                    'reason' => 'Existing appointment: ' . Carbon::parse($existing->start_time)->format('g:i A')
                        . '–' . Carbon::parse($existing->end_time)->format('g:i A'),
                    'hours' => $hours,
                    'existing_start' => $existing->start_time,
                    'existing_end' => $existing->end_time,
                ]);
            }

            return array_merge($base, ['hours' => $hours]);
        })->values();

        return response()->json([
            'statuses' => $statuses,
            'required_count' => $requiredCount,
        ]);
    }

    /**
     * Final, lock-protected resource validation. Called INSIDE the booking
     * transaction so two simultaneous bookings cannot reserve the same
     * therapist or room. Locks are always acquired in the same order:
     * therapists (ascending id), then room.
     */
    private function lockAndValidateResources(
        Request $request,
        $services,
        Carbon $startTime,
        Carbon $endTime,
        $assignedStaffId,
        array $assistingStaffIds,
        $roomId,
        callable $fail,
        array $preferredStaffIds = []
    ) {
        $tz = 'Asia/Manila';
        $date = $startTime->format('Y-m-d');
        $startStr = $startTime->format('H:i:s');
        $endStr = $endTime->format('H:i:s');

        $bizError = $this->validateBusinessHours($date, $startTime, $endTime);
        if ($bizError) {
            throw new \RuntimeException($bizError);
        }

        $therapistIds = array_values(array_unique(array_filter(
            array_merge(
                [(int) $assignedStaffId],
                array_map('intval', $assistingStaffIds),
                array_map('intval', $preferredStaffIds)
            )
        )));

        $requiredCount = app(\App\Services\AppointmentAvailabilityService::class)
            ->requirements($services)['therapists_required'];

        if ($assignedStaffId && count($therapistIds) !== $requiredCount) {
            throw new \RuntimeException("This appointment requires exactly {$requiredCount} therapist(s).");
        }

        // Lock in deterministic order to avoid deadlocks: therapists (ascending id), then room.
        sort($therapistIds, SORT_NUMERIC);
        $lockedUsers = collect();
        if (!empty($therapistIds)) {
            $lockedUsers = User::whereIn('id', $therapistIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
        }
        if (($assignedStaffId || !empty($preferredStaffIds)) && $lockedUsers->count() !== count($therapistIds)) {
            throw new \RuntimeException('One or more selected therapists could not be found.');
        }

        $requiresRoom = $services->contains(fn($service) => (bool) $service->requires_room);
        $lockedRoom = null;
        $compatibleRoomIds = collect();

        if ($requiresRoom) {
            $eval = \App\Services\RoomCompatibilityService::evaluateSlot(
                $services,
                $date,
                $startStr,
                $endStr
            );

            if (!$eval['room_available'] || $eval['compatible_rooms']->isEmpty()) {
                throw new \RuntimeException('No compatible rooms available for this time. Please choose a different time.');
            }

            $compatibleRoomIds = $eval['compatible_rooms']->pluck('id');
            $targetRoomId = $roomId ? (int) $roomId : (int) $compatibleRoomIds->first();

            $lockedRoom = Room::where('id', $targetRoomId)->lockForUpdate()->first();
            if (!$lockedRoom || !$lockedRoom->is_active || $lockedRoom->status === 'maintenance'
                || !$compatibleRoomIds->contains($targetRoomId)) {
                throw new \RuntimeException('The selected room is not compatible with all selected services or is no longer available. Please pick another slot.');
            }
        }

        // Revalidate after ALL resource locks are held. These are current
        // (locking) reads, so appointments committed while we waited are seen
        // even under MySQL REPEATABLE READ.
        $occupancy = app(AppointmentOccupancyService::class);
        if ($assignedStaffId) {
            $walkInNow = $request->boolean('walk_in_now');
            foreach ($therapistIds as $therapistId) {
                $therapist = $lockedUsers->get($therapistId);
                if (!$therapist) {
                    throw new \RuntimeException('One or more selected therapists could not be found.');
                }

                // Validate the COMPLETE team, not only the primary therapist:
                // role, active status, leave, shift, conflicts and the buffer.
                $reason = $this->validateReceptionistTherapist(
                    $therapist,
                    $date,
                    $startStr,
                    $endStr,
                    $walkInNow
                );
                if ($reason) {
                    throw new \RuntimeException('Selected therapist ' . $therapist->full_name . ' is not available: ' . $reason);
                }
            }
        } elseif (!empty($preferredStaffIds)) {
            foreach ($preferredStaffIds as $prefId) {
                $prefCheck = \App\Services\TherapistAssignmentService::checkTherapistAvailability(
                    $lockedUsers->get($prefId),
                    $date,
                    $startStr,
                    $endStr,
                    null,
                    true
                );
                if (!$prefCheck['available']) {
                    throw new \RuntimeException('One of your selected preferred therapists is no longer available for this time. Please refresh and choose another or continue with No preference.');
                }
            }
        } else {
            $hasAvailableStaff = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
                ->where('is_active', true)
                ->get()
                ->contains(fn($staff) => !$occupancy->hasStaffConflict((int) $staff->id, $date, $startStr, $endStr, null, true));

            if (!$hasAvailableStaff) {
                throw new \RuntimeException('Sorry — no available staff at this hour (all staff are occupied or off-shift). Please choose a different time slot.');
            }
        }

        if ($lockedRoom) {
            // Physical availability must be rechecked while the room lock is held.
            if (!$lockedRoom->isAvailableFor($date, $startStr, $endStr, null, true)) {
                throw new \RuntimeException('The selected room is no longer available for this time. Please pick another slot.');
            }

            $request->merge(['room_id' => (int) $lockedRoom->id]);
        }

        return null;
    }

    // ==================== STORE ====================

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'services' => 'required|array|min:1',
            'services.*' => 'exists:services,id',
            'preferred_staff_ids' => 'nullable|array',
            'preferred_staff_ids.*' => 'integer|exists:users,id',
            'appointment_date' =>
                'required|date|after_or_equal:today',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'guest_first_name' =>
                'required|string|max:100',
            'guest_phone' =>
                'required|string|regex:/^09\d{9}$/',
            'medical_notes' =>
                'nullable|string|max:2000',
            'treatment_notes' =>
                'nullable|string|max:2000',
            'notes' =>
                'nullable|string|max:2000',
            'staff_id' =>
                'nullable|exists:users,id',
            'room_id' =>
                'nullable|exists:rooms,id',
            'payment_method' =>
                'nullable|in:cash,card,gcash,paymaya,bank_transfer',
            'payment_amount' =>
                'nullable|numeric|min:0',
            'payment_type' =>
                'nullable|in:full,deposit',
            'walk_in_now' =>
                'nullable|boolean',
            'source' =>
                'nullable|in:public,receptionist',
        ]);

        $user = auth()->user();

        $isReceptionist = $user &&
            $user->roles()->where('name', 'receptionist')->exists();

        $isCustomer = $user &&
            $user->roles()->where('name', 'customer')->exists();

        $source = $request->get('source', 'public');

        $walkInNow = $request->boolean('walk_in_now');

        if ($user) {
            session([
                'user_role' => strtolower(
                    $user->roles()->first()->name ?? 'customer'
                ),
            ]);
        } else {
            session([
                'user_role' => 'guest',
            ]);
        }

        $services = Service::whereIn(
            'id',
            $request->services
        )->get();

        $startTime = Carbon::parse(
            $request->appointment_date
                . ' '
                . $request->start_time,
            'Asia/Manila'
        );

        $totalDuration =
            (int) $services->sum('duration_minutes');

        // The server is authoritative for treatment duration. A client-supplied
        // end_time must never be able to shrink or extend the conflict window.
        $endTime = $startTime
            ->copy()
            ->addMinutes($totalDuration);

        // If the parsed end time is earlier than the start time, 
        // it means the appointment crossed midnight into the next day.
        if ($endTime->lt($startTime)) {
            $endTime->addDay();
        }

        $totalPrice = $services->sum(
            fn($s) => $s->discount_price ?? $s->price
        );

        $tz = 'Asia/Manila';

        $requiresRoom = $services->contains(
            fn($s) => $s->requires_room
        );

        $startTimeStr =
            $startTime->format('H:i:s');

        $endTimeStr =
            $endTime->format('H:i:s');

        $fail = function (string $message) use ($request) {
            if (
                $request->ajax() ||
                $request->wantsJson() ||
                $request->get('source') === 'receptionist'
            ) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', $message);
        };

        // Validate services: draft or inactive services cannot be booked
        foreach ($services as $svc) {
            if (!$svc->is_active || ($svc->duration_minutes === null && !$svc->is_package)) {
                return $fail('The service "' . $svc->name . '" is not currently available for booking.');
            }
        }

        if ($totalDuration <= 0) {
            return $fail('The selected services do not have a valid treatment duration.');
        }

        // Validate package choice groups: exactly one option must be selected for each required group
        $validatedPackageChoices = [];
        foreach ($services as $svc) {
            if ($svc->is_package) {
                $svc->load('choiceGroups.options.service');
                foreach ($svc->choiceGroups as $cg) {
                    $selectedOptionServiceId = $request->input("package_choices.{$cg->id}");
                    if ($cg->is_required && empty($selectedOptionServiceId)) {
                        return $fail("Please select an option for '{$cg->label}' in {$svc->name}.");
                    }

                    if (!empty($selectedOptionServiceId)) {
                        $validOption = $cg->options->first(function ($opt) use ($selectedOptionServiceId) {
                            return (int) $opt->service_id === (int) $selectedOptionServiceId
                                || (int) $opt->id === (int) $selectedOptionServiceId;
                        });
                        if (!$validOption) {
                            return $fail("Invalid option selected for '{$cg->label}'.");
                        }
                        $validatedPackageChoices[] = [
                            'choice_group_id' => $cg->id,
                            'selected_service_id' => (int) $validOption->service_id,
                            'group_label_snapshot' => $cg->label,
                            'selected_service_name_snapshot' => $validOption->service ? $validOption->service->name : 'Selected Service',
                        ];
                    }
                }
            }
        }

        $bizError = $this->validateBusinessHours(
            $request->appointment_date,
            $startTime,
            $endTime
        );

        if ($bizError) {
            return $fail($bizError);
        }

        if (
            !$isReceptionist &&
            $startTime->lte(
                Carbon::now($tz)->addMinutes(30)
            )
        ) {
            return $fail(
                'Please choose a time at least 30 minutes from now.'
            );
        }

        /*
         * RECEPTIONIST STAFF VALIDATION
         *
         * Quick Book / Right Now:
         * - use attendance-aware availability
         *
         * Future receptionist appointment:
         * - use normal schedule-based availability
         */
        if (
            $isReceptionist &&
            $request->filled('staff_id')
        ) {
            $appointmentDate = Carbon::parse(
                $request->appointment_date,
                $tz
            )->format('Y-m-d');

            $isQuickBookToday =
                $walkInNow &&
                $appointmentDate ===
                    Carbon::today($tz)->format('Y-m-d');

            $staffAvailable = $isQuickBookToday
                ? $this->isQuickBookStaffAvailable(
                    (int) $request->staff_id,
                    $appointmentDate,
                    $startTimeStr,
                    $endTimeStr
                )
                : $this->isStaffAvailable(
                    (int) $request->staff_id,
                    $appointmentDate,
                    $startTimeStr,
                    $endTimeStr
                );

            if (!$staffAvailable) {
                return $fail(
                    $isQuickBookToday
                        ? 'Selected staff is not currently available. The staff member must be checked in, not checked out, and free of another appointment.'
                        : 'Selected staff is not available at this time (off-shift, on leave, or has another appointment).'
                );
            }
        } else {
            /*
             * If no specific staff was selected, retain the
             * normal behavior for regular bookings.
             *
             * For Quick Book / Right Now, search only
             * currently checked-in staff.
             */
            $appointmentDate = Carbon::parse(
                $request->appointment_date,
                $tz
            )->format('Y-m-d');

            $isQuickBookToday =
                $isReceptionist &&
                $walkInNow &&
                $appointmentDate ===
                    Carbon::today($tz)->format('Y-m-d');

            if ($isQuickBookToday) {
                $hasAvailableStaff = User::whereHas(
                    'roles',
                    fn($q) => $q->where('name', 'staff')
                )
                    ->where('is_active', true)
                    ->get()
                    ->contains(
                        fn($s) =>
                            $this->isQuickBookStaffAvailable(
                                $s->id,
                                $appointmentDate,
                                $startTimeStr,
                                $endTimeStr
                            )
                    );
            } else {
                $hasAvailableStaff = User::whereHas(
                    'roles',
                    fn($q) => $q->where('name', 'staff')
                )
                    ->where('is_active', true)
                    ->get()
                    ->contains(
                        fn($s) =>
                            $this->isStaffAvailable(
                                $s->id,
                                $request->appointment_date,
                                $startTimeStr,
                                $endTimeStr
                            )
                    );
            }

            if (!$hasAvailableStaff) {
                return $fail(
                    $isQuickBookToday
                        ? 'Sorry — no staff member is currently checked in and available for this walk-in.'
                        : 'Sorry — no available staff at this hour (all staff are occupied or off-shift). Please choose a different time slot.'
                );
            }
        }

        $requestedRoomId = null;
        if ($requiresRoom) {
            if ($request->filled('room_id') && $request->get('source', 'public') === 'public') {
                $requestedRoomId = (int) $request->room_id;
            }

            $appointmentResult = DB::transaction(function () use ($request, $services, $startTimeStr, $endTimeStr, &$fail, &$requestedRoomId) {
                $eval = \App\Services\RoomCompatibilityService::evaluateSlot(
                    $services,
                    $request->appointment_date,
                    $startTimeStr,
                    $endTimeStr
                );

                if (!$eval['room_available'] || $eval['compatible_rooms']->isEmpty()) {
                    return $fail('No compatible rooms available for this time. Please choose a different time.');
                }

                if ($request->filled('room_id')) {
                    $roomId = (int) $request->room_id;
                    $room = Room::lockForUpdate()->find($roomId);
                    $isCompatible = $eval['compatible_rooms']->contains('id', $roomId);

                    if (
                        !$room ||
                        !$room->is_active ||
                        $room->status === 'maintenance' ||
                        !$isCompatible
                    ) {
                        return $fail('The selected room is not compatible with all selected services or is no longer available. Please pick another slot.');
                    }

                    $requestedRoomId = $roomId;
                    $request->merge(['room_id' => $roomId]);
                } else {
                    $request->merge([
                        'room_id' => $eval['compatible_rooms']->first()->id,
                    ]);
                }

                return null;
            });

            if ($appointmentResult instanceof \Illuminate\Http\JsonResponse || $appointmentResult instanceof \Illuminate\Http\RedirectResponse) {
                return $appointmentResult;
            }
        }

        /*
         * CUSTOMER HANDLING
         */
        if (
            $isReceptionist &&
            $request->filled('customer_id')
        ) {
            $customer = Customer::find(
                $request->customer_id
            );

            if (!$customer) {
                return $fail(
                    'The selected customer could not be found.'
                );
            }

            if (
                $request->filled('guest_phone') &&
                $customer->phone_number !==
                    $request->guest_phone
            ) {
                $customer->phone_number =
                    $request->guest_phone;
            }

            if ($request->filled('medical_notes')) {
                $customer->medical_notes =
                    $request->medical_notes;
            }

            $customer->save();
        } elseif ($isCustomer) {
            $customer = Customer::firstOrNew([
                'user_id' => $user->id,
            ]);

            if (!$customer->exists) {
                $customer->first_name =
                    $user->first_name ?? '';

                $customer->last_name =
                    $user->last_name ?? '';

                $customer->email =
                    $user->email ?? null;

                $customer->customer_type =
                    'regular';
            }

            if (
                empty($customer->nickname) &&
                $request->filled('guest_first_name')
            ) {
                $customer->nickname =
                    trim(
                        $request->guest_first_name
                    );
            }

            if ($request->filled('guest_phone')) {
                $customer->phone_number =
                    $request->guest_phone;
            }

            // Do not overwrite existing customer medical notes with appointment-specific information.
            if ($request->filled('medical_notes') && empty($customer->medical_notes)) {
                $customer->medical_notes =
                    $request->medical_notes;
            }

            $customer->save();
        } else {
            $nameParts = preg_split(
                '/\s+/',
                trim($request->guest_first_name),
                2
            );

            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            // Guest bookings must NEVER link to a registered customer account (user_id IS NOT NULL)
            // solely through phone-number matching. Match only unlinked guest profiles (user_id IS NULL)
            // with matching phone number AND name, or create a dedicated guest customer record.
            $existingGuest = Customer::where('phone_number', $request->guest_phone)
                ->whereNull('user_id')
                ->where('first_name', $firstName)
                ->where('last_name', $lastName)
                ->first();

            if ($existingGuest) {
                $customer = $existingGuest;
            } else {
                $customer = Customer::create([
                    'user_id' => null,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'customer_type' => 'regular',
                    'phone_number' => $request->guest_phone,
                    'medical_notes' => null,
                ]);
            }
        }

        $status = 'pending';
        $confirmedAt = null;

        if ($isReceptionist) {
            $status = 'confirmed';
            $confirmedAt = now();
        }

        $assignedStaffId = $isReceptionist && $request->filled('staff_id') ? (int) $request->staff_id : null;
        $assistingStaffIds = $isReceptionist ? array_map('intval', (array) $request->input('assisting_staff_ids', [])) : [];
        $preferredStaffIds = !$isReceptionist ? array_map('intval', array_filter((array) $request->input('preferred_staff_ids', []))) : [];

        if (!empty($preferredStaffIds)) {
            if (count($preferredStaffIds) !== count(array_unique($preferredStaffIds))) {
                return $fail('Duplicate preferred therapists are not allowed.');
            }
            $requiredCount = max(1, (int) $services->max('therapists_required'));
            if (count($preferredStaffIds) > $requiredCount) {
                return $fail("You may select up to {$requiredCount} preferred therapist(s) for the selected services.");
            }
        }

        $appointment = null;
        $isDuplicateSubmission = false;

        try {
            $transactionResult = DB::transaction(function () use (
                $customer,
            $request,
            $services,
            $startTime,
            $endTime,
            $totalPrice,
            $status,
            $confirmedAt,
            $user,
            $requestedRoomId,
            $isReceptionist,
            $assignedStaffId,
            $assistingStaffIds,
            $preferredStaffIds,
            $validatedPackageChoices,
            $fail,
            &$appointment,
            &$isDuplicateSubmission
        ) {
            // Re-lock and revalidate every resource before creating anything.
            $failure = $this->lockAndValidateResources(
                $request,
                $services,
                $startTime,
                $endTime,
                $assignedStaffId,
                $assistingStaffIds,
                $request->room_id,
                $fail,
                $preferredStaffIds
            );

            if ($failure) {
                return $failure;
            }

            // Duplicate-submission idempotency protection:
            // If the exact same customer submitted an identical appointment within the last 10 seconds,
            // reuse the existing appointment instead of creating duplicate records.
            $recentDuplicate = Appointment::where('customer_id', $customer->id)
                ->whereDate('appointment_date', $request->appointment_date)
                ->where('start_time', $startTime->format('H:i:s'))
                ->where('end_time', $endTime->format('H:i:s'))
                ->where('created_at', '>=', now()->subSeconds(10))
                ->latest('id')
                ->first();

            if ($recentDuplicate) {
                $appointment = $recentDuplicate;
                $isDuplicateSubmission = true;
                return;
            }

            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'user_id' => $assignedStaffId,
                'room_id' => $request->room_id,
                'requested_room_id' => $requestedRoomId,
                'appointment_date' =>
                    $request->appointment_date,
                'start_time' =>
                    $startTime->format('H:i:s'),
                'end_time' =>
                    $endTime->format('H:i:s'),
                'status' => $status,
                'total_price' => $totalPrice,
                'created_by' => $user?->id,
                'confirmed_at' => $confirmedAt,
                'notes' =>
                    $request->notes ?? null,
                'treatment_notes' =>
                    $request->treatment_notes
                    ?? $request->medical_notes
                    ?? null,
                'ip_address' => $request->ip(),
                'guest_first_name' => $request->guest_first_name ? trim($request->guest_first_name) : null,
                'guest_phone' => $request->guest_phone ?? null,
            ]);

            if ($isReceptionist && $assignedStaffId) {
                $appointment->syncStaff($assignedStaffId, $assistingStaffIds);
            } elseif (!empty($preferredStaffIds)) {
                $appointment->preferredStaff()->sync($preferredStaffIds);
            }

            foreach ($services as $service) {
                AppointmentService::create([
                    'appointment_id' =>
                        $appointment->id,
                    'service_id' =>
                        $service->id,
                    'service_name' =>
                        $service->name,
                    'service_duration' =>
                        $service->duration_minutes,
                    'price_at_booking' =>
                        $service->discount_price
                        ?? $service->price,
                ]);
            }

            foreach ($validatedPackageChoices as $choice) {
                \App\Models\AppointmentPackageSelection::create([
                    'appointment_id' => $appointment->id,
                    'choice_group_id' => $choice['choice_group_id'],
                    'selected_service_id' => $choice['selected_service_id'],
                    'group_label_snapshot' => $choice['group_label_snapshot'],
                    'selected_service_name_snapshot' => $choice['selected_service_name_snapshot'],
                ]);
            }

            if (
                $request->filled('payment_amount') &&
                $request->payment_amount > 0
            ) {
                $appointment->payments()->create([
                    'payment_method' =>
                        $request->payment_method
                        ?? 'cash',
                    'amount' =>
                        $request->payment_amount,
                    'type' =>
                        $request->payment_type
                        ?? 'full',
                    'paid_at' => now(),
                ]);
            }

            if (
                $request->room_id &&
                $request->boolean('walk_in_now')
            ) {
                Room::where(
                    'id',
                    $request->room_id
                )->update([
                    'status' => 'occupied',
                ]);
            }
        });
        } catch (\RuntimeException $e) {
            return $fail($e->getMessage());
        }

        if ($transactionResult instanceof \Illuminate\Http\JsonResponse
            || $transactionResult instanceof \Illuminate\Http\RedirectResponse) {
            return $transactionResult;
        }

        if (!$isReceptionist && !$isDuplicateSubmission) {
            if ($customer && $customer->user) {
                NotificationController::sendTo(
                    $customer->user,
                    'Booking Request Submitted',
                    'Your booking request for ' . $appointment->appointment_date->format('M j, Y') . ' at ' . Carbon::parse($appointment->start_time)->format('g:i A') . ' has been received and is awaiting confirmation.',
                    'booking',
                    'info',
                    route('customer.bookings'),
                    'View Bookings'
                );
            }

            $receptionists = User::whereHas(
                'roles',
                fn($q) =>
                    $q->where(
                        'name',
                        'receptionist'
                    )
            )->get();

            NotificationController::sendTo(
                $receptionists,
                'New Online Booking',
                ($customer->display_name
                    ?? 'A customer')
                    . ' booked for '
                    . $appointment
                        ->appointment_date
                        ->format('M j')
                    . ' at '
                    . Carbon::parse(
                        $appointment->start_time
                    )->format('g:i A'),
                'booking',
                'info',
                route('receptionist.pending'),
                'View Pending'
            );

            $admins = User::whereHas(
                'roles',
                fn($q) =>
                    $q->where(
                        'name',
                        'admin'
                    )
            )->get();

            NotificationController::sendTo(
                $admins,
                'New Online Booking',
                ($customer->display_name
                    ?? 'A customer')
                    . ' booked for '
                    . $appointment
                        ->appointment_date
                        ->format('M j')
                    . ' at '
                    . Carbon::parse(
                        $appointment->start_time
                    )->format('g:i A'),
                'booking',
                'info',
                route(
                    'admin.appointments',
                    ['status' => 'pending']
                ),
                'Review'
            );
        }

        if (
            $request->ajax() ||
            $request->wantsJson() ||
            $request->get('source') === 'receptionist'
        ) {
            return response()->json([
                'success' => true,
                'message' =>
                    'Appointment confirmed for '
                    . $customer->display_name,
                'redirect' =>
                    route('receptionist.active'),
                'appointment_id' =>
                    $appointment->id,
            ]);
        }

        if (
            $isReceptionist &&
            $request->boolean('walk_in_now')
        ) {
            return redirect()
                ->route('receptionist.active')
                ->with(
                    'success',
                    'Walk-in appointment confirmed for '
                        . $customer->display_name
                );
        }

        session()->put("authorized_booking_{$appointment->id}", true);

        return redirect()->route(
            'booking.confirmation',
            $appointment->id
        );
    }

    public function confirmation(
        Appointment $appointment
    ) {
        $user = auth()->user();
        $isAuthorized = false;

        if ($user) {
            if ($user->isAdmin() || $user->isReceptionist()) {
                $isAuthorized = true;
            } elseif (($user->customerProfile && $appointment->customer_id === $user->customerProfile->id) || $appointment->user_id === $user->id) {
                $isAuthorized = true;
            }
        }

        if (!$isAuthorized && session()->get("authorized_booking_{$appointment->id}") === true) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            abort(403, 'Unauthorized access to booking confirmation.');
        }

        $appointment->load(
            'services',
            'customer'
        );

        return view('booking.confirmation', [
            'appointment' => $appointment,
            'role' => session(
                'user_role',
                'guest'
            ),
        ]);
    }

    public function occupiedSlots(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $occupied = Appointment::where(
            'appointment_date',
            $request->date
        )
            ->whereIn(
                'status',
                ['pending', 'confirmed', 'completed']
            )
            ->get([
                'start_time',
                'end_time',
            ])
            ->map(
                fn($a) => [
                    'start_time' =>
                        Carbon::parse(
                            $a->start_time
                        )->format('H:i'),
                    'end_time' =>
                        Carbon::parse(
                            $a->end_time
                        )->format('H:i'),
                ]
            );

        return response()->json($occupied);
    }

    // ==================== FUTURE STAFF GAPS ====================

    public function staffGaps(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'duration' => 'required|integer|min:15',
            'staff_id' => 'required|exists:users,id',
            'assisting_staff_ids' => 'nullable|array',
            'assisting_staff_ids.*' => 'exists:users,id',
            'services' => 'nullable|array',
            'services.*' => 'exists:services,id',
            'buffer_minutes' => 'nullable|integer|min:0|max:60',
        ]);

        $tz = 'Asia/Manila';
        $date = Carbon::parse($validated['date'], $tz)->format('Y-m-d');
        $duration = (int) $validated['duration'];
        $serviceIds = $validated['services'] ?? [];

        $services = empty($serviceIds)
            ? collect()
            : Service::whereIn('id', $serviceIds)->get();

        // Both the primary and the assisting therapist must be free for the
        // complete treatment plus the preparation buffer.
        $staffIds = array_values(array_unique(array_merge(
            [(int) $validated['staff_id']],
            array_map('intval', $validated['assisting_staff_ids'] ?? [])
        )));

        $availabilityService = app(\App\Services\AppointmentAvailabilityService::class);
        $requiredCount = $availabilityService->requirements($services)['therapists_required'];

        // The selected team must match the appointment's actual requirement.
        // A smaller team must never receive slots that the backend later rejects.
        if (count($staffIds) !== $requiredCount) {
            return response()->json([
                'gaps' => [],
                'message' => "This service combination requires exactly {$requiredCount} therapist(s).",
            ]);
        }

        $minStart = $date === Carbon::today($tz)->format('Y-m-d')
            ? Carbon::now($tz)->addMinutes((int) ($validated['buffer_minutes'] ?? 0))
            : null;

        $gaps = $availabilityService->availableTimes([
            'date' => $date,
            'services' => $services,
            'duration' => $duration,
            'staff_ids' => $staffIds,
            'team_size' => $requiredCount,
            'min_start' => $minStart,
        ]);

        return response()->json(['gaps' => $gaps]);
    }

    private function extractTime(
        $value
    ): ?string {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \Carbon\Carbon) {
            return $value->format('H:i');
        }

        if (
            is_string($value) &&
            strlen($value) > 8 &&
            str_contains($value, ' ')
        ) {
            return substr($value, 11, 5);
        }

        if (
            is_string($value) &&
            strlen($value) === 8 &&
            str_contains($value, ':')
        ) {
            return substr($value, 0, 5);
        }

        if (
            is_string($value) &&
            strlen($value) === 5 &&
            str_contains($value, ':')
        ) {
            return $value;
        }

        return null;
    }

    // ==================== QUICK BOOK: NEXT RIGHT-NOW SLOTS ====================

    public function nextSlots(Request $request)
    {
        $validated = $request->validate([
            'date' => [
                'required',
                'date',
            ],
            'duration' => [
                'required',
                'integer',
                'min:1',
            ],
            'services' => [
                'required',
                'array',
                'min:1',
            ],
            'services.*' => [
                'integer',
                'exists:services,id',
            ],
            'buffer_minutes' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $tz = 'Asia/Manila';

        $date = Carbon::parse(
            $validated['date'],
            $tz
        )->format('Y-m-d');

        $duration =
            (int) $validated['duration'];

        $bufferMinutes = (int) (
            $validated['buffer_minutes']
            ?? 5
        );

        $services = Service::whereIn(
            'id',
            $validated['services']
        )
            ->where(
                'is_active',
                true
            )
            ->get();

        if ($services->isEmpty()) {
            return response()->json([
                'slots' => [],
                'nextDayHint' => null,
            ]);
        }

        $requiresRoom = $services->contains(
            fn($service) =>
                (bool) $service->requires_room
        );

        $isToday =
            $date ===
            Carbon::today($tz)->format('Y-m-d');

        $now = Carbon::now($tz);

        /*
         * No fixed Sunday rule.
         *
         * Whether a date is operational is determined by
         * staff WorkSchedule / ScheduleException.
         */
        $staffMembers = User::where(
            'is_active',
            true
        )
            ->whereHas(
                'roles',
                fn($query) =>
                    $query->where(
                        'name',
                        'staff'
                    )
            )
            ->get();

        $slots = [];
        $isReceptionist = auth()->user() && auth()->user()->roles()->where('name', 'receptionist')->exists();

        foreach ($staffMembers as $staff) {
            /*
             * Individual staff schedule remains the source
             * of normal availability.
             */
            $workWindow =
                $this->getStaffWorkWindow(
                    $staff->id,
                    $date,
                    $isToday // Right Now only: extend past shift while checked in.
                );

            if (!$workWindow) {
                continue;
            }

            $workStartCarbon = Carbon::parse(
                $date . ' ' .
                $workWindow['start'],
                $tz
            );

            $workEndCarbon = Carbon::parse(
                $date . ' ' .
                $workWindow['end'],
                $tz
            );

            /*
             * TODAY:
             *
             * Right Now requires active attendance.
             */
            $attendanceOpen = false;

            if ($isToday) {
                $attendanceOpen = $this->isStaffCurrentlyCheckedIn($staff->id, $date);

                if (!$attendanceOpen) {
                    continue;
                }
            }

            $availabilityStartCarbon =
                $workStartCarbon->copy();

            $availabilityEndCarbon =
                $workEndCarbon->copy();

            /*
             * A checked-in staff member can continue receiving
             * a Right Now booking after the scheduled shift end.
             *
             * There is intentionally no fixed 8 PM rule here.
             */
            if (
                $isToday &&
                $attendanceOpen
            ) {
                $endLimit = $now->copy()->endOfDay();
                
                if ($isReceptionist) {
                    $endLimit->addHours(3);
                }

                if (
                    $availabilityEndCarbon
                        ->lt($endLimit)
                ) {
                    $availabilityEndCarbon =
                        $endLimit->copy();
                }
            }

            /*
             * Determine where to begin searching.
             */
            if ($isToday) {
                $searchStartCarbon = $now
                    ->copy()
                    ->addMinutes(
                        $bufferMinutes
                    );

                if (
                    $searchStartCarbon
                        ->lt(
                            $availabilityStartCarbon
                        )
                ) {
                    $searchStartCarbon =
                        $availabilityStartCarbon
                            ->copy();
                }
            } else {
                $searchStartCarbon =
                    $availabilityStartCarbon
                        ->copy();
            }

            if (
                $searchStartCarbon
                    ->gte(
                        $availabilityEndCarbon
                    )
            ) {
                continue;
            }

            /*
             * Existing appointments for this staff member, including the
             * post-treatment preparation period for each resource.
             */
            $intervals = app(AppointmentOccupancyService::class)->staffIntervals(
                (int) $staff->id,
                $date
            );

            $currentStart =
                $searchStartCarbon->copy();

            /*
             * Find gaps before appointments.
             */
            foreach ($intervals as $interval) {
                $appointmentStart =
                    Carbon::parse($date . ' ' . $interval['start'], $tz);

                $appointmentEnd =
                    Carbon::parse($date . ' ' . $interval['end'], $tz)
                        ->greaterThan(Carbon::parse($date . ' 23:59', $tz))
                        ? Carbon::parse($date . ' 23:59', $tz)
                        : Carbon::parse($date . ' ' . $interval['end'], $tz);

                if (
                    $appointmentEnd
                        ->lte($currentStart)
                ) {
                    continue;
                }

                if (
                    $appointmentStart
                        ->gt($currentStart)
                ) {
                    $gapEnd =
                        $appointmentStart->copy();

                    while (
                        $currentStart
                            ->copy()
                            ->addMinutes(
                                $duration + AppointmentOccupancyService::PREPARATION_MINUTES
                            )
                            ->lte($gapEnd)
                        &&
                        $currentStart
                            ->copy()
                            ->addMinutes(
                                $duration + AppointmentOccupancyService::PREPARATION_MINUTES
                            )
                            ->lte(
                                $availabilityEndCarbon
                            )
                    ) {
                        $slotEnd =
                            $currentStart
                                ->copy()
                                ->addMinutes(
                                    $duration
                                );

                        if (
                            $this->isSlotValid(
                                $currentStart,
                                $slotEnd,
                                $duration,
                                $date,
                                $requiresRoom,
                                $services
                            )
                        ) {
                            $slot =
                                $this->buildSlot(
                                    $currentStart,
                                    $duration,
                                    $date,
                                    $staff,
                                    $requiresRoom,
                                    $services
                                );

                            if ($slot) {
                                if (isset($isReceptionist) && $isReceptionist && $isToday && $currentStart->format('H:i') === $searchStartCarbon->format('H:i')) {
                                    $slot['display'] = 'Start Now (' . $slot['display'] . ')';
                                }
                                $slots[] = $slot;
                            }
                        }

                        $currentStart->addMinutes(30);

                    }
                }

                if (
                    $appointmentEnd
                        ->gt($currentStart)
                ) {
                    $currentStart =
                        $appointmentEnd->copy();
                }

                if (
                    $currentStart
                        ->gte(
                            $availabilityEndCarbon
                        )
                ) {
                    break;
                }
            }

            /*
             * Search the final gap after the last appointment.
             */
            while (
                $currentStart
                    ->copy()
                    ->addMinutes($duration + AppointmentOccupancyService::PREPARATION_MINUTES)
                    ->lte(
                        $availabilityEndCarbon
                    )
            ) {
                $slotEnd =
                    $currentStart
                        ->copy()
                        ->addMinutes(
                            $duration
                        );

                if (
                    $this->isSlotValid(
                        $currentStart,
                        $slotEnd,
                        $duration,
                        $date,
                        $requiresRoom,
                        $services
                    )
                ) {
                    $slot =
                        $this->buildSlot(
                            $currentStart,
                            $duration,
                            $date,
                            $staff,
                            $requiresRoom,
                            $services
                        );

                    if ($slot) {
                        if (isset($isReceptionist) && $isReceptionist && $isToday && $currentStart->format('H:i') === $searchStartCarbon->format('H:i')) {
                            $slot['display'] = 'Start Now (' . $slot['display'] . ')';
                        }
                        $slots[] = $slot;
                    }
                }

                $currentStart->addMinutes(30);
            }
        }

        /*
         * Sort returned slots chronologically.
         */
        usort(
            $slots,
            function ($a, $b) {
                $timeA = $a['time'];
                $timeB = $b['time'];
                
                // If one is early morning (next day) and the other is evening, early morning comes AFTER
                $isNextDayA = $timeA < '05:00';
                $isNextDayB = $timeB < '05:00';
                
                if ($isNextDayA !== $isNextDayB) {
                    return $isNextDayA ? 1 : -1;
                }

                return strcmp($timeA, $timeB);
            }
        );

        $slots = array_slice(
            $slots,
            0,
            12
        );

        /*
         * If there are no slots, find the next date on which
         * at least one active staff member has a valid schedule.
         *
         * No Sunday assumption is used.
         */
        $nextDayHint = null;

        if (empty($slots)) {
            for (
                $offset = 1;
                $offset <= 14;
                $offset++
            ) {
                $candidate =
                    $now->copy()
                        ->addDays($offset);

                $candidateDate =
                    $candidate->format('Y-m-d');

                $earliestStart = null;

                foreach (
                    $staffMembers as $staff
                ) {
                    $candidateWindow =
                        $this->getStaffWorkWindow(
                            $staff->id,
                            $candidateDate
                        );

                    if (!$candidateWindow) {
                        continue;
                    }

                    $candidateStart =
                        Carbon::parse(
                            $candidateDate . ' ' .
                            $candidateWindow['start'],
                            $tz
                        );

                    if (
                        $earliestStart === null ||
                        $candidateStart
                            ->lt($earliestStart)
                    ) {
                        $earliestStart =
                            $candidateStart;
                    }
                }

                if ($earliestStart) {
                    $nextDayHint = [
                        'date' =>
                            $candidateDate,

                        'display_date' =>
                            $candidate
                                ->format(
                                    'F j, Y'
                                ),

                        'time' =>
                            $earliestStart
                                ->format('H:i'),

                        'display_time' =>
                            $earliestStart
                                ->format('g:i A'),
                    ];

                    break;
                }
            }
        }

        /*
         * Condition-specific diagnostics for an empty result, so the
         * receptionist is never told "no staff" when the real cause is room
         * configuration or occupied rooms.
         */
        $diagnostics = null;

        if (empty($slots)) {
            $compatibleRooms = $requiresRoom
                ? \App\Services\RoomCompatibilityService::findCompatibleForServices($services)
                : collect();

            $scheduledCount = $staffMembers->filter(
                fn($staff) => $this->getStaffWorkWindow($staff->id, $date) !== null
            )->count();

            $checkedInCount = (!$isToday)
                ? $scheduledCount
                : $staffMembers->filter(
                    fn($staff) => $this->getStaffWorkWindow($staff->id, $date) !== null
                        && $this->isStaffCurrentlyCheckedIn($staff->id, $date)
                )->count();

            $earliestStart = $isToday
                ? $now->copy()->addMinutes($bufferMinutes)
                : Carbon::parse($date . ' ' . ($workStart ?? '09:00'), $tz);
            $candidateEnd = $earliestStart->copy()->addMinutes($duration);

            $roomsBusy = $requiresRoom
                && $compatibleRooms->isNotEmpty()
                && $compatibleRooms->every(
                    fn($room) => !$room->isAvailableFor(
                        $date,
                        $earliestStart->format('H:i:s'),
                        $candidateEnd->format('H:i:s')
                    )
                );

            $diagnostics = [
                'no_staff_scheduled' => $scheduledCount === 0,
                'staff_not_checked_in' => $isToday && $scheduledCount > 0 && $checkedInCount === 0,
                'no_compatible_rooms' => $requiresRoom && $compatibleRooms->isEmpty(),
                'compatible_rooms_busy' => $roomsBusy,
                'all_staff_busy' => !$isToday && $scheduledCount > 0,
            ];
        }

        return response()->json([
            'slots' => $slots,
            'nextDayHint' => $nextDayHint,
            'next_day_hint' => $nextDayHint,
            'diagnostics' => $diagnostics,
        ]);
    }
}