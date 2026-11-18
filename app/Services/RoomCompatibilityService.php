<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Service;
use Illuminate\Support\Collection;

class RoomCompatibilityService
{
    /**
     * Find rooms compatible with ALL services requiring rooms.
     *
     * Strict Room Compatibility Rules:
     * 1. Rooms must be active (is_active = true) and not under maintenance (status != 'maintenance').
     * 2. Compatibility is determined strictly by the new many-to-many roomCategories relationship.
     * 3. Legacy rooms.category_id is never used as a substitute for roomCategories.
     * 4. Unclassified rooms (rooms without assigned roomCategories) are NEVER treated as universally compatible.
     * 5. For services with requires_room = true and null room_category_id: no general-room policy is invented;
     *    such services cannot be matched to rooms until a room category is configured by the admin.
     */
    public static function findCompatibleForServices($services): Collection
    {
        $roomReqServices = $services->filter(fn($s) => (bool) $s->requires_room);
        if ($roomReqServices->isEmpty()) {
            return collect();
        }

        // If any service requires a room but has no room_category_id configured,
        // it cannot be matched to any room under strict category architecture.
        if ($roomReqServices->contains(fn($s) => is_null($s->room_category_id))) {
            return collect();
        }

        $roomCategoryIds = $roomReqServices->pluck('room_category_id')->filter()->unique();
        if ($roomCategoryIds->isEmpty()) {
            return collect();
        }

        $query = Room::active()->where('status', '!=', 'maintenance');

        // A room must explicitly possess EVERY required room category via the roomCategories pivot
        foreach ($roomCategoryIds as $rcId) {
            $query->whereHas('roomCategories', function ($sub) use ($rcId) {
                $sub->where('room_categories.id', $rcId);
            });
        }

        return $query->get();
    }

    /**
     * Find rooms that are physically free (available for appointment time) among active non-maintenance rooms
     * matching the required categories or all active rooms.
     */
    public static function findPhysicallyFreeForServices($services, $date, $startTime, $endTime, ?int $excludeAppointmentId = null): Collection
    {
        $rooms = self::findCompatibleForServices($services);

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
