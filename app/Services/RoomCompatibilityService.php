<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Service;
use Illuminate\Support\Collection;

class RoomCompatibilityService
{
    /**
     * Find rooms compatible with ALL services requiring rooms.
     */
    public static function findCompatibleForServices($services): Collection
    {
        $roomReqServices = $services->filter(fn($s) => $s->requires_room);
        if ($roomReqServices->isEmpty()) {
            return collect();
        }

        $categoryIds = $roomReqServices->map(function ($s) {
            return $s->room_category_id ?: $s->category_id;
        })->filter()->unique();

        $query = Room::active()->where('status', '!=', 'maintenance');

        if ($categoryIds->count() === 1) {
            $catId = $categoryIds->first();
            $query->where(function ($q) use ($catId) {
                $q->where('category_id', $catId)
                  ->orWhereNull('category_id');
            });
        } elseif ($categoryIds->count() > 1) {
            // Multiple different categories required -> only General Rooms can accommodate all
            $query->whereNull('category_id');
        } else {
            // requires_room = true but no category specified -> any active non-maintenance room
        }

        return $query->get();
    }

    /**
     * Find rooms that are physically free (available for appointment time) among active non-maintenance rooms
     * matching the required categories or all active rooms.
     */
    public static function findPhysicallyFreeForServices($services, $date, $startTime, $endTime, ?int $excludeAppointmentId = null): Collection
    {
        $roomReqServices = $services->filter(fn($s) => $s->requires_room);
        if ($roomReqServices->isEmpty()) {
            return collect();
        }

        $categoryIds = $roomReqServices->map(function ($s) {
            return $s->room_category_id ?: $s->category_id;
        })->filter()->unique();

        $query = Room::active()->where('status', '!=', 'maintenance');

        if ($categoryIds->isNotEmpty()) {
            $query->where(function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds)
                  ->orWhereNull('category_id');
            });
        }

        $rooms = $query->get();

        return $rooms->filter(function ($room) use ($date, $startTime, $endTime, $excludeAppointmentId) {
            return $room->isAvailableFor($date, $startTime, $endTime, $excludeAppointmentId);
        });
    }

    /**
     * Evaluate slot compatibility and availability for a given service combination and time.
     */
    public static function evaluateSlot($services, $date, $startTime, $endTime, ?int $excludeAppointmentId = null): array
    {
        $roomReqServices = $services->filter(fn($s) => $s->requires_room);
        if ($roomReqServices->isEmpty()) {
            return [
                'requires_room' => false,
                'status' => 'available',
                'compatible_rooms' => collect(),
                'free_rooms' => collect(),
                'room_available' => true,
            ];
        }

        // 1. Check compatible rooms available for the time
        $compatibleRooms = self::findCompatibleForServices($services);
        $availableCompatible = $compatibleRooms->filter(function ($room) use ($date, $startTime, $endTime, $excludeAppointmentId) {
            return $room->isAvailableFor($date, $startTime, $endTime, $excludeAppointmentId);
        });

        $physicallyFree = self::findPhysicallyFreeForServices($services, $date, $startTime, $endTime, $excludeAppointmentId);

        if ($availableCompatible->isNotEmpty()) {
            return [
                'requires_room' => true,
                'status' => 'available',
                'compatible_rooms' => $availableCompatible,
                'free_rooms' => $physicallyFree,
                'room_available' => true,
            ];
        }

        // 2. No compatible room available -> slot is unavailable
        return [
            'requires_room' => true,
            'status' => 'unavailable',
            'compatible_rooms' => collect(),
            'free_rooms' => $physicallyFree,
            'room_available' => false,
        ];
    }
}
