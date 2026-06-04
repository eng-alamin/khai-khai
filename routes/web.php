<?php

use Illuminate\Support\Facades\Route;



Route::middleware('guest')->group(function () {
    Route::get('vendor/register', App\Livewire\VendorRegistrationWizard::class)->name('vendor.register');
    Route::get('vendor/registration-success', function () {
        return view('vendor/registration-success');
    })->name('vendor.registration.success');

    Route::get('login', App\Livewire\Login::class)->name('login');
    Route::get('logout', function () {
        Auth::logout();
        return redirect()->route('login');
    })->name('logout');
    Route::get('password/request', App\Livewire\Login::class)->name('password.request');

    // Password reset routes
    Route::get('password/request', function () {
        return view('auth.forgot-password'); // Or use Livewire component
    })->name('password.request');
    
    Route::get('password/reset/{token}', function ($token) {
        return view('auth.reset-password', ['token' => $token]);
    })->name('password.reset');
});

// Authentication required routes
Route::middleware('auth')->group(function () {
    Route::post('logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');

    // Route::get('logout', function () {
    //     Auth::logout();
    //     request()->session()->invalidate();
    //     request()->session()->regenerateToken();
    //     return redirect()->route('login');
    // })->name('logout');
    
    // Vendor success page (accessible after registration)
    Route::get('vendor/registration-success', function () {
        return view('vendor/registration-success');
    })->name('vendor.registration.success');
    
    // Rider success page
    Route::get('rider/registration-success', function () {
        return view('rider/registration-success');
    })->name('rider.registration.success');
});


// Customer
// Route::prefix('customer')->name('customer.')->middleware(['auth'])->group(function () {
// Route::middleware(['auth', 'role:customer'])->group(function () {
Route::middleware(['auth'])->group(function () {
    Route::get('/', App\Livewire\Customer\HomeComponent::class)->name('customer.home');
    Route::get('restaurants', App\Livewire\Customer\RestaurantComponent::class)->name('customer.restaurants');
    Route::get('restaurants/{slug}', App\Livewire\Customer\RestaurantComponent::class)->name('customer.restaurant');
    Route::get('items', App\Livewire\Customer\ItemComponent::class)->name('customer.items');
    Route::get('orders', App\Livewire\Customer\OrderListComponent::class)->name('customer.orders');
    Route::get('track', App\Livewire\Customer\OrderTrackComponent::class)->name('customer.track');
    Route::get('profile', App\Livewire\Customer\ProfileComponent::class)->name('customer.profile');   
    Route::get('addresses', App\Livewire\Customer\AddressComponent::class)->name('customer.addresses');   
    Route::get('offers', App\Livewire\Customer\OfferComponent::class)->name('customer.offers');   
    Route::get('support', App\Livewire\Customer\SupportComponent::class)->name('customer.support');   
});

// Vendor
Route::middleware(['auth', 'role:vendor'])->group(function () {
    Route::get('dashboard', App\Livewire\Vendor\DashboardComponent::class)->name('vendor.dashboard');
    Route::get('menu/categories', App\Livewire\Vendor\MenuCategoryComponent::class)->name('menu.categories');
    Route::get('menu/items', App\Livewire\Vendor\MenuItemComponent::class)->name('menu.items');
    Route::get('promotions', App\Livewire\Vendor\PromotionComponent::class)->name('promotions');
    Route::get('settings', App\Livewire\Vendor\SettingComponent::class)->name('settings');
});



// Rider 
Route::get('rider/register', App\Livewire\VendorRegistrationWizard::class)->name('rider.register');





    Route::get('clear', function () {
        Artisan::call('optimize:clear');
        return redirect()->back()->with('success','Thanks for the fast site!');
    })->name('clear');
    Route::get('backup', function () {
        // Artisan::call('backup:clean');
        Artisan::call('backup:run');
        return redirect()->back()->with('success','Thanks for the backup!');
    })->name('backup');
    Route::get('link', function () {
        Artisan::call('storage:link');
        return redirect()->back()->with('success','Thanks for the link storage!');
    });
    Route::get('fresh', function () {
        Artisan::call('migrate:fresh --seed');
    });
    Route::get('migrate', function () {
        Artisan::call('migrate');
    });
      Route::get('key', function () {
        Artisan::call('key:generate');
    });