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
use App\Traits\ScheduleTrait;

class ReceptionistController extends Controller
{
    use ScheduleTrait;

    /* ─── DASHBOARD ─── */

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
        $availableStaff = $this->schedGetAvailableStaff(
            $appointment->appointment_date,
            $appointment->start_time,
            $appointment->end_time,
            $appointment->id
        );
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
            'staff_id' => 'required|exists:users,id',
            'room_id' => 'nullable|exists:rooms,id',
            'payment_method' => 'required|in:cash,card,gcash,paymaya,bank_transfer',
            'payment_type' => 'required|in:' . $validPaymentTypes,
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($request, $appointment, $requiresDeposit, $depositRequired, $meta) {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointment->id);
            $appointment->load(['services.category', 'customer']);

            $staffAvailable = $this->schedGetAvailableStaff(
                $appointment->appointment_date,
                $appointment->start_time,
                $appointment->end_time,
                $appointment->id
            )->firstWhere('user.id', (int) $request->staff_id);

            if (!$staffAvailable || !$staffAvailable['available']) {
                return back()->with('error', 'Selected staff is not available for this time slot. Reason: ' . ($staffAvailable['status_label'] ?? 'Not scheduled'));
            }

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

            $requiresRoom = $appointment->services->contains(fn($s) => $s->requires_room);
            if ($requiresRoom && $request->room_id) {
                $room = Room::lockForUpdate()->find($request->room_id);
                if (!$room) return back()->with('error', 'Selected room no longer exists.');

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

                if (!$room->isAvailableFor(
                    $appointment->appointment_date,
                    $appointment->start_time,
                    $appointment->end_time,
                    $appointment->id
                )) {
                    return back()->with('error', 'Room is no longer available for this time slot.');
                }
            }

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

            $appointment->update([
                'user_id' => $request->staff_id,
                'room_id' => $request->room_id,
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
                'deposit_required' => $depositRequired,
                'notes' => ($appointment->notes ?? '') . ($request->notes ? "\n[Rec: " . $request->notes . "]" : ""),
            ]);

            if ($request->room_id) {
                Room::where('id', $request->room_id)->update(['status' => 'occupied']);
            }

            if (in_array($request->payment_type, ['deposit', 'full'])) {
                Payment::create([
                    'appointment_id' => $appointment->id,
                    'payment_method' => $request->payment_method,
                    'amount' => $request->amount,
                    'type' => $request->payment_type,
                    'paid_at' => now(),
                ]);
            }

            $msg = match ($request->payment_type) {
                'deposit' => 'Booking confirmed! Deposit of ₱' . number_format($request->amount, 2) . ' recorded. Balance due at counter.',
                'full' => 'Booking confirmed! Full payment of ₱' . number_format($request->amount, 2) . ' recorded.',
                'cash_on_site' => 'Booking confirmed! Customer will pay ₱' . number_format($appointment->total_price, 2) . ' at the counter.',
                default => 'Booking confirmed!',
            };

            if ($appointment->customer && $appointment->customer->user) {
                NotificationController::sendTo(
                    $appointment->customer->user,
                    'Booking Confirmed',
                    'Your appointment on ' . Carbon::parse($appointment->appointment_date)->format('M j, Y') . ' is confirmed.',
                    'booking', 'success', route('customer.index'), 'My Bookings'
                );
            }
            if ($appointment->staff) {
                NotificationController::sendTo(
                    $appointment->staff,
                    'New Assignment',
                    ($appointment->customer->full_name ?? 'Walk-in') . ' — ' . Carbon::parse($appointment->appointment_date)->format('M j') . ' at ' . Carbon::parse($appointment->start_time)->format('g:i A'),
                    'booking', 'info', route('staff.index'), 'My Schedule'
                );
            }

