<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Models\ScheduleException;
use App\Models\ShiftTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Room;

class ReceptionistController extends Controller
{
    // ================== DASHBOARD & BOOKINGS ==================
    public function desk()
    {
        $today = Carbon::today();
        
        $stats = [
            'pending' => Appointment::where('status', 'pending')->count(),
            'today' => Appointment::whereDate('appointment_date', $today)
                ->whereIn('status', ['confirmed', 'completed'])
                ->count(),
            'sales' => Payment::whereDate('paid_at', $today)
                ->whereIn('type', ['completion', 'additional', 'full'])
                ->sum('amount'),
        ];
        
        $pending = Appointment::with(['customer', 'services'])
            ->where('status', 'pending')
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->limit(20)
            ->get();
            
        return view('receptionist-dashboard', compact('stats', 'pending'));
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

    $request->validate([
        'staff_id'       => 'required|exists:users,id',
        'room_id'        => 'nullable|exists:rooms,id',
        'payment_method' => 'required|in:cash,card,gcash,paymaya,bank_transfer',
        'payment_type'   => 'required|in:full,deposit',
        'amount'         => 'required|numeric|min:0',
        'notes'          => 'nullable|string|max:500',
    ]);

    // Check staff conflict
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

    // Check room requirements and conflicts
    $requiresRoom = $appointment->services->contains(function ($s) {
        return $s->requires_room;
    });

    if ($requiresRoom && $request->room_id) {
        $room = Room::find($request->room_id);
        
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

    // Calculate required deposit
    $depositRequired = $appointment->services->sum(function ($s) {
        $min = $s->deposit_percentage_min ?? $s->category->deposit_percentage_min ?? 0;
        return ($s->pivot->price_at_booking ?? $s->price) * ($min / 100);
    });

    // Update appointment
    $appointment->update([
        'user_id'          => $request->staff_id,
        'room_id'          => $request->room_id,
        'status'           => 'confirmed',
        'confirmed_at'     => now(),
        'deposit_required' => $depositRequired,
        'notes'            => ($appointment->notes ?? '') . ($request->notes ? "\n[Rec: " . $request->notes . "]" : ""),
    ]);

    // Update room status to occupied if assigned
    if ($request->room_id) {
        Room::where('id', $request->room_id)->update(['status' => 'occupied']);
    }

    // Record payment
    Payment::create([
        'appointment_id' => $appointment->id,
        'payment_method' => $request->payment_method,
        'amount'         => $request->amount,
        'type'           => $request->payment_type,
        'paid_at'        => now(),
    ]);

    $msg = $request->payment_type === 'deposit'
        ? 'Booking confirmed! Deposit of ₱' . number_format($request->amount, 2) . ' recorded. Balance due at counter.'
        : 'Booking confirmed! Full payment recorded.';

    return redirect()->route('receptionist.active')->with('success', $msg);
}

public function cancel(Request $request, Appointment $appointment)
{
    $request->validate(['reason' => 'required|in:customer_no_show,customer_cancelled,staff_unavailable,other']);
    
    $reason = $request->reason === 'other' ? $request->other_reason : str_replace('_', ' ', $request->reason);
    
    // Release room before cancelling
    if ($appointment->room_id) {
        Room::where('id', $appointment->room_id)->update(['status' => 'available']);
    }

    $appointment->status = 'cancelled';
    $appointment->cancellation_reason = $request->reason;
    $appointment->save();

    if ($request->reason === 'customer_no_show') {
        $existingPayment = Payment::where('appointment_id', $appointment->id)->first();
        if ($existingPayment && $existingPayment->amount > 0) {
            Payment::create([
                'appointment_id' => $appointment->id, 
                'payment_method' => $existingPayment->payment_method, 
                'amount' => -$existingPayment->amount, 
                'type' => 'refund', 
                'paid_at' => now(),
            ]);
        }
    }
    
    return redirect()->route('receptionist.dashboard')->with('success', 'Cancelled: ' . $reason);
}


    public function pending()
    {
        $appointments = Appointment::where('status', 'pending')->with(['customer', 'services'])->orderBy('appointment_date')->orderBy('start_time')->get();
        return view('receptionist.pending', compact('appointments'));
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
    private function getAvailableStaff($date, $startTime, $endTime, $excludeAppointmentId = null)
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $dateStr = Carbon::parse($date)->toDateString();

        return User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->get()
            ->filter(function($staff) use ($dateStr, $dayOfWeek, $startTime, $endTime) {
                // Check exception (day_off, holiday, sick_leave, urgent_leave)
                $exception = ScheduleException::where('user_id', $staff->id)
                    ->whereDate('exception_date', $dateStr)
                    ->first();

                if ($exception && in_array($exception->type, ['day_off', 'holiday', 'sick_leave', 'urgent_leave'])) {
                    return false; // Staff is off
                }

                // Check work schedule
                $schedule = WorkSchedule::where('user_id', $staff->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->first();

                if (!$schedule || $schedule->is_day_off) {
                    return false; // No schedule or day off
                }

                // Check if appointment time is outside work hours
                $schStart = $this->extractTime($schedule->start_time);
                $schEnd = $this->extractTime($schedule->end_time);

                if ($startTime < $schStart || $endTime > $schEnd) {
                    return false; // Outside hours
                }

                // Check custom hours exception
                if ($exception && $exception->type === 'custom_hours') {
                    $exStart = $this->extractTime($exception->start_time);
                    $exEnd = $this->extractTime($exception->end_time);
                    if ($startTime < $exStart || $endTime > $exEnd) {
                        return false; // Outside custom hours
                    }
                }

                return true; // Staff passes all checks
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

                // Check for conflicts
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

        /**
     * Add extra service during active session
     */
public function addExtraService(Request $request, Appointment $appointment)
{
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

    // Recalculate total AND extend end_time
    $appointment->load('services');
    $newTotal = $appointment->services->sum(function ($s) {
        return $s->pivot->custom_price ?? $s->pivot->price_at_booking;
    });
    
    // Extend end_time by the new service's duration
    $newService = $appointment->services->last();
    $extraMinutes = $newService->duration_minutes ?? 0;
    
    if ($extraMinutes > 0) {
        $newEndTime = \Carbon\Carbon::parse($appointment->end_time)->addMinutes($extraMinutes);
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
        'amount'         => 'required|numeric|min:0',
        'payment_type'   => 'required|in:full,completion',
    ]);

    $totalPaid = $appointment->payments()->sum('amount');
    $balanceDue = $appointment->total_price - $totalPaid;

    if ($request->amount < $balanceDue && !$request->has('force_complete')) {
        return back()->with('error', 'Customer still owes ₱' . number_format($balanceDue - $request->amount, 2));
    }

    if ($request->amount > 0) {
        Payment::create([
            'appointment_id' => $appointment->id,
            'payment_method' => $request->payment_method,
            'amount'         => $request->amount,
            'type'           => $request->payment_type,
            'paid_at'        => now(),
        ]);
    }

    // Release room when appointment completes
    if ($appointment->room_id) {
        Room::where('id', $appointment->room_id)->update(['status' => 'available']);
    }

    $appointment->update(['status' => 'completed']);

    return redirect()->route('receptionist.active')->with('success', 'Appointment completed!');
}
public function handleNoShow(Request $request, Appointment $appointment)
{
    $request->validate(['action' => 'required|in:forfeit,refund']);

    $depositPayment = $appointment->payments()->where('type', 'deposit')->first();

    // Release room before marking no-show
    if ($appointment->room_id) {
        Room::where('id', $appointment->room_id)->update(['status' => 'available']);
    }

    if ($request->action === 'forfeit') {
        $appointment->status = 'cancelled';
        $appointment->cancellation_reason = 'customer_no_show';
        $appointment->save();
        
        return back()->with('success', 'Deposit forfeited. Appointment marked as no-show.');
    }

    if ($depositPayment) {
        Payment::create([
            'appointment_id' => $appointment->id,
            'payment_method' => $depositPayment->payment_method,
            'amount' => -$depositPayment->amount,
            'type' => 'refund',
            'paid_at' => now(),
        ]);
    }

    $appointment->status = 'cancelled';
    $appointment->cancellation_reason = 'customer_no_show';
    $appointment->save();

    return back()->with('success', 'Deposit refunded. Appointment marked as no-show.');
}
public function active()
{
    $today = Carbon::today();
    $appointments = Appointment::with(['customer', 'services', 'payments', 'staff', 'room'])
        ->where('status', 'confirmed')
        ->orderBy('appointment_date')
        ->orderBy('start_time')
        ->get();

    return view('receptionist.active', compact('appointments'));
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

    // Get category name from the appointment's services
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