<?php
namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use App\Models\User;
use App\Models\Zone;
use App\Filament\Resources\UserResource\Pages;


class UserResource extends Resource
{
    protected static ?string $model = User::class;
    public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}

    public static function getNavigationLabel(): string
    {
        return __('Users');
    }
    
    public static function getPluralLabel(): string
    {
        return __('Users');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('User Management');
    }
     // أو أي مجموعة أخرى
    protected static ?int $navigationSort = 1; // ترتيب التبويب
    // protected static ?string $navigationIcon = 'heroicon-o-user-group'; // أيقونة التبويب
    protected static bool $shouldRegisterNavigation = true;

    

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('Name')) // Translation
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->label(__('Email')) // Translation
                ->email()
                ->required(),

            Forms\Components\TextInput::make('phone')
                ->label(__('Phone')) // Translation
                ->tel(),

            Forms\Components\Select::make('role')
                ->label(__('Role')) // Translation
                ->options([
                    'manager' => __('Manager'),
                    'general_manager' => __('General Manager'),
                    'hr' => __('HR'),
                ])
                ->required(),

            Forms\Components\TextInput::make('password')
                ->label(__('Password')) // Translation
                ->password()
                ->required()
                ->visibleOn('create')
                ->hiddenOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->searchable(),

                    Tables\Columns\TextColumn::make('role')
                    ->label(__('Role'))
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'manager' => __('Manager'),
                            'general_manager' => __('General Manager'),
                            'hr' => __('HR'),
                            default => $state,
                        };
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            'manager' => 'primary',
                            'general_manager' => 'success',
                            'hr' => 'danger',
                            default => 'secondary',
                        };
                    }),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('Role'))
                    ->options([
                        'manager' => __('Manager'),
                        'general_manager' => __('General Manager'),
                        'hr' => __('HR'),
                    ]),

                TernaryFilter::make('email_verified_at')
                    ->label(__('Verified Email'))
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
