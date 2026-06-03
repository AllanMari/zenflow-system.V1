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
        'email',
        'phone_number',
        'customer_type',
        'medical_notes'
    ];

    protected $casts = [
        'customer_type' => 'string',
    ];

    // Link to user account (if registered)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // All appointments
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    // Check if guest (no user account)
    public function isGuest()
    {
        return is_null($this->user_id);
    }

    // Get full name
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Get name from user if linked, otherwise from customer record
    public function getNameAttribute()
    {
        if ($this->user) {
            return $this->user->first_name . ' ' . $this->user->last_name;
        }
        return $this->full_name;
    }
}