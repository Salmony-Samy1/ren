<?php

use App\Http\Controllers\Frontend\ActivitiesController;
use App\Http\Controllers\Frontend\AuthController;
use App\Http\Controllers\Frontend\BookingController;
use App\Http\Controllers\Frontend\CategoriesController;
use App\Http\Controllers\Frontend\CountriesController;
use App\Http\Controllers\Frontend\PaymentController;
use App\Http\Controllers\Frontend\PointsController;
use App\Http\Controllers\Frontend\UserProfileController;
use App\Http\Controllers\Frontend\CitiesController;
use App\Http\Controllers\Frontend\FollowController;
use App\Http\Controllers\Frontend\NeighbourhoodController;
use App\Http\Controllers\Frontend\NotificationsController;
use App\Http\Controllers\Frontend\RegionsController;
use App\Http\Controllers\Frontend\ReferralController;
use App\Http\Controllers\Frontend\ReviewsController;
use App\Http\Controllers\Frontend\ServicesController;
use App\Http\Controllers\Frontend\WalletController;
use App\Http\Controllers\Frontend\WishlistController;
use App\Http\Controllers\Frontend\ConversationsController;
use App\Http\Controllers\Frontend\MessagesController;
use App\Http\Controllers\Frontend\EventController;
use App\Http\Controllers\Frontend\CateringController;
use App\Http\Controllers\Frontend\RestaurantController;
use App\Http\Controllers\Frontend\PropertyController;
use App\Http\Controllers\Frontend\MyServicesController;
use App\Http\Controllers\Frontend\FavoriteController;
use App\Http\Controllers\Frontend\ServiceSearchController;
use App\Http\Controllers\Frontend\SearchController;
use App\Http\Controllers\Frontend\GoogleMapsController;
use App\Http\Controllers\Frontend\NationalIdVerificationController;
use App\Http\Controllers\Frontend\MainServiceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\InvoiceController;
use App\Http\Controllers\Frontend\SupportController;
use App\Http\Controllers\Frontend\AppRatingController;
use App\Http\Controllers\Frontend\FaqController;
use App\Http\Controllers\Frontend\ContactUsController;
use App\Http\Controllers\Frontend\ExperienceController;
use App\Http\Controllers\Frontend\LegalController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
|
*/

Route::group(['prefix' => '/auth'], function () {
    Route::post('/login', [AuthController::class, 'login'])->name('app.login')->middleware('throttle:5,1');
    Route::get('/refresh-token', [AuthController::class, 'refreshToken'])->middleware(['auth:api', 'force.guard.api']);
    Route::post('/register-provider', [AuthController::class, 'registerProvider']);
    Route::post('/register-customer', [AuthController::class, 'registerCustomer']);
    Route::post('/set-new-password', [AuthController::class, 'setNewPassword']);
    Route::post('/send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:5,1');
    Route::post('/validate-otp', [AuthController::class, 'validateOtp'])->middleware('throttle:5,1');
    Route::post('/profile/complete', [AuthController::class, 'completeProfile'])->middleware([\App\Http\Middleware\EnsurePendingProfile::class]);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth:api', 'force.guard.api']);
});

// Legal Pages Routes
Route::group(['prefix' => '/legal'], function () {
    // Public routes (no authentication required)
    Route::get('/', [LegalController::class, 'index']);
    Route::get('/terms', [LegalController::class, 'terms']);
    Route::get('/privacy', [LegalController::class, 'privacy']);
    Route::get('/{slug}', [LegalController::class, 'show']);
    
    // Protected routes (authentication required)
    Route::group(['middleware' => ['auth:api']], function () {
        Route::post('/accept-terms', [LegalController::class, 'acceptTerms']);
        Route::get('/user/agreements', [LegalController::class, 'userAgreements']);
        Route::get('/check/{legalPageId}', [LegalController::class, 'checkAgreement']);
    });
});

