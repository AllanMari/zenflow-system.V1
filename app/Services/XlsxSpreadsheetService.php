<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pure-PHP OpenXML (.xlsx) Spreadsheet Engine for ZenFlow.
 *
 * Implements standard PKZIP binary packing and OpenXML SpreadsheetML generation/parsing
 * with zero external dependencies (no ext-zip or composer packages required).
 * Designed for shared hosting environments like Hostinger.
 */
class XlsxSpreadsheetService
{
    /**
     * Column index (1-based) to Excel letters (1 => A, 27 => AA, etc.)
     */
    public static function colToLetter(int $col): string
    {
        $letter = '';
        while ($col > 0) {
            $rem = ($col - 1) % 26;
            $letter = chr(65 + $rem) . $letter;
            $col = (int) floor(($col - $rem) / 26);
        }
        return $letter;
    }

    /**
     * Excel letters to 1-based column index ('A' => 1, 'AA' => 27)
     */
    public static function letterToCol(string $letter): int
    {
        $col = 0;
        $letter = strtoupper(trim($letter));
        $len = strlen($letter);
        for ($i = 0; $i < $len; $i++) {
            $col = $col * 26 + (ord($letter[$i]) - 64);
        }
        return $col;
    }

    /**
     * Escape special XML characters.
     */
    public static function xmlEscape(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        // Remove invalid XML characters
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $value);
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate an Excel .xlsx downloadable template with 5 structured sections:
     * 1. Title Banner & Purpose
     * 2. Simple Instructions (Read First)
     * 3. Field Guide (Column by column requirements, hints, and examples)
     * 4. Sample Entries (Amber background, realistic data)
     * 5. Data Entry Table (Frozen header row, pre-styled empty rows ready for input)
     */
    public function generateTemplate(string $type, array $definition): string
    {
        $columns = $definition['columns'] ?? [];
        $samples = $definition['samples'] ?? [];
        $instructions = $definition['simple_instructions'] ?? [];
        $numCols = max(count($columns), 1);
        $lastColLetter = self::colToLetter($numCols);

        // Calculate custom column widths based on content requirements
        $colWidths = [];
        foreach ($columns as $idx => $col) {
            $key = $col['key'] ?? '';
            $width = match ($key) {
                'offline_reference', 'reference', 'ref' => 20,
                'transaction_date', 'date' => 16,
                'transaction_time', 'time_in', 'time_out', 'time' => 16,
                'service_name', 'service_or_package_name' => 28,
                'amount', 'price' => 15,
                'payment_method' => 16,
                'customer_name' => 22,
                'customer_phone', 'phone' => 18,
                'staff_username', 'employee' => 20,
                'room_name', 'room' => 18,
                'treatment_notes' => 36,
                'notes' => 34,
                'status' => 14,
                default => 18,
            };
            $colWidths[$idx + 1] = $width;
        }

        $sheetRowsXml = '';
        $mergeCells = [];
        $r = 1;

        // ─────────────────────────────────────────────────────────────
        // SECTION 1: TITLE BANNER & SUBTITLE
        // ─────────────────────────────────────────────────────────────
        $sheetRowsXml .= "<row r=\"{$r}\" ht=\"30\" customHeight=\"1\">\n";
        $sheetRowsXml .= "  <c r=\"A{$r}\" s=\"2\" t=\"inlineStr\"><is><t>  ZENFLOW SPA — " . self::xmlEscape(strtoupper($definition['name'])) . " IMPORT TEMPLATE</t></is></c>\n";
        $sheetRowsXml .= "</row>\n";
        $mergeCells[] = "A{$r}:{$lastColLetter}{$r}";
        $r++;

        $subtitle = ($type === 'offline_sales')
            ? '  Use this spreadsheet to record sales and treatment notes completed offline. Import back when internet is restored.'
            : '  Use this spreadsheet to record staff attendance logs offline. Import back to update employee attendance.';
        $sheetRowsXml .= "<row r=\"{$r}\" ht=\"20\" customHeight=\"1\">\n";
        $sheetRowsXml .= "  <c r=\"A{$r}\" s=\"3\" t=\"inlineStr\"><is><t>{$subtitle}</t></is></c>\n";
        $sheetRowsXml .= "</row>\n";
        $mergeCells[] = "A{$r}:{$lastColLetter}{$r}";
        $r++;

        // Blank separator
        $sheetRowsXml .= "<row r=\"{$r}\" ht=\"8\" customHeight=\"1\"/>\n";
        $r++;

        $isOfflineSales = ($type === 'offline_sales');

        // ─────────────────────────────────────────────────────────────
        // SECTIONS 2 & 3: SIMPLE INSTRUCTIONS + FIELD GUIDE
        // (Skipped for Offline Sales — template is kept compact)
        // ─────────────────────────────────────────────────────────────
        if (!$isOfflineSales) {
            // Simple Instructions
            $sheetRowsXml .= "<row r=\"{$r}\" ht=\"22\" customHeight=\"1\">\n";
            $sheetRowsXml .= "  <c r=\"A{$r}\" s=\"4\" t=\"inlineStr\"><is><t>  1. SIMPLE INSTRUCTIONS (READ FIRST)</t></is></c>\n";
            $sheetRowsXml .= "</row>\n";
            $mergeCells[] = "A{$r}:{$lastColLetter}{$r}";
            $r++;

            foreach ($instructions as $inst) {
                $sheetRowsXml .= "<row r=\"{$r}\" ht=\"18\" customHeight=\"1\">\n";
                $sheetRowsXml .= "  <c r=\"A{$r}\" s=\"5\" t=\"inlineStr\"><is><t>  • " . self::xmlEscape($inst) . "</t></is></c>\n";
                $sheetRowsXml .= "</row>\n";
                $mergeCells[] = "A{$r}:{$lastColLetter}{$r}";
                $r++;
            }

            // Blank separator
            $sheetRowsXml .= "<row r=\"{$r}\" ht=\"8\" customHeight=\"1\"/>\n";
            $r++;

            // Field Guide
            $sheetRowsXml .= "<row r=\"{$r}\" ht=\"22\" customHeight=\"1\">\n";
            $sheetRowsXml .= "  <c r=\"A{$r}\" s=\"4\" t=\"inlineStr\"><is><t>  2. FIELD GUIDE &amp; COLUMN SPECIFICATIONS</t></is></c>\n";
            $sheetRowsXml .= "</row>\n";
            $mergeCells[] = "A{$r}:{$lastColLetter}{$r}";
            $r++;

            // Field Guide Header
            $sheetRowsXml .= "<row r=\"{$r}\" ht=\"20\" customHeight=\"1\">\n";
            $sheetRowsXml .= "  <c r=\"A{$r}\" s=\"1\" t=\"inlineStr\"><is><t>Column Name</t></is></c>\n";
            $sheetRowsXml .= "  <c r=\"B{$r}\" s=\"1\" t=\"inlineStr\"><is><t>Requirement</t></is></c>\n";
            $sheetRowsXml .= "  <c r=\"C{$r}\" s=\"1\" t=\"inlineStr\"><is><t>Description, Expected Format &amp; Examples</t></is></c>\n";
            $sheetRowsXml .= "</row>\n";
            if ($numCols > 3) {
                $mergeCells[] = "C{$r}:{$lastColLetter}{$r}";
            }
            $r++;

            foreach ($columns as $idx => $col) {
                $reqText = !empty($col['required']) ? 'REQUIRED' : 'OPTIONAL';
                $reqStyle = !empty($col['required']) ? '8' : '0';
                $label = self::xmlEscape($col['label'] ?? '');
                $hint = self::xmlEscape($col['hint'] ?? '');

                $sheetRowsXml .= "<row r=\"{$r}\" ht=\"19\" customHeight=\"1\">\n";
                $sheetRowsXml .= "  <c r=\"A{$r}\" s=\"0\" t=\"inlineStr\"><is><t>{$label}</t></is></c>\n";
                $sheetRowsXml .= "  <c r=\"B{$r}\" s=\"{$reqStyle}\" t=\"inlineStr\"><is><t>{$reqText}</t></is></c>\n";
                $sheetRowsXml .= "  <c r=\"C{$r}\" s=\"0\" t=\"inlineStr\"><is><t>{$hint}</t></is></c>\n";
                $sheetRowsXml .= "</row>\n";
                if ($numCols > 3) {
                    $mergeCells[] = "C{$r}:{$lastColLetter}{$r}";
                }
                $r++;
            }

            // Blank separator
            $sheetRowsXml .= "<row r=\"{$r}\" ht=\"8\" customHeight=\"1\"/>\n";
            $r++;
        }

        // ─────────────────────────────────────────────────────────────
        // SAMPLE RECORDS (EXAMPLES ONLY)
        // ─────────────────────────────────────────────────────────────
        $sampleSectionNum = $isOfflineSales ? '1' : '3';
        $sheetRowsXml .= "<row r=\"{$r}\" ht=\"22\" customHeight=\"1\">\n";
        $sheetRowsXml .= "  <c r=\"A{$r}\" s=\"4\" t=\"inlineStr\"><is><t>  {$sampleSectionNum}. SAMPLE RECORDS (EXAMPLES ONLY — DO NOT EDIT)</t></is></c>\n";
        $sheetRowsXml .= "</row>\n";
        $mergeCells[] = "A{$r}:{$lastColLetter}{$r}";
        $r++;

        // Sample Headers (Generous 32pt height so multiline headers never clip)
        $sheetRowsXml .= "<row r=\"{$r}\" ht=\"32\" customHeight=\"1\">\n";
        foreach ($columns as $cIdx => $col) {
            $colLet = self::colToLetter($cIdx + 1);
            $sheetRowsXml .= "  <c r=\"{$colLet}{$r}\" s=\"1\" t=\"inlineStr\"><is><t>" . self::xmlEscape($col['label']) . "</t></is></c>\n";
        }
        $sheetRowsXml .= "</row>\n";
        $r++;

        // Sample Rows with generous dynamic height based on wrapped text
        foreach ($samples as $sampleRow) {
            // Calculate row height: estimate lines per cell based on usable width
            $maxLines = 1;
            foreach ($columns as $cIdx => $col) {
                $val = (string) ($sampleRow[$cIdx] ?? '');
                if ($val === '') {
                    continue;
                }
                $w = $colWidths[$cIdx + 1] ?? 16;
                $usableChars = max(8, (int) floor($w * 1.15) - 3);
                $lines = max(1, (int) ceil(mb_strlen($val) / $usableChars));
                $maxLines = max($maxLines, $lines);
            }
            // 18pt per text line + 10pt vertical padding; minimum 26pt
            $rowHt = max(26, ($maxLines * 18) + 10);

            $sheetRowsXml .= "<row r=\"{$r}\" ht=\"{$rowHt}\" customHeight=\"1\">\n";
            foreach ($columns as $cIdx => $col) {
                $colLet = self::colToLetter($cIdx + 1);
                $val = $sampleRow[$cIdx] ?? '';
                if ($col['key'] === 'amount' && is_numeric($val) && $val !== '') {
                    $sheetRowsXml .= "  <c r=\"{$colLet}{$r}\" s=\"11\"><v>{$val}</v></c>\n";
                } else {
                    $sheetRowsXml .= "  <c r=\"{$colLet}{$r}\" s=\"7\" t=\"inlineStr\"><is><t>" . self::xmlEscape((string) $val) . "</t></is></c>\n";
                }
            }
            $sheetRowsXml .= "</row>\n";
            $r++;
        }

        // Blank separator
        $sheetRowsXml .= "<row r=\"{$r}\" ht=\"10\" customHeight=\"1\"/>\n";
        $r++;

        // ─────────────────────────────────────────────────────────────
        // DATA ENTRY TABLE (ENTER REAL RECORDS HERE)
        // ─────────────────────────────────────────────────────────────
        $firstDataEntryRow = $r + 2;
        $dataSectionNum = $isOfflineSales ? '2' : '4';

        $sheetRowsXml .= "<row r=\"{$r}\" ht=\"24\" customHeight=\"1\">\n";
        $sheetRowsXml .= "  <c r=\"A{$r}\" s=\"4\" t=\"inlineStr\"><is><t>  {$dataSectionNum}. DATA ENTRY TABLE — ENTER YOUR REAL RECORDS STARTING AT ROW {$firstDataEntryRow}</t></is></c>\n";
        $sheetRowsXml .= "</row>\n";
        $mergeCells[] = "A{$r}:{$lastColLetter}{$r}";
        $r++;

        // Table Header Row (Deep Teal background, bold white text; 32pt height prevents clipping)
        $sheetRowsXml .= "<row r=\"{$r}\" ht=\"32\" customHeight=\"1\">\n";
        foreach ($columns as $cIdx => $col) {
            $colLet = self::colToLetter($cIdx + 1);
            $style = '1';
            $sheetRowsXml .= "  <c r=\"{$colLet}{$r}\" s=\"{$style}\" t=\"inlineStr\"><is><t>" . self::xmlEscape($col['label']) . "</t></is></c>\n";
        }
        $sheetRowsXml .= "</row>\n";
        $r++;

        // Pre-style 25 empty entry rows ready for user typing
        for ($entryIndex = 0; $entryIndex < 25; $entryIndex++) {
            $rowNum = $r;
            $isZebra = ($entryIndex % 2 === 1);
            $baseStyle = $isZebra ? '6' : '0';
            $currencyStyle = $isZebra ? '10' : '9';

            $sheetRowsXml .= "<row r=\"{$rowNum}\" ht=\"24\" customHeight=\"1\">\n";
            foreach ($columns as $cIdx => $col) {
                $colLet = self::colToLetter($cIdx + 1);
                $style = ($col['key'] === 'amount') ? $currencyStyle : $baseStyle;
                $sheetRowsXml .= "  <c r=\"{$colLet}{$rowNum}\" s=\"{$style}\"/>\n";
            }
            $sheetRowsXml .= "</row>\n";
            $r++;
        }

        // Build Cols XML
        $colsXml = "  <cols>\n";
        foreach ($colWidths as $cNum => $w) {
            $colsXml .= "    <col min=\"{$cNum}\" max=\"{$cNum}\" width=\"{$w}\" customWidth=\"1\"/>\n";
        }
        $colsXml .= "  </cols>\n";

        // Build MergeCells XML
        $mergeXml = '';
        if (!empty($mergeCells)) {
            $mergeXml = "  <mergeCells count=\"" . count($mergeCells) . "\">\n";
            foreach ($mergeCells as $m) {
                $mergeXml .= "    <mergeCell ref=\"{$m}\"/>\n";
            }
            $mergeXml .= "  </mergeCells>\n";
        }

        // SheetView with 85% zoom and natural scrolling (no stuck frozen rows)
        $sheetViewsXml = "  <sheetViews>\n" .
                         "    <sheetView tabSelected=\"1\" workbookViewId=\"0\" zoomScale=\"85\" zoomScaleNormal=\"85\"/>\n" .
                         "  </sheetViews>\n";

        $sheetXml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n" .
                    "<worksheet xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\">\n" .
                    $sheetViewsXml .
                    $colsXml .
                    "  <sheetData>\n" .
                    $sheetRowsXml .
                    "  </sheetData>\n" .
                    $mergeXml .
                    "</worksheet>";

        return $this->buildZipPackage($sheetXml, 'ZenFlow Template');
    }

