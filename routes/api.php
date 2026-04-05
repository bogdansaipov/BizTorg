<?php

use App\Http\Controllers\Api\Auth\ApiSocialAuthController;
use App\Http\Controllers\Api\Auth\CustomLoginController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ConversationsController;
use App\Http\Controllers\Api\MessagesController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RegionsController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\ShopProfileController;
use App\Http\Controllers\ShopRatingController;
use App\Http\Controllers\ShopSubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — all prefixed with /v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Authentication ───────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register',               [CustomLoginController::class,    'register']);
        Route::post('login',                  [CustomLoginController::class,    'login']);
        Route::post('google',                 [ApiSocialAuthController::class,  'googleSignIn']);
        Route::post('facebook',               [ApiSocialAuthController::class,  'facebookSignIn']);
        Route::post('send-verification-code', [CustomLoginController::class,    'sendVerificationCode']);
        Route::post('verify-and-register',    [CustomLoginController::class,    'verifyAndRegister']);
    });

    // ── Users ────────────────────────────────────────────────────────────────
    Route::get('user/{id}',           [CustomLoginController::class, 'show']);
    Route::get('user/{id}/fcm-token', [CustomLoginController::class, 'getFcmToken']);
    Route::post('store-fcm-token',    [CustomLoginController::class, 'storeFcmToken']);
    Route::post('clear-fcm-token',    [CustomLoginController::class, 'clearFcmToken']);

    // ── Profiles ─────────────────────────────────────────────────────────────
    Route::get('profile/{id}',    [ProfileController::class, 'getUserDataJson']);
    Route::post('profile/update', [ProfileController::class, 'updateProfile']);

    // ── Regions ──────────────────────────────────────────────────────────────
    Route::get('regions',                          [RegionsController::class, 'fetchRegions']);
    Route::get('{parentRegionId}/child_regions',   [RegionsController::class, 'fetchChildRegions']);

    // ── Categories & Home ────────────────────────────────────────────────────
    Route::get('home',                               [CategoryController::class, 'homePage']);
    Route::get('categories',                         [CategoryController::class, 'fetchCategories']);
    Route::get('{categoryId}/subcategories',         [CategoryController::class, 'fetchSubcategories']);
    Route::get('find-category/subcategory/{id}',     [CategoryController::class, 'getCategory']);
    Route::get('search',                             [CategoryController::class, 'searchProducts']);
    Route::get('search-recommendations',             [CategoryController::class, 'searchRecommendations']);

    // ── Products (public) ────────────────────────────────────────────────────
    Route::get('{subcategoryId}/products',           [ProductController::class, 'getProducts']);
    Route::get('{subcategoryId}/attributes',         [ProductController::class, 'getAttributes']);
    Route::get('category/{categoryId}/products',     [ProductController::class, 'getProductsByCategory']);
    Route::get('filter-products',                    [ProductController::class, 'filterProducts']);
    Route::get('product/{productId}',                [ProductController::class, 'getProduct']);
    Route::get('product/slug/{productSlug}',         [ProductController::class, 'getProductBySlug']);
    Route::get('fetch/product/{id}',                 [ProductController::class, 'fetchSingleProduct']);
    Route::post('product/create',                    [ProductController::class, 'createProduct']);

    // ── Products (authenticated) ─────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('product/update/{id}',           [ProductController::class, 'updateProduct']);
        Route::delete('products/delete/{productId}', [ProductController::class, 'removeProduct']);
        Route::delete('product/image/{id}',          [ProductController::class, 'deleteImage']);
        Route::get('user/{uuid}/products',            [ProductController::class, 'getUserProducts']);
        Route::get('favorites',                       [ProductController::class, 'getFavorite']);
        Route::post('favorite/toggle',               [ProductController::class, 'toggleFavorites']);
        Route::get('user/favorites/{uuid}',           [ProductController::class, 'getFavoritesOfUser']);
    });

    // ── Messaging ────────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('send/message',                  [MessagesController::class,      'sendMessage']);
        Route::get('getMessages/{receiver_id}',      [MessagesController::class,      'getMessages']);
        Route::post('upload/chat-image',             [MessagesController::class,      'uploadChatImage']);
        Route::get('user/get/chat/conversations',    [ConversationsController::class, 'getChats']);
    });

    // ── Notifications ────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('notifications',                  [NotificationsController::class, 'index']);
        Route::post('notifications/mark-all-seen',   [NotificationsController::class, 'markAsSeen']);
        Route::post('notifications/mark-seen-for-chat', [NotificationsController::class, 'markSeenForChat']);
    });

    // ── Shops ────────────────────────────────────────────────────────────────
    Route::get('shops/{userId}',                     [ShopProfileController::class, 'getUserProducts']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('shop-profiles',                 [ShopProfileController::class,      'store']);
        Route::post('shop/update',                   [ShopProfileController::class,      'updateShopData']);
        Route::post('{shopId}/upload-profile-images',[ShopProfileController::class,      'updateImages']);
        Route::get('{shopId}/getShop',               [ShopProfileController::class,      'getShopProfile']);
        Route::post('subscribe/{shopId}',            [ShopSubscriptionController::class, 'subscribe']);
        Route::post('unsubscribe/{shopId}',          [ShopSubscriptionController::class, 'unsubscribe']);
        Route::post('shop/rate',                     [ShopRatingController::class,       'rateShop']);
    });
});
