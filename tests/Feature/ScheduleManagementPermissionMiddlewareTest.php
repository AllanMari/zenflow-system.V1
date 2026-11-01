<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleManagementPermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private User $permittedReceptionist;
    private User $unpermittedReceptionist;
    private User $admin;
    private User $staff;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        $this->permittedReceptionist = User::factory()->create(['can_manage_schedules' => true]);
        $this->permittedReceptionist->roles()->attach($receptionistRole);

        $this->unpermittedReceptionist = User::factory()->create(['can_manage_schedules' => false]);
        $this->unpermittedReceptionist->roles()->attach($receptionistRole);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->staff = User::factory()->create();
        $this->staff->roles()->attach($staffRole);

        $this->customer = User::factory()->create();
        $this->customer->roles()->attach($customerRole);
    }

    public function test_can_manage_schedules_alias_resolves_and_permitted_receptionist_is_allowed()
    {
        // Hits route 'receptionist.schedules.block', which uses ['auth', 'role:receptionist', 'can_manage_schedules']
        $response = $this->actingAs($this->permittedReceptionist)
            ->post(route('receptionist.schedules.block'), []);

        // Does NOT throw missing middleware class error (500). Instead reaches controller and validates/handles (or 302 back).
        $this->assertNotEquals(500, $response->getStatusCode());
        $this->assertContains($response->getStatusCode(), [200, 302, 422]);
    }

    public function test_receptionist_without_permission_is_denied_with_403()
    {
        $response = $this->actingAs($this->unpermittedReceptionist)
            ->post(route('receptionist.schedules.block'), []);

        $response->assertStatus(403);
    }

    public function test_staff_role_is_denied()
    {
        $response = $this->actingAs($this->staff)
            ->post(route('receptionist.schedules.block'), []);

        // Blocked by role:receptionist middleware with 401
        $this->assertContains($response->getStatusCode(), [401, 403]);
    }

    public function test_customer_role_is_denied()
    {
        $response = $this->actingAs($this->customer)
            ->post(route('receptionist.schedules.block'), []);

        // Blocked by role:receptionist middleware with 401
        $this->assertContains($response->getStatusCode(), [401, 403]);
    }

    public function test_admin_access_on_receptionist_route_group_denied_by_existing_role_receptionist_middleware()
    {
        // Existing route definition: Route::middleware(['auth', 'role:receptionist', 'can_manage_schedules'])
        // Admin reaches 'role:receptionist', which denies with 403 since RoleMiddleware expects $user->hasRole('receptionist')
        $response = $this->actingAs($this->admin)
            ->post(route('receptionist.schedules.block'), []);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_middleware_unit_logic_directly()
    {
        $middleware = new \App\Http\Middleware\EnsureCanManageSchedules();

        $request = \Illuminate\Http\Request::create('/test', 'POST');

        // Admin passes
        $request->setUserResolver(fn() => $this->admin);
        $response = $middleware->handle($request, fn() => response('OK'));
        $this->assertEquals('OK', $response->getContent());

        // Permitted receptionist passes
        $request->setUserResolver(fn() => $this->permittedReceptionist);
        $response = $middleware->handle($request, fn() => response('OK'));
        $this->assertEquals('OK', $response->getContent());

        // Unpermitted receptionist aborted with 403
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $request->setUserResolver(fn() => $this->unpermittedReceptionist);
        $middleware->handle($request, fn() => response('OK'));
    }
}
