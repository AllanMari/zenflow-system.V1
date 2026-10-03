<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class ScheduleException extends Model
{
    protected $fillable = [
        'user_id', 'exception_date', 'type', 'start_time', 'end_time', 'reason'
    ];

    protected $casts = [
        'exception_date' => 'date',
    ];

    protected static $oldTypeCache = [];

    protected static function booted()
    {
        static::updating(function ($exception) {
            if ($exception->isDirty('type')) {
                self::$oldTypeCache[$exception->getKey()] = $exception->getOriginal('type');
            }
        });

        static::saved(function ($exception) {
            $isUpdate = isset(self::$oldTypeCache[$exception->getKey()]);

            // Only proceed if it is not an update (new record) OR if something actually changed
            if ($isUpdate && !$exception->wasChanged(['type', 'reason', 'exception_date', 'start_time', 'end_time'])) {
                unset(self::$oldTypeCache[$exception->getKey()]);
                return;
            }

            $oldStatus = null;
            if ($isUpdate) {
                $oldType = self::$oldTypeCache[$exception->getKey()] ?? null;
                if ($oldType) {
                    $oldStatus = ucfirst(str_replace('_', ' ', $oldType));
                } else {
                    $oldStatus = ucfirst(str_replace('_', ' ', $exception->type));
                }
            }
            unset(self::$oldTypeCache[$exception->getKey()]);

            $isLeave = in_array($exception->type, ['sick_leave', 'urgent_leave']);

            if ($isUpdate) {
                $changeType = $isLeave ? 'Leave Updated' : 'Day Off/Holiday Change';
            } else {
                $changeType = $isLeave ? 'Leave Added' : 'Day Off/Holiday Change';
            }

            AttendanceLog::create([
                'user_id' => $exception->user_id,
                'changed_by' => auth()->id() ?? $exception->user_id,
                'schedule_exception_id' => $exception->id,
                'exception_type' => $exception->type,
                'change_type' => $changeType,
                'old_status' => $oldStatus,
                'new_status' => ucfirst(str_replace('_', ' ', $exception->type)),
                'reason' => $exception->reason,
                'changed_at' => now(),
            ]);
        });

        static::deleted(function ($exception) {
            unset(self::$oldTypeCache[$exception->getKey()]);
            $isLeave = in_array($exception->type, ['sick_leave', 'urgent_leave']);
            $changeType = $isLeave ? 'Leave Removed' : 'Day Off/Holiday Change';

            AttendanceLog::create([
                'user_id' => $exception->user_id,
                'changed_by' => auth()->id() ?? $exception->user_id,
                'schedule_exception_id' => null,
                'change_type' => $changeType,
                'exception_type' => $exception->type,
                'old_status' => ucfirst(str_replace('_', ' ', $exception->type)),
                'new_status' => 'Removed / Normal Schedule',
                'reason' => 'Exception deleted: ' . $exception->reason,
                'changed_at' => now(),
            ]);
        });
    }

    // FIX: Added user relationship
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}