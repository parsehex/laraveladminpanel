<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class PageSize
{
    /** @var list<int> */
    public const DEFAULT_OPTIONS = [25, 50, 100, 250, 500, 1000];

    public const PREFERENCE_KEY = 'page_size';

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
        ?User $user = null,
    ): int|string {
        $user ??= $request->user();
        $preferenceKey = self::PREFERENCE_KEY;

        if ($request->has($name)) {
            $provided = self::normalize($request->input($name), $options, $allowAll, null);

            if ($provided !== null) {
                if ($user !== null) {
                    UserPreferences::put($user, $preferenceKey, [
                        'limit' => $provided,
                    ]);
                }

                return $provided;
            }

            return $default;
        }

        if ($user !== null) {
            $preferred = UserPreferences::get($user, $preferenceKey);

            if (is_array($preferred) && array_key_exists('limit', $preferred)) {
                $resolved = self::normalize($preferred['limit'], $options, $allowAll, null);

                if ($resolved !== null) {
                    return $resolved;
                }

                UserPreferences::forget($user, $preferenceKey);
            }
        }

        return $default;
    }

    /**
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

    /**
     * @param  list<int>  $options
     * @return int|'all'|null
     */
    private static function normalize(mixed $value, array $options, bool $allowAll, ?int $fallback): int|string|null
    {
        if ($allowAll && $value === 'all') {
            return 'all';
        }

        $size = (int) $value;

        if (in_array($size, $options, true)) {
            return $size;
        }

        return $fallback;
    }
}
