<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\TherapistAssignmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TherapistGenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $receptionist;
    private Role $staffRole;
    private Role $receptionistRole;
    private Customer $customer;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $this->staffRole = Role::firstOrCreate(['name' => 'staff']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        $this->admin = User::factory()->create([
            'password' => Hash::make('AdminPass123!'),
            'is_active' => true,
        ]);
        $this->admin->roles()->attach($adminRole);

        $this->receptionist = User::factory()->create([
            'password' => Hash::make('RecepPass123!'),
            'is_active' => true,
        ]);
        $this->receptionist->roles()->attach($this->receptionistRole);

        $custUser = User::factory()->create(['is_active' => true]);
        $custUser->roles()->attach($customerRole);

        $this->customer = Customer::create([
            'user_id' => $custUser->id,
            'first_name' => 'Maya',
            'last_name' => 'Cruz',
            'phone_number' => '09171112222',
            'customer_type' => 'regular',
        ]);

        $category = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);
        $this->service = Service::create([
            'category_id' => $category->id,
            'name' => 'Swedish Massage',
            'duration_minutes' => 60,
            'price' => 800.00,
            'requires_room' => false,
            'requires_prepayment' => false,
            'therapists_required' => 1,
            'is_active' => true,
        ]);
    }

    private function makeStaff(?string $gender, int $dayOfWeek, bool $isActive = true, string $firstName = 'Alex'): User
    {
        $staff = User::factory()->create([
            'first_name' => $firstName,
            'last_name' => 'Therapist',
            'gender' => $gender,
            'is_active' => $isActive,
        ]);
        $staff->roles()->attach($this->staffRole);

        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'is_day_off' => false,
        ]);

        return $staff;
    }

    public function test_admin_can_create_staff_with_gender(): void
    {
        $res = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'username' => 'therapist_elena',
            'first_name' => 'Elena',
            'last_name' => 'Reyes',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
            'role' => 'staff',
            'gender' => 'female',
        ]);

        $res->assertRedirect(route('admin.users.index'));
        $created = User::where('username', 'therapist_elena')->firstOrFail();
        $this->assertSame('female', $created->gender);
    }

    public function test_gender_is_required_when_creating_staff(): void
    {
        $res = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'username' => 'therapist_nogender',
            'first_name' => 'NoGender',
            'last_name' => 'Staff',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
            'role' => 'staff',
        ]);

        $res->assertSessionHasErrors('gender');
        $this->assertDatabaseMissing('users', ['username' => 'therapist_nogender']);
    }

    public function test_gender_is_not_saved_for_non_staff_roles(): void
    {
        $res = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'username' => 'recep_marco',
            'first_name' => 'Marco',
            'last_name' => 'Santos',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
            'role' => 'receptionist',
            'gender' => 'male',
        ]);

        $res->assertRedirect(route('admin.users.index'));
        $created = User::where('username', 'recep_marco')->firstOrFail();
        $this->assertNull($created->gender);
    }

    public function test_non_admin_cannot_create_or_update_users(): void
    {
        $staff = $this->makeStaff(null, Carbon::now('Asia/Manila')->dayOfWeek);

        $this->actingAs($this->receptionist)->post(route('admin.users.store'), [
            'username' => 'unauth_staff',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
            'role' => 'staff',
            'gender' => 'male',
        ])->assertForbidden();

        $this->actingAs($this->receptionist)->put(route('admin.users.update', $staff), [
            'username' => $staff->username,
            'first_name' => $staff->first_name,
            'last_name' => $staff->last_name,
            'role' => 'staff',
            'gender' => 'female',
            'admin_password' => 'RecepPass123!',
        ])->assertForbidden();
    }

    public function test_admin_can_update_staff_gender_and_omitting_preserves_existing(): void
    {
        $staff = $this->makeStaff(null, Carbon::now('Asia/Manila')->dayOfWeek);

        // Set gender to male
        $this->actingAs($this->admin)->put(route('admin.users.update', $staff), [
            'username' => $staff->username,
            'first_name' => $staff->first_name,
            'last_name' => $staff->last_name,
            'role' => 'staff',
            'gender' => 'male',
            'admin_password' => 'AdminPass123!',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame('male', $staff->fresh()->gender);

        // Update first_name while leaving gender empty ("Unchanged") -> preserves 'male'
        $this->actingAs($this->admin)->put(route('admin.users.update', $staff), [
            'username' => $staff->username,
            'first_name' => 'UpdatedName',
            'last_name' => $staff->last_name,
            'role' => 'staff',
            'gender' => '',
            'admin_password' => 'AdminPass123!',
        ])->assertRedirect(route('admin.users.index'));

        $fresh = $staff->fresh();
        $this->assertSame('UpdatedName', $fresh->first_name);
        $this->assertSame('male', $fresh->gender);
    }

    public function test_admin_users_index_displays_compact_gender_labels_for_staff(): void
    {
        $dow = Carbon::now('Asia/Manila')->dayOfWeek;
        $this->makeStaff('male', $dow, true, 'Carlo');
        $this->makeStaff('female', $dow, true, 'Bea');
        $this->makeStaff(null, $dow, true, 'Legacy');

        $res = $this->actingAs($this->admin)->get(route('admin.users.index'));
        $res->assertOk()
            ->assertSee('Male')
            ->assertSee('Female')
            ->assertSee('Not Set');
    }

    public function test_preferred_therapists_api_returns_gender_for_active_staff_only(): void
    {
        $future = Carbon::now('Asia/Manila')->addDays(2);
        $male = $this->makeStaff('male', $future->dayOfWeek, true, 'Carlo');
        $female = $this->makeStaff('female', $future->dayOfWeek, true, 'Bea');
        $unset = $this->makeStaff(null, $future->dayOfWeek, true, 'Legacy');
        $inactive = $this->makeStaff('male', $future->dayOfWeek, false, 'Inactive');

        $res = $this->getJson(route('api.booking.preferred-therapists', [
            'date' => $future->toDateString(),
            'start_time' => '10:00',
            'services' => [$this->service->id],
        ]));

        $res->assertOk();
        $therapists = collect($res->json('therapists'))->keyBy('id');

        $this->assertSame('male', $therapists[$male->id]['gender']);
        $this->assertSame('female', $therapists[$female->id]['gender']);
        $this->assertNull($therapists[$unset->id]['gender']);
        $this->assertFalse($therapists->has($inactive->id));
    }

    public function test_booking_wizard_includes_gender_grouping_labels(): void
    {
        $res = $this->get(route('booking.wizard'));
        $res->assertOk()
            ->assertSee('Male Therapists')
            ->assertSee('Female Therapists')
            ->assertSee('Not Specified')
            ->assertSee('preferredTherapistGroups');
    }

    public function test_gender_does_not_affect_availability_or_preferred_holds(): void
    {
        $future = Carbon::now('Asia/Manila')->addDays(2);
        $male = $this->makeStaff('male', $future->dayOfWeek, true, 'Carlo');
        $female = $this->makeStaff('female', $future->dayOfWeek, true, 'Bea');
        $unset = $this->makeStaff(null, $future->dayOfWeek, true, 'Legacy');

        // Identical schedules -> identical availability regardless of gender
        $availList = collect(TherapistAssignmentService::customerAvailability(
            $future->toDateString(),
            '10:00',
            60
        ))->keyBy('id');

        $this->assertSame('available', $availList[$male->id]['status']);
        $this->assertSame('available', $availList[$female->id]['status']);
        $this->assertSame('available', $availList[$unset->id]['status']);

        // Pending appointment holding the male therapist blocks overlapping slot for that therapist
        $holding = Appointment::create([
            'customer_id' => $this->customer->id,
            'appointment_date' => $future->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 800.00,
        ]);
        $holding->services()->attach($this->service->id, [
            'price_at_booking' => 800.00,
            'service_name' => $this->service->name,
            'service_duration' => 60,
        ]);
        $holding->preferredStaff()->attach($male->id);

        $afterHold = collect(TherapistAssignmentService::customerAvailability(
            $future->toDateString(),
            '10:30',
            60
        ))->keyBy('id');

        $this->assertSame('occupied', $afterHold[$male->id]['status']);
        $this->assertSame('available', $afterHold[$female->id]['status']);
        $this->assertSame('available', $afterHold[$unset->id]['status']);
    }
}
