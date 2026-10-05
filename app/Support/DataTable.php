<?php

namespace App\Support;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
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

    public function sortPreferenceKey(): string
    {
        return 'data_table.sort.'.$this->storageKey;
    }

    public function columnsPreferenceKey(): string
    {
        return 'data_table.columns.'.$this->storageKey;
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
     * Stored column visibility for this table, keyed by column key.
     *
     * @return array<string, bool>|null
     */
    public function columnVisibilityFor(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $stored = UserPreferences::get($user, $this->columnsPreferenceKey());

        if (! is_array($stored)) {
            return null;
        }

        $allowedKeys = collect($this->columns)->pluck('key')->all();
        $visibility = [];

        foreach ($stored as $key => $visible) {
            if (! is_string($key) || ! in_array($key, $allowedKeys, true)) {
                continue;
            }

            $visibility[$key] = (bool) $visible;
        }

        return $visibility === [] ? null : $visibility;
    }

    /**
     * Persist, clear, or restore the user's remembered sort for this table.
     */
    public function resolveSortRequest(Request $request, ?User $user): Request|RedirectResponse
    {
        if ($user === null) {
            return $request;
        }

        $preferenceKey = $this->sortPreferenceKey();

        if ($request->has('sort')) {
            if ($request->filled('sort')) {
                $sort = (string) $request->get('sort');
                $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';

                if ($this->isValidSortColumn($sort)) {
                    UserPreferences::put($user, $preferenceKey, [
                        'sort' => $sort,
                        'direction' => $direction,
                    ]);
                }

                return $request;
            }

            UserPreferences::forget($user, $preferenceKey);

            return $request;
        }

        $preferred = UserPreferences::get($user, $preferenceKey);

        if (! is_array($preferred)) {
            return $request;
        }

        $sort = $preferred['sort'] ?? null;
        $direction = ($preferred['direction'] ?? null) === 'asc' ? 'asc' : 'desc';

        if (! is_string($sort) || ! $this->isValidSortColumn($sort)) {
            UserPreferences::forget($user, $preferenceKey);

            return $request;
        }

        return redirect()->to($request->fullUrlWithQuery([
            'sort' => $sort,
            'direction' => $direction,
        ]));
    }

    /**
     * @return array{sort: ?string, direction: 'asc'|'desc'}
     */
    public function sortState(Request $request): array
    {
        $sort = $request->get('sort');

        return [
            'sort' => filled($sort) ? (string) $sort : null,
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

    private function isValidSortColumn(string $sort): bool
    {
        $column = collect($this->columns)->firstWhere('key', $sort);

        return is_array($column)
            && ($column['sortable'] ?? true)
            && isset($column['sort']);
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
            if ($columnName instanceof Expression) {
                $grammar = $query->getQuery()->getGrammar();
                $query->orderByRaw($columnName->getValue($grammar).' '.$defaultDirection);

                continue;
            }

            $query->orderBy($columnName, $defaultDirection);
        }
    }
}
