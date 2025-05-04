<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
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
        $middleware->redirectTo(function ($request) {
            if (
                $request->expectsJson() ||
                $request->is('api/*') ||
                $request->header('X-Requested-With') === 'XMLHttpRequest'
            ) {
                abort(response()->json([
                    'message' => 'Unauthenticated.',
                    'code' => 401,
                ], 401));
            }

            return '/login'; // أو '/admin/login' لو تستخدم Filament فقط
        });
    })

    ->withCommands([
        // أضف اسم الـCommand هنا
        \App\Console\Commands\ProcessAttendanceCommand::class,
        \App\Console\Commands\CheckConsecutiveAbsences::class,
    ])
    ->withSchedule(function (Schedule $schedule) {
        // جدولة الـCommand للعمل كل ساعة
        $schedule->command('attendance:process')->everyFifteenMinutes();
        $schedule->command('attendance:check-absences')->dailyAt('10:10');
        $schedule->command('backup:run --only-db')->dailyAt('03:00');

        // $schedule->command('attendance:check-absences')->dailyAt('21:06');
        // $schedule->command('attendance:check-absences')
        //     ->name('absences-morning')
        //     ->between('09:06', '09:10')
        //     ->withoutOverlapping();

        // $schedule->command('attendance:check-absences')
        //     ->name('absences-evening')
        //     ->between('21:06', '21:10')
        //     ->withoutOverlapping();

        // $schedule->command('attendance:process')->hourly();
        // $schedule->command('attendance:process')->everyMinute();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
