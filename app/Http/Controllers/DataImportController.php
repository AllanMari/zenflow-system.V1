<?php

namespace App\Http\Controllers;

use App\Services\DataImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DataImportController extends Controller
{
    public function __construct(
        protected DataImportService $importService
    ) {}

    /**
     * Show Data Import management hub.
     */
    public function index(Request $request)
    {
        $currentType = $request->query('type', 'attendance');
        if (!in_array($currentType, DataImportService::SUPPORTED_TYPES)) {
            $currentType = 'attendance';
        }

        $templateDef = $this->importService->getTemplateDefinition($currentType);

        return view('admin.data-import.index', [
            'currentType' => $currentType,
            'templateDef' => $templateDef,
            'supportedTypes' => DataImportService::SUPPORTED_TYPES,
        ]);
    }

    /**
     * Download spreadsheet template for selected dataset.
     * Supports both professional styled XLSX (format=xlsx, recommended) and CSV (format=csv).
     */
    public function downloadTemplate(Request $request, string $type)
    {
        if (!in_array($type, DataImportService::SUPPORTED_TYPES)) {
            abort(404, 'Unsupported import type');
        }

        $format = strtolower($request->query('format', 'csv'));
        return $this->importService->downloadTemplate($type, $format);
    }

    /**
     * Upload and preview spreadsheet (XLSX or CSV) before committing to database.
     */
    public function preview(Request $request)
    {
        $type = $request->input('import_type', 'attendance');
        if (!in_array($type, DataImportService::SUPPORTED_TYPES)) {
            $type = 'attendance';
        }

        $file = $request->file('file') ?? $request->file('csv_file');
        if (!$file) {
            return redirect()
                ->route('admin.data-import.index', ['type' => $type])
                ->with('error', 'Please choose an Excel (.xlsx) or CSV (.csv) spreadsheet file to upload.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'csv', 'txt'])) {
            return redirect()
                ->route('admin.data-import.index', ['type' => $type])
                ->with('error', 'The uploaded file must be an Excel spreadsheet (.xlsx) or CSV file (.csv).');
        }

        if ($file->getSize() > 10 * 1024 * 1024) {
            return redirect()
                ->route('admin.data-import.index', ['type' => $type])
                ->with('error', 'The uploaded file cannot exceed 10MB.');
        }

        $result = $this->importService->parseAndValidate($type, $file);

        if (!$result['success']) {
            return redirect()
                ->route('admin.data-import.index', ['type' => $type])
                ->with('error', $result['error']);
        }

        // Store validated records in session for confirmation
        $previewToken = Str::random(32);
        session()->put("import_payload_{$previewToken}", [
            'type' => $type,
            'records' => $result['validRecords'],
            'created_at' => now()->timestamp,
        ]);

        return view('admin.data-import.preview', [
            'result' => $result,
            'previewToken' => $previewToken,
            'type' => $type,
        ]);
    }

    /**
     * Confirm and execute the import of validated records.
     */
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'preview_token' => 'required|string',
            'import_type' => 'required|in:' . implode(',', DataImportService::SUPPORTED_TYPES),
        ]);

        $token = $validated['preview_token'];
        $sessionKey = "import_payload_{$token}";
        $payload = session()->get($sessionKey);

        if (!$payload || $payload['type'] !== $validated['import_type']) {
            return redirect()
                ->route('admin.data-import.index', ['type' => $validated['import_type']])
                ->with('error', 'Import session expired or is invalid. Please upload the file again.');
        }

        // Execute import within transaction
        $importSummary = $this->importService->executeImport(
            $payload['type'],
            $payload['records']
        );

        // Clear payload from session
        session()->forget($sessionKey);

        $msg = sprintf(
            'Import completed successfully! %d records imported. %d duplicate records were safely skipped.',
            $importSummary['imported'],
            $importSummary['skipped']
        );

        if (!empty($importSummary['errors'])) {
            $msg .= ' (' . count($importSummary['errors']) . ' errors encountered).';
        }

        return redirect()
            ->route('admin.data-import.index', ['type' => $payload['type']])
            ->with('success', $msg);
    }
}
