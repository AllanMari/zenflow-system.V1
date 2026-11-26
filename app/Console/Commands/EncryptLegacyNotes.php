<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EncryptLegacyNotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notes:encrypt-legacy {--dry-run : Report counts without modifying records} {--chunk=100 : Number of rows to process per chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely encrypt legacy plaintext customers.medical_notes and appointments.treatment_notes in chunks.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $customerStats = $this->processTable('customers', 'medical_notes', $chunkSize, $dryRun);
        $appointmentStats = $this->processTable('appointments', 'treatment_notes', $chunkSize, $dryRun);

        $this->info(sprintf(
            'customers.medical_notes — processed: %d, encrypted: %d, skipped: %d, failed: %d',
            $customerStats['processed'],
            $customerStats['encrypted'],
            $customerStats['skipped'],
            $customerStats['failed']
        ));

        $this->info(sprintf(
            'appointments.treatment_notes — processed: %d, encrypted: %d, skipped: %d, failed: %d',
            $appointmentStats['processed'],
            $appointmentStats['encrypted'],
            $appointmentStats['skipped'],
            $appointmentStats['failed']
        ));

        $totalFailed = $customerStats['failed'] + $appointmentStats['failed'];

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{processed:int, encrypted:int, skipped:int, failed:int}
     */
    private function processTable(string $table, string $column, int $chunkSize, bool $dryRun): array
    {
        $stats = [
            'processed' => 0,
            'encrypted' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        DB::table($table)
            ->select(['id', $column])
            ->whereNotNull($column)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use ($table, $column, $dryRun, &$stats) {
                foreach ($rows as $row) {
                    $stats['processed']++;
                    $raw = $row->{$column};

                    if (!is_string($raw) || $raw === '') {
                        $stats['skipped']++;
                        continue;
                    }

                    if ($this->looksLikeEncryptedEnvelope($raw)) {
                        try {
                            Crypt::decryptString($raw);
                            $stats['skipped']++;
                        } catch (DecryptException) {
                            // Looks like an encrypted Laravel payload, but cannot be decrypted
                            // with the current APP_KEY. Never overwrite or double-encrypt it.
                            $stats['failed']++;
                            Log::warning('Legacy note conversion skipped undecryptable payload.', [
                                'table' => $table,
                                'column' => $column,
                                'record_id' => $row->id,
                            ]);
                        }
                        continue;
                    }

                    try {
                        $ciphertext = Crypt::encryptString($raw);
                        if (!$dryRun) {
                            DB::table($table)
                                ->where('id', $row->id)
                                ->update([$column => $ciphertext]);
                        }
                        $stats['encrypted']++;
                    } catch (Throwable $e) {
                        $stats['failed']++;
                        Log::error('Legacy note encryption failed for record.', [
                            'table' => $table,
                            'column' => $column,
                            'record_id' => $row->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $stats;
    }

    /**
     * Check whether a raw string matches Laravel's base64 JSON encryption envelope
     * without treating arbitrary plaintext as ciphertext.
     */
    private function looksLikeEncryptedEnvelope(string $value): bool
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false || $decoded === '') {
            return false;
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload)) {
            return false;
        }

        return isset($payload['iv'], $payload['value'], $payload['mac'])
            && is_string($payload['iv'])
            && is_string($payload['value'])
            && is_string($payload['mac']);
    }
}