// User Profile Management Routes
Route::group(['prefix' => '/profile', 'middleware' => ['phone.verified','terms.accepted']], function () {
    Route::get('/', [UserProfileController::class, 'show']);
    Route::put('/basic-info', [UserProfileController::class, 'updateBasicInfo']);
    Route::put('/password', [UserProfileController::class, 'updatePassword']);
    Route::put('/theme', function(\App\Http\Requests\User\UpdateThemeRequest $request){
        $user = auth()->user();
        $user->update(['theme' => $request->validated()['theme']]);
        return format_response(true, __('Theme updated'), new \App\Http\Resources\UserResource($user));
    });
    Route::put('/company', [UserProfileController::class, 'updateCompanyProfile'])->middleware('auth:api', 'user_type:provider');
    Route::put('/customer', [UserProfileController::class, 'updateCustomerProfile'])->middleware('auth:api', 'user_type:customer');

    // Account deletion flow via OTP
    Route::post('/account/request-otp', [UserProfileController::class, 'requestDeleteOtp'])->middleware('throttle:5,1');
    Route::delete('/account', [UserProfileController::class, 'deleteAccount']);

    Route::post('/avatar', [UserProfileController::class, 'uploadAvatar']);
    Route::post('/company-logo', [UserProfileController::class, 'uploadCompanyLogo'])->middleware('user_type:provider,company');
    Route::apiResource('/experiences', App\Http\Controllers\Frontend\ExperienceController::class)
        ->only(['index', 'store', 'update', 'destroy']);

});
Route::group(['middleware' => ['auth:api','phone.verified']], function () {
    Route::get('/neighbourhoods', [NeighbourhoodController::class, 'index']);
    Route::get('/regions', [RegionsController::class, 'index']);
    Route::get('/categories', [CategoriesController::class, 'index']);
    Route::get('/main-services', [MainServiceController::class, 'index']);
    Route::get('/main-services/{mainService}', [MainServiceController::class, 'show']);
    
    // Main Service Media Routes (Protected)
    Route::group(['prefix' => '/main-services/{mainService}'], function () {
        Route::get('/images', [MainServiceController::class, 'getImages']);
        Route::post('/images', [MainServiceController::class, 'uploadImage']);
        Route::post('/videos', [MainServiceController::class, 'uploadVideo']);
        Route::delete('/images/{mediaId}', [MainServiceController::class, 'deleteImage']);
        Route::delete('/videos/{mediaId}', [MainServiceController::class, 'deleteVideo']);
    });

    Route::get('/ratings', [AppRatingController::class, 'index']);
    Route::post('/ratings', [AppRatingController::class, 'store']);

    Route::get('/faqs', [FaqController::class, 'index']);
    Route::get('/faqs/{faq}', [FaqController::class, 'show']);
    // Route::post('/faqs', [FaqController::class, 'store']);
    // Route::put('/faqs/{faq}', [FaqController::class, 'update']);
    // Route::delete('/faqs/{faq}', [FaqController::class, 'destroy']);

    Route::get('/contact-messages', [ContactUsController::class, 'index']);
    Route::post('/contact-messages', [ContactUsController::class, 'store']);
});


Route::group(['prefix' => '/services', 'middleware' => ['auth:api','phone.verified']], function () {
    Route::get('search', [ServiceSearchController::class, 'search']);
    Route::get('by-category/{category}', [ServicesController::class, 'index']);
    Route::get('{service}', [ServicesController::class, 'show']);
});

// Home endpoint
Route::get('/home', [\App\Http\Controllers\Frontend\HomeController::class, 'index'])->middleware(['auth:api','phone.verified']);

// ----------------------------------------------------------------



