<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Minimal root endpoint for uptime checks and to avoid 500s on /
Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});

// Lightweight health endpoint (no session middleware)
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Authentication Routes wrapped in the 'web' middleware group
Route::middleware('web')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Admin session login route
    Route::post('/admin/session-login', [\App\Http\Controllers\Admin\SessionController::class, 'login']);
});


// // Test route to check auth status
// Route::get('/test-auth', function() {
//     return response()->json([
//         'auth_check' => Auth::check(),
//         'user' => Auth::user() ? [
//             'id' => Auth::user()->id,
//             'email' => Auth::user()->email,
//             'type' => Auth::user()->type
//         ] : null
//     ]);
// });

// Admin Panel Routes - Removed (Using React Next.js instead)
// All admin routes have been removed as the admin panel will be built with React Next.js

// Webhook Routes
Route::group(['prefix' => '/webhooks'], function () {
    Route::post('/apple-pay', [WebhookController::class, 'applePay']);
    Route::post('/visa', [WebhookController::class, 'visa']);
    Route::post('/mada', [WebhookController::class, 'mada']);
    Route::post('/samsung-pay', [WebhookController::class, 'samsungPay']);
    Route::post('/benefit', [WebhookController::class, 'benefit']);
    Route::post('/stc-pay', [WebhookController::class, 'stcPay']);
    Route::post('/{gateway}', [WebhookController::class, 'generic']);
});