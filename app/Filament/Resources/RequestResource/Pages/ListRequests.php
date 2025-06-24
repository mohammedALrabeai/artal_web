<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Filament\Resources\RequestResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;

class ListRequests extends ListRecords
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

//     public function getDefaultActiveTab(): string
// {
//     return 'needs_approval';
// }


    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->latest();
    }

    // public function getTabs(): array
    // {
    //     return [
    //         'all' => Tab::make(__('All Requests'))
    //             ->icon('heroicon-o-clipboard-document-list')
    //             ->modifyQueryUsing(fn ($query) => $query),

    //         'my_requests' => Tab::make(__('My Requests'))
    //             ->icon('heroicon-o-user')
    //             ->modifyQueryUsing(fn ($query) => $query->where('submitted_by', auth()->id())),

    //         'pending_my_approval' => Tab::make(__('Requests Requiring My Approval'))
    //             ->icon('heroicon-o-check-circle')
    //             ->modifyQueryUsing(fn ($query) => $query->where('status', 'pending') // ✅ الطلبات التي حالتها "قيد الموافقة"
    //                 ->whereIn('current_approver_role', auth()->user()->roles->pluck('name')->toArray()) // ✅ البحث ضمن جميع أدوار المستخدم
    //             ),
    //     ];
    // }






public function getTabs(): array
{
    $today = Carbon::today();

    return [
        // ✅ أول تبويبة: "للموافقة" - الطلبات pending أو استبعاد لتاريخ اليوم أو أقدم
        'needs_approval' => Tab::make(__('Requests Needing Approval'))
    ->icon('heroicon-o-clock')
    ->modifyQueryUsing(function ($query) use ($today) {
        return $query->where('status', 'pending')
            ->where(function ($q) use ($today) {
                $q->where('type', '!=', 'exclusion')
                  ->orWhereHas('exclusion', fn ($q) => $q->whereDate('exclusion_date', '<=', $today));
            });
    }),


        // ✅ ثاني تبويبة: "تحت الإجراء" - استبعادات مستقبلية فقط
        'upcoming_exclusions' => Tab::make(__('Upcoming Exclusions'))
            ->icon('heroicon-o-calendar-days')
            ->modifyQueryUsing(function ($query) use ($today) {
                return $query->where('type', 'exclusion')
                    ->whereHas('exclusion', fn ($q) => $q->whereDate('exclusion_date', '>', $today));
            }),

            'pending_my_approval' => Tab::make(__('Requests Requiring My Approval'))
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'pending') // ✅ الطلبات التي حالتها "قيد الموافقة"
                    ->whereIn('current_approver_role', auth()->user()->roles->pluck('name')->toArray()) // ✅ البحث ضمن جميع أدوار المستخدم
                ),

        // ✅ ثالث تبويبة: "طلباتي"
        'my_requests' => Tab::make(__('My Requests'))
            ->icon('heroicon-o-user')
            ->modifyQueryUsing(fn ($query) => $query->where('submitted_by', auth()->id())),

        // ✅ رابع تبويبة: "كل الطلبات"
        'all' => Tab::make(__('All Requests'))
            ->icon('heroicon-o-clipboard-document-list')
            ->modifyQueryUsing(fn ($query) => $query),
    ];
}


}
