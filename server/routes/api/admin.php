<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\CustomersController;
use App\Http\Controllers\Admin\PointsController;
use App\Http\Controllers\Admin\Providers\ProvidersController;
use App\Http\Controllers\Admin\Users\UsersController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\PolicyController;
use App\Http\Controllers\Admin\TrainingVideoController;
use App\Http\Controllers\Admin\ReviewsController;
use App\Http\Controllers\Admin\SmsController;
use App\Http\Controllers\Admin\NationalIdVerificationController;
use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\EventsController;
use App\Http\Controllers\Admin\CateringController;
use App\Http\Controllers\Admin\TableReservationsController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\MainServiceController;
use App\Http\Controllers\Admin\CateringItemCategoriesController;
use App\Http\Controllers\Frontend\RestaurantMenuCategoryController;

Route::group(['prefix' => '/auth'], function () {
    Route::post('/login', [AuthController::class, 'login'])->name('admin.api.login');
    Route::get('/refresh-token', [AuthController::class, 'refreshToken'])->middleware('auth:api');
    Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
});


Route::group(['middleware' => ['admin.api.auth', 'throttle:admin']], function () {
    
    // Users Management API Routes
    Route::group(['prefix' => '/users'], function () {
        Route::get('/', [UsersController::class, 'index']);
        Route::get('/{user}', [UsersController::class, 'show']);
        Route::patch('/{user}/status', [UsersController::class, 'updateStatus']);
        Route::patch('/{user}/approve', [UsersController::class, 'approve']);
        Route::patch('/{user}/reject', [UsersController::class, 'reject']);
        Route::get('/{user}/login-history', [UsersController::class, 'loginHistory']);
        Route::get('/login-history', [UsersController::class, 'loginHistory']);
        Route::get('/{user}/activities', [UsersController::class, 'activities']);
        Route::get('/{user}/warnings', [UsersController::class, 'warnings']);
        Route::get('/{user}/notifications', [UsersController::class, 'notifications']);
    });

    // Providers Management API Routes
    Route::group(['prefix' => '/providers'], function () {
        // Provider CRUD
        Route::get('/', [ProvidersController::class, 'index']);
        Route::get('/{provider}', [ProvidersController::class, 'show']);
        Route::patch('/{provider}/status', [ProvidersController::class, 'updateStatus']);
        Route::patch('/{provider}/approve', [ProvidersController::class, 'approve']);
        Route::patch('/{provider}/reject', [ProvidersController::class, 'reject']);
        
        // Provider Performance
        Route::get('/{provider}/performance', [ProvidersController::class, 'performance']);
        
        // Provider Reviews
        Route::get('/{provider}/reviews', [ProvidersController::class, 'reviews']);
        Route::patch('/reviews/{review}/approve', [ProvidersController::class, 'approveReview']);
        Route::patch('/reviews/{review}/reject', [ProvidersController::class, 'rejectReview']);
        
        // Provider Documents
        Route::get('/{provider}/documents', [ProvidersController::class, 'documents']);
        
        // Provider Alerts
        Route::get('/{provider}/alerts', [ProvidersController::class, 'alerts']);
        Route::patch('/alerts/{alert}/read', [ProvidersController::class, 'markAlertAsRead']);
        Route::patch('/alerts/{alert}/acknowledge', [ProvidersController::class, 'acknowledgeAlert']);
        Route::patch('/alerts/{alert}/resolve', [ProvidersController::class, 'resolveAlert']);
        
        // Provider Comparison
        Route::get('/comparison/data', [ProvidersController::class, 'comparison']);
    });

    // Other API Resources
    Route::apiResource('/roles', RolesController::class);
    Route::apiResource('/categories', CategoryController::class);
    Route::apiResource('/main-services', MainServiceController::class);

    // Properties Management (moved out of /events)
    Route::group(['prefix' => '/properties'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\PropertiesController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\PropertiesController::class, 'store']);
        Route::get('/calendar', [\App\Http\Controllers\Admin\PropertiesController::class, 'calendar']);
        Route::post('/{property}/pricing-rules', [\App\Http\Controllers\Admin\PropertiesController::class, 'storePricingRule']);
        Route::post('/{property}/sync', [\App\Http\Controllers\Admin\PropertiesController::class, 'sync']);
        Route::post('/{property}/google-places-link', [\App\Http\Controllers\Admin\PropertiesController::class, 'linkGooglePlace']);
    });

    // Events (Admin) - نقاط متكاملة لإدارة الفعاليات وفقاً للتوثيق
    Route::group(['prefix' => '/events'], function () {
        // إدارة الفعاليات الأساسية
        Route::get('/', [\App\Http\Controllers\Admin\EventsController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\EventsController::class, 'store']);
        Route::get('/{event}', [\App\Http\Controllers\Admin\EventsController::class, 'show']);
        Route::put('/{event}', [\App\Http\Controllers\Admin\EventsController::class, 'update']);
        Route::delete('/{event}', [\App\Http\Controllers\Admin\EventsController::class, 'destroy']);
        
        // إحصائيات تتبع التذاكر (من التوثيق)
        Route::get('/{event}/analytics', [\App\Http\Controllers\Admin\EventsController::class, 'analytics']);
        
        // إدارة الحضور (من التوثيق)
        Route::get('/{event}/attendance/analytics', [\App\Http\Controllers\Admin\EventsController::class, 'attendanceAnalytics']);
        Route::get('/{event}/attendance/list', [\App\Http\Controllers\Admin\EventsController::class, 'attendanceList']);
        Route::post('/{event}/attendance/check-in', [\App\Http\Controllers\Admin\EventsController::class, 'checkIn']);
        
        // التحكم في المحتوى (من التوثيق)
        Route::put('/{event}/content/text', [\App\Http\Controllers\Admin\EventsController::class, 'updateContentText']);
        Route::post('/{event}/content/media', [\App\Http\Controllers\Admin\EventsController::class, 'uploadMedia']);
        Route::delete('/{event}/media/{media}', [\App\Http\Controllers\Admin\EventsController::class, 'deleteMedia']);
        
        // النقاط الموجودة مسبقاً
        Route::get('/{event}/attendees', [\App\Http\Controllers\Admin\EventsController::class, 'attendees']);
        Route::patch('/{event}/attendance', [\App\Http\Controllers\Admin\EventsController::class, 'updateAttendance']);
    });

    // جلب قائمة الطلبات (من التوثيق)
    Route::group(['prefix' => '/orders'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\OrdersController::class, 'index']);
    });

    // مراجعة محتوى المستخدمين (من التوثيق)
    Route::group(['prefix' => '/user-content/reviews'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\UserContentReviewController::class, 'index']);
        Route::put('/{content}/status', [\App\Http\Controllers\Admin\UserContentReviewController::class, 'updateStatus']);
    });

    // Table Reservations (Admin)
    Route::group(['prefix' => '/reservations/tables'], function () {
        Route::get('/', [TableReservationsController::class, 'index']);
        Route::post('/', [TableReservationsController::class, 'store']);
        Route::get('/calendar', [TableReservationsController::class, 'calendar']);
        Route::patch('/{reservation}/status', [TableReservationsController::class, 'updateStatus']);
    });

    // Venues operating hours (use Restaurant as venue)
    Route::post('/venues/{venue}/operating-hours', [TableReservationsController::class, 'storeOperatingHours']);

    // Restaurant Menu Categories (Admin)   
    Route::group(['prefix' => '/restaurant-menu-categories'], function () {
        Route::get('/', [RestaurantMenuCategoryController::class, 'index']);
        Route::post('/', [RestaurantMenuCategoryController::class, 'store']);
        Route::put('/{category}', [RestaurantMenuCategoryController::class, 'update']);
        Route::delete('/{category}', [RestaurantMenuCategoryController::class, 'destroy']);
    });

    // Restaurant Tables Management (Admin)
    Route::group(['prefix' => '/restaurants/{restaurant}/tables'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\RestaurantTablesController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\RestaurantTablesController::class, 'store']);
        Route::get('/{table}', [\App\Http\Controllers\Admin\RestaurantTablesController::class, 'show']);
        Route::put('/{table}', [\App\Http\Controllers\Admin\RestaurantTablesController::class, 'update']);
        Route::delete('/{table}', [\App\Http\Controllers\Admin\RestaurantTablesController::class, 'destroy']);
        
        // Image Management
        Route::post('/{table}/images', [\App\Http\Controllers\Admin\RestaurantTablesController::class, 'uploadImage']);
        Route::post('/{table}/images/multiple', [\App\Http\Controllers\Admin\RestaurantTablesController::class, 'uploadMultipleImages']);
        Route::delete('/{table}/images/{mediaId}', [\App\Http\Controllers\Admin\RestaurantTablesController::class, 'deleteImage']);
        Route::delete('/{table}/images', [\App\Http\Controllers\Admin\RestaurantTablesController::class, 'deleteAllImages']);
        Route::post('/{table}/images/reorder', [\App\Http\Controllers\Admin\RestaurantTablesController::class, 'reorderImages']);
    });

    Route::group(['prefix' => '/catering'], function () {
        // OFFERS MANAGEMENT
        Route::get('/offers', [\App\Http\Controllers\Admin\CateringController::class, 'offersIndex']);
        Route::post('/offers', [\App\Http\Controllers\Admin\CateringController::class, 'offersStore']);
        Route::get('/offers/{service}', [\App\Http\Controllers\Admin\CateringController::class, 'offersShow']);
        Route::put('/offers/{service}', [\App\Http\Controllers\Admin\CateringController::class, 'offersUpdate']);
        Route::delete('/offers/{service}', [\App\Http\Controllers\Admin\CateringController::class, 'offersDestroy']);
        Route::post('/offers/{service}/media', [\App\Http\Controllers\Admin\CateringController::class, 'offersUploadMedia']);

        // ORDERS MANAGEMENT
        Route::get('/orders', [\App\Http\Controllers\Admin\CateringController::class, 'ordersIndex']);
        Route::get('/orders/{booking}', [\App\Http\Controllers\Admin\CateringController::class, 'ordersShow']);
        Route::put('/orders/{booking}/status', [\App\Http\Controllers\Admin\CateringController::class, 'ordersUpdateStatus']);
        
        // CATEGORIES MANAGEMENT
        Route::apiResource('/categories', CateringItemCategoriesController::class)->except(['create', 'edit']);
        Route::put('/categories/{category}/toggle-status', [CateringItemCategoriesController::class, 'toggleStatus']);

        // DELIVERY SCHEDULING
        Route::get('/deliveries', [\App\Http\Controllers\Admin\CateringController::class, 'deliveriesIndex']);
        Route::patch('/deliveries/{delivery}', [\App\Http\Controllers\Admin\CateringController::class, 'deliveriesUpdate']);

        // SERVICE QUALITY EVALUATION (REVIEWS)
        Route::get('/reviews', [\App\Http\Controllers\Admin\CateringController::class, 'reviewsIndex']);
        Route::get('/reviews/stats', [\App\Http\Controllers\Admin\CateringController::class, 'reviewsStats']);

        // SPECIAL EVENTS MANAGEMENT
        Route::get('/special-events', [\App\Http\Controllers\Admin\CateringController::class, 'specialEventsIndex']);
        Route::post('/special-events', [\App\Http\Controllers\Admin\CateringController::class, 'specialEventsStore']);
        Route::put('/special-events/{event}', [\App\Http\Controllers\Admin\CateringController::class, 'specialEventsUpdate']);
        Route::delete('/special-events/{event}', [\App\Http\Controllers\Admin\CateringController::class, 'specialEventsDestroy']);

        // MINIMUM LIMIT RULES CONTROL
        Route::get('/minimum-rules', [\App\Http\Controllers\Admin\CateringController::class, 'minimumRulesIndex']);
        Route::post('/minimum-rules', [\App\Http\Controllers\Admin\CateringController::class, 'minimumRulesStore']);
        Route::put('/minimum-rules/{rule}', [\App\Http\Controllers\Admin\CateringController::class, 'minimumRulesUpdate']);
        Route::delete('/minimum-rules/{rule}', [\App\Http\Controllers\Admin\CateringController::class, 'minimumRulesDestroy']);
        Route::post('/minimum-rules/{rule}/apply', [\App\Http\Controllers\Admin\CateringController::class, 'minimumRulesApply']);
    });

    // Booking Hub (Admin)
    Route::group(['prefix' => '/bookings'], function () {
        Route::get('/all', [\App\Http\Controllers\Admin\BookingHubController::class, 'index']);
        Route::patch('/{type}/{id}/status', [\App\Http\Controllers\Admin\BookingHubController::class, 'updateStatus'])
            ->whereIn('type', ['service','table','event'])
            ->whereNumber('id');
    });

    // Admin: Users custom endpoints
    Route::patch('/users/{user}/status', [UsersController::class, 'updateStatus']);
    Route::get('/users/{user}/login-activities', [UsersController::class, 'loginActivities']);
    
    // إدارة المستخدمين - المسارات الجديدة
    Route::get('/users-list', [UsersController::class, 'getUsersList']);
    Route::post('/users/{user}/categories', [UsersController::class, 'assignUserCategories']);
    Route::get('/users/{user}/categories', [UsersController::class, 'getUserCategories']);
    Route::post('/users/{user}/warnings', [UsersController::class, 'addUserWarning']);
    Route::get('/users/{user}/warnings', [UsersController::class, 'getUserWarnings']);
    Route::post('/users/{user}/notifications', [UsersController::class, 'sendUserNotification']);
    Route::get('/users/{user}/service-usage', [UsersController::class, 'getUserServiceUsage']);
    // Admin: Notifications
    Route::post('/notifications/send', [\App\Http\Controllers\Admin\NotificationsController::class, 'send'])->middleware('throttle:10,1');

    // Admin: Countries Management
    Route::group(['prefix' => '/countries'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\CountriesController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\CountriesController::class, 'store']);
        Route::get('/dropdown', [\App\Http\Controllers\Admin\CountriesController::class, 'dropdown']);
        Route::get('/{country}', [\App\Http\Controllers\Admin\CountriesController::class, 'show']);
        Route::put('/{country}', [\App\Http\Controllers\Admin\CountriesController::class, 'update']);
        Route::delete('/{country}', [\App\Http\Controllers\Admin\CountriesController::class, 'destroy']);
        Route::get('/{country}/statistics', [\App\Http\Controllers\Admin\CountriesController::class, 'statistics']);
    });

    Route::group(['prefix' => '/hobbies'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\HobbyController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\HobbyController::class, 'store']);
        Route::put('/{hobby}', [\App\Http\Controllers\Admin\HobbyController::class, 'update']);
        Route::delete('/{hobby}', [\App\Http\Controllers\Admin\HobbyController::class, 'destroy']);
    });

    Route::group(['prefix' => '/customers'], function () {
        Route::get('/', [CustomersController::class, 'index']);
        Route::get('/{customer}', [CustomersController::class, 'show']);
    });

    // Commission routes
    Route::group(['prefix' => '/commission'], function () {
        Route::get('/settings', [CommissionController::class, 'settings']);
        Route::put('/settings', [CommissionController::class, 'updateSettings']);
        Route::get('/statistics', [CommissionController::class, 'statistics']);
        Route::get('/bookings/{bookingId}/calculate', [CommissionController::class, 'calculateForBooking']);
        Route::post('/bookings/{bookingId}/process', [CommissionController::class, 'processForBooking']);
        Route::get('/report', [CommissionController::class, 'report']);
        
        // Task 4: Advanced Commission Management APIs
        Route::get('/rules', [CommissionController::class, 'commissionRules']);
        Route::post('/rules', [CommissionController::class, 'createCommissionRule']);
        Route::put('/rules/{id}', [CommissionController::class, 'updateCommissionRule']);
        Route::delete('/rules/{id}', [CommissionController::class, 'deleteCommissionRule']);
        
        Route::get('/fees', [CommissionController::class, 'feeStructures']);
        Route::post('/fees', [CommissionController::class, 'createFeeStructure']);
        
        Route::get('/referral-operations', [CommissionController::class, 'referralOperations']);
        Route::get('/service-report', [CommissionController::class, 'commissionReportByService']);
    });

    // Referral Configuration Routes (Task 4)
    Route::group(['prefix' => '/referral-configuration'], function () {
        Route::get('/', [CommissionController::class, 'referralConfiguration']);
        Route::post('/', [CommissionController::class, 'referralConfiguration']);
    });

    // Approval Routes
    Route::group(['prefix' => '/approvals', 'middleware' => ['user_type:admin']], function () {
        Route::get('/pending-providers', [ApprovalController::class, 'pendingProviders']);
        Route::get('/pending-services', [ApprovalController::class, 'pendingServices']);
        Route::post('/providers/{provider}/approve', [ApprovalController::class, 'approveProvider']);
        Route::post('/providers/{provider}/reject', [ApprovalController::class, 'rejectProvider']);
        Route::post('/services/{service}/approve', [ApprovalController::class, 'approveService']);
        Route::post('/services/{service}/reject', [ApprovalController::class, 'rejectService']);
        Route::get('/settings', [ApprovalController::class, 'getApprovalSettings']);
        // Legal Documents Approval
        Route::group(['prefix' => '/legal-documents', 'middleware' => ['user_type:admin']], function () {
            Route::get('/', [\App\Http\Controllers\Admin\LegalDocumentsApprovalController::class, 'index']);
            Route::post('/{document}/approve', [\App\Http\Controllers\Admin\LegalDocumentsApprovalController::class, 'approve']);
            Route::post('/{document}/reject', [\App\Http\Controllers\Admin\LegalDocumentsApprovalController::class, 'reject']);
        });

        Route::put('/settings', [ApprovalController::class, 'updateApprovalSettings']);
    });

    // Points Routes
    Route::group(['prefix' => '/points'], function () {
        Route::get('/settings', [PointsController::class, 'settings']);
        Route::put('/settings', [PointsController::class, 'updateSettings']);
        Route::get('/statistics', [PointsController::class, 'statistics']);
    });

    // Reviews Routes
    Route::group(['prefix' => '/reviews'], function () {
        Route::get('/', [ReviewsController::class, 'index']);
        Route::get('/statistics', [ReviewsController::class, 'statistics']);
        Route::get('/{review}', [ReviewsController::class, 'show']);
        Route::post('/{review}/approve', [ReviewsController::class, 'approve']);
        Route::post('/{review}/reject', [ReviewsController::class, 'reject']);
        Route::delete('/{review}', [ReviewsController::class, 'destroy']);
    });

    // Referral Routes
    Route::group(['prefix' => '/referrals'], function () {
        Route::get('/statistics', [ReferralController::class, 'statistics']);
        Route::get('/top-referrers', [ReferralController::class, 'topReferrers']);
        Route::get('/referrers/{userId}', [ReferralController::class, 'showReferrer']);
    });

    // SMS Routes
    Route::group(['prefix' => '/sms'], function () {
        Route::get('/settings', [SmsController::class, 'settings']);
        Route::put('/settings', [SmsController::class, 'updateSettings']);
        Route::post('/test', [SmsController::class, 'sendTestSms']);
        Route::get('/balance', [SmsController::class, 'getBalance']);
        Route::post('/bulk', [SmsController::class, 'sendBulkSms']);
        Route::get('/status', [SmsController::class, 'checkMessageStatus']);
        Route::post('/notifications', [SmsController::class, 'sendCustomNotification']);
    });

    // National ID Verification Routes
    Route::group(['prefix' => '/national-id'], function () {
        Route::get('/', [NationalIdVerificationController::class, 'index']);
        Route::post('/verify', [NationalIdVerificationController::class, 'verify']);
        Route::get('/status', [NationalIdVerificationController::class, 'checkStatus']);
        Route::post('/revoke', [NationalIdVerificationController::class, 'revoke']);
        Route::get('/statistics', [NationalIdVerificationController::class, 'statistics']);
        Route::put('/settings', [NationalIdVerificationController::class, 'updateSettings']);
    });

    // Financial Reports Routes
    Route::group(['prefix' => '/financial-reports'], function () {
        Route::get('/dashboard', [FinancialReportController::class, 'dashboard']);
        Route::get('/trends', [FinancialReportController::class, 'trendAnalysis']);
        Route::get('/provider-profitability', [FinancialReportController::class, 'providerProfitability']);
        Route::get('/revenue', [FinancialReportController::class, 'revenueReport']);
        Route::get('/detailed-revenue', [FinancialReportController::class, 'detailedRevenue']); // Task 1
        Route::get('/net-profit', [FinancialReportController::class, 'netProfit']); // Task 2
        Route::get('/monthly-revenue', [FinancialReportController::class, 'monthlyRevenueReport']);
        Route::get('/daily-revenue', [FinancialReportController::class, 'dailyRevenueReport']);
        Route::get('/revenue-by-service-type', [FinancialReportController::class, 'revenueByServiceType']);
        Route::get('/revenue-by-provider', [FinancialReportController::class, 'revenueByProvider']);
        
        // Task 3: Financial Reports Export
        Route::post('/export/initiate', [FinancialReportController::class, 'initiateExport']);
        Route::get('/export/status/{reportId}', [FinancialReportController::class, 'checkExportStatus']);
        Route::get('/export/history', [FinancialReportController::class, 'exportHistory']);
        Route::get('/export/statistics', [FinancialReportController::class, 'exportStatistics']);
        Route::get('/export/download/{reportId}', [FinancialReportController::class, 'downloadReport']);
        Route::get('/commission', [FinancialReportController::class, 'commissionReport']);
        Route::get('/tax', [FinancialReportController::class, 'taxReport']);
        Route::get('/discount', [FinancialReportController::class, 'discountReport']);
        Route::get('/performance', [FinancialReportController::class, 'performanceReport']);
        Route::get('/quick-stats', [FinancialReportController::class, 'quickStats']);
        Route::get('/export', [FinancialReportController::class, 'exportToCsv']);
    });
    Route::get('/settlements/summary', [\App\Http\Controllers\Admin\FinancialReportController::class, 'settlementsSummary']);
    Route::get('/settlements/export', [\App\Http\Controllers\Admin\FinancialReportController::class, 'exportSettlementsCsv']);

    // Invoice Routes
    Route::group(['prefix' => '/invoices'], function () {
        Route::get('/', [InvoiceController::class, 'index']);

        Route::get('/{invoice}', [InvoiceController::class, 'show']);
        Route::post('/', [InvoiceController::class, 'store']);
        Route::put('/{invoice}', [InvoiceController::class, 'update']);
        Route::delete('/{invoice}', [InvoiceController::class, 'destroy']);
        Route::put('/{invoice}/status', [InvoiceController::class, 'updateStatus']);
        Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel']);
        Route::get('/export/csv', [InvoiceController::class, 'exportCsv']);
    });

    // Financials & Collections
    Route::group(['prefix' => '/financials'], function () {
        Route::get('/revenue-summary', [\App\Http\Controllers\Admin\FinancialsController::class, 'revenueSummary']);
        Route::get('/commissions', [\App\Http\Controllers\Admin\FinancialsController::class, 'commissionsIndex']);
        Route::post('/commissions/{invoice}/mark-as-paid', [\App\Http\Controllers\Admin\FinancialsController::class, 'commissionsMarkAsPaid']);
        Route::get('/reports/vat', [\App\Http\Controllers\Admin\FinancialsController::class, 'vatReport']);
    });


    // Geographic Expansion
    Route::group(['prefix' => '/geo'], function () {
        Route::patch('/cities/{city}/status', [\App\Http\Controllers\Admin\GeoController::class, 'updateCityStatus']);
        Route::get('/markets/performance', [\App\Http\Controllers\Admin\GeoController::class, 'marketsPerformance']);
        Route::get('/markets/demand-forecast', [\App\Http\Controllers\Admin\GeoController::class, 'demandForecast']);
    });

    // Support Team Monitoring
    Route::get('/support/performance-report', [\App\Http\Controllers\Admin\SupportMonitoringController::class, 'performanceReport']);

    // Legal & Licensing
    Route::group(['prefix' => '/providers'], function () {
        Route::post('/{provider}/documents', [\App\Http\Controllers\Admin\ProviderDocumentsController::class, 'store']);
        Route::get('/{provider}/documents', [\App\Http\Controllers\Admin\ProviderDocumentsController::class, 'index']);
    });

    // Security Center
    Route::group(['prefix' => '/security'], function () {
        Route::get('/activity-log', [\App\Http\Controllers\Admin\SecurityCenterController::class, 'activityLog']);
        Route::get('/2fa-status', [\App\Http\Controllers\Admin\SecurityCenterController::class, 'twoFaStatus']);
    });

    // Inter-Team Tasks
    Route::group(['prefix' => '/tasks'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\TasksController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\TasksController::class, 'store']);
        Route::patch('/{task}/status', [\App\Http\Controllers\Admin\TasksController::class, 'updateStatus']);
        Route::post('/{task}/comments', [\App\Http\Controllers\Admin\TasksController::class, 'addComment']);
    });

    // QA Monitoring
    Route::group(['prefix' => '/qa'], function () {
        Route::get('/kpis', [\App\Http\Controllers\Admin\QaController::class, 'kpis']);
        Route::post('/reviews', [\App\Http\Controllers\Admin\QaController::class, 'storeReview']);
    });

    // Executive Dashboard
    Route::get('/dashboards/executive', [\App\Http\Controllers\Admin\ExecutiveDashboardController::class, 'show']);

    // Coupons CRUD

    // RBAC Center
    Route::get('/permissions', [\App\Http\Controllers\Admin\PermissionsController::class, 'index']);
    Route::post('/roles/{role}/permissions', [\App\Http\Controllers\Admin\PermissionsController::class, 'assignToRole']);
    Route::post('/users/{user}/permissions', [\App\Http\Controllers\Admin\PermissionsController::class, 'assignToUser']);
    Route::post('/users/{user}/roles', [\App\Http\Controllers\Admin\UserRolesController::class, 'assign']);

    Route::apiResource('/coupons', \App\Http\Controllers\Admin\CouponController::class);


	    // Advanced Reports (Admin)
	    Route::group(['prefix' => '/reports'], function () {
	        Route::post('/generate', [\App\Http\Controllers\Admin\AdvancedReportsController::class, 'generate']);
	        Route::post('/schedule', [\App\Http\Controllers\Admin\AdvancedReportsController::class, 'schedule']);
	    });

    // Pages CRUD
    Route::apiResource('/pages', \App\Http\Controllers\Admin\PageController::class);


    // Knowledge Hub: Policies
    Route::group(['prefix' => '/knowledge'], function () {
        Route::apiResource('/policies', PolicyController::class);
        // Training Videos
        Route::apiResource('/videos', TrainingVideoController::class);
        Route::get('/videos/{video}/stream', [TrainingVideoController::class, 'stream']);
        Route::get('/videos/{video}/download', [TrainingVideoController::class, 'download']);
        Route::get('/videos/{video}/thumbnail', [TrainingVideoController::class, 'thumbnail']);
    });


    // Global Booking Calendar
    Route::get('/calendar/bookings', [\App\Http\Controllers\Admin\BookingCalendarController::class, 'index']);

    // Support Tickets (Admin)
    Route::group(['prefix' => '/support/tickets'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\SupportTicketsController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\SupportTicketsController::class, 'store']);
        Route::get('/{ticket}', [\App\Http\Controllers\Admin\SupportTicketsController::class, 'show']);
        Route::post('/{ticket}/replies', [\App\Http\Controllers\Admin\SupportTicketsController::class, 'addReply']);
        Route::post('/{ticket}/assign', [\App\Http\Controllers\Admin\SupportTicketsController::class, 'assign']);
        Route::patch('/{ticket}/status', [\App\Http\Controllers\Admin\SupportTicketsController::class, 'updateStatus']);
    });

    // Suggestions (Admin)
    Route::group(['prefix' => '/suggestions'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\SuggestionsController::class, 'index']);
        Route::get('/{suggestion}', [\App\Http\Controllers\Admin\SuggestionsController::class, 'show']);
        Route::patch('/{suggestion}/status', [\App\Http\Controllers\Admin\SuggestionsController::class, 'updateStatus']);
        Route::post('/{suggestion}/assign', [\App\Http\Controllers\Admin\SuggestionsController::class, 'assign']);
    });

    // Realtime Admin Dashboard
    Route::group(['prefix' => '/realtime'], function () {
        Route::get('/metrics', [\App\Http\Controllers\Admin\RealtimeController::class, 'metrics']);
        Route::post('/heartbeat', [\App\Http\Controllers\Admin\RealtimeController::class, 'heartbeat']);
    });

    // Interactive actions from admin dashboard
    Route::post('/support/quick-response', [\App\Http\Controllers\Admin\DashboardActionsController::class, 'quickResponse']);
    Route::post('/services/{service}/reactivate', [\App\Http\Controllers\Admin\DashboardActionsController::class, 'reactivateService']);
    Route::post('/alerts/{alert}/acknowledge', [\App\Http\Controllers\Admin\DashboardActionsController::class, 'acknowledge']);




    // Points Ledger Reports
    Route::group(['prefix' => '/points-ledger'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\PointsLedgerReportController::class, 'index']);
        Route::get('/export', [\App\Http\Controllers\Admin\PointsLedgerReportController::class, 'exportCsv']);
    });

});


