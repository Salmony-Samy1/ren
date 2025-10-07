<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\CategoriesController;
use App\Http\Controllers\Frontend\MainServiceController;
use App\Http\Controllers\Frontend\ServicesController;
use App\Http\Controllers\Frontend\SearchController;
use App\Http\Controllers\Frontend\CitiesController;
use App\Http\Controllers\Frontend\RegionsController;
use App\Http\Controllers\Frontend\NeighbourhoodController;
use App\Http\Controllers\Frontend\AuthController;
use App\Http\Controllers\Frontend\SocialAuthController;
use App\Http\Controllers\Frontend\FaqController;
use App\Http\Controllers\Frontend\LegalController;
use App\Http\Controllers\Frontend\ExperienceController;
use App\Http\Controllers\Frontend\RestaurantMenuCategoryController;
use App\Http\Controllers\Frontend\NationalityController;
use App\Http\Controllers\Frontend\HobbyController;
use App\Http\Controllers\Public\CountriesController;
use App\Http\Controllers\Frontend\PagesController;


// Auth endpoints removed from public to avoid duplicate auth flows.
// Please use /api/v1/app/auth/* endpoints for OTP and login.

// Socialite OAuth
Route::get('/auth/google/redirect', [\App\Http\Controllers\Frontend\SocialiteAuthController::class, 'redirectGoogle']);
Route::get('/auth/google/callback', [\App\Http\Controllers\Frontend\SocialiteAuthController::class, 'callbackGoogle']);
Route::get('/auth/apple/redirect', [\App\Http\Controllers\Frontend\SocialiteAuthController::class, 'redirectApple']);
Route::get('/auth/apple/callback', [\App\Http\Controllers\Frontend\SocialiteAuthController::class, 'callbackApple']);

// Guest/Public routes for browsing content without auth
Route::get('/countries', [CountriesController::class, 'index']);
Route::get('/countries/dropdown', [CountriesController::class, 'dropdown']);
Route::get('/countries/{country}', [CountriesController::class, 'show']);
Route::get('/cities', [CitiesController::class, 'index']);
Route::get('/neighbourhoods', [NeighbourhoodController::class, 'index']);
Route::get('/regions', [RegionsController::class, 'index']);
Route::get('/categories', [CategoriesController::class, 'index']);
Route::get('/main-services', [MainServiceController::class, 'index']);
Route::get('/main-services/{mainService}', [MainServiceController::class, 'show']);

// Main Service Media Routes
Route::group(['prefix' => '/main-services/{mainService}'], function () {
    Route::get('/images', [MainServiceController::class, 'getImages']);
    Route::post('/images', [MainServiceController::class, 'uploadImage']);
    Route::post('/videos', [MainServiceController::class, 'uploadVideo']);
    Route::delete('/images/{mediaId}', [MainServiceController::class, 'deleteImage']);
    Route::delete('/videos/{mediaId}', [MainServiceController::class, 'deleteVideo']);
});

Route::group(['prefix' => '/pages'], function () {
    Route::get('/', [PagesController::class, 'index']);
    Route::get('/search', [PagesController::class, 'search']);
    Route::get('/by-audience/{audience}', [PagesController::class, 'byAudience']);
    Route::get('/available-types', [PagesController::class, 'getAvailableTypes']);
    Route::get('/available-audiences', [PagesController::class, 'getAvailableAudiences']);
    Route::get('/{slug}', [PagesController::class, 'show']);
});


// Main Service Requirements (Public)
Route::get('/main-services/{mainService}/countries/{country}/required-documents',   
    [\App\Http\Controllers\Frontend\OnboardingController::class, 'getRequiredDocuments']);
Route::get('/all-country-requirements/{mainServiceId?}', [\App\Http\Controllers\Frontend\OnboardingController::class, 'getAllCountryRequirements']);

Route::get('/nationalities', [NationalityController::class, 'index']);
Route::get('/hobbies', [HobbyController::class, 'index']);


Route::get('/faqs', [FaqController::class, 'index']);
Route::get('/faqs/{faq}', [FaqController::class, 'show']);
Route::get('/legal/{slug}', [LegalController::class, 'show']);
Route::get('/legal', [LegalController::class, 'index']);

// Catering Legal Terms (Public)
Route::get('/catering/legal-terms', [\App\Http\Controllers\Frontend\CateringLegalTermsController::class, 'getRequiredTerms']);


