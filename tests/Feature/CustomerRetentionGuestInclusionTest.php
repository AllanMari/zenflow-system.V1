<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\SalesAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CustomerRetentionGuestInclusionTest extends TestCase
{
    use RefreshDatabase;

    private User $therapist;

    private Customer $guestPlaceholder;

    protected function setUp(): void
    {
        parent::setUp();

        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['label' => 'Staff']);
        $this->therapist = User::factory()->create([
            'first_name' => 'Elena',
            'last_name' => 'Therapist',
            'is_active' => true,
        ]);
        $this->therapist->roles()->attach($staffRole);

        $this->guestPlaceholder = Customer::create([
            'first_name' => 'Shared',
            'last_name' => 'GuestProfile',
            'phone_number' => '09170000000',
        ]);
    }

    public function test_1_same_normalized_guest_phone_with_different_name_spelling_counts_as_one_returning_customer()
    {
        $this->createGuestAppointment('  Maria   Santos ', '09171234567', '2026-10-05');
        $this->createGuestAppointment('Mariah Santos', '09171234567', '2026-10-20');

        $data = $this->getRetentionForOctober();
        $ret = $data['customerRetention'];

        $this->assertEquals(1, $ret['customers_served']);
        $this->assertEquals(0, $ret['registered_customers']);
        $this->assertEquals(1, $ret['guest_customers']);
        $this->assertEquals(1, $ret['returning_customers']);
        $this->assertEquals(0, $ret['new_customers']);
        $this->assertEquals(1, $ret['repeat_visits']);
        $this->assertEquals(100.0, $ret['retention_rate']);
    }

    public function test_2_different_philippine_phone_formats_normalize_to_single_guest_identity()
    {
        $this->createGuestAppointment('Juan Dela Cruz', '+63 917-555-0199', '2026-10-03');
        $this->createGuestAppointment('Juan Dela Cruz', '0917 555 0199', '2026-10-12');
        $this->createGuestAppointment('Juan Dela Cruz', '639175550199', '2026-10-18');
        $this->createGuestAppointment('Juan Dela Cruz', '9175550199', '2026-10-22');

        $data = $this->getRetentionForOctober();
        $ret = $data['customerRetention'];

        $this->assertEquals(1, $ret['customers_served']);
        $this->assertEquals(1, $ret['guest_customers']);
        $this->assertEquals(1, $ret['returning_customers']);
        $this->assertEquals(0, $ret['new_customers']);
        $this->assertEquals(3, $ret['repeat_visits']);
    }

    public function test_3_same_name_with_different_phone_numbers_remain_separate()
    {
        $this->createGuestAppointment('Ana Reyes', '09171111111', '2026-10-05');
        $this->createGuestAppointment('Ana Reyes', '09172222222', '2026-10-15');

        $data = $this->getRetentionForOctober();
        $ret = $data['customerRetention'];

        $this->assertEquals(2, $ret['customers_served']);
        $this->assertEquals(2, $ret['guest_customers']);
        $this->assertEquals(0, $ret['returning_customers']);
        $this->assertEquals(2, $ret['new_customers']);
        $this->assertEquals(0.0, $ret['retention_rate']);
    }

    public function test_4_same_phone_number_groups_under_phone_primary_identity()
    {
        $this->createGuestAppointment('Carlos Mendoza', '09181234567', '2026-10-05');
        $this->createGuestAppointment('Lucia Mendoza', '09181234567', '2026-10-15');

        $data = $this->getRetentionForOctober();
        $ret = $data['customerRetention'];

        // Phone number is the primary customer identifier
        $this->assertEquals(1, $ret['customers_served']);
        $this->assertEquals(1, $ret['guest_customers']);
        $this->assertEquals(1, $ret['returning_customers']);
        $this->assertEquals(1, $ret['repeat_visits']);
    }

    public function test_5_registered_customers_continue_to_be_counted_correctly()
    {
        $c1 = Customer::create(['first_name' => 'Reg', 'last_name' => 'Returning', 'phone_number' => '09190000001']);
        $c2 = Customer::create(['first_name' => 'Reg', 'last_name' => 'New', 'phone_number' => '09190000002']);

        $this->createRegisteredAppointment($c1, '2026-09-10');
        $this->createRegisteredAppointment($c1, '2026-10-08');
        $this->createRegisteredAppointment($c2, '2026-10-14');

        $data = $this->getRetentionForOctober();
        $ret = $data['customerRetention'];

        $this->assertEquals(2, $ret['customers_served']);
        $this->assertEquals(2, $ret['registered_customers']);
        $this->assertEquals(0, $ret['guest_customers']);
        $this->assertEquals(1, $ret['returning_customers']);
        $this->assertEquals(1, $ret['new_customers']);
        $this->assertEquals(50.0, $ret['retention_rate']);
    }

    public function test_6_guest_booking_unambiguously_matching_registered_customer_does_not_double_count()
    {
        $user = User::factory()->create();
        $registered = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Sofia',
            'last_name' => 'Lim',
            'phone_number' => '09178889999',
        ]);

        // Prior visit as guest (even with a different customer_id placeholder), current visit as registered
        $this->createGuestAppointment('Sofia Lim', '+63 917 888 9999', '2026-09-15');
        $this->createRegisteredAppointment($registered, '2026-10-10');

        $data = $this->getRetentionForOctober();
        $ret = $data['customerRetention'];

        $this->assertEquals(1, $ret['customers_served']);
        $this->assertEquals(1, $ret['registered_customers']);
        $this->assertEquals(0, $ret['guest_customers']);
        $this->assertEquals(1, $ret['returning_customers']);
        $this->assertEquals(0, $ret['new_customers']);
        $this->assertEquals(100.0, $ret['retention_rate']);
    }

    public function test_7_ambiguous_registered_matches_are_not_automatically_merged()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $dupA = Customer::create(['user_id' => $userA->id, 'first_name' => 'Mark', 'last_name' => 'Tan', 'phone_number' => '09173334444']);
        $dupB = Customer::create(['user_id' => $userB->id, 'first_name' => 'Mark', 'last_name' => 'Tan', 'phone_number' => '09173334444']);

        $this->createRegisteredAppointment($dupA, '2026-10-05');
        $this->createGuestAppointment('Mark Tan', '09173334444', '2026-10-12');

        $data = $this->getRetentionForOctober();
        $ret = $data['customerRetention'];

        // Kept separate because 2 registered customers share the same name+phone
        $this->assertEquals(2, $ret['customers_served']);
        $this->assertEquals(1, $ret['registered_customers']);
        $this->assertEquals(1, $ret['guest_customers']);
        $this->assertEquals(0, $ret['returning_customers']);
        $this->assertEquals(2, $ret['new_customers']);
    }

    public function test_8_missing_or_unusable_guest_phone_is_reported_separately()
    {
        $this->createGuestAppointment('Jane Doe', '00000000000', '2026-10-06');
        $this->createGuestAppointment('John Smith', '', '2026-10-07');
        $this->createGuestAppointment('Short Phone', '12345', '2026-10-09');
        $this->createGuestAppointment('Valid Guest', '09176667777', '2026-10-08');

        $data = $this->getRetentionForOctober();
        $ret = $data['customerRetention'];

        $this->assertEquals(1, $ret['customers_served']);
        $this->assertEquals(1, $ret['guest_customers']);
        $this->assertEquals(3, $ret['unidentified_guest_bookings']);
        $this->assertEquals(1, $ret['new_customers']);
        $this->assertEquals(0, $ret['returning_customers']);
    }

    public function test_9_cancelled_no_show_and_pending_appointments_do_not_qualify()
    {
        $this->createGuestAppointment('Bea Gomez', '09174445555', '2026-10-05', 'completed');
        $this->createGuestAppointment('Bea Gomez', '09174445555', '2026-10-10', 'cancelled');
        $this->createGuestAppointment('Bea Gomez', '09174445555', '2026-10-12', 'no_show');
        $this->createGuestAppointment('Bea Gomez', '09174445555', '2026-10-15', 'pending');

        $data = $this->getRetentionForOctober();
        $ret = $data['customerRetention'];

        $this->assertEquals(1, $ret['customers_served']);
        $this->assertEquals(0, $ret['returning_customers']);
        $this->assertEquals(1, $ret['new_customers']);
        $this->assertEquals(0, $ret['repeat_visits']);
    }

    public function test_10_historical_completed_visits_outside_period_identify_returning_guests()
    {
        // Completed guest visit in September, one completed visit in October
        $this->createGuestAppointment('Clara Bautista', '09179990000', '2026-09-20', 'completed');
        $this->createGuestAppointment('Clara Bautista', '+639179990000', '2026-10-18', 'completed');

        $data = $this->getRetentionForOctober();
        $ret = $data['customerRetention'];
        $therapistRet = collect($data['therapistRetentionData'])->firstWhere('therapist_id', $this->therapist->id);

        $this->assertEquals(1, $ret['customers_served']);
        $this->assertEquals(1, $ret['guest_customers']);
        $this->assertEquals(1, $ret['returning_customers']);
        $this->assertEquals(0, $ret['new_customers']);
        $this->assertEquals(1, $ret['repeat_visits']);
        $this->assertEquals(100.0, $ret['retention_rate']);

        $this->assertNotNull($therapistRet);
        $this->assertEquals(1, $therapistRet->unique_clients);
        $this->assertEquals(1, $therapistRet->repeat_clients);
        $this->assertEquals(100.0, $therapistRet->retention_rate);
    }

    private function getRetentionForOctober(): array
    {
        $analytics = app(SalesAnalyticsService::class);

        return $analytics->getDashboardData(new Request([
            'period' => 'custom',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));
    }

    private function createGuestAppointment(
        string $name,
        string $phone,
        string $date,
        string $status = 'completed'
    ): Appointment {
        return Appointment::create([
            'customer_id' => $this->guestPlaceholder->id,
            'user_id' => $this->therapist->id,
            'guest_first_name' => $name,
            'guest_phone' => $phone,
            'appointment_date' => Carbon::parse($date)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => $status,
            'total_price' => 500,
        ]);
    }

    private function createRegisteredAppointment(
        Customer $customer,
        string $date,
        string $status = 'completed'
    ): Appointment {
        return Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $this->therapist->id,
            'appointment_date' => Carbon::parse($date)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'status' => $status,
            'total_price' => 500,
        ]);
    }
}
