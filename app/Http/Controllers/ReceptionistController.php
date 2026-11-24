<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentPackageSelection;
use App\Models\Payment;
use App\Services\TherapistAssignmentService;
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

    /* ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ DASHBOARD ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ */

    public function desk()
    {
        $today = Carbon::today('Asia/Manila');

        // 1. Operational KPIs
        $confirmedToday = Appointment::whereDate('appointment_date', $today)
            ->where('status', 'confirmed')
            ->count();

        $completedToday = Appointment::whereDate('appointment_date', $today)
            ->where('status', 'completed')
            ->count();

        // Net cash collected today (all payment types net of refunds stored as negative amounts)
        $collectionsToday = (float) Payment::whereDate('paid_at', $today)->sum('amount');

        // Staff attendance metrics (present/late records for active users with staff role)
        $presentStaffCount = Attendance::whereDate('date', $today)
            ->whereIn('status', ['present', 'late'])
            ->whereHas('user', fn($q) => $q->where('is_active', true)->whereHas('roles', fn($r) => $r->where('name', 'staff')))
            ->count();

        $totalActiveStaff = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->where('is_active', true)
            ->count();

        // Room availability metrics (active non-maintenance rooms vs rooms occupied right now)
        $totalActiveRooms = Room::active()->where('status', '!=', 'maintenance')->count();
        $currentTime = Carbon::now('Asia/Manila')->format('H:i:s');
        $occupiedRoomIds = Appointment::whereDate('appointment_date', $today)
            ->where('status', 'confirmed')
            ->whereNotNull('room_id')
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>', $currentTime)
            ->pluck('room_id')
            ->unique();
        $availableRoomsCount = max(0, $totalActiveRooms - $occupiedRoomIds->count());

        $stats = [
            'pending'             => Appointment::pendingValid()->count(),
            'confirmed_today'     => $confirmedToday,
            'completed_today'     => $completedToday,
            'collections_today'   => $collectionsToday,
            'present_staff_count' => $presentStaffCount,
            'total_active_staff'  => $totalActiveStaff,
            'available_rooms'     => $availableRoomsCount,
            'total_active_rooms'  => $totalActiveRooms,
        ];

        // 2. Today's Full Schedule (Chronological Feed)
        $todaySchedule = Appointment::with(['customer', 'staff', 'services', 'room', 'payments'])
            ->whereDate('appointment_date', $today)
            ->whereIn('status', ['confirmed', 'completed'])
            ->orderBy('start_time')
            ->get();

        // 3. Valid Pending Bookings (Top 8 for Dashboard Preview)
        $pending = Appointment::with(['customer', 'services.category'])
            ->pendingValid()
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->limit(8)
            ->get();

        // 4. Same-day Absent Staff Scheduling Conflicts
        // Approved leave / holiday are not ordinary absences.
        $absentStaffIds = Attendance::whereDate('date', $today)
            ->whereIn('status', ['absent'])
            ->pluck('user_id');

        $absentStaffConflicts = collect();
        if ($absentStaffIds->isNotEmpty()) {
            $absentStaffConflicts = Appointment::with(['customer', 'staff', 'services', 'room'])
                ->whereDate('appointment_date', $today)
                ->where('status', 'confirmed')
                ->whereIn('user_id', $absentStaffIds)
                ->orderBy('start_time')
                ->get();
        }

        return view('receptionist-dashboard', compact(
            'stats',
            'todaySchedule',
            'pending',
            'absentStaffConflicts'
        ));
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
            'assisting_staff_ids' => 'nullable|array',
            'assisting_staff_ids.*' => 'exists:users,id',
            'room_id' => 'nullable|exists:rooms,id',
            'payment_method' => 'required|in:cash,card,gcash,paymaya,bank_transfer',
            'payment_type' => 'required|in:' . $validPaymentTypes,
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'treatment_notes' => 'nullable|string|max:2000',
        ]);

        return DB::transaction(function () use ($request, $appointment, $requiresDeposit, $depositRequired, $meta) {
            $appointment = Appointment::lockForUpdate()->findOrFail($appointment->id);
            $appointment->load(['services.category', 'customer']);

            // Guard: reject confirmation if the appointment is no longer pending
            // or has already expired. This check runs after the row lock to close
            // the race window with the expiration sweep command.
            if ($appointment->status !== 'pending') {
                if ($appointment->status === 'confirmed') {
                    return redirect()->route('receptionist.active')
                        ->with('info', 'This appointment has already been confirmed.')
                        ->with('error', 'This appointment has already been confirmed.');
                }
                return back()->with('error', 'This appointment can no longer be confirmed (current status: ' . $appointment->status . ').');
            }
            if ($appointment->is_expired) {
                return back()->with('error', 'This appointment has expired and cannot be confirmed. Please ask the customer to rebook.');
            }

            $primaryId = (int) $request->staff_id;
            $assistingIds = array_map('intval', (array) $request->input('assisting_staff_ids', []));

            try {
                \App\Services\TherapistAssignmentService::assignStaff(
                    $appointment,
                    $primaryId,
                    $assistingIds,
                    $request->room_id ? (int) $request->room_id : null
                );
            } catch (\InvalidArgumentException $e) {
                return back()->with('error', $e->getMessage());
            }

            if ($request->payment_type === 'deposit') {
                if ($request->amount < $depositRequired) {
                    return back()->with('error', 'Deposit amount must be at least ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â±' . number_format($depositRequired, 2));
                }
                if ($request->amount > $appointment->total_price) {
                    return back()->with('error', 'Deposit cannot exceed total price of ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â±' . number_format($appointment->total_price, 2));
                }
            }
            if ($request->payment_type === 'full' && $request->amount < $appointment->total_price) {
                return back()->with('error', 'Full payment must be at least ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â±' . number_format($appointment->total_price, 2));
            }
            if ($request->payment_type === 'cash_on_site' && $request->amount != 0) {
                return back()->with('error', 'Cash on site amount must be 0.');
            }

            $updateData = [
                'user_id' => $request->staff_id,
                'room_id' => $request->room_id,
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
                'deposit_required' => $depositRequired,
                'notes' => ($appointment->notes ?? '') . ($request->notes ? ($appointment->notes ? "\n" : "") . "[Rec: " . $request->notes . "]" : ""),
            ];

            if ($request->has('treatment_notes') && $request->filled('treatment_notes')) {
                $updateData['treatment_notes'] = $request->treatment_notes;
            }

            $appointment->update($updateData);

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

            // A confirmed appointment changes therapist availability immediately.
            $this->clearScheduleCache();

            $msg = match ($request->payment_type) {
                'deposit' => 'Booking confirmed! Deposit of ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â±' . number_format($request->amount, 2) . ' recorded. Balance due at counter.',
                'full' => 'Booking confirmed! Full payment of ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â±' . number_format($request->amount, 2) . ' recorded.',
                'cash_on_site' => 'Booking confirmed! Customer will pay ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â±' . number_format($appointment->total_price, 2) . ' at the counter.',
                default => 'Booking confirmed!',
            };

            if ($appointment->customer && $appointment->customer->user) {
                NotificationController::sendTo(
                    $appointment->customer->user,
                    'Booking Confirmed',
                    'Your appointment on ' . Carbon::parse($appointment->appointment_date)->format('M j, Y') . ' is confirmed.',
                    'booking', 'success', route('customer-dashboard'), 'My Bookings'
                );
            }
            if ($appointment->staff) {
                NotificationController::sendTo(
                    $appointment->staff,
                    'New Assignment',
                    ($appointment->client_name ?? 'Walk-in') . ' ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â ' . Carbon::parse($appointment->appointment_date)->format('M j') . ' at ' . Carbon::parse($appointment->start_time)->format('g:i A'),
                    'booking', 'info', route('staff.dashboard'), 'My Schedule'
                );
            }

            return redirect()->route('receptionist.active')->with('success', $msg);
        });
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $request->validate(['reason' => 'required|in:customer_cancelled,staff_unavailable,other']);
        $reason = $request->reason === 'other' ? $request->other_reason : str_replace('_', ' ', $request->reason);

        return DB::transaction(function () use ($appointment, $request, $reason) {
            $lockedAppt = Appointment::lockForUpdate()->findOrFail($appointment->id);

            if ($lockedAppt->status === 'cancelled') {
                return redirect()->route('receptionist.dashboard')->with('info', 'This appointment has already been cancelled.');
            }

            if (!in_array($lockedAppt->status, ['pending', 'confirmed'])) {
                return back()->with('error', 'Cannot cancel an appointment that is already ' . $lockedAppt->status . '.');
            }

            if ($lockedAppt->room_id) {
                Room::where('id', $lockedAppt->room_id)->update(['status' => 'available']);
            }

            $lockedAppt->status = 'cancelled';
            $lockedAppt->cancellation_reason = $request->reason;
            $lockedAppt->save();

            if ($lockedAppt->customer && $lockedAppt->customer->user) {
                NotificationController::sendTo(
                    $lockedAppt->customer->user,
                    'Appointment Cancelled',
                    'Your appointment on ' . Carbon::parse($lockedAppt->appointment_date)->format('M j, Y') . ' was cancelled. Reason: ' . ucfirst($reason),
                    'booking', 'warning', route('customer-dashboard'), 'My Bookings'
                );
            }
            if ($lockedAppt->staff) {
                NotificationController::sendTo(
                    $lockedAppt->staff,
                    'Assignment Cancelled',
                    ($lockedAppt->client_name ?? 'Walk-in') . "'s appointment on " . Carbon::parse($lockedAppt->appointment_date)->format('M j') . ' at ' . Carbon::parse($lockedAppt->start_time)->format('g:i A') . ' was cancelled. Reason: ' . ucfirst($reason),
                    'booking', 'warning', route('staff.dashboard'), 'My Schedule'
                );
            }
            $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get();
            if ($admins->isNotEmpty()) {
                NotificationController::sendTo(
                    $admins,
                    'Appointment Cancelled',
                    ($lockedAppt->client_name ?? 'Walk-in') . "'s appointment was cancelled. Reason: " . ucfirst($reason),
                    'booking', 'warning', route('admin.appointments', ['status' => 'cancelled']), 'Review'
                );
            }

            return redirect()->route('receptionist.dashboard')->with('success', 'Cancelled: ' . $reason);
        });
    }

    public function pending()
    {
        $now = now();
        Appointment::pendingOverdue()->chunkById(50, function ($overdue) {
            foreach ($overdue as $appt) {
                $appt->markAsExpired();
            }
        });

        $appointments = Appointment::pendingValid()
            ->with(['customer', 'services.category', 'packageSelections'])
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


    /* ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ APPOINTMENT ACTIONS ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ */

    /**
     * Add a service to an existing appointment.
     *
     * The catalog service (including package choice groups) is authoritative.
     * Choice options are recorded as package selections; they are NOT added as
     * separate appointment services. Existing services, options, therapist
     * assignments and financial records are preserved.
     */
    public function addExtraService(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'service_id' => 'required_without:custom_service_id|exists:services,id',
            'custom_service_id' => 'required_without:service_id|exists:services,id',
            'package_choices' => 'nullable|array',
            'therapist_ids' => 'nullable|array',
            'therapist_ids.*' => 'exists:users,id',
        ]);

        $serviceId = (int) ($validated['service_id'] ?? $validated['custom_service_id']);

        try {
            $result = DB::transaction(function () use ($request, $appointment, $serviceId, $validated) {
                $appointment = Appointment::lockForUpdate()
                    ->with(['services', 'assignedStaff', 'room'])
                    ->findOrFail($appointment->id);

                if (!in_array($appointment->status, ['pending', 'confirmed'], true)) {
                    return back()->with('error', 'Services cannot be added to an appointment with status: ' . $appointment->status . '.');
                }

                $service = \App\Models\Service::with('choiceGroups.options.service')->findOrFail($serviceId);
                if (!$service->is_active) {
                    return back()->with('error', 'The selected service is not currently available.');
                }
                if (!$service->duration_minutes || (int) $service->duration_minutes <= 0) {
                    return back()->with('error', 'The selected service does not have a valid treatment duration.');
                }

                $validatedChoices = [];
                foreach ($service->choiceGroups as $group) {
                    $selectedId = $request->input("package_choices.{$group->id}");
                    if ($group->is_required && empty($selectedId)) {
                        return back()->with('error', "Please select an option for '{$group->label}' in {$service->name}.");
                    }
                    if (!empty($selectedId)) {
                        $option = $group->options->first(function ($opt) use ($selectedId) {
                            return (int) $opt->service_id === (int) $selectedId
                                || (int) $opt->id === (int) $selectedId;
                        });
                        if (!$option) {
                            return back()->with('error', "Invalid option selected for '{$group->label}'.");
                        }
                        $validatedChoices[] = [
                            'choice_group_id' => $group->id,
                            'selected_service_id' => (int) $option->service_id,
                            'group_label_snapshot' => $group->label,
                            'selected_service_name_snapshot' => $option->service ? $option->service->name : 'Selected Service',
                        ];
                    }
                }

                // A package already on the appointment may have its saved
                // alternative changed without attaching a duplicate service.
                // This only updates existing package selections; duration,
                // price, therapists and room are untouched.
                if ($appointment->services->contains('id', $service->id)) {
                    if ($service->choiceGroups->isEmpty()) {
                        return back()->with('error', 'That service is already on this appointment.');
                    }

                    foreach ($validatedChoices as $choice) {
                        AppointmentPackageSelection::updateOrCreate(
                            [
                                'appointment_id' => $appointment->id,
                                'choice_group_id' => $choice['choice_group_id'],
                            ],
                            $choice
                        );
                    }

                    $this->clearScheduleCache();

                    return back()->with('success', 'Updated package selection for ' . $service->name . '.');
                }

                $availability = app(\App\Services\AppointmentAvailabilityService::class);
                $newServices = $appointment->services->push($service);
                $requirements = $availability->requirements($newServices);
                $duration = $requirements['duration'];
                $requiredCount = $requirements['therapists_required'];

                $start = app(\App\Services\AppointmentOccupancyService::class)->appointmentStart($appointment);
                $newEnd = $start->copy()->addMinutes($duration);

                $bizWindow = \App\Services\BusinessScheduleService::getOperatingWindow($start->toDateString());
                if (!($bizWindow['is_open'] ?? false)) {
                    return back()->with('error', $bizWindow['reason'] ?: 'The spa is closed on this date.');
                }
                if (!empty($bizWindow['end']) && $newEnd->gt(Carbon::parse($start->toDateString() . ' ' . $bizWindow['end']))) {
                    return back()->with('error', 'Adding this service would extend the appointment past business closing time.');
                }

                // Existing therapists are never silently removed or replaced.
                $currentStaffIds = $availability->appointmentStaffIds($appointment);
                $submittedIds = array_values(array_unique(array_map('intval', $validated['therapist_ids'] ?? [])));
                if (count($currentStaffIds) < $requiredCount && empty($submittedIds)) {
                    return back()->with('error', "This service combination requires {$requiredCount} therapist(s). Please select the additional therapist(s).");
                }

                if (!empty($submittedIds)) {
                    foreach ($currentStaffIds as $existingId) {
                        if (!in_array($existingId, $submittedIds, true)) {
                            return back()->with('error', 'Existing therapists cannot be removed while adding a service. Use Reassign to change the therapist team.');
                        }
                    }
                    if (count($submittedIds) !== $requiredCount) {
                        return back()->with('error', "Appointment requires exactly {$requiredCount} therapist(s), but " . count($submittedIds) . ' were provided.');
                    }
                    $newStaffIds = $submittedIds;
                } else {
                    $newStaffIds = $currentStaffIds;
                    if (count($newStaffIds) !== $requiredCount) {
                        return back()->with('error', "This appointment needs {$requiredCount} therapist(s) for the updated services. Use Reassign first.");
                    }
                }

                $staffCheck = $availability->isWindowAvailable([
                    'date' => $start->toDateString(),
                    'services' => $newServices,
                    'duration' => $duration,
                    'staff_ids' => $newStaffIds,
                    'team_size' => $requiredCount,
                    'room_id' => $appointment->room_id,
                    'exclude_appointment_id' => $appointment->id,
                    'start' => $start,
                    'end' => $newEnd,
                ]);
                if (!$staffCheck['available']) {
                    return back()->with('error', 'Cannot add service: ' . $staffCheck['reason']);
                }

                if ($requirements['requires_room']) {
                    if (!$appointment->room_id) {
                        return back()->with('error', 'This service combination requires a room, but the appointment has no room assigned.');
                    }
                    $compatible = \App\Services\RoomCompatibilityService::findCompatibleForServices($newServices);
                    if (!$compatible->contains('id', (int) $appointment->room_id)) {
                        return back()->with('error', 'The current room is not compatible with all selected services.');
                    }
                }

                // Consistent lock order: appointment, then therapists (ascending), then room.
                $lockedStaff = User::whereIn('id', $newStaffIds)->orderBy('id')->lockForUpdate()->get();
                if ($lockedStaff->count() !== count($newStaffIds)) {
                    return back()->with('error', 'One or more selected therapists could not be found.');
                }
                $lockedRoom = $appointment->room_id
                    ? Room::where('id', $appointment->room_id)->lockForUpdate()->first()
                    : null;
                if ($appointment->room_id && !$lockedRoom) {
                    return back()->with('error', 'The assigned room could not be found.');
                }

                foreach ($newStaffIds as $staffId) {
                    $check = TherapistAssignmentService::checkTherapistAvailability(
                        $lockedStaff->firstWhere('id', $staffId),
                        $start->toDateString(),
                        $start->format('H:i:s'),
                        $newEnd->format('H:i:s'),
                        $appointment->id,
                        true // current read after locks
                    );
                    if (!$check['available']) {
                        return back()->with('error', 'Cannot add service: ' . ($check['reason'] ?: 'a therapist is unavailable.'));
                    }
                }

                if ($lockedRoom && !$lockedRoom->isAvailableFor(
                    $start->toDateString(),
                    $start->format('H:i:s'),
                    $newEnd->format('H:i:s'),
                    $appointment->id,
                    true // current read after locks
                )) {
                    return back()->with('error', 'Cannot add service: the assigned room is no longer available for the extended treatment.');
                }

                // All validations passed — mutate atomically.
                $appointment->services()->attach($service->id, [
                    'price_at_booking' => $service->discount_price ?? $service->price,
                    'service_name' => $service->name,
                    'service_duration' => (int) $service->duration_minutes,
                    'is_extra' => true,
                ]);

                foreach ($validatedChoices as $choice) {
                    AppointmentPackageSelection::updateOrCreate(
                        [
                            'appointment_id' => $appointment->id,
                            'choice_group_id' => $choice['choice_group_id'],
                        ],
                        $choice
                    );
                }

                $appointment->load('services');
                $newTotal = $appointment->services->sum(
                    fn($s) => $s->pivot->custom_price ?? $s->pivot->price_at_booking
                );
                $appointment->update([
                    'total_price' => $newTotal,
                    'end_time' => $newEnd->format('H:i:s'),
                ]);

                if (!empty($submittedIds)) {
                    $primaryId = in_array((int) $appointment->user_id, $submittedIds, true)
                        ? (int) $appointment->user_id
                        : (int) $newStaffIds[0];
                    $assisting = array_values(array_diff($newStaffIds, [$primaryId]));
                    $appointment->syncStaff($primaryId, $assisting);
                }

                $this->clearScheduleCache();

                return back()->with('success', 'Added ' . $service->name . ' (₱' . number_format((float) $service->price, 2) . ').');
            });
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return $result;
    }

    public function complete(Request $request, Appointment $appointment)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,card,gcash,paymaya,bank_transfer',
            'payment_type' => 'required|in:full,completion',
        ]);

        if ($appointment->status === 'completed') {
            return redirect()->route('receptionist.active')->with('info', 'This appointment has already been completed.');
        }

        return DB::transaction(function () use ($request, $appointment) {
            $lockedAppt = Appointment::lockForUpdate()->find($appointment->id);

            if ($lockedAppt->status === 'completed') {
                return redirect()->route('receptionist.active')->with('info', 'This appointment has already been completed.');
            }

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
                ->with('success', 'Appointment completed! Payment recorded: ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â±' . number_format($balanceDue, 2));
        });
    }

    private function notifyCustomerCompletion($appointment): void
    {
        if ($appointment->customer && $appointment->customer->user) {
            \App\Http\Controllers\NotificationController::sendTo(
                $appointment->customer->user,
                'Thank You!',
                'Your appointment on ' . Carbon::parse($appointment->appointment_date)->format('M j') . ' is complete. We hope to see you again!',
                'booking', 'success', route('customer-dashboard'), 'My Bookings'
            );
        }
    }

    public function handleNoShow(Request $request, Appointment $appointment)
    {
        $request->validate([
            'action' => 'required|in:forfeit,refund',
            'no_show_reason' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (trim($value) === '') {
                        $fail('A valid no-show reason is required.');
                    }
                },
            ],
        ]);

        return DB::transaction(function () use ($request, $appointment) {
            $lockedAppt = Appointment::lockForUpdate()->findOrFail($appointment->id);

            if ($lockedAppt->status === 'no_show') {
                return back()->with('info', 'This appointment has already been marked as no-show.');
            }

            $totalPaid = $lockedAppt->payments()->sum('amount');
            $hasPayments = $totalPaid > 0;

            if ($lockedAppt->room_id) {
                Room::where('id', $lockedAppt->room_id)->update(['status' => 'available']);
            }

            if ($request->action === 'forfeit') {
                $lockedAppt->status = 'no_show';
                $lockedAppt->cancellation_reason = null;
                $lockedAppt->no_show_reason = $request->input('no_show_reason');
                $lockedAppt->save();

                $msg = $hasPayments
                    ? 'Payment of ₱' . number_format($totalPaid, 2) . ' forfeited. Appointment marked as no-show.'
                    : 'Appointment marked as no-show.';
                return back()->with('success', $msg);
            }

            // Refund action
            if ($hasPayments) {
                $alreadyRefunded = $lockedAppt->payments()->where('type', 'refund')->exists();
                if (!$alreadyRefunded) {
                    $originalPayment = $lockedAppt->payments()->orderBy('paid_at', 'asc')->first();
                    Payment::create([
                        'appointment_id' => $lockedAppt->id,
                        'payment_method' => $originalPayment->payment_method ?? 'cash',
                        'amount' => -$totalPaid,
                        'type' => 'refund',
                        'paid_at' => now(),
                    ]);
                }
            }

            $lockedAppt->status = 'no_show';
            $lockedAppt->cancellation_reason = null;
            $lockedAppt->no_show_reason = $request->input('no_show_reason');
            $lockedAppt->refunded_by = auth()->id();
            $lockedAppt->save();

            return back()->with('success', 'Payment of ₱' . number_format($totalPaid, 2) . ' refunded. Appointment marked as no-show.');
        });
    }

