<?php

namespace App\Providers;

use App\Models\Part;
use App\Models\Model;
use App\Models\Truck;
use App\Models\User;
use App\Policies\ModelPolicy;
use App\Policies\PartPolicy;
use App\Policies\TruckPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Model::class => ModelPolicy::class,
        Part::class => PartPolicy::class,
        Truck::class => TruckPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $_ability) {
            if ($user && method_exists($user, 'hasRole') && $user->hasRole(config('authorization.super_admin_role', 'Super Admin'))) {
                return true;
            }

            return null;
        });
    }
}
