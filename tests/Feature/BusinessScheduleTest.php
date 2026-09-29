<?php

namespace Tests\Feature;

use App\Models\BusinessException;
use App\Models\BusinessHour;
use App\Models\Role;
use App\Models\User;
use App\Services\BusinessScheduleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $receptionist;
    protected User $authReceptionist;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'admin']);
        $receptionistRole = Role::create(['name' => 'receptionist']);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->receptionist = User::factory()->create(['can_manage_business_hours' => false]);
        $this->receptionist->roles()->attach($receptionistRole);

        $this->authReceptionist = User::factory()->create(['can_manage_business_hours' => true]);
        $this->authReceptionist->roles()->attach($receptionistRole);
    }

    public function test_admin_can_view_business_hours_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('business-hours.index'));
        $response->assertStatus(200);
        $response->assertSee('Business Hours');
    }

    public function test_authorized_receptionist_can_view_business_hours_page(): void
    {
        $response = $this->actingAs($this->authReceptionist)->get(route('business-hours.index'));
        $response->assertStatus(200);
    }

    public function test_unauthorized_receptionist_cannot_view_business_hours_page(): void
    {
        $response = $this->actingAs($this->receptionist)->get(route('business-hours.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_update_weekly_hours(): void
    {
        $hoursData = [];
        for ($i = 0; $i <= 6; $i++) {
            $hoursData[$i] = [
                'open_time' => '10:00',
                'close_time' => '19:00',
                'is_closed' => ($i === 0) ? '1' : '0', // Close on Sunday
            ];
        }

        $response = $this->actingAs($this->admin)->post(route('business-hours.update'), [
            'hours' => $hoursData,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('business_hours', [
            'day_of_week' => 0,
            'is_closed' => 1,
        ]);
        $this->assertDatabaseHas('business_hours', [
            'day_of_week' => 1,
            'open_time' => '10:00',
            'close_time' => '19:00',
            'is_closed' => 0,
        ]);
    }

    public function test_admin_can_add_and_delete_business_exception(): void
    {
        $targetDate = now()->addDays(5)->format('Y-m-d');

        $response = $this->actingAs($this->admin)->post(route('business-exceptions.store'), [
            'date' => $targetDate,
            'title' => 'Holiday Closure',
            'type' => 'holiday',
            'is_closed' => '1',
            'notice_message' => 'Closed for holiday celebration.',
        ]);

        $response->assertRedirect();
        $exception = BusinessException::where('title', 'Holiday Closure')->first();
        $this->assertNotNull($exception);
        $this->assertTrue($exception->is_closed);
        $this->assertEquals('Closed for holiday celebration.', $exception->notice_message);

        $deleteResponse = $this->actingAs($this->admin)->delete(route('business-exceptions.destroy', $exception));
        $deleteResponse->assertRedirect();

        $this->assertDatabaseMissing('business_exceptions', [
            'id' => $exception->id,
        ]);
    }

    public function test_business_schedule_service_window_resolution(): void
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');

        BusinessException::create([
            'date' => $targetDate,
            'title' => 'Special Maintenance Day',
            'type' => 'event',
            'is_closed' => true,
            'notice_message' => 'Closed for maintenance.',
        ]);

        $window = BusinessScheduleService::getOperatingWindow($targetDate);
        $this->assertFalse($window['is_open']);
        $this->assertEquals('Special Maintenance Day', $window['reason']);
    }

    public function test_admin_can_toggle_receptionist_permission(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('admin.receptionist.toggle-business-hours', $this->receptionist)
        );

        $response->assertRedirect();
        $this->assertTrue($this->receptionist->fresh()->can_manage_business_hours);
    }

    public function test_landing_and_wizard_display_active_notice_banner(): void
    {
        $today = now('Asia/Manila')->format('Y-m-d');
        BusinessException::create([
            'date' => $today,
            'title' => 'Holiday Today',
            'type' => 'holiday',
            'is_closed' => true,
            'notice_message' => 'We are taking a special holiday break today!',
        ]);

        $landingRes = $this->get(route('landing'));
        $landingRes->assertStatus(200);
        $landingRes->assertSee('Holiday Today');
        $landingRes->assertSee('We are taking a special holiday break today!');

        $wizardRes = $this->get(route('booking.wizard'));
        $wizardRes->assertStatus(200);
        $wizardRes->assertSee('Holiday Today');
        $wizardRes->assertSee('We are taking a special holiday break today!');
    }
}

