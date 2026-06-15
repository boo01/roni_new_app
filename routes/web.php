<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'show'])->name('home');
Route::get('/categories', [CatalogController::class, 'categories'])->name('categories');
Route::get('/category/{slug}', [CatalogController::class, 'category'])->name('category.show');
Route::get('/product/{slug}', [CatalogController::class, 'product'])->name('product.show');

Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::view('/cart', 'pages.cart')->name('cart.show');
Route::view('/checkout', 'pages.checkout')->name('checkout.show');

Route::get('/orders/{order}/thank-you', [OrderController::class, 'thankYou'])->name('order.thank-you');
Route::get('/orders/{order}/invoice.pdf', [OrderController::class, 'invoice'])->name('order.invoice');

Route::redirect('/dashboard', '/account')->name('dashboard');

Route::get('/about', fn () => app(PageController::class)->show('about'))->name('page.about');
Route::get('/contact', fn () => app(PageController::class)->show('contact'))->name('page.contact');
Route::get('/page/{slug}', [PageController::class, 'show'])->name('page.show');

// Admin UI language (session-persisted; consumed by SetAdminLocale on panel routes).
Route::get('/admin/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, \App\Http\Middleware\SetAdminLocale::LOCALES, true), 404);
    session([\App\Http\Middleware\SetAdminLocale::SESSION_KEY => $locale]);
    return back(fallback: url('/admin'));
})->middleware('auth')->name('admin.locale');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::get('/account/orders/{order}', [AccountController::class, 'order'])->name('account.order');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
