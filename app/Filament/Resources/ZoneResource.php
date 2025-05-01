<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ZoneResource\Pages;
use App\Models\Pattern;
use App\Models\Project;
use App\Models\Zone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ZoneResource extends Resource
{
    protected static ?string $model = Zone::class;

    protected static ?int $navigationSort = -9;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    protected static ?string $navigationIcon = 'heroicon-o-map-pin'; // أيقونة المورد

    public static function getNavigationLabel(): string
    {
        return __('Zones');
    }

    public static function getPluralLabel(): string
    {
        return __('Zones');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Zone & Project Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('Name'))
                ->required()
                ->maxLength(255),

            Forms\Components\DatePicker::make('start_date')
                ->label(__('Start Date'))
                ->required(),

            Forms\Components\Select::make('pattern_id')
                ->label(__('Pattern'))
                ->options(Pattern::all()->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('project_id')
                ->label(__('Project'))
                ->options(Project::all()->pluck('name', 'id'))
                ->searchable()
                ->disabled(fn ($record) => $record !== null)
                ->required(),

            Forms\Components\TextInput::make('lat')
                ->label(__('Latitude'))
                ->required()
                ->default(fn ($record) => $record?->lat)
                ->id('lat'),

            Forms\Components\TextInput::make('longg')
                ->label(__('Longitude'))
                ->required()
                ->default(fn ($record) => $record?->longg)
                ->id('longg'),

            Forms\Components\TextInput::make('area')
                ->label(__('Range (meter)'))
                ->required()
                ->numeric(),

            Forms\Components\TextInput::make('emp_no')
                ->label(__('Number of Employees in one shift'))
                ->numeric()
                ->required(),
            Forms\Components\Toggle::make('status')
                ->label(__('Active'))
                ->default(true),
            Forms\Components\View::make('components.map-picker')
                ->label(__('Pick Location'))
                ->columnSpanFull(),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('pattern.name')
                    ->label(__('Pattern'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label(__('Project'))
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('Start Date'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('area')
                    ->label(__('Range (meter)')),

                Tables\Columns\TextColumn::make('emp_no')
                    ->label(__('Number of Employees'))
                    // ->state(function ($record) {
                    //     return \App\Models\EmployeeProjectRecord::where('zone_id', $record->id)
                    //         ->where('status', true)
                    //         ->count().' موظف';
                    // })
                    ->state(function ($record) {
                        return $record->emp_no.' موظف';
                    })
                    ->extraAttributes(['class' => 'cursor-pointer text-primary underline'])
                    ->action(
                        Tables\Actions\Action::make('show_zone_assignments')
                            ->label('عرض الموظفين')
                            ->modalHeading('الموظفون المسندون للموقع')
                            ->modalSubmitAction(false)
                            ->modalWidth('7xl')
                            ->action(fn () => null)
                            ->mountUsing(function (Tables\Actions\Action $action, $record) {
                                $assignments = \App\Models\EmployeeProjectRecord::with(['employee', 'shift.zone.pattern']) // ← shift ⟶ zone ⟶ pattern
                                    ->where('zone_id', $record->id)
                                    ->where('status', true)
                                    ->get()
                                    ->sortBy(fn ($item) => $item->employee->name ?? '');

                                $action->modalContent(
                                    view('filament.modals.zone-assignments', [
                                        'assignments' => $assignments,
                                        'calculateWorkPattern' => [\App\Filament\Resources\EmployeeProjectRecordResource::class, 'calculateWorkPattern'],
                                    ])
                                );
                            })
                    ),

                Tables\Columns\BooleanColumn::make('status')
                    ->label(__('Active'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('pattern_id')
                    ->label(__('Pattern'))
                    ->options(Pattern::all()->pluck('name', 'id')),
                SelectFilter::make('project_id')
                    ->label(__('Project'))
                    ->options(Project::all()->pluck('name', 'id'))
                    ->searchable()
                    ->multiple(),

                TernaryFilter::make('status')
                    ->label(__('Active'))
                    ->nullable(),
            ])
            ->paginationPageOptions([10, 25, 50, 100])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label(__('View'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Zone $record) => ZoneResource::getUrl('view', ['record' => $record->id])), // ربط زر العرض بصفحة التفاصيل
                Tables\Actions\Action::make('transferProject')
                    ->label('نقل الموقع إلى مشروع آخر')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\Select::make('new_project_id')
                            ->label('المشروع الجديد')
                            ->options(Project::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Zone $record, array $data) {
                        $newProjectId = $data['new_project_id'];

                        $oldProjectName = $record->project->name; // حفظ المشروع القديم قبل التحديث

                        // تحديث المشروع للموقع
                        $record->update([
                            'project_id' => $newProjectId,
                        ]);

                        // تحديث كل سجلات الموظفين المرتبطين بهذا الموقع
                        \App\Models\EmployeeProjectRecord::where('zone_id', $record->id)
                            ->update(['project_id' => $newProjectId]);

                        // إشعار باستخدام NotificationService
                        $notificationService = new \App\Services\NotificationService;
                        $userName = auth()->user()?->name ?? 'مستخدم غير معروف';

                        $message = '';
                        $message .= "تم النقل بواسطة: {$userName}\n\n";
                        $message .= "اسم الموقع: {$record->name}\n";
                        $message .= "المشروع السابق: {$oldProjectName}\n";
                        $message .= "المشروع الجديد: {$record->project->name}\n";

                        $notificationService->sendNotification(
                            ['manager', 'general_manager', 'hr'],
                            '🔁 نقل موقع إلى مشروع آخر',
                            $message,
                            [
                                $notificationService->createAction('عرض الموقع', "/admin/zones/{$record->id}", ''),
                                $notificationService->createAction('قائمة المواقع', '/admin/zones', ''),
                            ]
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('✅ تم نقل الموقع بنجاح')
                            ->body('تم تحديث المشروع وجميع الموظفين المرتبطين بالموقع.')
                            ->success()
                            ->send();
                    })
                    ->color('warning')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'manager', 'hr_manager', 'general_manager'])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZones::route('/'),
            'create' => Pages\CreateZone::route('/create'),
            'edit' => Pages\EditZone::route('/{record}/edit'),
            'view' => Pages\ViewZone::route('/{record}'), // صفحة عرض التفاصيل
        ];
    }
}
