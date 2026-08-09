<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'duration_minutes',
        'price',
        'discount_price',
        'description',
        'landing_description',
        'image',
        'is_package',
        'included_services',        // ← ADD THIS
        'requires_prepayment',
        'is_active',
        'show_on_landing',
        'deposit_percentage_min',
        'deposit_percentage_max',
        'requires_room',
        'room_category_id',
        'code',
    ];

    protected $casts = [
        'is_package' => 'boolean',
        'included_services' => 'array',     // ← ADD THIS
        'requires_prepayment' => 'boolean',
        'is_active' => 'boolean',
        'show_on_landing' => 'boolean',
        'price' => 'decimal:2',
        'deposit_percentage_min' => 'integer',
        'deposit_percentage_max' => 'integer',
        'requires_room' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function staff()
    {
        return $this->belongsToMany(User::class, 'service_staff', 'service_id', 'user_id');
    }

    public function appointments()
    {
        return $this->belongsToMany(Appointment::class, 'appointment_services')
                    ->withPivot('price_at_booking');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSingle($query)
    {
        return $query->where('is_package', false);
    }

    public function requiresPrepayment()
    {
        return $this->requires_prepayment;
    }

    public function roomCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'room_category_id');
    }
}