// Secure remaining admin routes
Route::group(['middleware' => ['auth:api', 'user_type:admin']], function () {

    // Settlements (Escrow)
    Route::get('/settlements/pending', [\App\Http\Controllers\Admin\SettlementController::class, 'pending']);
    // Provider payout settings
    Route::put('/settings/provider-payout', [\App\Http\Controllers\Admin\ProviderPayoutSettingsController::class, 'update']);

    Route::post('/settlements/{transaction}/process', [\App\Http\Controllers\Admin\SettlementController::class, 'process']);
    Route::post('/settlements/{transaction}/partial', [\App\Http\Controllers\Admin\SettlementController::class, 'partial']);


    // Dynamic Assets
    Route::put('/settings/assets', [\App\Http\Controllers\Admin\SettingsController::class, 'updateAssets']);
    // Engagement
    Route::put('/settings/engagement', [\App\Http\Controllers\Admin\SettingsController::class, 'updateEngagement']);



    // Banners
    Route::group(['prefix' => '/banners'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\BannerController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\BannerController::class, 'store']);
        Route::put('/{banner}', [\App\Http\Controllers\Admin\BannerController::class, 'update']);
        Route::delete('/{banner}', [\App\Http\Controllers\Admin\BannerController::class, 'destroy']);
    });


    Route::group(['prefix' => '/sms'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\SmsController::class, 'getBalance']);
    });


    // Gift Packages
    Route::group(['prefix' => '/gifts/packages'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\GiftPackageController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\GiftPackageController::class, 'store']);
        Route::put('/{giftPackage}', [\App\Http\Controllers\Admin\GiftPackageController::class, 'update']);
        Route::delete('/{giftPackage}', [\App\Http\Controllers\Admin\GiftPackageController::class, 'destroy']);
    });

});