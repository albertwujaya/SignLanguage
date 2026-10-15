<?php

namespace App\Providers;

use App\Http\ViewComposers\AdminSidebarComposer;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('admin.partials.pagination');

        if (config('app.env') === 'production' || app()->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::define('access-admin', fn (User $user): bool => $user->isStaff());
        Gate::define('manage-users', fn (User $user): bool => $user->isAdmin());
        Gate::define('delete-gestures', fn (User $user): bool => $user->isAdmin());

        View::composer('admin.partials.sidebar', AdminSidebarComposer::class);
    }
}
