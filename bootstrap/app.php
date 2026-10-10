<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureApiUserActive;
use App\Http\Middleware\EnsureRiderApproved;
use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\EnsureVendorRestaurantActive;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\UpdateLastSeen;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Logged-in users who open a guest-only page (login, register ...)
        // are sent to the dashboard that matches their role.
        $middleware->redirectUsersTo(fn (Request $request) => match ($request->user()?->role) {
            'admin'  => route('admin.dashboard'),
            'vendor' => route('vendor.dashboard'),
            'rider'  => route('rider.dashboard'),
            default  => route('customer.home'),
        });

        $middleware->alias([
            'role'           => RoleMiddleware::class,
            'rider.approved' => EnsureRiderApproved::class,
            'vendor.active'  => EnsureVendorRestaurantActive::class,
            'user.active'    => EnsureUserActive::class,
            'api.active'     => EnsureApiUserActive::class,
        ]);
        $middleware->web(append: [
            UpdateLastSeen::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every /api/* request always gets a JSON error (401, 403, 404, 422,
        // 500), never an HTML page or a redirect to the web login page.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
