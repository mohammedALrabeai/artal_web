<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use App\Filament\Resources\RequestResource;
use App\Models\Request;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'requests';

    public function form(Form $form): Form
    {
        return RequestResource::form($form);
    }

    public function table(Table $table): Table
    {
        return RequestResource::table($table)
            ->query(function (Builder $query) {
                $query->withoutGlobalScope(SoftDeletingScope::class);
            })
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // viow Request
                Tables\Actions\Action::make('request')
                    ->label(__('View Request'))
                    // ->icon('')
                    ->url(fn ($record) => RequestResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(true)
                // ->style('secondary')
                // ->target('_blank')
                ,
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
