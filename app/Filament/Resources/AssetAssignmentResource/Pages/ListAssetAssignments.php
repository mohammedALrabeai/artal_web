<?php

namespace App\Filament\Resources\AssetAssignmentResource\Pages;

use App\Enums\AssetStatus;
use App\Filament\Resources\AssetAssignmentResource;
use App\Models\AssetAssignment;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Filament\Actions;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AssetAssignmentsExport;
use Filament\Actions\Action;


use Filament\Forms;

use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Maatwebsite\Excel\Excel as ExcelWriter;

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



protected function getHeaderActions(): array
{
    return [
        Actions\CreateAction::make(),

      
        Actions\Action::make('exportAssignments')
            ->label(__('Export Assignments'))
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->modalHeading(__('Export Assignments'))
            ->form([
                Forms\Components\DatePicker::make('start_date')
                    ->label(__('From Date'))
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->label(__('To Date'))
                    ->required(),
                Forms\Components\Select::make('date_basis')
                    ->label(__('Date Basis'))
                    ->options([
                        'assigned' => __('Assigned Date'),
                        'returned' => __('Returned Date'),
                        'both'     => __('Assigned OR Returned'),
                    ])
                    ->default('both')
                    ->required(),
                Forms\Components\TextInput::make('filename')
                    ->label(__('Filename'))
                    ->default('asset-assignments')
                    ->helperText(__('Without extension')),
            ])
            ->action(function (array $data) {
                $start = $data['start_date'];
                $end   = $data['end_date'];
                $basis = $data['date_basis'] ?? 'both';
                $fname = trim($data['filename'] ?? 'asset-assignments');

                // إن لم توجد صفوف، رجّع إشعار لطيف بدل ملف فارغ (اختياري)
                $export = new AssetAssignmentsExport($start, $end, $basis);
                $rowsCount = $export->collection()->count();
                if ($rowsCount === 0) {
                    \Filament\Notifications\Notification::make()
                        ->title(__('No data to export for selected dates'))
                        ->warning()
                        ->send();
                    return;
                }

                return Excel::download(
                    $export,
                    $fname . '_' . now('Asia/Riyadh')->format('Ymd_His') . '.xlsx'
                );
            }),
    ];
}




}
