<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class FaceAndBodyDraftSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'category_id'          => 6,
                'name'                 => 'Body Scrub',
                'duration_minutes'     => null,
                'price'                => 650.00,
                'description'          => 'Exfoliate, smooth, and glow with a refreshing full-body cleanse.',
                'landing_description'  => 'Exfoliate, smooth, and glow with a refreshing full-body cleanse.',
                'is_package'           => false,
                'is_active'            => false,
                'show_on_landing'      => false,
                'requires_room'        => false,
                'room_category_id'     => null,
                'participants_required' => 1,
                'therapists_required'  => 1,
            ],
            [
                'category_id'          => 6,
                'name'                 => 'Foot Spa',
                'duration_minutes'     => null,
                'price'                => 500.00,
                'description'          => 'Soothing scrub and light massage for soft, refreshed feet.',
                'landing_description'  => 'Soothing scrub and light massage for soft, refreshed feet.',
                'is_package'           => false,
                'is_active'            => false,
                'show_on_landing'      => false,
                'requires_room'        => false,
                'room_category_id'     => null,
                'participants_required' => 1,
                'therapists_required'  => 1,
            ],
            [
                'category_id'          => 6,
                'name'                 => 'Facial Cleansing',
                'duration_minutes'     => null,
                'price'                => 450.00,
                'description'          => 'Deep clean, unclog pores, and reveal fresh, glowing skin.',
                'landing_description'  => 'Deep clean, unclog pores, and reveal fresh, glowing skin.',
                'is_package'           => false,
                'is_active'            => false,
                'show_on_landing'      => false,
                'requires_room'        => false,
                'room_category_id'     => null,
                'participants_required' => 1,
                'therapists_required'  => 1,
            ],
        ];

        foreach ($services as $data) {
            Service::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }

        // Verify
        $created = Service::whereIn('name', ['Body Scrub', 'Foot Spa', 'Facial Cleansing'])->get();
        foreach ($created as $s) {
            $this->command->info("Service: {$s->id} | {$s->name} | duration_minutes=" . ($s->duration_minutes ?? 'NULL') . " | is_active=" . ($s->is_active ? 'true' : 'false'));
        }
    }
}
