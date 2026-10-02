<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Listeners\UpdateLastLoginInfo;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\URL;

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
        // Behind a proxy Laravel must trust the forwarded headers to know the
        // request is HTTPS (see config/trustedproxy.php).
        if ($proxies = config('trustedproxy.proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // Production runs on HTTPS only: every generated link, asset and
        // Livewire request URL uses https://.
        if ($this->app->isProduction()) {
            URL::forceHttps();
        }

        Event::listen(Login::class, UpdateLastLoginInfo::class);

        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}