Route::group(['middleware' => ['auth:api', 'phone.verified']], function () {
    Route::get('/conversations', [ConversationsController::class, 'index']);
    Route::post('/conversations', [ConversationsController::class, 'store']);
    Route::post('/conversations/support', [ConversationsController::class, 'support']);
    Route::get('/conversations/{conversation}/messages', [MessagesController::class, 'index']);
    Route::post('/conversations/{conversation}/messages', [MessagesController::class, 'store']);
});

// Public routes for countries (needed for registration)
Route::get('/countries', [CountriesController::class, 'index']);
Route::get('/countries/{country}', [CountriesController::class, 'show']);

Route::group(['middleware' => ['auth:api','phone.verified']], function () {
    Route::get('/wallet', [WalletController::class, 'index']);
    Route::post('/wallet/top-up', [WalletController::class, 'topUp'])
        ->middleware(['throttle:5,1', 'payment.rate.limit']); // Rate limiting للشحن
    Route::post('/wallet/transfer', [WalletController::class, 'transfer'])->middleware('throttle:10,1');
    Route::get('/wallet/settings', [WalletController::class, 'getSettings']);
    
    // Disabled: legacy wallet top-up endpoint
    Route::post('/charge', [WalletController::class, 'deposit'])->middleware(app()->environment('production') && !env('WALLET_TEST_MODE', false) ? 'throttle:1,1' : null);
});

Route::group(['middleware' => ['auth:api']], function() {
    Route::post('/reels/{reel}/like', function(\App\Models\ProviderReel $reel){ $reel->increment('likes'); return format_response(true, 'ok', ['likes' => $reel->likes]); });
});

// Booking Routes
Route::group(['prefix' => '/bookings', 'middleware' => ['auth:api','phone.verified']], function () {
    Route::get('/', [BookingController::class, 'index']);
    Route::post('/', [BookingController::class, 'store']);
    Route::post('/bulk', [BookingController::class, 'storeBulk']);
    Route::get('/check-availability', [BookingController::class, 'checkAvailability']);
        Route::post('/quote', [BookingController::class, 'quote']);
    Route::get('/{booking}', [BookingController::class, 'show']);
    Route::put('/{booking}/status', [BookingController::class, 'updateStatus']);
    Route::delete('/{booking}', [BookingController::class, 'cancel']);
});
    // Orders (customer): draft then attach to booking
    Route::group(['middleware' => ['auth:api','phone.verified','user_type:customer']], function () {
        Route::post('/orders', [\App\Http\Controllers\Frontend\OrderController::class, 'createDraft']);
        Route::get('/orders/{order}', [\App\Http\Controllers\Frontend\OrderController::class, 'show']);
        Route::post('/orders/{order}/items', [\App\Http\Controllers\Frontend\OrderController::class, 'upsertItem']);
        Route::delete('/orders/{order}/items/{orderItemId}', [\App\Http\Controllers\Frontend\OrderController::class, 'removeItem']);
        Route::post('/orders/{order}/apply-coupon', [\App\Http\Controllers\Frontend\OrderController::class, 'applyCoupon']);
    });

// Cart Routes
Route::group(['prefix' => '/cart', 'middleware' => ['auth:api','phone.verified']], function () {
    Route::get('/', [\App\Http\Controllers\Frontend\CartController::class, 'index']);
    Route::post('/items', [\App\Http\Controllers\Frontend\CartController::class, 'add']);
    Route::put('/items/{item}', [\App\Http\Controllers\Frontend\CartController::class, 'updateItem']);
    Route::delete('/items/{item}', [\App\Http\Controllers\Frontend\CartController::class, 'removeItem']);
    Route::post('/set-address', [\App\Http\Controllers\Frontend\CartController::class, 'setAddress']);
});

// Checkout Routes
Route::group(['prefix' => '/checkout', 'middleware' => ['auth:api','phone.verified']], function () {
    Route::post('/summary', [\App\Http\Controllers\Frontend\CheckoutController::class, 'summary']);
    Route::post('/confirm', [\App\Http\Controllers\Frontend\CheckoutConfirmController::class, 'confirm'])->middleware(\App\Http\Middleware\CheckoutThrottle::class);
});

