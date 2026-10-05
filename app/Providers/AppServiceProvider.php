<?php

namespace App\Providers;

use App\Models\AppNotification;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();

        Gate::define('admin', fn (User $user) => $user->isAdmin());

        View::composer('layouts.app', function ($view) {
            $view->with([
                'unreadNotifications' => AppNotification::unread()->count(),
                'companyName' => Setting::get('company_name', 'Meu Cash'),
            ]);
        });
    }
}
