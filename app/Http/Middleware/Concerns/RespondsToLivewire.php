<?php

namespace App\Http\Middleware\Concerns;

use Illuminate\Http\Request;

/**
 * Shared by the middleware that Livewire re-runs on every component update
 * (see AppServiceProvider::boot()).
 *
 * A normal page request can be answered with a redirect. A Livewire update is
 * an Ajax call, so a redirect would be followed silently and the browser would
 * end up showing a page inside the component. HTTP 419 is different: Livewire
 * shows its "page expired" prompt and reloads the page. The reload is a normal
 * request, so the middleware then sends the user to the right place with the
 * right message.
 */
trait RespondsToLivewire
{
    protected function isLivewireRequest(Request $request): bool
    {
        // When Livewire re-runs middleware it hands them a copy of the page's
        // request, so the real incoming request is checked as well.
        return $request->headers->has('X-Livewire')
            || request()->headers->has('X-Livewire');
    }

    protected function expireLivewireRequest(): never
    {
        abort(419, 'Page Expired');
    }
}