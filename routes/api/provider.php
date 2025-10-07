<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\MyServicesController;
use App\Http\Controllers\Frontend\BookingController;
use App\Http\Controllers\Frontend\InvoiceController;
use App\Http\Controllers\Frontend\ConversationsController;
use App\Http\Controllers\Frontend\MessagesController;
use App\Http\Controllers\Frontend\AvailabilityController;
use App\Http\Controllers\Frontend\HelpSupportController;
use App\Http\Controllers\Frontend\OnboardingController;
use App\Http\Controllers\Frontend\CateringItemCategoriesController;

Route::group(['prefix' => '/services'], function () {
    Route::get('/', [MyServicesController::class, 'index']);
    Route::post('/', [MyServicesController::class, 'store']);
    Route::get('/{service}', [MyServicesController::class, 'show']);
    Route::put('/{service}', [MyServicesController::class, 'update']);
    Route::delete('/{service}', [MyServicesController::class, 'destroy']);

    // Availability blocks per service
    Route::get('/{service}/availability', [AvailabilityController::class, 'index']);
    Route::post('/{service}/availability', [AvailabilityController::class, 'store']);
    Route::delete('/{service}/availability/{block}', [AvailabilityController::class, 'destroy']);
});

// Catering Item Categories for Providers
Route::group(['prefix' => '/catering-categories'], function () {
    Route::get('/', [CateringItemCategoriesController::class, 'index']);
    Route::get('/search', [CateringItemCategoriesController::class, 'search']);
    Route::get('/stats', [CateringItemCategoriesController::class, 'stats']);
    Route::get('/{id}', [CateringItemCategoriesController::class, 'show']);
});


Route::group(['prefix' => '/onboarding', 'middleware' => ['auth:api', 'phone.verified']], function () {
    Route::post('/main-service', [\App\Http\Controllers\Frontend\OnboardingController::class, 'submitMainService']);
    Route::get('/main-service', [\App\Http\Controllers\Frontend\OnboardingController::class, 'index']);
});


// Service Country Validation (Protected)
Route::group(['middleware' => ['auth:api', 'phone.verified']], function () {
    Route::get('/categories/{categoryId}/available-countries', 
        [\App\Http\Controllers\Frontend\OnboardingController::class, 'getAvailableCountries']);
    Route::get('/main-services/{mainServiceId}/validation-summary', 
        [\App\Http\Controllers\Frontend\OnboardingController::class, 'getValidationSummary']);
});

// Provider Registration Requirements (Public)
Route::get('/registration/main-services/{mainServiceId}/countries/{countryId}/requirements',
    [\App\Http\Controllers\Frontend\OnboardingController::class, 'getRegistrationRequirements']);

// Onboarding Requirements (Public)
Route::get('/onboarding/requirements', 
    [\App\Http\Controllers\Frontend\OnboardingController::class, 'getOnboardingRequirements']);



Route::group(['prefix' => '/bookings'], function () {
    Route::get('/', [BookingController::class, 'providerIndex']);
    Route::put('/{booking}/approve', [BookingController::class, 'approve']);
    Route::put('/{booking}/reject', [BookingController::class, 'reject']);
});

Route::group(['prefix' => '/invoices'], function () {
    Route::get('/', [InvoiceController::class, 'index']);
    Route::get('/{invoice}', [InvoiceController::class, 'show']);
});

// Route::group(['prefix' => '/conversations'], function () {
//     Route::get('/', [ConversationsController::class, 'index']);
//     Route::post('/', [ConversationsController::class, 'store']);
//     Route::get('/{conversation}/messages', [MessagesController::class, 'index']);
//     Route::post('/{conversation}/messages', [MessagesController::class, 'store']);
// });


// Provider reels management
Route::group(['prefix' => '/reels'], function () {
    Route::get('/', [\App\Http\Controllers\Frontend\ProviderReelsController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Frontend\ProviderReelsController::class, 'store']);
    Route::delete('/{reel}', [\App\Http\Controllers\Frontend\ProviderReelsController::class, 'destroy']);
});

// Help & Support Center
Route::group(['prefix' => '/help-support'], function () {
    // 1. المحادثة المباشرة مع الدعم الفني
    Route::post('/start-chat', [HelpSupportController::class, 'startSupportChat']);
    
    // 2. قاعدة المعرفة - الأسئلة الشائعة
    Route::get('/knowledge-base', [HelpSupportController::class, 'getKnowledgeBase']);
    
    // 3. الإبلاغ عن مشكلة تقنية
    Route::post('/report-issue', [HelpSupportController::class, 'reportTechnicalIssue']);
    
    // 4. اقتراح تطوير
    Route::post('/submit-suggestion', [HelpSupportController::class, 'submitSuggestion']);
    
    // جلب البيانات الخاصة بالمستخدم
    Route::get('/my-tickets', [HelpSupportController::class, 'getMySupportTickets']);
    Route::get('/my-suggestions', [HelpSupportController::class, 'getMySuggestions']);
    Route::get('/ticket/{ticketId}', [HelpSupportController::class, 'getSupportTicketDetails']);
    Route::get('/suggestion/{suggestionId}', [HelpSupportController::class, 'getSuggestionDetails']);
});

// Conversations (تم تفعيلها)
Route::group(['prefix' => '/conversations'], function () {
    Route::get('/', [ConversationsController::class, 'index']);
    Route::post('/', [ConversationsController::class, 'store']);
    Route::post('/support', [ConversationsController::class, 'support']);
    Route::get('/{conversation}/messages', [MessagesController::class, 'index']);
    Route::post('/{conversation}/messages', [MessagesController::class, 'store']);
});

