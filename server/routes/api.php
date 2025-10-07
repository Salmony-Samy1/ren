<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/admin'], function (){
   require __DIR__.'/api/admin.php';
});

Route::group(['prefix' => '/app'], function (){
   require __DIR__.'/api/frontend.php';
});

Route::group(['prefix' => '/public'], function (){
   require __DIR__.'/api/public.php';
});

// Webhooks (no authentication required)
Route::post('/webhooks/tap', [\App\Http\Controllers\Webhooks\TapWebhookController::class, 'handle'])
    ->middleware(['throttle:60,1', 'csp']); // Rate limiting + CSP for webhooks


