<?php

namespace App\Filament\Resources\RequestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;



use App\Models\RequestApproval;
use App\Models\User;



class RequestApprovalRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals'; // اسم العلاقة في موديل الطلب

    protected static ?string $recordTitleAttribute = 'approver.name'; // عرض اسم المستخدم

    public  function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('approver_id')
                ->label(__('Approver'))
                ->options(User::all()->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('approver_type')
                ->label(__('Approver Type'))
                ->required(),

            Forms\Components\Select::make('status')
                ->label(__('Status'))
                ->options([
                    'pending' => __('Pending'),
                    'approved' => __('Approved'),
                    'rejected' => __('Rejected'),
                ])
                ->required(),

            Forms\Components\Textarea::make('notes')
                ->label(__('Notes'))
                ->nullable(),
        ]);
    }

    public  function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('approver.name')
                    ->label(__('Approver'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('approver_type')
                    ->label(__('Approver Type')),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status')),

                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Notes')),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label(__('Approved At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'pending' => __('Pending'),
                        'approved' => __('Approved'),
                        'rejected' => __('Rejected'),
                    ]),
                    Tables\Filters\Filter::make('only_my_approvals')
                    ->label(__('Only My Approvals'))
                    ->query(fn (Builder $query) => $query->where('approver_id', auth()->id())),
           
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->visible(fn (Model $record) => $record->approver_id === auth()->id() && $record->status === 'pending'),
            Tables\Actions\DeleteAction::make()
                ->visible(fn (Model $record) => $record->approver_id === auth()->id()),
      
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = auth()->user();
        
        // السماح بعرض السجل إذا كان المستخدم لديه الصلاحيات المناسبة
        return in_array($user->role, ['hr', 'manager', 'general_manager']);
    }
    

public  function canEditForRecord(Model $record): bool
{
    $user = auth()->user();
    $approverId = $record->approver_id;

    // السماح بالتعديل فقط إذا كان المستخدم هو المعني بالموافقة
    return $user->id === $approverId && $record->status === 'pending';
}

}


