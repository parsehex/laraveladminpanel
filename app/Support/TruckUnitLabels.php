<?php

namespace App\Support;

use App\Models\Truck;
use App\Models\TruckAppliance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TruckUnitLabels
{
    public const SORT_UNIT_LABEL = 'unit_label';

    /** @var array<string, string> */
    public const SORTS = [
        self::SORT_UNIT_LABEL => 'Unit Label',
        'status' => 'Status',
        'category' => 'Category',
        'serial_number' => 'Serial #',
        'product_name' => 'Product Name',
        'id' => 'Added order',
    ];

    public static function format(string $truckName, int $number): string
    {
        return trim($truckName).'-'.sprintf('%03d', $number);
    }

    /**
     * @return array{changes: list<array{id: int, from: ?string, to: string, serial_number: ?string}>, unchanged: int}
     */
    public function plan(Truck $truck, string $sort): array
    {
        if (! array_key_exists($sort, self::SORTS)) {
            throw new InvalidArgumentException('Unknown unit label sort.');
        }

        $appliances = $truck->appliances()->with('category:id,name')->get();
        $assignments = $sort === self::SORT_UNIT_LABEL
            ? $this->preserveNumbers($truck, $appliances)
            : $this->sequence($truck, $this->sorted($appliances, $sort));

        $changes = [];
        $unchanged = 0;

        foreach ($assignments as $assignment) {
            $from = trim((string) $assignment['appliance']->unit_label);

            if ($from === $assignment['label']) {
                $unchanged++;

                continue;
            }

            $changes[] = [
                'id' => $assignment['appliance']->id,
                'from' => $from !== '' ? $from : null,
                'to' => $assignment['label'],
                'serial_number' => $assignment['appliance']->serial_number,
            ];
        }

        return [
            'changes' => $changes,
            'unchanged' => $unchanged,
        ];
    }

    /**
     * @return int Number of labels updated.
     */
    public function apply(Truck $truck, string $sort, int $userId): int
    {
        $changes = $this->plan($truck, $sort)['changes'];

        DB::transaction(function () use ($changes, $userId): void {
            foreach ($changes as $change) {
                TruckAppliance::query()->whereKey($change['id'])->update([
                    'unit_label' => $change['to'],
                    'updated_by' => $userId,
                ]);
            }
        });

        return count($changes);
    }

    /**
     * @param  Collection<int, TruckAppliance>  $appliances
     * @return list<array{appliance: TruckAppliance, label: string}>
     */
    private function preserveNumbers(Truck $truck, Collection $appliances): array
    {
        /** @var array<int, array{id: int, exact: bool}> $claimed */
        $claimed = [];

        foreach ($appliances as $appliance) {
            $number = $this->trailingNumber($appliance->unit_label);

            if ($number === null) {
                continue;
            }

            $formatted = self::format((string) $truck->name, $number);
            $exact = trim((string) $appliance->unit_label) === $formatted;

            if (! isset($claimed[$number])) {
                $claimed[$number] = ['id' => $appliance->id, 'exact' => $exact];

                continue;
            }

            $winner = $claimed[$number];
            $thisWins = ($exact && ! $winner['exact'])
                || ($exact === $winner['exact'] && $appliance->id < $winner['id']);

            if ($thisWins) {
                $claimed[$number] = ['id' => $appliance->id, 'exact' => $exact];
            }
        }

        /** @var array<int, int> $numberById */
        $numberById = [];

        foreach ($claimed as $number => $winner) {
            $numberById[$winner['id']] = $number;
        }

        $next = 1;

        foreach ($appliances->sortBy('id') as $appliance) {
            if (isset($numberById[$appliance->id])) {
                continue;
            }

            while (isset($claimed[$next])) {
                $next++;
            }

            $numberById[$appliance->id] = $next;
            $claimed[$next] = ['id' => $appliance->id, 'exact' => false];
            $next++;
        }

        return $appliances
            ->sortBy(fn (TruckAppliance $appliance): int => $numberById[$appliance->id])
            ->map(fn (TruckAppliance $appliance): array => [
                'appliance' => $appliance,
                'label' => self::format((string) $truck->name, $numberById[$appliance->id]),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, TruckAppliance>  $appliances
     * @return list<array{appliance: TruckAppliance, label: string}>
     */
    private function sequence(Truck $truck, Collection $appliances): array
    {
        $number = 1;
        $assignments = [];

        foreach ($appliances as $appliance) {
            $assignments[] = [
                'appliance' => $appliance,
                'label' => self::format((string) $truck->name, $number),
            ];
            $number++;
        }

        return $assignments;
    }

    /**
     * @param  Collection<int, TruckAppliance>  $appliances
     * @return Collection<int, TruckAppliance>
     */
    private function sorted(Collection $appliances, string $sort): Collection
    {
        return $appliances
            ->sortBy([
                [fn (TruckAppliance $left, TruckAppliance $right): int => $this->sortValue($left, $sort) <=> $this->sortValue($right, $sort), 'asc'],
                [fn (TruckAppliance $left, TruckAppliance $right): int => $left->id <=> $right->id, 'asc'],
            ])
            ->values();
    }

    private function sortValue(TruckAppliance $appliance, string $sort): string
    {
        $value = match ($sort) {
            'status' => $appliance->status ?: 'Triage',
            'category' => $appliance->category?->name,
            'serial_number' => $appliance->serial_number,
            'product_name' => $appliance->product_name,
            'id' => sprintf('%010d', $appliance->id),
            default => $appliance->unit_label,
        };

        $value = trim((string) $value);

        if ($value === '') {
            return '￿';
        }

        return strtolower($value);
    }

    private function trailingNumber(?string $label): ?int
    {
        if (preg_match('/(\d+)$/', trim((string) $label), $matches) !== 1) {
            return null;
        }

        $number = (int) $matches[1];

        return $number > 0 ? $number : null;
    }
}
