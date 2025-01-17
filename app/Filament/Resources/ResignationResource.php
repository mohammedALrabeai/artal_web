<?php
namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Employee;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Resignation;
use Filament\Resources\Resource;
use App\Filament\Resources\ResignationResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ResignationResource extends Resource
{
    protected static ?string $model = Resignation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard';

    protected static ?int $navigationSort = 3; // ترتيب في لوحة التحكم
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationLabel(): string
    {
        return __('Resignation');
    }
    
    public static function getPluralLabel(): string
    {
        return __('Resignations');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('Employee Management');
    }
 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                ->label(__('Employee'))
                ->searchable()
                ->placeholder(__('Search for an employee...'))
               
                ->getSearchResultsUsing(function (string $search) {
                    return \App\Models\Employee::query()
                        ->where('national_id', 'like', "%{$search}%") // البحث باستخدام رقم الهوية
                        ->orWhere('first_name', 'like', "%{$search}%") // البحث باستخدام الاسم الأول
                        ->orWhere('family_name', 'like', "%{$search}%") // البحث باستخدام اسم العائلة
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(function ($employee) {
                            return [
                                $employee->id => "{$employee->first_name} {$employee->family_name} ({$employee->id})"
                            ]; // عرض الاسم الأول، العائلة، والمعرف
                        });
                })
                ->getOptionLabelUsing(function ($value) {
                    $employee = \App\Models\Employee::find($value);
                    return $employee
                        ? "{$employee->first_name} {$employee->family_name} ({$employee->id})" // عرض الاسم والمعرف عند الاختيار
                        : null;
                })
                ->preload()
                ->required(),
                Forms\Components\DatePicker::make('resignation_date')
                    ->label(__('Resignation Date'))
                    ->required(),

                Forms\Components\TextInput::make('reason')
                    ->label(__('Reason'))
                    ->maxLength(255),

                Forms\Components\Textarea::make('notes')
                    ->label(__('Notes'))
                    ->maxLength(1000),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label(__('Employee'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('resignation_date')
                    ->label(__('Resignation Date'))
                    ->date()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(50),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employee_id')
                    ->label(__('Employee'))
                    ->relationship('employee', 'first_name'),

                Tables\Filters\Filter::make('resignation_date')
                    ->label(__('Resignation Date'))
                    ->form([
                        Forms\Components\DatePicker::make('start_date')->label(__('Start Date')),
                        Forms\Components\DatePicker::make('end_date')->label(__('End Date')),
                    ])
                    ->query(function ($query, $data) {
                        return $query->when($data['start_date'], fn ($q) => $q->whereDate('resignation_date', '>=', $data['start_date']))
                                     ->when($data['end_date'], fn ($q) => $q->whereDate('resignation_date', '<=', $data['end_date']));
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                ExportBulkAction::make()
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResignations::route('/'),
            'create' => Pages\CreateResignation::route('/create'),
            'edit' => Pages\EditResignation::route('/{record}/edit'),
        ];
    }
}
