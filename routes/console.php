<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
| Best practices applied:
| - Staggered run times to avoid resource spikes
| - withoutOverlapping() prevents duplicate runs
| - onOneServer() ensures single execution in multi-server setups
| - appendOutputTo() logs output for debugging
| - onFailure() logs errors
|--------------------------------------------------------------------------
*/

$logPath = storage_path('logs/scheduler.log');

// ── Subscription & Trial Expiry Check ────────────────────
// Checks for expiring trials (3-day warning), expired trials,
// and expired subscriptions. Sends email notifications.
Schedule::command('subscriptions:check-expiry')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo($logPath)
    ->onFailure(function () {
        \Log::error('Scheduled command failed: subscriptions:check-expiry');
    });

// ── Clean Activity Log (older than 90 days) ──────────────
Schedule::command('activitylog:clean')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo($logPath);

// ── Prune Stale Sanctum Tokens (older than 30 days) ──────
Schedule::command('sanctum:prune-expired --hours=720')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo($logPath);

// ── Clear Expired Cache & Optimize ───────────────────────
Schedule::command('cache:prune-stale-tags')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

// ── Queue Health: Restart Long-Running Workers ───────────
Schedule::command('queue:restart')
    ->dailyAt('04:30')
    ->onOneServer();
