<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {

        if (config('app.env') === 'production' || app()->environment('production')) {
            URL::forceScheme('https');
        }


        Gate::define('access-admin', function (User $user) {
            return strtolower($user->role) === 'admin';
        });
    }
}