            return redirect()->route('receptionist.active')->with('success', $msg);
        });
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $request->validate(['reason' => 'required|in:customer_cancelled,staff_unavailable,other']);
        $reason = $request->reason === 'other' ? $request->other_reason : str_replace('_', ' ', $request->reason);

        if ($appointment->room_id) {
            Room::where('id', $appointment->room_id)->update(['status' => 'available']);
        }

        $appointment->status = 'cancelled';
        $appointment->cancellation_reason = $request->reason;
        $appointment->save();

        if ($appointment->customer && $appointment->customer->user) {
            NotificationController::sendTo(
                $appointment->customer->user,
                'Appointment Cancelled',
                'Your appointment on ' . Carbon::parse($appointment->appointment_date)->format('M j, Y') . ' was cancelled. Reason: ' . ucfirst($reason),
                'booking', 'warning', route('customer.index'), 'My Bookings'
            );
        }
        if ($appointment->staff) {
            NotificationController::sendTo(
                $appointment->staff,
                'Assignment Cancelled',
                ($appointment->customer->full_name ?? 'Walk-in') . "'s appointment on " . Carbon::parse($appointment->appointment_date)->format('M j') . ' at ' . Carbon::parse($appointment->start_time)->format('g:i A') . ' was cancelled. Reason: ' . ucfirst($reason),
                'booking', 'warning', route('staff.index'), 'My Schedule'
            );
        }
        $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get();
        if ($admins->isNotEmpty()) {
            NotificationController::sendTo(
                $admins,
                'Appointment Cancelled',
                ($appointment->customer->full_name ?? 'Walk-in') . "'s appointment was cancelled. Reason: " . ucfirst($reason),
                'booking', 'warning', route('admin.appointments', ['status' => 'cancelled']), 'Review'
            );
        }

        return redirect()->route('receptionist.dashboard')->with('success', 'Cancelled: ' . $reason);
    }

    public function pending()
    {
        $now = now();

        Appointment::pendingOverdue()->limit(100)->get()->each->markAsExpired();

        $appointments = Appointment::pendingValid()
            ->with(['customer', 'services.category'])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->paginate(20);

        $staffAvailability = [];
        $appointmentMeta = [];
        $urgentCount = 0;

        foreach ($appointments as $appt) {
            $staffAvailability[$appt->id] = $this->schedGetAvailableStaff(
                $appt->appointment_date,
                $appt->start_time,
                $appt->end_time,
                $appt->id
            )->keyBy(fn($item) => $item['user']->id);

            $appointmentMeta[$appt->id] = $this->getAppointmentDepositMeta($appt);

            $dateStr = $appt->appointment_date->toDateString();
            $apptDateTime = Carbon::parse($dateStr . ' ' . $appt->start_time);
            $diffInMinutes = $now->diffInMinutes($apptDateTime, false);

            $appt->computed_urgency = [
                'is_overdue' => $diffInMinutes < 0,
                'is_soon' => $diffInMinutes >= 0 && $diffInMinutes <= 120,
                'is_today' => $apptDateTime->isToday(),
                'diff_minutes' => $diffInMinutes,
                'datetime' => $apptDateTime,
                'date_string' => $dateStr,
                'timestamp' => $apptDateTime->timestamp,
            ];

            $u = $appt->computed_urgency;
            if ($u['is_soon'] || ($u['is_overdue'] && $u['diff_minutes'] >= -60)) {
                $urgentCount++;
            }
        }

        $allStaff = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get();

        $pendingCount = Appointment::pendingValid()->count();

        return view('receptionist.pending', compact(
            'appointments', 'staffAvailability', 'allStaff',
            'appointmentMeta', 'pendingCount', 'urgentCount'
        ));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]);
        $user->update(['first_name' => $request->first_name, 'last_name' => $request->last_name]);
        return redirect()->back()->with('success', 'Profile updated successfully!');
    }


    /* ─── APPOINTMENT ACTIONS ─── */

    public function addExtraService(Request $request, Appointment $appointment)
    {
        $request->validate([
            'service_id' => 'required_without:custom_service_id|exists:services,id',
            'custom_service_id' => 'required_without:service_id|exists:services,id',
        ]);

        if ($request->filled('service_id')) {
            $service = $appointment->services()->where('services.id', $request->service_id)->first();
            if (!$service) return back()->with('error', 'Service not found on this appointment.');
            $appointment->services()->attach($service->id, [
                'price_at_booking' => $service->pivot->price_at_booking,
                'service_name' => $service->name,
                'service_duration' => $service->duration_minutes,
                'is_extra' => true,
            ]);
            $message = "Added extra {$service->name}";
        } elseif ($request->filled('custom_service_id')) {
            $service = \App\Models\Service::findOrFail($request->custom_service_id);
            $appointment->services()->attach($service->id, [
                'price_at_booking' => $service->price,
                'service_name' => $service->name,
                'service_duration' => $service->duration_minutes,
                'is_extra' => true,
            ]);
            $message = "Added {$service->name} (₱" . number_format($service->price, 2) . ")";
        } else {
            return back()->with('error', 'Please select a service.');
        }

        $appointment->load('services');
        $newTotal = $appointment->services->sum(fn($s) => $s->pivot->custom_price ?? $s->pivot->price_at_booking);
        $newService = $appointment->services->last();
        $extraMinutes = $newService->duration_minutes ?? 0;

        if ($extraMinutes > 0) {
            $newEndTime = Carbon::parse($appointment->end_time)->addMinutes($extraMinutes);

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

            $appointment->update(['total_price' => $newTotal, 'end_time' => $newEndTime->format('H:i:s')]);
        } else {
            $appointment->update(['total_price' => $newTotal]);
        }

        return back()->with('success', $message);
    }

    public function complete(Request $request, Appointment $appointment)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,card,gcash,paymaya,bank_transfer',
            'payment_type' => 'required|in:full,completion',
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
                $this->notifyCustomerCompletion($lockedAppt);
                return redirect()->route('receptionist.active')->with('success', 'Appointment completed!');
            }

            Payment::create([
                'appointment_id' => $lockedAppt->id,
                'payment_method' => $request->payment_method,
                'amount' => $balanceDue,
                'type' => $request->payment_type,
                'paid_at' => now(),
            ]);

            if ($lockedAppt->room_id) {
                Room::where('id', $lockedAppt->room_id)->update(['status' => 'available']);
            }
            $lockedAppt->update(['status' => 'completed']);
            $this->notifyCustomerCompletion($lockedAppt);

            return redirect()->route('receptionist.active')
                ->with('success', 'Appointment completed! Payment recorded: ₱' . number_format($balanceDue, 2));
        });
    }

    private function notifyCustomerCompletion($appointment): void
    {
        if ($appointment->customer && $appointment->customer->user) {
            \App\Http\Controllers\NotificationController::sendTo(
                $appointment->customer->user,
                'Thank You!',
                'Your appointment on ' . Carbon::parse($appointment->appointment_date)->format('M j') . ' is complete. We hope to see you again!',
                'booking', 'success', route('customer.index'), 'My Bookings'
            );
        }
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

        $available = $this->schedGetAvailableStaff(
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
                    return back()->with('error', 'Current room does not match service category requirements. Please reschedule to a compatible room first.');
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
            $staffAvailable = $this->schedGetAvailableStaff(
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
                'Moved to ' . Carbon::parse($appointment->appointment_date)->format('M j, Y') . ' at ' . Carbon::parse($appointment->start_time)->format('g:i A'),
                'booking', 'warning', route('customer.index'), 'My Bookings'
            );
        }
        if ($appointment->staff) {
            \App\Http\Controllers\NotificationController::sendTo(
                $appointment->staff,
                'Appointment Rescheduled',
                'An appointment was moved to ' . Carbon::parse($appointment->appointment_date)->format('M j') . ' at ' . Carbon::parse($appointment->start_time)->format('g:i A'),
                'booking', 'warning', route('staff.index'), 'My Schedule'
            );
        }

        return back()->with('success', 'Appointment rescheduled successfully.');
    }

    public function availableRooms(Request $request, Appointment $appointment)
    {
        $appointment->load('services.category');
        $requiresRoom = $appointment->services->contains(fn($s) => $s->requires_room);

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

        $query = Room::active()->where('status', '!=', 'maintenance');
        if ($requiredCategoryId) {
            $query->where(function ($q) use ($requiredCategoryId) {
                $q->where('category_id', $requiredCategoryId)->orWhereNull('category_id');
            });
        }

        $rooms = $query->get()->filter(function ($room) use ($appointment) {
            return $room->isAvailableFor(
                $appointment->appointment_date,
                $appointment->start_time,
                $appointment->end_time,
                $appointment->id
            );
        })->values();

        $categoryName = null;
        if ($requiredCategoryId) {
            $categoryService = $appointment->services->firstWhere('room_category_id', $requiredCategoryId);
            $categoryName = $categoryService?->category?->name;
        }

        return response()->json([
            'requires_room' => true,
            'required_category' => $categoryName,
            'rooms' => $rooms->map(fn($room) => [
                'id' => $room->id,
                'name' => $room->name,
                'category' => $room->category?->name,
                'is_general' => is_null($room->category_id),
            ]),
            'message' => $rooms->isEmpty() ? 'No rooms available for this time slot.' : null
        ]);
    }

    /* ─── DEPOSIT META ─── */

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
                'service' => $service,
                'price' => $price,
                'requires_deposit' => $requiresDeposit,
                'min_percent' => $minPercent,
                'deposit_amount' => $serviceDeposit,
            ];
        }

        return [
            'hasDepositRequired' => $hasDepositRequired,
            'totalDepositRequired' => $totalDepositRequired,
            'serviceBreakdown' => $serviceBreakdown,
            'totalPrice' => (float) $appointment->total_price,
            'requiresDeposit' => $hasDepositRequired,
            'systemDepositRequired' => $totalDepositRequired,
            'hasDeposit' => $hasDepositRequired,
            'depositAmount' => $totalDepositRequired,
            'maxDepositPercent' => $maxDepositPercent,
        ];
    }
}