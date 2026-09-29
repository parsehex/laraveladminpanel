<?php

namespace App\Imports;

use App\Imports\Concerns\ParsesCsv;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TruckCsvImport
{
    use ParsesCsv;

    /**
     * @var array<string, string>
     */
    private const FIELD_LABELS = [
        'name' => 'Name',
        'units_on_truck' => 'Units on Truck',
        'cost_of_truck' => 'Cost of Truck',
        'shipping_cost' => 'Shipping Cost',
        'arrival_date' => 'Arrival Date',
        'status' => 'Status',
        'notes' => 'Notes',
    ];

    public function preview(string $path): CsvImportPreview
    {
        $creates = [];
        $updates = [];
        $errors = [];
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

            if ($parsed['action'] === 'create') {
                $creates[] = $parsed['display'];

                continue;
            }

            if ($parsed['changes'] === []) {
                $unchangedCount++;

                continue;
            }

            $updates[] = [
                'row_number' => $parsed['row_number'],
                'match_key' => $parsed['match_key'],
                'changes' => $parsed['changes'],
            ];
        }

        return new CsvImportPreview(
            creates: $creates,
            updates: $updates,
            errors: $errors,
            unchangedCount: $unchangedCount,
            createColumns: self::FIELD_LABELS,
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
                    $parsed['existing']->update($payload);
                    $updated++;

                    UserAction::log('edit_truck', null, [
                        'truck_id' => $parsed['existing']->id,
                        'name' => $parsed['existing']->name,
                        'from_import' => true,
                    ]);

                    continue;
                }

                $truck = Truck::create([
                    ...$payload,
                    'created_by' => $user->id,
                ]);
                $imported++;

                UserAction::log('add_truck', null, [
                    'truck_id' => $truck->id,
                    'name' => $truck->name,
                    'from_import' => true,
                ]);
            }
        });

        return new CsvImportResult($imported, $updated);
    }

    /**
     * @return \Generator<int, array{
     *     row_number: int,
     *     action: 'create'|'update'|'unchanged'|'error',
     *     existing: ?Truck,
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
            /** @var array<string, array{display: array<string, mixed>, payload: array<string, mixed>}> $pendingByName */
            $pendingByName = [];

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                    continue;
                }

                $name = trim((string) $this->csvValue($row, $columns, ['name', 'truck_name'], 0));
                if ($name === '') {
                    continue;
                }

                $arrivalDate = trim((string) $this->csvValue($row, $columns, ['arrival_date', 'record_date', 'date'], 4));
                $status = strtolower(trim((string) $this->csvValue($row, $columns, ['status'], 5)));

                $candidate = [
                    'name' => $name,
                    'units_on_truck' => (int) $this->csvValue($row, $columns, ['units_on_truck', 'units', 'count_units'], 1),
                    'cost_of_truck' => $this->csvMoney($this->csvValue($row, $columns, ['cost_of_truck', 'purchase_price', 'cost'], 2)),
                    'shipping_cost' => $this->csvMoney($this->csvValue($row, $columns, ['shipping_cost', 'shipping'], 3)),
                    'arrival_date' => $arrivalDate !== '' ? $arrivalDate : now()->toDateString(),
                    'status' => $status !== '' ? $status : 'active',
                    'notes' => trim((string) $this->csvValue($row, $columns, ['notes'], 6)) ?: null,
                ];

                $validator = Validator::make($candidate, [
                    'name' => ['required', 'string', 'max:255'],
                    'units_on_truck' => ['required', 'integer', 'min:0'],
                    'cost_of_truck' => ['required', 'numeric', 'min:0'],
                    'shipping_cost' => ['nullable', 'numeric', 'min:0'],
                    'arrival_date' => ['required', 'date'],
                    'status' => ['required', Rule::in(['active', 'inactive', 'breakdown'])],
                    'notes' => ['nullable', 'string', 'max:5000'],
                ]);

                if ($validator->fails()) {
                    yield [
                        'row_number' => $rowNumber,
                        'action' => 'error',
                        'existing' => null,
                        'match_key' => $name,
                        'display' => $candidate,
                        'payload' => [],
                        'changes' => [],
                        'errors' => $validator->errors()->all(),
                    ];

                    continue;
                }

                $payload = $validator->validated();
                $payload['shipping_cost'] = $payload['shipping_cost'] ?? 0;

                $display = [
                    'name' => $payload['name'],
                    'units_on_truck' => $payload['units_on_truck'],
                    'cost_of_truck' => $payload['cost_of_truck'],
                    'shipping_cost' => $payload['shipping_cost'],
                    'arrival_date' => $payload['arrival_date'],
                    'status' => $payload['status'],
                    'notes' => $payload['notes'],
                ];

                $existing = Truck::query()->where('name', $payload['name'])->first();
                $pending = $pendingByName[$payload['name']] ?? null;

                if (! $existing && $pending !== null) {
                    $changes = $this->diffFields($pending['display'], $display, self::FIELD_LABELS);
                    $pendingByName[$payload['name']] = [
                        'display' => $display,
                        'payload' => $payload,
                    ];

                    yield [
                        'row_number' => $rowNumber,
                        'action' => $changes === [] ? 'unchanged' : 'update',
                        'existing' => null,
                        'match_key' => $payload['name'],
                        'display' => $display,
                        'payload' => $payload,
                        'changes' => $changes,
                        'errors' => [],
                    ];

                    continue;
                }

                if (! $existing) {
                    $pendingByName[$payload['name']] = [
                        'display' => $display,
                        'payload' => $payload,
                    ];

                    yield [
                        'row_number' => $rowNumber,
                        'action' => 'create',
                        'existing' => null,
                        'match_key' => $payload['name'],
                        'display' => $display,
                        'payload' => $payload,
                        'changes' => [],
                        'errors' => [],
                    ];

                    continue;
                }

                $pendingByName[$payload['name']] = [
                    'display' => $display,
                    'payload' => $payload,
                ];

                $current = [
                    'name' => $existing->name,
                    'units_on_truck' => $existing->units_on_truck,
                    'cost_of_truck' => $existing->cost_of_truck,
                    'shipping_cost' => $existing->shipping_cost,
                    'arrival_date' => $existing->arrival_date?->format('Y-m-d') ?? (string) $existing->arrival_date,
                    'status' => $existing->status,
                    'notes' => $existing->notes,
                ];

                $changes = $this->diffFields($current, $display, self::FIELD_LABELS);

                yield [
                    'row_number' => $rowNumber,
                    'action' => $changes === [] ? 'unchanged' : 'update',
                    'existing' => $existing,
                    'match_key' => $payload['name'],
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
}
