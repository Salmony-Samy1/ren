<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\BookingController;
use App\Http\Controllers\Frontend\WalletController;
use App\Http\Controllers\Frontend\ReviewsController;
use App\Http\Controllers\Frontend\FavoriteController;
use App\Http\Controllers\Frontend\WishlistController;
use App\Http\Controllers\Frontend\ConversationsController;
use App\Http\Controllers\Frontend\MessagesController;
use App\Http\Controllers\Frontend\InvoiceController;
use App\Http\Controllers\Frontend\PaymentController;
use App\Http\Controllers\Frontend\SavedCardsController;
use App\Http\Controllers\Frontend\NotificationsController;
use App\Http\Controllers\Frontend\ReferralController;

Route::group(['prefix' => '/bookings'], function () {
    Route::get('/', [BookingController::class, 'index']);
    Route::post('/', [BookingController::class, 'store']);
    Route::post('/quote', [BookingController::class, 'quote']);
    Route::get('/check-availability', [BookingController::class, 'checkAvailability']);
    Route::get('/{booking}', [BookingController::class, 'show']);
    Route::put('/{booking}/status', [BookingController::class, 'updateStatus']);
    Route::put('/{booking}/privacy', [BookingController::class, 'updatePrivacy']);
    Route::put('/{booking}/privacy/viewers', [BookingController::class, 'updatePrivacyViewers']);
    Route::delete('/{booking}', [BookingController::class, 'cancel']);
});

Route::group(['prefix' => '/payments'], function () {
    Route::post('/process', [PaymentController::class, 'processPayment'])
        ->middleware(['throttle:5,1', 'payment.rate.limit']); // تقليل الحد إلى 5 طلبات في الدقيقة
    Route::get('/transactions', [PaymentController::class, 'getUserTransactions']);
    Route::get('/transactions/{transaction}', [PaymentController::class, 'showTransaction']);
    Route::get('/gateway-settings', [PaymentController::class, 'getGatewaySettings']);
});

// Saved Cards Management
Route::group(['prefix' => '/saved-cards'], function () {
    Route::get('/', [SavedCardsController::class, 'index']);
    Route::post('/create-token', [SavedCardsController::class, 'createToken'])
        ->middleware(['throttle:3,1', 'payment.rate.limit']); // تقليل الحد إلى 3 طلبات في الدقيقة
    Route::put('/set-default', [SavedCardsController::class, 'setDefault']);
    Route::delete('/delete', [SavedCardsController::class, 'destroy']);
    Route::get('/tap-config', [SavedCardsController::class, 'getTapConfig']);
});

Route::group(['prefix' => '/wallet'], function () {
    Route::get('/', [WalletController::class, 'index']);
    Route::post('/top-up', [WalletController::class, 'topUp'])
        ->middleware(['throttle:5,1', 'payment.rate.limit']); // Rate limiting للشحن
    Route::post('/transfer', [WalletController::class, 'transfer'])->middleware('throttle:10,1');
    Route::get('/settings', [WalletController::class, 'getSettings']);
    Route::post('/currency', [\App\Http\Controllers\Frontend\WalletCurrencyController::class, 'set'])->middleware('throttle:3,1');
    
    // Disabled: legacy wallet top-up endpoint
    Route::post('/charge', [WalletController::class, 'deposit'])->middleware('throttle:1,1');
});

Route::group(['prefix' => '/reviews'], function () {
    Route::get('/my-reviews', [ReviewsController::class, 'myReviews']);
    Route::post('/services/{service}', [ReviewsController::class, 'store']);
    Route::get('/services/{service}', [ReviewsController::class, 'index']);
    Route::get('/{review}', [ReviewsController::class, 'show']);
    Route::put('/{review}', [ReviewsController::class, 'update']);
    Route::delete('/{review}', [ReviewsController::class, 'destroy']);
});

Route::group(['prefix' => '/favorites'], function () {
    Route::get('/', [FavoriteController::class, 'index']);
    Route::post('/add', [FavoriteController::class, 'add']);
    Route::delete('/remove/{service}', [FavoriteController::class, 'remove']);
});

Route::group(['prefix' => '/wishlist'], function () {
    Route::get('/services', [WishlistController::class, 'getAllServices']);
    Route::get('/activities', [WishlistController::class, 'getAllActivities']);
    Route::post('/add-service/{service}', [WishlistController::class, 'addService']);
    Route::delete('/remove-service/{service}', [WishlistController::class, 'removeService']);
    Route::post('/add-activity/{activity}', [WishlistController::class, 'addActivity']);
    Route::delete('/remove-activity/{activity}', [WishlistController::class, 'removeActivity']);
});

Route::group(['prefix' => '/conversations'], function () {
    Route::get('/', [ConversationsController::class, 'index']);
    Route::post('/', [ConversationsController::class, 'store']);
    Route::get('/{conversation}/messages', [MessagesController::class, 'index']);
    Route::post('/{conversation}/messages', [MessagesController::class, 'store']);
});

Route::group(['prefix' => '/invoices'], function () {
    Route::get('/', [InvoiceController::class, 'index']);
    Route::get('/{invoice}', [InvoiceController::class, 'show']);
    Route::post('/', [InvoiceController::class, 'store']);
    Route::put('/{invoice}/status', [InvoiceController::class, 'updateStatus']);
    Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel']);
    Route::get('/{invoice}/export-pdf', [InvoiceController::class, 'exportPdf']);
    Route::post('/{invoice}/send-email', [InvoiceController::class, 'sendEmail']);
    Route::get('/statistics', [InvoiceController::class, 'statistics']);
});

Route::group(['prefix' => '/notifications'], function () {
    Route::get('/', [NotificationsController::class, 'index']);
    Route::get('/read/{notification}', [NotificationsController::class, 'read']);
    Route::get('/unread/count', [NotificationsController::class, 'unreadCount']);
});

Route::group(['prefix' => '/referrals'], function () {
    Route::get('/', [\App\Http\Controllers\Frontend\ReferralController::class, 'show']);
    Route::get('/stats', [\App\Http\Controllers\Frontend\ReferralController::class, 'stats']);
    Route::post('/generate-code', [\App\Http\Controllers\Frontend\ReferralController::class, 'generateCode']);
    Route::post('/validate-code', [\App\Http\Controllers\Frontend\ReferralController::class, 'validateCode']);
    Route::get('/list', [\App\Http\Controllers\Frontend\ReferralController::class, 'referrals']);
});


Route::get('/feed', [\App\Http\Controllers\Frontend\FeedController::class, 'index']);

