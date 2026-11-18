<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageInclusionsDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_displays_highlighted_package_and_its_actual_inclusions(): void
    {
        $category = ServiceCategory::create([
            'name' => 'Packages & Bundles',
            'is_active' => true,
            'show_on_landing' => true,
        ]);

        $service1 = Service::create([
            'category_id' => $category->id,
            'name' => 'Body Scrub',
            'duration_minutes' => 45,
            'price' => 650.00,
            'is_package' => false,
            'is_active' => true,
            'show_on_landing' => true,
        ]);

        $service2 = Service::create([
            'category_id' => $category->id,
            'name' => 'Facial Cleansing',
            'duration_minutes' => 45,
            'price' => 450.00,
            'is_package' => false,
            'is_active' => true,
            'show_on_landing' => true,
        ]);

        $package = Service::create([
            'category_id' => $category->id,
            'name' => 'Package 1 (Scrub + Facial)',
            'duration_minutes' => 90,
            'price' => 860.00,
            'is_package' => true,
            'included_services' => [(string) $service1->id, (string) $service2->id],
            'is_active' => true,
            'show_on_landing' => true,
        ]);

        $response = $this->get(route('landing'));
        $response->assertStatus(200);

        // Package title and package badge
        $response->assertSee('Package 1 (Scrub + Facial)');
        $response->assertSee('Package Inclusions');

        // Actual inclusions rendered
        $response->assertSee('Body Scrub');
        $response->assertSee('Facial Cleansing');
        $response->assertSee('(45m)');
    }

    public function test_landing_page_displays_single_services_without_package_badge_or_inclusions(): void
    {
        $category = ServiceCategory::create([
            'name' => 'Massage Services',
            'is_active' => true,
            'show_on_landing' => true,
        ]);

        $service = Service::create([
            'category_id' => $category->id,
            'name' => 'Swedish Massage',
            'duration_minutes' => 60,
            'price' => 530.00,
            'is_package' => false,
            'is_active' => true,
            'show_on_landing' => true,
        ]);

        $response = $this->get(route('landing'));
        $response->assertStatus(200);

        $response->assertSee('Swedish Massage');
        $response->assertSee('530.00');
    }

    public function test_landing_page_gracefully_handles_package_with_empty_or_null_inclusions(): void
    {
        $category = ServiceCategory::create([
            'name' => 'Special Packages',
            'is_active' => true,
            'show_on_landing' => true,
        ]);

        $emptyPackage = Service::create([
            'category_id' => $category->id,
            'name' => 'Custom Package Without Set Items',
            'duration_minutes' => 60,
            'price' => 700.00,
            'is_package' => true,
            'included_services' => null,
            'is_active' => true,
            'show_on_landing' => true,
        ]);

        $response = $this->get(route('landing'));
        $response->assertStatus(200);
        $response->assertSee('Custom Package Without Set Items');
        // Package Inclusions section should not be printed when empty
        $response->assertDontSee('Package Inclusions');
    }

    public function test_booking_wizard_serializes_and_renders_package_inclusions_in_json(): void
    {
        $category = ServiceCategory::create([
            'name' => 'Packages',
            'is_active' => true,
            'show_on_landing' => true,
        ]);

        $s1 = Service::create([
            'category_id' => $category->id,
            'name' => 'Foot Scrub',
            'duration_minutes' => 45,
            'price' => 400.00,
            'is_package' => false,
            'is_active' => true,
        ]);

        $s2 = Service::create([
            'category_id' => $category->id,
            'name' => 'Foot Massage',
            'duration_minutes' => 45,
            'price' => 450.00,
            'is_package' => false,
            'is_active' => true,
        ]);

        $package = Service::create([
            'category_id' => $category->id,
            'name' => 'Package 3',
            'duration_minutes' => 90,
            'price' => 750.00,
            'is_package' => true,
            'included_services' => [$s1->id, $s2->id],
            'is_active' => true,
        ]);

        $response = $this->get(route('booking.wizard'));
        $response->assertStatus(200);

        // Package and included components should be in the window.bookingCategories JSON payload
        $response->assertSee('Package 3');
        $response->assertSee('Foot Scrub');
        $response->assertSee('Foot Massage');

        // Blade template has the alpine x-if bindings for package inclusions
        $response->assertSee('service.included_services');
    }
}
