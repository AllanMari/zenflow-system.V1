<?php

namespace App\Http\Controllers;

use App\Models\BusinessHour;
use App\Models\BusinessException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessScheduleController extends Controller
{
    private function authorizeManager(): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $isAdmin = $user->roles->contains('name', 'admin');
        $isReceptionistWithAccess = $user->roles->contains('name', 'receptionist') && $user->can_manage_business_hours;

        if (!$isAdmin && !$isReceptionistWithAccess) {
            abort(403, 'You do not have permission to manage business hours and holidays.');
        }
    }

    public function index()
    {
        $this->authorizeManager();

        // 1. Ensure 7 days exist for business hours
        $days = [];
        for ($i = 0; $i <= 6; $i++) {
            $hour = BusinessHour::firstOrCreate(
                ['day_of_week' => $i],
                [
                    'open_time' => '09:00',
                    'close_time' => '20:00',
                    'is_closed' => false,
                ]
            );
            $days[$i] = $hour;
        }

        // 2. Operational summary metrics for upcoming schedule changes (Asia/Manila)
        $today = now('Asia/Manila')->toDateString();

        $upcomingClosuresCount = BusinessException::whereDate('date', '>=', $today)
            ->where(function ($q) {
                $q->where('is_closed', true)
                  ->orWhere('type', 'holiday');
            })
            ->count();

        $upcomingCustomHoursCount = BusinessException::whereDate('date', '>=', $today)
            ->where('is_closed', false)
            ->where('type', 'custom_hours')
            ->count();

        $upcomingEventsCount = BusinessException::whereDate('date', '>=', $today)
            ->where('is_closed', false)
            ->where('type', 'event')
            ->count();

        $nextScheduledChange = BusinessException::whereDate('date', '>=', $today)
            ->orderBy('date', 'asc')
            ->first();

        // 3. Fetch exceptions with tab, type, and search filters
        $filter = request('filter', request('status', 'all'));
        $typeFilter = request('type_filter', 'all');
        $search = trim((string) request('search', ''));

        $query = BusinessException::query();

        if ($filter === 'upcoming') {
            $query->whereDate('date', '>=', $today)->orderBy('date', 'asc');
        } elseif ($filter === 'past') {
            $query->whereDate('date', '<', $today)->orderBy('date', 'desc');
        } else {
            $query->orderBy('date', 'desc');
        }

        if ($typeFilter !== 'all') {
            if ($typeFilter === 'holiday') {
                $query->where(function ($q) {
                    $q->where('type', 'holiday')->orWhere('is_closed', true);
                });
            } elseif ($typeFilter === 'custom_hours') {
                $query->where('type', 'custom_hours')->where('is_closed', false);
            } elseif ($typeFilter === 'event') {
                $query->where('type', 'event')->where('is_closed', false);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('notice_message', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $exceptions = $query->paginate(15)->withQueryString();

        if (request()->ajax() || request()->header('X-Requested-With') === 'XMLHttpRequest' || request()->query('fragment') === 'exceptions') {
            return view('shared._business_exceptions_table', compact(
                'exceptions',
                'filter',
                'typeFilter',
                'search'
            ));
        }

        $receptionists = User::whereHas('roles', fn($q) => $q->where('name', 'receptionist'))
            ->orderBy('first_name')
            ->get();

        return view('shared.business-hours', compact(
            'days',
            'exceptions',
            'receptionists',
            'filter',
            'typeFilter',
            'search',
            'upcomingClosuresCount',
            'upcomingCustomHoursCount',
            'upcomingEventsCount',
            'nextScheduledChange'
        ));
    }

    public function updateHours(Request $request)
    {
        $this->authorizeManager();

        $request->validate([
            'hours' => 'required|array|size:7',
            'hours.*.open_time' => 'nullable|date_format:H:i',
            'hours.*.close_time' => 'nullable|date_format:H:i',
            'hours.*.is_closed' => 'nullable|boolean',
        ]);

        // FIX: Validate that close_time is after open_time for each day
        foreach ($request->hours as $dayOfWeek => $data) {
            $isClosed = !empty($data['is_closed']);
            if (!$isClosed && !empty($data['open_time']) && !empty($data['close_time'])) {
                try {
                    $open = \Carbon\Carbon::createFromFormat('H:i', $data['open_time']);
                    $close = \Carbon\Carbon::createFromFormat('H:i', $data['close_time']);
                    
                    if ($close->lte($open)) {
                        return back()->withErrors([
                            "hours.{$dayOfWeek}.close_time" => "Close time must be after open time for " . \App\Models\BusinessHour::getDayName($dayOfWeek) . "."
                        ])->withInput();
                    }
                } catch (\Exception $e) {
                    return back()->withErrors([
                        "hours.{$dayOfWeek}" => "Invalid time format for " . \App\Models\BusinessHour::getDayName($dayOfWeek) . "."
                    ])->withInput();
                }
            }
        }

        foreach ($request->hours as $dayOfWeek => $data) {
            $isClosed = !empty($data['is_closed']);

            BusinessHour::updateOrCreate(
                ['day_of_week' => $dayOfWeek],
                [
                    'open_time' => $isClosed ? null : ($data['open_time'] ?? '09:00'),
                    'close_time' => $isClosed ? null : ($data['close_time'] ?? '20:00'),
                    'is_closed' => $isClosed,
                ]
            );
        }

        return back()->with('success', 'Weekly business hours updated successfully.');
    }

    /**
     * Parse date input which can be single date or range 'YYYY-MM-DD to YYYY-MM-DD'.
     */
    private function parseDateRange(Request $request): array
    {
        $rawDate = trim((string) ($request->date_range ?: $request->date));
        if (str_contains($rawDate, ' to ')) {
            $parts = explode(' to ', $rawDate);
            $start = trim($parts[0]);
            $end = trim($parts[1]);
        } else {
            $start = $rawDate;
            $end = $rawDate;
        }

        return [$start, $end];
    }

    public function storeException(Request $request)
    {
        $this->authorizeManager();

        [$startStr, $endStr] = $this->parseDateRange($request);

        $request->merge([
            'date_start' => $startStr,
            'date_end' => $endStr,
        ]);

        $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'title' => 'required|string|max:255',
            'type' => 'required|in:holiday,event,custom_hours',
            'is_closed' => 'nullable|boolean',
            'open_time' => 'nullable|required_if:type,custom_hours|date_format:H:i',
            'close_time' => 'nullable|required_if:type,custom_hours|date_format:H:i',
            'notice_message' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:1000',
        ]);

        $isClosed = $request->boolean('is_closed', false) || $request->type === 'holiday';

        // FIX: Validate that close_time is after open_time for custom hours
        if (!$isClosed && !empty($request->open_time) && !empty($request->close_time)) {
            try {
                $open = \Carbon\Carbon::createFromFormat('H:i', $request->open_time);
                $close = \Carbon\Carbon::createFromFormat('H:i', $request->close_time);

                if ($close->lte($open)) {
                    return back()->withErrors([
                        'close_time' => 'Close time must be after open time.'
                    ])->withInput();
                }
            } catch (\Exception $e) {
                return back()->withErrors([
                    'time' => 'Invalid time format.'
                ])->withInput();
            }
        }

        $startDate = Carbon::parse($startStr);
        $endDate = Carbon::parse($endStr);
        $affectedDates = [];

        for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
            $affectedDates[] = $d->toDateString();
        }

        // Safeguard: Check if any affected dates already have a different existing exception
        $existingCollisions = BusinessException::where(function ($q) use ($affectedDates) {
            if (count($affectedDates) === 1) {
                $q->whereDate('date', $affectedDates[0]);
            } else {
                $q->whereDate('date', '>=', $affectedDates[0])
                  ->whereDate('date', '<=', end($affectedDates));
            }
        })->get();

        if ($existingCollisions->isNotEmpty()) {
            $collisionDetails = $existingCollisions->map(function ($exc) {
                return Carbon::parse($exc->date)->format('M d, Y') . " ('{$exc->title}')";
            })->join(', ');

            return back()->withErrors([
                'date_range' => "Cannot save exception: The selected date range overlaps with existing scheduled exception(s): {$collisionDetails}. Please edit or remove conflicting exceptions first."
            ])->withInput();
        }

        // Atomic multi-day save
        DB::transaction(function () use ($affectedDates, $request, $isClosed) {
            foreach ($affectedDates as $dateStr) {
                BusinessException::create([
                    'date' => $dateStr,
                    'title' => $request->title,
                    'type' => $request->type,
                    'is_closed' => $isClosed,
                    'open_time' => $isClosed ? null : $request->open_time,
                    'close_time' => $isClosed ? null : $request->close_time,
                    'notice_message' => $request->notice_message,
                    'description' => $request->description,
                ]);
            }
        });

        // Check for existing active appointments across all affected dates
        $conflictingAppointments = \App\Models\Appointment::with('services')
            ->where(function ($q) use ($affectedDates) {
                if (count($affectedDates) === 1) {
                    $q->whereDate('appointment_date', $affectedDates[0]);
                } else {
                    $q->whereDate('appointment_date', '>=', $affectedDates[0])
                      ->whereDate('appointment_date', '<=', end($affectedDates));
                }
            })
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->filter(function ($appt) use ($isClosed, $request) {
                if ($isClosed) {
                    return true;
                }

                if (!empty($request->open_time) && !empty($request->close_time)) {
                    $apptDate = Carbon::parse($appt->appointment_date)->toDateString();
                    $open = Carbon::parse($apptDate . ' ' . $request->open_time);
                    $close = Carbon::parse($apptDate . ' ' . $request->close_time);

                    $duration = $appt->services->sum('duration_minutes');
                    $startTime = Carbon::parse($apptDate . ' ' . $appt->start_time);
                    $endTime = $startTime->copy()->addMinutes($duration);

                    return $startTime->lt($open) || $endTime->gt($close);
                }

                return false;
            });

        $dateSummary = count($affectedDates) > 1
            ? "for range {$startStr} to {$endStr} (" . count($affectedDates) . " days)"
            : "for {$startStr}";

        if ($conflictingAppointments->isNotEmpty()) {
            $count = $conflictingAppointments->count();
            $warningMsg = "Holiday/Special Event date exception saved successfully {$dateSummary}. Warning: {$count} existing appointment(s) fall within this period and conflict with the new operating hours/closure. They were NOT automatically cancelled or rescheduled and require staff review.";
            return back()->with('success', "Holiday/Special Event date exception saved successfully {$dateSummary}.")->with('warning', $warningMsg);
        }

        return back()->with('success', "Holiday/Special Event date exception saved successfully {$dateSummary}.");
    }

    public function updateException(Request $request, BusinessException $exception)
    {
        $this->authorizeManager();

        $request->validate([
            'date' => 'required|date',
            'title' => 'required|string|max:255',
            'type' => 'required|in:holiday,event,custom_hours',
            'is_closed' => 'nullable|boolean',
            'open_time' => 'nullable|required_if:type,custom_hours|date_format:H:i',
            'close_time' => 'nullable|required_if:type,custom_hours|date_format:H:i',
            'notice_message' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:1000',
        ]);

        $isClosed = $request->boolean('is_closed', false) || $request->type === 'holiday';

        if (!$isClosed && !empty($request->open_time) && !empty($request->close_time)) {
            try {
                $open = \Carbon\Carbon::createFromFormat('H:i', $request->open_time);
                $close = \Carbon\Carbon::createFromFormat('H:i', $request->close_time);

                if ($close->lte($open)) {
                    return back()->withErrors([
                        'close_time' => 'Close time must be after open time.'
                    ])->withInput();
                }
            } catch (\Exception $e) {
                return back()->withErrors([
                    'time' => 'Invalid time format.'
                ])->withInput();
            }
        }

        // If date changed, ensure no other record exists for the new date
        if ($request->date !== $exception->date->toDateString()) {
            $exists = BusinessException::where('date', $request->date)
                ->where('id', '!=', $exception->id)
                ->exists();
            if ($exists) {
                return back()->withErrors([
                    'date' => 'An exception already exists on ' . $request->date . '.'
                ])->withInput();
            }
        }

        $exception->update([
            'date' => $request->date,
            'title' => $request->title,
            'type' => $request->type,
            'is_closed' => $isClosed,
            'open_time' => $isClosed ? null : $request->open_time,
            'close_time' => $isClosed ? null : $request->close_time,
            'notice_message' => $request->notice_message,
            'description' => $request->description,
        ]);

        // Check conflicts on the updated date
        $conflictingAppointments = \App\Models\Appointment::with('services')
            ->whereDate('appointment_date', $request->date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->filter(function ($appt) use ($isClosed, $request) {
                if ($isClosed) {
                    return true;
                }

                if (!empty($request->open_time) && !empty($request->close_time)) {
                    $open = Carbon::parse($request->date . ' ' . $request->open_time);
                    $close = Carbon::parse($request->date . ' ' . $request->close_time);

                    $duration = $appt->services->sum('duration_minutes');
                    $startTime = Carbon::parse($request->date . ' ' . $appt->start_time);
                    $endTime = $startTime->copy()->addMinutes($duration);

                    return $startTime->lt($open) || $endTime->gt($close);
                }

                return false;
            });

        if ($conflictingAppointments->isNotEmpty()) {
            $count = $conflictingAppointments->count();
            $warningMsg = "Holiday/Special Event date exception updated successfully. Warning: {$count} existing appointment(s) fall on this date and conflict with the new operating hours/closure. They were NOT automatically cancelled or rescheduled and require staff review.";
            return back()->with('success', 'Holiday/Special Event date exception updated successfully.')->with('warning', $warningMsg);
        }

        return back()->with('success', 'Holiday/Special Event date exception updated successfully.');
    }

    public function destroyException(BusinessException $exception)
    {
        $this->authorizeManager();

        $exception->delete();

        return back()->with('success', 'Business exception removed successfully.');
    }
}
