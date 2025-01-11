<?php

namespace App\Filament\Resources;

use App\Models\Comment;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\CommentResource\Pages;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationLabel(): string
    {
        return __('Comments');
    }

    public static function getPluralLabel(): string
    {
        return __('Comments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Business & License Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('related_table')
                    ->label(__('Related Table'))
                    ->options([
                        'commercial_records' => __('Commercial Records'),
                        'private_licenses' => __('Private Licenses'),
                        'municipal_licenses' => __('Municipal Licenses'),
                        'postal_subscriptions' => __('Postal Subscriptions'),
                        'national_addresses' => __('National Addresses'),
                    ])
                    ->required(),

                Forms\Components\TextInput::make('related_id')
                    ->label(__('Related ID'))
                    ->required()
                    ->numeric(),

                Forms\Components\Textarea::make('comment')
                    ->label(__('Comment'))
                    ->required()
                    ->maxLength(1000),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('related_table')
                    ->label(__('Related Table'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('related_id')
                    ->label(__('Related ID'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('comment')
                    ->label(__('Comment'))
                    ->toggleable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('by_table')
                ->label(__('Filter by Table'))
                ->query(function (Builder $query, array $data) {
                    if (!empty($data['value'])) {
                        $query->where('related_table', $data['value']); // تطبيق الشرط إذا كانت القيمة موجودة
                    }
                })
                ->form([
                    Forms\Components\Select::make('value')
                        ->label(__('Table'))
                        ->options([
                            'commercial_records' => __('Commercial Records'),
                            'private_licenses' => __('Private Licenses'),
                            'municipal_licenses' => __('Municipal Licenses'),
                            'postal_subscriptions' => __('Postal Subscriptions'),
                            'national_addresses' => __('National Addresses'),
                        ])
                        ->placeholder(__('All Tables')), // إضافة اختيار "الكل" كخيار افتراضي
                ]),
            
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                ExportBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
            'create' => Pages\CreateComment::route('/create'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }
}
