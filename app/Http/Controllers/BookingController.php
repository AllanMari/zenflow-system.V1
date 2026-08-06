<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentService;
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
        return (int)$h * 60 + (int)$m;
    }

    private function isSlotValid($start, $end, int $duration, string $date, bool $requiresRoom, $services): bool
    {
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

    private function buildSlot(Carbon $startTime, int $duration, string $date, $staff, bool $requiresRoom, $services): ?array
    {
        $endTime = $startTime->copy()->addMinutes($duration);
        
        $freeRooms = [];
        if ($requiresRoom) {
            $roomCategoryIds = $services->where('requires_room', true)
                ->whereNotNull('room_category_id')
                ->pluck('room_category_id')
                ->unique()
                ->values();

            $roomsQuery = Room::active()->where('status', '!=', 'maintenance');
            if ($roomCategoryIds->isNotEmpty()) {
                $roomsQuery->where(function ($q) use ($roomCategoryIds) {
                    $q->whereIn('category_id', $roomCategoryIds)->orWhereNull('category_id');
                });
            }

            foreach ($roomsQuery->get() as $room) {
                if ($room->isAvailableFor($date, $startTime->format('H:i:s'), $endTime->format('H:i:s'))) {
                    $freeRooms[] = ['id' => $room->id, 'name' => $room->name];
                }
            }
            
            if (empty($freeRooms)) return null;
        }

        return [
            'time' => $startTime->format('H:i'),
            'end_time' => $endTime->format('H:i'),
            'display' => $startTime->format('g:i A') . ' – ' . $endTime->format('g:i A'),
            'staff_id' => $staff->id,
            'staff_name' => $staff->full_name ?? trim($staff->first_name . ' ' . $staff->last_name),
            'free_rooms' => $freeRooms,
        ];
    }
    // ==================== LANDING PAGE ====================
    public function landing()
    {
        $hero = (object) [
            'title'    => LandingSetting::where('key', 'hero_title')->value('value') ?? 'Spa Alexandria',
            'subtitle' => LandingSetting::where('key', 'hero_subtitle')->value('value') ?? '',
            'image'    => LandingSetting::where('key', 'hero_image')->value('value') ?? null,
        ];

        $benefits = [
            [
                'icon'  => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                'title' => 'Faster Booking',
                'desc'  => 'Save your details and book in seconds.',
            ],
            [
                'icon'  => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'title' => 'Track History',
                'desc'  => 'View past appointments and preferences.',
            ],
            [
                'icon'  => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
                'title' => 'Exclusive Offers',
                'desc'  => 'Members get special discounts and perks.',
            ],
            [
                'icon'  => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
                'title' => 'Personalized Care',
                'desc'  => 'We remember your favorite treatments.',
            ],
        ];

        $categories = ServiceCategory::with(['services' => fn($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('landing', compact('hero', 'benefits', 'categories'));
    }

    // ==================== PUBLIC WIZARD ====================
    public function wizard(Request $request)
    {
        $categories = ServiceCategory::where('is_active', true)
            ->whereHas('services', fn($q) => $q->where('is_active', true))
            ->with(['services' => fn($q) => $q->where('is_active', true)
                ->select('id', 'category_id', 'name', 'price', 'discount_price', 'duration_minutes', 
                        'is_package', 'included_services', 'deposit_percentage_min', 'deposit_percentage_max',
                        'description', 'image', 'landing_description', 'requires_room', 'room_category_id')
            ])
            ->get();

        $categoriesJson = $categories->map(function($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'color' => $cat->color,
                'services' => $cat->services->map(function($s) use ($cat) {
                    $included = null;
                    if ($s->included_services) {
                        $ids = is_string($s->included_services) ? json_decode($s->included_services, true) : $s->included_services;
                        if ($ids) {
                            $included = Service::whereIn('id', $ids)
                                ->select('id', 'name', 'duration_minutes')
                                ->get()
                                ->toArray();
                        }
                    }

                    $depMin = $s->deposit_percentage_min ?? $cat->deposit_percentage_min ?? 0;
                    $depMax = $s->deposit_percentage_max ?? $cat->deposit_percentage_max ?? 0;

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
                        'image' => $s->image ? asset($s->image) : null,
                        'requires_room' => $s->requires_room,
                        'room_category_id' => $s->room_category_id,
                    ];
                })
            ];
        })->toJson();

        $preselectedIds = [];
        $defaultName = '';
        $defaultPhone = '';
        $customerMedicalNotes = '';

        $user = auth()->user();

        if ($user && $user->roles()->where('name', 'customer')->exists()) {
            $customer = Customer::where('user_id', $user->id)->first();
            if ($customer) {
                $defaultName = $customer->first_name;
                $defaultPhone = $customer->phone_number ?? '';
                $customerMedicalNotes = $customer->medical_notes ?? '';
            } else {
                $defaultName = $user->first_name;
            }
        }

        if ($request->has('rebook_from')) {
            $query = Appointment::with('services')->where('id', $request->rebook_from);
            if ($user && $user->roles()->where('name', 'customer')->exists()) {
                $query->whereHas('customer', fn($q) => $q->where('user_id', $user->id));
            } elseif ($user) {
                $query->where('created_by', $user->id);
            }
            $rebook = $query->first();
            if ($rebook) {
                $preselectedIds = $rebook->services->pluck('id')->toArray();
            }
        }

        return view('booking.wizard', compact(
            'categoriesJson', 'preselectedIds', 'defaultName', 'defaultPhone', 
            'customerMedicalNotes'
        ));
    }

    // ==================== RECEPTIONIST QUICK BOOK ====================
    public function quickBook(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->roles()->where('name', 'receptionist')->exists()) {
            abort(403);
        }

        $categories = ServiceCategory::where('is_active', true)
            ->whereHas('services', fn($q) => $q->where('is_active', true))
            ->with(['services' => fn($q) => $q->where('is_active', true)
                ->select('id', 'category_id', 'name', 'price', 'discount_price', 'duration_minutes', 'requires_room', 'room_category_id')
            ])
            ->get();

        // Fetch ALL columns — restricting to first_name/last_name breaks accessors/full_name
        $staff = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))->get();

        $rooms = Room::active()->where('status', '!=', 'maintenance')->get();

        return view('receptionist.quick-book', compact('categories', 'staff', 'rooms'));
    }

    // ==================== API: SLOTS (merged endpoint) ====================
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

        $services = empty($serviceIds) ? collect() : Service::whereIn('id', $serviceIds)->get();
        $requiresRoom = $services->contains(fn($s) => $s->requires_room);

        $rooms = collect();
        $roomAppointments = collect();

        if ($requiresRoom) {
            $roomCategoryIds = $services->where('requires_room', true)
                ->whereNotNull('room_category_id')
                ->pluck('room_category_id')
                ->unique()
                ->values();

            $roomsQuery = Room::active()->where('status', '!=', 'maintenance');
            if ($roomCategoryIds->isNotEmpty()) {
                $roomsQuery->where(function ($q) use ($roomCategoryIds) {
                    $q->whereIn('category_id', $roomCategoryIds)->orWhereNull('category_id');
                });
            }
            $rooms = $roomsQuery->get();
        }

        $tz = 'Asia/Manila';
        $now = Carbon::now($tz);
        $slots = [];

        if ($specificDate) {
            $startDate = Carbon::parse($specificDate, $tz);
            $endDate = $startDate->copy();
        } else {
            $startDate = Carbon::today($tz);
            $endDate = $startDate->copy()->addDays(14);
        }

        if ($requiresRoom && $rooms->isNotEmpty()) {
            $dateList = [];
            for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
                if ($d->dayOfWeek !== Carbon::SUNDAY) {
                    $dateList[] = $d->format('Y-m-d');
                }
            }

            if (!empty($dateList)) {
                $roomAppointments = Appointment::whereIn('appointment_date', $dateList)
                    ->whereIn('status', ['confirmed', 'completed'])
                    ->whereNotNull('room_id')
                    ->get(['appointment_date', 'room_id', 'start_time', 'end_time'])
                    ->groupBy('appointment_date')
                    ->map(fn($group) => $group->groupBy('room_id'));
            }
        }

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->dayOfWeek === Carbon::SUNDAY) continue;

            $open = Carbon::parse($date->format('Y-m-d') . ' 10:00:00', $tz);
            $close = Carbon::parse($date->format('Y-m-d') . ' 20:00:00', $tz);
            $dateStr = $date->format('Y-m-d');
            $dayAppointments = $roomAppointments[$dateStr] ?? null;

            while ($open->lte($close)) {
                $slotEnd = $open->copy()->addMinutes($duration);
                $minLeadTime = $request->boolean('receptionist') ? 0 : 30;

                if ($slotEnd->lte($close) && $open->gt($now->copy()->addMinutes($minLeadTime))) {
                    $roomAvailable = true;
                    $freeRooms = [];

                    if ($requiresRoom) {
                        if ($rooms->isEmpty()) {
                            $roomAvailable = false;
                        } elseif ($dayAppointments && $dayAppointments->isNotEmpty()) {
                            $slotStartStr = $open->format('H:i:s');
                            $slotEndStr = $slotEnd->format('H:i:s');

                            foreach ($rooms as $room) {
                                $apts = $dayAppointments[$room->id] ?? collect();
                                $isFree = $apts->isEmpty() || !$apts->contains(function ($apt) use ($slotStartStr, $slotEndStr) {
                                    return ($slotStartStr < $apt->end_time) && ($slotEndStr > $apt->start_time);
                                });
                                if ($isFree) {
                                    $freeRooms[] = ['id' => $room->id, 'name' => $room->name];
                                }
                            }
                            $roomAvailable = count($freeRooms) > 0;
                        } else {
                            $freeRooms = $rooms->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->toArray();
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

        return response()->json([
            'slots' => $slots,
            'requires_room' => $requiresRoom,
            'room_count' => $rooms->count(),
        ]);
    }

    // ==================== API: CUSTOMER AUTOCOMPLETE ====================
    public function customerLookup(Request $request)
    {
        $request->validate(['phone' => 'required|string|min:6']);

        $customers = Customer::where('phone_number', 'like', '%' . $request->phone . '%')
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'phone_number']);

        return response()->json($customers->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->full_name,
            'phone' => $c->phone_number,
        ]));
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

        $slotsRes = $this->getSlotsInternal($duration, $serviceIds, true);
        $slots = $slotsRes['slots'];

        $today = Carbon::today('Asia/Manila')->format('Y-m-d');
        $nextSlot = collect($slots)->first(fn($s) => $s['room_available']);

        if (!$nextSlot) {
            return response()->json(['slot' => null, 'message' => 'No slots available']);
        }

        $date = $nextSlot['date'];
        $time = $nextSlot['time'];
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        $availableStaff = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->get()
            ->filter(function($staff) use ($date, $dayOfWeek, $time, $nextSlot, $duration) {
                $schedule = $staff->workSchedules()
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_day_off', false)
                    ->first();

                if (!$schedule) return false;

                $schStart = substr($schedule->start_time, 0, 5);
                $schEnd = substr($schedule->end_time, 0, 5);
                $slotEndTime = Carbon::parse($time)->addMinutes($duration)->format('H:i');

                if ($time < $schStart || $slotEndTime > $schEnd) return false;

                $conflict = Appointment::where('user_id', $staff->id)
                    ->where('appointment_date', $date)
                    ->where('status', 'confirmed')
                    ->where(function($q) use ($time, $slotEndTime) {
                        $q->whereBetween('start_time', [$time, $slotEndTime])
                          ->orWhereBetween('end_time', [$time, $slotEndTime])
                          ->orWhere(function($sq) use ($time, $slotEndTime) {
                              $sq->where('start_time', '<=', $time)
                                 ->where('end_time', '>=', $slotEndTime);
                          });
                    })->exists();

                return !$conflict;
            })
            ->map(fn($s) => ['id' => $s->id, 'name' => $s->full_name])
            ->values();

        return response()->json([
            'slot' => $nextSlot,
            'available_staff' => $availableStaff,
        ]);
    }

    private function getSlotsInternal(int $duration, array $serviceIds, bool $isReceptionist = false)
    {
        $services = empty($serviceIds) ? collect() : Service::whereIn('id', $serviceIds)->get();
        $requiresRoom = $services->contains(fn($s) => $s->requires_room);

        $rooms = collect();
        $roomAppointments = collect();

        if ($requiresRoom) {
            $roomCategoryIds = $services->where('requires_room', true)
                ->whereNotNull('room_category_id')
                ->pluck('room_category_id')
                ->unique()
                ->values();

            $roomsQuery = Room::active()->where('status', '!=', 'maintenance');
            if ($roomCategoryIds->isNotEmpty()) {
                $roomsQuery->where(function ($q) use ($roomCategoryIds) {
                    $q->whereIn('category_id', $roomCategoryIds)->orWhereNull('category_id');
                });
            }
            $rooms = $roomsQuery->get();

            $startDate = Carbon::today('Asia/Manila');
            $endDate = $startDate->copy()->addDays(14);
            $dateList = [];
            for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
                if ($d->dayOfWeek !== Carbon::SUNDAY) $dateList[] = $d->format('Y-m-d');
            }

            if (!empty($dateList)) {
                $roomAppointments = Appointment::whereIn('appointment_date', $dateList)
                    ->whereIn('status', ['confirmed', 'completed'])
                    ->whereNotNull('room_id')
                    ->get(['appointment_date', 'room_id', 'start_time', 'end_time'])
                    ->groupBy('appointment_date')
                    ->map(fn($group) => $group->groupBy('room_id'));
            }
        }

        $tz = 'Asia/Manila';
        $now = Carbon::now($tz);
        $slots = [];
        $startDate = Carbon::today($tz);
        $endDate = $startDate->copy()->addDays(14);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->dayOfWeek === Carbon::SUNDAY) continue;

            $open = Carbon::parse($date->format('Y-m-d') . ' 10:00:00', $tz);
            $close = Carbon::parse($date->format('Y-m-d') . ' 20:00:00', $tz);
            $dateStr = $date->format('Y-m-d');
            $dayAppointments = $roomAppointments[$dateStr] ?? null;

            while ($open->lte($close)) {
                $slotEnd = $open->copy()->addMinutes($duration);
                $minLeadTime = $isReceptionist ? 0 : 30;

                if ($slotEnd->lte($close) && $open->gt($now->copy()->addMinutes($minLeadTime))) {
                    $roomAvailable = true;
                    $freeRooms = [];

                    if ($requiresRoom) {
                        if ($rooms->isEmpty()) {
                            $roomAvailable = false;
                        } elseif ($dayAppointments && $dayAppointments->isNotEmpty()) {
                            $slotStartStr = $open->format('H:i:s');
                            $slotEndStr = $slotEnd->format('H:i:s');
                            foreach ($rooms as $room) {
                                $apts = $dayAppointments[$room->id] ?? collect();
                                $isFree = $apts->isEmpty() || !$apts->contains(function ($apt) use ($slotStartStr, $slotEndStr) {
                                    return ($slotStartStr < $apt->end_time) && ($slotEndStr > $apt->start_time);
                                });
                                if ($isFree) $freeRooms[] = ['id' => $room->id, 'name' => $room->name];
                            }
                            $roomAvailable = count($freeRooms) > 0;
                        } else {
                            $freeRooms = $rooms->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->toArray();
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

        return ['slots' => $slots, 'requires_room' => $requiresRoom, 'room_count' => $rooms->count()];
    }

    // ==================== STORE (shared by wizard + quick-book) ====================
    public function store(Request $request)
    {
        $request->validate([
            'services' => 'required|array|min:1',
            'services.*' => 'exists:services,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'guest_first_name' => 'required|string|max:255',
            'guest_phone' => 'required|string|regex:/^09\d{9}$/',
            'medical_notes' => 'nullable|string|max:2000',
            'staff_id' => 'nullable|exists:users,id',
            'room_id' => 'nullable|exists:rooms,id',
            'payment_method' => 'nullable|in:cash,card,gcash,paymaya,bank_transfer',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_type' => 'nullable|in:full,deposit',
            'walk_in_now' => 'nullable|boolean',
            'source' => 'nullable|in:public,receptionist',
        ]);

        $user = auth()->user();
        $isReceptionist = $user && $user->roles()->where('name', 'receptionist')->exists();
        $isCustomer = $user && $user->roles()->where('name', 'customer')->exists();
        $source = $request->get('source', 'public');

        if ($user) {
            session(['user_role' => strtolower($user->roles()->first()->name ?? 'customer')]);
        } else {
            session(['user_role' => 'guest']);
        }

        $services = Service::whereIn('id', $request->services)->get();
        $startTime = Carbon::parse($request->appointment_date . ' ' . $request->start_time, 'Asia/Manila');
        $totalDuration = $services->sum('duration_minutes');

        $endTime = $request->end_time 
            ? Carbon::parse($request->appointment_date . ' ' . $request->end_time, 'Asia/Manila')
            : $startTime->copy()->addMinutes($totalDuration);

        $totalPrice = $services->sum(fn($s) => $s->discount_price ?? $s->price);

        if ($isCustomer) {
            $customer = Customer::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $request->guest_first_name,
                    'last_name' => $user->last_name,
                    'phone_number' => $request->guest_phone,
                    'customer_type' => 'regular',
                ]
            );
        } else {
            $nameParts = explode(' ', $request->guest_first_name, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? 'Guest';

            $customer = Customer::firstOrCreate(
                ['phone_number' => $request->guest_phone],
                [
                    'user_id' => null,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'customer_type' => 'regular',
                ]
            );

            if ($customer->first_name !== $firstName) {
                $customer->update(['first_name' => $firstName, 'last_name' => $lastName]);
            }
        }

        if ($request->filled('medical_notes')) {
            $customer->update(['medical_notes' => $request->medical_notes]);
        }

        // RECEPTIONIST = ALWAYS CONFIRMED, NEVER PENDING
        $status = 'pending';
        $confirmedAt = null;

        if ($isReceptionist) {
            $status = 'confirmed';
            $confirmedAt = now();
        }

        $appointment = null;

        DB::transaction(function() use (
            $customer, $request, $services, $startTime, $endTime, 
            $totalPrice, $status, $confirmedAt, $user, &$appointment
        ) {
            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'user_id' => $request->staff_id,
                'room_id' => $request->room_id,
                'appointment_date' => $request->appointment_date,
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'status' => $status,
                'total_price' => $totalPrice,
                'created_by' => $user?->id,
                'confirmed_at' => $confirmedAt,
                'notes' => $request->medical_notes ?? $request->notes ?? null,
            ]);

            foreach ($services as $service) {
                AppointmentService::create([
                    'appointment_id' => $appointment->id,
                    'service_id' => $service->id,
                    'price_at_booking' => $service->discount_price ?? $service->price,
                ]);
            }

            if ($request->filled('payment_amount') && $request->payment_amount > 0) {
                $appointment->payments()->create([
                    'payment_method' => $request->payment_method ?? 'cash',
                    'amount' => $request->payment_amount,
                    'type' => $request->payment_type ?? 'full',
                    'paid_at' => now(),
                ]);
            }

            if ($request->room_id && $request->boolean('walk_in_now')) {
                Room::where('id', $request->room_id)->update(['status' => 'occupied']);
            }
        });

        // JSON response for Quick Book / AJAX — goes straight to active sessions
        if ($request->ajax() || $request->wantsJson() || $request->get('source') === 'receptionist') {
            return response()->json([
                'success' => true,
                'message' => 'Appointment confirmed for ' . $customer->first_name,
                'redirect' => route('receptionist.active'),
                'appointment_id' => $appointment->id,
            ]);
        }

        if ($isReceptionist && $request->boolean('walk_in_now')) {
            return redirect()->route('receptionist.active')
                ->with('success', 'Walk-in appointment confirmed for ' . $customer->first_name);
        }

        return redirect()->route('booking.confirmation', $appointment->id);
    }

    public function confirmation(Appointment $appointment)
    {
        $appointment->load('services', 'customer');
        return view('booking.confirmation', [
            'appointment' => $appointment,
            'role' => session('user_role', 'guest')
        ]);
    }

    public function occupiedSlots(Request $request)
    {
        $request->validate(['date' => 'required|date']);

        $occupied = Appointment::where('appointment_date', $request->date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get(['start_time', 'end_time'])
            ->map(fn($a) => [
                'start_time' => Carbon::parse($a->start_time)->format('H:i'),
                'end_time' => Carbon::parse($a->end_time)->format('H:i'),
            ]);

        return response()->json($occupied);
    }

    public function staffGaps(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'duration' => 'required|integer|min:15',
            'staff_id' => 'required|exists:users,id',
            'services' => 'nullable|array',
            'services.*' => 'exists:services,id',
            'buffer_minutes' => 'nullable|integer|min:0|max:60',
        ]);

        $date = $request->date;
        $duration = (int) $request->duration;
        $staffId = $request->staff_id;
        $serviceIds = $request->get('services', []);
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $tz = 'Asia/Manila';

        $services = empty($serviceIds) ? collect() : Service::whereIn('id', $serviceIds)->get();
        $requiresRoom = $services->contains(fn($s) => $s->requires_room);

        $exception = ScheduleException::where('user_id', $staffId)
            ->whereDate('exception_date', $date)
            ->first();

        if ($exception && in_array($exception->type, ['day_off', 'holiday', 'sick_leave', 'urgent_leave'])) {
            return response()->json(['gaps' => []]);
        }

        $schedule = WorkSchedule::where('user_id', $staffId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_day_off', false)
            ->first();

        if (!$schedule) {
            return response()->json(['gaps' => []]);
        }

        $workStart = $this->extractTime($schedule->start_time);
        $workEnd = $this->extractTime($schedule->end_time);

        if ($exception && $exception->type === 'custom_hours') {
            $workStart = $this->extractTime($exception->start_time);
            $workEnd = $this->extractTime($exception->end_time);
        }

        if (!$workStart || !$workEnd) {
            return response()->json(['gaps' => []]);
        }

        $appointments = Appointment::where('user_id', $staffId)
            ->where('appointment_date', $date)
            ->whereIn('status', ['confirmed', 'completed'])
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

        $busy = [];
        foreach ($appointments as $apt) {
            $busy[] = [
                'start' => $this->extractTime($apt->start_time),
                'end' => $this->extractTime($apt->end_time),
            ];
        }

        $gaps = [];

        if (empty($busy)) {
            $this->addGaps($gaps, $workStart, $workEnd, $date, $duration, $requiresRoom, $services);
        } else {
            $current = $workStart;
            foreach ($busy as $interval) {
                if ($current < $interval['start']) {
                    $this->addGaps($gaps, $current, $interval['start'], $date, $duration, $requiresRoom, $services);
                }
                $current = max($current, $interval['end']);
            }
            if ($current < $workEnd) {
                $this->addGaps($gaps, $current, $workEnd, $date, $duration, $requiresRoom, $services);
            }
        }

        // CRITICAL FIX: Always hide slots that are already in the past when booking for today
        $isToday = $date === Carbon::today($tz)->format('Y-m-d');
        if ($isToday) {
            $nowTime = Carbon::now($tz)->format('H:i');
            $gaps = array_values(array_filter($gaps, fn($g) => $g['time'] > $nowTime));
        }

        // Additional buffer for walk-ins (only when explicitly requested)
        $bufferMinutes = (int) $request->get('buffer_minutes', 0);
        if ($isToday && $bufferMinutes > 0) {
            $minTime = Carbon::now($tz)->addMinutes($bufferMinutes)->format('H:i');
            $gaps = array_values(array_filter($gaps, fn($g) => $g['time'] >= $minTime));
        }

        return response()->json(['gaps' => $gaps]);
    }

    private function extractTime($value): ?string
    {
        if (empty($value)) return null;
        if ($value instanceof \Carbon\Carbon) return $value->format('H:i');
        if (is_string($value) && strlen($value) > 8 && str_contains($value, ' ')) return substr($value, 11, 5);
        if (is_string($value) && strlen($value) === 8 && str_contains($value, ':')) return substr($value, 0, 5);
        if (is_string($value) && strlen($value) === 5 && str_contains($value, ':')) return $value;
        return null;
    }

    private function addGaps(array &$gaps, string $start, string $end, string $date, int $duration, bool $requiresRoom, $services): void
    {
        $slotStart = Carbon::parse($date . ' ' . $start);
        $windowEnd = Carbon::parse($date . ' ' . $end);
        $requiredEnd = $slotStart->copy()->addMinutes($duration);

        while ($requiredEnd->lte($windowEnd)) {
            $timeStr = $slotStart->format('H:i');
            $endStr = $requiredEnd->format('H:i');

            $gap = [
                'time' => $timeStr,
                'end_time' => $endStr,
                'display' => $slotStart->format('g:i A') . ' – ' . $requiredEnd->format('g:i A'),
                'free_rooms' => [],
            ];

            if ($requiresRoom) {
                $roomCategoryIds = $services->where('requires_room', true)
                    ->whereNotNull('room_category_id')
                    ->pluck('room_category_id')
                    ->unique()
                    ->values();

                $roomsQuery = Room::active()->where('status', '!=', 'maintenance');
                if ($roomCategoryIds->isNotEmpty()) {
                    $roomsQuery->where(function ($q) use ($roomCategoryIds) {
                        $q->whereIn('category_id', $roomCategoryIds)->orWhereNull('category_id');
                    });
                }

                foreach ($roomsQuery->get() as $room) {
                    if ($room->isAvailableFor($date, $timeStr . ':00', $endStr . ':00')) {
                        $gap['free_rooms'][] = ['id' => $room->id, 'name' => $room->name];
                    }
                }
            }

            $gaps[] = $gap;
            $slotStart->addMinutes(30);
            $requiredEnd->addMinutes(30);
        }
    }

