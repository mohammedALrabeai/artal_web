<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Register singletons وأقراص مخصّصة */
    public function register(): void
    {
        // 🔒 قرص Google Drive يُحمَّل فقط فى CLI
        if ($this->app->runningInConsole()) {
            \Illuminate\Support\Facades\Storage::extend('google', function ($app, $config) {
                $client = new \Google\Client([
                    'client_id'     => $config['clientId'],
                    'client_secret' => $config['clientSecret'],
                ]);
                $client->refreshToken($config['refreshToken']);

                $service = new \Google\Service\Drive($client);
                $adapter = new \Masbug\Flysystem\GoogleDriveAdapter(
                    $service,
                    $config['folderId'] ?? null,
                );

                return new \Illuminate\Filesystem\FilesystemAdapter(
                    new \League\Flysystem\Filesystem($adapter),
                    $adapter,
                    $config,
                );
            });
        }
    }

    /** Bootstrap – يُستدعى عند كل طلب */
    public function boot(): void
    {
        // 1️⃣ سجّل الأصول مرّة واحدة
        FilamentAsset::register([
            Css::make('filament-extra', resource_path('css/filament-extra.css')),
            // Js::make('echo', \Vite::asset('resources/js/echo.js'))->module(),
        ]);

        // 2️⃣ دمج حقن <head> فى عرض واحد
        // Filament::serving(function () {
        //     Filament::registerRenderHook(
        //         'panels::head.end',
        //         fn () => view('filament.partials.head-assets')->render()
        //     );
        // });

        // 3️⃣ إعداد الـ Language Switch (كما هو)
        \BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch::configureUsing(
            fn ($switch) => $switch->locales(['ar', 'en', 'fr'])
        );
    }
}
