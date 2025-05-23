<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

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

            Forms\Components\Select::make('roles')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload()
                ->searchable(),

            Forms\Components\TextInput::make('password')
                ->label(__('Password')) // Translation
                ->password()
                ->required()
                ->visibleOn(['create', 'edit']),
            //     ->hiddenOn('edit'),

            //        Forms\Components\TextInput::make('password')
            // ->label('كلمة المرور')
            // ->password()
            // ->confirmed()                                           // يضيف الحقل الثاني للتأكيد
            // ->confirmationFieldLabel('تأكيد كلمة المرور')         // يعيد تسمية حقل التأكيد
            // ->confirmationPlaceholder('أعد كتابة كلمة المرور')     // يضع Placeholder بالعربي
            // ->required(fn (string $context): bool => $context === 'create')
            // ->dehydrated(fn ($state): bool => filled($state))
            // ->dehydrateStateUsing(fn ($state) => Hash::make($state))
            // ->visibleOn(['create', 'edit'])
            // ->hiddenOn('index')
            //         ,
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
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('الأدوار')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                // Tables\Columns\TextColumn::make('role.name') // عرض اسم الدور المرتبط
                // ->label(__('Role'))
                // ->sortable()
                // ->color(function ($state) {
                //     return match ($state) {
                //         'Manager' => 'primary',
                //         'General Manager' => 'success',
                //         'HR' => 'danger',
                //         default => 'secondary',
                //     };
                // }),
            ])
            ->filters([
                // SelectFilter::make('role_id')
                // ->label(__('Role'))
                // ->relationship('role', 'name'), // فلترة باستخدام العلاقة

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
