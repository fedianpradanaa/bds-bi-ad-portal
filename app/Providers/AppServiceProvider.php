<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \Filament\Auth\Http\Responses\Contracts\LoginResponse::class,
            LoginResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Role::class, RolePolicy::class);

        // Allow super admin / is_admin users to bypass all gates
        Gate::before(function ($user, $ability) {
            return ($user->is_admin || $user->hasRole('super_admin')) ? true : null;
        });
    }
}
