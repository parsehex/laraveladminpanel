<?php

namespace App\Providers;

use App\Contracts\AuthorizationServiceContract;
use App\Services\AuthorizationService;
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
