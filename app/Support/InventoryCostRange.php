<?php

namespace App\Support;

use App\Models\TruckAppliance;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Date range for the inventory cost panels: units added between the start and end dates, with status as of the end date.
 */
class InventoryCostRange
{
    /** @var array<string, string> */
    public const PRESETS = [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
        'all' => 'All',
    ];

    /** @var list<string> */
    public const QUERY_KEYS = ['cost_period', 'cost_from', 'cost_date'];

    public function __construct(
        public readonly ?string $period,
        public readonly ?CarbonInterface $from,
        public readonly ?CarbonInterface $to,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $period = $request->string('cost_period')->toString();
        $now = now();

        if (array_key_exists($period, self::PRESETS)) {
            return match ($period) {
                'daily' => new self($period, $now->copy()->subDay()->startOfDay(), $now->copy()->endOfDay()),
                'weekly' => new self($period, $now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay()),
                'monthly' => new self($period, $now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()),
                'yearly' => new self($period, $now->copy()->subDays(365)->startOfDay(), $now->copy()->endOfDay()),
                default => new self($period, null, null),
            };
        }

        $from = rescue(fn () => $request->date('cost_from'), null, false);
        $to = rescue(fn () => $request->date('cost_date'), null, false);

        if (! $from && ! $to) {
            return new self(null, null, null);
        }

        if ($from && $to && $from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return new self('custom', $from?->copy()->startOfDay(), $to?->copy()->endOfDay());
    }

    public function isSelected(): bool
    {
        return $this->period !== null;
    }

    public function label(): string
    {
        return match ($this->period) {
            'daily' => 'Last 1 day',
            'weekly' => 'Last 7 days',
            'monthly' => 'Last 30 days',
            'yearly' => 'Last 365 days',
            'custom' => match (true) {
                $this->from && $this->to => $this->from->format('m/d/Y').' - '.$this->to->format('m/d/Y').' (EOD)',
                $this->from !== null => 'Since '.$this->from->format('m/d/Y'),
                default => 'Through '.$this->to->format('m/d/Y').' (EOD)',
            },
            default => 'All time',
        };
    }

    /**
     * Statuses come from inventory_status_histories only for end dates before today; otherwise the live status is used.
     */
    public function usesStatusHistory(): bool
    {
        return $this->to !== null && $this->to->lessThan(now()->startOfDay());
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function presetUrl(string $url, array $query, string $period): string
    {
        return $url.'?'.Arr::query([...Arr::except($query, [...self::QUERY_KEYS, 'page']), 'cost_period' => $period]);
    }

    /**
     * One row per unit: truck_id, current_status, base_cost, total_parts_cost.
     *
     * @param  Closure(Builder): mixed  $scope
     */
    public function rowsQuery(Closure $scope): Builder
    {
        $partsCostSql = TruckAppliance::partsCostSql('truck_appliances');

        $query = DB::table('truck_appliances')
            ->select('truck_appliances.truck_id')
            ->selectRaw('COALESCE(truck_appliances.price, 0) as base_cost')
            ->selectRaw("{$partsCostSql} as total_parts_cost")
            ->whereNull('truck_appliances.deleted_at')
            ->when($this->from, fn (Builder $query) => $query->where('truck_appliances.created_at', '>=', $this->from))
            ->when($this->to, fn (Builder $query) => $query->where('truck_appliances.created_at', '<=', $this->to));

        if ($this->usesStatusHistory()) {
            $latestStatusRows = DB::table('inventory_status_histories')
                ->select('truck_appliance_id', 'status')
                ->selectRaw('ROW_NUMBER() OVER(PARTITION BY truck_appliance_id ORDER BY created_at DESC) as row_number')
                ->where('created_at', '<=', $this->to);

            $rankedStatusRows = DB::query()
                ->fromSub($latestStatusRows, 'ranked_status')
                ->where('row_number', 1);

            $query->joinSub($rankedStatusRows, 'latest_status', function ($join) {
                $join->on('latest_status.truck_appliance_id', '=', 'truck_appliances.id');
            })->selectRaw("COALESCE(NULLIF(latest_status.status, ''), 'Triage') as current_status");
        } else {
            $query->selectRaw("COALESCE(NULLIF(truck_appliances.status, ''), 'Triage') as current_status");
        }

        $scope($query);

        return $query;
    }
}
