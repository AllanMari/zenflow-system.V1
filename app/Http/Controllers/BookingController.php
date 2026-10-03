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
            $roomCategoryIds = $services
                ->where('requires_room', true)
                ->whereNotNull('room_category_id')
                ->pluck('room_category_id')
                ->unique()
                ->values();

            $roomsQuery = Room::active()
                ->where('status', '!=', 'maintenance');

            if ($roomCategoryIds->isNotEmpty()) {
                $roomsQuery->where(function ($q) use ($roomCategoryIds) {
                    $q->whereIn('category_id', $roomCategoryIds)
                        ->orWhereNull('category_id');
                });
            }

            foreach ($roomsQuery->get() as $room) {
                if (
                    $room->isAvailableFor(
                        $date,
                        $startTime->format('H:i:s'),
                        $endTime->format('H:i:s')
                    )
                ) {
                    $freeRooms[] = [
                        'id' => $room->id,
                        'name' => $room->name,
                    ];
                }
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

        return view('landing', compact('hero', 'benefits', 'categories', 'activeNotice'));
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
                        'room_category_id'
                    ),
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
                                    'duration_minutes'
                                )
                                ->get()
                                ->toArray();
                        }
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
                        'deposit_percentage_min' => (float) $depMin,
                        'deposit_percentage_max' => (float) $depMax,
                        'description' => $s->description,
                        'image' => $s->image
                            ? asset($s->image)
                            : null,
                        'requires_room' => $s->requires_room,
                        'room_category_id' => $s->room_category_id,
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

        return view(
            'booking.wizard',
            compact(
                'categoriesJson',
                'preselectedIds',
                'defaultName',
                'defaultPhone',
                'customerMedicalNotes',
                'activeNotice'
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
                        'requires_room',
                        'room_category_id'
                    ),
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

        $rooms = collect();
        $roomAppointments = collect();

        if ($requiresRoom) {
            $roomCategoryIds = $services
                ->where('requires_room', true)
                ->whereNotNull('room_category_id')
                ->pluck('room_category_id')
                ->unique()
                ->values();

            $roomsQuery = Room::active()
                ->where('status', '!=', 'maintenance');

            if ($roomCategoryIds->isNotEmpty()) {
                $roomsQuery->where(function ($q) use ($roomCategoryIds) {
                    $q->whereIn('category_id', $roomCategoryIds)
                        ->orWhereNull('category_id');
                });
            }

            $rooms = $roomsQuery->get();
        }

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

        $allStaffAppointments = collect();
        if (!empty($dateList) && $staffMembers->isNotEmpty()) {
            $allStaffAppointments = Appointment::whereIn('appointment_date', $dateList)
                ->whereIn('status', ['confirmed', 'completed', 'pending'])
                ->whereIn('user_id', $staffMembers->pluck('id'))
                ->get(['appointment_date', 'user_id', 'start_time', 'end_time'])
                ->groupBy('appointment_date')
                ->map(fn($group) => $group->groupBy('user_id'));
        }

        /*
         * Room appointments are loaded for the actual dates
         * being searched. There is intentionally no Sunday
         * exclusion here.
         */
        if ($requiresRoom && $rooms->isNotEmpty() && !empty($dateList)) {
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
             * Build the operational windows from staff schedules.
             *
             * If multiple staff members are scheduled at different
             * times, their windows are merged so the booking API
             * exposes the actual available operational periods.
             */
            $windows = [];
            $staffWindows = [];

            foreach ($staffMembers as $staff) {
                $window = $this->getStaffWorkWindow(
                    $staff->id,
                    $dateStr
                );

                if (!$window) {
                    continue;
                }

                $staffWindows[$staff->id] = $window;
                
                $windows[] = [
                    'start' => $window['start'],
                    'end' => $window['end'],
                ];
            }

            if (empty($windows)) {
                continue;
            }

            /*
             * Merge overlapping/touching staff windows.
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

            $dayAppointments = $roomAppointments[$dateStr] ?? null;

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
                    $slotEnd = $open->copy()->addMinutes($duration);

                    $minLeadTime = $request->boolean('receptionist')
                        ? 0
                        : 30;

                    $minimumStart = $now
                        ->copy()
                        ->addMinutes($minLeadTime);

                    $isDateToday =
                        $dateStr === $now->format('Y-m-d');

                    $timeIsValid =
                        !$isDateToday ||
                        $open->gt($minimumStart);

                    if (
                        $slotEnd->lte($close) &&
                        $timeIsValid
                    ) {
                        $roomAvailable = true;
                        $freeRooms = [];

                        $slotStartStr = $open->format('H:i:s');
                        $slotEndStr = $slotEnd->format('H:i:s');

                        $hasAvailableStaffForSlot = false;
                        $dailyStaffApts = $allStaffAppointments[$dateStr] ?? collect();

                        foreach ($staffWindows as $staffId => $windowData) {
                            $startTimeStr = substr($slotStartStr, 0, 5);
                            $endTimeStr = substr($slotEndStr, 0, 5);

                            if ($startTimeStr < $windowData['start'] || $endTimeStr > $windowData['end']) {
                                continue;
                            }

                            $apts = $dailyStaffApts[$staffId] ?? collect();
                            $isFree = $apts->isEmpty() || !$apts->contains(function ($apt) use ($slotStartStr, $slotEndStr) {
                                return ($slotStartStr < $apt->end_time) && ($slotEndStr > $apt->start_time);
                            });

                            if ($isFree) {
                                $hasAvailableStaffForSlot = true;
                                break;
                            }
                        }

                        if (!$hasAvailableStaffForSlot) {
                            $roomAvailable = false;
                        }

                        if ($requiresRoom && $roomAvailable) {
                            if ($rooms->isEmpty()) {
                                $roomAvailable = false;
                            } elseif (
                                $dayAppointments &&
                                $dayAppointments->isNotEmpty()
                            ) {
                                $slotStartStr = $open->format('H:i:s');
                                $slotEndStr = $slotEnd->format('H:i:s');

                                foreach ($rooms as $room) {
                                    $apts = $dayAppointments[$room->id]
                                        ?? collect();

                                    $isFree = $apts->isEmpty()
                                        || !$apts->contains(
                                            function ($apt) use (
                                                $slotStartStr,
                                                $slotEndStr
                                            ) {
                                                return (
                                                    $slotStartStr
                                                    < $apt->end_time
                                                )
                                                    && (
                                                        $slotEndStr
                                                        > $apt->start_time
                                                    );
                                            }
                                        );

                                    if ($isFree) {
                                        $freeRooms[] = [
                                            'id' => $room->id,
                                            'name' => $room->name,
                                        ];
                                    }
                                }

                                $roomAvailable =
                                    count($freeRooms) > 0;
                            } else {
                                $freeRooms = $rooms
                                    ->map(
                                        fn($r) => [
                                            'id' => $r->id,
                                            'name' => $r->name,
                                        ]
                                    )
                                    ->toArray();
                            }
                        }

                        $slots[] = [
                            'date' => $dateStr,
                            'time' => $open->format('H:i'),
                            'display' => $open->format('g:i A'),
                            'room_available' => $roomAvailable,
                            'free_rooms' => $freeRooms,
                        ];
                    }

                    $open->addMinutes(30);
                }
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

        return response()->json([
            'slots' => $slots,
            'requires_room' => $requiresRoom,
            'room_count' => $rooms->count(),
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

                $conflict = Appointment::where(
                    'user_id',
                    $staff->id
                )
                    ->where(
                        'appointment_date',
                        $date
                    )
                    ->whereIn(
                        'status',
                        [
                            'confirmed',
                            'pending',
                            'completed',
                        ]
                    )
                    ->where(function ($q) use (
                        $time,
                        $slotEndTime
                    ) {
                        $q->where(
                            'start_time',
                            '<',
                            $slotEndTime . ':00'
                        )
                            ->where(
                                'end_time',
                                '>',
                                $time . ':00'
                            );
                    })
                    ->exists();

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

        $rooms = collect();
        $roomAppointments = collect();

        if ($requiresRoom) {
            $roomCategoryIds = $services
                ->where('requires_room', true)
                ->whereNotNull('room_category_id')
                ->pluck('room_category_id')
                ->unique()
                ->values();

            $roomsQuery = Room::active()
                ->where('status', '!=', 'maintenance');

            if ($roomCategoryIds->isNotEmpty()) {
                $roomsQuery->where(function ($q) use ($roomCategoryIds) {
                    $q->whereIn('category_id', $roomCategoryIds)
                        ->orWhereNull('category_id');
                });
            }

            $rooms = $roomsQuery->get();
        }

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

        if ($requiresRoom && $rooms->isNotEmpty()) {
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
                        $freeRooms = [];

                        if ($requiresRoom) {
                            if ($rooms->isEmpty()) {
                                $roomAvailable = false;
                            } elseif (
                                $dayAppointments &&
                                $dayAppointments->isNotEmpty()
                            ) {
                                $slotStartStr =
                                    $open->format('H:i:s');

                                $slotEndStr =
                                    $slotEnd->format('H:i:s');

                                foreach ($rooms as $room) {
                                    $apts =
                                        $dayAppointments[$room->id]
                                        ?? collect();

                                    $isFree =
                                        $apts->isEmpty()
                                        || !$apts->contains(
                                            function ($apt)
                                            use (
                                                $slotStartStr,
                                                $slotEndStr
                                            ) {
                                                return (
                                                    $slotStartStr
                                                    < $apt->end_time
                                                )
                                                    && (
                                                        $slotEndStr
                                                        > $apt->start_time
                                                    );
                                            }
                                        );

                                    if ($isFree) {
                                        $freeRooms[] = [
                                            'id' => $room->id,
                                            'name' => $room->name,
                                        ];
                                    }
                                }

                                $roomAvailable =
                                    count($freeRooms) > 0;
                            } else {
                                $freeRooms =
                                    $rooms
                                        ->map(
                                            fn($r) => [
                                                'id' => $r->id,
                                                'name' => $r->name,
                                            ]
                                        )
                                        ->toArray();
                            }
                        }

                        $slots[] = [
                            'date' => $dateStr,
                            'time' => $open->format('H:i'),
                            'display' =>
                                $open->format('g:i A'),
                            'room_available' =>
                                $roomAvailable,
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
        string $date
    ): ?array {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $isCurrentlyCheckedIn = $this->isStaffCurrentlyCheckedIn($staffId, $date);

        $exception = ScheduleException::where(
            'user_id',
            $staffId
        )
            ->whereDate('exception_date', $date)
            ->first();

        if (
            $exception &&
            in_array(
                $exception->type,
                [
                    'day_off',
                    'holiday',
                    'sick_leave',
                    'urgent_leave',
                ]
            )
        ) {
            if ($isCurrentlyCheckedIn) {
                return ['start' => '00:00', 'end' => '23:59'];
            }
            return null;
        }

        $schedule = WorkSchedule::where(
            'user_id',
            $staffId
        )
            ->where('day_of_week', $dayOfWeek)
            ->where('is_day_off', false)
            ->first();

        $start = null;
        $end = null;

        if ($schedule) {
            $start = $this->extractTime($schedule->start_time);
            $end = $this->extractTime($schedule->end_time);
        }

        if (
            $exception &&
            $exception->type === 'custom_hours'
        ) {
            $start = $this->extractTime($exception->start_time);
            $end = $this->extractTime($exception->end_time);
        }

        if (!$start || !$end) {
            if ($isCurrentlyCheckedIn) {
                return ['start' => '00:00', 'end' => '23:59'];
            }
            return null;
        }

        if ($isCurrentlyCheckedIn) {
            $end = '23:59';
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
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

        return !Appointment::where(
            'user_id',
            $staffId
        )
            ->where(
                'appointment_date',
                $date
            )
            ->whereIn(
                'status',
                ['confirmed', 'pending', 'completed']
            )
            ->where(function ($q) use ($start, $end) {
                $q->where(
                    'start_time',
                    '<',
                    $end . ':00'
                )
                    ->where(
                        'end_time',
                        '>',
                        $start . ':00'
                    );
            })
            ->exists();
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
        $today = Carbon::today('Asia/Manila')->format('Y-m-d');

        if ($date !== $today) {
            return false;
        }

        /*
         * Only count attendance records that belong to today in Manila time.
         * The date column stores the Manila local date (not UTC-shifted),
         * so we compare directly against today's Manila date.
         */
        $attendance = Attendance::where('user_id', $staffId)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->whereDate('date', $today)
            ->orderBy('id', 'desc')
            ->first();

        return $attendance !== null;
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
            $date
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
        $hasConflict = Appointment::where(
            'user_id',
            $staffId
        )
            ->where(
                'appointment_date',
                $date
            )
            ->whereIn(
                'status',
                ['confirmed', 'pending', 'completed']
            )
            ->where(function ($q) use ($start, $end) {
                $q->where(
                    'start_time',
                    '<',
                    $end . ':00'
                )
                    ->where(
                        'end_time',
                        '>',
                        $start . ':00'
                    );
            })
            ->exists();

        return !$hasConflict;
    }

    private function findFreeRoom(
        $services,
        string $date,
        string $startTime,
        string $endTime
    ): ?Room {
        $roomCategoryIds = $services
            ->where('requires_room', true)
            ->whereNotNull('room_category_id')
            ->pluck('room_category_id')
            ->unique()
            ->values();

        $query = Room::active()
            ->where('status', '!=', 'maintenance');

        if ($roomCategoryIds->isNotEmpty()) {
            $query->where(function ($q) use ($roomCategoryIds) {
                $q->whereIn('category_id', $roomCategoryIds)
                    ->orWhereNull('category_id');
            });
        }

        return $query->get()->first(
            fn($room) => $room->isAvailableFor(
                $date,
                $startTime,
                $endTime
            )
        );
    }

    // ==================== STORE ====================

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'services' => 'required|array|min:1',
            'services.*' => 'exists:services,id',
            'appointment_date' =>
                'required|date|after_or_equal:today',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'guest_first_name' =>
                'required|string|max:255',
            'guest_phone' =>
                'required|string|regex:/^09\d{9}$/',
            'medical_notes' =>
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
            $services->sum('duration_minutes');

        $endTime = $request->end_time
            ? Carbon::parse(
                $request->appointment_date
                    . ' '
                    . $request->end_time,
                'Asia/Manila'
            )
            : $startTime
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

        if ($requiresRoom) {
            if ($request->filled('room_id')) {
                $room = Room::find(
                    $request->room_id
                );

                if (
                    !$room ||
                    !$room->isAvailableFor(
                        $request->appointment_date,
                        $startTimeStr,
                        $endTimeStr
                    )
                ) {
                    return $fail(
                        'The selected room is no longer available at this time. Please pick another slot.'
                    );
                }
            } else {
                $freeRoom = $this->findFreeRoom(
                    $services,
                    $request->appointment_date,
                    $startTimeStr,
                    $endTimeStr
                );

                if (!$freeRoom) {
                    return $fail(
                        'No rooms available for this time. Please choose a different time.'
                    );
                }

                $request->merge([
                    'room_id' => $freeRoom->id,
                ]);
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

            if ($request->filled('medical_notes')) {
                $customer->medical_notes =
                    $request->medical_notes;
            }

            $customer->save();
        } else {
            $existingCustomer = Customer::where(
                'phone_number',
                $request->guest_phone
            )->first();

            if ($existingCustomer) {
                $customer = $existingCustomer;

                if ($request->filled('medical_notes')) {
                    $customer->medical_notes =
                        $request->medical_notes;

                    $customer->save();
                }
            } else {
                $nameParts = preg_split(
                    '/\s+/',
                    trim($request->guest_first_name),
                    2
                );

                $firstName =
                    $nameParts[0] ?? '';

                $lastName =
                    $nameParts[1] ?? '';

                $customer = Customer::create([
                    'user_id' => null,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'customer_type' => 'regular',
                    'phone_number' =>
                        $request->guest_phone,
                    'medical_notes' =>
                        $request->medical_notes
                            ?? null,
                ]);
            }
        }

        $status = 'pending';
        $confirmedAt = null;

        if ($isReceptionist) {
            $status = 'confirmed';
            $confirmedAt = now();
        }

        $appointment = null;

        DB::transaction(function () use (
            $customer,
            $request,
            $services,
            $startTime,
            $endTime,
            $totalPrice,
            $status,
            $confirmedAt,
            $user,
            &$appointment
        ) {
            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'user_id' => $request->staff_id,
                'room_id' => $request->room_id,
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
                    $request->medical_notes
                    ?? $request->notes
                    ?? null,
                'ip_address' => $request->ip(), // Added IP address tracking
            ]);

            foreach ($services as $service) {
                AppointmentService::create([
                    'appointment_id' =>
                        $appointment->id,
                    'service_id' =>
                        $service->id,
                    'price_at_booking' =>
                        $service->discount_price
                        ?? $service->price,
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

        if (!$isReceptionist) {
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

        return redirect()->route(
            'booking.confirmation',
            $appointment->id
        );
    }

    public function confirmation(
        Appointment $appointment
    ) {
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
        $request->validate([
            'date' => 'required|date',
            'duration' => 'required|integer|min:15',
            'staff_id' => 'required|exists:users,id',
            'services' => 'nullable|array',
            'services.*' => 'exists:services,id',
            'buffer_minutes' =>
                'nullable|integer|min:0|max:60',
        ]);

        $date = $request->date;
        $duration = (int) $request->duration;
        $staffId = $request->staff_id;
        $serviceIds = $request->get('services', []);
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $tz = 'Asia/Manila';

        $services = empty($serviceIds)
            ? collect()
            : Service::whereIn(
                'id',
                $serviceIds
            )->get();

        $requiresRoom = $services->contains(
            fn($s) => $s->requires_room
        );

        $window = $this->getStaffWorkWindow($staffId, $date);

        if (!$window) {
            return response()->json([
                'gaps' => [],
            ]);
        }

        $bizWindow = \App\Services\BusinessScheduleService::getOperatingWindow($date);
        
        if (!$bizWindow['is_open']) {
            return response()->json([
                'gaps' => [],
            ]);
        }

        $workStart = max($window['start'], $bizWindow['start'] ?? '00:00');
        $workEnd = min($window['end'], $bizWindow['end'] ?? '23:59');

        if ($workStart >= $workEnd) {
            return response()->json([
                'gaps' => [],
            ]);
        }

        $appointments = Appointment::where(
            'user_id',
            $staffId
        )
            ->where(
                'appointment_date',
                $date
            )
            ->whereIn(
                'status',
                ['confirmed', 'completed', 'pending']
            )
            ->orderBy('start_time')
            ->get([
                'start_time',
                'end_time',
            ]);

        $busy = [];

        foreach ($appointments as $apt) {
            $busy[] = [
                'start' => $this->extractTime(
                    $apt->start_time
                ),
                'end' => $this->extractTime(
                    $apt->end_time
                ),
            ];
        }

        $gaps = [];

        if (empty($busy)) {
            $this->addGaps(
                $gaps,
                $workStart,
                $workEnd,
                $date,
                $duration,
                $requiresRoom,
                $services
            );
        } else {
            $current = $workStart;

            foreach ($busy as $interval) {
                if ($current < $interval['start']) {
                    $this->addGaps(
                        $gaps,
                        $current,
                        $interval['start'],
                        $date,
                        $duration,
                        $requiresRoom,
                        $services
                    );
                }

                $current = max(
                    $current,
                    $interval['end']
                );
            }

            if ($current < $workEnd) {
                $this->addGaps(
                    $gaps,
                    $current,
                    $workEnd,
                    $date,
                    $duration,
                    $requiresRoom,
                    $services
                );
            }
        }

        $isToday =
            $date ===
            Carbon::today($tz)->format('Y-m-d');

        if ($isToday) {
            $bufferMinutes = (int) $request->get(
                'buffer_minutes',
                0
            );

            $minCarbon = Carbon::now($tz)->addMinutes($bufferMinutes);

            $gaps = array_values(
                array_filter(
                    $gaps,
                    function($g) use ($minCarbon, $date, $tz) {
                        $timeStr = $g['time'];
                        $slotCarbon = Carbon::parse($date . ' ' . $timeStr, $tz);
                        
                        if ($timeStr < '05:00') {
                            $slotCarbon->addDay();
                        }
                        
                        return $slotCarbon->gte($minCarbon);
                    }
                )
            );
        }

        return response()->json([
            'gaps' => $gaps,
        ]);
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

    private function addGaps(
        array &$gaps,
        string $start,
        string $end,
        string $date,
        int $duration,
        bool $requiresRoom,
        $services
    ): void {
        $slotStart = Carbon::parse(
            $date . ' ' . $start
        );

        $windowEnd = Carbon::parse(
            $date . ' ' . $end
        );

        if ($end === '23:59') {
            $isReceptionist = auth()->user() && auth()->user()->roles()->where('name', 'receptionist')->exists();
            if ($isReceptionist) {
                $windowEnd->addHours(3);
            }
        }

        $requiredEnd = $slotStart
            ->copy()
            ->addMinutes($duration);

        while ($requiredEnd->lte($windowEnd)) {
            $timeStr =
                $slotStart->format('H:i');

            $endStr =
                $requiredEnd->format('H:i');

            $gap = [
                'time' => $timeStr,
                'end_time' => $endStr,
                'display' =>
                    $slotStart->format('g:i A')
                    . ' – '
                    . $requiredEnd->format('g:i A'),
                'free_rooms' => [],
            ];

            if ($requiresRoom) {
                $roomCategoryIds = $services
                    ->where('requires_room', true)
                    ->whereNotNull('room_category_id')
                    ->pluck('room_category_id')
                    ->unique()
                    ->values();

                $roomsQuery = Room::active()
                    ->where(
                        'status',
                        '!=',
                        'maintenance'
                    );

                if ($roomCategoryIds->isNotEmpty()) {
                    $roomsQuery->where(
                        function ($q)
                        use ($roomCategoryIds) {
                            $q->whereIn(
                                'category_id',
                                $roomCategoryIds
                            )
                                ->orWhereNull(
                                    'category_id'
                                );
                        }
                    );
                }

                foreach (
                    $roomsQuery->get()
                    as $room
                ) {
                    if (
                        $room->isAvailableFor(
                            $date,
                            $timeStr . ':00',
                            $endStr . ':00'
                        )
                    ) {
                        $gap['free_rooms'][] = [
                            'id' => $room->id,
                            'name' => $room->name,
                        ];
                    }
                }
            }

            $gaps[] = $gap;

            $slotStart->addMinutes(30);
            $requiredEnd->addMinutes(30);
        }
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
                    $date
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
             * Existing appointments for this staff member.
             */
            $appointments = Appointment::where(
                'user_id',
                $staff->id
            )
                ->whereDate(
                    'appointment_date',
                    $date
                )
                ->whereIn(
                    'status',
                    [
                        'confirmed',
                        'pending',
                        'completed',
                    ]
                )
                ->orderBy('start_time')
                ->get();

            $currentStart =
                $searchStartCarbon->copy();

            /*
             * Find gaps before appointments.
             */
            foreach ($appointments as $appointment) {
                $appointmentStart =
                    Carbon::parse(
                        $date . ' ' .
                        $this->extractTime(
                            $appointment->start_time
                        ),
                        $tz
                    );

                $appointmentEnd =
                    Carbon::parse(
                        $date . ' ' .
                        $this->extractTime(
                            $appointment->end_time
                        ),
                        $tz
                    );

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
                                $duration
                            )
                            ->lte($gapEnd)
                        &&
                        $currentStart
                            ->copy()
                            ->addMinutes(
                                $duration
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
                    ->addMinutes($duration)
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

        return response()->json([
            'slots' => $slots,
            'nextDayHint' => $nextDayHint,
        ]);
    }
}