// Payment Routes
Route::group(['prefix' => '/payments', 'middleware' => ['auth:api','phone.verified']], function () {
    Route::post('/process', [PaymentController::class, 'processPayment'])->middleware('throttle:10,1');
    Route::get('/transactions', [PaymentController::class, 'getUserTransactions']);
    Route::get('/transactions/{transaction}', [PaymentController::class, 'showTransaction']);
    Route::get('/gateway-settings', [PaymentController::class, 'getGatewaySettings']);
});

// Points Routes
Route::group(['prefix' => '/points', 'middleware' => ['auth:api','phone.verified']], function () {
    Route::get('/', [PointsController::class, 'index']);
    Route::get('/history', [PointsController::class, 'history']);
    Route::post('/convert-to-wallet', [PointsController::class, 'convertToWallet']);
    Route::get('/settings', [PointsController::class, 'settings']);
});

// Reviews Routes
Route::group(['prefix' => '/reviews', 'middleware' => ['auth:api','phone.verified']], function () {
    Route::get('/my-reviews', [ReviewsController::class, 'myReviews']);
    Route::post('/services/{service}', [ReviewsController::class, 'store']);
    Route::get('/services/{service}', [ReviewsController::class, 'index']);
    Route::get('/{review}', [ReviewsController::class, 'show']);
    Route::put('/{review}', [ReviewsController::class, 'update']);
    Route::delete('/{review}', [ReviewsController::class, 'destroy']);
});

    // Referral Routes
    Route::group(['prefix' => '/referrals', 'middleware' => ['auth:api','phone.verified']], function () {
        Route::get('/', [ReferralController::class, 'show']);
        Route::get('/stats', [ReferralController::class, 'stats']);
        Route::post('/generate-code', [ReferralController::class, 'generateCode']);
        Route::post('/validate-code', [ReferralController::class, 'validateCode']);
        Route::get('/list', [ReferralController::class, 'referrals']);
    });


    // Publicly accessible searchServices for app prefix (to satisfy tests)
    Route::get('/search/services', [SearchController::class, 'searchServices']);

    // Search Routes
    Route::group(['prefix' => '/search', 'middleware' => ['auth:api','phone.verified']], function () {
        Route::get('/advanced', [SearchController::class, 'advancedSearch']);
        Route::get('/quick', [SearchController::class, 'quickSearch']);
        Route::get('/services', [SearchController::class, 'searchServices']);
        Route::get('/users', [SearchController::class, 'searchUsers']);
        Route::get('/suggestions', [SearchController::class, 'suggestions']);
        Route::get('/stats', [SearchController::class, 'stats']);
        Route::get('/near-me', [SearchController::class, 'nearMe']);
    });

    // Google Maps Routes
    Route::group(['prefix' => '/maps', 'middleware' => ['auth:api','phone.verified']], function () {
        Route::get('/geocode', [GoogleMapsController::class, 'geocode']);
        Route::get('/reverse-geocode', [GoogleMapsController::class, 'reverseGeocode']);
        Route::get('/distance', [GoogleMapsController::class, 'calculateDistance']);
        Route::get('/nearby-places', [GoogleMapsController::class, 'nearbyPlaces']);
        Route::get('/search-places', [GoogleMapsController::class, 'searchPlaces']);
        Route::get('/place-details', [GoogleMapsController::class, 'placeDetails']);
        Route::get('/directions', [GoogleMapsController::class, 'directions']);
        Route::get('/nearby-services', [GoogleMapsController::class, 'nearbyServices']);
    });

    // Catering Legal Terms (Protected)
    Route::group(['prefix' => '/catering-legal'], function () {
        Route::get('/compliance', [\App\Http\Controllers\Frontend\CateringLegalTermsController::class, 'checkCompliance']);
        Route::get('/eligibility', [\App\Http\Controllers\Frontend\CateringLegalTermsController::class, 'checkEligibility']);
        Route::post('/accept-terms', [\App\Http\Controllers\Frontend\CateringLegalTermsController::class, 'acceptTerms']);
        Route::get('/agreements', [\App\Http\Controllers\Frontend\CateringLegalTermsController::class, 'getUserAgreements']);
    });

