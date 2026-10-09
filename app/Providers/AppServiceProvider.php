<?php

namespace App\Providers;

use App\Mail\BrevoTransport;
use App\Models\DetergentInventory;
use App\Models\LaundryOrder;
use App\Policies\DetergentInventoryPolicy;
use App\Policies\LaundryOrderPolicy;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
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
     * Configure custom mail transports.
     */
    protected function configureMailTransport(): void
    {
        Mail::extend('brevo', function () {
            return new BrevoTransport(config('services.brevo.key'));
        });
    }

    /**
     * The application's policies.
     */
    protected array $policies = [
        LaundryOrder::class => LaundryOrderPolicy::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Register custom mail transport
         */
        $this->configureMailTransport();

        /*
         * Register policies
         */
        Gate::policy(LaundryOrder::class,        LaundryOrderPolicy::class);
        Gate::policy(DetergentInventory::class,  DetergentInventoryPolicy::class);

        /*
         * Force HTTPS in production.
         *
         * This is important when deploying to Render because
         * Render handles HTTPS at the proxy/load-balancer level.
         */
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        /*
         * Share unread message count with tenant layout.
         */
        view()->composer('components.layouts.tenant', function ($view) {
            $unreadCount = 0;

            if (auth()->check() && auth()->user()->hasRole('tenant')) {
                $tenant = auth()->user()->tenant;

                if ($tenant) {
                    $unreadCount = \App\Models\TenantMessage::where(
                        'tenant_id',
                        $tenant->id
                    )
                        ->get()
                        ->sum(function ($message) {
                            return $message->unreadRepliesCountFor(
                                auth()->user()
                            );
                        });
                }
            }

            $view->with('unreadMessagesCount', $unreadCount);
        });

        /*
         * Local development settings.
         *
         * Trust proxies and handle tunnel URLs when using
         * Cloudflare Tunnel, ngrok, etc.
         */
        if (app()->environment('local')) {
            /*
             * Trust all proxies in local development so
             * cloudflared/ngrok host headers are respected
             * and asset URLs use the tunnel domain.
             */
            \Illuminate\Http\Request::setTrustedProxies(
                ['127.0.0.1', '::1', 'REMOTE_ADDR'],
                \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
            );

            /*
             * Add a header to tunnel responses to skip
             * the ngrok browser warning page.
             */
            Event::listen(
                RequestHandled::class,
                function ($event) {
                    $host = $event->request->getHost();

                    /*
                     * Works for Cloudflare Tunnel, ngrok,
                     * and ngrok free domains.
                     */
                    if (
                        str_contains($host, '.trycloudflare.com') ||
                        str_contains($host, '.ngrok') ||
                        str_contains($host, '.ngrok-free.app')
                    ) {
                        $event->response->headers->set(
                            'ngrok-skip-browser-warning',
                            '1'
                        );
                    }
                }
            );
        }
    }
}