<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
     *
     * @param string|\Carbon\Carbon $date
     * @param string|\Carbon\Carbon $startTime
     * @param string|\Carbon\Carbon $endTime
     */
    public function isAvailableFor($date, $startTime, $endTime, ?int $excludeAppointmentId = null): bool
    {
        if ($this->status === 'maintenance' || !$this->is_active) {
            return false;
        }

        // ── Normalize inputs (handles Carbon instances & bad casts) ──
        $dateStr = $this->normalizeDate($date);
        $startStr = $this->normalizeTime($startTime);
        $endStr = $this->normalizeTime($endTime);

        // Defensive: if we can't parse times, block the room
        if (!$dateStr || !$startStr || !$endStr) {
            return false;
        }

        $query = $this->appointments()
            ->whereDate('appointment_date', $dateStr)
            ->whereIn('status', ['confirmed', 'completed'])
            // ── Correct overlap check (exclusive boundaries) ──
            // Overlap exists if: existing_start < new_end AND existing_end > new_start
            ->where(function ($q) use ($startStr, $endStr) {
                $q->where('start_time', '<', $endStr)
                  ->where('end_time', '>', $startStr);
            });

        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        return !$query->exists();
    }

    /**
     * Extract Y-m-d from string or Carbon instance.
     */
    private function normalizeDate($value): ?string
    {
        if (empty($value)) return null;

        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        // If it's already a date string with time attached (e.g. "2026-06-15 00:00:00")
        if (str_contains($value, ' ')) {
            return substr($value, 0, 10);
        }

        // Assume it's a clean date string
        return $value;
    }

    /**
     * Extract H:i:s from string or Carbon instance.
     */
    private function normalizeTime($value): ?string
    {
        if (empty($value)) return null;

        if ($value instanceof Carbon) {
            return $value->format('H:i:s');
        }

        // If it contains a space, it's likely a datetime string — grab the time part
        if (str_contains($value, ' ')) {
            return substr($value, 11, 8);
        }

        // If it's already H:i or H:i:s, ensure H:i:s format
        if (str_contains($value, ':')) {
            $parts = explode(':', $value);
            return sprintf('%02d:%02d:%02d', $parts[0], $parts[1] ?? 0, $parts[2] ?? 0);
        }

        return null;
    }

    public static function findAvailableFor(Service $service, $date, $startTime, $endTime, ?int $excludeAppointmentId = null)
    {
        $query = self::active()
            ->where('status', '!=', 'maintenance');

        if ($service->requires_room && $service->room_category_id) {
            $query->where(function ($q) use ($service) {
                $q->where('category_id', $service->room_category_id)
                  ->orWhereNull('category_id');
            });
        } elseif (! $service->requires_room) {
            return collect();
        }

        $rooms = $query->get();

        return $rooms->filter(function ($room) use ($date, $startTime, $endTime, $excludeAppointmentId) {
            return $room->isAvailableFor($date, $startTime, $endTime, $excludeAppointmentId);
        });
    }
}