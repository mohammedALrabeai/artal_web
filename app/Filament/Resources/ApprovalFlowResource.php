<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Role;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\RequestType;
use App\Models\ApprovalFlow;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\KeyValue;
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
        // ✅ إخفاء العدد عن المستخدمين غير الإداريين
        if (!auth()->user()?->hasRole('admin')) {
            return null;
        }
    
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
                // نوع الطلب
            Select::make('request_type')
            ->label(__('Request Type'))
            ->options(RequestType::pluck('name', 'key')) // جلب الأنواع من جدول `request_types`
            ->searchable()
            ->required(),

        // مستوى الموافقة
        Forms\Components\TextInput::make('approval_level')
            ->label(__('Approval Level'))
            ->numeric()
            ->required(),

        // دور المراجع
        Select::make('approver_role')
            ->label(__('Approver Role'))
            ->options(Role::pluck('name', 'name')) // جلب الأدوار من جدول `roles`
            ->searchable()
            ->required(),

        // الشروط
        KeyValue::make('conditions')
            ->label(__('Conditions'))
            ->keyLabel(__('Key'))
            ->valueLabel(__('Value'))
            ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request_type')->label('نوع الطلب'),
                Tables\Columns\TextColumn::make('approval_level')->label('مستوى الموافقة'),
                Tables\Columns\TextColumn::make('approver_role')->label('دور المراجع'),
                Tables\Columns\TextColumn::make('conditions')
                ->label(__('Conditions'))
                ->formatStateUsing(fn($state) => $state ? json_encode($state) : '-'),
           
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