    /**
     * Generate an Executive Report in XLSX format (Appointments, Attendance, Sales Reports).
     */
    public function generateReport(
        string $title,
        array $headers,
        iterable $rows,
        ?array $meta = null,
        ?array $totals = null
    ): string {
        $numCols = max(count($headers), 1);
        $lastColLetter = self::colToLetter($numCols);

        // Estimate column widths dynamically
        $colWidths = array_fill(1, $numCols, 13);
        foreach ($headers as $idx => $h) {
            $colWidths[$idx + 1] = max($colWidths[$idx + 1], mb_strlen((string) $h) + 3);
        }

        $sheetRowsXml = '';
        $mergeCells = [];
        $r = 1;

        // Title Banner
        $sheetRowsXml .= "<row r=\"{$r}\" ht=\"30\" customHeight=\"1\">\n";
        $sheetRowsXml .= "  <c r=\"A{$r}\" s=\"2\" t=\"inlineStr\"><is><t>  ZENFLOW — " . self::xmlEscape(strtoupper($title)) . "</t></is></c>\n";
        $sheetRowsXml .= "</row>\n";
        $mergeCells[] = "A{$r}:{$lastColLetter}{$r}";
        $r++;

        // Metadata rows
        if (!empty($meta)) {
            foreach ($meta as $metaItem) {
                $label = self::xmlEscape($metaItem[0] ?? '');
                $val = self::xmlEscape($metaItem[1] ?? '');
                $sheetRowsXml .= "<row r=\"{$r}\" ht=\"18\" customHeight=\"1\">\n";
                $sheetRowsXml .= "  <c r=\"A{$r}\" s=\"4\" t=\"inlineStr\"><is><t>  {$label}:</t></is></c>\n";
                $sheetRowsXml .= "  <c r=\"B{$r}\" s=\"5\" t=\"inlineStr\"><is><t>{$val}</t></is></c>\n";
                $sheetRowsXml .= "</row>\n";
                if ($numCols > 2) {
                    $mergeCells[] = "B{$r}:{$lastColLetter}{$r}";
                }
                $r++;
            }
            // Blank row
            $sheetRowsXml .= "<row r=\"{$r}\" ht=\"10\" customHeight=\"1\"/>\n";
            $r++;
        }

        // Table Header (Frozen Pane)
        $headerRowNumber = $r;
        $sheetRowsXml .= "<row r=\"{$r}\" ht=\"24\" customHeight=\"1\">\n";
        foreach ($headers as $idx => $headerText) {
            $colLet = self::colToLetter($idx + 1);
            $sheetRowsXml .= "  <c r=\"{$colLet}{$r}\" s=\"1\" t=\"inlineStr\"><is><t>" . self::xmlEscape((string) $headerText) . "</t></is></c>\n";
        }
        $sheetRowsXml .= "</row>\n";
        $r++;

        // Data Rows
        $dataIndex = 0;
        foreach ($rows as $row) {
            $dataIndex++;
            $isZebra = ($dataIndex % 2 === 0);
            $rowArr = is_array($row) ? array_values($row) : (array) $row;

            $sheetRowsXml .= "<row r=\"{$r}\" ht=\"20\" customHeight=\"1\">\n";
            foreach ($headers as $cIdx => $headerText) {
                $colLet = self::colToLetter($cIdx + 1);
                $val = $rowArr[$cIdx] ?? '';
                $strVal = (string) $val;

                // Adjust column width estimation (compact max 26 chars, text wrap handles the rest)
                $colWidths[$cIdx + 1] = min(26, max($colWidths[$cIdx + 1], mb_strlen($strVal) + 2));

                // Numeric / Currency check
                if (is_numeric($strVal) && !preg_match('/^0\d+/', $strVal) && (str_contains(strtolower($headerText), 'php') || str_contains(strtolower($headerText), 'sales') || str_contains(strtolower($headerText), 'amount') || str_contains(strtolower($headerText), 'price') || str_contains(strtolower($headerText), 'balance'))) {
                    $style = $isZebra ? '10' : '9';
                    $sheetRowsXml .= "  <c r=\"{$colLet}{$r}\" s=\"{$style}\"><v>{$strVal}</v></c>\n";
                } elseif (is_numeric($strVal) && !preg_match('/^0\d+/', $strVal) && strlen($strVal) < 10) {
                    $style = $isZebra ? '13' : '12';
                    $sheetRowsXml .= "  <c r=\"{$colLet}{$r}\" s=\"{$style}\"><v>{$strVal}</v></c>\n";
                } else {
                    $style = $isZebra ? '6' : '0';
                    $sheetRowsXml .= "  <c r=\"{$colLet}{$r}\" s=\"{$style}\" t=\"inlineStr\"><is><t>" . self::xmlEscape($strVal) . "</t></is></c>\n";
                }
            }
            $sheetRowsXml .= "</row>\n";
            $r++;
        }

        // Summary / Totals Row (If Provided)
        if (!empty($totals)) {
            $sheetRowsXml .= "<row r=\"{$r}\" ht=\"22\" customHeight=\"1\">\n";
            foreach ($headers as $cIdx => $headerText) {
                $colLet = self::colToLetter($cIdx + 1);
                $val = $totals[$cIdx] ?? '';
                $strVal = (string) $val;
                if (is_numeric($strVal)) {
                    $sheetRowsXml .= "  <c r=\"{$colLet}{$r}\" s=\"11\"><v>{$strVal}</v></c>\n";
                } else {
                    $sheetRowsXml .= "  <c r=\"{$colLet}{$r}\" s=\"11\" t=\"inlineStr\"><is><t>" . self::xmlEscape($strVal) . "</t></is></c>\n";
                }
            }
            $sheetRowsXml .= "</row>\n";
            $r++;
        }

        // Build Cols XML
        $colsXml = "  <cols>\n";
        foreach ($colWidths as $cNum => $w) {
            $colsXml .= "    <col min=\"{$cNum}\" max=\"{$cNum}\" width=\"{$w}\" customWidth=\"1\"/>\n";
        }
        $colsXml .= "  </cols>\n";

        // Build MergeCells XML
        $mergeXml = '';
        if (!empty($mergeCells)) {
            $mergeXml = "  <mergeCells count=\"" . count($mergeCells) . "\">\n";
            foreach ($mergeCells as $m) {
                $mergeXml .= "    <mergeCell ref=\"{$m}\"/>\n";
            }
            $mergeXml .= "  </mergeCells>\n";
        }

        // Freeze Pane under header row with comfortable 85% zoom
        $firstDataRow = $headerRowNumber + 1;
        $freezeXml = "  <sheetViews>\n" .
                     "    <sheetView tabSelected=\"1\" workbookViewId=\"0\" zoomScale=\"85\" zoomScaleNormal=\"85\">\n" .
                     "      <pane ySplit=\"{$headerRowNumber}\" topLeftCell=\"A{$firstDataRow}\" activePane=\"bottomLeft\" state=\"frozen\"/>\n" .
                     "    </sheetView>\n" .
                     "  </sheetViews>\n";

        $sheetXml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n" .
                    "<worksheet xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\">\n" .
                    $freezeXml .
                    $colsXml .
                    "  <sheetData>\n" .
                    $sheetRowsXml .
                    "  </sheetData>\n" .
                    $mergeXml .
                    "</worksheet>";

        return $this->buildZipPackage($sheetXml, 'Report');
    }

