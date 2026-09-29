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

        // 2. Fetch upcoming & recent exceptions
        $exceptions = BusinessException::orderBy('date', 'desc')
            ->paginate(15);

        $receptionists = User::whereHas('roles', fn($q) => $q->where('name', 'receptionist'))
            ->orderBy('first_name')
            ->get();

        return view('shared.business-hours', compact('days', 'exceptions', 'receptionists'));
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

    public function storeException(Request $request)
    {
        $this->authorizeManager();

        $request->validate([
            'date' => 'required|date',
            'title' => 'required|string|max:255',
            'type' => 'required|in:holiday,event,custom_hours',
            'is_closed' => 'nullable|boolean',
            'open_time' => 'nullable|required_if:is_closed,0|date_format:H:i',
            'close_time' => 'nullable|required_if:is_closed,0|date_format:H:i',
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

        BusinessException::updateOrCreate(
            ['date' => $request->date],
            [
                'title' => $request->title,
                'type' => $request->type,
                'is_closed' => $isClosed,
                'open_time' => $isClosed ? null : $request->open_time,
                'close_time' => $isClosed ? null : $request->close_time,
                'notice_message' => $request->notice_message,
                'description' => $request->description,
            ]
        );

        return back()->with('success', 'Holiday/Special Event date exception saved successfully.');
    }

    public function destroyException(BusinessException $exception)
    {
        $this->authorizeManager();

        $exception->delete();

        return back()->with('success', 'Business exception removed successfully.');
    }
}
