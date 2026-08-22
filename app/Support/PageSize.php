<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class PageSize
{
    /** @var list<int> */
    public const DEFAULT_OPTIONS = [25, 50, 100, 250, 500, 1000];

    /**
     * @param  list<int>  $options
     * @return int|'all'
     */
    public static function resolve(
        Request $request,
        string $name = 'limit',
        int $default = 25,
        array $options = self::DEFAULT_OPTIONS,
        bool $allowAll = true,
    ): int|string {
        $value = $request->input($name, $default);

        if ($allowAll && $value === 'all') {
            return 'all';
        }

        $size = (int) $value;

        return in_array($size, $options, true) ? $size : $default;
    }

    /**
     * @param  Builder|Relation  $query
     * @param  list<int>  $options
     */
    public static function paginate(
        Builder|Relation $query,
        Request $request,
        string $name = 'limit',
        int $default = 25,
        array $options = self::DEFAULT_OPTIONS,
        bool $allowAll = true,
        string $pageName = 'page',
    ): LengthAwarePaginator {
        $limit = self::resolve($request, $name, $default, $options, $allowAll);

        if ($limit === 'all') {
            return $query->paginate($query->count() ?: 1, ['*'], $pageName)->withQueryString();
        }

        return $query->paginate($limit, ['*'], $pageName)->withQueryString();
    }
}
