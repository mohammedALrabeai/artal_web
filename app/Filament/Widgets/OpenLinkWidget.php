<?php

namespace App\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class OpenLinkWidget extends Widget
{
    use HasWidgetShield;

    protected static ?int $sort = -10; // تحديد ترتيب الويدجت

    protected static string $view = 'filament.widgets.open-link-widget';

    // ✅ منع العرض إذا لم يكن لدى المستخدم الصلاحية
    // public static function canView(): bool
    // {
    //     return Auth::user()?->can('view_OpenLinkWidget');
    // }
}
