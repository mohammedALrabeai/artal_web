<?php

namespace App\Filament\Resources\AssetAssignmentResource\Pages;

use App\Enums\AssetStatus;
use App\Filament\Resources\AssetAssignmentResource;
use App\Models\AssetAssignment;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListAssetAssignments extends ListRecords
{
    protected static string $resource = AssetAssignmentResource::class;

    // ربط التبويب بالرابط (?activeTab=...) والعمل مع SPA
    #[Url(as: 'activeTab', history: true, keep: true)]
    public ?string $activeTab = null;

    public function getDefaultActiveTab(): string
    {
        return 'all';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()->label(__('All')),
            'assigned' => Tab::make()->label(__('Assigned')),
            'returned' => Tab::make()->label(__('Returned')),
            'charged_assets' => Tab::make()->label(__('Charged Assets')),
            // أضف تبويبات أخرى لاحقاً إن رغبت (overdue / no_expected ...)
        ];
    }

    // الفلترة الفعلية حسب التبويب النشط (من الخاصية المرتبطة بالرابط)
    protected function getTableQuery(): Builder
    {
        $q = AssetAssignment::query()->select('asset_assignments.*');
        $active = $this->activeTab ?? $this->getDefaultActiveTab();

        return match ($active) {
            'assigned' => $q->whereNull('returned_date'),

            'returned' => $q->whereNotNull('returned_date'),

            // فلترة المخصومة بدون لمس علاقات Eloquent (Subquery/Join آمن)
            'charged_assets' => $q->whereIn('asset_id', function ($sub) {
                $sub->from('assets')
                    ->select('id')
                    ->where('status', AssetStatus::CHARGED->value);
            }),

            default => $q,
        };
    }

    // عند تغيير التبويب في SPA: أعد ضبط الجدول لإعادة التحميل فورًا
   public function updated($name, $value): void
{
    if ($name === 'activeTab') {
        $this->resetTable();
        $this->resetPage();
    }
}

}