    /**
     * Generate a multi-section XLSX report (Business Intelligence, Service Popularity).
     */
    public function generateMultiSectionReport(
        string $title,
        array $sections,
        ?array $meta = null
    ): string {
        // Find max columns across all sections
        $maxCols = 2;
        foreach ($sections as $sec) {
            $maxCols = max($maxCols, count($sec['headers'] ?? []));
        }
        $lastColLetter = self::colToLetter($maxCols);

        $colWidths = array_fill(1, $maxCols, 15);

        $sheetRowsXml = '';
        $mergeCells = [];
        $r = 1;

        // Title Banner
        $sheetRowsXml .= "<row r=\"{$r}\" ht=\"30\" customHeight=\"1\">\n";
        $sheetRowsXml .= "  <c r=\"A{$r}\" s=\"2\" t=\"inlineStr\"><is><t>  ZENFLOW — " . self::xmlEscape(strtoupper($title)) . "</t></is></c>\n";
        $sheetRowsXml .= "</row>\n";
        $mergeCells[] = "A{$r}:{$lastColLetter}{$r}";
        $r++;

        // Metadata rows
        if (!empty($meta)) {
            foreach ($meta as $metaItem) {
                $label = self::xmlEscape($metaItem[0] ?? '');
                $val = self::xmlEscape($metaItem[1] ?? '');
                $sheetRowsXml .= "<row r=\"{$r}\" ht=\"18\" customHeight=\"1\">\n";
                $sheetRowsXml .= "  <c r=\"A{$r}\" s=\"4\" t=\"inlineStr\"><is><t>  {$label}:</t></is></c>\n";
                $sheetRowsXml .= "  <c r=\"B{$r}\" s=\"5\" t=\"inlineStr\"><is><t>{$val}</t></is></c>\n";
                $sheetRowsXml .= "</row>\n";
                if ($maxCols > 2) {
                    $mergeCells[] = "B{$r}:{$lastColLetter}{$r}";
                }
                $r++;
            }
            $sheetRowsXml .= "<row r=\"{$r}\" ht=\"10\" customHeight=\"1\"/>\n";
            $r++;
        }

        // Iterate Sections
        foreach ($sections as $sIdx => $sec) {
            $secTitle = $sec['title'] ?? "SECTION " . ($sIdx + 1);
            $secHeaders = $sec['headers'] ?? [];
            $secRows = $sec['rows'] ?? [];

            // Section Header Banner
            $sheetRowsXml .= "<row r=\"{$r}\" ht=\"22\" customHeight=\"1\">\n";
            $sheetRowsXml .= "  <c r=\"A{$r}\" s=\"4\" t=\"inlineStr\"><is><t>  " . self::xmlEscape(strtoupper($secTitle)) . "</t></is></c>\n";
            $sheetRowsXml .= "</row>\n";
            $mergeCells[] = "A{$r}:{$lastColLetter}{$r}";
            $r++;

            // Table Header
            if (!empty($secHeaders)) {
                $sheetRowsXml .= "<row r=\"{$r}\" ht=\"22\" customHeight=\"1\">\n";
                foreach ($secHeaders as $hIdx => $hText) {
                    $colLet = self::colToLetter($hIdx + 1);
                    $sheetRowsXml .= "  <c r=\"{$colLet}{$r}\" s=\"1\" t=\"inlineStr\"><is><t>" . self::xmlEscape((string) $hText) . "</t></is></c>\n";
                    $colWidths[$hIdx + 1] = max($colWidths[$hIdx + 1], mb_strlen((string) $hText) + 3);
                }
                $sheetRowsXml .= "</row>\n";
                $r++;
            }

            // Table Rows
            $dIndex = 0;
            foreach ($secRows as $row) {
                $dIndex++;
                $isZebra = ($dIndex % 2 === 0);
                $rowArr = is_array($row) ? array_values($row) : (array) $row;

                $sheetRowsXml .= "<row r=\"{$r}\" ht=\"20\" customHeight=\"1\">\n";
                foreach ($rowArr as $cIdx => $val) {
                    $colLet = self::colToLetter($cIdx + 1);
                    $strVal = (string) $val;
                    $headerName = $secHeaders[$cIdx] ?? '';

                    $colWidths[$cIdx + 1] = min(26, max($colWidths[$cIdx + 1], mb_strlen($strVal) + 2));

                    if (is_numeric($strVal) && !preg_match('/^0\d+/', $strVal) && (str_contains(strtolower($headerName), 'php') || str_contains(strtolower($headerName), 'revenue') || str_contains(strtolower($headerName), 'amount') || str_contains(strtolower($headerName), 'price'))) {
                        $style = $isZebra ? '10' : '9';
                        $sheetRowsXml .= "  <c r=\"{$colLet}{$r}\" s=\"{$style}\"><v>{$strVal}</v></c>\n";
                    } elseif (is_numeric($strVal) && !preg_match('/^0\d+/', $strVal) && strlen($strVal) < 10) {
                        $style = $isZebra ? '13' : '12';
                        $sheetRowsXml .= "  <c r=\"{$colLet}{$r}\" s=\"{$style}\"><v>{$strVal}</v></c>\n";
                    } else {
                        $style = $isZebra ? '6' : '0';
                        $sheetRowsXml .= "  <c r=\"{$colLet}{$r}\" s=\"{$style}\" t=\"inlineStr\"><is><t>" . self::xmlEscape($strVal) . "</t></is></c>\n";
                    }
                }
                $sheetRowsXml .= "</row>\n";
                $r++;
            }

            // Blank separator after section
            $sheetRowsXml .= "<row r=\"{$r}\" ht=\"10\" customHeight=\"1\"/>\n";
            $r++;
        }

        // Build Cols XML
        $colsXml = "  <cols>\n";
        foreach ($colWidths as $cNum => $w) {
            $colsXml .= "    <col min=\"{$cNum}\" max=\"{$cNum}\" width=\"{$w}\" customWidth=\"1\"/>\n";
        }
        $colsXml .= "  </cols>\n";

        // Build MergeCells XML
        $mergeXml = '';
        if (!empty($mergeCells)) {
            $mergeXml = "  <mergeCells count=\"" . count($mergeCells) . "\">\n";
            foreach ($mergeCells as $m) {
                $mergeXml .= "    <mergeCell ref=\"{$m}\"/>\n";
            }
            $mergeXml .= "  </mergeCells>\n";
        }

        // SheetView with 85% zoom
        $sheetViewsXml = "  <sheetViews>\n" .
                         "    <sheetView tabSelected=\"1\" workbookViewId=\"0\" zoomScale=\"85\" zoomScaleNormal=\"85\"/>\n" .
                         "  </sheetViews>\n";

        $sheetXml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n" .
                    "<worksheet xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\">\n" .
                    $sheetViewsXml .
                    $colsXml .
                    "  <sheetData>\n" .
                    $sheetRowsXml .
                    "  </sheetData>\n" .
                    $mergeXml .
                    "</worksheet>";

        return $this->buildZipPackage($sheetXml, 'Executive Summary');
    }