public function active()
{
    $appointments = Appointment::with([
        'customer',
        'services',
        'payments',
        'staff',
        'assignedStaff',
        'room',
        'packageSelections',
    ])
        ->where('status', 'confirmed')
        ->whereDate('appointment_date', '>=', today())
        ->orderBy('appointment_date')
        ->orderBy('start_time')
        ->get();

    $pastUnresolvedAppointments = Appointment::with([
        'customer',
        'services',
        'payments',
        'staff',
        'assignedStaff',
        'room',
        'packageSelections',
    ])
        ->where('status', 'confirmed')
        ->get()
        ->filter(function ($appointment) {
            $endDateTime = Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->end_time);
            return $endDateTime->isPast();
        })
        ->sortBy(function ($appointment) {
            return Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->end_time);
        })
        ->values();

    $allStaff = User::whereHas('roles', function ($query) {
        $query->where('name', 'staff');
    })
        ->where('is_active', true)
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

    $catalogServices = \App\Models\Service::where('is_active', true)
        ->with('choiceGroups.options.service')
        ->orderBy('name')
        ->get();

    $allRooms = Room::active()
        ->where('status', '!=', 'maintenance')
        ->orderBy('name')
        ->get();

    return view('receptionist.active', compact(
        'appointments',
        'pastUnresolvedAppointments',
        'allStaff',
        'catalogServices',
        'allRooms'
    ));
}

    public function reassignStaff(Request $request, int $appointmentId)
    {
        $request->validate([
            'staff_id' => 'required|exists:users,id',
            'assisting_staff_ids' => 'nullable|array',
            'assisting_staff_ids.*' => 'exists:users,id',
        ]);

        return DB::transaction(function () use ($request, $appointmentId) {
            $appointment = Appointment::lockForUpdate()->with(['services.category', 'room'])->findOrFail($appointmentId);

            if (!in_array($appointment->status, ['pending', 'confirmed'])) {
                return back()->with('error', 'Cannot reassign staff for an appointment with status: ' . $appointment->status . '.');
            }

            $primaryId = (int) $request->staff_id;
            $assistingIds = array_map('intval', (array) $request->input('assisting_staff_ids', []));

            if (in_array($primaryId, $assistingIds, true)) {
                return back()->with('error', 'The same therapist cannot be selected more than once.');
            }
            if (count(array_unique(array_merge([$primaryId], $assistingIds))) !== count(array_merge([$primaryId], $assistingIds))) {
                return back()->with('error', 'The same therapist cannot be selected more than once.');
            }

            $requiredCount = \App\Services\TherapistAssignmentService::resolveRequiredCount($appointment);
            if (count(array_merge([$primaryId], $assistingIds)) !== $requiredCount) {
                return back()->with('error', "This appointment requires exactly {$requiredCount} therapist(s).");
            }

            try {
                // assignStaff() locks the whole team, revalidates every member
                // and the room inside the transaction, and excludes this
                // appointment from its own conflicts. The team is saved only
                // when all checks pass, so a failure cannot partially update.
                \App\Services\TherapistAssignmentService::assignStaff(
                    $appointment,
                    $primaryId,
                    $assistingIds,
                    $appointment->room_id ? (int) $appointment->room_id : null
                );
            } catch (\InvalidArgumentException $e) {
                return back()->with('error', $e->getMessage());
            }

            $this->clearScheduleCache();

            return back()->with('success', 'Staff team successfully reassigned.');
        });
    }

    /**
     * Bulk availability for the reschedule calendar. One request can return the
     * availability of a whole month plus the time cards for a selected date.
     */
    public function rescheduleAvailability(Request $request, int $appointmentId)
    {
        $validated = $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'date' => 'nullable|date',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        $appointment = Appointment::with(['services', 'assignedStaff'])->findOrFail($appointmentId);
        if (!in_array($appointment->status, ['pending', 'confirmed'], true)) {
            return response()->json([
                'dates' => [], 'times' => [],
                'message' => 'This appointment can no longer be rescheduled.',
            ], 422);
        }

        $availability = app(\App\Services\AppointmentAvailabilityService::class);
        $requirements = $availability->requirements($appointment->services, $appointment);
        $staffIds = $availability->appointmentStaffIds($appointment);
        $dates = [];
        $monthReason = null;

        if (!empty($validated['month'])) {
            $month = $availability->availableDatesForAppointment($appointment, $validated['month']);
            $dates = $month['dates'];
            $monthReason = $month['reason'];
        }

        $times = [];
        $reason = $monthReason;
        if (!empty($validated['date'])) {
            $date = Carbon::parse($validated['date'], 'Asia/Manila')->toDateString();
            $start = Carbon::parse($date . ' ' . substr((string) $appointment->start_time, 0, 5), 'Asia/Manila');

            $bizWindow = \App\Services\BusinessScheduleService::getOperatingWindow($date);
            if (!($bizWindow['is_open'] ?? false)) {
                $reason = $bizWindow['reason'] ?: 'The spa is closed on this date.';
            } else {
                $roomId = !empty($validated['room_id'])
                    ? (int) $validated['room_id']
                    : $appointment->room_id;

                $times = $availability->availableTimes([
                    'date' => $date,
                    'services' => $requirements['services'],
                    'duration' => $requirements['duration'],
                    'staff_ids' => $staffIds,
                    'team_size' => $requirements['therapists_required'],
                    'room_id' => $roomId,
                    'exclude_appointment_id' => $appointment->id,
                    'min_start' => $date === Carbon::today('Asia/Manila')->toDateString()
                        ? Carbon::now('Asia/Manila')
                        : null,
                ]);

                if (empty($times)) {
                    $reason = 'The current therapist team and room have no valid time on this date. Reassign the therapists first if a change is needed.';
                }
            }
        }

        return response()->json([
            'dates' => $dates,
            'times' => $times,
            'duration' => $requirements['duration'],
            'therapists_required' => $requirements['therapists_required'],
            'room_id' => $appointment->room_id,
            'current_time' => substr((string) $appointment->start_time, 0, 5),
            'message' => $reason,
        ]);
    }

    public function reschedule(Request $request, int $appointmentId)
    {
        $validated = $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        return DB::transaction(function () use ($request, $validated, $appointmentId) {
            $appointment = Appointment::lockForUpdate()
                ->with(['services', 'assignedStaff', 'customer.user', 'staff'])
                ->findOrFail($appointmentId);

            if (!in_array($appointment->status, ['pending', 'confirmed'], true)) {
                return back()->with('error', 'Cannot reschedule an appointment with status: ' . $appointment->status . '.');
            }

            $availability = app(\App\Services\AppointmentAvailabilityService::class);
            $occupancy = app(\App\Services\AppointmentOccupancyService::class);
            $requirements = $availability->requirements($appointment->services, $appointment);
            $staffIds = $availability->appointmentStaffIds($appointment);

            // The current team is preserved: a missing therapist is never
            // replaced by another available therapist to make a date work.
            if (count($staffIds) !== $requirements['therapists_required']) {
                return back()->with('error', "This appointment requires {$requirements['therapists_required']} therapist(s) for its services. Please reassign staff first.");
            }

            $date = Carbon::parse($validated['appointment_date'], 'Asia/Manila')->toDateString();
            $start = Carbon::parse($date . ' ' . $validated['start_time'], 'Asia/Manila');
            $newEnd = $start->copy()->addMinutes($requirements['duration']);

            if ($date === Carbon::today('Asia/Manila')->toDateString() && $start->lt(Carbon::now('Asia/Manila'))) {
                return back()->with('error', 'The selected time is in the past.');
            }

            $bizWindow = \App\Services\BusinessScheduleService::getOperatingWindow($date);
            if (!($bizWindow['is_open'] ?? false)) {
                return back()->with('error', $bizWindow['reason'] ?: 'The spa is closed on this date.');
            }
            if (!empty($bizWindow['end']) && $newEnd->gt(Carbon::parse($date . ' ' . $bizWindow['end']))) {
                return back()->with('error', 'The selected time would extend the treatment past business closing time.');
            }

            // Preserve the existing room unless the receptionist explicitly
            // selected a different one in the form.
            $newRoomId = $request->has('room_id')
                ? ($validated['room_id'] ? (int) $validated['room_id'] : null)
                : $appointment->room_id;

            if ($requirements['requires_room'] && !$newRoomId) {
                return back()->with('error', 'The selected services require a room.');
            }
            if ($newRoomId && $requirements['requires_room']) {
                $compatible = \App\Services\RoomCompatibilityService::findCompatibleForServices($requirements['services']);
                if (!$compatible->contains('id', $newRoomId)) {
                    return back()->with('error', 'The selected room is not compatible with all services on this appointment.');
                }
            }

            $check = $availability->isWindowAvailable([
                'date' => $date,
                'services' => $requirements['services'],
                'duration' => $requirements['duration'],
                'staff_ids' => $staffIds,
                'team_size' => $requirements['therapists_required'],
                'room_id' => $newRoomId,
                'exclude_appointment_id' => $appointment->id,
                'start' => $start,
                'end' => $newEnd,
            ]);
            if (!$check['available']) {
                return back()->with('error', 'Current staff is not available at the new time: ' . $check['reason'] . '. Please reassign staff first.');
            }

            // Lock in a consistent order: therapists (ascending), then room.
            $lockedStaff = User::whereIn('id', $staffIds)->orderBy('id')->lockForUpdate()->get();
            if ($lockedStaff->count() !== count($staffIds)) {
                return back()->with('error', 'One or more assigned therapists could not be found.');
            }
            $lockedRoom = $newRoomId ? Room::where('id', $newRoomId)->lockForUpdate()->first() : null;
            if ($newRoomId && !$lockedRoom) {
                return back()->with('error', 'The selected room could not be found.');
            }

            foreach ($staffIds as $staffId) {
                $staffCheck = TherapistAssignmentService::checkTherapistAvailability(
                    $lockedStaff->firstWhere('id', $staffId),
                    $date,
                    $start->format('H:i:s'),
                    $newEnd->format('H:i:s'),
                    $appointment->id,
                    true // current read after locks
                );
                if (!$staffCheck['available']) {
                    return back()->with('error', 'Current staff is not available at the new time: ' . ($staffCheck['reason'] ?? 'unavailable') . '. Please reassign staff first.');
                }
            }

            if ($lockedRoom && !$lockedRoom->isAvailableFor(
                $date,
                $start->format('H:i:s'),
                $newEnd->format('H:i:s'),
                $appointment->id,
                true // current read after locks
            )) {
                return back()->with('error', 'The selected room is not available at the new time.');
            }

            $oldRoomId = $appointment->room_id;
            $appointment->update([
                'appointment_date' => $date,
                'start_time' => $start->format('H:i:s'),
                'end_time' => $newEnd->format('H:i:s'),
                'room_id' => $newRoomId,
                'rescheduled_at' => now(),
            ]);

            if ($oldRoomId && $oldRoomId !== $newRoomId) {
                Room::where('id', $oldRoomId)->update(['status' => 'available']);
            }
            if ($newRoomId && $oldRoomId !== $newRoomId) {
                Room::where('id', $newRoomId)->update(['status' => 'occupied']);
            }

            $this->clearScheduleCache();

            if ($appointment->customer && $appointment->customer->user) {
                \App\Http\Controllers\NotificationController::sendTo(
                    $appointment->customer->user,
                    'Appointment Rescheduled',
                    'Moved to ' . Carbon::parse($appointment->appointment_date)->format('M j, Y') . ' at ' . Carbon::parse($appointment->start_time)->format('g:i A'),
                    'booking', 'warning', route('customer-dashboard'), 'My Bookings'
                );
            }
            if ($appointment->staff) {
                \App\Http\Controllers\NotificationController::sendTo(
                    $appointment->staff,
                    'Appointment Rescheduled',
                    'An appointment was moved to ' . Carbon::parse($appointment->appointment_date)->format('M j') . ' at ' . Carbon::parse($appointment->start_time)->format('g:i A'),
                    'booking', 'warning', route('staff.dashboard'), 'My Schedule'
                );
            }

            return back()->with('success', 'Appointment rescheduled successfully.');
        });
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

        $treatmentEnd = app(\App\Services\AppointmentOccupancyService::class)
            ->treatmentEnd($appointment);

        $eval = \App\Services\RoomCompatibilityService::evaluateSlot(
            $appointment->services,
            $appointment->appointment_date,
            $appointment->start_time,
            $treatmentEnd->format('H:i:s'),
            $appointment->id
        );

        $rooms = $eval['compatible_rooms'];
        if ($rooms->isEmpty()) {
            $rooms = $eval['free_rooms'];
        }

        return response()->json([
            'requires_room' => true,
            'review_required' => false,
            'rooms' => $rooms->map(fn($room) => [
                'id' => $room->id,
                'name' => $room->name,
                'category' => $room->roomCategories->pluck('name')->join(', ') ?: ($room->category?->name ?? 'General Use'),
                'is_general' => $room->roomCategories->isEmpty() && is_null($room->category_id),
                'is_compatible' => $eval['compatible_rooms']->contains('id', $room->id),
            ])->values(),
            'message' => $rooms->isEmpty() ? 'No rooms available for this time slot.' : null
        ]);
    }

    /**
     * Update treatment notes for an appointment (receptionist workflow).
     */
    public function updateTreatmentNotes(Request $request, Appointment $appointment)
    {
        $request->validate([
            'treatment_notes' => 'nullable|string|max:2000',
        ]);

        $appointment->update([
            'treatment_notes' => $request->treatment_notes ?: null,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Treatment notes updated successfully.',
                'treatment_notes' => $appointment->treatment_notes,
            ]);
        }

        return back()->with('success', 'Treatment notes updated successfully.');
    }

    /* ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ DEPOSIT META ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ */

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