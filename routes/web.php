<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'show'])->name('home');
Route::get('/category/{slug}', [CatalogController::class, 'category'])->name('category.show');
Route::get('/product/{slug}', [CatalogController::class, 'product'])->name('product.show');

Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::view('/cart', 'pages.cart')->name('cart.show');
Route::view('/checkout', 'pages.checkout')->name('checkout.show');

Route::get('/orders/{order}/thank-you', [OrderController::class, 'thankYou'])->name('order.thank-you');
Route::get('/orders/{order}/invoice.pdf', [OrderController::class, 'invoice'])->name('order.invoice');

// Dev-only login helper for verifying B2B pricing in the browser before
// real auth ships in Phase 5. Removed when /login is implemented.
if (app()->environment('local')) {
    Route::get('/dev-login/{user}', function (\App\Models\User $user) {
        Auth::login($user);
        return redirect()->route('home');
    });
}

// Auth stubs — replaced with full flow in Phase 5.
Route::view('/login', 'pages.login-stub')->name('login');
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('home');
})->name('logout');
