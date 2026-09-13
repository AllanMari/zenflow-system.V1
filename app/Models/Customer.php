<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'nickname',
        'email',
        'phone_number',
        'customer_type',
        'medical_notes',
    ];

    protected $casts = [
        'customer_type' => 'string',
    ];

    /**
     * Linked user account.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * All appointments.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Check if this is a guest customer.
     */
    public function isGuest()
    {
        return is_null($this->user_id);
    }

    /**
     * Get the customer's actual full name.
     *
     * Used for internal/admin/receptionist purposes.
     */
    public function getFullNameAttribute(): string
    {
        return trim(
            ($this->first_name ?? '') . ' ' . ($this->last_name ?? '')
        );
    }

    /**
     * Get the customer's preferred display name.
     *
     * If a nickname exists, use it.
     * Otherwise fall back to the actual name.
     */
    public function getDisplayNameAttribute(): string
    {
        if (!empty($this->nickname)) {
            return $this->nickname;
        }

        return $this->full_name;
    }

    /**
     * Get the customer's name.
     *
     * Kept for compatibility with existing code.
     *
     * For registered customers, prefer the nickname if available.
     */
    public function getNameAttribute(): string
    {
        if (!empty($this->nickname)) {
            return $this->nickname;
        }

        if ($this->user) {
            return trim(
                ($this->user->first_name ?? '') . ' ' .
                ($this->user->last_name ?? '')
            );
        }

        return $this->full_name;
    }
}