<?php


namespace App\Filament\Resources\RequestResource\RelationManagers;
namespace App\Filament\Resources\RequestResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ApprovalsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals';

    protected static ?string $recordTitleAttribute = 'approver.name';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('approver_id')
                ->label(__('Approver'))
                ->relationship('approver', 'name') // ربط العلاقة مباشرة
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('approver_role')
                ->label(__('Approver Role'))
                ->disabled() // لا يمكن تغييره يدويًا
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
                ->helperText(__('Provide any additional notes or comments.'))
                ->nullable(),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('approver.name')
                    ->label(__('Approver'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('approver_role')
                    ->label(__('Approver Role')),

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
                Tables\Filters\SelectFilter::make('approver_role')
                    ->label(__('Approver Role'))
                    ->options([
                        'hr' => __('HR'),
                        'manager' => __('Manager'),
                        'general_manager' => __('General Manager'),
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
                    ->visible(fn (Model $record) => $record->approver_id === auth()->id() && $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return in_array(auth()->user()->role->name, ['hr', 'manager', 'general_manager']);
    }

    public function canEditForRecord(Model $record): bool
    {
        return auth()->user()->id === $record->approver_id && $record->status === 'pending';
    }
}
