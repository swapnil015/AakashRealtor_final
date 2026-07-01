<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tiny streaming CSV helper. Writes a header row plus one row per record using
 * a row-mapping callback, streaming straight to the client so large exports
 * never buffer in memory. Used by the Inquiry and Requirement admin exports.
 */
class CsvExporter
{
    /**
     * @param  string                       $filename  Base name (no extension).
     * @param  array<int, string>           $headers   Column header labels.
     * @param  iterable<mixed>              $rows      Records to export (e.g. a lazy cursor).
     * @param  callable(mixed): array<int, mixed>  $map  Maps a record to a flat row.
     */
    public static function stream(string $filename, array $headers, iterable $rows, callable $map): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $map): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, $map($row));
            }

            fclose($out);
        }, "{$filename}-" . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
