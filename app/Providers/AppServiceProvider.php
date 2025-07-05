<?php

namespace App\Providers;

use App\View\Components\NotificationBell;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Facades\Filament;
use Filament\Notifications\Livewire\DatabaseNotifications;
use Filament\Notifications\Notification;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // DatabaseNotifications::trigger('filament.notifications.database-notifications-trigger');

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         Filament::serving(function () {
        Filament::registerRenderHook(
            'panels::head.end',
            fn (): string => '<link rel="stylesheet" href="' . asset('css/additional-styles.css') . '">'
        );
    });
      Filament::serving(function () {
        Filament::registerRenderHook(
            'panels::head.end',
            fn (): string => '<link rel="stylesheet" href="' . asset('css/attendance-css-only.css') . '">'
        );
    });

        Storage::extend('google', function ($app, $config) {
            $client = new GoogleClient;
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->refreshToken($config['refreshToken']);

            $service = new GoogleDrive($client);
            $adapter = new GoogleDriveAdapter($service, $config['folderId'] ?? null);

            $flysystem = new Filesystem($adapter);

            // ✅ حل المشكلة هنا: لف الـ Flysystem في FilesystemAdapter
            return new FilesystemAdapter($flysystem, $adapter, $config);
        });
        // Filament::registerRenderHook('header.end', function () {
        //     // جلب الإشعارات غير المقروءة إذا كان المستخدم مسجلاً دخوله
        //     $notifications = auth()->check() ? auth()->user()->unreadNotifications : collect([]);
        //     $unreadCount = $notifications->count();

        //     // تمرير البيانات إلى العرض
        //     return view('components.notification-bell', [
        //         'notifications' => $notifications,
        //         'unreadCount' => $unreadCount,
        //     ])->render();
        // });
        // Blade::component('notification-bell', NotificationBell::class);
        // Filament::registerTheme(asset('css/filament-overrides.css'));
        FilamentAsset::register([
            Css::make('table-width-override', resource_path('css/filament-overrides.css')),
        ]);

        FilamentAsset::register([
            Js::make('echo', Vite::asset('resources/js/echo.js'))->module(),
        ]);
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['ar', 'en', 'fr']); // also accepts a closure
        });

        // DatabaseNotifications::trigger('filament.notifications.database-notifications-trigger');
        // Filament::serving(function () {
        //     // طباعة معرف المستخدم للتأكد
        //     \Log::info('Current user ID: ' . auth()->id());

        //     // الاستعلام الكامل مع طباعة النتائج
        //     $notifications = DB::table('notifications')
        //         ->where('notifiable_type', 'App\\Models\\User')
        //         ->where('notifiable_id', auth()->id())
        //         ->whereNull('read_at')
        //         ->get();

        //     // طباعة الإشعارات كاملة للتحقق
        //     \Log::info('Full notifications:', $notifications->toArray());

        //     // عدد الإشعارات
        //     $unreadCount = $notifications->count();
        //     \Log::info('Unread count: ' . $unreadCount);

        //     // إعداد العرض
        //     Filament::registerRenderHook(
        //         'global-search.end',
        //         fn () => view('notifications.badge', [
        //             'count' => $unreadCount,
        //             'debug' => true // إضافة متغير للتصحيح
        //         ])
        //     );
        // });

        // DatabaseNotifications::configureUsing(function ($notifications) {
        //     $notifications->count(function () {
        //         return auth()->user()->unreadNotifications()->count();
        //     });
        // });
        // DatabaseNotifications::trigger('filament.notifications.database-notifications-trigger')
        // ->count(function () {
        //     return auth()->user()->unreadNotifications()->count();
        // });
        // DatabaseNotifications::trigger('filament.notifications.database-notifications-trigger');
        // DatabaseNotifications::view('filament.notifications.database-notifications');

        // DatabaseNotifications::pollingInterval('10s');

        // Filament::serving(function () {
        //     Filament::registerRenderHook(
        //         'header.end',
        //         fn (): string => view('components.filament-notification-header')->render(),
        //     );
        // });
        // Filament::serving(function () {
        //     Filament::registerRenderHook('global-search.end', function () {
        //         return view('components.notification-icon');
        //     });
        // });
    }
}
