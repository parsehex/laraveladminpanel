<?php

namespace App\Imports;

use App\Imports\Concerns\ParsesCsv;
use App\Models\Model;
use App\Models\Part;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PartCsvImport
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
        $modelsToLink = [];

        foreach ($this->parseRows($path) as $parsed) {
            if ($parsed['action'] === 'error') {
                $errors[] = [
                    'row_number' => $parsed['row_number'],
                    'match_key' => $parsed['match_key'],
                    'errors' => $parsed['errors'],
                ];

                continue;
            }

            foreach ($parsed['linkable_models'] as $modelNumber) {
                $modelsToLink[] = $modelNumber;
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

            $effects = $this->newModelLinkEffects($parsed['existing'], $parsed['linkable_models']);
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

        $modelsToLink = array_values(array_unique($modelsToLink));
        sort($modelsToLink);

        return new CsvImportPreview(
            creates: $creates,
            updates: $updates,
            errors: $errors,
            unchangedCount: $unchangedCount,
            createColumns: self::FIELD_LABELS,
            sideEffects: $modelsToLink === [] ? [] : [
                'Models that will be linked (existing catalog matches)' => $modelsToLink,
            ],
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
                $modelCompatibility = $parsed['model_compatibility'];

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
                    $this->syncModelsFromCompatibilityString($part, $modelCompatibility);

                    continue;
                }

                $payload['created_by'] = $user->id;
                $part = Part::create($payload);
                $imported++;
                $this->syncModelsFromCompatibilityString($part, $modelCompatibility);
            }
        });

        return new CsvImportResult($imported, $updated);
    }

    /**
     * @return \Generator<int, array{
     *     row_number: int,
     *     action: 'create'|'update'|'unchanged'|'restore'|'error',
     *     existing: ?Part,
     *     match_key: string,
     *     display: array<string, mixed>,
     *     payload: array<string, mixed>,
     *     model_compatibility: ?string,
     *     linkable_models: list<string>,
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

                if (count($row) < 7) {
                    continue;
                }

                $partNumber = $this->normalizeIdentifier((string) $this->csvValue($row, $columns, ['part_number', 'partnumber'], 1));
                if ($partNumber === '') {
                    continue;
                }

                $modelCompatibility = trim((string) $this->csvValue($row, $columns, ['models_it_applies_to', 'model_compatibility', 'models'], 6)) ?: null;

                $candidate = [
                    'part_number' => $partNumber,
                    'product_name' => trim((string) $this->csvValue($row, $columns, ['product_name', 'product', 'name'], null)) ?: null,
                    'model_compatibility' => $modelCompatibility,
                    'total_stock' => 0,
                    'retail_price' => $this->csvValue($row, $columns, ['retail_price', 'retail'], 2),
                    'your_price' => $this->csvValue($row, $columns, ['your_price', 'cost'], 3),
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
                        'model_compatibility' => $modelCompatibility,
                        'linkable_models' => [],
                        'changes' => [],
                        'errors' => $validator->errors()->all(),
                    ];

                    continue;
                }

                $payload = $validator->validated();
                $payload['total_stock'] = $payload['total_stock'] ?? 0;

                $display = [
                    'part_number' => $payload['part_number'],
                    'product_name' => $payload['product_name'],
                    'retail_price' => $payload['retail_price'],
                    'your_price' => $payload['your_price'],
                    'cross_reference' => $payload['cross_reference'],
                    'model_compatibility' => $payload['model_compatibility'],
                    'total_stock' => $payload['total_stock'],
                ];

                $linkableModels = $this->resolveLinkableModelNumbers($modelCompatibility);
                $existing = Part::withTrashed()->where('part_number', $payload['part_number'])->first();
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
                        'model_compatibility' => $modelCompatibility,
                        'linkable_models' => $linkableModels,
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
                        'model_compatibility' => $modelCompatibility,
                        'linkable_models' => $linkableModels,
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
                        'model_compatibility' => $modelCompatibility,
                        'linkable_models' => $linkableModels,
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
                    'model_compatibility' => $modelCompatibility,
                    'linkable_models' => $linkableModels,
                    'changes' => $changes,
                    'errors' => [],
                ];
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<string>  $linkableModels
     * @return list<string>
     */
    private function newModelLinkEffects(?Part $part, array $linkableModels): array
    {
        if ($part === null || $linkableModels === []) {
            return [];
        }

        $existingNumbers = $part->models()
            ->pluck('model_number')
            ->map(fn ($number) => (string) $number)
            ->all();

        $newLinks = array_values(array_diff($linkableModels, $existingNumbers));

        return array_map(
            fn (string $modelNumber) => 'Link model '.$modelNumber,
            $newLinks,
        );
    }

    /**
     * @return list<string>
     */
    private function resolveLinkableModelNumbers(?string $compatibility): array
    {
        $numbers = $this->compatibilityNumbers($compatibility);

        if ($numbers === []) {
            return [];
        }

        return Model::query()
            ->whereIn('model_number', $numbers)
            ->orderBy('model_number')
            ->pluck('model_number')
            ->map(fn ($number) => (string) $number)
            ->all();
    }

    private function syncModelsFromCompatibilityString(Part $part, ?string $compatibility): void
    {
        $numbers = $this->compatibilityNumbers($compatibility);

        if ($numbers === []) {
            return;
        }

        $modelIds = Model::query()
            ->whereIn('model_number', $numbers)
            ->pluck('id')
            ->all();

        if ($modelIds === []) {
            return;
        }

        $existingIds = DB::table('model_parts')
            ->where('part_id', $part->id)
            ->distinct()
            ->pluck('model_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $part->syncCompatibleModels(array_values(array_unique([...$existingIds, ...$modelIds])));
    }

    /**
     * @return list<string>
     */
    private function compatibilityNumbers(?string $compatibility): array
    {
        if ($compatibility === null || trim($compatibility) === '') {
            return [];
        }

        return collect(preg_split('/[,;|]+/', $compatibility) ?: [])
            ->map(fn ($value) => $this->normalizeIdentifier((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
