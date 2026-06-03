<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LandingSetting;

class LandingSettingsSeeder extends Seeder
{
    public function run(): void
    {
        LandingSetting::updateOrCreate(['key' => 'hero_title'], ['value' => 'Spa Alexandria']);
        LandingSetting::updateOrCreate(['key' => 'hero_subtitle'], ['value' => 'Experience tranquility, rejuvenation, and personalized care.']);
    }
}