<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\RelationManagers\RelationManager;

class LoanRelationManager extends RelationManager
{
    protected static string $relationship = 'loans'; // العلاقة بين الموظفين والقروض

    // protected static ?string $recordTitleAttribute = 'amount'; // حقل عرض العنوان في الجدول
    public static function getRecordTitleAttribute(): ?string
{
    return __('Loans'); // نص مترجم
}

    public  function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bank_id')
                    ->label(__('Bank'))
                    ->relationship('bank', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('amount')
                    ->label(__('Loan Amount'))
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('duration')
                    ->label(__('Loan Duration (Months)'))
                    ->numeric()
                    ->required(),
                Forms\Components\Textarea::make('purpose')
                    ->label(__('Loan Purpose'))
                    ->maxLength(255),
            ]);
    }

    public  function table(Tables\Table $table): Tables\Table
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
}
