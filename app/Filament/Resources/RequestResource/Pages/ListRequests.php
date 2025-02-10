<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Filament\Resources\RequestResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListRequests extends ListRecords
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->latest();
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('All Requests'))
                ->icon('heroicon-o-clipboard-document-list')
                ->modifyQueryUsing(fn ($query) => $query),

            'my_requests' => Tab::make(__('My Requests'))
                ->icon('heroicon-o-user')
                ->modifyQueryUsing(fn ($query) => $query->where('submitted_by', auth()->id())),

            'pending_my_approval' => Tab::make(__('Requests Requiring My Approval'))
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'pending') // ✅ الطلبات التي حالتها "قيد الموافقة"
                    ->whereIn('current_approver_role', auth()->user()->roles->pluck('name')->toArray()) // ✅ البحث ضمن جميع أدوار المستخدم
                ),
        ];
    }
}
