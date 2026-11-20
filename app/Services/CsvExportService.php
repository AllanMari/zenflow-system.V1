<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    /**
     * UTF-8 Byte Order Mark (BOM) for seamless Microsoft Excel compatibility.
     */
    public const UTF8_BOM = "\xEF\xBB\xBF";

    /**
     * Generate an Excel-compatible downloadable CSV streamed response.
     *
     * @param string $filename Download filename
     * @param array $headers Column headers
     * @param iterable $rows Data rows (array of arrays)
     * @param array|null $meta Optional metadata header rows placed before the table
     * @return StreamedResponse
     */
    public function stream(
        string $filename,
        array $headers,
        iterable $rows,
        ?array $meta = null
    ): StreamedResponse {
        $headersList = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $this->sanitizeFilename($filename) . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->streamDownload(function () use ($headers, $rows, $meta) {
            $handle = fopen('php://output', 'w');

            // 1. Output UTF-8 BOM so Excel opens special characters and currency properly
            fwrite($handle, self::UTF8_BOM);

            // 2. Output optional metadata block (Report Title, Period, Prepared By, etc.)
            if (!empty($meta)) {
                foreach ($meta as $metaRow) {
                    fputcsv($handle, (array) $metaRow);
                }
                // Blank separator row
                fputcsv($handle, []);
            }

            // 3. Output Table Headers
            fputcsv($handle, $headers);

            // 4. Output Data Rows
            foreach ($rows as $row) {
                fputcsv($handle, (array) $row);
            }

            fclose($handle);
        }, $this->sanitizeFilename($filename), $headersList);
    }

    /**
     * Generate a multi-section CSV report (useful for Executive / Business Intelligence reports).
     *
     * @param string $filename
     * @param array $sections Array of ['title' => '...', 'headers' => [...], 'rows' => [...]]
     * @param array|null $meta
     * @return StreamedResponse
     */
    public function streamMultiSection(
        string $filename,
        array $sections,
        ?array $meta = null
    ): StreamedResponse {
        $headersList = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $this->sanitizeFilename($filename) . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->streamDownload(function () use ($sections, $meta) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM
            fwrite($handle, self::UTF8_BOM);

            // Metadata block
            if (!empty($meta)) {
                foreach ($meta as $metaRow) {
                    fputcsv($handle, (array) $metaRow);
                }
                fputcsv($handle, []);
            }

            // Iterate Sections
            foreach ($sections as $index => $section) {
                if (!empty($section['title'])) {
                    fputcsv($handle, ['=== ' . strtoupper($section['title']) . ' ===']);
                }

                if (!empty($section['headers'])) {
                    fputcsv($handle, $section['headers']);
                }

                if (!empty($section['rows'])) {
                    foreach ($section['rows'] as $row) {
                        fputcsv($handle, (array) $row);
                    }
                }

                // Add spacing between sections
                if ($index < count($sections) - 1) {
                    fputcsv($handle, []);
                    fputcsv($handle, []);
                }
            }

            fclose($handle);
        }, $this->sanitizeFilename($filename), $headersList);
    }

    /**
     * Ensure the CSV filename ends with .csv and has safe characters.
     */
    protected function sanitizeFilename(string $filename): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_\-\.]/', '-', $filename);
        if (!str_ends_with(strtolower($clean), '.csv')) {
            $clean .= '.csv';
        }
        return $clean;
    }
}
