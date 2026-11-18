<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ServiceCategory $serviceCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_active' => true,
        ]);
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
        $this->admin->roles()->attach($adminRole);

        $this->serviceCategory = ServiceCategory::create([
            'name' => 'General Wellness',
            'color' => '#10B981',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_room_categories_index(): void
    {
        RoomCategory::create([
            'name' => 'Couple Suite',
            'code' => 'COUPLE',
            'description' => 'Room for two participants',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.room-categories.index'));

        $response->assertStatus(200);
        $response->assertSee('Couple Suite');
        $response->assertSee('COUPLE');
    }

    public function test_admin_can_create_room_category(): void
    {
        $payload = [
            'name' => 'VIP Suite',
            'code' => 'vip_suite',
            'description' => 'Luxury room with private shower',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.room-categories.store'), $payload);

        $response->assertRedirect(route('admin.room-categories.index'));
        $this->assertDatabaseHas('room_categories', [
            'name' => 'VIP Suite',
            'code' => 'VIP_SUITE',
            'is_active' => true,
        ]);
    }

    public function test_room_category_code_must_be_unique(): void
    {
        RoomCategory::create([
            'name' => 'Single Room',
            'code' => 'SINGLE',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.room-categories.store'), [
            'name' => 'Another Single Room',
            'code' => 'SINGLE',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_update_and_toggle_room_category(): void
    {
        $category = RoomCategory::create([
            'name' => 'Single Room',
            'code' => 'SINGLE',
            'description' => 'Old description',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.room-categories.update', $category), [
            'name' => 'Updated Single Room',
            'code' => 'SINGLE_UPDATED',
            'description' => 'New description',
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('admin.room-categories.index'));
        $this->assertDatabaseHas('room_categories', [
            'id' => $category->id,
            'name' => 'Updated Single Room',
            'code' => 'SINGLE_UPDATED',
            'description' => 'New description',
            'is_active' => false,
        ]);
    }

    public function test_admin_cannot_delete_room_category_assigned_to_rooms(): void
    {
        $category = RoomCategory::create([
            'name' => 'Couple Suite',
            'code' => 'COUPLE',
            'is_active' => true,
        ]);

        $room = Room::create([
            'name' => 'Room 101',
            'status' => 'available',
            'is_active' => true,
        ]);

        $room->roomCategories()->attach($category->id);

        $response = $this->actingAs($this->admin)->delete(route('admin.room-categories.destroy', $category));

        $response->assertRedirect(route('admin.room-categories.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('room_categories', ['id' => $category->id]);
    }

    public function test_admin_cannot_delete_room_category_referenced_by_services(): void
    {
        $category = RoomCategory::create([
            'name' => 'VIP Lounge',
            'code' => 'VIP',
            'is_active' => true,
        ]);

        Service::create([
            'category_id' => $this->serviceCategory->id,
            'name' => 'VIP Treatment',
            'duration_minutes' => 90,
            'price' => 1500,
            'requires_room' => true,
            'room_category_id' => $category->id,
            'participants_required' => 1,
            'therapists_required' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.room-categories.destroy', $category));

        $response->assertRedirect(route('admin.room-categories.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('room_categories', ['id' => $category->id]);
    }

    public function test_admin_can_delete_unused_room_category(): void
    {
        $category = RoomCategory::create([
            'name' => 'Unused Suite',
            'code' => 'UNUSED',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.room-categories.destroy', $category));

        $response->assertRedirect(route('admin.room-categories.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('room_categories', ['id' => $category->id]);
    }

    public function test_admin_can_assign_multiple_categories_to_room(): void
    {
        $cat1 = RoomCategory::create(['name' => 'Regular', 'code' => 'REG', 'is_active' => true]);
        $cat2 = RoomCategory::create(['name' => 'Couple', 'code' => 'CPL', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('admin.rooms.store'), [
            'name' => 'Room Flex 1',
            'status' => 'available',
            'is_active' => '1',
            'room_category_ids' => [$cat1->id, $cat2->id],
        ]);

        $response->assertRedirect();
        $room = Room::where('name', 'Room Flex 1')->firstOrFail();
        $this->assertCount(2, $room->roomCategories);
        $this->assertTrue($room->roomCategories->contains($cat1));
        $this->assertTrue($room->roomCategories->contains($cat2));

        // Test updating room categories (e.g. removing one)
        $this->actingAs($this->admin)->put(route('admin.rooms.update', $room), [
            'name' => 'Room Flex 1',
            'status' => 'available',
            'is_active' => '1',
            'room_category_ids' => [$cat2->id],
        ]);

        $room->refresh();
        $this->assertCount(1, $room->roomCategories);
        $this->assertTrue($room->roomCategories->contains($cat2));
        $this->assertFalse($room->roomCategories->contains($cat1));
    }

    public function test_admin_can_create_service_with_participants_therapists_and_room_category(): void
    {
        $roomCategory = RoomCategory::create(['name' => 'Couple Suite', 'code' => 'COUPLE', 'is_active' => true]);

        $payload = [
            'category_id' => $this->serviceCategory->id,
            'name' => 'Couples Harmony Massage',
            'duration_minutes' => 90,
            'price' => 2000,
            'participants_required' => 2,
            'therapists_required' => 2,
            'requires_room' => '1',
            'room_category_id' => $roomCategory->id,
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.services.store'), $payload);

        $response->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseHas('services', [
            'name' => 'Couples Harmony Massage',
            'participants_required' => 2,
            'therapists_required' => 2,
            'room_category_id' => $roomCategory->id,
        ]);
    }

    public function test_admin_can_create_package_with_independent_requirements(): void
    {
        $single1 = Service::create([
            'category_id' => $this->serviceCategory->id,
            'name' => 'Single Facial',
            'duration_minutes' => 60,
            'price' => 800,
            'participants_required' => 1,
            'therapists_required' => 1,
            'is_active' => true,
        ]);

        $roomCategory = RoomCategory::create(['name' => 'VIP Room', 'code' => 'VIP', 'is_active' => true]);

        // Package with 4 participants and 2 therapists (independent of child inclusion requirements)
        $payload = [
            'category_id' => $this->serviceCategory->id,
            'name' => 'Group Party Package',
            'duration_minutes' => 120,
            'price' => 3000,
            'is_package' => '1',
            'included_services' => [$single1->id],
            'participants_required' => 4,
            'therapists_required' => 2,
            'requires_room' => '1',
            'room_category_id' => $roomCategory->id,
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.services.store'), $payload);

        $response->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseHas('services', [
            'name' => 'Group Party Package',
            'is_package' => true,
            'participants_required' => 4,
            'therapists_required' => 2,
            'room_category_id' => $roomCategory->id,
        ]);
    }

    public function test_service_validation_rejects_negative_or_zero_participants_and_therapists(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.services.store'), [
            'category_id' => $this->serviceCategory->id,
            'name' => 'Invalid Service',
            'duration_minutes' => 60,
            'price' => 500,
            'participants_required' => 0,
            'therapists_required' => -1,
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors(['participants_required', 'therapists_required']);
    }

    public function test_inactive_room_category_assignment_preservation_for_room(): void
    {
        // 1. Create a room category
        $category = RoomCategory::create([
            'name' => 'Legacy Jacuzzi Suite',
            'code' => 'JACUZZI',
            'is_active' => true,
        ]);

        // 2. Assign it to an existing room
        $room = Room::create([
            'name' => 'Room 205',
            'status' => 'available',
            'is_active' => true,
        ]);
        $room->roomCategories()->sync([$category->id]);

        // 3. Deactivate the category
        $category->update(['is_active' => false]);

        // 4 & 5. Edit the room and save without changing category assignments (send other field changes)
        $response = $this->actingAs($this->admin)->put(route('admin.rooms.update', $room), [
            'name' => 'Room 205 Renamed',
            'status' => 'maintenance',
            'notes' => 'Under scheduled maintenance',
            'is_active' => '1',
            'room_category_ids' => [$category->id], // inactive category preserved in payload
        ]);

        $response->assertRedirect(route('admin.rooms.index'));

        // 6. Verify the inactive category remains assigned
        $room->refresh();
        $this->assertEquals('Room 205 Renamed', $room->name);
        $this->assertEquals('maintenance', $room->status);
        $this->assertCount(1, $room->roomCategories);
        $this->assertEquals($category->id, $room->roomCategories->first()->id);
        $this->assertFalse($room->roomCategories->first()->is_active);

        // Verify index view displays inactive indicator and does not show legacy category as authoritative
        $indexResponse = $this->actingAs($this->admin)->get(route('admin.rooms.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Legacy Jacuzzi Suite');
        $indexResponse->assertSee('(Inactive)');
    }

    public function test_inactive_room_category_preservation_for_service_and_package(): void
    {
        $category = RoomCategory::create([
            'name' => 'Vintage Sauna',
            'code' => 'SAUNA',
            'is_active' => true,
        ]);

        // Single Service with room category
        $service = Service::create([
            'category_id' => $this->serviceCategory->id,
            'name' => 'Sauna Therapy',
            'duration_minutes' => 60,
            'price' => 900,
            'participants_required' => 1,
            'therapists_required' => 1,
            'requires_room' => true,
            'room_category_id' => $category->id,
            'is_active' => true,
        ]);

        // Package with room category
        $package = Service::create([
            'category_id' => $this->serviceCategory->id,
            'name' => 'Sauna Special Package',
            'duration_minutes' => 120,
            'price' => 1800,
            'is_package' => true,
            'included_services' => [$service->id],
            'participants_required' => 2,
            'therapists_required' => 2,
            'requires_room' => true,
            'room_category_id' => $category->id,
            'is_active' => true,
        ]);

        // Deactivate the room category
        $category->update(['is_active' => false]);

        // Update service other fields preserving inactive room_category_id
        $respService = $this->actingAs($this->admin)->put(route('admin.services.update', $service), [
            'category_id' => $this->serviceCategory->id,
            'name' => 'Sauna Therapy Deluxe',
            'duration_minutes' => 75,
            'price' => 1100,
            'participants_required' => 1,
            'therapists_required' => 1,
            'requires_room' => '1',
            'room_category_id' => $category->id,
            'is_active' => '1',
        ]);
        $respService->assertRedirect(route('admin.services.index'));

        $service->refresh();
        $this->assertEquals('Sauna Therapy Deluxe', $service->name);
        $this->assertEquals($category->id, $service->room_category_id);
        $this->assertFalse($service->roomCategory->is_active);

        // Update package other fields preserving inactive room_category_id
        $respPackage = $this->actingAs($this->admin)->put(route('admin.services.update', $package), [
            'category_id' => $this->serviceCategory->id,
            'name' => 'Sauna Special Package Deluxe',
            'duration_minutes' => 120,
            'price' => 2000,
            'is_package' => '1',
            'included_services' => [$service->id],
            'participants_required' => 2,
            'therapists_required' => 2,
            'requires_room' => '1',
            'room_category_id' => $category->id,
            'is_active' => '1',
        ]);
        $respPackage->assertRedirect(route('admin.services.index'));

        $package->refresh();
        $this->assertEquals('Sauna Special Package Deluxe', $package->name);
        $this->assertEquals($category->id, $package->room_category_id);
        $this->assertFalse($package->roomCategory->is_active);
    }

    public function test_room_list_displays_uncategorized_when_no_room_categories_assigned(): void
    {
        // Room with legacy category_id pointing to service_categories, but no roomCategories pivot
        $room = Room::create([
            'name' => 'Legacy Room Only',
            'category_id' => $this->serviceCategory->id,
            'status' => 'available',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.rooms.index'));
        $response->assertStatus(200);

        // Must display Uncategorized because roomCategories relationship is authoritative
        $response->assertSee('Uncategorized');
        // Must NOT display serviceCategory name as a room category badge
        $response->assertDontSee('General Wellness</span>', false);
    }

    public function test_requires_room_false_clears_room_category_id(): void
    {
        $category = RoomCategory::create([
            'name' => 'Standard Suite',
            'code' => 'STD',
            'is_active' => true,
        ]);

        $service = Service::create([
            'category_id' => $this->serviceCategory->id,
            'name' => 'Chair Massage',
            'duration_minutes' => 30,
            'price' => 350,
            'participants_required' => 1,
            'therapists_required' => 1,
            'requires_room' => true,
            'room_category_id' => $category->id,
            'is_active' => true,
        ]);

        // When requires_room is toggled off, room_category_id must be nullified
        $response = $this->actingAs($this->admin)->put(route('admin.services.update', $service), [
            'category_id' => $this->serviceCategory->id,
            'name' => 'Chair Massage',
            'duration_minutes' => 30,
            'price' => 350,
            'participants_required' => 1,
            'therapists_required' => 1,
            'requires_room' => '0',
            'room_category_id' => $category->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.services.index'));
        $service->refresh();
        $this->assertFalse($service->requires_room);
        $this->assertNull($service->room_category_id);
    }
}
