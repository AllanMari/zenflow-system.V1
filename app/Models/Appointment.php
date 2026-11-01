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
        'requested_room_id',
        'ip_address',
        'guest_first_name',
        'guest_phone',
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

    /* Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Relationships Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */

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

    public function requestedRoom()
    {
        return $this->belongsTo(Room::class, 'requested_room_id');
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Accessors Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */

    public function getTotalPaidAttribute()
    {
        return $this->payments->sum('amount');
    }

    public function getRemainingBalanceAttribute()
    {
        return max(0, $this->total_price - $this->total_paid);
    }

    public function getActiveOutstandingBalanceAttribute()
    {
        if (in_array($this->status, ['pending', 'confirmed'])) {
            return $this->remaining_balance;
        }
        return 0.00;
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

    public function getClientNameAttribute()
    {
        if (!empty($this->guest_first_name)) {
            return trim($this->guest_first_name);
        }

        if ($this->relationLoaded('customer') ? $this->customer : $this->customer()->exists()) {
            return $this->customer->display_name ?? $this->customer->full_name ?? 'Walk-in';
        }

        return 'Walk-in';
    }

    public function getClientPhoneAttribute()
    {
        if (!empty($this->guest_phone)) {
            return trim($this->guest_phone);
        }

        $customer = $this->relationLoaded('customer') ? $this->customer : $this->customer()->first();

        return $customer?->phone_number ?: null;
    }

    public function getTimeRangeAttribute()
    {
        return Carbon::parse($this->start_time)->format('g:i A') . ' - ' .
               Carbon::parse($this->end_time)->format('g:i A');
    }

    /**
     * Authoritative expiration deadline for pending appointments.
     * Earlier of:
     *   (a) created_at + 24 hours, OR
     *   (b) midnight at the start of the following day after the scheduled appointment date (app timezone).
     *
     * A pending appointment is expired when the current time reaches or passes
     * this deadline. An appointment scheduled for today remains valid throughout
     * today unless created_at + 24h expires first. start_time is NOT used as an
     * expiration trigger.
     */
    public function getExpiresAtAttribute(): ?Carbon
    {
        if ($this->status !== 'pending') {
            return null;
        }

        $tz = config('app.timezone', 'UTC');

        $createdAt = $this->created_at
            ? Carbon::parse($this->created_at)->timezone($tz)
            : Carbon::now($tz);

        $creationDeadline = $createdAt->copy()->addHours(24);

        $dateStr = $this->appointment_date instanceof Carbon
            ? $this->appointment_date->toDateString()
            : substr((string) $this->appointment_date, 0, 10);

        // Deadline (b): midnight at start of the day AFTER appointment date — NOT start_time, NOT start of today.
        $dateDeadline = Carbon::parse($dateStr, $tz)->addDay()->startOfDay();

        return $creationDeadline->lt($dateDeadline) ? $creationDeadline : $dateDeadline;
    }

    /**
     * Determine if a pending appointment has reached its expiration deadline.
     */
    public function getIsExpiredAttribute(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $expiresAt = $this->expires_at;
        return $expiresAt ? $expiresAt->isPast() : false;
    }

    /* Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Scopes Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopePendingValid(Builder $query): Builder
    {
        $now = Carbon::now(config('app.timezone', 'UTC'));
        $todayStr = $now->toDateString();
        $cutoff24h = $now->copy()->subHours(24);

        // An appointment is still valid when BOTH of the following hold:
        //   1. created_at is within the last 24 hours (submission deadline not yet passed), AND
        //   2. appointment_date is today or in the future (calendar date has not passed).
        // start_time is intentionally excluded â€” expiry fires at midnight on the appt date.
        return $query->where('status', 'pending')
            ->where('created_at', '>', $cutoff24h)
            ->whereDate('appointment_date', '>=', $todayStr);
    }

    public function scopePendingOverdue(Builder $query): Builder
    {
        $now = Carbon::now(config('app.timezone', 'UTC'));
        $todayStr = $now->toDateString();
        $cutoff24h = $now->copy()->subHours(24);

        // An appointment is overdue when EITHER of the following holds:
        //   1. created_at has passed the 24-hour submission deadline, OR
        //   2. appointment_date is before today (calendar date has passed).
        // start_time is intentionally excluded â€” expiry fires at midnight on the appt date,
        // not at the moment the scheduled start time arrives within that day.
        return $query->where('status', 'pending')
            ->where(function ($q) use ($cutoff24h, $todayStr) {
                $q->where('created_at', '<=', $cutoff24h)
                  ->orWhereDate('appointment_date', '<', $todayStr);
            });
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

    /* Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Actions Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */

    /**
     * Mark a pending appointment as expired (cancelled).
     * Idempotent and concurrency-safe Ã¢â‚¬â€ only transitions if status is currently 'pending'.
     */
    public function markAsExpired(): bool
    {
        $updated = static::where('id', $this->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'expired',
                'updated_at' => now(),
            ]);

        if ($updated > 0) {
            $this->status = 'cancelled';
            $this->cancellation_reason = 'expired';

            if ($this->room_id) {
                $hasActiveOther = static::where('room_id', $this->room_id)
                    ->where('id', '!=', $this->id)
                    ->whereDate('appointment_date', $this->appointment_date)
                    ->whereIn('status', ['confirmed', 'in_progress'])
                    ->exists();

                if (!$hasActiveOther) {
                    Room::where('id', $this->room_id)->update(['status' => 'available']);
                }
            }

            return true;
        }

        return false;
    }
}