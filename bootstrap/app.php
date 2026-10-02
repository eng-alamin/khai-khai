<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureRiderApproved;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\UpdateLastSeen;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
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
        ]);
        $middleware->web(append: [
            UpdateLastSeen::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();