<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_day_off'
    ];

    protected $casts = [
        'is_day_off' => 'boolean',
        // FIX: Removed broken 'datetime:H:i' casts — DB stores TIME, we handle in accessors
    ];

    // FIX: Custom accessors to format TIME columns as H:i
    protected function startTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? substr($value, 0, 5) : null,
            set: fn ($value) => $value,
        );
    }

    protected function endTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? substr($value, 0, 5) : null,
            set: fn ($value) => $value,
        );
    }

    // Staff member
    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scope: Working days (not day off)
    public function scopeWorkingDays($query)
    {
        return $query->where('is_day_off', false);
    }

    // Scope: Specific day of week
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    // Get day name
    public function getDayNameAttribute()
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return $days[$this->day_of_week] ?? 'Unknown';
    }
}