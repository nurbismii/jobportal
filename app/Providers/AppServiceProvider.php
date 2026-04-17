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
        $secondaryHost = env('MAIL_HOST_SECOND');
        $mailers = config('mail.mailers', []);
        $dynamicMailers = [
            'failover' => [
                'transport' => 'failover',
                'mailers' => array_values(array_filter([
                    'smtp',
                    $secondaryHost ? 'smtp_second' : null,
                    'log',
                ])),
            ],
        ];

        if ($secondaryHost) {
            $dynamicMailers['smtp_second'] = [
                'transport' => 'smtp',
                'host' => $secondaryHost,
                'port' => env('MAIL_PORT_SECOND', 587),
                'encryption' => env('MAIL_ENCRYPTION_SECOND', 'tls'),
                'username' => env('MAIL_USERNAME_SECOND'),
                'password' => env('MAIL_PASSWORD_SECOND'),
                'timeout' => null,
                'auth_mode' => null,
            ];
        }

        config([
            'mail.driver' => null,
            'mail.default' => env('MAIL_MAILER', config('mail.default', 'failover')),
            'mail.secondary_mailer' => env('MAIL_SECONDARY_MAILER', config('mail.secondary_mailer', 'smtp_second')),
            'mail.from_second' => [
                'address' => env('MAIL_FROM_ADDRESS_SECOND', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
                'name' => env('MAIL_FROM_NAME_SECOND', env('MAIL_FROM_NAME', 'Example')),
            ],
            'mail.mailers' => array_merge($mailers, $dynamicMailers),
        ]);
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
