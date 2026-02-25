<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\SetUserLocale::class,
        ]);

        $middleware->alias([
            'user.active' => \App\Http\Middleware\CheckUserActive::class,
            'subscription.write' => \App\Http\Middleware\CheckSubscriptionWrite::class,
            'user.type' => \App\Http\Middleware\CheckUserType::class,
            'feature.enabled' => \App\Http\Middleware\CheckFeatureEnabled::class,
            'branch.context' => \App\Http\Middleware\SetBranchContext::class,
            'user.locale' => \App\Http\Middleware\SetUserLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
