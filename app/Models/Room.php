<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'status',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Check if room is free for a given time slot.
     */
    public function isAvailableFor(string $date, string $startTime, string $endTime, ?int $excludeAppointmentId = null): bool
    {
        if ($this->status === 'maintenance' || !$this->is_active) {
            return false;
        }

        $query = $this->appointments()
            ->where('appointment_date', $date)
            ->whereIn('status', ['confirmed', 'completed'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function ($sq) use ($startTime, $endTime) {
                      $sq->where('start_time', '<=', $startTime)
                         ->where('end_time', '>=', $endTime);
                  });
            });

        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        return !$query->exists();
    }

    public static function findAvailableFor(Service $service, string $date, string $startTime, string $endTime, ?int $excludeAppointmentId = null)
    {
        $query = self::active()
            ->where('status', '!=', 'maintenance');

        if ($service->requires_room && $service->room_category_id) {
            // Service requires specific category: match category OR general room
            $query->where(function ($q) use ($service) {
                $q->where('category_id', $service->room_category_id)
                  ->orWhereNull('category_id');
            });
        } elseif ($service->requires_room) {
            // Service requires any room (no specific category)
            // Can use any active room
        } else {
            // Service doesn't require room at all
            return collect();
        }

        $rooms = $query->get();

        return $rooms->filter(function ($room) use ($date, $startTime, $endTime, $excludeAppointmentId) {
            return $room->isAvailableFor($date, $startTime, $endTime, $excludeAppointmentId);
        });
    }
}