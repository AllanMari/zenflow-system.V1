<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataImportService
{
    /**
     * Supported dataset types for import.
     */
    public const SUPPORTED_TYPES = ['attendance', 'offline_sales'];

    /**
     * In-memory cache for catalog services to enable case-insensitive & space-normalized lookups.
     */
    protected ?array $servicesCache = null;

    /**
     * In-memory cache for staff users to enable case-insensitive & space-normalized lookups.
     */
    protected ?array $staffCache = null;

    /**
     * Reset in-memory caches (useful in tests).
     */
    public function resetCaches(): void
    {
        $this->servicesCache = null;
        $this->staffCache = null;
    }

    /**
     * Get specifications, human-readable headers, and descriptions for all supported templates.
     */
    public function getTemplateDefinition(string $type): array
    {
        return match ($type) {
            'attendance' => [
                'type' => 'attendance',
                'name' => 'Staff Attendance Logs',
                'filename' => 'zenflow_attendance_template.csv',
                'columns' => [
                    [
                        'key' => 'staff_username',
                        'label' => 'Staff Username or Full Name',
                        'required' => true,
                        'hint' => 'Staff username (e.g. therapist_ana) or real full name (e.g. Ana Reyes)',
                    ],
                    [
                        'key' => 'date',
                        'label' => 'Attendance Date',
                        'required' => true,
                        'hint' => 'Date of attendance in Year-Month-Day format (e.g. 2026-11-19)',
                    ],
                    [
                        'key' => 'time_in',
                        'label' => 'Time In',
                        'required' => false,
                        'hint' => 'Clock-in time in 12-hour AM/PM format (e.g. 09:00 AM). Standard 24-hour time is also supported.',
                    ],
                    [
                        'key' => 'time_out',
                        'label' => 'Time Out',
                        'required' => false,
                        'hint' => 'Clock-out time in 12-hour AM/PM format (e.g. 06:00 PM). Standard 24-hour time is also supported.',
                    ],
                    [
                        'key' => 'status',
                        'label' => 'Status',
                        'required' => true,
                        'hint' => 'Must be: present, late, absent, on_leave, or holiday',
                    ],
                    [
                        'key' => 'notes',
                        'label' => 'Notes',
                        'required' => false,
                        'hint' => 'Optional remarks (e.g. reason for being late or sick leave)',
                    ],
                ],
                'headers' => [
                    'Staff Username or Full Name',
                    'Attendance Date',
                    'Time In',
                    'Time Out',
                    'Status',
                    'Notes',
                ],
                'canonical_columns' => [
                    'staff_username',
                    'date',
                    'time_in',
                    'time_out',
                    'status',
                    'notes',
                ],
                'required' => ['staff_username', 'date', 'status'],
                'samples' => [
                    [
                        'therapist_ana',
                        '2026-11-19',
                        '09:00 AM',
                        '06:00 PM',
                        'present',
                        'Morning shift completed on time.',
                    ],
                    [
                        'Ana Reyes',
                        '2026-11-20',
                        '09:15 AM',
                        '06:00 PM',
                        'late',
                        'Traffic delay; notified receptionist.',
                    ],
                    [
                        'therapist_carlo',
                        '2026-11-21',
                        '',
                        '',
                        'on_leave',
                        'Approved sick leave.',
                    ],
                ],
                'notes' => 'Upload daily staff attendance records. Staff names can be username or full name. Red badges mark required columns.',
                'simple_instructions' => [
                    'Use this to record staff daily attendance when logging offline.',
                    'Columns marked "Required" must not be left empty: Staff Member, Attendance Date, and Status.',
                    'You can type the staff username (e.g. therapist_ana) or real full name (e.g. Ana Reyes).',
                    'Date must be in Year-Month-Day format (e.g. 2026-11-19).',
                    'Enter Time In and Time Out in 12-hour AM/PM format (e.g. 09:00 AM, 06:00 PM). Standard 24-hour time (e.g. 18:00) is also accepted.',
                    'Status must be: present, late, absent, on_leave, or holiday.',
                    'ZenFlow will show you a full preview before saving so you can verify everything.',
                ],
            ],

            'offline_sales' => [
                'type' => 'offline_sales',
                'name' => 'Offline Sales & Completed Transactions',
                'filename' => 'zenflow_offline_sales_template.csv',
                'columns' => [
                    [
                        'key' => 'offline_reference',
                        'label' => 'Offline Reference',
                        'required' => true,
                        'hint' => 'Unique paper receipt or bill number (e.g. OFF-001)',
                    ],
                    [
                        'key' => 'transaction_date',
                        'label' => 'Transaction Date',
                        'required' => true,
                        'hint' => 'Date of sale in Year-Month-Day format (e.g. 2026-11-19)',
                    ],
                    [
                        'key' => 'transaction_time',
                        'label' => 'Transaction Time',
                        'required' => false,
                        'hint' => 'Time of sale in 12-hour AM/PM format (e.g. 02:30 PM). 24-hour time is also supported.',
                    ],
                    [
                        'key' => 'service_name',
                        'label' => 'Service or Package Name',
                        'required' => true,
                        'hint' => 'Name of the spa catalog service OR package purchased (e.g. Classic Swedish Massage or Massage Package). Enter only one.',
                    ],
                    [
                        'key' => 'amount',
                        'label' => 'Amount Paid',
                        'required' => true,
                        'hint' => 'Actual amount collected in PHP (e.g. 530.00). Required; catalog price is not substituted.',
                    ],
                    [
                        'key' => 'payment_method',
                        'label' => 'Payment Method',
                        'required' => true,
                        'hint' => 'Must be: cash, card, gcash, paymaya, or bank_transfer',
                    ],
                    [
                        'key' => 'customer_name',
                        'label' => 'Customer Name',
                        'required' => false,
                        'hint' => 'Full name of walk-in client (e.g. Maria Santos). Optional for anonymous walk-ins.',
                    ],
                    [
                        'key' => 'customer_phone',
                        'label' => 'Customer Phone',
                        'required' => false,
                        'hint' => 'Philippine mobile number (e.g. 09171234567). Optional.',
                    ],
                    [
                        'key' => 'staff_username',
                        'label' => 'Staff / Therapist',
                        'required' => false,
                        'hint' => 'Therapist username or full name. Supports multiple therapists separated by commas (e.g. Ana Reyes, Maria Santos). Equal commission split.',
                    ],
                    [
                        'key' => 'treatment_notes',
                        'label' => 'Treatment Notes',
                        'required' => false,
                        'hint' => 'Remarks about the actual treatment or session associated with this transaction or booking.',
                    ],
                    [
                        'key' => 'notes',
                        'label' => 'Notes',
                        'required' => false,
                        'hint' => 'General remarks about the transaction, such as offline sale details or payment-related information.',
                    ],
                ],
                'headers' => [
                    'Offline Reference',
                    'Transaction Date',
                    'Transaction Time',
                    'Service or Package Name',
                    'Amount Paid',
                    'Payment Method',
                    'Customer Name',
                    'Customer Phone',
                    'Staff / Therapist',
                    'Treatment Notes',
                    'Notes',
                ],
                'canonical_columns' => [
                    'offline_reference',
                    'transaction_date',
                    'transaction_time',
                    'service_name',
                    'amount',
                    'payment_method',
                    'customer_name',
                    'customer_phone',
                    'staff_username',
                    'treatment_notes',
                    'notes',
                ],
                'required' => [
                    'offline_reference',
                    'transaction_date',
                    'service_name',
                    'amount',
                    'payment_method',
                ],
                'samples' => [
                    [
                        'OFF-20261119-001',
                        '2026-11-19',
                        '10:30 AM',
                        'Classic Swedish Massage',
                        '530.00',
                        'cash',
                        'Maria Santos',
                        '09171234567',
                        'therapist_ana',
                        'Target upper back tension, medium pressure.',
                        'Offline receipt during power interruption.',
                    ],
                    [
                        'OFF-20261119-002',
                        '2026-11-19',
                        '02:00 PM',
                        'Body Scrub',
                        '650.00',
                        'gcash',
                        'Juan dela Cruz',
                        '09189876543',
                        'Ana Reyes',
                        'Client sensitive to hot towels.',
                        'Paid via front desk QR.',
                    ],
                    [
                        'OFF-20261119-003',
                        '2026-11-19',
                        '04:45 PM',
                        'Alexandria Signature Massage',
                        '1200.00',
                        'card',
                        'Walk-in Guest',
                        '',
                        '',
                        'Aromatherapy lavender oil selected.',
                        'Terminal transaction #4412.',
                    ],
                ],
                'notes' => 'Record sales conducted offline during system downtime. Supports both services and packages. Red badges mark required columns.',
                'simple_instructions' => [
                    'Use this spreadsheet to record sales and session notes completed when the system is offline.',
                    'Columns marked "Required" must not be left empty: Offline Reference, Transaction Date, Service or Package Name, Amount Paid, and Payment Method.',
                    'Enter Transaction Time in 12-hour format with AM/PM (e.g. 02:30 PM). Standard 24-hour time (e.g. 14:30) is also accepted.',
                    'Amount Paid is required. Record the actual amount collected; catalog price is not automatically substituted.',
                    'Customer Name, Customer Phone, and Staff / Therapist are optional for anonymous walk-ins or older receipts without therapist attribution.',
                    'Treatment Notes are saved specifically to the appointment session and kept separate from general payment remarks.',
                    'ZenFlow will show you a complete preview and check for errors or duplicates before anything is saved.',
                ],
            ],

            default => throw new \InvalidArgumentException("Unsupported import type: [{$type}]"),
        };
    }

    /**
     * Generate a downloadable template with metadata instructions and samples.
     * Supports both professional styled XLSX (default) and standard CSV format.
     */
    public function downloadTemplate(string $type, string $format = 'xlsx'): StreamedResponse
    {
        $def = $this->getTemplateDefinition($type);

        if (strtolower($format) === 'csv') {
            $csvService = app(CsvExportService::class);

            $metadata = [
                ['ZENFLOW IMPORT TEMPLATE', strtoupper($def['name'])],
                ['INSTRUCTIONS', $def['notes']],
                ['REQUIRED FIELDS', implode(', ', $def['required'])],
                ['GUIDANCE', 'Fill out your records below the header row. Do not alter column order. Empty rows are ignored.'],
            ];

            if ($type === 'attendance') {
                $metadata[] = ['ALLOWED STATUSES', 'present, late, absent, on_leave, holiday'];
                $metadata[] = ['DATE & TIME FORMAT', 'Date: YYYY-MM-DD (e.g. 2026-11-19) | Time: HH:MM in 24-hr format (e.g. 09:00, 18:00)'];
                $metadata[] = ['STAFF MATCHING', 'Supports username (therapist_ana) or full name (Ana Reyes), case-insensitive.'];
            } elseif ($type === 'offline_sales') {
                $metadata[] = ['SERVICE OR PACKAGE', 'Supports both catalog services and packages, case-insensitive (e.g. "body scrub", "Body Scrub").'];
                $metadata[] = ['PAYMENT METHODS', 'cash, card, gcash, paymaya, bank_transfer'];
                $metadata[] = ['AMOUNT NOTE', 'Amount Paid is required. Record the actual collected amount; catalog price is not substituted.'];
                $metadata[] = ['TIME FORMAT', 'Enter time in 12-hour AM/PM format (e.g. 02:30 PM). 24-hour time is also supported.'];
                $metadata[] = ['TREATMENT NOTES', 'Treatment Notes are saved to the appointment session and kept separate from general transaction notes.'];
                $metadata[] = ['DUPLICATE SAFEGUARD', 'Offline reference codes are case-insensitive (e.g. OFF-001 vs off-001) to prevent double imports.'];
            }

            return $csvService->stream(
                $def['filename'],
                $def['headers'],
                $def['samples'],
                $metadata
            );
        }

        $xlsxService = app(XlsxSpreadsheetService::class);
        $binary = $xlsxService->generateTemplate($type, $def);
        $xlsxFilename = str_replace('.csv', '.xlsx', $def['filename']);

        return $xlsxService->downloadResponse($xlsxFilename, $binary);
    }

    /**
     * Resolve a raw CSV column header to a canonical key.
     *
     * Handles human-readable headers (e.g. 'Staff Username or Full Name [REQUIRED]'),
     * technical slugs ('staff_username'), bracketed annotations, capitalization,
     * and punctuation differences.
     */
    public function canonicalizeHeader(string $rawHeader, string $type): ?string
    {
        $cleaned = $this->stripBom(trim($rawHeader));
        if ($cleaned === '') {
            return null;
        }

        // 1. Remove bracketed / parenthesized text: "[REQUIRED]", "(Optional)", etc.
        $withoutAnnotations = preg_replace('/\[[^\]]*\]|\([^\)]*\)/', '', $cleaned);

        // 2. Lowercase and collapse whitespace/underscores to single spaces
        $normalized = strtolower(trim((string) $withoutAnnotations));
        $normalized = preg_replace('/[^a-z0-9_ ]/', '', $normalized);
        $normalized = trim((string) preg_replace('/[\s_]+/', ' ', $normalized));

        $rawLower = strtolower(trim($cleaned));

        if ($type === 'attendance') {
            $aliases = [
                'staff_username' => [
                    'staff username or full name', 'staff username', 'username', 'staff', 'therapist',
                    'employee', 'employee name', 'staff name', 'staff therapist', 'staff id', 'user',
                    'staff_username', 'staff_id', 'user_id', 'employee_name', 'staff / therapist',
                ],
                'date' => [
                    'attendance date', 'date', 'day', 'attendance_date', 'work date', 'work_date',
                ],
                'time_in' => [
                    'time in', 'time_in', 'check in', 'check_in', 'clock in', 'clock_in', 'in',
                ],
                'time_out' => [
                    'time out', 'time_out', 'check out', 'check_out', 'clock out', 'clock_out', 'out',
                ],
                'status' => [
                    'status', 'attendance status', 'attendance_status',
                ],
                'notes' => [
                    'notes', 'notes reason', 'remarks', 'reason', 'comment', 'comments',
                ],
            ];
        } else {
            $aliases = [
                'offline_reference' => [
                    'offline reference', 'reference', 'ref', 'reference no', 'ref no', 'offline ref',
                    'offline_reference', 'reference number', 'receipt no', 'receipt number', 'invoice no',
                    'reference_number', 'reference_no', 'receipt_no',
                ],
                'transaction_date' => [
                    'transaction date', 'transaction_date', 'date', 'tx date', 'sale date', 'trans date',
                ],
                'transaction_time' => [
                    'transaction time', 'transaction_time', 'time', 'tx time', 'sale time',
                ],
                'service_name' => [
                    'service or package name', 'service or package', 'service/package name', 'service/package',
                    'service / package name', 'service / package', 'service name', 'package name', 'service',
                    'package', 'treatment', 'item', 'service_or_package_name', 'service_name', 'package_name',
                ],
                'amount' => [
                    'amount paid', 'amount', 'price', 'total', 'total price', 'amount_paid', 'total_price',
                    'paid amount',
                ],
                'payment_method' => [
                    'payment method', 'payment_method', 'method', 'payment type', 'payment', 'pay method',
                    'mode of payment', 'mop',
                ],
                'customer_name' => [
                    'customer name', 'customer_name', 'customer', 'client', 'guest', 'guest name', 'client name',
                ],
                'customer_phone' => [
                    'customer phone', 'customer_phone', 'phone', 'mobile', 'contact', 'contact number',
                    'phone number', 'phone_number', 'mobile number', 'mobile_number',
                ],
                'staff_username' => [
                    'staff therapist', 'staff / therapist', 'staff username', 'staff name', 'therapist',
                    'staff', 'employee', 'therapist name', 'staff_username', 'staff_name', 'assigned staff',
                ],
                'treatment_notes' => [
                    'treatment notes', 'treatment note', 'treatment_notes', 'treatment_note', 'session notes',
                    'session note', 'service notes', 'service note', 'treatment remarks', 'session remarks',
                ],
                'notes' => [
                    'notes', 'note', 'remarks', 'general notes', 'general note', 'transaction notes',
                    'transaction note', 'payment notes', 'comment', 'comments', 'memo',
                ],
            ];
        }

        foreach ($aliases as $canonical => $synonyms) {
            if ($normalized === $canonical || in_array($normalized, $synonyms, true)) {
                return $canonical;
            }
            if ($rawLower === $canonical || in_array($rawLower, $synonyms, true)) {
                return $canonical;
            }
        }

        return null;
    }

    /**
     * Parse an uploaded CSV or XLSX spreadsheet, validate structure, check types, and detect duplicates.
     */
    public function parseAndValidate(string $type, UploadedFile $file): array
    {
        $def = $this->getTemplateDefinition($type);
        $requiredHeaders = $def['required'];

        // 1. Basic file validation
        if (!$file->isValid()) {
            return [
                'success' => false,
                'error' => 'File upload failed or file is corrupted.',
            ];
        }

        $path = $file->getRealPath();
        if (!file_exists($path) || filesize($path) === 0) {
            return [
                'success' => false,
                'error' => 'The uploaded spreadsheet file is empty.',
            ];
        }

        // Determine if file is XLSX (by extension or zip magic bytes)
        $ext = strtolower($file->getClientOriginalExtension());
        $isXlsx = ($ext === 'xlsx');
        if (!$isXlsx) {
            $handleCheck = fopen($path, 'rb');
            if ($handleCheck) {
                $magic = fread($handleCheck, 4);
                fclose($handleCheck);
                if ($magic === "PK\x03\x04") {
                    $isXlsx = true;
                }
            }
        }

        $rowsMatrix = []; // rowNum => array of cell values
        if ($isXlsx) {
            try {
                $xlsxService = app(XlsxSpreadsheetService::class);
                $rowsMatrix = $xlsxService->parse($path);
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'error' => 'Failed to parse Excel file: ' . $e->getMessage(),
                ];
            }
        } else {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                return [
                    'success' => false,
                    'error' => 'Unable to read uploaded CSV file.',
                ];
            }
            $rNum = 0;
            while (($csvRow = fgetcsv($handle, 4096, ',')) !== false) {
                $rNum++;
                $rowsMatrix[$rNum] = $csvRow;
            }
            fclose($handle);
        }

        // 2. Read headers, skip metadata/instruction lines
        $columnMap = null; // index => canonicalKey
        $headerRowNumber = null;

        $metadataPrefixes = [
            'ZENFLOW IMPORT TEMPLATE',
            'ZENFLOW SPA',
            'INSTRUCTIONS',
            'REQUIRED FIELDS',
            'REQUIRED COLUMNS',
            'GUIDANCE',
            'ALLOWED STATUSES',
            'DATE & TIME FORMAT',
            'STAFF MATCHING',
            'SERVICE MATCHING',
            'SERVICE OR PACKAGE',
            'PAYMENT METHODS',
            'AMOUNT NOTE',
            'TREATMENT NOTES',
            'DUPLICATE SAFEGUARD',
            'COLUMN NAME',
        ];

        foreach ($rowsMatrix as $rowNumber => $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            if (!empty($row[0])) {
                $row[0] = $this->stripBom($row[0]);
            }

            $firstCell = strtoupper(trim((string) ($row[0] ?? '')));
            // Skip title banners and section headers like "1. SIMPLE INSTRUCTIONS", "2. FIELD GUIDE", etc.
            if (preg_match('/^\d+\.\s+/', $firstCell) || in_array($firstCell, $metadataPrefixes, true) || str_starts_with($firstCell, 'ZENFLOW')) {
                continue;
            }

            // Test if this row contains our expected headers
            $mappedCandidate = [];
            foreach ($row as $idx => $cell) {
                $canonical = $this->canonicalizeHeader((string) $cell, $type);
                if ($canonical !== null) {
                    $mappedCandidate[$idx] = $canonical;
                }
            }

            $foundRequired = array_intersect($requiredHeaders, array_values($mappedCandidate));
            if (count($foundRequired) === count($requiredHeaders)) {
                $columnMap = $mappedCandidate;
                $headerRowNumber = $rowNumber;
                break;
            }
        }

        if (!$columnMap || $headerRowNumber === null) {
            return [
                'success' => false,
                'error' => 'Could not find valid column headers in spreadsheet. Expected required columns: ' . implode(', ', $requiredHeaders),
            ];
        }

        // 3. Parse and validate data rows (rows strictly after header row)
        $validRecords = [];
        $duplicateRecords = [];
        $invalidRecords = [];
        $dataRowCount = 0;

        // Maps for in-batch duplicate detection (case-insensitive)
        $batchAttendanceKeys = [];
        $batchOfflineRefs = [];

        foreach ($rowsMatrix as $rowNumber => $rawRow) {
            if ($rowNumber <= $headerRowNumber) {
                continue;
            }

            if ($this->isEmptyRow($rawRow)) {
                continue;
            }

            $dataRowCount++;

            // Map each column using detected canonical map
            $rowData = [];
            foreach ($columnMap as $index => $canonicalKey) {
                $val = isset($rawRow[$index]) ? trim((string) $rawRow[$index]) : '';
                $rowData[$canonicalKey] = $val;
            }

            // Validate and detect duplicates based on dataset type
            $result = match ($type) {
                'attendance' => $this->validateAttendanceRow(
                    $rowData,
                    $rowNumber,
                    $batchAttendanceKeys
                ),
                'offline_sales' => $this->validateOfflineSaleRow(
                    $rowData,
                    $rowNumber,
                    $batchOfflineRefs
                ),
            };

            if ($result['status'] === 'valid') {
                $validRecords[] = $result['record'];
            } elseif ($result['status'] === 'duplicate') {
                $duplicateRecords[] = $result;
            } else {
                $invalidRecords[] = $result;
            }
        }

        if ($dataRowCount === 0) {
            return [
                'success' => false,
                'error' => 'The uploaded file contains headers but no data records.',
            ];
        }

        return [
            'success' => true,
            'type' => $type,
            'typeName' => $def['name'],
            'totalRows' => $dataRowCount,
            'validCount' => count($validRecords),
            'duplicateCount' => count($duplicateRecords),
            'invalidCount' => count($invalidRecords),
            'validRecords' => $validRecords,
            'duplicateRecords' => $duplicateRecords,
            'invalidRecords' => $invalidRecords,
        ];
    }

    /**
     * Execute the validated batch import in a database transaction.
     */
    public function executeImport(string $type, array $validRecords): array
    {
        if (empty($validRecords)) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['No valid records to import.'],
            ];
        }

        return DB::transaction(function () use ($type, $validRecords) {
            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($validRecords as $row) {
                try {
                    $wasImported = match ($type) {
                        'attendance' => $this->importAttendanceRecord($row),
                        'offline_sales' => $this->importOfflineSaleRecord($row),
                    };

                    if ($wasImported) {
                        $imported++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Error importing record: " . $e->getMessage();
                }
            }

            return [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ];
        });
    }

    /* ─── ROW VALIDATORS & DUPLICATE CHECKERS ─── */

    protected function validateAttendanceRow(array $row, int $rowNum, array &$batchKeys): array
    {
        $staffInput = self::normalizeString($row['staff_username'] ?? '');
        $dateInput = self::normalizeString($row['date'] ?? '');
        $statusInput = strtolower(self::normalizeString($row['status'] ?? 'present'));
        $timeIn = !empty($row['time_in']) ? self::normalizeString($row['time_in']) : null;
        $timeOut = !empty($row['time_out']) ? self::normalizeString($row['time_out']) : null;

        $errors = [];

        // 1. Staff lookup (case-insensitive by username, full name, or ID)
        $user = null;
        if (empty($staffInput)) {
            $this->addValidationError(
                $errors,
                'Staff Member',
                'staff_username',
                $staffInput,
                'Staff username, full name, or ID is required.',
                'Provide an active staff username (e.g. therapist_ana) or full name (e.g. Ana Reyes).'
            );
        } else {
            $user = $this->resolveStaffUser($staffInput);
            if (!$user) {
                $this->addValidationError(
                    $errors,
                    'Staff Member',
                    'staff_username',
                    $staffInput,
                    "Staff member '{$staffInput}' not found in active staff accounts.",
                    'Verify the staff username or full name matches an existing active staff user.'
                );
            }
        }

        // 2. Date parsing
        $parsedDate = null;
        if (empty($dateInput)) {
            $this->addValidationError(
                $errors,
                'Attendance Date',
                'date',
                $dateInput,
                'Attendance date is required.',
                'Use standard YYYY-MM-DD format (e.g. 2026-11-19).'
            );
        } else {
            try {
                $parsedDate = Carbon::parse($dateInput)->toDateString();
            } catch (\Throwable $e) {
                $this->addValidationError(
                    $errors,
                    'Attendance Date',
                    'date',
                    $dateInput,
                    'Invalid date format.',
                    'Use standard YYYY-MM-DD format (e.g. 2026-11-19).'
                );
            }
        }

        // 3. Status validation
        $validStatuses = ['present', 'late', 'absent', 'on_leave', 'holiday'];
        if (!in_array($statusInput, $validStatuses, true)) {
            $this->addValidationError(
                $errors,
                'Status',
                'status',
                $row['status'] ?? '',
                'Status value is not supported.',
                'Allowed values: ' . implode(', ', $validStatuses) . '.'
            );
        }

        // 4. Time In formatting (Supports 12-hour AM/PM e.g. "09:00 AM" and standard 24-hour e.g. "09:00")
        $formattedTimeIn = null;
        if (!empty($timeIn)) {
            try {
                $formattedTimeIn = Carbon::parse($timeIn)->format('H:i:s');
            } catch (\Throwable $e) {
                $this->addValidationError(
                    $errors,
                    'Time In',
                    'time_in',
                    $timeIn,
                    'Invalid Time In format.',
                    'Use 12-hour AM/PM format (e.g. 09:00 AM) or standard 24-hour format (e.g. 09:00).'
                );
            }
        }

        // 5. Time Out formatting (Supports 12-hour AM/PM e.g. "06:00 PM" and standard 24-hour e.g. "18:00")
        $formattedTimeOut = null;
        if (!empty($timeOut)) {
            try {
                $formattedTimeOut = Carbon::parse($timeOut)->format('H:i:s');
            } catch (\Throwable $e) {
                $this->addValidationError(
                    $errors,
                    'Time Out',
                    'time_out',
                    $timeOut,
                    'Invalid Time Out format.',
                    'Use 12-hour AM/PM format (e.g. 06:00 PM) or standard 24-hour format (e.g. 18:00).'
                );
            }
        }

        // If there are validation errors, return detailed invalid record
        if (!empty($errors)) {
            $reasons = array_map(function ($e) {
                $valDisplay = $e['value'] !== '' ? " ('{$e['value']}')" : '';
                return "{$e['column']}{$valDisplay}: {$e['message']} {$e['suggestion']}";
            }, $errors);

            return [
                'status' => 'invalid',
                'row' => $rowNum,
                'data' => $row,
                'reason' => implode(' | ', $reasons),
                'errors' => $errors,
            ];
        }

        // In-batch duplicate check
        $key = $user->id . '_' . $parsedDate;
        if (in_array($key, $batchKeys, true)) {
            return [
                'status' => 'duplicate',
                'row' => $rowNum,
                'data' => $row,
                'reason' => "Attendance for {$user->full_name} on {$parsedDate} duplicated in uploaded file.",
            ];
        }

        // Database duplicate check (safe for both SQLite datetime strings & MySQL date columns)
        $exists = Attendance::where('user_id', $user->id)
            ->where(function ($q) use ($parsedDate) {
                $q->whereDate('date', $parsedDate)
                  ->orWhere('date', 'like', $parsedDate . '%');
            })
            ->exists();

        if ($exists) {
            return [
                'status' => 'duplicate',
                'row' => $rowNum,
                'data' => $row,
                'reason' => "Attendance record for {$user->full_name} on {$parsedDate} already exists in database.",
            ];
        }

        $batchKeys[] = $key;

        return [
            'status' => 'valid',
            'row' => $rowNum,
            'record' => [
                'user_id' => $user->id,
                'date' => $parsedDate,
                'status' => $statusInput,
                'check_in' => $formattedTimeIn,
                'check_out' => $formattedTimeOut,
                'notes' => $row['notes'] ?? null,
                'staff_username' => $user->username ?? $staffInput,
                'staff_name' => $user->full_name,
            ],
        ];
    }

    protected function validateOfflineSaleRow(array $row, int $rowNum, array &$batchRefs): array
    {
        $ref = self::normalizeString($row['offline_reference'] ?? '');
        $date = self::normalizeString($row['transaction_date'] ?? '');
        $time = self::normalizeString($row['transaction_time'] ?? '');
        $serviceInput = self::normalizeString($row['service_name'] ?? '');
        $methodInput = strtolower(self::normalizeString($row['payment_method'] ?? 'cash'));
        $amountInput = isset($row['amount']) && trim((string) $row['amount']) !== '' ? trim((string) $row['amount']) : null;
        $customerName = self::normalizeString($row['customer_name'] ?? '');
        $rawPhone = $row['customer_phone'] ?? '';
        $customerPhone = self::normalizePhoneNumber($rawPhone);
        $staffInput = self::normalizeString($row['staff_username'] ?? '');

        $errors = [];

        // 1. Reference check
        if (empty($ref)) {
            $this->addValidationError(
                $errors,
                'Offline Reference',
                'offline_reference',
                $ref,
                'Offline reference code is required.',
                'Provide a unique transaction ID (e.g. OFF-20261119-001).'
            );
        }

        // 2. Date check
        $parsedDate = null;
        if (empty($date)) {
            $this->addValidationError(
                $errors,
                'Transaction Date',
                'transaction_date',
                $date,
                'Transaction date is required.',
                'Use standard YYYY-MM-DD format (e.g. 2026-11-19).'
            );
        } else {
            try {
                $parsedDate = Carbon::parse($date)->toDateString();
            } catch (\Throwable $e) {
                $this->addValidationError(
                    $errors,
                    'Transaction Date',
                    'transaction_date',
                    $date,
                    'Invalid transaction date format.',
                    'Use standard YYYY-MM-DD format (e.g. 2026-11-19).'
                );
            }
        }

        // 3. Time formatting (Supports 12-hour AM/PM e.g. "02:30 PM" and 24-hour e.g. "14:30")
        $formattedTime = '12:00:00';
        if (!empty($time)) {
            try {
                $formattedTime = Carbon::parse($time)->format('H:i:s');
            } catch (\Throwable $e) {
                $this->addValidationError(
                    $errors,
                    'Transaction Time',
                    'transaction_time',
                    $time,
                    'Invalid transaction time format.',
                    'Use 12-hour AM/PM format (e.g. 02:30 PM) or standard 24-hour format (e.g. 14:30).'
                );
            }
        }

        // 4. Service or Package lookup (case-insensitive & space-normalized)
        $service = null;
        if (empty($serviceInput)) {
            $this->addValidationError(
                $errors,
                'Service or Package Name',
                'service_name',
                $serviceInput,
                'Service or Package name is required.',
                'Enter an active catalog service or package (e.g. Classic Swedish Massage, Body Scrub, Massage Package).'
            );
        } else {
            $service = $this->resolveService($serviceInput);
            if (!$service) {
                $this->addValidationError(
                    $errors,
                    'Service or Package Name',
                    'service_name',
                    $serviceInput,
                    "Service or Package '{$serviceInput}' was not found in the spa catalog.",
                    'Check catalog spelling. Name matching is case-insensitive (e.g. Body Scrub, body scrub, BODY SCRUB).'
                );
            }
        }

        // 5. Payment method check
        $validMethods = ['cash', 'card', 'gcash', 'paymaya', 'bank_transfer'];
        if (empty($methodInput)) {
            $this->addValidationError(
                $errors,
                'Payment Method',
                'payment_method',
                $row['payment_method'] ?? '',
                'Payment method is required.',
                'Must be one of: ' . implode(', ', $validMethods) . '.'
            );
        } elseif (!in_array($methodInput, $validMethods, true)) {
            $this->addValidationError(
                $errors,
                'Payment Method',
                'payment_method',
                $row['payment_method'] ?? '',
                "Payment method '{$methodInput}' is invalid.",
                'Must be one of: ' . implode(', ', $validMethods) . '.'
            );
        }

        // 6. Amount validation (Strictly required; catalog price is never automatically substituted)
        $finalAmount = 0.0;
        if ($amountInput === null || $amountInput === '') {
            $this->addValidationError(
                $errors,
                'Amount Paid',
                'amount',
                '',
                'Amount Paid is required for historical sales records.',
                'Enter the actual amount collected in PHP (e.g. 530.00). Catalog price is not automatically substituted.'
            );
        } else {
            $hasNegative = str_contains($amountInput, '-');
            $cleanAmount = preg_replace('/[^0-9.]/', '', $amountInput);
            if ($hasNegative || !is_numeric($cleanAmount) || (float) $cleanAmount < 0) {
                $this->addValidationError(
                    $errors,
                    'Amount Paid',
                    'amount',
                    $amountInput,
                    'Amount Paid cannot be negative and must be a valid number.',
                    'Enter the actual amount collected in PHP (e.g. 530.00).'
                );
            } else {
                $finalAmount = (float) $cleanAmount;
            }
        }

        // 7. Optional Staff verification (supports single or comma-separated multiple therapists)
        $resolvedStaff = [];
        if (!empty($staffInput)) {
            $rawNames = explode(',', $staffInput);
            $parsedNames = array_values(array_filter(array_map([self::class, 'normalizeString'], $rawNames)));

            if (empty($parsedNames)) {
                $this->addValidationError(
                    $errors,
                    'Staff / Therapist',
                    'staff_username',
                    $staffInput,
                    'Staff/Therapist field is invalid.',
                    'Leave blank for unassigned walk-in, or provide valid therapist name(s).'
                );
            } else {
                foreach ($parsedNames as $name) {
                    $resolved = $this->resolveStaffUser($name);
                    if (!$resolved) {
                        $this->addValidationError(
                            $errors,
                            'Staff / Therapist',
                            'staff_username',
                            $name,
                            "Staff/Therapist '{$name}' was not found in active accounts.",
                            'Leave blank for unassigned walk-in, or provide a valid username (e.g. therapist_ana) or full name (e.g. Ana Reyes).'
                        );
                    } else {
                        // Avoid duplicates within this row while preserving order
                        if (!collect($resolvedStaff)->contains('id', $resolved->id)) {
                            $resolvedStaff[] = $resolved;
                        }
                    }
                }
            }
        }

        // 8. Optional Phone validation
        if (!empty($rawPhone) && (!empty($customerPhone) && (strlen($customerPhone) < 10 || strlen($customerPhone) > 13))) {
            $this->addValidationError(
                $errors,
                'Customer Phone',
                'customer_phone',
                $rawPhone,
                'Customer phone must be a valid mobile number.',
                'Use standard Philippine mobile format (e.g. 09171234567 or +639171234567).'
            );
        }

        if (!empty($errors)) {
            $reasons = array_map(function ($e) {
                $valDisplay = $e['value'] !== '' ? " ('{$e['value']}')" : '';
                return "{$e['column']}{$valDisplay}: {$e['message']} {$e['suggestion']}";
            }, $errors);

            return [
                'status' => 'invalid',
                'row' => $rowNum,
                'data' => $row,
                'reason' => implode(' | ', $reasons),
                'errors' => $errors,
            ];
        }

        // Duplicate checks: In uploaded batch (case-insensitive)
        $refLower = strtolower($ref);
        if (in_array($refLower, $batchRefs, true)) {
            return [
                'status' => 'duplicate',
                'row' => $rowNum,
                'data' => $row,
                'reason' => "Transaction reference '{$ref}' appears multiple times in uploaded file.",
            ];
        }

        // Duplicate checks: In database (case-insensitive using LOWER(notes) LIKE %[offline ref: ...])
        $existsInDb = Appointment::whereRaw('LOWER(notes) LIKE ?', ['%[offline ref: ' . $refLower . ']%'])
            ->orWhere('notes', 'like', '%[Offline Ref: ' . $ref . ']%')
            ->exists();

        if ($existsInDb) {
            return [
                'status' => 'duplicate',
                'row' => $rowNum,
                'data' => $row,
                'reason' => "Offline sale '{$ref}' has already been imported into ZenFlow.",
            ];
        }

        $batchRefs[] = $refLower;

        $isPackage = (bool) ($service?->is_package ?? false);
        $itemType = $isPackage ? 'Package' : 'Service';

        $primaryStaff = $resolvedStaff[0] ?? null;
        $assistingStaffIds = !empty($resolvedStaff)
            ? collect($resolvedStaff)->skip(1)->pluck('id')->values()->toArray()
            : [];
        $staffNamesString = !empty($resolvedStaff)
            ? collect($resolvedStaff)->map(fn ($u) => $u->full_name ?? $u->name)->join(', ')
            : 'Unassigned';

        return [
            'status' => 'valid',
            'row' => $rowNum,
            'record' => [
                'offline_reference' => $ref,
                'transaction_date' => $parsedDate,
                'transaction_time' => $formattedTime,
                'service_id' => $service->id,
                'service_name' => $service->name,
                'is_package' => $isPackage,
                'item_type' => $itemType,
                'amount' => $finalAmount,
                'payment_method' => $methodInput,
                'customer_name' => $customerName ?: 'Walk-in',
                'customer_phone' => $customerPhone ?: null,
                'staff_id' => $primaryStaff?->id,
                'staff_username' => $primaryStaff?->username,
                'staff_name' => $primaryStaff?->full_name ?? 'Unassigned',
                'staff_names' => $staffNamesString,
                'assisting_staff_ids' => $assistingStaffIds,
                'treatment_notes' => !empty($row['treatment_notes']) ? self::normalizeString($row['treatment_notes']) : null,
                'notes' => !empty($row['notes']) ? self::normalizeString($row['notes']) : null,
            ],
        ];
    }

    /* ─── DATABASE INSERTERS ─── */

    protected function importAttendanceRecord(array $row): bool
    {
        Attendance::create([
            'user_id' => $row['user_id'],
            'date' => $row['date'],
            'status' => $row['status'],
            'check_in' => $row['check_in'] ?? null,
            'check_out' => $row['check_out'] ?? null,
            'notes' => $row['notes'] ?? null,
            'marked_by' => auth()->id(),
        ]);

        return true;
    }

    protected function importOfflineSaleRecord(array $row): bool
    {
        $ref = $row['offline_reference'];
        $transactionDate = $row['transaction_date'];
        $transactionTime = $row['transaction_time'] ?? '12:00:00';
        $amount = (float) $row['amount'];
        $paymentMethod = $row['payment_method'];

        $service = Service::find($row['service_id']);
        if (!$service) {
            throw new \RuntimeException("Service ID {$row['service_id']} not found during execution.");
        }

        $staff = !empty($row['staff_id']) ? User::find($row['staff_id']) : null;
        $customerName = trim($row['customer_name'] ?? '');
        $customerPhone = !empty($row['customer_phone']) ? trim($row['customer_phone']) : null;

        // 1. Resolve or create customer profile
        $customer = null;
        if (!empty($customerPhone)) {
            $customer = Customer::where('phone_number', $customerPhone)->first();
            if (!$customer) {
                $nameParts = explode(' ', $customerName, 2);
                $customer = Customer::create([
                    'first_name' => !empty($nameParts[0]) ? $nameParts[0] : 'Walk-in',
                    'last_name' => $nameParts[1] ?? 'Customer',
                    'phone_number' => $customerPhone,
                    'customer_type' => 'regular',
                ]);
            }
        }

        if (!$customer) {
            // Find or initialize standard Walk-in guest customer record
            $customer = Customer::firstOrCreate(
                ['phone_number' => '09000000000'],
                [
                    'first_name' => !empty($customerName) && strtolower($customerName) !== 'walk-in' ? $customerName : 'Walk-in',
                    'last_name' => 'Guest',
                    'customer_type' => 'regular',
                ]
            );
        }

        // 2. Resolve timestamps
        $startTime = Carbon::parse($transactionTime)->format('H:i:s');
        $duration = $service->duration_minutes ?: ($service->is_package ? 90 : 60);
        $endTime = Carbon::parse($startTime)->addMinutes($duration)->format('H:i:s');
        $paidAt = Carbon::parse("{$transactionDate} {$startTime}");

        // 3. Create completed appointment
        $auditNotes = "[Offline Ref: {$ref}]" . (!empty($row['notes']) ? ' ' . trim($row['notes']) : ' Offline completed sale.');
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $staff?->id,
            'appointment_date' => $transactionDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'completed',
            'total_price' => $amount,
            'notes' => trim($auditNotes),
            'treatment_notes' => !empty($row['treatment_notes']) ? trim($row['treatment_notes']) : null,
            'guest_first_name' => $customer->first_name,
            'guest_phone' => $customer->phone_number,
            'created_by' => auth()->id(),
            'confirmed_at' => $paidAt,
        ]);

        // 4. Attach service to appointment_services
        $appointment->services()->attach($service->id, [
            'price_at_booking' => $amount,
            'service_name' => $service->name,
            'service_duration' => $duration,
            'is_extra' => false,
        ]);

        // 5. Attach staff to appointment_staff if assigned (primary + assisting)
        if (!empty($row['staff_id'])) {
            $assistingIds = $row['assisting_staff_ids'] ?? [];
            $appointment->syncStaff((int) $row['staff_id'], $assistingIds);
        }

        // 6. Create payment
        Payment::create([
            'appointment_id' => $appointment->id,
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'type' => 'full',
            'paid_at' => $paidAt,
        ]);

        return true;
    }

    /* ─── LOOKUP RESOLVERS & HELPERS ─── */

    public function resolveService(string $input): ?Service
    {
        $input = self::normalizeString($input);
        if ($input === '') {
            return null;
        }

        if ($this->servicesCache === null) {
            $this->servicesCache = [];
            $services = Service::all();
            foreach ($services as $svc) {
                $normName = strtolower(self::normalizeString($svc->name));
                $this->servicesCache['name_' . $normName] = $svc;

                if (!empty($svc->code)) {
                    $normCode = strtolower(self::normalizeString($svc->code));
                    $this->servicesCache['code_' . $normCode] = $svc;
                }

                $this->servicesCache['id_' . $svc->id] = $svc;
            }
        }

        $needle = strtolower($input);

        if (isset($this->servicesCache['name_' . $needle])) {
            return $this->servicesCache['name_' . $needle];
        }
        if (isset($this->servicesCache['code_' . $needle])) {
            return $this->servicesCache['code_' . $needle];
        }
        if (is_numeric($needle) && isset($this->servicesCache['id_' . (int) $needle])) {
            return $this->servicesCache['id_' . (int) $needle];
        }

        return null;
    }

    public function resolveStaffUser(string $input): ?User
    {
        $input = self::normalizeString($input);
        if ($input === '') {
            return null;
        }

        if ($this->staffCache === null) {
            $this->staffCache = [];
            $users = User::where('is_active', true)->get();

            foreach ($users as $u) {
                // By ID
                $this->staffCache['id_' . $u->id] = $u;

                // By username
                if (!empty($u->username)) {
                    $this->staffCache['user_' . strtolower(self::normalizeString($u->username))] = $u;
                }

                // By full name ("Ana Reyes")
                $fullName = strtolower(self::normalizeString(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')));
                if ($fullName !== '') {
                    $this->staffCache['name_' . $fullName] = $u;
                }

                // By reverse full name ("Reyes Ana")
                $revName = strtolower(self::normalizeString(($u->last_name ?? '') . ' ' . ($u->first_name ?? '')));
                if ($revName !== '') {
                    $this->staffCache['name_' . $revName] = $u;
                }
            }
        }

        $needle = strtolower($input);

        if (isset($this->staffCache['user_' . $needle])) {
            return $this->staffCache['user_' . $needle];
        }
        if (isset($this->staffCache['name_' . $needle])) {
            return $this->staffCache['name_' . $needle];
        }
        if (is_numeric($needle) && isset($this->staffCache['id_' . (int) $needle])) {
            return $this->staffCache['id_' . (int) $needle];
        }

        return null;
    }

    protected function addValidationError(
        array &$errors,
        string $column,
        string $field,
        ?string $value,
        string $message,
        string $suggestion
    ): void {
        $errors[] = [
            'column' => $column,
            'field' => $field,
            'value' => $value ?? '',
            'message' => $message,
            'suggestion' => $suggestion,
        ];
    }

    /**
     * Normalize internal spacing (collapse tabs, newlines, multiple spaces to one space) and trim.
     */
    public static function normalizeString(?string $text): string
    {
        if ($text === null) {
            return '';
        }
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Normalize Philippine mobile phone numbers to standard 11-digit 09XXXXXXXXX format.
     */
    public static function normalizePhoneNumber(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);
        if ($digits === '') {
            return null;
        }

        // Philippine international prefix 639XXXXXXXXX -> 09XXXXXXXXX
        if (strlen($digits) === 12 && str_starts_with($digits, '639')) {
            $digits = '0' . substr($digits, 2);
        }
        // Missing leading 0: 9XXXXXXXXX -> 09XXXXXXXXX
        elseif (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    /* ─── UTILITIES ─── */

    protected function isEmptyRow(?array $row): bool
    {
        if (empty($row)) {
            return true;
        }
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }

    protected function stripBom(string $text): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $text);
    }
}
