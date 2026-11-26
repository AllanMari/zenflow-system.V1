<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Customer-facing 15-minute late-arrival policy banner in the booking wizard.
 * The notice is a permanent, non-dismissible banner above all wizard steps.
 */
class CustomerLatePolicyVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const HEADING = '15-Minute Late-Arrival Policy';

    private const WORDING = 'Please arrive on time for your scheduled appointment. '
        . 'Customers who have not checked in within 15 minutes after their scheduled appointment time '
        . 'may have their appointment cancelled in accordance with Spa Alexandria\'s late-arrival policy.';

    public function test_guest_booking_flow_shows_prominent_policy_before_steps(): void
    {
        $content = $this->get(route('booking.wizard'))
            ->assertOk()
            ->assertSee(self::HEADING)
            ->assertSee(self::WORDING, false)
            ->assertSee('Online requests remain pending until confirmed.')
            ->assertSee('A receptionist may contact you using the mobile number provided.')
            ->getContent();

        $this->assertSame(1, substr_count($content, self::HEADING));
        $this->assertSame(1, substr_count($content, 'Please arrive on time for your scheduled appointment.'));
        $this->assertLessThan(strpos($content, 'STEP INDICATOR'), strpos($content, self::HEADING));
        $this->assertStringNotContainsString('automatically cancelled', $content);
        $this->assertStringNotContainsString('marked as no-show', $content);
    }

    public function test_registered_customer_booking_flow_shows_same_banner(): void
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
        $this->assertLessThan(strpos($content, 'STEP INDICATOR'), strpos($content, self::HEADING));
    }

    public function test_policy_is_not_duplicated_in_final_review_panel(): void
    {
        $content = $this->get(route('booking.wizard'))->assertOk()->getContent();

        $beforeYouBook = strpos($content, 'Before you book');
        $sectionEnd = strpos($content, '</aside>', $beforeYouBook);
        $reviewPanel = substr($content, $beforeYouBook, $sectionEnd - $beforeYouBook);

        $this->assertStringNotContainsString(self::HEADING, $reviewPanel);
        $this->assertStringNotContainsString('Please arrive on time for your scheduled appointment.', $reviewPanel);
    }
}
