<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Services\AppointmentOccupancyService;

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

    /**
     * Many-to-many: the room categories this physical room belongs to.
     */
    public function roomCategories()
    {
        return $this->belongsToMany(RoomCategory::class, 'room_category_room')
                    ->withTimestamps();
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Derived operational state: treatment has ended but the room is still in
     * its five-minute preparation window. No database column is added.
     */
    public function isPreparing(?Carbon $atTime = null): bool
    {
        $at = $atTime ? $atTime->copy()->timezone('Asia/Manila') : Carbon::now('Asia/Manila');

        $occupancy = app(AppointmentOccupancyService::class);

        return $this->appointments()
            ->whereDate('appointment_date', $at->toDateString())
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->with('services')
            ->get()
            ->contains(function ($appointment) use ($occupancy, $at) {
                $treatmentEnd = $occupancy->treatmentEnd($appointment);
                $resourceEnd = $occupancy->resourceEnd($appointment);

                return $treatmentEnd->lessThanOrEqualTo($at) && $resourceEnd->greaterThan($at);
            });
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
     * Check if the room currently has an active in-progress confirmed appointment.
     */
    public function isCurrentlyOccupied(?Carbon $atTime = null): bool
    {
        $at = $atTime ? $atTime->copy()->timezone('Asia/Manila') : Carbon::now('Asia/Manila');
        $dateStr = $at->toDateString();
        $timeStr = $at->format('H:i:s');

        return $this->appointments()
            ->whereDate('appointment_date', $dateStr)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->where('start_time', '<=', $timeStr)
            ->where('end_time', '>', $timeStr)
            ->exists();
    }

    /**
     * Check if room is free for a given time slot.
     *
     * @param string|\Carbon\Carbon $date
     * @param string|\Carbon\Carbon $startTime
     * @param string|\Carbon\Carbon $endTime
     */
    public function isAvailableFor(
        $date,
        $startTime,
        $endTime,
        ?int $excludeAppointmentId = null,
        bool $lockForUpdate = false
    ): bool {
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

        // Resource end includes the post-treatment preparation period for this room.
        return !app(AppointmentOccupancyService::class)->hasRoomConflict(
            (int) $this->id,
            $dateStr,
            $startStr,
            $endStr,
            $excludeAppointmentId,
            $lockForUpdate
        );
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

    public static function findCompatibleForServices($services)
    {
        return \App\Services\RoomCompatibilityService::findCompatibleForServices($services);
    }

    public static function findAvailableForServices($services, $date, $startTime, $endTime, ?int $excludeAppointmentId = null)
    {
        $eval = \App\Services\RoomCompatibilityService::evaluateSlot($services, $date, $startTime, $endTime, $excludeAppointmentId);
        return $eval['free_rooms'];
    }

    public static function findAvailableFor(Service $service, $date, $startTime, $endTime, ?int $excludeAppointmentId = null)
    {
        return self::findAvailableForServices(collect([$service]), $date, $startTime, $endTime, $excludeAppointmentId);
    }
}
