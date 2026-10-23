<?php

namespace App\Support;

use App\Enums\FurnitureQuickAction;
use App\Models\Model as CatalogModel;
use App\Models\TruckAppliance;
use App\Models\User;
use App\Models\UserAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Count-based actions on the units of one furniture model.
 *
 * Checked units are used first; the rest are picked oldest-received first from units the action's rule
 * allows. An override relaxes the rule to a wider pool (never Sold units for selling, never Scrap for
 * the main workflow statuses) after eligible units run out.
 */
class FurnitureQuickActions
{
    public function eligibleRule(FurnitureQuickAction $action, ?string $targetStatus = null): FurnitureUnitRule
    {
        return match ($action) {
            FurnitureQuickAction::Sell => new FurnitureUnitRule(onlyStatuses: ['Ready', 'Show Room']),
            FurnitureQuickAction::ChangeStatus => match ($targetStatus) {
                'Show Room' => new FurnitureUnitRule(onlyStatuses: ['Ready']),
                'Ready' => new FurnitureUnitRule(exceptStatuses: ['Ready', 'Show Room', 'Sold', 'Scrap']),
                default => new FurnitureUnitRule(exceptStatuses: self::withoutDuplicates([(string) $targetStatus, 'Sold', 'Scrap'])),
            },
            FurnitureQuickAction::MarkAssembled => new FurnitureUnitRule(exceptStatuses: ['Sold', 'Scrap'], unassembledOnly: true),
            FurnitureQuickAction::SetLocation => new FurnitureUnitRule(exceptStatuses: ['Sold']),
        };
    }

    public function overrideRule(FurnitureQuickAction $action, ?string $targetStatus = null): FurnitureUnitRule
    {
        return match ($action) {
            FurnitureQuickAction::Sell => new FurnitureUnitRule(exceptStatuses: ['Sold', 'Scrap']),
            FurnitureQuickAction::ChangeStatus => match ($targetStatus) {
                'Show Room', 'Ready' => new FurnitureUnitRule(exceptStatuses: [$targetStatus, 'Sold', 'Scrap']),
                default => new FurnitureUnitRule(exceptStatuses: self::withoutDuplicates([(string) $targetStatus, 'Sold'])),
            },
            FurnitureQuickAction::MarkAssembled => new FurnitureUnitRule(exceptStatuses: ['Sold'], unassembledOnly: true),
            FurnitureQuickAction::SetLocation => $this->eligibleRule($action),
        };
    }

    /**
     * How many units of the model each action could take, keyed by "sell", "assemble", "location"
     * and "status:{target}" for every target status.
     *
     * @param  list<string>  $targetStatuses
     * @return array<string, array{eligible: int, override: int, rule: string, override_rule: string}>
     */
    public function availability(CatalogModel $model, array $targetStatuses): array
    {
        $buckets = FurnitureModelGroups::liveUnits()
            ->where('truck_appliances.model_id', $model->id)
            ->leftJoin('furniture_details', 'furniture_details.truck_appliance_id', '=', 'truck_appliances.id')
            ->selectRaw("COALESCE(NULLIF(truck_appliances.status, ''), 'Triage') as status_name")
            ->selectRaw('(furniture_details.assembled_at IS NOT NULL) as is_assembled')
            ->selectRaw('COUNT(*) as unit_count')
            ->groupByRaw("COALESCE(NULLIF(truck_appliances.status, ''), 'Triage'), (furniture_details.assembled_at IS NOT NULL)")
            ->toBase()
            ->get();

        $summarize = function (FurnitureUnitRule $eligible, FurnitureUnitRule $override) use ($buckets): array {
            $countFor = fn (FurnitureUnitRule $rule): int => (int) $buckets
                ->filter(fn (object $bucket) => $rule->allows($bucket->status_name, (bool) $bucket->is_assembled))
                ->sum('unit_count');

            return [
                'eligible' => $countFor($eligible),
                'override' => $countFor($override),
                'rule' => $eligible->describe(),
                'override_rule' => $override->describe(),
            ];
        };

        $availability = [];

        foreach ([FurnitureQuickAction::Sell, FurnitureQuickAction::MarkAssembled, FurnitureQuickAction::SetLocation] as $action) {
            $availability[$action->value] = $summarize($this->eligibleRule($action), $this->overrideRule($action));
        }

        foreach ($targetStatuses as $target) {
            $availability[FurnitureQuickAction::ChangeStatus->value.':'.$target] = $summarize(
                $this->eligibleRule(FurnitureQuickAction::ChangeStatus, $target),
                $this->overrideRule(FurnitureQuickAction::ChangeStatus, $target),
            );
        }

        return $availability;
    }

