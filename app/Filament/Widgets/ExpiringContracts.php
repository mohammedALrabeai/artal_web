<?php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Employee;
use Carbon\Carbon;

class ExpiringContracts extends Widget
{
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
