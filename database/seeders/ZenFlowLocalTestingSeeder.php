<?php

namespace Database\Seeders;

use App\Models\PackageChoiceGroup;
use App\Models\PackageChoiceOption;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * OPTIONAL local-only sample data for manual testing.
 *
 * Never registered in DatabaseSeeder. Run explicitly:
 *   php artisan db:seed --class=ZenFlowLocalTestingSeeder
 *
 * It only creates records whose names start with "LOCAL TEST -" and never
 * deletes, truncates or overwrites existing business data.
 */
class ZenFlowLocalTestingSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment() !== 'local') {
            abort(403, 'ZenFlowLocalTestingSeeder may only run in the local environment.');
        }

        $category = ServiceCategory::firstOrCreate(
            ['name' => 'LOCAL TEST - Massage'],
            ['is_active' => true]
        );

        $roomCategory = RoomCategory::firstOrCreate(
            ['code' => 'local_test_room'],
            ['name' => 'LOCAL TEST - Standard Room', 'is_active' => true, 'description' => 'Sample room category for local testing.']
        );

        foreach ([1, 2] as $index) {
            $room = Room::firstOrCreate(
                ['name' => 'LOCAL TEST - Room ' . $index],
                ['status' => 'available', 'is_active' => true, 'notes' => 'Local testing sample room.']
            );
            $room->roomCategories()->syncWithoutDetaching([$roomCategory->id]);
        }

        $massage = Service::firstOrCreate(
            ['name' => 'LOCAL TEST - 60 Minute Massage'],
            [
                'category_id' => $category->id,
                'room_category_id' => $roomCategory->id,
                'duration_minutes' => 60,
                'price' => 1000,
                'is_active' => true,
                'requires_room' => true,
                'show_on_landing' => false,
            ]
        );

        $addOn = Service::firstOrCreate(
            ['name' => 'LOCAL TEST - 30 Minute Add-on'],
            [
                'category_id' => $category->id,
                'room_category_id' => $roomCategory->id,
                'duration_minutes' => 30,
                'price' => 500,
                'is_active' => true,
                'requires_room' => true,
                'show_on_landing' => false,
            ]
        );

        $package = Service::firstOrCreate(
            ['name' => 'LOCAL TEST - Couples Package'],
            [
                'category_id' => $category->id,
                'room_category_id' => $roomCategory->id,
                'duration_minutes' => 90,
                'price' => 2500,
                'is_active' => true,
                'requires_room' => true,
                'is_package' => true,
                'therapists_required' => 2,
                'participants_required' => 2,
                'included_services' => [$massage->id, $addOn->id],
                'show_on_landing' => false,
            ]
        );

        $group = PackageChoiceGroup::firstOrCreate(
            ['service_id' => $package->id, 'label' => 'LOCAL TEST - Choice'],
            ['is_required' => false, 'max_selections' => 1]
        );

        PackageChoiceOption::firstOrCreate(
            ['choice_group_id' => $group->id, 'service_id' => $addOn->id],
            ['sort_order' => 1]
        );

        // Dedicated local test therapists. The users table has no email column,
        // so reserved "local.test.*" usernames serve as deterministic identifiers.
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        $therapists = [
            'local.test.therapist01' => ['first' => 'LOCAL TEST', 'last' => 'Therapist 01', 'days' => [1, 2, 3, 4, 5, 6], 'start' => '09:00:00', 'end' => '17:00:00'],
            'local.test.therapist02' => ['first' => 'LOCAL TEST', 'last' => 'Therapist 02', 'days' => [1, 2, 3, 4, 5, 6], 'start' => '10:00:00', 'end' => '18:00:00'],
            'local.test.therapist03' => ['first' => 'LOCAL TEST', 'last' => 'Therapist 03', 'days' => [2, 3, 4, 5, 6], 'start' => '12:00:00', 'end' => '20:00:00'],
        ];

        foreach ($therapists as $username => $definition) {
            $therapist = User::firstOrCreate(
                ['username' => $username],
                [
                    'first_name' => $definition['first'],
                    'last_name' => $definition['last'],
                    // Random per-account local password; never exposed or shared.
                    'password' => Hash::make(Str::random(32)),
                    'is_active' => true,
                ]
            );

            $therapist->roles()->syncWithoutDetaching([$staffRole->id]);

            // work_schedules stores one row per day. start_time/end_time are
            // NOT NULL in the schema, so days off use a neutral placeholder
            // together with is_day_off = true (the placeholder is never used
            // by availability logic, which filters on is_day_off = false).
            for ($dayOfWeek = 0; $dayOfWeek <= 6; $dayOfWeek++) {
                $works = in_array($dayOfWeek, $definition['days'], true);

                WorkSchedule::updateOrCreate(
                    [
                        'user_id' => $therapist->id,
                        'day_of_week' => $dayOfWeek,
                    ],
                    [
                        'start_time' => $works ? $definition['start'] : '00:00:00',
                        'end_time' => $works ? $definition['end'] : '00:00:00',
                        'is_day_off' => !$works,
                    ]
                );
            }
        }

        $this->command?->info('LOCAL TEST sample categories, rooms, services, package, therapists and weekly schedules created (idempotent).');
    }
}
