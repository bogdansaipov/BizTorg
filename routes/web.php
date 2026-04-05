<?php

use App\Http\Controllers\AttributeAttributeValueController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use TCG\Voyager\Facades\Voyager;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

Route::get('/', [IndexController::class, 'index'])->name('index.show');
Route::get('/get-paginated-products', [IndexController::class, 'getPaginatedProducts'])->name('products.paginate');

// Product detail (public)
Route::get('/obyavlenie/{slug}', [ProductController::class, 'getProduct'])->name('product.get');

// Category browsing (public)
Route::get('/category/{slug}',            [CategoryController::class, 'index'])->name('category.show');
Route::get('/category/{slug}/filter',     [CategoryController::class, 'filterProducts'])->name('category.filter');    // Needs verification — method may not exist
Route::get('/category/{slug}/attributes', [CategoryController::class, 'getAttributes'])->name('category.attributes'); // Needs verification — method may not exist

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'generateSitemap']);

// Static pages
Route::get('/privacy-policy-document', fn () => view('privacy_policy'));
Route::get('/pages-facebook',           fn () => view('pages_show'));

/*
|--------------------------------------------------------------------------
| Social OAuth
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::get('google',             [SocialAuthController::class, 'redirectToGoogle'])->name('google.redirect');
    Route::get('google/callback',    [SocialAuthController::class, 'handleGoogleCallback']);
    Route::get('facebook',           [SocialAuthController::class, 'redirectToFacebook'])->name('facebook.redirect');
    Route::get('facebook/callback',  [SocialAuthController::class, 'handleFacebookCallback']);
    Route::get('telegram',           [SocialAuthController::class, 'redirectToTelegram'])->name('telegram.redirect');
    Route::get('telegram/callback',  [SocialAuthController::class, 'handleTelegramCallback']);
});

/*
|--------------------------------------------------------------------------
| Authenticated web routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // ── Profile ──────────────────────────────────────────────────────────────
    Route::prefix('profile')->group(function () {
        Route::get('/',           [ProfileController::class, 'getUserData'])->name('profile.view');
        Route::get('create',      [ProfileController::class, 'create'])->name('profile.navigate');
        Route::post('create/new', [ProfileController::class, 'store'])->name('profile.store');
        Route::get('edit',        [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('update',    [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('delete',   [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::get('products',    [ProfileController::class, 'getUserProducts'])->name('profile.products');
        Route::get('favorites',   [ProfileController::class, 'getUserFavorites'])->name('profile.favorites');
        Route::get('addProduct',  fn () => view('products.add_product')->with('section', 'add'))->name('profile.addProduct');
    });

    // ── Favorites ────────────────────────────────────────────────────────────
    Route::post('/favorites/toggle', [ProfileController::class, 'toggleFavorites'])->name('favorites.toggle');

    // ── Products ─────────────────────────────────────────────────────────────
    Route::post('/product/store',              [ProductController::class, 'createProduct'])->name('products.store');
    Route::put('/product/edit',                [ProductController::class, 'editProduct'])->name('products.update');
    Route::get('/product/fetch/{id}',          [ProductController::class, 'fetchSingleProduct'])->name('product.get.edit');
    Route::get('/fetch-attributes',            [ProductController::class, 'fetchAttributesBySubcategory'])->name('fetch.attributes');
    Route::get('/product/add',                 [ProductController::class, 'fetchProductAttributes'])->name('product.fetch');
    Route::delete('/product/image/{id}',       [ProductController::class, 'deleteImage'])->name('product.image.delete');

    // ── Regions (used by product form) ────────────────────────────────────────
    Route::get('/regions/parents',             [ProductController::class, 'getParentRegions']);
    Route::get('/regions/children/{parentId}', [ProductController::class, 'getChildRegions']);
});

/*
|--------------------------------------------------------------------------
| Standard auth routes (login, register, password reset, verification)
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Admin panel (Voyager)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {
    Voyager::routes();

    Route::post('attribute-attribute-values', [AttributeAttributeValueController::class, 'store'])
        ->name('voyager.attribute-attribute-values.store');
});