    /**
     * @param  list<int>  $checkedUnitIds
     * @param  array{status?: ?string, sold_price?: float|string|null, location?: ?string, notes?: ?string}  $input
     * @return array{units: Collection<int, TruckAppliance>, overridden_ids: list<int>}
     *
     * @throws ValidationException
     */
    public function perform(
        CatalogModel $model,
        FurnitureQuickAction $action,
        int $count,
        array $checkedUnitIds,
        bool $override,
        array $input,
        User $user,
    ): array {
        $targetStatus = $input['status'] ?? null;
        $eligible = $this->eligibleRule($action, $targetStatus);
        $pool = $override ? $this->overrideRule($action, $targetStatus) : $eligible;
        $checkedUnitIds = array_values(array_unique($checkedUnitIds));

        $result = DB::transaction(function () use ($model, $action, $count, $checkedUnitIds, $override, $input, $user, $targetStatus, $eligible, $pool) {
            $units = fn (): Builder => FurnitureModelGroups::liveUnits()
                ->where('truck_appliances.model_id', $model->id)
                ->with('furnitureDetails');

            $checked = $checkedUnitIds === []
                ? new Collection
                : $units()->whereKey($checkedUnitIds)->lockForUpdate()->get();

            if ($checked->count() !== count($checkedUnitIds)) {
                throw ValidationException::withMessages([
                    'unit_ids' => __('Some checked units are no longer part of this model. Reload the page and try again.'),
                ]);
            }

            $blocked = $checked->reject(fn (TruckAppliance $unit) => $pool->allowsUnit($unit));

            if ($blocked->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'unit_ids' => $override
                        ? __('Checked units :labels can’t take this action, even with the override (needs units :rule).', [
                            'labels' => self::labels($blocked),
                            'rule' => $pool->describe(),
                        ])
                        : __('Checked units :labels aren’t eligible (needs units :rule). Uncheck them or confirm the override.', [
                            'labels' => self::labels($blocked),
                            'rule' => $eligible->describe(),
                        ]),
                ]);
            }

            $needed = $count - $checked->count();
            $picked = $checked->concat($this->pick($units(), $eligible, $checked->modelKeys(), $needed));

            if ($override && $picked->count() < $count) {
                $picked = $picked->concat($this->pick($units(), $pool, $picked->modelKeys(), $count - $picked->count()));
            }

            if ($picked->count() < $count) {
                throw ValidationException::withMessages([
                    'count' => $override
                        ? __('Only :available units of this model can take this action (units :rule). Lower the count.', [
                            'available' => $picked->count(),
                            'rule' => $pool->describe(),
                        ])
                        : __('Only :available units are eligible (units :rule). Lower the count or confirm the override.', [
                            'available' => $picked->count(),
                            'rule' => $eligible->describe(),
                        ]),
                ]);
            }

            $overriddenIds = $picked
                ->reject(fn (TruckAppliance $unit) => $eligible->allowsUnit($unit))
                ->modelKeys();

            foreach ($picked as $unit) {
                $notes = implode(' · ', array_filter([
                    filled($input['notes'] ?? null) ? trim($input['notes']) : null,
                    'Quick action from model page',
                    in_array($unit->id, $overriddenIds, true) ? 'status rules overridden' : null,
                ]));

                match ($action) {
                    FurnitureQuickAction::Sell => $unit->applyStatus('Sold', $user, $notes, $input['sold_price'] ?? null),
                    FurnitureQuickAction::ChangeStatus => $unit->applyStatus((string) $targetStatus, $user, $notes),
                    FurnitureQuickAction::MarkAssembled => $unit->markAssembled($user),
                    FurnitureQuickAction::SetLocation => $unit->update([
                        'location' => filled($input['location'] ?? null) ? trim($input['location']) : null,
                        'updated_by' => $user->id,
                    ]),
                };
            }

            return ['units' => $picked->values(), 'overridden_ids' => array_values($overriddenIds)];
        });

        UserAction::log('furniture_quick_action', null, array_filter([
            'model_id' => $model->id,
            'action' => $action->value,
            'status' => $action === FurnitureQuickAction::ChangeStatus ? $targetStatus : null,
            'location' => $action === FurnitureQuickAction::SetLocation ? ($input['location'] ?? null) : null,
            'sold_price_each' => $action === FurnitureQuickAction::Sell ? $input['sold_price'] ?? null : null,
            'count' => $count,
            'unit_ids' => $result['units']->modelKeys(),
            'checked_unit_ids' => $checkedUnitIds,
            'override' => $override,
            'overridden_unit_ids' => $result['overridden_ids'],
        ], fn ($value) => $value !== null && $value !== []), $user);

        return $result;
    }

    /**
     * @param  Collection<int, TruckAppliance>  $units
     */
    public static function labels(Collection $units, int $limit = 10): string
    {
        $labels = $units->map(fn (TruckAppliance $unit) => $unit->unit_label ?: '#'.$unit->id);
        $shown = $labels->take($limit)->implode(', ');
        $remaining = $labels->count() - $limit;

        return $remaining > 0 ? $shown.' and '.$remaining.' more' : $shown;
    }

    /**
     * @param  Builder<TruckAppliance>  $units
     * @param  list<int>  $excludeIds
     * @return Collection<int, TruckAppliance>
     */
    private function pick(Builder $units, FurnitureUnitRule $rule, array $excludeIds, int $limit): Collection
    {
        if ($limit <= 0) {
            return new Collection;
        }

        $rule->apply($units);

        return $units
            ->whereKeyNot($excludeIds)
            ->orderBy('truck_appliances.created_at')
            ->orderBy('truck_appliances.id')
            ->limit($limit)
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  list<string>  $statuses
     * @return list<string>
     */
    private static function withoutDuplicates(array $statuses): array
    {
        return array_values(array_unique(array_filter($statuses)));
    }
}
