<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiInsight extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_type', 'period_start', 'period_end',
        'metrics_input', 'insights_output', 'model_used', 'response_time_ms',
    ];

    protected $casts = [
        'metrics_input' => 'array',
        'insights_output' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
    ];
}