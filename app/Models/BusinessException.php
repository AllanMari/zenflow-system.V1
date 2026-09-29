<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessException extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'title',
        'type',
        'is_closed',
        'open_time',
        'close_time',
        'notice_message',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'is_closed' => 'boolean',
    ];
}