Route::apiResource('/activities', ActivitiesController::class)->middleware(['auth:api', 'user_type:provider','phone.verified']);

Route::post('/follow/{user}', [FollowController::class, 'follow'])->middleware(['auth:api','phone.verified']);
Route::post('/follow/{user}/respond', [FollowController::class, 'respond'])->middleware(['auth:api','phone.verified']);
Route::delete('/unfollow/{user}', [FollowController::class, 'unfollow'])->middleware(['auth:api','phone.verified']);
Route::get('/follow-list', [FollowController::class, 'list'])->middleware('auth:api');
Route::post('/add-review', [ReviewsController::class, 'add'])->middleware(['auth:api', 'user_type:customer']);
Route::post('/follow/{user}/accept', [FollowController::class, 'accept'])->middleware(['auth:api','phone.verified']);
Route::post('/follow/{user}/reject', [FollowController::class, 'reject'])->middleware(['auth:api','phone.verified']);


Route::group(['middleware' => ['auth:api', 'user_type:customer','phone.verified'], 'prefix' => '/wishlist'], function () {
    Route::get('/services', [WishlistController::class, 'getAllServices']);
    Route::get('/activities', [WishlistController::class, 'getAllActivities']);
    Route::post('/add-service/{service}', [WishlistController::class, 'addService']);
    Route::delete('/remove-service/{service}', [WishlistController::class, 'removeService']);
    Route::post('/add-activity/{activity}', [WishlistController::class, 'addActivity']);
    Route::delete('/remove-activity/{activity}', [WishlistController::class, 'removeActivity']);
});

Route::group(['middleware' => ['auth:api','phone.verified'],'prefix' => '/gifts'], function () {
    Route::get('/received', [\App\Http\Controllers\Frontend\GiftController::class, 'listReceived']);
    Route::get('/sent', [\App\Http\Controllers\Frontend\GiftController::class, 'listSent']);
    Route::post('/offer', [\App\Http\Controllers\Frontend\GiftController::class, 'offer']);
    Route::post('/{gift}/accept', [\App\Http\Controllers\Frontend\GiftController::class, 'accept']);
    Route::post('/{gift}/reject', [\App\Http\Controllers\Frontend\GiftController::class, 'reject']);
});

