<?php

namespace App\Providers;

use App\Contracts\AuthorizationServiceContract;
use App\Services\AuthorizationService;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuthorizationServiceContract::class, AuthorizationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        EloquentBuilder::macro('whereLike', function (string $column, string $value, string $boolean = 'and') {
            /** @var EloquentBuilder $this */
            $operator = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

            return $this->where($column, $operator, $value, $boolean);
        });

        EloquentBuilder::macro('orWhereLike', function (string $column, string $value) {
            /** @var EloquentBuilder $this */
            $operator = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

            return $this->orWhere($column, $operator, $value);
        });

        QueryBuilder::macro('whereLike', function (string $column, string $value, string $boolean = 'and') {
            /** @var QueryBuilder $this */
            $operator = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

            return $this->where($column, $operator, $value, $boolean);
        });

        QueryBuilder::macro('orWhereLike', function (string $column, string $value) {
            /** @var QueryBuilder $this */
            $operator = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

            return $this->orWhere($column, $operator, $value);
        });

        Blade::directive('role', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasRole({$expression})): ?>";
        });
        Blade::directive('endrole', fn () => '<?php endif; ?>');

        Blade::directive('permission', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->can({$expression})): ?>";
        });
        Blade::directive('endpermission', fn () => '<?php endif; ?>');

        Blade::directive('canAccess', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->can({$expression})): ?>";
        });
        Blade::directive('endcanAccess', fn () => '<?php endif; ?>');
    }
}
