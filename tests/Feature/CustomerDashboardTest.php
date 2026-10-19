<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomerUser(bool $withProfile = true): array
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $customer = $withProfile ? Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Taylor',
            'last_name' => 'Customer',
            'email' => $user->email,
            'phone_number' => '1234567890',
        ]) : null;

        return [$user, $customer];
    }

    private function createAppointment(Customer $customer, string $date, string $start, string $end, string $status): Appointment
    {
        return Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
            'total_price' => 100,
        ]);
    }

    public function test_dashboard_requires_customer_authorization(): void
    {
        $this->get(route('customer-dashboard'))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('customer-dashboard'))->assertUnauthorized();
    }

    public function test_dashboard_summarizes_only_own_appointments_and_selects_nearest_active_one(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-17 12:00:00', config('app.timezone')));
        try {
            [$user, $customer] = $this->createCustomerUser();
            [, $otherCustomer] = $this->createCustomerUser();
            $next = $this->createAppointment($customer, '2026-10-18', '10:00:00', '11:00:00', 'pending');
            $this->createAppointment($customer, '2026-10-20', '10:00:00', '11:00:00', 'confirmed');
            $this->createAppointment($customer, '2026-10-17', '09:00:00', '10:00:00', 'pending');
            $this->createAppointment($customer, '2026-10-19', '10:00:00', '11:00:00', 'completed');
            $this->createAppointment($customer, '2026-10-21', '10:00:00', '11:00:00', 'cancelled');
            $this->createAppointment($otherCustomer, '2026-10-18', '08:00:00', '09:00:00', 'pending');

            $response = $this->actingAs($user)->get(route('customer-dashboard'));
            $response->assertOk()
                ->assertViewHas('upcomingCount', 2)
                ->assertViewHas('pendingCount', 1)
                ->assertViewHas('completedCount', 1)
                ->assertViewHas('upcomingAppointments', function ($appointments) use ($next) {
                    return $appointments->first()?->id === $next->id;
                })
                ->assertSee('Sunday, October 18, 2026')
                ->assertSee('Awaiting confirmation')
                ->assertSee(route('customer.profile'))
                ->assertDontSee('Lifetime Spent');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_treats_an_appointment_ending_now_as_elapsed_and_shows_empty_state(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-17 12:00:00', config('app.timezone')));
        try {
            [$user, $customer] = $this->createCustomerUser();
            $this->createAppointment($customer, '2026-10-17', '11:00:00', '12:00:00', 'confirmed');

            $this->actingAs($user)->get(route('customer-dashboard'))
                ->assertOk()
                ->assertSee('No upcoming appointments')
                ->assertSee('Your first session starts with a booking.')
                ->assertSee(route('booking.wizard'))
                ->assertSee(route('customer.bookings'))
                ->assertDontSee('Lifetime Spent')
                ->assertDontSee('Happening now');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_handles_customer_without_profile(): void
    {
        [$user] = $this->createCustomerUser(false);

        $this->actingAs($user)->get(route('customer-dashboard'))
            ->assertOk()
            ->assertViewHas('upcomingCount', 0)
            ->assertViewHas('pendingCount', 0)
            ->assertViewHas('completedCount', 0)
            ->assertSee('No upcoming appointments')
            ->assertSee('Your first session starts with a booking.');
    }

    public function test_dashboard_uses_returning_empty_copy_when_completed_visits_exist(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-17 12:00:00', config('app.timezone')));
        try {
            [$user, $customer] = $this->createCustomerUser();
            $this->createAppointment($customer, '2026-10-10', '10:00:00', '11:00:00', 'completed');

            $this->actingAs($user)->get(route('customer-dashboard'))
                ->assertOk()
                ->assertViewHas('completedCount', 1)
                ->assertViewHas('upcomingCount', 0)
                ->assertSee('No upcoming appointments')
                ->assertSee('No visits on the calendar. Book when you are ready.')
                ->assertDontSee('Your first session starts with a booking.')
                ->assertDontSee('Lifetime Spent');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_highlights_an_in_progress_appointment_as_happening_now(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-17 12:00:00', config('app.timezone')));
        try {
            [$user, $customer] = $this->createCustomerUser();
            $this->createAppointment($customer, '2026-10-17', '11:00:00', '13:00:00', 'confirmed');

            $this->actingAs($user)->get(route('customer-dashboard'))
                ->assertOk()
                ->assertViewHas('upcomingCount', 1)
                ->assertSee('Happening now')
                ->assertSee('Confirmed')
                ->assertDontSee('Lifetime Spent');
        } finally {
            Carbon::setTestNow();
        }
    }
}