Route::group(['prefix' => '/services'], function () {
    Route::get('by-category/{category}', [ServicesController::class, 'index']);
    // المسارات الثابتة يجب أن تأتي قبل المسارات المتغيرة
    Route::get('/restaurants/{restaurant}/menu', [\App\Http\Controllers\Frontend\RestaurantMenuController::class, 'index']);
    Route::get('/restaurants/{restaurant}/tables', [\App\Http\Controllers\Frontend\RestaurantTablesController::class, 'index']);
    Route::get('/restaurant-menu-categories', [\App\Http\Controllers\Frontend\RestaurantMenuCategoryController::class, 'index']);
    // المسار المتغير يجب أن يأتي في النهاية
    Route::get('{service}', [ServicesController::class, 'show']);
});

// API v1 Public endpoints for restaurant tables
Route::get('/services/restaurants/{restaurant}/tables', [\App\Http\Controllers\Api\V1\Public\RestaurantTablesController::class, 'index']);
Route::get('/services/restaurants/{restaurant}/tables/{table}', [\App\Http\Controllers\Api\V1\Public\RestaurantTablesController::class, 'show']);

// مسارات رفع الصور العامة (بدون مصادقة)
Route::post('/restaurants/{restaurant}/tables/{table}/images', [\App\Http\Controllers\Frontend\RestaurantTablesController::class, 'uploadImage']);
Route::delete('/restaurants/{restaurant}/tables/{table}/images/{mediaId}', [\App\Http\Controllers\Frontend\RestaurantTablesController::class, 'deleteImage']);

// Public search endpoints
Route::group(['prefix' => '/search'], function () {
    Route::get('/advanced', [SearchController::class, 'advancedSearch']);
    Route::get('/quick', [SearchController::class, 'quickSearch']);
    Route::get('/services', [SearchController::class, 'searchServices']);
    Route::get('/users', [SearchController::class, 'searchUsers']);
    Route::get('/suggestions', [SearchController::class, 'suggestions']);
    Route::get('/stats', [SearchController::class, 'stats']);
});
Route::group(['prefix' => '/gifts'], function () {
    Route::get('/', [\App\Http\Controllers\Frontend\GiftController::class, 'index']);
});


// Event browse endpoints (with filters: gender_type, city_id, district, category_id)
Route::group(['prefix' => '/events'], function () {
    Route::get('/today', [\App\Http\Controllers\Frontend\EventBrowseController::class, 'today']);
    Route::get('/tomorrow', [\App\Http\Controllers\Frontend\EventBrowseController::class, 'tomorrow']);
    Route::get('/between', [\App\Http\Controllers\Frontend\EventBrowseController::class, 'betweenDates']);
});

// Catering browse endpoints (public)
Route::group(['prefix' => '/catering'], function () {
    Route::get('/', [\App\Http\Controllers\Frontend\CateringBrowseController::class, 'index']);
    Route::get('/by-category/{category}', [\App\Http\Controllers\Frontend\CateringBrowseController::class, 'byCategory']);
    Route::get('/{service}', [\App\Http\Controllers\Frontend\CateringBrowseController::class, 'show']);
});

// Properties (Main Service 4) browsing
Route::group(['prefix' => '/properties'], function () {
    Route::get('/categories', [\App\Http\Controllers\Frontend\PropertyBrowseController::class, 'categories']);
    Route::get('/search', [\App\Http\Controllers\Frontend\PropertyBrowseController::class, 'search']);
});


// Public reels and leaderboards
Route::get('/reels/latest', [\App\Http\Controllers\Frontend\ProviderReelsController::class, 'publicLatest']);
Route::get('/leaderboards/top-providers', [\App\Http\Controllers\Frontend\LeaderboardController::class, 'topProviders']);
Route::get('/leaderboards/top-catering', [\App\Http\Controllers\Frontend\LeaderboardController::class, 'topCatering']);
Route::get('/experiences/public', [App\Http\Controllers\Frontend\ExperienceController::class, 'publicIndex']);



Route::get('/assets', [\App\Http\Controllers\Frontend\PublicAssetsController::class, 'index']);
Route::get('/feed', [\App\Http\Controllers\Frontend\FeedController::class, 'publicFeed']);


// Reels counters
Route::post('/reels/{reel}/view', function(\App\Models\ProviderReel $reel){ $reel->increment('views'); return format_response(true, 'ok', ['views' => $reel->views]); });



Route::get('/user/{user}', [\App\Http\Controllers\Frontend\PublicProfileController::class, 'show']);
