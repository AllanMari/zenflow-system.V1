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
        'treatment_notes',
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

    /**
     * The attributes that should be hidden for serialization.
     * Prevents treatment notes from leaking into API responses or JSON payloads.
     */
    protected $hidden = [
        'treatment_notes',
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

    /**
     * Legacy single-therapist relationship (backward-compatible).
     * Returns the primary therapist from appointments.user_id.
     */
    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Customer-requested preferred therapists (informational only, never a reservation).
     * Completely separate from assignedStaff() / appointment_staff.
     */
    public function preferredStaff()
    {
        return $this->belongsToMany(User::class, 'appointment_preferred_staff', 'appointment_id', 'user_id')
                    ->withTimestamps();
    }

    /**
     * Multi-therapist pivot relationship via appointment_staff.
     * Includes all assigned therapists with their role (primary/assisting).
     */
    public function assignedStaff()
    {
        return $this->belongsToMany(User::class, 'appointment_staff')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * The primary therapist assigned to this appointment.
     */
    public function primaryTherapist()
    {
        return $this->belongsToMany(User::class, 'appointment_staff')
                    ->wherePivot('role', 'primary')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Assisting therapists assigned to this appointment.
     */
    public function assistingTherapists()
    {
        return $this->belongsToMany(User::class, 'appointment_staff')
                    ->wherePivot('role', 'assisting')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Assign staff to this appointment enforcing the primary therapist invariant:
     * - Exactly one primary therapist
     * - Zero or more assisting therapists
     * - Synchronizes legacy appointments.user_id with the primary therapist
     *
     * @param int $primaryTherapistId
     * @param array<int> $assistingTherapistIds
     * @return void
     * @throws \InvalidArgumentException
     */
    public function syncStaff(int $primaryTherapistId, array $assistingTherapistIds = []): void
    {
        $uniqueAssisting = array_values(array_unique(array_filter($assistingTherapistIds)));

        if (in_array($primaryTherapistId, $uniqueAssisting, true)) {
            throw new \InvalidArgumentException('A primary therapist cannot also be assigned as an assisting therapist on the same appointment.');
        }

        $syncData = [
            $primaryTherapistId => ['role' => 'primary'],
        ];

        foreach ($uniqueAssisting as $assistantId) {
            $syncData[$assistantId] = ['role' => 'assisting'];
        }

        $this->assignedStaff()->sync($syncData);

        // Keep legacy appointments.user_id synchronized with the primary therapist
        if ($this->user_id !== $primaryTherapistId) {
            $this->update(['user_id' => $primaryTherapistId]);
        }
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'appointment_services')
                    ->withPivot('price_at_booking', 'service_name', 'service_duration', 'is_extra');
    }

    /**
     * Resolves the required number of therapists for this appointment using TherapistAssignmentService.
     */
    public function getTherapistsRequiredAttribute(): int
    {
        return \App\Services\TherapistAssignmentService::resolveRequiredCount($this);
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

    public function packageSelections()
    {
        return $this->hasMany(\App\Models\AppointmentPackageSelection::class);
    }

    public function requestedRoom()
    {
        return $this->belongsTo(Room::class, 'requested_room_id');
    }

    /**
     * Check if a given user is authorized to view this appointment's treatment notes.
     *
     * Treatment notes contain confidential session health information and considerations.
     * Authorized:
     * - Primary therapist assigned to this appointment
     * - Assisting therapists assigned to this appointment
     * - Receptionist and Admin staff (operational oversight)
     *
     * Unauthorized:
     * - Unrelated staff members
     * - Customers and guests
     * - Public visitors
     */
    public function canViewTreatmentNotes(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->isAdmin() || $user->isReceptionist()) {
            return true;
        }

        if ($user->hasRole('staff')) {
            if ($this->user_id === $user->id) {
                return true;
            }

            if ($this->relationLoaded('assignedStaff')) {
                return $this->assignedStaff->contains('id', $user->id);
            }

            return $this->assignedStaff()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    public function getPrimaryStaffAttribute()
    {
        if ($this->relationLoaded('assignedStaff') && $this->assignedStaff) {
            return $this->assignedStaff->firstWhere('pivot.role', 'primary') ?? $this->staff;
        }
        return $this->primaryTherapist->first() ?? $this->staff;
    }

    public function getAssistingStaffAttribute()
    {
        if ($this->relationLoaded('assignedStaff') && $this->assignedStaff) {
            return $this->assignedStaff->where('pivot.role', 'assisting')->values();
        }
        return $this->assistingTherapists;
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

    /**
     * Whether this appointment currently occupies therapist/room capacity.
     */
    public function isOccupyingResource(): bool
    {
        if (in_array($this->status, ['confirmed', 'completed'], true)) {
            return true;
        }

        return $this->status === 'pending' && !$this->is_expired;
    }

    /**
     * Appointments that currently occupy therapist/room capacity.
     * Excludes cancelled, no_show, expired, and overdue pending bookings
     * even before any scheduled cleanup job updates their status.
     */
    public function scopeOccupyingResources(Builder $query): Builder
    {
        $now = Carbon::now(config('app.timezone', 'UTC'));
        $todayStr = $now->toDateString();
        $cutoff24h = $now->copy()->subHours(24);

        return $query->where(function (Builder $q) use ($cutoff24h, $todayStr) {
            $q->whereIn('status', ['confirmed', 'completed'])
              ->orWhere(function (Builder $pending) use ($cutoff24h, $todayStr) {
                  $pending->where('status', 'pending')
                      ->where('created_at', '>', $cutoff24h)
                      ->whereDate('appointment_date', '>=', $todayStr);
              });
        });
    }

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
        return $query->where(function ($q) use ($staffId) {
            $q->where('user_id', $staffId)
              ->orWhereHas('assignedStaff', function ($sq) use ($staffId) {
                  $sq->where('users.id', $staffId);
              });
        });
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