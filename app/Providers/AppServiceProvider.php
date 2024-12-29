<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Filament\Notifications\Livewire\DatabaseNotifications;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Facades\DB;


use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Vite;

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
        FilamentAsset::register([
            Js::make('echo', Vite::asset('resources/js/echo.js'))->module(),
        ]);
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['ar','en','fr']); // also accepts a closure
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
