<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $admin = User::firstOrCreate(
            ['username' => 'test_admin_room_report'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Test',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        $this->admin = $admin;
    }

    public function test_admin_can_generate_room_report_pdf_preview()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.rooms.report-pdf') . '?action=stream');

        $response->assertStatus(200);
        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'inline',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_admin_can_generate_room_report_pdf_download()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.rooms.report-pdf') . '?action=download');

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_receptionist_cannot_access_room_report_pdf()
    {
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist'], ['label' => 'Receptionist']);
        $receptionist = User::firstOrCreate(
            ['username' => 'test_rec_room_report'],
            [
                'first_name' => 'Rec',
                'last_name' => 'Test',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $receptionist->roles()->syncWithoutDetaching([$receptionistRole->id]);

        $this->actingAs($receptionist)
            ->get(route('admin.rooms.report-pdf') . '?action=stream')
            ->assertStatus(403);

        $this->actingAs($receptionist)
            ->get(route('admin.rooms.report-pdf') . '?action=download')
            ->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login_for_room_report_pdf()
    {
        $this->get(route('admin.rooms.report-pdf') . '?action=stream')
            ->assertRedirect(route('login'));
    }

    public function test_admin_rooms_index_contains_generate_report_action()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.rooms.index'));

        $response->assertStatus(200);
        $response->assertSee('Generate Report');
    }

    public function test_room_report_generation_does_not_modify_room_records()
    {
        $room = Room::create([
            'name' => 'Suite 1',
            'status' => 'available',
            'is_active' => true,
            'notes' => 'Needs deep cleaning before next session',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.rooms.report-pdf') . '?action=stream');

        $response->assertStatus(200);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'name' => 'Suite 1',
            'status' => 'available',
            'is_active' => true,
        ]);
        $this->assertEquals(1, Room::count());
    }
}
