<?php

namespace App\Imports;

use App\Imports\Concerns\ParsesCsv;
use App\Models\KitCatalogPart;
use App\Models\KitInventory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class KitCatalogPartCsvImport
{
    use ParsesCsv;

    /**
     * @var array<string, string>
     */
    private const FIELD_LABELS = [
        'part_number' => 'Part Number',
        'product_name' => 'Product Name',
        'retail_price' => 'Retail Price',
        'your_price' => 'Your Price',
        'cross_reference' => 'Cross Reference',
        'model_compatibility' => 'Models it applies to',
        'total_stock' => 'Total Stock',
    ];

    public function preview(string $path): CsvImportPreview
    {
        $creates = [];
        $updates = [];
        $errors = [];
        $sideEffectRows = [];
        $unchangedCount = 0;

        foreach ($this->parseRows($path) as $parsed) {
            if ($parsed['action'] === 'error') {
                $errors[] = [
                    'row_number' => $parsed['row_number'],
                    'match_key' => $parsed['match_key'],
                    'errors' => $parsed['errors'],
                ];

                continue;
            }

            if ($parsed['action'] === 'create' || $parsed['action'] === 'restore') {
                $display = $parsed['display'];
                if ($parsed['action'] === 'restore') {
                    $display['product_name'] = ($display['product_name'] ?? '—').' (restore)';
                }
                $creates[] = $display;

                continue;
            }

            if ($parsed['changes'] !== []) {
                $updates[] = [
                    'row_number' => $parsed['row_number'],
                    'match_key' => $parsed['match_key'],
                    'changes' => $parsed['changes'],
                ];

                continue;
            }

            $effects = $this->inventorySyncEffects(
                $parsed['existing'],
                (int) ($parsed['display']['total_stock'] ?? 0),
            );

            if ($effects !== []) {
                $sideEffectRows[] = [
                    'row_number' => $parsed['row_number'],
                    'match_key' => $parsed['match_key'],
                    'effects' => $effects,
                ];

                continue;
            }

            $unchangedCount++;
        }

        return new CsvImportPreview(
            creates: $creates,
            updates: $updates,
            errors: $errors,
            unchangedCount: $unchangedCount,
            createColumns: self::FIELD_LABELS,
            sideEffectRows: $sideEffectRows,
        );
    }

    public function commit(string $path, User $user): CsvImportResult
    {
        $imported = 0;
        $updated = 0;

        DB::transaction(function () use ($path, $user, &$imported, &$updated) {
            foreach ($this->parseRows($path) as $parsed) {
                if ($parsed['action'] === 'error') {
                    throw ValidationException::withMessages([
                        'csv_file' => ["Row {$parsed['row_number']}: ".implode(' ', $parsed['errors'])],
                    ]);
                }

                $payload = $parsed['payload'];
                $payload['updated_by'] = $user->id;

                if ($parsed['existing']) {
                    $part = $parsed['existing'];

                    if ($part->trashed()) {
                        $part->restore();
                        $payload['created_by'] = $part->created_by ?: $user->id;
                        $imported++;
                    } else {
                        $updated++;
                    }

                    $part->update($payload);
                    $this->syncInventory($part);

                    continue;
                }

                $payload['created_by'] = $user->id;
                $part = KitCatalogPart::create($payload);
                $imported++;
                $this->syncInventory($part);
            }
        });

        return new CsvImportResult($imported, $updated);
    }

    /**
     * @return \Generator<int, array{
     *     row_number: int,
     *     action: 'create'|'update'|'unchanged'|'restore'|'error',
     *     existing: ?KitCatalogPart,
     *     match_key: string,
     *     display: array<string, mixed>,
     *     payload: array<string, mixed>,
     *     changes: list<array{field: string, label: string, from: string, to: string}>,
     *     errors: list<string>
     * }>
     */
    private function parseRows(string $path): \Generator
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'csv_file' => ['Unable to read the staged CSV file.'],
            ]);
        }

        try {
            $headers = fgetcsv($handle) ?: [];
            $columns = $this->csvColumns($headers);
            $rowNumber = 1;
            /** @var array<string, array{display: array<string, mixed>, payload: array<string, mixed>}> $pendingByNumber */
            $pendingByNumber = [];

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                    continue;
                }

                $partNumber = $this->normalizeIdentifier((string) $this->csvValue($row, $columns, ['part_number', 'partnumber'], 1));
                if ($partNumber === '') {
                    continue;
                }

                $candidate = [
                    'part_number' => $partNumber,
                    'product_name' => trim((string) $this->csvValue($row, $columns, ['product_name', 'product', 'name'], null)) ?: null,
                    'model_compatibility' => trim((string) $this->csvValue($row, $columns, ['models_it_applies_to', 'model_compatibility', 'models'], 6)) ?: null,
                    'total_stock' => (int) ($this->csvMoney($this->csvValue($row, $columns, ['total_stock', 'stock'], null)) ?: 0),
                    'retail_price' => $this->csvMoney($this->csvValue($row, $columns, ['retail_price', 'retail'], 2)),
                    'your_price' => $this->csvMoney($this->csvValue($row, $columns, ['your_price', 'cost'], 3)),
                    'cross_reference' => trim((string) $this->csvValue($row, $columns, ['cross_reference_information', 'cross_reference'], 5)) ?: null,
                ];

                $validator = Validator::make($candidate, [
                    'part_number' => ['required', 'string', 'max:255'],
                    'product_name' => ['nullable', 'string', 'max:255'],
                    'model_compatibility' => ['nullable', 'string', 'max:255'],
                    'total_stock' => ['nullable', 'integer', 'min:0'],
                    'retail_price' => ['required', 'numeric', 'min:0'],
                    'your_price' => ['required', 'numeric', 'min:0'],
                    'cross_reference' => ['nullable', 'string', 'max:255'],
                ]);

                if ($validator->fails()) {
                    yield [
                        'row_number' => $rowNumber,
                        'action' => 'error',
                        'existing' => null,
                        'match_key' => $partNumber,
                        'display' => $candidate,
                        'payload' => [],
                        'changes' => [],
                        'errors' => $validator->errors()->all(),
                    ];

                    continue;
                }

                $payload = $validator->validated();
                $display = [
                    'part_number' => $payload['part_number'],
                    'product_name' => $payload['product_name'],
                    'retail_price' => $payload['retail_price'],
                    'your_price' => $payload['your_price'],
                    'cross_reference' => $payload['cross_reference'],
                    'model_compatibility' => $payload['model_compatibility'],
                    'total_stock' => $payload['total_stock'] ?? 0,
                ];

                $existing = KitCatalogPart::withTrashed()->where('part_number', $payload['part_number'])->first();
                $pending = $pendingByNumber[$payload['part_number']] ?? null;

                if (! $existing && $pending !== null) {
                    $changes = $this->diffFields($pending['display'], $display, self::FIELD_LABELS);
                    $pendingByNumber[$payload['part_number']] = [
                        'display' => $display,
                        'payload' => $payload,
                    ];

                    yield [
                        'row_number' => $rowNumber,
                        'action' => $changes === [] ? 'unchanged' : 'update',
                        'existing' => null,
                        'match_key' => $payload['part_number'],
                        'display' => $display,
                        'payload' => $payload,
                        'changes' => $changes,
                        'errors' => [],
                    ];

                    continue;
                }

                if (! $existing) {
                    $pendingByNumber[$payload['part_number']] = [
                        'display' => $display,
                        'payload' => $payload,
                    ];

                    yield [
                        'row_number' => $rowNumber,
                        'action' => 'create',
                        'existing' => null,
                        'match_key' => $payload['part_number'],
                        'display' => $display,
                        'payload' => $payload,
                        'changes' => [],
                        'errors' => [],
                    ];

                    continue;
                }

                if ($existing->trashed()) {
                    $pendingByNumber[$payload['part_number']] = [
                        'display' => $display,
                        'payload' => $payload,
                    ];

                    yield [
                        'row_number' => $rowNumber,
                        'action' => 'restore',
                        'existing' => $existing,
                        'match_key' => $payload['part_number'],
                        'display' => $display,
                        'payload' => $payload,
                        'changes' => [],
                        'errors' => [],
                    ];

                    continue;
                }

                $pendingByNumber[$payload['part_number']] = [
                    'display' => $display,
                    'payload' => $payload,
                ];

                $current = [
                    'part_number' => $existing->part_number,
                    'product_name' => $existing->product_name,
                    'retail_price' => $existing->retail_price,
                    'your_price' => $existing->your_price,
                    'cross_reference' => $existing->cross_reference,
                    'model_compatibility' => $existing->model_compatibility,
                    'total_stock' => $existing->total_stock,
                ];

                $changes = $this->diffFields($current, $display, self::FIELD_LABELS);

                yield [
                    'row_number' => $rowNumber,
                    'action' => $changes === [] ? 'unchanged' : 'update',
                    'existing' => $existing,
                    'match_key' => $payload['part_number'],
                    'display' => $display,
                    'payload' => $payload,
                    'changes' => $changes,
                    'errors' => [],
                ];
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return list<string>
     */
    private function inventorySyncEffects(?KitCatalogPart $part, int $totalStock): array
    {
        if ($part === null) {
            return [];
        }

        $inventory = KitInventory::query()->where('part_name', $part->part_number)->first();

        if (! $inventory) {
            return ['Create kit inventory row with stock '.$totalStock];
        }

        if ((int) $inventory->current_stock !== $totalStock) {
            return ['Sync kit inventory stock '.(int) $inventory->current_stock.' → '.$totalStock];
        }

        return [];
    }

    private function syncInventory(KitCatalogPart $part): void
    {
        KitInventory::updateOrCreate(
            ['part_name' => $part->part_number],
            [
                'current_stock' => $part->total_stock,
                'min_level' => 0,
            ]
        );
    }
}
