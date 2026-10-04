<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\RespondsToLivewire;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps vendors whose restaurant is blocked (restaurants.is_active = 0) or not
 * approved (restaurants.is_approved = 0) out of the vendor panel.
 *
 * "role:vendor" only looks at users.is_active, but Admin -> Vendors -> Block
 * changes the restaurant row. This middleware closes that gap.
 *
 * Use after "role:vendor". It is also registered as a Livewire persistent
 * middleware, so it re-runs on every component update, not just page loads.
 */
class EnsureVendorRestaurantActive
{
    use RespondsToLivewire;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only vendors are checked. Other roles pass through untouched.
        if (! $user || ! $user->isVendor()) {
            return $next($request);
        }

        // Always read fresh values: the admin may have just changed them.
        $restaurant = $user->restaurant()->first();

        // Vendors without a restaurant row keep the existing behaviour.
        if (! $restaurant) {
            return $next($request);
        }

        $message = null;

        if (! $restaurant->is_approved) {
            $message = 'আপনার রেস্টুরেন্ট এখনো অনুমোদিত হয়নি। অনুগ্রহ করে অপেক্ষা করুন।';
        } elseif (! $restaurant->is_active) {
            $message = 'আপনার রেস্টুরেন্ট ব্লক করা হয়েছে। বিস্তারিত জানতে সাপোর্টে যোগাযোগ করুন।';
        }

        if ($message === null) {
            return $next($request);
        }

        Auth::logout();

        if ($this->isLivewireRequest($request)) {
            // Shown on the login page after Livewire reloads the page.
            session()->flash('error', $message);
            $this->expireLivewireRequest();
        }

        return redirect()->route('login')->with('error', $message);
    }
}
