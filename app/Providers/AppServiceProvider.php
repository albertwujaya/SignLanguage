<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('admin.partials.pagination');

        Gate::define('access-admin', fn (User $user): bool => $user->isStaff());
        Gate::define('manage-users', fn (User $user): bool => $user->isAdmin());
        Gate::define('delete-gestures', fn (User $user): bool => $user->isAdmin());
    }
}
