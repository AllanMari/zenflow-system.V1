<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class PdfReportDefault extends Model
{
    protected $fillable = [
        'report_key',
        'schema_version',
        'settings',
        'updated_by',
    ];

    protected $casts = [
        'schema_version' => 'integer',
        'settings' => 'array',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Safely retrieve saved system-wide defaults for a report key.
     * Falls back to null if the table does not exist or schema is incompatible.
     */
    public static function findSettingsForReport(string $reportKey): ?array
    {
        try {
            if (!Schema::hasTable('pdf_report_defaults')) {
                return null;
            }

            $record = static::query()->where('report_key', $reportKey)->first();
            if (!$record || !is_array($record->settings)) {
                return null;
            }

            $supportedVersion = (int) config('pdf.schema_version', 1);
            if ((int) $record->schema_version > $supportedVersion) {
                return null;
            }

            return $record->settings;
        } catch (\Throwable) {
            return null;
        }
    }
}
