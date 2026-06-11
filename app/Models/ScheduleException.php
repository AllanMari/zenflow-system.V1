<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleException extends Model
{
    protected $fillable = [
        'user_id', 'exception_date', 'type', 'start_time', 'end_time', 'reason'
    ];

    protected $casts = [
        'exception_date' => 'date',
    ];

    // FIX: Added user relationship
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}