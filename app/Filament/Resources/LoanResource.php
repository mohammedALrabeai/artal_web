<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Loan;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\LoanResource\Pages;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function getNavigationLabel(): string
    {
        return __('Loans');
    }

    public static function getPluralLabel(): string
    {
        return __('Loans');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Finance Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('employee_id')
                ->label(__('Employee'))
                ->relationship('employee', 'first_name')
                ->required(),
            Forms\Components\Select::make('bank_id')
                ->label(__('Bank'))
                ->relationship('bank', 'name')
                ->required(),
            Forms\Components\TextInput::make('amount')
                ->label(__('Loan Amount'))
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('duration_months')
                ->label(__('Loan Duration (Months)'))
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('purpose')
                ->label(__('Purpose')),
            Forms\Components\DatePicker::make('start_date')
                ->label(__('Start Date')),
            Forms\Components\DatePicker::make('end_date')
                ->label(__('End Date')),
            Forms\Components\Textarea::make('notes')
                ->label(__('Notes')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label(__('Employee Name'))
                    ->searchable() // يجعل العمود قابلاً للبحث
                    ->sortable(), // يجعل العمود قابلاً للترتيب
                    
                Tables\Columns\TextColumn::make('bank.name')
                    ->label(__('Bank Name'))
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Loan Amount'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' SAR'), // تنسيق المبلغ
                
                Tables\Columns\TextColumn::make('duration_months')
                    ->label(__('Loan Duration (Months)'))
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('purpose')
                    ->label(__('Purpose'))
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('Start Date'))
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('End Date'))
                    ->date()
                    ->sortable()
                    ->default('---'), // إذا كانت القيمة فارغة
            ])
            ->filters([
                // فلتر القروض النشطة
                Filter::make('active_loans')
                    ->label(__('Active Loans'))
                    ->query(fn ($query) => $query->whereNull('end_date')),
                
                // فلتر القروض المكتملة
                Filter::make('completed_loans')
                    ->label(__('Completed Loans'))
                    ->query(fn ($query) => $query->whereNotNull('end_date')),
    
                // فلتر حسب البنك
                SelectFilter::make('bank_id')
                    ->label(__('Filter by Bank'))
                    ->options(
                        \App\Models\Bank::all()->pluck('name', 'id') // جلب أسماء البنوك
                    ),
    
                // فلتر حسب الموظف
                SelectFilter::make('employee_id')
                    ->label(__('Filter by Employee'))
                    ->options(
                        \App\Models\Employee::all()->pluck('first_name', 'id') // جلب أسماء الموظفين
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('start_date', 'desc') // ترتيب افتراضي حسب تاريخ البداية
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
    

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
}
