<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


use Illuminate\Support\Facades\Schedule;

// Daily expiry for loyalty points at 02:30 (closure-based to avoid command registration requirements)
Schedule::call(function () {
    \App\Models\User::query()->chunkById(100, function ($users) {
        foreach ($users as $user) {
            try { app(\App\Services\PointsLedgerService::class)->expireDue($user); } catch (\Throwable $e) { /* log silently */ }
        }
    });
})->name('points-expiry')->dailyAt('02:30')->withoutOverlapping();


// Auto re-availability for restaurant tables: check every 15 minutes
Schedule::command('restaurants:auto-reavailability')
    ->name('restaurants-auto-reavailability')
    ->everyFifteenMinutes();

// Support SLA breach alerts (every hour instead of every 10 minutes to reduce load)
Schedule::call(function(){
    $thresholdMins = (int) (get_setting('support_sla_minutes') ?? 240);
    $cutoff = now()->subMinutes($thresholdMins);
    $overdue = \App\Models\SupportTicket::query()
        ->whereIn('status', ['open','pending','in_progress'])
        ->where('created_at','<=',$cutoff)
        ->where('sla_alert_sent', false) // Only send alert once per ticket
        ->get();
    foreach ($overdue as $t) {
        try {
            app(\App\Services\NotificationService::class)->created([
                'user_id' => $t->assigned_to ?: 1,
                'action' => 'support_sla_overdue',
                'message' => 'Ticket #'.$t->id.' is overdue the SLA threshold',
            ]);
            // Mark as alert sent to prevent duplicate notifications
            $t->update(['sla_alert_sent' => true]);
        } catch (\Throwable $e) { }
    }
})->name('support-sla-alerts')->hourly()->withoutOverlapping();

// Legal documents expiry warnings (daily)
Schedule::call(function(){
    $days = (int) (get_setting('legal_doc_expiry_warn_days') ?? 30);
    $soon = now()->addDays($days);
    $expiring = \App\Models\CompanyLegalDocument::query()
        ->whereNotNull('expires_at')
        ->whereBetween('expires_at', [now(), $soon])
        ->get();
    foreach ($expiring as $doc) {
        try {
            app(\App\Services\NotificationService::class)->created([
                'user_id' => optional($doc->companyProfile?->user)->id ?? 1,
                'action' => 'legal_doc_expiring',
                'message' => 'Legal document '.$doc->doc_type.' is expiring on '.$doc->expires_at,
            ]);
        } catch (\Throwable $e) { }
    }
})->name('legal-docs-expiry-warn')->dailyAt('09:00')->withoutOverlapping();

// Performance optimization (daily at 3 AM)
Schedule::command('app:optimize-performance')
    ->name('performance-optimization')
    ->dailyAt('03:00')
    ->withoutOverlapping();
