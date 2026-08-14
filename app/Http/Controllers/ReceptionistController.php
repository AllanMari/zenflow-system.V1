<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Models\ScheduleException;
use App\Models\ShiftTemplate;
use App\Models\Room;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReceptionistController extends Controller
{
    // ================== DASHBOARD & BOOKINGS ==================
    public function desk()
    {
        $today = Carbon::today();

        $stats = [
            'pending' => Appointment::pendingValid()->count(),
            'today' => Appointment::whereDate('appointment_date', $today)
                ->whereIn('status', ['confirmed', 'completed'])
                ->count(),
            'sales' => Payment::whereDate('paid_at', $today)
                ->whereIn('type', ['completion', 'additional', 'full'])
                ->sum('amount'),
        ];

        $pending = Appointment::with(['customer', 'services'])
            ->pendingValid()
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->limit(20)
            ->get();

        // Find absent staff with confirmed appointments today
        $absentStaffIds = Attendance::whereDate('date', $today)
            ->whereIn('status', ['absent', 'on_leave', 'holiday'])
            ->pluck('user_id');

        $absentStaffConflicts = collect();
        if ($absentStaffIds->isNotEmpty()) {
            $absentStaffConflicts = Appointment::with(['customer', 'staff', 'services'])
                ->whereDate('appointment_date', $today)
                ->where('status', 'confirmed')
                ->whereIn('user_id', $absentStaffIds)
                ->orderBy('start_time')
                ->get();
        }

        return view('receptionist-dashboard', compact('stats', 'pending', 'absentStaffConflicts'));
    }

    public function booking(Appointment $appointment)
    {
        $appointment->load(['customer', 'services']);
        $availableStaff = $this->getAvailableStaff($appointment->appointment_date, $appointment->start_time, $appointment->end_time, $appointment->id);
        return view('receptionist.booking', compact('appointment', 'availableStaff'));
    }

    public function confirm(Request $request, Appointment $appointment)
    {
        $appointment->load(['services.category', 'customer']);

        $meta = $this->getAppointmentDepositMeta($appointment);
        $requiresDeposit = $meta['requiresDeposit'];
        $depositRequired = $meta['systemDepositRequired'];

        $validPaymentTypes = $requiresDeposit ? 'deposit,full' : 'deposit,full,cash_on_site';

        $request->validate([
            'staff_id'       => 'required|exists:users,id',
            'room_id'        => 'nullable|exists:rooms,id',
            'payment_method' => 'required|in:cash,card,gcash,paymaya,bank_transfer',
            'payment_type'   => 'required|in:' . $validPaymentTypes,
            'amount'         => 'required|numeric|min:0',
            'notes'          => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($request, $appointment, $requiresDeposit, $depositRequired, $meta) {
            // Re-fetch with lock to prevent race conditions
            $appointment = Appointment::lockForUpdate()->findOrFail($appointment->id);
            $appointment->load(['services.category', 'customer']);

            // ── Staff availability (re-checked inside transaction) ──
            $staffAvailable = $this->getAvailableStaff(
                $appointment->appointment_date,
                $appointment->start_time,
                $appointment->end_time,
                $appointment->id
            )->firstWhere('user.id', (int) $request->staff_id);

            if (!$staffAvailable || !$staffAvailable['available']) {
                return back()->with('error', 'Selected staff is not available for this time slot. Reason: ' . ($staffAvailable['status_label'] ?? 'Not scheduled'));
            }

            // ── Staff conflict (double booking) ──
            $conflict = Appointment::where('user_id', $request->staff_id)
                ->where('appointment_date', $appointment->appointment_date)
                ->where('status', 'confirmed')
                ->where('id', '!=', $appointment->id)
                ->where(function ($q) use ($appointment) {
                    $q->whereBetween('start_time', [$appointment->start_time, $appointment->end_time])
                      ->orWhereBetween('end_time', [$appointment->start_time, $appointment->end_time])
                      ->orWhere(function ($sq) use ($appointment) {
                          $sq->where('start_time', '<=', $appointment->start_time)
                             ->where('end_time', '>=', $appointment->end_time);
                      });
                })->exists();

            if ($conflict) {
                return back()->with('error', 'Staff no longer available. Please select another.');
            }

            // ── Room requirements and conflicts ──
            $requiresRoom = $appointment->services->contains(function ($s) {
                return $s->requires_room;
            });

            if ($requiresRoom && $request->room_id) {
                $room = Room::lockForUpdate()->find($request->room_id);

                if (!$room) {
                    return back()->with('error', 'Selected room no longer exists.');
                }

                // Validate room category compatibility
                $requiredCategoryIds = $appointment->services
                    ->where('requires_room', true)
                    ->whereNotNull('room_category_id')
                    ->pluck('room_category_id')
                    ->unique()
                    ->values();

                if ($requiredCategoryIds->isNotEmpty() && $room->category_id) {
                    if (!$requiredCategoryIds->contains($room->category_id)) {
                        return back()->with('error', 'Selected room does not match service category requirements.');
                    }
                }

                // Check room availability
                if (!$room->isAvailableFor(
                    $appointment->appointment_date,
                    $appointment->start_time,
                    $appointment->end_time,
                    $appointment->id
                )) {
                    return back()->with('error', 'Room is no longer available for this time slot.');
                }
            }

            // ── Validate amounts ──
            if ($request->payment_type === 'deposit') {
                if ($request->amount < $depositRequired) {
                    return back()->with('error', 'Deposit amount must be at least ₱' . number_format($depositRequired, 2));
                }
                if ($request->amount > $appointment->total_price) {
                    return back()->with('error', 'Deposit cannot exceed total price of ₱' . number_format($appointment->total_price, 2));
                }
            }

            if ($request->payment_type === 'full' && $request->amount < $appointment->total_price) {
                return back()->with('error', 'Full payment must be at least ₱' . number_format($appointment->total_price, 2));
            }

            if ($request->payment_type === 'cash_on_site' && $request->amount != 0) {
                return back()->with('error', 'Cash on site amount must be 0.');
            }

            // ── Update appointment ──
            $appointment->update([
                'user_id'          => $request->staff_id,
                'room_id'          => $request->room_id,
                'status'           => 'confirmed',
                'confirmed_at'     => now(),
                'confirmed_by'     => auth()->id(),
                'deposit_required' => $depositRequired,
                'notes'            => ($appointment->notes ?? '') . ($request->notes ? "\n[Rec: " . $request->notes . "]" : ""),
            ]);

            // Update room status to occupied if assigned
            if ($request->room_id) {
                Room::where('id', $request->room_id)->update(['status' => 'occupied']);
            }

            // Record payment ONLY for deposit or full (not cash_on_site)
            if (in_array($request->payment_type, ['deposit', 'full'])) {
                Payment::create([
                    'appointment_id' => $appointment->id,
                    'payment_method' => $request->payment_method,
                    'amount'         => $request->amount,
                    'type'           => $request->payment_type,
                    'paid_at'        => now(),
                ]);
            }

            $msg = match ($request->payment_type) {
                'deposit' => 'Booking confirmed! Deposit of ₱' . number_format($request->amount, 2) . ' recorded. Balance due at counter.',
                'full' => 'Booking confirmed! Full payment of ₱' . number_format($request->amount, 2) . ' recorded.',
                'cash_on_site' => 'Booking confirmed! Customer will pay ₱' . number_format($appointment->total_price, 2) . ' at the counter.',
                default => 'Booking confirmed!',
            };

            // Notify customer
            if ($appointment->customer && $appointment->customer->user) {
                NotificationController::sendTo(
                    $appointment->customer->user,
                    'Booking Confirmed',
                    'Your appointment on ' . \Carbon\Carbon::parse($appointment->appointment_date)->format('M j, Y') . ' is confirmed.',
                    'booking',
                    'success',
                    route('customer.index'),
                    'My Bookings'
                );
            }

            // Notify assigned staff
            if ($appointment->staff) {
                NotificationController::sendTo(
                    $appointment->staff,
                    'New Assignment',
                    ($appointment->customer->full_name ?? 'Walk-in') . ' — ' . \Carbon\Carbon::parse($appointment->appointment_date)->format('M j') . ' at ' . \Carbon\Carbon::parse($appointment->start_time)->format('g:i A'),
                    'booking',
                    'info',
                    route('staff.index'),
                    'My Schedule'
                );
            }

            return redirect()->route('receptionist.active')->with('success', $msg);
        });
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $request->validate(['reason' => 'required|in:customer_cancelled,staff_unavailable,other']);

        $reason = $request->reason === 'other' ? $request->other_reason : str_replace('_', ' ', $request->reason);

        // Release room before cancelling
        if ($appointment->room_id) {
            Room::where('id', $appointment->room_id)->update(['status' => 'available']);
        }

        $appointment->status = 'cancelled';
        $appointment->cancellation_reason = $request->reason;
        $appointment->save();

        // ── NOTIFICATIONS ──

        if ($appointment->customer && $appointment->customer->user) {
            NotificationController::sendTo(
                $appointment->customer->user,
                'Appointment Cancelled',
                'Your appointment on ' . \Carbon\Carbon::parse($appointment->appointment_date)->format('M j, Y') . ' was cancelled. Reason: ' . ucfirst($reason),
                'booking',
                'warning',
                route('customer.index'),
                'My Bookings'
            );
        }

        if ($appointment->staff) {
            NotificationController::sendTo(
                $appointment->staff,
                'Assignment Cancelled',
                ($appointment->customer->full_name ?? 'Walk-in') . "'s appointment on " . \Carbon\Carbon::parse($appointment->appointment_date)->format('M j') . ' at ' . \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') . ' was cancelled. Reason: ' . ucfirst($reason),
                'booking',
                'warning',
                route('staff.index'),
                'My Schedule'
            );
        }

        $admins = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get();
        if ($admins->isNotEmpty()) {
            NotificationController::sendTo(
                $admins,
                'Appointment Cancelled',
                ($appointment->customer->full_name ?? 'Walk-in') . "'s appointment was cancelled. Reason: " . ucfirst($reason),
                'booking',
                'warning',
                route('admin.appointments', ['status' => 'cancelled']),
                'Review'
            );
        }

        return redirect()->route('receptionist.dashboard')->with('success', 'Cancelled: ' . $reason);
    }

    public function pending()
    {
        $now = now();

        /* ── AUTO-EXPIRE OVERDUE (no cron/console needed) ──
        Every time this page loads, we check for pending bookings
        that are past their appointment date and mark them expired.
        This is lightweight — it only touches overdue records. */
        Appointment::pendingOverdue()
            ->limit(100) // safety cap per request
            ->get()
            ->each->markAsExpired();

        // Now fetch only valid pending bookings (today or future)
        $appointments = Appointment::pendingValid()
            ->with(['customer', 'services.category'])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->paginate(20);

        // Pre-compute staff availability for each appointment
        $staffAvailability = [];
        foreach ($appointments as $appt) {
            $available = $this->getAvailableStaff(
                $appt->appointment_date,
                $appt->start_time,
                $appt->end_time,
                $appt->id
            );
            $staffAvailability[$appt->id] = $available->keyBy(fn($item) => $item['user']->id);
        }

        $allStaff = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get();

        // Pre-compute deposit/pricing metadata for each appointment
        $appointmentMeta = [];
        foreach ($appointments as $appt) {
            $appointmentMeta[$appt->id] = $this->getAppointmentDepositMeta($appt);
        }

        // Pre-compute urgency data in controller (was doing heavy Carbon work in Blade)
        foreach ($appointments as $appt) {
            $dateStr = $appt->appointment_date->toDateString();
            $apptDateTime = Carbon::parse($dateStr . ' ' . $appt->start_time);
            $diffInMinutes = $now->diffInMinutes($apptDateTime, false);

            $appt->computed_urgency = [
                'is_overdue'    => $diffInMinutes < 0,
                'is_soon'       => $diffInMinutes >= 0 && $diffInMinutes <= 120,
                'is_today'      => $apptDateTime->isToday(),
                'diff_minutes'  => $diffInMinutes,
                'datetime'      => $apptDateTime,
                'date_string'   => $dateStr,
                'timestamp'     => $apptDateTime->timestamp,
            ];
        }

        $pendingCount = Appointment::pendingValid()->count();

        $urgentCount = $appointments->filter(function($a) use ($now) {
            $u = $a->computed_urgency;
            return $u['is_soon'] || ($u['is_overdue'] && $u['diff_minutes'] >= -60);
        })->count();

        return view('receptionist.pending', compact(
            'appointments',
            'staffAvailability',
            'allStaff',
            'appointmentMeta',
            'pendingCount',
            'urgentCount'
        ));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $request->validate(['first_name' => 'required|string|max:255', 'last_name' => 'required|string|max:255']);
        $user->update(['first_name' => $request->first_name, 'last_name' => $request->last_name]);
        return redirect()->back()->with('success', 'Profile updated successfully!');
    }

    // ================== SCHEDULING ==================
    public function schedules(Request $request)
    {
        $weekStart = $request->get('week_start') ? Carbon::parse($request->get('week_start'))->startOfWeek() : now()->startOfWeek();
        $staff = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))->with(['workSchedules', 'scheduleExceptions' => fn($q) => $q->whereBetween('exception_date', [$weekStart, $weekStart->copy()->endOfWeek()])])->get();
        $templates = ShiftTemplate::where('is_active', true)->get();

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $days[] = ['date' => $date->toDateString(), 'label' => $date->format('D'), 'day' => $date->format('j'), 'dow' => $date->dayOfWeek, 'is_today' => $date->isToday()];
        }

        $timeline = [];
        foreach ($staff as $s) {
            $row = ['user' => $s, 'days' => []];
            $schedules = $s->workSchedules->keyBy('day_of_week');
            for ($i = 0; $i < 7; $i++) {
                $date = $weekStart->copy()->addDays($i); $dow = $date->dayOfWeek; $dateStr = $date->toDateString();
                $sch = $schedules[$dow] ?? null; $ex = $s->scheduleExceptions->firstWhere('exception_date', $dateStr);
                $cell = ['date' => $dateStr, 'dow' => $dow, 'type' => 'off', 'start_time' => null, 'end_time' => null, 'exception' => null, 'exception_type' => null];
                if ($ex) {
                    $cell['type'] = 'exception'; $cell['exception'] = $ex;
                    if ($ex->type === 'custom_hours') { $cell['start_time'] = $this->extractTime($ex->start_time); $cell['end_time'] = $this->extractTime($ex->end_time); $cell['exception_type'] = 'custom'; }
                    else { $cell['exception_type'] = $ex->type; }
                } elseif ($sch && !$sch->is_day_off) { $cell['type'] = 'work'; $cell['start_time'] = $this->extractTime($sch->start_time); $cell['end_time'] = $this->extractTime($sch->end_time); }
                $row['days'][] = $cell;
            }
            $timeline[] = $row;
        }

        return view('shared.schedule-timeline', [
            'isAdmin' => false, 'canEdit' => auth()->user()->can_manage_schedules, 'staff' => $staff,
            'receptionists' => collect(), 'templates' => $templates, 'days' => $days, 'timeline' => $timeline,
            'weekStart' => $weekStart->toDateString(), 'weekEnd' => $weekStart->copy()->endOfWeek()->toDateString(),
            'weekLabel' => $weekStart->format('M j') . ' – ' . $weekStart->copy()->endOfWeek()->format('M j, Y'),
        ]);
    }

    public function applyTemplate(Request $request, User $user)
    {
        $request->validate(['template_id' => 'required|exists:shift_templates:id', 'week_start' => 'nullable|date']);
        if (!auth()->user()->can_manage_schedules) abort(403);
        $template = ShiftTemplate::findOrFail($request->template_id);
        $template->applyToUser($user, $request->week_start ? Carbon::parse($request->week_start) : null);
        return response()->json(['success' => true]);
    }

    public function quickBlock(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id', 'block_type' => 'required|in:single,range',
            'exception_type' => 'required|in:day_off,holiday,sick_leave,urgent_leave,custom_hours',
            'date' => 'required|date', 'end_date' => 'nullable|date|after_or_equal:date',
            'start_time' => 'nullable|required_if:exception_type,custom_hours',
            'end_time' => 'nullable|required_if:exception_type,custom_hours|after:start_time',
            'reason' => 'nullable|string|max:255',
        ]);
        if (!auth()->user()->can_manage_schedules) abort(403);
        $start = Carbon::parse($request->date); $end = $request->block_type === 'range' && $request->end_date ? Carbon::parse($request->end_date) : $start;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            ScheduleException::updateOrCreate(
                ['user_id' => $request->user_id, 'exception_date' => $date->toDateString()],
                ['type' => in_array($request->exception_type, ['sick_leave', 'urgent_leave']) ? 'day_off' : $request->exception_type, 'start_time' => $request->exception_type === 'custom_hours' ? $request->start_time : null, 'end_time' => $request->exception_type === 'custom_hours' ? $request->end_time : null, 'reason' => $request->reason]
            );
        }
        return back()->with('success', 'Block added successfully');
    }

    public function bulkUpdateSchedules(Request $request)
    {
        if (!auth()->user()->can_manage_schedules) abort(403);
        $schedules = $request->schedules ?? [];
        if (array_key_exists(0, $schedules)) {
            $normalized = [];
            foreach ($schedules as $item) { $uid = $item['user_id']; $dow = $item['day_of_week']; $normalized[$uid][$dow] = $item; }
            $schedules = $normalized;
        }
        foreach ($schedules as $userId => $days) {
            foreach ($days as $dayNum => $data) {
                $isOff = isset($data['is_day_off']) && $data['is_day_off'] == '1';
                WorkSchedule::updateOrCreate(['user_id' => $userId, 'day_of_week' => $dayNum], ['start_time' => $isOff ? null : $data['start_time'], 'end_time' => $isOff ? null : $data['end_time'], 'is_day_off' => $isOff]);
            }
        }
        return back()->with('success', 'Schedule updated');
    }

    // ================== ENHANCED STAFF AVAILABILITY ==================
    public function getAvailableStaff($date, $startTime, $endTime, $excludeAppointmentId = null)
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $dateStr = Carbon::parse($date)->toDateString();
        $isToday = $dateStr === now()->toDateString();

        return User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->where('is_active', true) // <-- FIX: only active staff
            ->get()
            ->filter(function($staff) use ($dateStr, $dayOfWeek, $startTime, $endTime, $isToday) {
                $exception = ScheduleException::where('user_id', $staff->id)
                    ->whereDate('exception_date', $dateStr)
                    ->first();

                if ($exception && in_array($exception->type, ['day_off', 'holiday', 'sick_leave', 'urgent_leave'])) {
                    return false;
                }

                $schedule = WorkSchedule::where('user_id', $staff->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->first();

                if (!$schedule || $schedule->is_day_off) {
                    return false;
                }

                $schStart = $this->extractTime($schedule->start_time);
                $schEnd = $this->extractTime($schedule->end_time);

                if ($startTime < $schStart || $endTime > $schEnd) {
                    return false;
                }

                if ($exception && $exception->type === 'custom_hours') {
                    $exStart = $this->extractTime($exception->start_time);
                    $exEnd = $this->extractTime($exception->end_time);
                    if ($startTime < $exStart || $endTime > $exEnd) {
                        return false;
                    }
                }

                if ($isToday) {
                    $attendance = Attendance::where('user_id', $staff->id)
                        ->whereDate('date', $dateStr)
                        ->first();

                    if ($attendance && in_array($attendance->status, ['absent', 'on_leave', 'holiday'])) {
                        return false;
                    }
                }

                return true;
            })
            ->map(function($staff) use ($date, $dateStr, $dayOfWeek, $startTime, $endTime, $excludeAppointmentId) {
                $exception = ScheduleException::where('user_id', $staff->id)
                    ->whereDate('exception_date', $dateStr)
                    ->first();

                $schedule = WorkSchedule::where('user_id', $staff->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_day_off', false)
                    ->first();

                $schStart = $this->extractTime($schedule->start_time);
                $schEnd = $this->extractTime($schedule->end_time);

                $query = Appointment::where('user_id', $staff->id)
                    ->where('appointment_date', $date)
                    ->where('status', 'confirmed');

                if ($excludeAppointmentId) {
                    $query->where('id', '!=', $excludeAppointmentId);
                }

                $busy = $query->where(function($q) use ($startTime, $endTime) {
                    $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function($sq) use ($startTime, $endTime) {
                        $sq->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
                })->exists();

                if ($busy) {
                    return [
                        'user' => $staff,
                        'available' => false,
                        'status' => 'conflict',
                        'status_label' => 'Double Booked',
                        'reason' => null,
                        'hours' => $schStart . ' - ' . $schEnd
                    ];
                }

                return [
                    'user' => $staff,
                    'available' => true,
                    'status' => 'available',
                    'status_label' => 'Available',
                    'reason' => null,
                    'hours' => $schStart . ' - ' . $schEnd
                ];
            });
    }

    private function extractTime($value)
    {
        if (empty($value)) return null;
        if (strlen($value) > 8 && str_contains($value, ' ')) return substr($value, 11, 5);
        if (strlen($value) === 8 && str_contains($value, ':')) return substr($value, 0, 5);
        if (strlen($value) === 5 && str_contains($value, ':')) return $value;
        return null;
    }

    private function getAppointmentDepositMeta(Appointment $appointment): array
    {
        $serviceBreakdown = [];
        $totalDepositRequired = 0;
        $hasDepositRequired = false;
        $maxDepositPercent = 0;

        foreach ($appointment->services as $service) {
            $price = (float) ($service->pivot->price_at_booking ?? $service->price ?? 0);

            $serviceMinPercent = (int) ($service->deposit_percentage_min ?? 0);
            $categoryMinPercent = (int) ($service->category?->deposit_percentage_min ?? 0);
            $minPercent = $serviceMinPercent > 0 ? $serviceMinPercent : $categoryMinPercent;

            $requiresDeposit = $service->requires_prepayment || $minPercent > 0;

            $serviceDeposit = 0;
            if ($requiresDeposit) {
                $hasDepositRequired = true;
                $serviceDeposit = $price * ($minPercent / 100);
                $totalDepositRequired += $serviceDeposit;
                $maxDepositPercent = max($maxDepositPercent, $minPercent);
            }

            $serviceBreakdown[] = [
                'service'          => $service,
                'price'            => $price,
                'requires_deposit' => $requiresDeposit,
                'min_percent'      => $minPercent,
                'deposit_amount'   => $serviceDeposit,
            ];
        }

        return [
            'hasDepositRequired'    => $hasDepositRequired,
            'totalDepositRequired'  => $totalDepositRequired,
            'serviceBreakdown'      => $serviceBreakdown,
            'totalPrice'            => (float) $appointment->total_price,
            'requiresDeposit'       => $hasDepositRequired,
            'systemDepositRequired' => $totalDepositRequired,
            'hasDeposit'            => $hasDepositRequired,
            'depositAmount'         => $totalDepositRequired,
            'maxDepositPercent'     => $maxDepositPercent,
        ];
    }

    public function addExtraService(Request $request, Appointment $appointment)
    {
        $request->validate([
            'service_id' => 'required_without:custom_service_id|exists:services,id',
            'custom_service_id' => 'required_without:service_id|exists:services,id',
        ]);

        if ($request->filled('service_id')) {
            $service = $appointment->services()->where('services.id', $request->service_id)->first();
            if (!$service) {
                return back()->with('error', 'Service not found on this appointment.');
            }

            $appointment->services()->attach($service->id, [
                'price_at_booking'   => $service->pivot->price_at_booking,
                'service_name'       => $service->name,
                'service_duration'   => $service->duration_minutes,
                'is_extra'           => true,
            ]);

            $message = "Added extra {$service->name}";
        }
        elseif ($request->filled('custom_service_id')) {
            $service = \App\Models\Service::findOrFail($request->custom_service_id);

            $appointment->services()->attach($service->id, [
                'price_at_booking'   => $service->price,
                'service_name'       => $service->name,
                'service_duration'   => $service->duration_minutes,
                'is_extra'           => true,
            ]);

            $message = "Added {$service->name} (₱" . number_format($service->price, 2) . ")";
        } else {
            return back()->with('error', 'Please select a service.');
        }

        $appointment->load('services');
        $newTotal = $appointment->services->sum(function ($s) {
            return $s->pivot->custom_price ?? $s->pivot->price_at_booking;
        });

        $newService = $appointment->services->last();
        $extraMinutes = $newService->duration_minutes ?? 0;

        if ($extraMinutes > 0) {
            $newEndTime = \Carbon\Carbon::parse($appointment->end_time)->addMinutes($extraMinutes);

            if ($appointment->user_id) {
                $staffConflict = Appointment::where('user_id', $appointment->user_id)
                    ->where('appointment_date', $appointment->appointment_date)
                    ->where('status', 'confirmed')
                    ->where('id', '!=', $appointment->id)
                    ->where(function ($q) use ($appointment, $newEndTime) {
                        $q->whereBetween('start_time', [$appointment->start_time, $newEndTime->format('H:i:s')])
                        ->orWhereBetween('end_time', [$appointment->start_time, $newEndTime->format('H:i:s')])
                        ->orWhere(function ($sq) use ($appointment, $newEndTime) {
                            $sq->where('start_time', '<=', $appointment->start_time)
                                ->where('end_time', '>=', $newEndTime->format('H:i:s'));
                        });
                    })->exists();

                if ($staffConflict) {
                    $appointment->services()->detach($newService->pivot->id);
                    return back()->with('error', 'Cannot add extra service: new end time (' . $newEndTime->format('g:i A') . ') conflicts with another appointment for this staff.');
                }
            }

            if ($appointment->room_id) {
                $roomConflict = Appointment::where('room_id', $appointment->room_id)
                    ->where('appointment_date', $appointment->appointment_date)
                    ->where('status', 'confirmed')
                    ->where('id', '!=', $appointment->id)
                    ->where(function ($q) use ($appointment, $newEndTime) {
                        $q->whereBetween('start_time', [$appointment->start_time, $newEndTime->format('H:i:s')])
                        ->orWhereBetween('end_time', [$appointment->start_time, $newEndTime->format('H:i:s')])
                        ->orWhere(function ($sq) use ($appointment, $newEndTime) {
                            $sq->where('start_time', '<=', $appointment->start_time)
                                ->where('end_time', '>=', $newEndTime->format('H:i:s'));
                        });
                    })->exists();

                if ($roomConflict) {
                    $appointment->services()->detach($newService->pivot->id);
                    return back()->with('error', 'Cannot add extra service: new end time (' . $newEndTime->format('g:i A') . ') conflicts with another appointment in this room.');
                }
            }

            $appointment->update([
                'total_price' => $newTotal,
                'end_time' => $newEndTime->format('H:i:s'),
            ]);
        } else {
            $appointment->update(['total_price' => $newTotal]);
        }

        return back()->with('success', $message);
    }

    public function complete(Request $request, Appointment $appointment)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,card,gcash,paymaya,bank_transfer',
            'payment_type'   => 'required|in:full,completion',
        ]);

        if ($appointment->status === 'completed') {
            return back()->with('error', 'This appointment is already completed.');
        }

        return DB::transaction(function () use ($request, $appointment) {
            $lockedAppt = Appointment::lockForUpdate()->find($appointment->id);

            $totalPaid = $lockedAppt->payments()->sum('amount');
            $balanceDue = $lockedAppt->total_price - $totalPaid;

            if ($balanceDue <= 0) {
                if ($lockedAppt->room_id) {
                    Room::where('id', $lockedAppt->room_id)->update(['status' => 'available']);
                }
                $lockedAppt->update(['status' => 'completed']);

                if ($lockedAppt->customer && $lockedAppt->customer->user) {
                    \App\Http\Controllers\NotificationController::sendTo(
                        $lockedAppt->customer->user,
                        'Thank You!',
                        'Your appointment on ' . \Carbon\Carbon::parse($lockedAppt->appointment_date)->format('M j') . ' is complete. We hope to see you again!',
                        'booking',
                        'success',
                        route('customer.index'),
                        'My Bookings'
                    );
                }

                return redirect()->route('receptionist.active')->with('success', 'Appointment completed!');
            }

            Payment::create([
                'appointment_id' => $lockedAppt->id,
                'payment_method' => $request->payment_method,
                'amount'         => $balanceDue,
                'type'           => $request->payment_type,
                'paid_at'        => now(),
            ]);

            if ($lockedAppt->room_id) {
                Room::where('id', $lockedAppt->room_id)->update(['status' => 'available']);
            }

            $lockedAppt->update(['status' => 'completed']);

            if ($lockedAppt->customer && $lockedAppt->customer->user) {
                \App\Http\Controllers\NotificationController::sendTo(
                    $lockedAppt->customer->user,
                    'Thank You!',
                    'Your appointment on ' . \Carbon\Carbon::parse($lockedAppt->appointment_date)->format('M j') . ' is complete. We hope to see you again!',
                    'booking',
                    'success',
                    route('customer.index'),
                    'My Bookings'
                );
            }

            return redirect()->route('receptionist.active')->with('success', 'Appointment completed! Payment recorded: ₱' . number_format($balanceDue, 2));
        });
    }

    public function handleNoShow(Request $request, Appointment $appointment)
    {
        $request->validate(['action' => 'required|in:forfeit,refund']);

        $totalPaid = $appointment->payments()->sum('amount');
        $hasPayments = $totalPaid > 0;

        if ($appointment->room_id) {
            Room::where('id', $appointment->room_id)->update(['status' => 'available']);
        }

        if ($request->action === 'forfeit') {
            $appointment->status = 'cancelled';
            $appointment->cancellation_reason = 'customer_no_show';
            $appointment->save();

            $msg = $hasPayments
                ? 'Payment of ₱' . number_format($totalPaid, 2) . ' forfeited. Appointment marked as no-show.'
                : 'Appointment marked as no-show.';

            return back()->with('success', $msg);
        }

        if ($hasPayments) {
            $originalPayment = $appointment->payments()->orderBy('paid_at', 'asc')->first();

            Payment::create([
                'appointment_id' => $appointment->id,
                'payment_method' => $originalPayment->payment_method,
                'amount' => -$totalPaid,
                'type' => 'refund',
                'paid_at' => now(),
            ]);
        }

        $appointment->status = 'cancelled';
        $appointment->cancellation_reason = 'customer_no_show';
        $appointment->save();

        return back()->with('success', 'Payment of ₱' . number_format($totalPaid, 2) . ' refunded. Appointment marked as no-show.');
    }

    public function active()
    {
        // FIX: Only show confirmed appointments for today onwards.
        // Historical confirmed appointments that were never completed/no-showed
        // should not linger in the active list forever.
        $appointments = Appointment::with(['customer', 'services', 'payments', 'staff', 'room'])
            ->where('status', 'confirmed')
            ->whereDate('appointment_date', '>=', today())
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();

        return view('receptionist.active', compact('appointments'));
    }

    public function reassignStaff(Request $request, int $appointmentId)
    {
        $appointment = Appointment::with(['services.category', 'room'])->findOrFail($appointmentId);

        $request->validate(['staff_id' => 'required|exists:users,id']);

        $available = $this->getAvailableStaff(
            $appointment->appointment_date,
            $appointment->start_time,
            $appointment->end_time,
            $appointment->id
        )->firstWhere('user.id', (int) $request->staff_id);

        if (!$available || !$available['available']) {
            return back()->with('error', 'Selected staff is not available for this time slot.');
        }

        if ($appointment->room_id) {
            $requiredCategoryIds = $appointment->services
                ->where('requires_room', true)
                ->whereNotNull('room_category_id')
                ->pluck('room_category_id')
                ->unique()
                ->values();

            if ($requiredCategoryIds->isNotEmpty() && $appointment->room->category_id) {
                if (!$requiredCategoryIds->contains($appointment->room->category_id)) {
                    return back()->with('error', 'Current room (' . $appointment->room->name . ') does not match the service category requirements. Please reschedule to a compatible room first.');
                }
            }
        }

        $appointment->update(['user_id' => $request->staff_id]);

        return back()->with('success', 'Staff reassigned to ' . $available['user']->full_name . '.');
    }

    public function reschedule(Request $request, int $appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        $duration = $appointment->services->sum('duration_minutes');
        $newEndTime = Carbon::parse($request->start_time)->addMinutes($duration)->format('H:i:s');

        if ($appointment->user_id) {
            $staffAvailable = $this->getAvailableStaff(
                $request->appointment_date,
                $request->start_time,
                $newEndTime,
                $appointment->id
            )->firstWhere('user.id', $appointment->user_id);

            if (!$staffAvailable || !$staffAvailable['available']) {
                return back()->with('error', 'Current staff is not available at the new time. Please reassign staff first.');
            }
        }

        $oldRoomId = $appointment->room_id;
        $newRoomId = $request->room_id;

        if ($newRoomId && $newRoomId != $oldRoomId) {
            $room = Room::find($newRoomId);
            if ($room && !$room->isAvailableFor($request->appointment_date, $request->start_time, $newEndTime, $appointment->id)) {
                return back()->with('error', 'Selected room is not available at the new time.');
            }
        }

        $appointment->update([
            'appointment_date' => $request->appointment_date,
            'start_time' => $request->start_time,
            'end_time' => $newEndTime,
            'room_id' => $newRoomId,
        ]);

        if ($oldRoomId && $oldRoomId != $newRoomId) {
            Room::where('id', $oldRoomId)->update(['status' => 'available']);
        }

        if ($newRoomId && $newRoomId != $oldRoomId) {
            Room::where('id', $newRoomId)->update(['status' => 'occupied']);
        }

        if ($appointment->customer && $appointment->customer->user) {
            \App\Http\Controllers\NotificationController::sendTo(
                $appointment->customer->user,
                'Appointment Rescheduled',
                'Moved to ' . \Carbon\Carbon::parse($appointment->appointment_date)->format('M j, Y') . ' at ' . \Carbon\Carbon::parse($appointment->start_time)->format('g:i A'),
                'booking',
                'warning',
                route('customer.index'),
                'My Bookings'
            );
        }

        if ($appointment->staff) {
            \App\Http\Controllers\NotificationController::sendTo(
                $appointment->staff,
                'Appointment Rescheduled',
                'An appointment was moved to ' . \Carbon\Carbon::parse($appointment->appointment_date)->format('M j') . ' at ' . \Carbon\Carbon::parse($appointment->start_time)->format('g:i A'),
                'booking',
                'warning',
                route('staff.index'),
                'My Schedule'
            );
        }

        return back()->with('success', 'Appointment rescheduled successfully.');
    }

    public function availableRooms(Request $request, Appointment $appointment)
    {
        $appointment->load('services.category');

        $requiresRoom = $appointment->services->contains(function ($service) {
            return $service->requires_room;
        });

        if (!$requiresRoom) {
            return response()->json([
                'requires_room' => false,
                'rooms' => [],
                'message' => 'No room required for this service combination.'
            ]);
        }

        $requiredCategoryId = $appointment->services
            ->where('requires_room', true)
            ->whereNotNull('room_category_id')
            ->pluck('room_category_id')
            ->first();

        $date = $appointment->appointment_date;
        $startTime = $appointment->start_time;
        $endTime = $appointment->end_time;

        $query = Room::active()
            ->where('status', '!=', 'maintenance');

        if ($requiredCategoryId) {
            $query->where(function ($q) use ($requiredCategoryId) {
                $q->where('category_id', $requiredCategoryId)
                  ->orWhereNull('category_id');
            });
        }

        $rooms = $query->get()->filter(function ($room) use ($date, $startTime, $endTime, $appointment) {
            return $room->isAvailableFor($date, $startTime, $endTime, $appointment->id);
        })->values();

        $categoryName = null;
        if ($requiredCategoryId) {
            $categoryService = $appointment->services->firstWhere('room_category_id', $requiredCategoryId);
            $categoryName = $categoryService?->category?->name;
        }

        return response()->json([
            'requires_room' => true,
            'required_category' => $categoryName,
            'rooms' => $rooms->map(function ($room) {
                return [
                    'id' => $room->id,
                    'name' => $room->name,
                    'category' => $room->category?->name,
                    'is_general' => is_null($room->category_id),
                ];
            }),
            'message' => $rooms->isEmpty() ? 'No rooms available for this time slot.' : null
        ]);
    }
}