<?php
namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Employee;
use Filament\Widgets\Widget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class ExpiringContracts extends Widget
{
    use HasWidgetShield;
    protected static string $view = 'filament.widgets.expiring-contracts';

    public function getData(): array
    {
        // جلب العقود التي ستنتهي خلال الأيام السبعة القادمة
        return [
            'expiringContracts' => Employee::where('contract_end', '<=', now()->addDays(30))->get(),

            // 'expiringContracts' => Employee::whereBetween('contract_end', [
            //     now()->startOfDay(),
            //     now()->addDays(7)->endOfDay(),
            // ])->get(),
        ];
    }
}
