<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Listeners\UpdateLastLoginInfo;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\URL;
use App\Http\Middleware\EnsureRiderApproved;
use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\EnsureVendorRestaurantActive;
use App\Http\Middleware\RoleMiddleware;
use Livewire\Livewire;

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

        // Route middleware normally runs only when a page is first opened. Every
        // click inside a Livewire component is a separate request, so without
        // this a user blocked (or un-approved) while a page is open could keep
        // working until they reload. These are checked again on every update.
        // ("auth" is already on Livewire's default list.)
        Livewire::addPersistentMiddleware([
            RoleMiddleware::class,
            EnsureRiderApproved::class,
            EnsureVendorRestaurantActive::class,
            EnsureUserActive::class,
        ]);

        Event::listen(Login::class, UpdateLastLoginInfo::class);

        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}