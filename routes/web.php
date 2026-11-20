<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppConfigController;
use App\Http\Controllers\AppVersionController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StoreSettingController;
use App\Http\Controllers\User\AuthController as UserAuthController;
use App\Http\Controllers\User\CartController as UserCartController;
use App\Http\Controllers\User\CategoryController as UserCategoryController;
use App\Http\Controllers\User\CheckoutController as UserCheckoutController;
use App\Http\Controllers\User\CompleteProfileController;
use App\Http\Controllers\User\GoogleAuthController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\OrderController as UserOrderController;
use App\Http\Controllers\User\ProductController as UserProductController;
use App\Http\Controllers\User\ProfileController as UserProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariantTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/categories', [UserCategoryController::class, 'index'])->name('user.categories.index');
Route::get('/products', [UserProductController::class, 'index'])->name('user.products.index');
Route::get('/products/{slug}', [UserProductController::class, 'show'])->name('user.products.show');

Route::middleware(['guest'])->group(function () {
    Route::get('/register', [UserAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [UserAuthController::class, 'register'])->name('register.store');
});

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::middleware(['auth'])->group(function () {
    Route::get('/complete-profile', [CompleteProfileController::class, 'show'])->name('user.complete-profile.show');
    Route::post('/complete-profile', [CompleteProfileController::class, 'store'])->name('user.complete-profile.store');
    Route::get('/profile', [UserProfileController::class, 'edit'])->name('user.profile.edit');
    Route::patch('/profile', [UserProfileController::class, 'update'])->name('user.profile.update');
    Route::put('/profile/password', [UserProfileController::class, 'updatePassword'])
        ->middleware('throttle:6,1')
        ->name('user.profile.password');
    Route::get('/my-orders', [UserOrderController::class, 'index'])->name('user.orders.index');
    Route::get('/my-orders/{orderNumber}', [UserOrderController::class, 'show'])->name('user.orders.show');
    Route::post('/my-orders/{orderNumber}/payment-proof', [UserOrderController::class, 'uploadPaymentProof'])->name('user.orders.payment-proof');
    Route::get('/cart', [UserCartController::class, 'index'])->name('user.cart.index');
    Route::post('/cart', [UserCartController::class, 'store'])->name('user.cart.store');
    Route::patch('/cart/{cart}', [UserCartController::class, 'update'])->name('user.cart.update');
    Route::delete('/cart/{cart}', [UserCartController::class, 'destroy'])->name('user.cart.destroy');

    Route::get('/checkout', [UserCheckoutController::class, 'index'])->name('user.checkout.index');
    Route::post('/checkout', [UserCheckoutController::class, 'store'])->name('user.checkout.store');
    Route::get('/checkout/shipping-cost', [UserCheckoutController::class, 'shippingCost'])->name('user.checkout.shipping-cost');
    Route::get('/checkout/locations/cities', [UserCheckoutController::class, 'cities'])->name('user.checkout.locations.cities');
    Route::get('/checkout/locations/subdistricts', [UserCheckoutController::class, 'subdistricts'])->name('user.checkout.locations.subdistricts');
    Route::post('/checkout/addresses', [UserCheckoutController::class, 'storeAddress'])->name('user.checkout.addresses.store');
    Route::patch('/checkout/addresses/{address}', [UserCheckoutController::class, 'updateAddress'])->name('user.checkout.addresses.update');
    Route::patch('/checkout/addresses/{address}/primary', [UserCheckoutController::class, 'setPrimaryAddress'])->name('user.checkout.addresses.primary');
    Route::delete('/checkout/addresses/{address}', [UserCheckoutController::class, 'destroyAddress'])->name('user.checkout.addresses.destroy');
});

Route::middleware(['auth', 'verified'])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('users', UserController::class);
    Route::resource('customers', CustomerController::class)->except(['create', 'store']);
    Route::resource('payment-methods', PaymentMethodController::class)->except(['create', 'show', 'edit']);

    Route::post('couriers/{courier}/toggle', [CourierController::class, 'toggle'])->name('couriers.toggle');
    Route::resource('couriers', CourierController::class)->except(['create', 'show', 'edit']);
    Route::resource('categories', CategoryController::class);
    Route::post('banners/{banner}/toggle', [BannerController::class, 'toggle'])->name('banners.toggle');
    Route::resource('banners', BannerController::class)->except(['show']);
    Route::resource('variant-types', VariantTypeController::class)->except(['show']);
    Route::get('variant-types/{variant_type}/values', [VariantTypeController::class, 'values'])->name('variant-types.values');
    Route::post('variant-types/{variant_type}/values', [VariantTypeController::class, 'syncValues'])->name('variant-types.values.sync');
    Route::get('products/template', [ProductController::class, 'downloadTemplate'])->name('products.template');
    Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
    Route::resource('products', ProductController::class);
    Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');

    // Locations UI
    Route::get('locations', [LocationController::class, 'provinces'])->name('locations.provinces');
    Route::get('locations/provinces/{province}/cities', [LocationController::class, 'cities'])->name('locations.cities');
    Route::get('locations/cities/{city}/subdistricts', [LocationController::class, 'subdistricts'])->name('locations.subdistricts');

    // Locations API
    Route::get('api/locations/cities', [LocationController::class, 'getCities'])->name('api.locations.cities');
    Route::get('api/locations/subdistricts', [LocationController::class, 'getSubdistricts'])->name('api.locations.subdistricts');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('orders/{order}/payment', [OrderController::class, 'updatePaymentStatus'])->name('orders.update-payment');
    Route::post('orders/{order}/shipping', [OrderController::class, 'updateShipping'])->name('orders.update-shipping');

    // Store Settings
    Route::get('store-settings', [StoreSettingController::class, 'index'])->name('store-settings.index');
    Route::post('store-settings', [StoreSettingController::class, 'update'])->name('store-settings.update');

    // Privacy & Policy Settings
    Route::get('privacy-policy-settings', [PrivacyPolicyController::class, 'index'])->name('privacy-policy.index');
    Route::post('privacy-policy-settings', [PrivacyPolicyController::class, 'update'])->name('privacy-policy.update');

    // FAQs
    Route::post('faqs/{faq}/toggle', [FaqController::class, 'toggle'])->name('faqs.toggle');
    Route::resource('faqs', FaqController::class);

    // App Versions (Manajemen Aplikasi)
    Route::get('app-versions/{app_version}/download', [AppVersionController::class, 'download'])->name('app-versions.download');
    Route::post('app-versions/{app_version}/set-latest', [AppVersionController::class, 'setLatest'])->name('app-versions.set-latest');
    Route::post('app-versions/{app_version}/toggle', [AppVersionController::class, 'toggle'])->name('app-versions.toggle');
    Route::post('app-versions/{app_version}/toggle-force-update', [AppVersionController::class, 'toggleForceUpdate'])->name('app-versions.toggle-force-update');
    Route::resource('app-versions', AppVersionController::class)->except(['create', 'show', 'edit']);

    // Notifications
    Route::post('notifications/{notification}/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::resource('notifications', NotificationController::class)->except(['edit', 'update']);
    Route::post('notification-settings/{notification_setting}/toggle', [NotificationSettingController::class, 'toggle'])->name('notification-settings.toggle');
    Route::resource('notification-settings', NotificationSettingController::class)->except(['create', 'show', 'edit']);

    // Product Reviews
    Route::post('reviews', [ProductReviewController::class, 'store'])->name('reviews.store');
    Route::post('reviews/{review}/toggle', [ProductReviewController::class, 'toggle'])->name('reviews.toggle');
    Route::delete('reviews/{review}', [ProductReviewController::class, 'destroy'])->name('reviews.destroy');

    // App Config & Configurations
    Route::get('configurations', [AppConfigController::class, 'index'])->name('configurations.index');
    Route::get('configurations/rajaongkir', [AppConfigController::class, 'rajaongkir'])->name('configurations.rajaongkir');
    Route::post('configurations/rajaongkir', [AppConfigController::class, 'updateRajaongkir'])->name('configurations.rajaongkir.update');
    Route::get('configurations/geoapify', [AppConfigController::class, 'geoapify'])->name('configurations.geoapify');
    Route::post('configurations/geoapify', [AppConfigController::class, 'updateGeoapify'])->name('configurations.geoapify.update');
    Route::get('configurations/firebase', [AppConfigController::class, 'firebase'])->name('configurations.firebase');
    Route::post('configurations/firebase', [AppConfigController::class, 'updateFirebase'])->name('configurations.firebase.update');
    Route::resource('app-configs', AppConfigController::class)->except(['create', 'show', 'edit']);

    Route::middleware(['role:super admin'])->group(function () {
        Route::resource('roles', RoleController::class);
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    });
});

require __DIR__.'/settings.php';
