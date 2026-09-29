<?php

namespace App\Imports\Concerns;

trait ParsesCsv
{
    /**
     * @param  array<int, mixed>  $headers
     * @return array<string, int>
     */
    protected function csvColumns(array $headers): array
    {
        $columns = [];

        foreach ($headers as $index => $header) {
            $key = strtolower(trim((string) $header));
            $key = preg_replace('/[^a-z0-9]+/', '_', $key);
            $columns[trim($key, '_')] = $index;
        }

        return $columns;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $columns
     * @param  list<string>  $keys
     */
    protected function csvValue(array $row, array $columns, array $keys, ?int $fallbackIndex = null): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $columns)) {
                return $row[$columns[$key]] ?? null;
            }
        }

        return $fallbackIndex !== null ? ($row[$fallbackIndex] ?? null) : null;
    }

    protected function csvMoney(mixed $value): float
    {
        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return $normalized === '' || $normalized === '-' ? 0.0 : (float) $normalized;
    }

    protected function normalizeIdentifier(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9-]/', '', strtoupper(trim($value))) ?? '');
    }

    protected function valuesEqual(mixed $left, mixed $right): bool
    {
        if (is_numeric($left) && is_numeric($right)) {
            return abs((float) $left - (float) $right) < 0.005;
        }

        $leftNormalized = $left === null || $left === '' ? null : (string) $left;
        $rightNormalized = $right === null || $right === '' ? null : (string) $right;

        return $leftNormalized === $rightNormalized;
    }

    protected function formatDisplayValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_float($value) || (is_string($value) && is_numeric($value) && str_contains((string) $value, '.'))) {
            return number_format((float) $value, 2);
        }

        return (string) $value;
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $incoming
     * @param  array<string, string>  $fieldLabels
     * @return list<array{field: string, label: string, from: string, to: string}>
     */
    protected function diffFields(array $current, array $incoming, array $fieldLabels): array
    {
        $changes = [];

        foreach ($fieldLabels as $field => $label) {
            if ($this->valuesEqual($current[$field] ?? null, $incoming[$field] ?? null)) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'label' => $label,
                'from' => $this->formatDisplayValue($current[$field] ?? null),
                'to' => $this->formatDisplayValue($incoming[$field] ?? null),
            ];
        }

        return $changes;
    }
}
