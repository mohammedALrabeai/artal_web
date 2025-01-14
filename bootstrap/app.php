<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\CorsMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // لتطبيق الـ CORS على جميع مسارات الويب فقط:
        $middleware->web(\App\Http\Middleware\CorsMiddleware::class);
    })
    
    ->withCommands([
        // أضف اسم الـCommand هنا
        \App\Console\Commands\ProcessAttendanceCommand::class,
    ])
    ->withSchedule(function (Schedule $schedule) {
        // جدولة الـCommand للعمل كل ساعة
        $schedule->command('attendance:process')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
