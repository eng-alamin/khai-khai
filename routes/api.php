<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\UpdateLastSeen;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\CustomerCatalogController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;

/*
|--------------------------------------------------------------------------
| Mobile API (Flutter customer app)
|--------------------------------------------------------------------------
| Laravel adds the "/api" prefix automatically, so the Flutter
| API_BASE_URL is "https://your-domain.com/api".
| Every path below must match lib/core/endpoints.dart.
*/

// Auth
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,60');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::post('forgot-password', [PasswordResetController::class, 'send'])->middleware('throttle:10,1');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'api.active'])->post('logout', [AuthController::class, 'logout']);
});

// Signed-in customer
Route::middleware(['auth:sanctum', 'api.active', UpdateLastSeen::class, 'throttle:120,1'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::put('me', [ProfileController::class, 'update']);
    Route::put('me/password', [PasswordController::class, 'update'])->middleware('throttle:5,1');
    Route::post('me/delete', [AccountController::class, 'destroy'])->middleware('throttle:5,1');

    Route::prefix('me/addresses')->controller(AddressController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::put('{address}', 'update')->whereNumber('address');
        Route::delete('{address}', 'destroy')->whereNumber('address');
        Route::post('{address}/default', 'setDefault')->whereNumber('address');
    });

    // Cart + orders (must be signed in: the controllers use $request->user())
    Route::post('cart/quote', [CartController::class, 'quote'])->middleware('throttle:60,1');

    Route::prefix('orders')->controller(OrderController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store')->middleware('throttle:20,1');

        // By order number (what the track screen uses): KK260921A1B2
        Route::get('{number}', 'show')->where('number', '[A-Za-z0-9]+');
        Route::get('{number}/rider-location', 'riderLocation')->where('number', '[A-Za-z0-9]+')->middleware('throttle:30,1');

        // By numeric id
        Route::post('{order}/cancel', 'cancel')->whereNumber('order');
        Route::post('{order}/review', 'review')->whereNumber('order');
        Route::post('{order}/reorder', 'reorder')->whereNumber('order');
    });

    // Notifications (own only) + push settings + the phone's FCM token
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->whereNumber('id');
    Route::get('notification-settings', [NotificationController::class, 'settings']);
    Route::put('notification-settings', [NotificationController::class, 'updateSettings']);
    Route::post('devices', [DeviceController::class, 'store']);
    Route::post('devices/unregister', [DeviceController::class, 'destroy']);

    // Favourites (restaurants + foods)
    Route::get('favorites', [FavoriteController::class, 'index']);
    Route::post('favorites', [FavoriteController::class, 'store']);
    Route::delete('favorites/{type}/{id}', [FavoriteController::class, 'destroy'])->whereNumber('id');

    // Support tickets (own tickets only)
    Route::prefix('support/tickets')->controller(SupportController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::post('{ticket}/reply', 'reply')->whereNumber('ticket');
    });
});

// Customer catalog (public, same as the website home / menu pages)
Route::prefix('customer')->controller(CustomerCatalogController::class)->group(function () {
    Route::get('home', 'home');
    Route::get('restaurants', 'restaurants');
    Route::get('restaurant-categories', 'restaurantCategories');
    Route::get('menu-categories', 'menuCategories');
    Route::get('menu-items', 'menuItems');
    Route::get('restaurants/{id}', 'restaurant')->whereNumber('id');
    Route::get('menu-items/{id}', 'food')->whereNumber('id');
});

// Offers screen (public, same as the website /offer page)
Route::get('customer/coupons', [CouponController::class, 'index'])->middleware('throttle:60,1');
