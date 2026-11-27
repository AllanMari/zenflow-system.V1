<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BirInvoiceNumberTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function makeAppointment(string $status = 'confirmed', float $price = 1000.00): Appointment
    {
        $customerUser = $this->makeUserWithRole('customer');
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Elena',
            'last_name' => 'Reyes',
            'phone_number' => '09171234567',
            'customer_type' => 'regular',
        ]);

        $category = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);
        $service = Service::create([
            'category_id' => $category->id,
            'name' => 'Swedish Massage',
            'duration_minutes' => 60,
            'price' => $price,
            'requires_room' => false,
            'requires_prepayment' => false,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today('Asia/Manila')->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => $status,
            'total_price' => $price,
        ]);

        $appointment->services()->attach($service->id, [
            'price_at_booking' => $price,
            'service_name' => 'Swedish Massage',
            'service_duration' => 60,
        ]);

        return $appointment;
    }

    public function test_recording_bir_invoice_number_during_payment_completion_preserves_leading_zeros(): void
    {
        $receptionist = $this->makeUserWithRole('receptionist');
        $appointment = $this->makeAppointment('confirmed', 1000.00);

        $response = $this->actingAs($receptionist)->post(route('receptionist.complete', $appointment), [
            'payment_method' => 'cash',
            'payment_type' => 'full',
            'bir_invoice_number' => '0000125',
        ]);

        $response->assertRedirect(route('receptionist.active'));
        $appointment->refresh();

        $this->assertSame('completed', $appointment->status);
        $this->assertSame('0000125', $appointment->bir_invoice_number);
        $this->assertSame($receptionist->id, $appointment->bir_invoice_recorded_by);
        $this->assertNotNull($appointment->bir_invoice_recorded_at);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $receptionist->id,
            'action' => 'bir_invoice_recorded',
            'entity_type' => Appointment::class,
            'entity_id' => $appointment->id,
        ]);
    }

    public function test_completing_payment_without_bir_invoice_number_allows_multiple_nulls(): void
    {
        $receptionist = $this->makeUserWithRole('receptionist');
        $appt1 = $this->makeAppointment('confirmed', 800.00);
        $appt2 = $this->makeAppointment('confirmed', 900.00);

        $this->actingAs($receptionist)->post(route('receptionist.complete', $appt1), [
            'payment_method' => 'cash',
            'payment_type' => 'full',
            'bir_invoice_number' => '   ',
        ])->assertRedirect(route('receptionist.active'));

        $this->actingAs($receptionist)->post(route('receptionist.complete', $appt2), [
            'payment_method' => 'gcash',
            'payment_type' => 'full',
        ])->assertRedirect(route('receptionist.active'));

        $this->assertNull($appt1->fresh()->bir_invoice_number);
        $this->assertNull($appt2->fresh()->bir_invoice_number);
    }

    public function test_recording_and_updating_bir_invoice_number_after_completion_with_overwrite_guard(): void
    {
        $receptionist = $this->makeUserWithRole('receptionist');
        $admin = $this->makeUserWithRole('admin');
        $appointment = $this->makeAppointment('completed', 1000.00);

        // Initial post-completion recording by receptionist
        $this->actingAs($receptionist)
            ->post(route('receptionist.invoice.bir-invoice', $appointment), [
                'bir_invoice_number' => '0000201',
            ])
            ->assertRedirect();

        $appointment->refresh();
        $this->assertSame('0000201', $appointment->bir_invoice_number);
        $this->assertSame($receptionist->id, $appointment->bir_invoice_recorded_by);

        // Silent overwrite without confirm_overwrite is rejected
        $this->actingAs($admin)
            ->post(route('admin.invoice.bir-invoice', $appointment), [
                'bir_invoice_number' => '0000202',
            ])
            ->assertSessionHasErrors('bir_invoice_number');

        $this->assertSame('0000201', $appointment->fresh()->bir_invoice_number);

        // Explicit overwrite with confirm_overwrite succeeds and logs update
        $this->actingAs($admin)
            ->post(route('admin.invoice.bir-invoice', $appointment), [
                'bir_invoice_number' => '0000202',
                'confirm_overwrite' => '1',
            ])
            ->assertRedirect();

        $appointment->refresh();
        $this->assertSame('0000202', $appointment->bir_invoice_number);
        $this->assertSame($admin->id, $appointment->bir_invoice_recorded_by);

        $log = ActivityLog::where('entity_id', $appointment->id)
            ->where('action', 'bir_invoice_updated')
            ->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('0000201', $log->details);
        $this->assertStringContainsString('0000202', $log->details);
    }

    public function test_duplicate_bir_invoice_number_is_rejected_during_and_after_completion(): void
    {
        $receptionist = $this->makeUserWithRole('receptionist');
        $existing = $this->makeAppointment('completed', 1000.00);
        $existing->update([
            'bir_invoice_number' => '0000999',
            'bir_invoice_recorded_by' => $receptionist->id,
            'bir_invoice_recorded_at' => now(),
        ]);

        $pendingComplete = $this->makeAppointment('confirmed', 1000.00);
        $this->actingAs($receptionist)
            ->post(route('receptionist.complete', $pendingComplete), [
                'payment_method' => 'cash',
                'payment_type' => 'full',
                'bir_invoice_number' => '0000999',
            ])
            ->assertSessionHasErrors('bir_invoice_number');
        $this->assertSame('confirmed', $pendingComplete->fresh()->status);

        $otherCompleted = $this->makeAppointment('completed', 1000.00);
        $this->actingAs($receptionist)
            ->post(route('receptionist.invoice.bir-invoice', $otherCompleted), [
                'bir_invoice_number' => '0000999',
            ])
            ->assertSessionHasErrors('bir_invoice_number');
        $this->assertNull($otherCompleted->fresh()->bir_invoice_number);
    }

    public function test_unauthorized_users_cannot_record_bir_invoice_number(): void
    {
        $customer = $this->makeUserWithRole('customer');
        $staff = $this->makeUserWithRole('staff');
        $appointment = $this->makeAppointment('completed', 1000.00);

        $this->post(route('receptionist.invoice.bir-invoice', $appointment), [
            'bir_invoice_number' => '0000333',
        ])->assertRedirect(route('login'));

        $this->actingAs($customer)
            ->post(route('receptionist.invoice.bir-invoice', $appointment), [
                'bir_invoice_number' => '0000333',
            ])
            ->assertStatus(403);

        $this->actingAs($staff)
            ->post(route('admin.invoice.bir-invoice', $appointment), [
                'bir_invoice_number' => '0000333',
            ])
            ->assertStatus(403);

        $this->assertNull($appointment->fresh()->bir_invoice_number);
    }

    public function test_invoice_view_and_pdf_display_internal_reference_and_bir_invoice_number(): void
    {
        $receptionist = $this->makeUserWithRole('receptionist');
        $recorded = $this->makeAppointment('completed', 1000.00);
        $recorded->update(['bir_invoice_number' => '0000456']);

        $unrecorded = $this->makeAppointment('completed', 1000.00);

        $this->actingAs($receptionist)
            ->get(route('receptionist.invoice', $recorded))
            ->assertOk()
            ->assertSee('Appointment and Payment Summary')
            ->assertSee('Internal Transaction Reference')
            ->assertSee('#SPA-' . $recorded->id)
            ->assertSee('BIR Invoice Number')
            ->assertSee('0000456');

        $this->actingAs($receptionist)
            ->get(route('receptionist.invoice', $unrecorded))
            ->assertOk()
            ->assertSee('#SPA-' . $unrecorded->id)
            ->assertSee('Not Recorded');

        $pdfHtml = view('reports.appointment-invoice-pdf', [
            'appointment' => $recorded->fresh(['customer', 'staff', 'assignedStaff', 'room', 'services', 'payments']),
            'referenceNumber' => '#SPA-' . $recorded->id,
            'birInvoiceNumber' => $recorded->bir_invoice_number,
            'total' => 1000.00,
            'totalPaid' => 1000.00,
            'totalRefunded' => 0.0,
            'netPaid' => 1000.00,
            'balance' => 0.0,
            'paymentMethods' => collect(['cash']),
            'generatedAt' => now('Asia/Manila'),
        ])->render();

        $this->assertStringContainsString('Appointment and Payment Summary', $pdfHtml);
        $this->assertStringContainsString('Internal Transaction Reference', $pdfHtml);
        $this->assertStringContainsString('0000456', $pdfHtml);
        $this->assertStringContainsString('It is not an official invoice or receipt issued under BIR regulations.', $pdfHtml);

        $pdfRes = $this->actingAs($receptionist)
            ->get(route('receptionist.invoice.preview', $recorded));
        $pdfRes->assertOk();
        $this->assertStringStartsWith('%PDF', $pdfRes->getContent());
    }

    public function test_transaction_log_displays_bir_invoice_number_and_handles_nulls(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $today = Carbon::today('Asia/Manila')->toDateString();

        $recorded = $this->makeAppointment('completed', 1000.00);
        $recorded->update(['bir_invoice_number' => '0000777']);
        Payment::create([
            'appointment_id' => $recorded->id,
            'amount' => 1000.00,
            'type' => 'full',
            'payment_method' => 'cash',
            'paid_at' => Carbon::today('Asia/Manila'),
        ]);

        $unrecorded = $this->makeAppointment('completed', 500.00);
        Payment::create([
            'appointment_id' => $unrecorded->id,
            'amount' => 500.00,
            'type' => 'full',
            'payment_method' => 'cash',
            'paid_at' => Carbon::today('Asia/Manila'),
        ]);

        $res = $this->actingAs($admin)->get(route('admin.sales.tx-log', [
            'period' => 'daily',
            'start_date' => $today,
            'end_date' => $today,
            'status' => 'completed',
        ]));

        $res->assertOk()
            ->assertSee('#SPA-' . $recorded->id)
            ->assertSee('0000777')
            ->assertSee('#SPA-' . $unrecorded->id)
            ->assertSee('Not Recorded');
    }

    public function test_senior_pwd_discount_and_payment_calculations_remain_unchanged_with_bir_invoice(): void
    {
        $receptionist = $this->makeUserWithRole('receptionist');
        $appointment = $this->makeAppointment('confirmed', 1000.00);

        $this->actingAs($receptionist)->post(route('receptionist.complete', $appointment), [
            'payment_method' => 'cash',
            'payment_type' => 'full',
            'discount_type' => 'senior_citizen',
            'discount_verified' => '1',
            'bir_invoice_number' => '0000888',
        ])->assertRedirect(route('receptionist.active'));

        $appointment->refresh();
        $this->assertSame('completed', $appointment->status);
        $this->assertEquals(1000.00, (float) $appointment->original_total_price);
        $this->assertEquals(200.00, (float) $appointment->discount_amount);
        $this->assertEquals(800.00, (float) $appointment->total_price);
        $this->assertSame('0000888', $appointment->bir_invoice_number);

        $payment = Payment::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(800.00, (float) $payment->amount);
    }
}
