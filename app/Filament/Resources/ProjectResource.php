<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?int $navigationSort = -10;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        // ✅ إخفاء العدد عن المستخدمين غير الإداريين
        if (! auth()->user()?->hasRole('admin')) {
            return null;
        }

        return static::getModel()::count();
    }

    public static function getNavigationLabel(): string
    {
        return __('Projects');
    }

    public static function getPluralLabel(): string
    {
        return __('Projects');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Zone & Project Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('Name')), // إضافة تسمية مترجمة
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()
                    ->label(__('Description')), // إضافة تسمية مترجمة
                // Forms\Components\TextInput::make('area_id')
                //     ->required()
                //     ->numeric(),
                Forms\Components\Select::make('area_id')
                    ->required()
                    ->options(
                        collect(\App\Models\Area::all())->pluck('name', 'id')
                    )
                    ->placeholder(__('Select Area')) // إضافة تسمية مترجمة
                    ->searchable()
                    ->label(__('Area')), // إضافة تسمية مترجمة
                Forms\Components\DatePicker::make('start_date')
                    ->required()
                    ->label(__('Start Date')), // إضافة تسمية مترجمة
                Forms\Components\DatePicker::make('end_date')
                    ->label(__('End Date')), // إضافة تسمية مترجمة
                Forms\Components\TextInput::make('emp_no')
                    ->label(__('Number of Employees (All shifts included)')) // التسمية موجودة بالفعل
                    ->numeric()
                    ->required(),
                Forms\Components\Toggle::make('status')
                    ->label(__('Status'))
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label(__('Name')), // إضافة تسمية مترجمة
                Tables\Columns\TextColumn::make('area.name')
                    ->numeric()
                    ->sortable()
                    ->label(__('Area')), // إضافة تسمية مترجمة
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->label(__('Start Date')), // إضافة تسمية مترجمة
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->label(__('End Date')), // إضافة تسمية مترجمة
                Tables\Columns\TextColumn::make('emp_no')
                    ->label(__('Number of Employees')) // التسمية موجودة بالفعل
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->boolean()
                    ->label(__('Status')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('Created At')), // إضافة تسمية مترجمة
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('Updated At')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('area_id')
                    ->label(__('Filter by Area'))
                    ->options(
                        \App\Models\Area::pluck('name', 'id')->toArray()
                    )
                    ->searchable()
                    ->multiple()
                    ->placeholder(__('All Areas')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_employees')
                        ->label('تصدير موظفي المشاريع المحددة')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('اختر نوع السجلات')
                                ->options([
                                    'active' => 'الموظفين النشطين فقط',
                                    'all' => 'جميع الموظفين',
                                ])
                                ->default('active')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $projectIds = $records->pluck('id')->toArray();
                            $onlyActive = $data['status'] === 'active';

                            return \Maatwebsite\Excel\Facades\Excel::download(
                                new \App\Exports\SelectedProjectsEmployeeExport($projectIds, $onlyActive),
                                'selected_projects_employees.xlsx'
                            );
                        })
                        ->requiresConfirmation()
                        ->modalHeading('تأكيد التصدير')
                        ->modalDescription('اختر هل تريد تصدير الموظفين النشطين فقط أم جميعهم')
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('export_pdf')
                        ->label('📄 تصدير PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        // ->requiresConfirmation()
                        // ->modalHeading('تأكيد التصدير')
                        // ->modalDescription('هل تريد تصدير تقرير الموظفين للمشاريع المحددة؟')
                        ->openUrlInNewTab()
                        ->action(function ($records) {
                            $ids = $records->pluck('id')->toArray();
                            $query = http_build_query(['ids' => implode(',', $ids)]);

                            return redirect()->away(route('projects.export.pdf').'?'.$query);
                        })
                        ->color('primary'),
                ])
                    ->label('تصدير موظفي المشاريع المحددة'),

                ExportBulkAction::make()
                    ->label(__('Export')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