    /**
     * Parse an uploaded XLSX file or raw binary string into rows compatible with DataImportService.
     *
     * Returns:
     * [
     *   'headers' => ['Offline Reference', ...],
     *   'headerRow' => 14,
     *   'rows' => [
     *     ['row' => 15, 'values' => ['OFF-001', '2026-11-19', ...]],
     *     ...
     *   ]
     * ]
     */
    public function parse(string $filePathOrBinary): array
    {
        $binary = is_file($filePathOrBinary) ? file_get_contents($filePathOrBinary) : $filePathOrBinary;
        if (empty($binary) || strlen($binary) < 22) {
            throw new \InvalidArgumentException('Uploaded file is empty or not a valid XLSX file.');
        }

        $files = self::unzip($binary);
        if (empty($files) || !isset($files['xl/worksheets/sheet1.xml'])) {
            throw new \InvalidArgumentException('Uploaded file is not a valid OpenXML spreadsheet (missing worksheet).');
        }

        // Load shared strings if present
        $sharedStrings = [];
        if (isset($files['xl/sharedStrings.xml'])) {
            $sharedStrings = $this->parseSharedStrings($files['xl/sharedStrings.xml']);
        }

        // Parse Sheet 1
        $sheetXml = $files['xl/worksheets/sheet1.xml'];
        return $this->parseWorksheetRows($sheetXml, $sharedStrings);
    }

