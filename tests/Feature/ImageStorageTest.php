<?php

namespace Tests\Feature;

use App\Models\LandingSetting;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageStorageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected User $receptionist;
    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('uploads');
        Storage::fake('public');

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->receptionist = User::factory()->create();
        $this->receptionist->roles()->attach($receptionistRole);

        $this->customer = User::factory()->create();
        $this->customer->roles()->attach($customerRole);

        $this->category = ServiceCategory::create([
            'name' => 'Massage Therapy',
            'color' => '#14b8a6',
            'is_active' => true,
        ]);
    }

    /**
     * Helper to create valid image files without requiring the PHP GD extension.
     */
    protected function createRealImageFile(string $name = 'test.jpg', string $type = 'jpg'): UploadedFile
    {
        $payloads = [
            'jpg' => '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=',
            'jpeg' => '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=',
            'png' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            'webp' => 'UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoBAAEALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA',
        ];

        $content = base64_decode($payloads[$type] ?? $payloads['jpg']);

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    public function test_admin_can_upload_service_image_to_uploads_disk(): void
    {
        $file = $this->createRealImageFile('swedish.jpg', 'jpg');

        $response = $this->actingAs($this->admin)->post(route('admin.services.store'), [
            'category_id' => $this->category->id,
            'name' => 'Swedish Massage',
            'duration_minutes' => 60,
            'price' => 500,
            'image' => $file,
            'is_active' => 1,
            'show_on_landing' => 1,
        ]);

        $response->assertRedirect(route('admin.services.index'));

        $service = Service::where('name', 'Swedish Massage')->first();
        $this->assertNotNull($service);
        $this->assertNotNull($service->image);
        $this->assertStringStartsWith('services/', $service->image);

        // Assert file exists on the `uploads` disk
        Storage::disk('uploads')->assertExists($service->image);
    }

    public function test_image_url_accessor_returns_valid_url_when_file_exists(): void
    {
        $file = $this->createRealImageFile('facial.png', 'png');
        $path = $file->store('services', 'uploads');

        $service = Service::create([
            'category_id' => $this->category->id,
            'name' => 'Signature Facial',
            'duration_minutes' => 45,
            'price' => 600,
            'image' => $path,
        ]);

        $this->assertNotNull($service->image_url);
        // Under Storage::fake(), the adapter returns a dummy URL ending with the file path.
        // Under real uploads disk, config URL is http://localhost/uploads/services/...
        $this->assertStringEndsWith($path, $service->image_url);
    }

    public function test_image_url_accessor_returns_null_when_file_is_missing(): void
    {
        $service = Service::create([
            'category_id' => $this->category->id,
            'name' => 'Hot Stone Massage',
            'duration_minutes' => 90,
            'price' => 1200,
            'image' => 'services/non_existent_image_12345.jpg',
        ]);

        // File does not exist on disk, accessor must return null
        $this->assertNull($service->image_url);
    }

    public function test_image_url_accessor_passes_through_external_urls(): void
    {
        $externalUrl = 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=500';

        $service = Service::create([
            'category_id' => $this->category->id,
            'name' => 'Aromatherapy',
            'duration_minutes' => 60,
            'price' => 800,
            'image' => $externalUrl,
        ]);

        $this->assertEquals($externalUrl, $service->image_url);
    }

    public function test_service_image_replacement_is_transactional(): void
    {
        // 1. Initial upload
        $oldFile = $this->createRealImageFile('old.jpg', 'jpg');
        $oldPath = $oldFile->store('services', 'uploads');

        $service = Service::create([
            'category_id' => $this->category->id,
            'name' => 'Deep Tissue',
            'duration_minutes' => 60,
            'price' => 700,
            'image' => $oldPath,
        ]);

        Storage::disk('uploads')->assertExists($oldPath);

        // 2. Replace with new image
        $newFile = $this->createRealImageFile('new.webp', 'webp');

        $response = $this->actingAs($this->admin)->put(route('admin.services.update', $service), [
            'category_id' => $this->category->id,
            'name' => 'Deep Tissue',
            'duration_minutes' => 60,
            'price' => 700,
            'image' => $newFile,
        ]);

        $response->assertRedirect(route('admin.services.index'));

        $service->refresh();
        $this->assertNotEquals($oldPath, $service->image);
        Storage::disk('uploads')->assertExists($service->image);
        Storage::disk('uploads')->assertMissing($oldPath);
    }

    public function test_service_image_removal_deletes_file_from_disk(): void
    {
        $file = $this->createRealImageFile('toremove.jpg', 'jpg');
        $path = $file->store('services', 'uploads');

        $service = Service::create([
            'category_id' => $this->category->id,
            'name' => 'Foot Reflexology',
            'duration_minutes' => 45,
            'price' => 400,
            'image' => $path,
        ]);

        Storage::disk('uploads')->assertExists($path);

        $response = $this->actingAs($this->admin)->put(route('admin.services.update', $service), [
            'category_id' => $this->category->id,
            'name' => 'Foot Reflexology',
            'duration_minutes' => 45,
            'price' => 400,
            'remove_image' => 1,
        ]);

        $response->assertRedirect(route('admin.services.index'));

        $service->refresh();
        $this->assertNull($service->image);
        Storage::disk('uploads')->assertMissing($path);
    }

    public function test_service_image_is_not_deleted_if_shared_by_another_service(): void
    {
        $file = $this->createRealImageFile('shared.jpg', 'jpg');
        $sharedPath = $file->store('services', 'uploads');

        $serviceA = Service::create([
            'category_id' => $this->category->id,
            'name' => 'Package Service A',
            'duration_minutes' => 60,
            'price' => 500,
            'image' => $sharedPath,
        ]);

        $serviceB = Service::create([
            'category_id' => $this->category->id,
            'name' => 'Package Service B',
            'duration_minutes' => 60,
            'price' => 500,
            'image' => $sharedPath,
        ]);

        // Replace service A's image
        $newFile = $this->createRealImageFile('newA.jpg', 'jpg');
        $this->actingAs($this->admin)->put(route('admin.services.update', $serviceA), [
            'category_id' => $this->category->id,
            'name' => 'Package Service A',
            'duration_minutes' => 60,
            'price' => 500,
            'image' => $newFile,
        ]);

        // Shared file should STILL exist because service B uses it
        Storage::disk('uploads')->assertExists($sharedPath);
    }

    public function test_executable_and_svg_uploads_are_rejected_by_validation(): void
    {
        // Reject PHP file disguised as image
        $phpFile = UploadedFile::fake()->create('shell.php', 10, 'text/x-php');

        $responsePhp = $this->actingAs($this->admin)->post(route('admin.services.store'), [
            'category_id' => $this->category->id,
            'name' => 'Bad Service 1',
            'duration_minutes' => 60,
            'price' => 500,
            'image' => $phpFile,
        ]);
        $responsePhp->assertSessionHasErrors('image');

        // Reject SVG upload (excluded per requirements)
        $svgFile = UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml');

        $responseSvg = $this->actingAs($this->admin)->post(route('admin.services.store'), [
            'category_id' => $this->category->id,
            'name' => 'Bad Service 2',
            'duration_minutes' => 60,
            'price' => 500,
            'image' => $svgFile,
        ]);
        $responseSvg->assertSessionHasErrors('image');
    }

    public function test_landing_hero_image_upload_stores_in_uploads_disk(): void
    {
        $heroFile = $this->createRealImageFile('hero.jpg', 'jpg');

        $response = $this->actingAs($this->admin)->post(route('admin.landing.update'), [
            'hero_title' => 'Welcome to Luxury Spa',
            'hero_subtitle' => 'Relax and Rejuvenate',
            'hero_image' => $heroFile,
        ]);

        $response->assertSessionHasNoErrors();

        $heroSetting = LandingSetting::where('key', 'hero_image')->first();
        $this->assertNotNull($heroSetting);
        $this->assertNotNull($heroSetting->value);
        $this->assertStringStartsWith('landing/', $heroSetting->value);

        Storage::disk('uploads')->assertExists($heroSetting->value);
    }

    public function test_unauthorized_users_cannot_upload_or_modify_service_images(): void
    {
        $file = $this->createRealImageFile('test.jpg', 'jpg');

        // Guest
        $guestResponse = $this->post(route('admin.services.store'), [
            'category_id' => $this->category->id,
            'name' => 'Guest Service',
            'duration_minutes' => 60,
            'price' => 500,
            'image' => $file,
        ]);
        $guestResponse->assertRedirect(route('login'));

        // Customer
        $customerResponse = $this->actingAs($this->customer)->post(route('admin.services.store'), [
            'category_id' => $this->category->id,
            'name' => 'Customer Service',
            'duration_minutes' => 60,
            'price' => 500,
            'image' => $file,
        ]);
        $customerResponse->assertStatus(403);
    }
}
