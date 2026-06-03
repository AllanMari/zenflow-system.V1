<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    // Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Assigned staff
    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Services booked
    public function services()
    {
        return $this->belongsToMany(Service::class, 'appointment_services')
                    ->withPivot('price_at_booking', 'service_name', 'service_duration', 'is_extra');
    }

    // Payments made
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Who created this appointment
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Calculate total paid
    public function getTotalPaidAttribute()
    {
        return $this->payments->sum('amount');
    }

    // Calculate remaining balance
    public function getRemainingBalanceAttribute()
    {
        return max(0, $this->total_price - $this->total_paid);
    }

    // Check if fully paid
    public function isFullyPaid()
    {
        return $this->remaining_balance <= 0;
    }

    // Check if deposit paid
    public function isDepositPaid()
    {
        $depositAmount = $this->total_price * 0.2; // 20% deposit
        return $this->total_paid >= $depositAmount;
    }

    // Format time for display
    public function getTimeRangeAttribute()
    {
        return Carbon::parse($this->start_time)->format('g:i A') . ' - ' . 
               Carbon::parse($this->end_time)->format('g:i A');
    }

    // Scope: Pending appointments
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Scope: Confirmed appointments
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    // Scope: Today's appointments
    public function scopeToday($query)
    {
        return $query->whereDate('appointment_date', today());
    }

    // Scope: Upcoming appointments
    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', today())
                     ->whereIn('status', ['pending', 'confirmed']);
    }

    // Scope: For specific staff
    public function scopeForStaff($query, $staffId)
    {
        return $query->where('user_id', $staffId);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}