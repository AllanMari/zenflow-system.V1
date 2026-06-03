<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ShiftTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'created_by',
        'is_active',
        'pattern',
    ];

    protected $casts = [
        'pattern' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Apply this template's weekly pattern to a user.
     */
    public function applyToUser(User $user, ?Carbon $weekStart = null): void
    {
        $pattern = $this->pattern;

        foreach ($pattern as $dow => $day) {
            WorkSchedule::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'day_of_week' => (int) $dow,
                ],
                [
                    'start_time' => !empty($day['is_day_off']) ? null : ($day['start_time'] ?? null),
                    'end_time' => !empty($day['is_day_off']) ? null : ($day['end_time'] ?? null),
                    'is_day_off' => !empty($day['is_day_off']),
                ]
            );
        }
    }
}