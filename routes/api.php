<?php

use App\Http\Controllers\Api\AppVersionController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\FcmTokenController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\RefreshTokenController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CourierController;
use App\Http\Controllers\Api\CustomerAddressController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderPaymentController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Controllers\Api\ShippingCostController;
use App\Http\Controllers\Api\StoreSettingController;
use App\Http\Controllers\Api\UserAccountController;
use App\Http\Controllers\Api\UserLocationController;
use App\Http\Controllers\Api\UserPasswordController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

// Health check / Ping endpoint
Route::get('ping', fn () => response()->json([
    'success' => true,
    'status' => 'ok',
    'message' => 'pong',
    'timestamp' => now()->toIso8601String(),
]))->name('api.ping');

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('ping', fn () => response()->json([
        'success' => true,
        'status' => 'ok',
        'message' => 'pong',
        'timestamp' => now()->toIso8601String(),
    ]))->name('ping');

    Route::prefix('auth')->name('auth.')->group(function () {
        // Guest auth routes with throttle
        Route::middleware('throttle:6,1')->group(function () {
            Route::post('register', RegisterController::class)->name('register');
            Route::post('login', LoginController::class)->name('login');

            // Password Reset via OTP
            Route::post('forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp'])->name('forgot-password.send-otp');
            Route::post('forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])->name('forgot-password.verify-otp');
            Route::post('reset-password', ResetPasswordController::class)->name('reset-password');
            Route::post('forgot-password/reset-password', ResetPasswordController::class)->name('forgot-password.reset');

            // Email Verification via OTP
            Route::post('email/send-otp', [EmailVerificationController::class, 'sendOtp'])->name('email.send-otp');
            Route::post('email/verify-otp', [EmailVerificationController::class, 'verifyOtp'])->name('email.verify-otp');
        });

        // Authenticated auth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', MeController::class)->name('me');
            Route::post('refresh-token', RefreshTokenController::class)->name('refresh-token');
            Route::post('email/resend-otp', [EmailVerificationController::class, 'sendOtp'])->name('email.resend-otp');
            Route::post('fcm-token', [FcmTokenController::class, 'update'])->name('fcm-token');
        });
    });

    // Alias: GET /api/v1/me
    Route::middleware('auth:sanctum')->get('me', MeController::class)->name('me');

    // User Location (scoped to authenticated user)
    Route::middleware('auth:sanctum')->prefix('user/location')->name('user.location.')->group(function () {
        Route::get('/', [UserLocationController::class, 'show'])->name('show');
        Route::put('/', [UserLocationController::class, 'update'])->name('update');
        Route::post('/', [UserLocationController::class, 'update'])->name('store');
    });

    // User Profile (Complete / Update Profile)
    Route::middleware('auth:sanctum')->prefix('user/profile')->name('user.profile.')->group(function () {
        Route::get('/', [UserProfileController::class, 'show'])->name('show');
        Route::match(['put', 'patch', 'post'], '/', [UserProfileController::class, 'update'])->name('update');
    });
    Route::middleware('auth:sanctum')->match(['put', 'patch', 'post'], 'profile', [UserProfileController::class, 'update'])->name('profile.update');
    Route::middleware('auth:sanctum')->match(['put', 'patch', 'post'], 'user/password', [UserPasswordController::class, 'update'])->name('user.password.update');
    Route::middleware('auth:sanctum')->match(['delete', 'post'], 'user/account', [UserAccountController::class, 'destroy'])->name('user.account.destroy');
    Route::middleware('auth:sanctum')->delete('user', [UserAccountController::class, 'destroy'])->name('user.destroy');

    // Customer Addresses (scoped to authenticated user)
    Route::middleware('auth:sanctum')->prefix('addresses')->name('addresses.')->group(function () {
        Route::get('/', [CustomerAddressController::class, 'index'])->name('index');
        Route::post('/', [CustomerAddressController::class, 'store'])->name('store');
        Route::get('{id}', [CustomerAddressController::class, 'show'])->whereNumber('id')->name('show');
        Route::match(['put', 'patch'], '{id}', [CustomerAddressController::class, 'update'])->whereNumber('id')->name('update');
        Route::patch('{id}/primary', [CustomerAddressController::class, 'setPrimary'])->whereNumber('id')->name('set-primary');
        Route::delete('{id}', [CustomerAddressController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    // Customer Orders & Checkout (scoped to authenticated user)
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::post('checkout', [CheckoutController::class, 'checkout'])->name('checkout');
            Route::post('checkout/preview', [CheckoutController::class, 'preview'])->name('checkout.preview');
            Route::post('/', [CheckoutController::class, 'checkout'])->name('store');
            Route::get('{identifier}', [OrderController::class, 'show'])->name('show');
            Route::post('{identifier}/payment-proof', [OrderPaymentController::class, 'upload'])->name('payment-proof');
            Route::post('{identifier}/upload-payment-proof', [OrderPaymentController::class, 'upload']);
        });

        Route::prefix('checkout')->name('checkout.')->group(function () {
            Route::post('/', [CheckoutController::class, 'checkout'])->name('index');
            Route::post('preview', [CheckoutController::class, 'preview'])->name('preview');
        });
    });

    // Notifications (scoped to authenticated user & broadcast)
    Route::middleware('auth:sanctum')->prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('mark-read', [NotificationController::class, 'markRead'])->name('mark-read');
        Route::post('mark-all-read', [NotificationController::class, 'markAllRead'])->name('mark-all-read');
        Route::post('{id}/mark-read', [NotificationController::class, 'markSingleRead'])->whereNumber('id')->name('mark-single-read');
    });

    // Product Reviews (Create review for completed & paid order)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('reviews', [ProductReviewController::class, 'store'])->name('reviews.store');
        Route::post('products/{product_id}/reviews', [ProductReviewController::class, 'store'])->whereNumber('product_id')->name('products.reviews.store');
    });

    // Carts (scoped to authenticated user)
    Route::middleware('auth:sanctum')->prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::post('/', [CartController::class, 'store'])->name('store');
        Route::match(['put', 'patch'], '{id}', [CartController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('{id}', [CartController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    // Wishlists (scoped to authenticated user)
    Route::middleware('auth:sanctum')->prefix('wishlist')->name('wishlist.')->group(function () {
        Route::get('/', [WishlistController::class, 'index'])->name('index');
        Route::post('/', [WishlistController::class, 'store'])->name('store');
        Route::delete('{productId}', [WishlistController::class, 'destroy'])->whereNumber('productId')->name('destroy');
    });

    // Banners
    Route::prefix('banners')->name('banners.')->group(function () {
        Route::get('/', [BannerController::class, 'index'])->name('index');
    });

    // Categories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
    });

    // Products
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('recommendations', [ProductController::class, 'recommendations'])->name('recommendations');
        Route::get('best-sellers', [ProductController::class, 'bestSellers'])->name('best-sellers');
        Route::get('slug/{slug}/reviews', [ProductReviewController::class, 'index'])->name('reviews.slug-path');
        Route::get('slug/{slug}', [ProductController::class, 'showBySlug'])->name('show-by-slug');
        Route::get('{id}', [ProductController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('{slug}/reviews', [ProductReviewController::class, 'index'])->name('reviews');
        Route::get('{slug}', [ProductController::class, 'showBySlug'])->name('show.slug');
    });

    // Payment Methods
    Route::prefix('payment-methods')->name('payment-methods.')->group(function () {
        Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
        Route::get('{id}', [PaymentMethodController::class, 'show'])->whereNumber('id')->name('show');
    });

    // Couriers, Shipping Types & RajaOngkir Shipping Cost
    Route::prefix('couriers')->name('couriers.')->group(function () {
        Route::get('/', [CourierController::class, 'index'])->name('index');
        Route::post('cost', [ShippingCostController::class, 'calculate'])->name('cost');
        Route::post('check-ongkir', [ShippingCostController::class, 'calculate'])->name('check-ongkir');
    });
    Route::get('shipping-types', [CourierController::class, 'index'])->name('shipping-types.index');
    Route::post('shipping/cost', [ShippingCostController::class, 'calculate'])->name('shipping.cost');
    Route::post('shipping/check-cost', [ShippingCostController::class, 'calculate'])->name('shipping.check-cost');
    Route::post('check-ongkir', [ShippingCostController::class, 'calculate'])->name('check-ongkir');

    // FAQs
    Route::prefix('faqs')->name('faqs.')->group(function () {
        Route::get('/', [FaqController::class, 'index'])->name('index');
        Route::get('{id}', [FaqController::class, 'show'])->whereNumber('id')->name('show');
    });

    // Store Settings & Policies
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [StoreSettingController::class, 'index'])->name('index');
        Route::get('privacy-policy', [StoreSettingController::class, 'privacyPolicy'])->name('privacy-policy');
    });

    // Locations (Provinces, Cities, Subdistricts & Reverse Geocoding via Geoapify)
    // App Versions (Manajemen Aplikasi Android / iOS)
    Route::prefix('app-versions')->name('app-versions.')->group(function () {
        Route::get('/', [AppVersionController::class, 'index'])->name('index');
        Route::get('latest', [AppVersionController::class, 'latest'])->name('latest');
        Route::get('latest/download', [AppVersionController::class, 'downloadLatest'])->name('latest.download');
        Route::get('check', [AppVersionController::class, 'latest'])->name('check');
        Route::get('{id}/download', [AppVersionController::class, 'download'])->whereNumber('id')->name('download');
        Route::get('{id}', [AppVersionController::class, 'show'])->whereNumber('id')->name('show');
    });
    Route::prefix('mobile-apps')->name('mobile-apps.')->group(function () {
        Route::get('/', [AppVersionController::class, 'index'])->name('index');
        Route::get('latest', [AppVersionController::class, 'latest'])->name('latest');
        Route::get('latest/download', [AppVersionController::class, 'downloadLatest'])->name('latest.download');
        Route::get('check', [AppVersionController::class, 'latest'])->name('check');
        Route::get('{id}/download', [AppVersionController::class, 'download'])->whereNumber('id')->name('download');
        Route::get('{id}', [AppVersionController::class, 'show'])->whereNumber('id')->name('show');
    });
    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('address', [LocationController::class, 'getAddress'])->name('address');
        Route::get('reverse-geocode', [LocationController::class, 'getAddress'])->name('reverse-geocode');
        Route::get('provinces', [LocationController::class, 'provinces'])->name('provinces');
        Route::get('cities', [LocationController::class, 'cities'])->name('cities');
        Route::get('subdistricts', [LocationController::class, 'subdistricts'])->name('subdistricts');
    });
    Route::get('get-address', [LocationController::class, 'getAddress'])->name('get-address');
    Route::get('provinces', [LocationController::class, 'provinces'])->name('provinces.index');
    Route::get('cities', [LocationController::class, 'cities'])->name('cities.index');
    Route::get('subdistricts', [LocationController::class, 'subdistricts'])->name('subdistricts.index');
});

Route::post('shipping/cost', [ShippingCostController::class, 'calculate'])->name('api.shipping.cost');
Route::post('shipping/check-cost', [ShippingCostController::class, 'calculate'])->name('api.shipping.check-cost');
Route::post('check-ongkir', [ShippingCostController::class, 'calculate'])->name('api.check-ongkir');
