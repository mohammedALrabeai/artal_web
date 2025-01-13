<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Request;
use App\Models\Employee;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Notifications\Notification;
use App\Filament\Resources\RequestResource\Pages;
use App\Filament\Resources\RequestResource\RelationManagers;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationLabel(): string
    {
        return __('Requests');
    }

    public static function getPluralLabel(): string
    {
        return __('Requests');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Request Management');
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            // اختيار نوع الطلب
            Forms\Components\Select::make('type')
                ->label(__('Type'))
                ->options(\App\Models\RequestType::all()
                    ->mapWithKeys(fn($type) => [$type->key => __($type->name)]) // ترجمة ديناميكية
                    ->toArray())
                ->required()
                ->reactive(),
    
            // اختيار الموظف
            // Forms\Components\Select::make('employee_id')
            //     ->label(__('Employee'))
            //     ->options(Employee::all()->pluck('first_name', 'id'))
            //     ->searchable()
            //     ->nullable()
            //     ->required(),
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
    
            // المقدم
            Forms\Components\Select::make('submitted_by')
                ->label(__('Submitted By'))
                ->options(User::all()->pluck('name', 'id'))
                ->searchable()
                ->required(),
    
            // وصف الطلب
            Forms\Components\Textarea::make('description')
                ->label(__('Description')),
    
            // الحقول الديناميكية بناءً على نوع الطلب
            // إذا كان نوع الطلب "إجازة"
            Forms\Components\Group::make([
                Forms\Components\DatePicker::make('start_date')
                    ->label(__('Start Date'))
                    ->required()
                    ->reactive()
                    ->visible(fn($livewire) => $livewire->data['type'] === 'leave'),
                
                Forms\Components\DatePicker::make('end_date')
                    ->label(__('End Date'))
                    ->required()
                    ->reactive()
                    ->visible(fn($livewire) => $livewire->data['type'] === 'leave'),
    
                Forms\Components\TextInput::make('duration')
                    ->label(__('Duration (Days)'))
                    ->numeric()
                    ->disabled() // يتم حساب المدة بناءً على تاريخ البداية والنهاية
                    ->default(fn ($livewire) => $livewire->data['start_date'] && $livewire->data['end_date']
        ? \Carbon\Carbon::parse($livewire->data['start_date'])->diffInDays(\Carbon\Carbon::parse($livewire->data['end_date'])) + 1
        : null)
                    ->visible(fn($livewire) => $livewire->data['type'] === 'leave'),
    
                Forms\Components\Select::make('leave_type')
                    ->label(__('Leave Type'))
                    ->options([
                        'annual' => __('Annual Leave'),
                        'sick' => __('Sick Leave'),
                        'unpaid' => __('Unpaid Leave'),
                    ])
                    ->required()
                    ->visible(fn($livewire) => $livewire->data['type'] === 'leave'),
    
                Forms\Components\Textarea::make('reason')
                    ->label(__('Reason'))
                    ->nullable()
                    ->visible(fn($livewire) => $livewire->data['type'] === 'leave'),
            ])->columns(1)

            ->visible(fn($livewire) => $livewire->data['type'] === 'leave'),
    
            // حقول طلبات أخرى مثل القروض
            Forms\Components\TextInput::make('amount')
                ->label(__('Amount'))
                ->visible(fn($livewire) => $livewire->data['type'] === 'loan')
                ->numeric(),
        ]);
    }
    

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->label(__('Type'))
                ->formatStateUsing(function ($state) {
                    // بافتراض أن حقل type يحتفظ بقيمة مثل 'leave' أو 'loan' أو 'transfer' ...إلخ
                    // نعيد ترجمة القيمة بناءً على مفتاح ترجمة مناسب
                    return __($state.'');
                }),
                Tables\Columns\TextColumn::make('submittedBy.name')->label(__('Submitted By')),
                Tables\Columns\TextColumn::make('employee.first_name')->label(__('Employee')),
                Tables\Columns\TextColumn::make('status')->label(__('Status'))
                ->formatStateUsing(function ($state) {
                    return __($state.'');
                })
                // ->colors([
                //     __('pending') => 'warning',  // أصفر أو برتقالي
                //     __('approved') => 'success', // أخضر
                //     __('rejected') => 'danger',  // أحمر
                // ])
                ->color(function ($state) {
                    return match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => null,
                    };
                })
                ->sortable(),
                Tables\Columns\TextColumn::make('current_approver_role')
                ->label(__('Current Approver Role'))
                ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state)))
                ->sortable(),

                Tables\Columns\TextColumn::make('duration')->label(__('Duration (Days)'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount')->label(__('Amount'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('additional_data')
                    ->label(__('Additional Data'))
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),

                    Tables\Columns\TextColumn::make('approvalFlows')
                        ->label(__('Remaining Levels'))
                        ->formatStateUsing(fn($record) => $record->approvalFlows
                            ->where('approval_level', '>', $record->approvals->max('approval_level'))
                            ->map(fn($flow) => __(':role (Level :level)', ['role' => $flow->approver_role, 'level' => $flow->approval_level]))
                            ->join(', '))
                        ->sortable(),

            ])
            ->filters([
                // Tables\Filters\SelectFilter::make('type')
                //     ->label(__('Type'))
                //     ->options([
                //         'leave' => __('Leave Request'),
                //         'transfer' => __('Transfer Request'),
                //         'compensation' => __('Compensation Request'),
                //     ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Type'))
                    
                    ->options(\App\Models\RequestType::all()->pluck('name', 'key')->map(fn($name) => __($name)) // ترجمة الأسماء (في حال كانت لديك مفاتيح ترجمة)
                    ->toArray()),

                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'pending' => __('Pending'),
                        'approved' => __('Approved'),
                        'rejected' => __('Rejected'),
                    ]),
                    Tables\Filters\SelectFilter::make('current_approver_role')
                    ->label(__('Current Approver Role'))
                    ->options([
                        'hr' => __('HR'),
                        'manager' => __('Manager'),
                        'general_manager' => __('General Manager'),
                    ])
            ])
            ->actions([
                // Tables\Actions\Action::make('do')
                // ->label(__('اعتماد الإجازة'))
                // ->icon('heroicon-o-check')
                // ->color('success')
                // ->action(function ($record) {
                //     // تأكد من أن الطلب هو من نوع إجازة وأن هناك سجل إجازة مرتبط به
                //     if ($record->type === 'leave' && $record->leave) {
                //         $record->leave->update([
                //             'approved' => true, // تحديث حالة الإجازة إلى "معتمدة"
                //         ]);
        
                //         // يمكنك إرسال تنبيه (Notification) للمستخدم
                //         Notification::make()
                //             ->title('تم اعتماد الإجازة بنجاح')
                //             ->success()
                //             ->send();
                //     } else {
                //         // إذا لم يكن هذا الطلب من نوع إجازة أو لا توجد إجازة مرتبطة
                //         Notification::make()
                //             ->title('لا توجد إجازة مرتبطة بهذا الطلب')
                //             ->danger()
                //             ->send();
                //     }
                // }),
                Tables\Actions\Action::make('approve')
                    ->label(__('Approve'))
                    ->action(fn ($record, array $data) => $record->approveRequest(auth()->user(), $data['comments']))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('comments')
                            ->label(__('Comments'))
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->status !== 'pending'),
                Tables\Actions\Action::make('reject')
                    ->label(__('Reject'))
                    ->action(fn ($record, array $data) => $record->rejectRequest(auth()->user(), $data['comments']))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('comments')
                            ->label(__('Reason for Rejection'))
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->status !== 'pending'),
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
            'edit' => Pages\EditRequest::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ApprovalsRelationManager::class,
        ];
    }
}