    /**
     * Download response helper for streaming XLSX files.
     */
    public function downloadResponse(string $filename, string $binaryContent): StreamedResponse
    {
        $safeFilename = str_ends_with(strtolower($filename), '.xlsx') ? $filename : $filename . '.xlsx';
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $safeFilename);

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $safeFilename . '"',
            'Content-Length' => strlen($binaryContent),
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($binaryContent) {
            echo $binaryContent;
        }, $safeFilename, $headers);
    }

    // =========================================================================
    // INTERNAL OPENXML PACKAGE BUILDER & STYLES
    // =========================================================================

    protected function buildZipPackage(string $sheetXml, string $sheetName): string
    {
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' . "\n" .
            '  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' . "\n" .
            '  <Default Extension="xml" ContentType="application/xml"/>' . "\n" .
            '  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . "\n" .
            '  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . "\n" .
            '  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' . "\n" .
            '</Types>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n" .
            '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' . "\n" .
            '</Relationships>';

        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n" .
            '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' . "\n" .
            '  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' . "\n" .
            '</Relationships>';

        $cleanSheetName = self::xmlEscape(mb_substr($sheetName, 0, 31));
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n" .
            '  <sheets>' . "\n" .
            '    <sheet name="' . $cleanSheetName . '" sheetId="1" r:id="rId1"/>' . "\n" .
            '  </sheets>' . "\n" .
            '</workbook>';

        $stylesXml = $this->getStylesXml();

        $zipFiles = [
            '[Content_Types].xml' => $contentTypes,
            '_rels/.rels' => $rootRels,
            'xl/_rels/workbook.xml.rels' => $wbRels,
            'xl/workbook.xml' => $workbook,
            'xl/styles.xml' => $stylesXml,
            'xl/worksheets/sheet1.xml' => $sheetXml,
        ];

        return self::zip($zipFiles);
    }

    protected function getStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n" .
