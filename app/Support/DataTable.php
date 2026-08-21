<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class DataTable
{
    /**
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array{0: string, 1: 'asc'|'desc'}  $defaultSort
     */
    public function __construct(
        public readonly string $storageKey,
        public readonly array $columns,
        public readonly array $defaultSort,
    ) {}

    public function storageKey(): string
    {
        return $this->storageKey;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function columnsForView(): array
    {
        return collect($this->columns)
            ->map(fn (array $column) => [
                'key' => $column['key'],
                'label' => $column['label'],
                'default' => $column['default'] ?? true,
                'align' => $column['align'] ?? 'left',
                'truncate' => $column['truncate'] ?? false,
                'sortable' => $column['sortable'] ?? true,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{sort: ?string, direction: 'asc'|'desc'}
     */
    public function sortState(Request $request): array
    {
        return [
            'sort' => $request->get('sort'),
            'direction' => $request->get('direction') === 'asc' ? 'asc' : 'desc',
        ];
    }

    public function applySorting(Builder|Relation $query, Request $request): void
    {
        $sort = $request->get('sort');
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';

        $column = collect($this->columns)->firstWhere('key', $sort);

        if (! is_array($column) || ! ($column['sortable'] ?? true) || ! isset($column['sort'])) {
            $this->applyDefaultSort($query);

            return;
        }

        $sortHandler = $column['sort'];

        if (is_string($sortHandler)) {
            $query->orderBy($sortHandler, $direction);

            return;
        }

        if ($sortHandler instanceof Closure) {
            $sortHandler($query, $direction);
        }
    }

    /**
     * @return array<int, array{0: string, 1: 'asc'|'desc'}>
     */
    private function defaultSorts(): array
    {
        if (isset($this->defaultSort[0]) && is_array($this->defaultSort[0])) {
            return $this->defaultSort;
        }

        return [$this->defaultSort];
    }

    private function applyDefaultSort(Builder|Relation $query): void
    {
        foreach ($this->defaultSorts() as [$columnName, $defaultDirection]) {
            $query->orderBy($columnName, $defaultDirection);
        }
    }
}
