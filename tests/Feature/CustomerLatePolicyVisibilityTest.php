<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Customer-facing 15-minute late-arrival policy visibility in the booking
 * wizard (shared by guests and registered customers), before submission.
 */
class CustomerLatePolicyVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const HEADING = '15-Minute Late-Arrival Policy';

    private const WORDING = 'Please arrive on time for your scheduled appointment. '
        . 'Customers who have not checked in within 15 minutes after their scheduled appointment time '
        . 'may have their appointment cancelled in accordance with Spa Alexandria\'s late-arrival policy.';

    public function test_guest_booking_flow_shows_policy_once_before_submission(): void
    {
        $content = $this->get(route('booking.wizard'))
            ->assertOk()
            ->assertSee('Before you book')
            ->assertSee(self::HEADING)
            ->assertSee(self::WORDING, false)
            ->assertSee('Online requests remain pending until confirmed.')
            ->assertSee('A receptionist may contact you using the mobile number provided.')
            ->getContent();

        $this->assertSame(1, substr_count($content, self::HEADING));
        $this->assertSame(1, substr_count($content, 'Customers who have not checked in within 15 minutes'));
        $this->assertStringNotContainsString('automatically cancelled', $content);
        $this->assertStringNotContainsString('marked as no-show', $content);
        $this->assertStringNotContainsString('mandatory acknowledgment', $content);
    }

    public function test_registered_customer_booking_flow_shows_same_policy(): void
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Cora',
            'last_name' => 'Customer',
            'email' => $user->email,
            'phone_number' => '09171234567',
        ]);

        $content = $this->actingAs($user)
            ->get(route('booking.wizard'))
            ->assertOk()
            ->assertSee(self::HEADING)
            ->assertSee(self::WORDING, false)
            ->getContent();

        $this->assertSame(1, substr_count($content, self::HEADING));
    }
}
