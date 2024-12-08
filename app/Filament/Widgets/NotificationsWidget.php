<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class NotificationsWidget extends Widget
{
    protected static string $view = 'filament.widgets.notifications-widget';

    /**
     * تمرير البيانات إلى Blade.
     */
    protected function getData(): array
    {
        $notifications = Auth::check() ? Auth::user()->unreadNotifications : collect();
    
        // عرض البيانات لفحصها
        dd($notifications);
    
        return [
            'notifications' => $notifications,
        ];
    }
}
