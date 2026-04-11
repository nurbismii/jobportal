<?php

namespace App\Providers;

use App\Models\AccountRecoveryRequest;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        View::composer('partials.admin._sidebar', function ($view) {
            $pendingAccountRecoveryRequests = 0;

            if (Schema::hasTable('account_recovery_requests')) {
                $pendingAccountRecoveryRequests = AccountRecoveryRequest::where('status', 'pending')->count();
            }

            $view->with('pendingAccountRecoveryRequests', $pendingAccountRecoveryRequests);
        });
    }
}
