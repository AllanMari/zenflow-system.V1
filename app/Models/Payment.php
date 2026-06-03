<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'payment_method',
        'amount',
        'type',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    // Scope: Deposits only
    public function scopeDeposits($query)
    {
        return $query->where('type', 'deposit');
    }

    // Scope: Full payments
    public function scopeFull($query)
    {
        return $query->where('type', 'full');
    }

    // Record payment timestamp automatically
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payment) {
            if (is_null($payment->paid_at)) {
                $payment->paid_at = now();
            }
        });
    }
}