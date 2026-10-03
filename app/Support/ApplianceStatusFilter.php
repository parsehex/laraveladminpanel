<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ApplianceStatusFilter
{
    /**
     * Apply selected inventory status filters, treating Triage as null/empty/Triage.
     *
     * @param  iterable<int, mixed>  $statuses
     */
    public static function apply(Builder $query, iterable $statuses, string $column = 'status'): void
    {
        $statuses = self::normalize($statuses);

        if ($statuses->isEmpty()) {
            return;
        }

        $query->where(function (Builder $statusQuery) use ($statuses, $column) {
            $explicitStatuses = $statuses->reject(fn (string $status) => $status === 'Triage')->values();

            if ($explicitStatuses->isNotEmpty()) {
                $statusQuery->whereIn($column, $explicitStatuses->all());
            }

            if ($statuses->contains('Triage')) {
                $method = $explicitStatuses->isNotEmpty() ? 'orWhere' : 'where';
                $statusQuery->{$method}(function (Builder $triageQuery) use ($column) {
                    $triageQuery->whereNull($column)
                        ->orWhere($column, '')
                        ->orWhere($column, 'Triage');
                });
            }
        });
    }

    /**
     * @param  iterable<int, mixed>  $statuses
     * @return Collection<int, string>
     */
    public static function normalize(iterable $statuses): Collection
    {
        return collect($statuses)
            ->map(fn ($status) => trim((string) $status))
            ->filter()
            ->values();
    }
}
