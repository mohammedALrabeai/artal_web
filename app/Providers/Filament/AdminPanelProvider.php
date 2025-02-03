<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use App\Filament\Pages\Auth\EditProfile;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Widgets\NotificationsWidget;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            // ->profile(EditProfile::class)
            ->profile()
            ->brandName('ارتال') // تخصيص اسم اللوحة
            ->brandLogo(asset('images/icon.png')) // إضافة شعار مخصص
            ->favicon(asset('images/favicon.png')) // أيقونة المتصفح

            ->colors([
                'primary' => Color::Amber,
                'secondary' => Color::Blue,
            ]) // تخصيص الألوان
            ->brandLogoHeight('120px')

            ->darkMode() // تفعيل الوضع الليلي
            ->sidebarCollapsibleOnDesktop() // قابلية طي القائمة الجانبية

            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
      
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                NotificationsWidget::class, // تسجيل الـ Widget هنا

                Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,

            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling(null)
->renderHook( PanelsRenderHook::USER_MENU_BEFORE, function () {
    return view('components.notification-bell', [
        'notifications' => auth()->user()->unreadNotifications,
        'unreadCount' => auth()->user()->unreadNotifications->count(),
    ])->render();
})
;
    }
}