public function nextSlots(Request $request)
{
    $request->validate([
        'date' => 'required|date',
        'duration' => 'required|integer|min:15',
        'services' => 'nullable|array',
        'services.*' => 'exists:services,id',
        'buffer_minutes' => 'nullable|integer|min:0|max:60',
    ]);

    $date = $request->date;
    $duration = (int) $request->duration;
    $serviceIds = $request->get('services', []);
    $bufferMinutes = (int) $request->get('buffer_minutes', 5);
    $dayOfWeek = Carbon::parse($date)->dayOfWeek;
    $tz = 'Asia/Manila';

    $services = empty($serviceIds) ? collect() : Service::whereIn('id', $serviceIds)->get();
    $requiresRoom = $services->contains(fn($s) => $s->requires_room);

    $staffMembers = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))->get();

    $now = Carbon::now($tz);
    $isToday = $date === $now->format('Y-m-d');
    
    $allSlots = [];

    foreach ($staffMembers as $staff) {
        $exception = ScheduleException::where('user_id', $staff->id)
            ->whereDate('exception_date', $date)
            ->first();

        if ($exception && in_array($exception->type, ['day_off', 'holiday', 'sick_leave', 'urgent_leave'])) {
            continue;
        }

        $schedule = WorkSchedule::where('user_id', $staff->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_day_off', false)
            ->first();

        if (!$schedule) continue;

        $workStart = $this->extractTime($schedule->start_time);
        $workEnd = $this->extractTime($schedule->end_time);

        if ($exception && $exception->type === 'custom_hours') {
            $workStart = $this->extractTime($exception->start_time);
            $workEnd = $this->extractTime($exception->end_time);
        }

        if (!$workStart || !$workEnd) continue;

        // Build Carbon objects for work window
        $workStartCarbon = Carbon::parse($date . ' ' . $workStart, $tz);
        $workEndCarbon = Carbon::parse($date . ' ' . $workEnd, $tz);

        // If today and shift already ended, skip
        if ($isToday && $now->gte($workEndCarbon)) continue;

        // Determine search start: now + buffer, clamped to work start
        $searchStart = $isToday 
            ? $now->copy()->addMinutes($bufferMinutes) 
            : $workStartCarbon->copy();

        // Can't start before shift begins
        if ($searchStart->lt($workStartCarbon)) {
            $searchStart = $workStartCarbon->copy();
        }

        $appointments = Appointment::where('user_id', $staff->id)
            ->where('appointment_date', $date)
            ->whereIn('status', ['confirmed', 'completed'])
            ->orderBy('start_time')
            ->get(['start_time', 'end_time']);

        $busy = [];
        foreach ($appointments as $apt) {
            $busy[] = [
                'start' => Carbon::parse($date . ' ' . $this->extractTime($apt->start_time), $tz),
                'end' => Carbon::parse($date . ' ' . $this->extractTime($apt->end_time), $tz),
            ];
        }

        $found = false;

        if (empty($busy)) {
            // Entire shift is free — can we fit starting from searchStart?
            if ($this->isSlotValid($searchStart, $workEndCarbon, $duration, $date, $requiresRoom, $services)) {
                $slot = $this->buildSlotQuick($searchStart, $duration, $date, $staff);
                if ($slot) {
                    $allSlots[] = $slot;
                    $found = true;
                }
            }
        } else {
            // Check gaps between appointments
            $currentStart = $workStartCarbon;

            foreach ($busy as $interval) {
                if ($currentStart->lt($interval['start'])) {
                    // Gap exists — can we fit starting from max(searchStart, currentStart)?
                    $gapStart = $searchStart->gt($currentStart) ? $searchStart : $currentStart;

                    if ($this->isSlotValid($gapStart, $interval['start'], $duration, $date, $requiresRoom, $services)) {
                        $slot = $this->buildSlot($gapStart, $duration, $date, $staff, $requiresRoom, $services);
                        if ($slot) {
                            $allSlots[] = $slot;
                            $found = true;
                            break;
                        }
                    }
                }
                $currentStart = $interval['end']->gt($currentStart) ? $interval['end'] : $currentStart;
            }

            // Check gap after last appointment
            if (!$found && $currentStart->lt($workEndCarbon)) {
                $gapStart = $searchStart->gt($currentStart) ? $searchStart : $currentStart;

                if ($this->isSlotValid($gapStart, $workEndCarbon, $duration, $date, $requiresRoom, $services)) {
                    $slot = $this->buildSlot($gapStart, $duration, $date, $staff, $requiresRoom, $services);
                    if ($slot) {
                        $allSlots[] = $slot;
                    }
                }
            }
        }
    }

    usort($allSlots, fn($a, $b) => $a['time'] <=> $b['time'] ?: $a['staff_name'] <=> $b['staff_name']);

    $slots = array_slice($allSlots, 0, 6);

    // If nothing today, find tomorrow's earliest opening
    $nextDayHint = null;
    $nextOpenTime = null;
    if (empty($slots) && $isToday) {
        $tomorrow = $now->copy()->addDay();
        $tomorrowDow = $tomorrow->dayOfWeek;

        $nextSchedules = WorkSchedule::whereIn('user_id', $staffMembers->pluck('id'))
            ->where('day_of_week', $tomorrowDow)
            ->where('is_day_off', false)
            ->get();

        if ($nextSchedules->isNotEmpty()) {
            $nextDayHint = $tomorrow->format('Y-m-d');
            $earliest = $nextSchedules
                ->map(fn($s) => $this->extractTime($s->start_time))
                ->filter()
                ->sort()
                ->first();
            $nextOpenTime = $earliest;
        }
    }

    return response()->json([
        'slots' => $slots,
        'requires_room' => $requiresRoom,
        'is_today' => $isToday,
        'next_day_hint' => $nextDayHint,
        'next_open_time' => $nextOpenTime,
    ]);
}

private function buildSlotQuick(Carbon $startTime, int $duration, string $date, $staff): array
{
    $endTime = $startTime->copy()->addMinutes($duration);
    
    return [
        'time' => $startTime->format('H:i'),
        'end_time' => $endTime->format('H:i'),
        'display' => $startTime->format('g:i A') . ' – ' . $endTime->format('g:i A'),
        'staff_id' => $staff->id,
        'staff_name' => $staff->full_name ?? trim($staff->first_name . ' ' . $staff->last_name),
        'free_rooms' => [], // Will be populated when user selects slot
    ];
}
}