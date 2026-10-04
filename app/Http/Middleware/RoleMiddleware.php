<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\RespondsToLivewire;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    use RespondsToLivewire;

    protected $dashboards = [
        'vendor' => 'vendor.dashboard',
        'rider' => 'rider.dashboard',
        'admin' => 'admin.dashboard',
        'customer' => 'customer.home',
    ];

    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($this->isLivewireRequest($request)) {
                $this->expireLivewireRequest();
            }

            return redirect()->route('login')
                ->with('error', 'এই পেজে প্রবেশ করতে লগইন করুন।');
        }

        // Check if account is active
        if (isset($user->is_active) && !$user->is_active) {
            Auth::logout();

            if ($this->isLivewireRequest($request)) {
                // Shown on the login page after the page reloads.
                session()->flash('error', 'আপনার অ্যাকাউন্ট নিষ্ক্রিয় করা হয়েছে।');
                $this->expireLivewireRequest();
            }

            return redirect()->route('login')
                ->with('error', 'আপনার অ্যাকাউন্ট নিষ্ক্রিয় করা হয়েছে।');
        }

        // If no roles specified, just check authentication
        if (empty($roles)) {
            return $next($request);
        }

        // Check if user's role is allowed
        if (!in_array($user->role, $roles)) {
            if ($this->isLivewireRequest($request)) {
                $this->expireLivewireRequest();
            }

            $dashboard = $this->getDashboardRoute($user->role);
            return redirect($dashboard)
                ->with('error', 'এই পেজে আপনার অ্যাক্সেস নেই।');
        }

        return $next($request);
    }

    private function getDashboardRoute(string $role): string
    {
        $routeName = $this->dashboards[$role] ?? 'login';

        // Check if route exists, otherwise fallback to home
        try {
            return route($routeName);
        } catch (\Exception $e) {
            return url('/');
        }
    }
}