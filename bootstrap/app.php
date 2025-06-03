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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'check.subscription' => \App\Http\Middleware\CheckSubscription::class,
            'set.dynamic.mail.config' => \App\Http\Middleware\SetDynamicMailConfig::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

    //railway run composer require santigarcor/laratrust
    //railway run php artisan laratrust:install
    //railway run composer dump-autoload
    //railway run php artisan db:seed