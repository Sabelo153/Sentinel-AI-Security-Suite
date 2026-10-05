<?php

declare(strict_types=1);

namespace Sentinel\Services;

final class CsvExportService
{
    /** @param array<int, array<string, mixed>> $rows */
    public function export(array $rows, array $columns = []): string
    {
        $stream = fopen('php://temp', 'r+');
        $columns = $columns !== [] ? $columns : ($rows === [] ? [] : array_keys($rows[0]));
        if ($columns === []) return '';
        fputcsv($stream, $columns);
        foreach ($rows as $row) fputcsv($stream, array_map([$this, 'safeCell'], array_map(static fn (string $column): mixed => $row[$column] ?? null, $columns)));
        rewind($stream);
        return (string) stream_get_contents($stream);
    }
    private function safeCell(mixed $value): mixed { if (is_string($value) && preg_match('/^[=+\-@]/', $value) === 1) return "'" . $value; return $value; }
}