'  <numFmts count="2">' . "\n" .
'    <numFmt numFmtId="164" formatCode="#,##0.00"/>' . "\n" .
'    <numFmt numFmtId="165" formatCode="yyyy-mm-dd"/>' . "\n" .
'  </numFmts>' . "\n" .
'  <fonts count="9">' . "\n" .
'    <!-- 0: Regular Data (9.5pt Segoe UI Slate) -->' . "\n" .
'    <font><sz val="9.5"/><color rgb="FF1E293B"/><name val="Segoe UI"/><family val="2"/></font>' . "\n" .
'    <!-- 1: Table Header (10pt Bold White) -->' . "\n" .
'    <font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Segoe UI"/><family val="2"/></font>' . "\n" .
'    <!-- 2: Title Banner (13pt Bold White) -->' . "\n" .
'    <font><b/><sz val="13"/><color rgb="FFFFFFFF"/><name val="Segoe UI"/><family val="2"/></font>' . "\n" .
'    <!-- 3: Section Title (10.5pt Bold Teal) -->' . "\n" .
'    <font><b/><sz val="10.5"/><color rgb="FF0F766E"/><name val="Segoe UI"/><family val="2"/></font>' . "\n" .
'    <!-- 4: Instruction/Hint (9pt Slate) -->' . "\n" .
'    <font><sz val="9"/><color rgb="FF475569"/><name val="Segoe UI"/><family val="2"/></font>' . "\n" .
'    <!-- 5: Required Badge (9pt Bold Rose) -->' . "\n" .
'    <font><b/><sz val="9"/><color rgb="FF9F1239"/><name val="Segoe UI"/><family val="2"/></font>' . "\n" .
'    <!-- 6: Sample Record (9pt Italic Amber) -->' . "\n" .
'    <font><i/><sz val="9"/><color rgb="FF92400E"/><name val="Segoe UI"/><family val="2"/></font>' . "\n" .
'    <!-- 7: Total Bold (10pt Bold Slate) -->' . "\n" .
'    <font><b/><sz val="10"/><color rgb="FF0F172A"/><name val="Segoe UI"/><family val="2"/></font>' . "\n" .
'    <!-- 8: Subtitle Italic (9pt White Italic) -->' . "\n" .
'    <font><i/><sz val="9"/><color rgb="FFE2E8F0"/><name val="Segoe UI"/><family val="2"/></font>' . "\n" .
'  </fonts>' . "\n" .
'  <fills count="8">' . "\n" .
'    <fill><patternFill patternType="none"/></fill>' . "\n" .
'    <fill><patternFill patternType="gray125"/></fill>' . "\n" .
'    <!-- 2: ZenFlow Teal (#0F766E) -->' . "\n" .
'    <fill><patternFill patternType="solid"><fgColor rgb="FF0F766E"/></patternFill></fill>' . "\n" .
'    <!-- 3: Forest Title Banner (#115E59) -->' . "\n" .
'    <fill><patternFill patternType="solid"><fgColor rgb="FF115E59"/></patternFill></fill>' . "\n" .
'    <!-- 4: Soft Mint Section (#F0FDFA) -->' . "\n" .
'    <fill><patternFill patternType="solid"><fgColor rgb="FFF0FDFA"/></patternFill></fill>' . "\n" .
'    <!-- 5: Zebra Striping Alternate (#F8FAFC) -->' . "\n" .
'    <fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>' . "\n" .
'    <!-- 6: Sample Amber Tint (#FEF3C7) -->' . "\n" .
'    <fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/></patternFill></fill>' . "\n" .
'    <!-- 7: Soft Rose Highlight (#FFE4E6) -->' . "\n" .
'    <fill><patternFill patternType="solid"><fgColor rgb="FFFFE4E6"/></patternFill></fill>' . "\n" .
'  </fills>' . "\n" .
'  <borders count="4">' . "\n" .
'    <!-- 0: none -->' . "\n" .
'    <border><left/><right/><top/><bottom/><diagonal/></border>' . "\n" .
'    <!-- 1: Thin light gray border (#CBD5E1) -->' . "\n" .
'    <border>' . "\n" .
'      <left style="thin"><color rgb="FFCBD5E1"/></left>' . "\n" .
'      <right style="thin"><color rgb="FFCBD5E1"/></right>' . "\n" .
'      <top style="thin"><color rgb="FFCBD5E1"/></top>' . "\n" .
'      <bottom style="thin"><color rgb="FFCBD5E1"/></bottom>' . "\n" .
'    </border>' . "\n" .
'    <!-- 2: Header Teal Border (#0D9488) -->' . "\n" .
'    <border>' . "\n" .
'      <left style="thin"><color rgb="FF0D9488"/></left>' . "\n" .
'      <right style="thin"><color rgb="FF0D9488"/></right>' . "\n" .
'      <top style="thin"><color rgb="FF0D9488"/></top>' . "\n" .
'      <bottom style="medium"><color rgb="FF0F766E"/></bottom>' . "\n" .
'    </border>' . "\n" .
'    <!-- 3: Summary Total Border -->' . "\n" .
'    <border>' . "\n" .
'      <left style="thin"><color rgb="FFCBD5E1"/></left>' . "\n" .
'      <right style="thin"><color rgb="FFCBD5E1"/></right>' . "\n" .
'      <top style="thin"><color rgb="FF0F766E"/></top>' . "\n" .
'      <bottom style="double"><color rgb="FF0F766E"/></bottom>' . "\n" .
'    </border>' . "\n" .
'  </borders>' . "\n" .
'  <cellStyleXfs count="1">' . "\n" .
'    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' . "\n" .
'  </cellStyleXfs>' . "\n" .
'  <cellXfs count="14">' . "\n" .
'    <!-- 0: Default Regular Data (Font 0, Fill 0, Border 1, Left, top-align/wrap) -->' . "\n" .
'    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>' . "\n" .
'    <!-- 1: Table Header (Font 1, Fill 2, Border 2, Center/Wrap) -->' . "\n" .
'    <xf numFmtId="0" fontId="1" fillId="2" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' . "\n" .
'    <!-- 2: Title Banner (Font 2, Fill 3, Left) -->' . "\n" .
'    <xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' . "\n" .
'    <!-- 3: Subtitle / Purpose (Font 8, Fill 3, Left) -->' . "\n" .
'    <xf numFmtId="0" fontId="8" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' . "\n" .
'    <!-- 4: Section Title (Font 3, Fill 4, Border 1, Left) -->' . "\n" .
'    <xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' . "\n" .
'    <!-- 5: Instruction Item (Font 4, Fill 4, Border 1, Left/Wrap) -->' . "\n" .
'    <xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>' . "\n" .
'    <!-- 6: Zebra Data Cell (Font 0, Fill 5, Border 1, Left, top-align/wrap) -->' . "\n" .
'    <xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>' . "\n" .
'    <!-- 7: Sample Row Cell (Font 6, Fill 6, Border 1, Left, top-align/wrap) -->' . "\n" .
'    <xf numFmtId="0" fontId="6" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>' . "\n" .
'    <!-- 8: Required Badge Cell (Font 5, Fill 7, Border 1, Center) -->' . "\n" .
'    <xf numFmtId="0" fontId="5" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' . "\n" .
'    <!-- 9: Currency Regular (Number 164, Font 0, Fill 0, Border 1, Right) -->' . "\n" .
'    <xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="top"/></xf>' . "\n" .
'    <!-- 10: Currency Zebra (Number 164, Font 0, Fill 5, Border 1, Right) -->' . "\n" .
'    <xf numFmtId="164" fontId="0" fillId="5" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="top"/></xf>' . "\n" .
'    <!-- 11: Summary Total Row Cell (Number 164, Font 7, Fill 4, Border 3, Right) -->' . "\n" .
'    <xf numFmtId="164" fontId="7" fillId="4" borderId="3" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' . "\n" .
'    <!-- 12: Centered Regular Cell (Font 0, Fill 0, Border 1, Center) -->' . "\n" .
'    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf>' . "\n" .
'    <!-- 13: Centered Zebra Cell (Font 0, Fill 5, Border 1, Center) -->' . "\n" .
'    <xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf>' . "\n" .
'  </cellXfs>' . "\n" .
'</styleSheet>';
    }

    // =========================================================================
    // OPENXML WORKSHEET PARSER
    // =========================================================================

    protected function parseSharedStrings(string $xmlContent): array
    {
        $strings = [];
        try {
            $xml = new \SimpleXMLElement($xmlContent);
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $strings[] = (string) $si->t;
                } elseif (isset($si->r)) {
                    $combined = '';
                    foreach ($si->r as $run) {
                        $combined .= (string) $run->t;
                    }
                    $strings[] = $combined;
                } else {
                    $strings[] = '';
                }
            }
        } catch (\Throwable $e) {
            // Fallback regex if SimpleXML encounters any odd entity
            preg_match_all('/<si>(.*?)<\/si>/s', $xmlContent, $matches);
            foreach ($matches[1] as $item) {
                preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $item, $tMatches);
                $strings[] = html_entity_decode(implode('', $tMatches[1] ?? []), ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }
        return $strings;
    }

    protected function parseWorksheetRows(string $sheetXml, array $sharedStrings): array
    {
        $allRows = [];

        try {
            $xml = new \SimpleXMLElement($sheetXml);
            $sheetData = $xml->sheetData;

            if ($sheetData) {
                foreach ($sheetData->row as $rowElem) {
                    $rowNum = (int) $rowElem['r'];
                    $cells = [];

                    foreach ($rowElem->c as $c) {
                        $ref = (string) $c['r'];
                        preg_match('/^([A-Z]+)(\d+)$/', $ref, $m);
                        $colLetter = $m[1] ?? 'A';
                        $colIdx = self::letterToCol($colLetter);

                        $type = (string) $c['t'];
                        $val = '';

                        if ($type === 's') {
                            // Shared string lookup
                            $sIdx = (int) ((string) $c->v);
                            $val = $sharedStrings[$sIdx] ?? '';
                        } elseif ($type === 'inlineStr') {
                            $val = (string) ($c->is->t ?? '');
                        } else {
                            $val = (string) ($c->v ?? '');
                        }

                        $cells[$colIdx] = trim($val);
                    }

                    // Fill any gaps so row has sequential 1..N indices
                    if (!empty($cells)) {
                        $maxCol = max(array_keys($cells));
                        $rowValues = [];
                        for ($i = 1; $i <= $maxCol; $i++) {
                            $rowValues[$i - 1] = $cells[$i] ?? '';
                        }
                        $allRows[$rowNum] = $rowValues;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Regex fallback for row and cell parsing
            preg_match_all('/<row[^>]*r="(\d+)"[^>]*>(.*?)<\/row>/s', $sheetXml, $rowMatches, PREG_SET_ORDER);
            foreach ($rowMatches as $rm) {
                $rowNum = (int) $rm[1];
                $cellXml = $rm[2];
                $cells = [];

                preg_match_all('/<c[^>]*r="([A-Z]+)\d+"(?:[^>]*t="([^"]*)")?[^>]*>(?:<v>([^<]*)<\/v>|<is><t>([^<]*)<\/t><\/is>)?<\/c>/s', $cellXml, $cm, PREG_SET_ORDER);
                foreach ($cm as $c) {
                    $colLetter = $c[1];
                    $type = $c[2] ?? '';
                    $val = $c[3] !== '' ? $c[3] : ($c[4] ?? '');
                    if ($type === 's') {
                        $val = $sharedStrings[(int) $val] ?? '';
                    }
                    $cells[self::letterToCol($colLetter)] = trim(html_entity_decode($val, ENT_QUOTES | ENT_XML1, 'UTF-8'));
                }

                if (!empty($cells)) {
                    $maxCol = max(array_keys($cells));
                    $rowValues = [];
                    for ($i = 1; $i <= $maxCol; $i++) {
                        $rowValues[$i - 1] = $cells[$i] ?? '';
                    }
                    $allRows[$rowNum] = $rowValues;
                }
            }
        }

        return $allRows;
    }

    // =========================================================================
    // PURE-PHP PKZIP PACKER & UNPACKER (Zero dependencies, Hostinger compatible)
    // =========================================================================

    public static function zip(array $files): string
    {
        $localFiles = '';
        $cd = '';
        $offset = 0;

        foreach ($files as $name => $content) {
            $crc = crc32($content);
            $uncomp = strlen($content);
            $compData = gzdeflate($content, 6);
            $comp = strlen($compData);
            $nameLen = strlen($name);

            // Local File Header (30 bytes + name)
            $localHeader = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 8, 0, 0, $crc, $comp, $uncomp, $nameLen, 0) . $name;
            $localFiles .= $localHeader . $compData;

            // Central Directory Entry (46 bytes + name)
            $cdHeader = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 8, 0, 0, $crc, $comp, $uncomp, $nameLen, 0, 0, 0, 0, 32, $offset) . $name;
            $cd .= $cdHeader;

            $offset += strlen($localHeader) + $comp;
        }

        $cdSize = strlen($cd);
        // End of Central Directory (22 bytes)
        $eocd = pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), $cdSize, $offset, 0);

        return $localFiles . $cd . $eocd;
    }

    public static function unzip(string $zipData): array
    {
        $files = [];
        $len = strlen($zipData);
        if ($len < 22) {
            return [];
        }

        // Search backward for EOCD signature (0x06054b50)
        $eocdPos = false;
        for ($i = $len - 22; $i >= max(0, $len - 65557); $i--) {
            if (substr($zipData, $i, 4) === "\x50\x4b\x05\x06") {
                $eocdPos = $i;
                break;
            }
        }
        if ($eocdPos === false) {
            return [];
        }

        $eocd = unpack('vdisk/vdiskStart/vtotalDisk/vtotal/VcdSize/VcdOffset/vcommentLen', substr($zipData, $eocdPos + 4, 18));
        $cdOffset = $eocd['cdOffset'];
        $totalEntries = $eocd['total'];

        $pos = $cdOffset;
        for ($e = 0; $e < $totalEntries; $e++) {
            if (substr($zipData, $pos, 4) !== "\x50\x4b\x01\x02") {
                break;
            }

            $cd = unpack('vmadeBy/vversion/vflag/vmethod/vtime/vdate/Vcrc/VcompSize/VuncompSize/vnameLen/vextraLen/vcommentLen/vdiskStart/vintAttr/VextAttr/Voffset', substr($zipData, $pos + 4, 42));
            $name = substr($zipData, $pos + 46, $cd['nameLen']);
            $pos += 46 + $cd['nameLen'] + $cd['extraLen'] + $cd['commentLen'];

            $localPos = $cd['offset'];
            if (substr($zipData, $localPos, 4) !== "\x50\x4b\x03\x04") {
                continue;
            }

            $lh = unpack('vversion/vflag/vmethod/vtime/vdate/Vcrc/VcompSize/VuncompSize/vnameLen/vextraLen', substr($zipData, $localPos + 4, 26));
            $dataStart = $localPos + 30 + $lh['nameLen'] + $lh['extraLen'];
            $rawPayload = substr($zipData, $dataStart, $cd['compSize']);

            $files[$name] = ($cd['method'] === 8) ? gzinflate($rawPayload) : $rawPayload;
        }

        return $files;
    }
}