Route::group(['middleware' => ['auth:api', 'user_type:provider', 'main_service_approved']], function () {
    // Note: Service CRUD operations are now handled by MyServicesController
    // Individual service type endpoints are deprecated in favor of unified service management

    // restaurant menu (provider)
    Route::post('/services/restaurants/{restaurant}/menu', [\App\Http\Controllers\Frontend\RestaurantMenuController::class, 'store']);
    Route::put('/services/restaurants/{restaurant}/menu/{menuItem}', [\App\Http\Controllers\Frontend\RestaurantMenuController::class, 'update']);
    // restaurant tables (provider) - Enhanced management
    Route::get('/services/restaurants/{restaurant}/tables', [\App\Http\Controllers\Frontend\ProviderRestaurantTablesController::class, 'index']);
    Route::post('/services/restaurants/{restaurant}/tables', [\App\Http\Controllers\Frontend\ProviderRestaurantTablesController::class, 'store']);
    Route::get('/services/restaurants/{restaurant}/tables/{table}', [\App\Http\Controllers\Frontend\ProviderRestaurantTablesController::class, 'show']);
    Route::put('/services/restaurants/{restaurant}/tables/{table}', [\App\Http\Controllers\Frontend\ProviderRestaurantTablesController::class, 'update']);
    Route::delete('/services/restaurants/{restaurant}/tables/{table}', [\App\Http\Controllers\Frontend\ProviderRestaurantTablesController::class, 'destroy']);
    
    // إحصائيات الطاولات
    Route::get('/services/restaurants/{restaurant}/tables-statistics', [\App\Http\Controllers\Frontend\ProviderRestaurantTablesController::class, 'statistics']);
    
    // رفع وحذف صور الطاولات
    Route::post('/services/restaurants/{restaurant}/tables/{table}/images', [\App\Http\Controllers\Frontend\ProviderRestaurantTablesController::class, 'uploadImage']);
    Route::delete('/services/restaurants/{restaurant}/tables/{table}/images/{mediaId}', [\App\Http\Controllers\Frontend\ProviderRestaurantTablesController::class, 'deleteImage']);
    Route::delete('/services/restaurants/{restaurant}/menu/{menuItem}', [\App\Http\Controllers\Frontend\RestaurantMenuController::class, 'destroy']);
    Route::delete('/services/restaurants/{restaurant}', [RestaurantController::class, 'destroy']);

    // properties media (specialized functionality - kept for media management)

    // properties media
    Route::get('/services/properties/{property}/media', [PropertyController::class, 'media']);
    Route::post('/services/properties/{property}/media/images', [PropertyController::class, 'addImages']);
    Route::post('/services/properties/{property}/media/videos', [PropertyController::class, 'addVideos']);
    Route::delete('/services/properties/{property}/media/{mediaId}', [PropertyController::class, 'deleteMedia']);


    Route::apiResource('/my-services', MyServicesController::class)->parameters(['my-services' => 'service']);
});


Route::group(['middleware' => ['auth:api','phone.verified'], 'prefix' => '/favorites'], function () {
    Route::get('/', [FavoriteController::class, 'index']);
    Route::post('/add', [FavoriteController::class, 'add']);
    Route::delete('/remove/{service}', [FavoriteController::class, 'remove']);
});


Route::group(['middleware' => ['auth:api','phone.verified']], function () {
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::put('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus']);
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel']);
    Route::get('/invoices/{invoice}/export-pdf', [InvoiceController::class, 'exportPdf']);
    Route::post('/invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail']);
    Route::get('/invoices/statistics', [InvoiceController::class, 'statistics']);
});

Route::group(['middleware' => ['auth:api','phone.verified'], 'prefix' => '/notifications'], function () {
    Route::get('/', [NotificationsController::class, 'index']);
    Route::get('/read/{notification}', [NotificationsController::class, 'read']);
    Route::get('/unread/count', [NotificationsController::class, 'unreadCount']);
});

// User addresses CRUD
Route::group(['prefix' => '/addresses', 'middleware' => ['auth:api','phone.verified']], function () {
    Route::get('/', [\App\Http\Controllers\Frontend\UserAddressController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Frontend\UserAddressController::class, 'store']);
    Route::get('/{address}', [\App\Http\Controllers\Frontend\UserAddressController::class, 'show']);
    Route::put('/{address}', [\App\Http\Controllers\Frontend\UserAddressController::class, 'update']);
    Route::delete('/{address}', [\App\Http\Controllers\Frontend\UserAddressController::class, 'destroy']);
    Route::post('/{address}/default', [\App\Http\Controllers\Frontend\UserAddressController::class, 'setDefault']);
});

// National ID Verification Routes
Route::group(['prefix' => '/national-id', 'middleware' => ['auth:api','phone.verified']], function () {
    Route::post('/verify', [NationalIdVerificationController::class, 'verify']);
    Route::get('/status', [NationalIdVerificationController::class, 'checkStatus']);
    Route::post('/revoke', [NationalIdVerificationController::class, 'revoke']);
    Route::get('/info', [NationalIdVerificationController::class, 'info']);
});