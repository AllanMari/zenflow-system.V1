<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleException extends Model
{
    protected $fillable = [
        'user_id', 'exception_date', 'type', 'start_time', 'end_time', 'reason'
    ];

    protected $casts = [
        'exception_date' => 'date',
    ];
}