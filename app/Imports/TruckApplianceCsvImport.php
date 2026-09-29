<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryStatus;
use App\Models\Model as ApplianceModel;
use App\Models\Subcategory;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use App\Models\UserAction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TruckApplianceCsvImport
{
    /**
     * @var list<string>
     */
    private const DIFF_FIELDS = [
        'unit_label',
        'category',
        'subcategory',
        'brand',
        'model',
        'product_name',
        'quantity',
        'price',
        'serial_number',
        'receiving_condition',
        'msrp',
        'fuel_type',
        'status',
        'sold_price',
        'sold_by',
        'sold_at',
    ];

    /**
     * @var array<string, string>
     */
    private const FIELD_LABELS = [
        'unit_label' => 'Unit Label',
        'category' => 'Category',
        'subcategory' => 'Sub Category',
        'brand' => 'Brand',
        'model' => 'Model',
        'product_name' => 'Product Name',
        'quantity' => 'Quantity',
        'price' => 'Our Cost',
        'serial_number' => 'Serial #',
        'receiving_condition' => 'Receiving Condition',
        'msrp' => 'MSRP',
        'fuel_type' => 'Fuel Type',
        'status' => 'Status',
        'sold_price' => 'Sold Price',
        'sold_by' => 'Sold By',
        'sold_at' => 'Sold Date',
    ];

    public function preview(Truck $truck, string $path, User $user): CsvImportPreview
    {
        $creates = [];
        $updates = [];
        $errors = [];
        $unchangedCount = 0;
        $newCategories = [];
        $newSubcategories = [];
        $newBrands = [];
        $newModels = [];

        $existingCategoryNames = Category::query()->pluck('name')->map(fn ($name) => (string) $name)->all();
        $existingBrandNames = Brand::query()->pluck('name')->map(fn ($name) => (string) $name)->all();
        $existingModels = ApplianceModel::query()->get(['model_number', 'msrp']);
        $existingSubcategories = Subcategory::query()
            ->with('category:id,name')
            ->get()
            ->mapWithKeys(fn (Subcategory $sub) => [
                strtolower(($sub->category?->name ?? '').'|'.$sub->name) => true,
            ])
            ->all();

        foreach ($this->parseRows($truck, $path, $user, writeCatalog: false) as $parsed) {
            if ($parsed['action'] === 'error') {
                $errors[] = [
                    'row_number' => $parsed['row_number'],
                    'match_key' => $parsed['match_key'] !== ''
                        ? $parsed['match_key']
                        : trim(($parsed['display']['unit_label'] ?? '').' / '.($parsed['display']['serial_number'] ?? ''), ' /'),
                    'errors' => $parsed['errors'],
                ];

                continue;
            }

            $this->collectSideEffects(
                $parsed['display'],
                $existingCategoryNames,
                $existingBrandNames,
                $existingModels,
                $existingSubcategories,
                $newCategories,
                $newSubcategories,
                $newBrands,
                $newModels,
            );

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

        sort($newCategories);
        sort($newSubcategories);
        sort($newBrands);
        sort($newModels);

        $sideEffects = array_filter([
            'Categories' => array_values(array_unique($newCategories)),
            'Subcategories' => array_values(array_unique($newSubcategories)),
            'Brands' => array_values(array_unique($newBrands)),
            'Models' => array_values(array_unique($newModels)),
        ]);

        return new CsvImportPreview(
            creates: $creates,
            updates: $updates,
            errors: $errors,
            unchangedCount: $unchangedCount,
            createColumns: self::FIELD_LABELS,
            sideEffects: $sideEffects,
        );
    }

    public function commit(Truck $truck, string $path, User $user): CsvImportResult
    {
        $imported = 0;
        $updated = 0;

        DB::transaction(function () use ($truck, $path, $user, &$imported, &$updated) {
            $existingAppliances = $truck->appliances()->orderBy('id')->get();
            $nextUnitNumber = $this->maxUnitNumber($truck) + 1;

            foreach ($this->parseRows($truck, $path, $user, writeCatalog: true, existingAppliances: $existingAppliances, nextUnitNumber: $nextUnitNumber) as $parsed) {
                if ($parsed['action'] === 'error') {
                    throw ValidationException::withMessages([
                        'csv_file' => ["Row {$parsed['row_number']}: ".implode(' ', $parsed['errors'])],
                    ]);
                }

                $payload = $parsed['payload'];

                if ($parsed['existing']) {
                    $parsed['existing']->update($payload);
                    $updated++;

                    continue;
                }

                $existingAppliances->push($truck->appliances()->create([
                    ...$payload,
                    'created_by' => $user->id,
                ]));
                $imported++;
            }
        });

        return new CsvImportResult($imported, $updated);
    }

    /**
     * @param  Collection<int, TruckAppliance>|null  $existingAppliances
     * @return \Generator<int, array{
     *     row_number: int,
     *     action: 'create'|'update'|'unchanged'|'error',
     *     existing: ?TruckAppliance,
     *     match_key: string,
     *     display: array<string, mixed>,
     *     payload: array<string, mixed>,
     *     changes: list<array{field: string, label: string, from: string, to: string}>,
     *     errors: list<string>
     * }>
     */
    private function parseRows(
        Truck $truck,
        string $path,
        User $user,
        bool $writeCatalog,
        ?Collection $existingAppliances = null,
        ?int $nextUnitNumber = null,
    ): \Generator {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'csv_file' => ['Unable to read the staged CSV file.'],
            ]);
        }

        try {
            $headers = fgetcsv($handle) ?: [];
            $columns = $this->csvColumns($headers);
            $existingAppliances ??= $truck->appliances()->orderBy('id')->with(['category', 'model'])->get();
            $nextUnitNumber ??= $this->maxUnitNumber($truck) + 1;
            $rowNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (count($row) < 11 || collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                    continue;
                }

                $parsed = $this->parseRow(
                    $truck,
                    $row,
                    $columns,
                    $rowNumber,
                    $user,
                    $writeCatalog,
                    $existingAppliances,
                    $nextUnitNumber,
                );

                if ($parsed['action'] === 'create' && ! $writeCatalog) {
                    $existingAppliances->push(new TruckAppliance($parsed['payload']));
                }

                yield $parsed;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $columns
     * @param  Collection<int, TruckAppliance>  $existingAppliances
     * @return array{
     *     row_number: int,
     *     action: 'create'|'update'|'unchanged'|'error',
     *     existing: ?TruckAppliance,
     *     match_key: string,
     *     display: array<string, mixed>,
     *     payload: array<string, mixed>,
     *     changes: list<array{field: string, label: string, from: string, to: string}>,
     *     errors: list<string>
     * }
     */
    private function parseRow(
        Truck $truck,
        array $row,
        array $columns,
        int $rowNumber,
        User $user,
        bool $writeCatalog,
        Collection $existingAppliances,
        int &$nextUnitNumber,
    ): array {
        $unitLabel = trim((string) $this->csvValue($row, $columns, ['unit_label'], 0));
        if ($unitLabel === '') {
            $unitLabel = $this->formatUnitLabel($truck, $nextUnitNumber);
            $nextUnitNumber++;
        }

        $categoryName = trim((string) $this->csvValue($row, $columns, ['category'], 1));
        $subcategory = trim((string) $this->csvValue($row, $columns, ['sub_category'], null));
        $brand = trim((string) $this->csvValue($row, $columns, ['brand'], 2));
        $modelNumber = $this->normalizeIdentifier((string) $this->csvValue($row, $columns, ['model', 'model_number', 'model_'], 3));
        $productName = trim((string) $this->csvValue($row, $columns, ['product_name', 'product'], 4));
        $quantity = (int) $this->csvValue($row, $columns, ['quantity'], 5);
        $ourCost = $this->csvMoney($this->csvValue($row, $columns, ['our_cost', 'cost'], 6));
        $serialNumber = $this->normalizeIdentifier((string) $this->csvValue($row, $columns, ['serial', 'serial_number'], 7));
        $receivingCondition = trim((string) $this->csvValue($row, $columns, ['receiving_condition'], 8));
        $msrp = $this->csvMoney($this->csvValue($row, $columns, ['msrp'], 9));
        $fuelType = trim((string) $this->csvValue($row, $columns, ['fuel_type'], 10));
        $status = trim((string) $this->csvValue($row, $columns, ['status'], null));
        $soldPrice = $this->csvNullableMoney($this->csvValue($row, $columns, ['sold_price'], null));
        $soldBy = trim((string) $this->csvValue($row, $columns, ['sold_by'], null));
        $soldAtRaw = trim((string) $this->csvValue($row, $columns, ['sold_at', 'sold_date'], null));
        $hasSoldInfo = $status === 'Sold'
            || ($soldPrice !== null && $soldPrice > 0)
            || $soldBy !== ''
            || $soldAtRaw !== '';

        $display = [
            'row_number' => $rowNumber,
            'unit_label' => $unitLabel ?: null,
            'category' => $categoryName !== '' ? $categoryName : null,
            'subcategory' => $subcategory !== '' ? $subcategory : null,
            'brand' => $brand !== '' ? $brand : null,
            'model' => $modelNumber !== '' ? $modelNumber : null,
            'product_name' => $productName !== '' ? $productName : null,
            'quantity' => $quantity,
            'price' => $ourCost,
            'serial_number' => $serialNumber !== '' ? $serialNumber : null,
            'receiving_condition' => $receivingCondition !== '' ? $receivingCondition : null,
            'msrp' => $msrp,
            'fuel_type' => $fuelType !== '' ? $fuelType : null,
            'status' => $hasSoldInfo ? 'Sold' : ($status !== '' ? $status : null),
            'sold_price' => $hasSoldInfo ? $soldPrice : null,
            'sold_by' => $hasSoldInfo ? ($soldBy !== '' ? $soldBy : $user->name) : null,
            'sold_at' => $hasSoldInfo ? ($soldAtRaw !== '' ? $soldAtRaw : null) : null,
        ];

        $validator = Validator::make([
            'msrp' => $msrp,
            'receiving_condition' => $receivingCondition ?: null,
            'status' => $hasSoldInfo ? 'Sold' : ($status ?: null),
            'sold_price' => $soldPrice,
            'sold_by' => $soldBy !== '' ? $soldBy : null,
            'sold_at' => $soldAtRaw !== '' ? $soldAtRaw : null,
        ], [
            'msrp' => ['required', 'numeric', 'min:0'],
            'receiving_condition' => ['nullable', Rule::in(TruckAppliance::RECEIVING_CONDITIONS)],
            'status' => ['nullable', Rule::in(InventoryStatus::activeNames())],
            'sold_price' => ['nullable', 'numeric', 'min:0'],
            'sold_by' => ['nullable', 'string', 'max:255'],
            'sold_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return [
                'row_number' => $rowNumber,
                'action' => 'error',
                'existing' => null,
                'match_key' => $unitLabel !== '' ? $unitLabel : $serialNumber,
                'display' => $display,
                'payload' => [],
                'changes' => [],
                'errors' => $validator->errors()->all(),
            ];
        }

        if ($hasSoldInfo) {
            $status = 'Sold';
        }

        $category = null;
        $model = null;

        if ($writeCatalog) {
            $category = $categoryName !== ''
                ? Category::firstOrCreate(
                    ['name' => $categoryName],
                    ['status' => 1, 'created_by' => $user->id, 'updated_by' => $user->id]
                )
                : null;

            if ($category?->id && $subcategory !== '') {
                Subcategory::firstOrCreate(
                    ['name' => $subcategory, 'category_id' => $category->id],
                    ['status' => 1, 'created_by' => $user->id, 'updated_by' => $user->id]
                );
            }

            $model = $modelNumber !== ''
                ? $this->resolveModel($modelNumber, $msrp, $productName, $brand, $category?->id, $user->id)
                : null;

            $this->syncBrand($brand, $user->id);
        } else {
            $category = $categoryName !== ''
                ? Category::query()->where('name', $categoryName)->first()
                : null;
            $model = $modelNumber !== ''
                ? $this->findModel($modelNumber, $msrp)
                : null;
        }

        $existing = $this->findExistingAppliance($existingAppliances, $unitLabel, $serialNumber);
        $serialToStore = $this->serialToStore($existing?->serial_number, $serialNumber);

        $payload = [
            'unit_label' => $unitLabel ?: null,
            'category_id' => $category?->id,
            'subcategory' => $subcategory ?: null,
            'model_id' => $model?->id,
            'serial_number' => $serialToStore,
            'brand' => $brand ?: null,
            'product_name' => $productName ?: null,
            'quantity' => $quantity,
            'price' => $ourCost,
            'msrp' => $msrp,
            'fuel_type' => $fuelType ?: null,
            'receiving_condition' => $receivingCondition ?: null,
            'status' => $status ?: null,
            'updated_by' => $user->id,
        ];

        if ($hasSoldInfo) {
            $payload['status'] = 'Sold';
            $payload['sold_price'] = $soldPrice;
            $payload['sold_by'] = $soldBy !== '' ? $soldBy : $user->name;
            $payload['sold_at'] = $soldAtRaw !== '' ? Carbon::parse($soldAtRaw) : now();
            $payload['location'] = null;
        } else {
            $payload['sold_price'] = null;
            $payload['sold_by'] = null;
            $payload['sold_at'] = null;
        }

        $display['serial_number'] = $serialToStore;
        $display['status'] = $payload['status'];
        $display['sold_price'] = $payload['sold_price'] ?? null;
        $display['sold_by'] = $payload['sold_by'] ?? null;
        $display['sold_at'] = isset($payload['sold_at']) && $payload['sold_at'] !== null
            ? Carbon::parse($payload['sold_at'])->format('Y-m-d H:i')
            : null;

        if (! $existing) {
            return [
                'row_number' => $rowNumber,
                'action' => 'create',
                'existing' => null,
                'match_key' => $unitLabel !== '' ? $unitLabel : $serialNumber,
                'display' => $display,
                'payload' => $payload,
                'changes' => [],
                'errors' => [],
            ];
        }

        $changes = $this->diffChanges($existing, $display, $payload);
        $matchKey = trim((string) $existing->unit_label) === $unitLabel && $unitLabel !== ''
            ? $unitLabel
            : (string) ($existing->serial_number ?: $serialNumber);

        return [
            'row_number' => $rowNumber,
            'action' => $changes === [] ? 'unchanged' : 'update',
            'existing' => $existing,
            'match_key' => $matchKey,
            'display' => $display,
            'payload' => $payload,
            'changes' => $changes,
            'errors' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $display
     * @param  list<string>  $existingCategoryNames
     * @param  list<string>  $existingBrandNames
     * @param  Collection<int, ApplianceModel>  $existingModels
     * @param  array<string, true>  $existingSubcategories
     * @param  list<string>  $newCategories
     * @param  list<string>  $newSubcategories
     * @param  list<string>  $newBrands
     * @param  list<string>  $newModels
     */
    private function collectSideEffects(
        array $display,
        array $existingCategoryNames,
        array $existingBrandNames,
        Collection $existingModels,
        array $existingSubcategories,
        array &$newCategories,
        array &$newSubcategories,
        array &$newBrands,
        array &$newModels,
    ): void {
        $categoryName = $display['category'] ?? null;
        $subcategory = $display['subcategory'] ?? null;
        $brand = $display['brand'] ?? null;
        $modelNumber = $display['model'] ?? null;
        $msrp = (float) ($display['msrp'] ?? 0);

        if (is_string($categoryName) && $categoryName !== '' && ! in_array($categoryName, $existingCategoryNames, true)) {
            $newCategories[] = $categoryName;
        }

        if (is_string($categoryName) && $categoryName !== '' && is_string($subcategory) && $subcategory !== '') {
            $key = strtolower($categoryName.'|'.$subcategory);
            if (! isset($existingSubcategories[$key])) {
                $newSubcategories[] = $categoryName.' / '.$subcategory;
            }
        }

        if (is_string($brand) && $brand !== '' && ! in_array($brand, $existingBrandNames, true)) {
            $newBrands[] = $brand;
        }

        if (is_string($modelNumber) && $modelNumber !== '') {
            $exists = $existingModels->contains(
                fn (ApplianceModel $model) => $model->model_number === $modelNumber
                    && abs((float) $model->msrp - $msrp) < 0.005
            );

            if (! $exists) {
                $newModels[] = $modelNumber.' ($'.number_format($msrp, 2).')';
            }
        }
    }

    /**
     * @param  array<string, mixed>  $display
     * @param  array<string, mixed>  $payload
     * @return list<array{field: string, label: string, from: string, to: string}>
     */
    private function diffChanges(TruckAppliance $existing, array $display, array $payload): array
    {
        $current = [
            'unit_label' => $existing->unit_label,
            'category' => $existing->category?->name,
            'subcategory' => $existing->subcategory,
            'brand' => $existing->brand,
            'model' => $existing->model?->model_number,
            'product_name' => $existing->product_name,
            'quantity' => $existing->quantity,
            'price' => $existing->price,
            'serial_number' => $existing->serial_number !== null ? trim((string) $existing->serial_number) : null,
            'receiving_condition' => $existing->receiving_condition,
            'msrp' => $existing->msrp,
            'fuel_type' => $existing->fuel_type,
            'status' => $existing->status,
            'sold_price' => $existing->sold_price,
            'sold_by' => $existing->sold_by,
            'sold_at' => $existing->sold_at?->format('Y-m-d H:i'),
        ];

        $incoming = [
            'unit_label' => $display['unit_label'],
            'category' => $display['category'],
            'subcategory' => $display['subcategory'],
            'brand' => $display['brand'],
            'model' => $display['model'],
            'product_name' => $display['product_name'],
            'quantity' => $display['quantity'],
            'price' => $payload['price'],
            'serial_number' => $payload['serial_number'],
            'receiving_condition' => $payload['receiving_condition'],
            'msrp' => $payload['msrp'],
            'fuel_type' => $payload['fuel_type'],
            'status' => $payload['status'],
            'sold_price' => $payload['sold_price'] ?? null,
            'sold_by' => $payload['sold_by'] ?? null,
            'sold_at' => isset($payload['sold_at']) && $payload['sold_at'] !== null
                ? Carbon::parse($payload['sold_at'])->format('Y-m-d H:i')
                : null,
        ];

        $changes = [];

        foreach (self::DIFF_FIELDS as $field) {
            if ($this->valuesEqual($current[$field] ?? null, $incoming[$field] ?? null)) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'label' => self::FIELD_LABELS[$field],
                'from' => $this->formatDisplayValue($current[$field] ?? null),
                'to' => $this->formatDisplayValue($incoming[$field] ?? null),
            ];
        }

        return $changes;
    }

    private function valuesEqual(mixed $left, mixed $right): bool
    {
        if (is_numeric($left) && is_numeric($right)) {
            return abs((float) $left - (float) $right) < 0.005;
        }

        $leftNormalized = $left === null || $left === '' ? null : (string) $left;
        $rightNormalized = $right === null || $right === '' ? null : (string) $right;

        return $leftNormalized === $rightNormalized;
    }

    private function formatDisplayValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_float($value) || (is_string($value) && is_numeric($value) && str_contains((string) $value, '.'))) {
            return number_format((float) $value, 2);
        }

        return (string) $value;
    }

    private function syncBrand(?string $brand, int $userId): void
    {
        $brand = trim((string) $brand);

        if ($brand === '') {
            return;
        }

        Brand::firstOrCreate(
            ['name' => $brand],
            [
                'status' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    private function findModel(string $modelNumber, float $msrp): ?ApplianceModel
    {
        return ApplianceModel::query()
            ->where('model_number', $modelNumber)
            ->get()
            ->first(fn (ApplianceModel $model) => abs((float) $model->msrp - $msrp) < 0.005);
    }

    private function resolveModel(string $modelNumber, float $msrp, ?string $productName, ?string $brand, ?int $categoryId, int $userId): ApplianceModel
    {
        $model = $this->findModel($modelNumber, $msrp);

        if ($model) {
            $updates = ['updated_by' => $userId];

            if (! $model->product_name && $productName) {
                $updates['product_name'] = $productName;
            }

            if (! $model->brand && $brand) {
                $updates['brand'] = $brand;
            }

            if (! $model->category_id && $categoryId) {
                $updates['category_id'] = $categoryId;
            }

            if (count($updates) > 1) {
                $model->update($updates);
            }

            return $model;
        }

        $model = ApplianceModel::query()->create([
            'model_number' => $modelNumber,
            'product_name' => $productName ?: null,
            'brand' => $brand ?: null,
            'category_id' => $categoryId,
            'msrp' => number_format($msrp, 2, '.', ''),
            'status' => 1,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        UserAction::log('add_model', null, [
            'model_id' => $model->id,
            'model_number' => $modelNumber,
            'category_id' => $categoryId,
            'from_truck' => true,
        ]);

        return $model;
    }

    /**
     * @param  Collection<int, TruckAppliance>  $appliances
     */
    private function findExistingAppliance(Collection $appliances, string $unitLabel, string $serialNumber): ?TruckAppliance
    {
        if ($unitLabel !== '') {
            $byLabel = $appliances->first(
                fn (TruckAppliance $appliance) => trim((string) $appliance->unit_label) === $unitLabel
            );

            if ($byLabel) {
                return $byLabel;
            }
        }

        if ($serialNumber === '') {
            return null;
        }

        return $appliances->first(
            fn (TruckAppliance $appliance) => $this->serialsReferToSameUnit($appliance->serial_number, $serialNumber)
        );
    }

    private function serialToStore(?string $stored, string $incoming): ?string
    {
        if ($incoming === '') {
            $trimmed = trim((string) $stored);

            return $trimmed === '' ? null : $trimmed;
        }

        if ($stored === null || trim($stored) === '') {
            return $incoming;
        }

        $storedTrimmed = trim($stored);

        if ($storedTrimmed === $incoming
            || $this->incomingSerialDropsLeadingZeros($storedTrimmed, $incoming)
            || $this->incomingSerialIsExcelNotation($storedTrimmed, $incoming)) {
            return $storedTrimmed;
        }

        return $incoming;
    }

    private function serialsReferToSameUnit(?string $stored, string $incoming): bool
    {
        if ($incoming === '' || $stored === null || trim($stored) === '') {
            return false;
        }

        $storedTrimmed = trim($stored);

        return $storedTrimmed === $incoming
            || $this->incomingSerialDropsLeadingZeros($storedTrimmed, $incoming)
            || $this->incomingSerialIsExcelNotation($storedTrimmed, $incoming);
    }

    private function incomingSerialDropsLeadingZeros(string $stored, string $incoming): bool
    {
        if ($stored === $incoming || ! ctype_digit($stored) || ! ctype_digit($incoming)) {
            return false;
        }

        return ltrim($stored, '0') === ltrim($incoming, '0');
    }

    private function incomingSerialIsExcelNotation(string $stored, string $incoming): bool
    {
        if (! ctype_digit($stored) || ! preg_match('/^(\d+)E(\d+)$/i', $incoming, $matches)) {
            return false;
        }

        $coefficient = $matches[1];
        $exponent = (int) $matches[2];

        if (strlen($stored) !== $exponent + 1) {
            return false;
        }

        $prefix = substr($stored, 0, strlen($coefficient));

        if ($prefix === $coefficient) {
            return true;
        }

        return strlen($prefix) === strlen($coefficient)
            && abs((int) $prefix - (int) $coefficient) <= 1;
    }

    private function normalizeIdentifier(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9-]/', '', strtoupper(trim($value))) ?? '');
    }

    private function maxUnitNumber(Truck $truck): int
    {
        return $truck->appliances()
            ->pluck('unit_label')
            ->map(function (?string $label) {
                preg_match('/(\d+)$/', (string) $label, $matches);

                return isset($matches[1]) ? (int) $matches[1] : 0;
            })
            ->max() ?? 0;
    }

    private function formatUnitLabel(Truck $truck, int $number): string
    {
        return trim((string) $truck->name).'-'.sprintf('%03d', $number);
    }

    /**
     * @param  array<int, mixed>  $headers
     * @return array<string, int>
     */
    private function csvColumns(array $headers): array
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
    private function csvValue(array $row, array $columns, array $keys, ?int $fallbackIndex = null): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $columns)) {
                return $row[$columns[$key]] ?? null;
            }
        }

        return $fallbackIndex !== null ? ($row[$fallbackIndex] ?? null) : null;
    }

    private function csvMoney(mixed $value): float
    {
        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return $normalized === '' || $normalized === '-' ? 0.0 : (float) $normalized;
    }

    private function csvNullableMoney(mixed $value): ?float
    {
        if (trim((string) $value) === '') {
            return null;
        }

        return $this->csvMoney($value);
    }
}
