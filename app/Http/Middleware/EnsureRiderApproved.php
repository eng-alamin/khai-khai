<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps riders who the admin has not approved yet away from order data and
 * earnings pages. They are sent to the Settings page, which shows the
 * "Pending Approval" status.
 *
 * Use after the "role:rider" middleware (which already logs out inactive users).
 */
class EnsureRiderApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isRider() && ! $user->canDeliver()) {
            return redirect()
                ->route('rider.settings')
                ->with('error', 'আপনার রাইডার অ্যাকাউন্ট এখনো অনুমোদিত হয়নি। অনুমোদনের পর এই পেজ ব্যবহার করতে পারবেন।');
        }

        return $next($request);
    }
}
