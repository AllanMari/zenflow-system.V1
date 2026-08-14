<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'appointment_date',
        'start_time',
        'end_time',
        'status',
        'total_price',
        'deposit_required',
        'notes',
        'created_by',
        'confirmed_at',
        'confirmed_by',
        'cancellation_reason',
        'no_show_reason',
        'refunded_by',
        'refund_approved_at',
        'rescheduled_from',
        'rescheduled_at',
        'reminder_sent_at',
        'room_id',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'status' => 'string',
        'total_price' => 'decimal:2',
        'deposit_required' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'confirmed_by' => 'integer',
        'refunded_by' => 'integer',
        'refund_approved_at' => 'datetime',
        'rescheduled_from' => 'integer',
        'rescheduled_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    /* ─── Relationships ─── */

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'appointment_services')
                    ->withPivot('price_at_booking', 'service_name', 'service_duration', 'is_extra');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /* ─── Accessors ─── */

    public function getTotalPaidAttribute()
    {
        return $this->payments->sum('amount');
    }

    public function getRemainingBalanceAttribute()
    {
        return max(0, $this->total_price - $this->total_paid);
    }

    public function isFullyPaid()
    {
        return $this->remaining_balance <= 0;
    }

    public function isDepositPaid()
    {
        $depositAmount = $this->total_price * 0.2;
        return $this->total_paid >= $depositAmount;
    }

    public function getTimeRangeAttribute()
    {
        return Carbon::parse($this->start_time)->format('g:i A') . ' - ' .
               Carbon::parse($this->end_time)->format('g:i A');
    }

    /* ─── Scopes ─── */

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopePendingValid(Builder $query): Builder
    {
        return $query->where('status', 'pending')
                     ->whereDate('appointment_date', '>=', today());
    }

    public function scopePendingOverdue(Builder $query): Builder
    {
        return $query->where('status', 'pending')
                     ->whereDate('appointment_date', '<', today());
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeActiveToday(Builder $query): Builder
    {
        return $query->where('status', 'confirmed')
                     ->whereDate('appointment_date', today());
    }

    public function scopeActiveUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'confirmed')
                     ->whereDate('appointment_date', '>=', today());
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('appointment_date', today());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('appointment_date', '>=', today())
                     ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeForStaff(Builder $query, int $staffId): Builder
    {
        return $query->where('user_id', $staffId);
    }

    /* ─── Actions ─── */

    /**
     * Mark a pending appointment as expired (cancelled).
     * Idempotent — safe to call multiple times.
     */
    public function markAsExpired(): void
    {
        if ($this->status !== 'pending') {
            return;
        }

        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => 'expired',
        ]);
    }
}