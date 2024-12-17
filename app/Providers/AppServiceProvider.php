<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Filament\Notifications\Livewire\DatabaseNotifications;



use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;

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
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['ar','en','fr']); // also accepts a closure
        });
       
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
