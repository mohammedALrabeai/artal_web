<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ApprovalFlow;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ApprovalFlowResource\Pages;
use App\Filament\Resources\ApprovalFlowResource\RelationManagers;

class ApprovalFlowResource extends Resource
{
    protected static ?string $model = ApprovalFlow::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
        public static function getNavigationLabel(): string
    {
        return __('Approval Flows');
    }
    
    public static function getPluralLabel(): string
    {
        return __('Approval Flows');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('Request Management');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('request_type')
                ->label('نوع الطلب')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('approval_level')
                ->label('مستوى الموافقة')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('approver_role')
                ->label('دور المراجع')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('conditions')
                ->label('شروط الموافقة')
                ->maxLength(500),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request_type')->label('نوع الطلب'),
                Tables\Columns\TextColumn::make('approval_level')->label('مستوى الموافقة'),
                Tables\Columns\TextColumn::make('approver_role')->label('دور المراجع'),
                Tables\Columns\TextColumn::make('conditions')->label('شروط الموافقة'),
           
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListApprovalFlows::route('/'),
            'create' => Pages\CreateApprovalFlow::route('/create'),
            'edit' => Pages\EditApprovalFlow::route('/{record}/edit'),
        ];
    }
}
