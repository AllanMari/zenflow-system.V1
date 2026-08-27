<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function today()
    {
        $today = Carbon::today();
        $staff = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->where('is_active', true)
            ->with(['attendances' => fn($q) => $q->whereDate('date', $today)])
            ->orderBy('first_name')
            ->get();

        return view('attendance.today', compact('staff', 'today'));
    }

    public function bulkMark(Request $request)
    {
        $request->validate([
            'attendances' => 'required|array',
            'attendances.*.user_id' => 'required|exists:users,id',
            'attendances.*.status' => 'required|in:present,absent,late,on_leave,holiday',
        ]);

        $today = Carbon::today();

        DB::transaction(function () use ($request, $today) {
            foreach ($request->attendances as $record) {
                Attendance::updateOrCreate(
                    ['user_id' => $record['user_id'], 'date' => $today],
                    [
                        'status' => $record['status'],
                        'marked_by' => auth()->id(),
                        'check_in' => $record['status'] === 'present' ? now() : null,
                    ]
                );
            }
        });

        return back()->with('success', 'Attendance recorded.');
    }

    public function report(Request $request)
    {
        $start = $request->date_from
            ? Carbon::parse($request->date_from)->startOfWeek()
            : now()->startOfWeek();
        $end = $start->copy()->endOfWeek();

        $staff = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $attendances = Attendance::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with('user')
            ->get()
            ->groupBy('user_id');

        return view('attendance.report', compact('staff', 'attendances', 'start', 'end'));
    }

    public function togglePermission(User $user)
    {
        if (!$user->roles()->where('name', 'receptionist')->exists()) {
            return back()->with('error', 'User is not a receptionist.');
        }
        $user->update(['can_mark_attendance' => !$user->can_mark_attendance]);
        return back()->with('success', 'Permission updated.');
    }

    public function quickCheckIn(Request $request, User $staff)
    {
        $request->validate(['status' => 'required|in:present,absent,late']);

        Attendance::updateOrCreate(
            ['user_id' => $staff->id, 'date' => today()],
            [
                'status' => $request->status,
                'check_in' => $request->status === 'present' ? now() : null,
                'marked_by' => auth()->id(),
            ]
        );

        return response()->json(['success' => true]);
    }

    public function quickCheckOut(Request $request, User $staff)
    {
        $attendance = Attendance::todayFor($staff->id);
        if ($attendance) {
            $attendance->update(['check_out' => now()]);
        }
        return response()->json(['success' => true]);
    }
}