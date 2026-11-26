<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MedicalNotesEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_medical_and_treatment_notes_are_stored_as_ciphertext_and_decrypted_via_eloquent(): void
    {
        $medicalPlaintext = 'Severe lavender allergy and high blood pressure.';
        $treatmentPlaintext = 'Focus on upper back; avoid deep pressure on left shoulder.';

        $customer = Customer::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'phone_number' => '09171234567',
            'customer_type' => 'regular',
            'medical_notes' => $medicalPlaintext,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 1000.00,
            'treatment_notes' => $treatmentPlaintext,
        ]);

        // Eloquent reads return original plaintext
        $customer->refresh();
        $appointment->refresh();
        $this->assertSame($medicalPlaintext, $customer->medical_notes);
        $this->assertSame($treatmentPlaintext, $appointment->treatment_notes);

        // Raw DB reads return ciphertext, never plaintext
        $rawMedical = DB::table('customers')->where('id', $customer->id)->value('medical_notes');
        $rawTreatment = DB::table('appointments')->where('id', $appointment->id)->value('treatment_notes');

        $this->assertIsString($rawMedical);
        $this->assertIsString($rawTreatment);
        $this->assertNotSame($medicalPlaintext, $rawMedical);
        $this->assertNotSame($treatmentPlaintext, $rawTreatment);
        $this->assertStringNotContainsString('lavender', $rawMedical);
        $this->assertStringNotContainsString('shoulder', $rawTreatment);
        $this->assertSame($medicalPlaintext, Crypt::decryptString($rawMedical));
        $this->assertSame($treatmentPlaintext, Crypt::decryptString($rawTreatment));
    }

    public function test_null_and_empty_notes_are_handled_without_decryption_errors(): void
    {
        $customerNull = Customer::create([
            'first_name' => 'Null',
            'last_name' => 'Case',
            'phone_number' => '09170000001',
            'customer_type' => 'regular',
            'medical_notes' => null,
        ]);

        $appointmentNull = Appointment::create([
            'customer_id' => $customerNull->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => 'pending',
            'total_price' => 500.00,
            'treatment_notes' => null,
        ]);

        $this->assertNull($customerNull->fresh()->medical_notes);
        $this->assertNull($appointmentNull->fresh()->treatment_notes);
        $this->assertNull(DB::table('customers')->where('id', $customerNull->id)->value('medical_notes'));
        $this->assertNull(DB::table('appointments')->where('id', $appointmentNull->id)->value('treatment_notes'));
    }

    public function test_legacy_plaintext_conversion_is_idempotent_and_does_not_double_encrypt(): void
    {
        $customer = Customer::create([
            'first_name' => 'Legacy',
            'last_name' => 'Guest',
            'phone_number' => '09170000002',
            'customer_type' => 'regular',
            'medical_notes' => null,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'status' => 'confirmed',
            'total_price' => 1200.00,
            'treatment_notes' => null,
        ]);

        // Insert raw legacy plaintext bypassing Eloquent casts
        $legacyMedical = 'Legacy plaintext allergy note';
        $legacyTreatment = 'Legacy plaintext treatment note';

        DB::table('customers')->where('id', $customer->id)->update(['medical_notes' => $legacyMedical]);
        DB::table('appointments')->where('id', $appointment->id)->update(['treatment_notes' => $legacyTreatment]);

        // Dry run must not modify database
        $this->artisan('notes:encrypt-legacy', ['--dry-run' => true])
            ->assertExitCode(0);
        $this->assertSame($legacyMedical, DB::table('customers')->where('id', $customer->id)->value('medical_notes'));

        // Live conversion encrypts both rows
        $this->artisan('notes:encrypt-legacy')
            ->expectsOutputToContain('customers.medical_notes — processed: 1, encrypted: 1, skipped: 0, failed: 0')
            ->expectsOutputToContain('appointments.treatment_notes — processed: 1, encrypted: 1, skipped: 0, failed: 0')
            ->assertExitCode(0);

        $firstCipherMedical = DB::table('customers')->where('id', $customer->id)->value('medical_notes');
        $firstCipherTreatment = DB::table('appointments')->where('id', $appointment->id)->value('treatment_notes');

        $this->assertNotSame($legacyMedical, $firstCipherMedical);
        $this->assertNotSame($legacyTreatment, $firstCipherTreatment);
        $this->assertSame($legacyMedical, $customer->fresh()->medical_notes);
        $this->assertSame($legacyTreatment, $appointment->fresh()->treatment_notes);

        // Second run must skip both and NOT double-encrypt
        $this->artisan('notes:encrypt-legacy')
            ->expectsOutputToContain('customers.medical_notes — processed: 1, encrypted: 0, skipped: 1, failed: 0')
            ->expectsOutputToContain('appointments.treatment_notes — processed: 1, encrypted: 0, skipped: 1, failed: 0')
            ->assertExitCode(0);

        $this->assertSame($firstCipherMedical, DB::table('customers')->where('id', $customer->id)->value('medical_notes'));
        $this->assertSame($firstCipherTreatment, DB::table('appointments')->where('id', $appointment->id)->value('treatment_notes'));
        $this->assertSame($legacyMedical, $customer->fresh()->medical_notes);
        $this->assertSame($legacyTreatment, $appointment->fresh()->treatment_notes);
    }

    public function test_authorized_customer_and_receptionist_workflows_read_and_write_encrypted_notes(): void
    {
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);

        $customerUser = User::factory()->create(['first_name' => 'Elena', 'last_name' => 'Cruz']);
        $customerUser->roles()->attach($customerRole);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Elena',
            'last_name' => 'Cruz',
            'phone_number' => '09171112233',
            'customer_type' => 'regular',
        ]);

        // Unauthenticated user cannot update customer medical notes
        $this->put(route('customer.medical-notes.update'), ['medical_notes' => 'Should fail'])
            ->assertRedirect(route('login'));

        // Customer updates own medical notes
        $this->actingAs($customerUser)
            ->put(route('customer.medical-notes.update'), ['medical_notes' => 'Mild eczema on arms'])
            ->assertRedirect();

        $this->assertSame('Mild eczema on arms', $customer->fresh()->medical_notes);
        $raw = DB::table('customers')->where('id', $customer->id)->value('medical_notes');
        $this->assertStringNotContainsString('eczema', $raw);

        // Receptionist can view customer details with decrypted notes
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($receptionistRole);

        $this->actingAs($receptionist)
            ->getJson(route('receptionist.customers.show', $customer))
            ->assertOk()
            ->assertJsonPath('customer.medical_notes', 'Mild eczema on arms');

        // Customer cannot access receptionist customer management endpoint
        $this->actingAs($customerUser)
            ->getJson(route('receptionist.customers.show', $customer))
            ->assertStatus(403);
    }
}
