<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1FoundationTest extends TestCase
{
    use RefreshDatabase;

    // ─── Room Categories ──────────────────────────────────────────────────────

    public function test_room_category_can_be_created(): void
    {
        $cat = RoomCategory::create([
            'name'   => 'Couple Room',
            'code'   => 'couple',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('room_categories', [
            'code' => 'couple',
            'name' => 'Couple Room',
        ]);
        $this->assertTrue($cat->is_active);
    }

    public function test_room_belongs_to_many_room_categories(): void
    {
        $room = Room::create([
            'name'      => 'Suite 1',
            'status'    => 'available',
            'is_active' => true,
        ]);

        $couple = RoomCategory::create(['name' => 'Couple Room', 'code' => 'couple']);
        $vip    = RoomCategory::create(['name' => 'VIP Suite',   'code' => 'vip']);

        $room->roomCategories()->attach([$couple->id, $vip->id]);

        $this->assertCount(2, $room->fresh()->roomCategories);
        $this->assertTrue($room->roomCategories->contains('code', 'couple'));
        $this->assertTrue($room->roomCategories->contains('code', 'vip'));
    }

    public function test_room_category_belongs_to_many_rooms(): void
    {
        $catCouple = RoomCategory::create(['name' => 'Couple Room', 'code' => 'couple']);

        $roomA = Room::create(['name' => 'A', 'status' => 'available', 'is_active' => true]);
        $roomB = Room::create(['name' => 'B', 'status' => 'available', 'is_active' => true]);

        $catCouple->rooms()->attach([$roomA->id, $roomB->id]);

        $this->assertCount(2, $catCouple->fresh()->rooms);
    }

    public function test_pivot_enforces_unique_room_category_pair(): void
    {
        $room = Room::create(['name' => 'R', 'status' => 'available', 'is_active' => true]);
        $cat  = RoomCategory::create(['name' => 'Standard', 'code' => 'standard']);

        $room->roomCategories()->attach($cat->id);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Duplicate insert should fail on the unique index
        \Illuminate\Support\Facades\DB::table('room_category_room')->insert([
            'room_id'          => $room->id,
            'room_category_id' => $cat->id,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    public function test_room_category_active_scope_filters_inactive(): void
    {
        RoomCategory::create(['name' => 'Active Cat',   'code' => 'active',   'is_active' => true]);
        RoomCategory::create(['name' => 'Inactive Cat', 'code' => 'inactive', 'is_active' => false]);

        $this->assertCount(1, RoomCategory::active()->get());
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeServiceCategory(string $name = 'Test Category'): \App\Models\ServiceCategory
    {
        return \App\Models\ServiceCategory::create(['name' => $name, 'is_active' => true]);
    }

    private function makeUser(string $role = 'staff'): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $roleModel = \App\Models\Role::firstOrCreate(['name' => $role]);
        $user->roles()->attach($roleModel);
        return $user;
    }

    private function makeAppointmentWithStaff(): Appointment
    {
        $cat     = $this->makeServiceCategory();
        $service = Service::create([
            'name'             => 'Test Service',
            'category_id'      => $cat->id,
            'duration_minutes' => 60,
            'price'            => 500,
            'is_active'        => true,
            'requires_room'    => false,
        ]);
        $therapist = $this->makeUser('staff');

        // appointments.customer_id is NOT NULL — create a minimal customer
        $customer = \App\Models\Customer::create([
            'first_name'    => 'Test',
            'last_name'     => 'Client',
            'customer_type' => 'regular',
            'phone_number'  => '09' . rand(100000000, 999999999),
        ]);

        $appt = Appointment::create([
            'customer_id'      => $customer->id,
            'user_id'          => $therapist->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time'       => '10:00:00',
            'end_time'         => '11:00:00',
            'status'           => 'pending',
            'total_price'      => 500,
        ]);
        $appt->services()->attach($service->id, ['price_at_booking' => 500]);

        // Backfill pivot for this test-created appointment
        \Illuminate\Support\Facades\DB::table('appointment_staff')->insert([
            'appointment_id' => $appt->id,
            'user_id'        => $therapist->id,
            'role'           => 'primary',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return $appt;
    }

    // ─── Service: participants_required & therapists_required ─────────────────

    public function test_service_has_participants_and_therapists_required_with_defaults(): void
    {
        $cat = $this->makeServiceCategory();
        $service = Service::create([
            'name'            => 'Single Massage',
            'category_id'     => $cat->id,
            'duration_minutes'=> 60,
            'price'           => 550,
            'is_active'       => true,
            'requires_room'   => false,
        ]);

        $this->assertEquals(1, $service->participants_required);
        $this->assertEquals(1, $service->therapists_required);
    }

    public function test_service_accepts_custom_participants_and_therapists(): void
    {
        $cat = $this->makeServiceCategory();
        $service = Service::create([
            'name'                 => 'Couple Package',
            'category_id'          => $cat->id,
            'duration_minutes'     => 90,
            'price'                => 2299,
            'is_package'           => true,
            'is_active'            => true,
            'requires_room'        => true,
            'participants_required'=> 2,
            'therapists_required'  => 2,
        ]);

        $this->assertEquals(2, $service->participants_required);
        $this->assertEquals(2, $service->therapists_required);
    }

    public function test_participants_and_therapists_are_independent(): void
    {
        $cat = $this->makeServiceCategory();
        $service = Service::create([
            'name'                 => 'Assisted Massage',
            'category_id'          => $cat->id,
            'duration_minutes'     => 60,
            'price'                => 650,
            'is_active'            => true,
            'requires_room'        => false,
            'participants_required'=> 1,
            'therapists_required'  => 2,
        ]);

        $this->assertEquals(1, $service->participants_required);
        $this->assertEquals(2, $service->therapists_required);
    }

    public function test_service_room_category_relationship(): void
    {
        $cat     = $this->makeServiceCategory();
        $roomCat = RoomCategory::create(['name' => 'Standard', 'code' => 'standard']);

        $service = Service::create([
            'name'            => 'Massage',
            'category_id'     => $cat->id,
            'duration_minutes'=> 60,
            'price'           => 500,
            'is_active'       => true,
            'requires_room'   => true,
            'room_category_id'=> $roomCat->id,
        ]);

        $this->assertInstanceOf(RoomCategory::class, $service->fresh()->roomCategory);
        $this->assertEquals('standard', $service->fresh()->roomCategory->code);
    }

    // ─── appointment_staff ────────────────────────────────────────────────────

    public function test_appointment_assigned_staff_relationship_exists(): void
    {
        $appt = $this->makeAppointmentWithStaff();

        $this->assertDatabaseHas('appointment_staff', [
            'appointment_id' => $appt->id,
            'user_id'        => $appt->user_id,
            'role'           => 'primary',
        ]);
    }

    public function test_appointment_assigned_staff_includes_lead_and_assisting(): void
    {
        $appt      = $this->makeAppointmentWithStaff();
        $assistant = $this->makeUser('staff');

        \Illuminate\Support\Facades\DB::table('appointment_staff')->insert([
            'appointment_id' => $appt->id,
            'user_id'        => $assistant->id,
            'role'           => 'assisting',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $loaded = $appt->fresh()->assignedStaff;

        $this->assertCount(2, $loaded);
        $this->assertTrue($loaded->contains('id', $appt->user_id));
        $this->assertTrue($loaded->contains('id', $assistant->id));

        $pivot = $loaded->firstWhere('id', $assistant->id)->pivot;
        $this->assertEquals('assisting', $pivot->role);
    }

    public function test_appointment_pivot_enforces_unique_therapist_per_appointment(): void
    {
        $appt = $this->makeAppointmentWithStaff();

        $this->expectException(\Illuminate\Database\QueryException::class);

        \Illuminate\Support\Facades\DB::table('appointment_staff')->insert([
            'appointment_id' => $appt->id,
            'user_id'        => $appt->user_id,
            'role'           => 'assisting',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    // ─── treatment_notes ─────────────────────────────────────────────────────

    public function test_appointment_treatment_notes_column_exists_and_is_nullable(): void
    {
        $appt = $this->makeAppointmentWithStaff();
        $this->assertNull($appt->fresh()->treatment_notes);
    }

    public function test_appointment_treatment_notes_can_be_saved_without_touching_notes(): void
    {
        $appt = $this->makeAppointmentWithStaff();
        $appt->update(['notes' => 'Operational note.']);
        $originalNotes = $appt->fresh()->notes;

        $appt->update(['treatment_notes' => 'Person 1: sensitive skin.']);

        $fresh = $appt->fresh();
        $this->assertEquals('Person 1: sensitive skin.', $fresh->treatment_notes);
        $this->assertEquals($originalNotes, $fresh->notes, 'operational notes must not change');
    }

    // ─── Cascading Deletes ────────────────────────────────────────────────────

    public function test_deleting_room_category_cascades_pivot(): void
    {
        $room = Room::create(['name' => 'Temp Room', 'status' => 'available', 'is_active' => true]);
        $cat  = RoomCategory::create(['name' => 'Temp Cat', 'code' => 'temp']);
        $room->roomCategories()->attach($cat->id);

        $this->assertDatabaseHas('room_category_room', ['room_id' => $room->id]);

        $cat->delete();

        $this->assertDatabaseMissing('room_category_room', [
            'room_id'          => $room->id,
            'room_category_id' => $cat->id,
        ]);
    }

    // ─── Phase 1.1 Corrective & Invariant Tests ────────────────────────────────

    public function test_service_room_category_foreign_key_references_room_categories(): void
    {
        $roomCat = RoomCategory::create(['name' => 'VIP Suite', 'code' => 'vip_suite']);
        $cat = $this->makeServiceCategory();

        $service = Service::create([
            'name'             => 'VIP Treatment',
            'category_id'      => $cat->id,
            'duration_minutes' => 90,
            'price'            => 1500,
            'is_active'        => true,
            'requires_room'    => true,
            'room_category_id' => $roomCat->id,
        ]);

        $this->assertEquals($roomCat->id, $service->room_category_id);
        $this->assertEquals('vip_suite', $service->roomCategory->code);

        // Deleting room_category should nullify room_category_id on services
        $roomCat->delete();
        $this->assertNull($service->fresh()->room_category_id);
    }

    public function test_service_participants_required_rejects_zero_and_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $cat = $this->makeServiceCategory();
        Service::create([
            'name'                  => 'Invalid Service',
            'category_id'           => $cat->id,
            'duration_minutes'      => 60,
            'price'                 => 500,
            'is_active'             => true,
            'requires_room'         => false,
            'participants_required' => 0,
        ]);
    }

    public function test_service_therapists_required_rejects_zero_and_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $cat = $this->makeServiceCategory();
        Service::create([
            'name'                 => 'Invalid Service 2',
            'category_id'          => $cat->id,
            'duration_minutes'     => 60,
            'price'                => 500,
            'is_active'            => true,
            'requires_room'        => false,
            'therapists_required'  => -2,
        ]);
    }

    public function test_appointment_sync_staff_enforces_exactly_one_primary_therapist(): void
    {
        $appt       = $this->makeAppointmentWithStaff();
        $primary    = $this->makeUser('staff');
        $assisting1 = $this->makeUser('staff');
        $assisting2 = $this->makeUser('staff');

        $appt->syncStaff($primary->id, [$assisting1->id, $assisting2->id]);

        $fresh = $appt->fresh();

        // Exactly one primary therapist
        $this->assertCount(1, $fresh->primaryTherapist);
        $this->assertEquals($primary->id, $fresh->primaryTherapist->first()->id);

        // Legacy appointments.user_id synchronized with primary therapist
        $this->assertEquals($primary->id, $fresh->user_id);

        // Two assisting therapists
        $this->assertCount(2, $fresh->assistingTherapists);
        $this->assertTrue($fresh->assistingTherapists->contains('id', $assisting1->id));
        $this->assertTrue($fresh->assistingTherapists->contains('id', $assisting2->id));
    }

    public function test_appointment_sync_staff_rejects_therapist_as_both_primary_and_assisting(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $appt    = $this->makeAppointmentWithStaff();
        $primary = $this->makeUser('staff');

        // Cannot pass same user as primary and assisting
        $appt->syncStaff($primary->id, [$primary->id]);
    }

    public function test_historical_assignment_backfill_integrity(): void
    {
        $appt = $this->makeAppointmentWithStaff();

        // Verify primary pivot role
        $pivot = \Illuminate\Support\Facades\DB::table('appointment_staff')
            ->where('appointment_id', $appt->id)
            ->where('user_id', $appt->user_id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertEquals('primary', $pivot->role);

        // Verify no second primary for this appointment
        $primaryCount = \Illuminate\Support\Facades\DB::table('appointment_staff')
            ->where('appointment_id', $appt->id)
            ->where('role', 'primary')
            ->count();

        $this->assertEquals(1, $primaryCount);
    }
}
