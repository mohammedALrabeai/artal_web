<?php

namespace App\Filament\Resources;

use App\Models\EmployeeNotification;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\EmployeeNotificationResource\Pages;

class EmployeeNotificationResource extends Resource
{
    protected static ?string $model = EmployeeNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_read', false)->count(); // عدد الإشعارات غير المقروءة
    }

    public static function getNavigationLabel(): string
    {
        return __('Notifications');
    }

    public static function getPluralLabel(): string
    {
        return __('Notifications');
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
                    ->relationship('employee', 'name')
                    ->label(__('Employee'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('type')
                    ->label(__('Notification Type'))
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->label(__('Title'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('message')
                    ->label(__('Message'))
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('attachment')
                    ->label(__('Attachment'))
                    ->disk('s3')
                    ->directory('notifications/attachments')
                    ->visibility('public')
                    ->nullable(),
                Forms\Components\Checkbox::make('sent_via_whatsapp')
                    ->label(__('Sent via WhatsApp'))
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label(__('Employee'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable(),
                    Tables\Columns\IconColumn::make('is_read')
                    ->label(__('Read'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(), // لإظهار الحذف الناعم
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options([
                        'general' => __('General'),
                        'notification' => __('Notification'),
                        'warning' => __('Warning'),
                        'violation' => __('Violation'),
                        'summons' => __('Summons'),
                        'other' => __('Other'),
                    ]),
                    Tables\Filters\SelectFilter::make('is_read')
                    ->label(__('Read Status'))
                    ->options([
                        true => __('Read'),
                        false => __('Unread'),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeNotifications::route('/'),
            'create' => Pages\CreateEmployeeNotification::route('/create'),
            'edit' => Pages\EditEmployeeNotification::route('/{record}/edit'),
        ];
    }
}
