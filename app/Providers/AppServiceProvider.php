<?php

namespace App\Providers;

use App\Models\Purchase;
use App\Models\User;
use App\Policies\PurchasePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()    {
        //
    }

    public function boot()    {
        Gate::policy(Purchase::class, PurchasePolicy::class);

        Gate::define('run-migrations', function (User $user) {
            return $user->isAdmin();
        });
    }
}
