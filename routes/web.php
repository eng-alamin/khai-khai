<?php

use Illuminate\Support\Facades\Route;


// Guest
// Route::middleware('guest')->group(function () {
    Route::get('vendor/register', App\Livewire\VendorRegistrationWizard::class)->name('vendor.register');
    Route::get('/vendor/registration/success/{restaurant}', App\Livewire\VendorRegistrationSuccess::class)->name('vendor.registration.success');
    Route::get('/rider/register', App\Livewire\RiderRegistrationWizard::class)->name('rider.register');
    Route::get('/rider/registration/success/{riderProfile}', App\Livewire\RiderRegistrationSuccess::class)->name('rider.registration.success');
    Route::get('/registration/success/{user}', App\Livewire\CustomerRegistrationSuccess::class)->name('customer.registration.success');

    Route::get('/login', App\Livewire\Login::class)->name('login');
    Route::get('/forgot-password', App\Livewire\ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', App\Livewire\ResetPassword::class)->name('password.reset');
// });

// Authentication required routes
Route::middleware('auth')->group(function () {
    Route::post('logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');
});


// Customer Frontend
Route::get('/', App\Livewire\Customer\HomeComponent::class)->name('customer.home');
Route::get('restaurants', App\Livewire\Customer\RestaurantComponent::class)->name('customer.restaurants');
Route::get('restaurants/{slug}', App\Livewire\Customer\RestaurantComponent::class)->name('customer.restaurant');
Route::get('items', App\Livewire\Customer\ItemComponent::class)->name('customer.items');
Route::get('/register', App\Livewire\CustomerRegistrationComponent::class)->name('customer.register');

// Customer
Route::middleware(['auth'])->group(function () {
    Route::get('orders', App\Livewire\Customer\OrderListComponent::class)->name('customer.orders');
    Route::get('track/{orderId}', App\Livewire\Customer\OrderTrackComponent::class)->name('customer.track');
    Route::get('profile', App\Livewire\Customer\ProfileComponent::class)->name('customer.profile');   
    Route::get('addresses', App\Livewire\Customer\AddressComponent::class)->name('customer.addresses');   
    Route::get('offers', App\Livewire\Customer\OfferComponent::class)->name('customer.offers');   
    Route::get('support', App\Livewire\Customer\SupportComponent::class)->name('customer.support');   
});

// Vendor
Route::middleware(['auth', 'role:vendor'])->group(function () {
    Route::get('dashboard', App\Livewire\Vendor\DashboardComponent::class)->name('vendor.dashboard');
    Route::get('orders/live', App\Livewire\Vendor\OrderLiveComponent::class)->name('vendor.orders.live');
    Route::get('orders/all', App\Livewire\Vendor\OrderListComponent::class)->name('vendor.orders.list');
    Route::get('menu/items', App\Livewire\Vendor\MenuItemComponent::class)->name('vendor.menu.items');
    Route::get('promotions', App\Livewire\Vendor\PromotionComponent::class)->name('vendor.promotions');
    Route::get('coupons', App\Livewire\Vendor\CouponComponent::class)->name('vendor.coupons');
    Route::get('finances', App\Livewire\Vendor\FinanceComponent::class)->name('vendor.finances');
    Route::get('reviews', App\Livewire\Vendor\ReviewComponent::class)->name('vendor.reviews');
    Route::get('settings', App\Livewire\Vendor\SettingComponent::class)->name('vendor.settings');
    Route::get('vendor/profile', App\Livewire\Vendor\ProfileComponent::class)->name('vendor.profile');
});

// Rider 
Route::middleware(['auth', 'role:rider'])->group(function () {
    Route::get('/rider/dashboard', App\Livewire\Rider\DashboardComponent::class)->name('rider.dashboard');
    Route::get('/rider/delivery/ongoing', App\Livewire\Rider\DeliveryOngoingComponent::class)->name('rider.delivery.ongoing');
    Route::get('/rider/delivery/history', App\Livewire\Rider\DeliveryHistoryComponent::class)->name('rider.delivery.history');
    Route::get('/rider/finance', App\Livewire\Rider\FinanceComponent::class)->name('rider.finance');
    Route::get('/rider/settings', App\Livewire\Rider\SettingComponent::class)->name('rider.settings');
    Route::get('/rider/profile', App\Livewire\Rider\ProfileComponent::class)->name('rider.profile');
});

// Admin
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', App\Livewire\Admin\DashboardComponent::class)->name('admin.dashboard');
    Route::get('/admin/notifications', App\Livewire\Admin\NotificationComponent::class)->name('admin.notifications');
    Route::get('/admin/orders', App\Livewire\Admin\OrderComponent::class)->name('admin.orders');

    Route::get('/admin/products', App\Livewire\Admin\ProductComponent::class)->name('admin.products');
    Route::get('/admin/categories', App\Livewire\Admin\CategoryComponent::class)->name('admin.categories');
    Route::get('admin/sliders', App\Livewire\Admin\SliderComponent::class)->name('admin.sliders');

    Route::get('/admin/vendors', App\Livewire\Admin\VendorComponent::class)->name('admin.vendors');
    Route::get('/admin/riders', App\Livewire\Admin\RiderComponent::class)->name('admin.riders');
    Route::get('/admin/customers', App\Livewire\Admin\CustomerComponent::class)->name('admin.customers');

    Route::get('/admin/revenues', App\Livewire\Admin\RevenueComponent::class)->name('admin.revenues');
    Route::get('/admin/settings', App\Livewire\Admin\SettingComponent::class)->name('admin.settings');
    Route::get('/admin/profile', App\Livewire\Admin\ProfileComponent::class)->name('admin.profile');
});