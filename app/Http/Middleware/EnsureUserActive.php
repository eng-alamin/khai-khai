<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\RespondsToLivewire;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs out a signed-in user whose account the admin has deactivated.
 *
 * RoleMiddleware already does this for vendor / rider / admin routes. The
 * customer routes only use "auth", so this covers them. Also re-checked on
 * every Livewire update (see AppServiceProvider), so a customer deactivated
 * while a page is open is stopped on the next click.
 */
class EnsureUserActive
{
    use RespondsToLivewire;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();

            if ($this->isLivewireRequest($request)) {
                // Shown on the login page after the page reloads.
                session()->flash('error', 'আপনার অ্যাকাউন্ট নিষ্ক্রিয় করা হয়েছে।');
                $this->expireLivewireRequest();
            }

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'আপনার অ্যাকাউন্ট নিষ্ক্রিয় করা হয়েছে।');
        }

        return $next($request);
    }
}
