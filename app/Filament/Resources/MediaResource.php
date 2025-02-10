<?php

// namespace App\Filament\Resources;

// use App\Filament\Resources\MediaResource\Pages;
// use App\Filament\Resources\MediaResource\RelationManagers;
// use App\Models\Media;
// use Filament\Forms;
// use Filament\Forms\Form;
// use Filament\Resources\Resource;
// use Filament\Tables;
// use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

// class MediaResource extends Resource
// {
//     protected static ?string $model = Media::class;

//     protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

//     public static function form(Form $form): Form
//     {
//         return $form
//             ->schema([
//                 Forms\Components\TextInput::make('model_type')
//                     ->required()
//                     ->maxLength(255),
//                 Forms\Components\TextInput::make('model_id')
//                     ->required()
//                     ->numeric(),
//                 Forms\Components\TextInput::make('uuid')
//                     ->label('UUID')
//                     ->maxLength(36)
//                     ->default(null),
//                 Forms\Components\TextInput::make('collection_name')
//                     ->required()
//                     ->maxLength(255),
//                 Forms\Components\TextInput::make('name')
//                     ->required()
//                     ->maxLength(255),
//                 Forms\Components\TextInput::make('file_name')
//                     ->required()
//                     ->maxLength(255),
//                 Forms\Components\TextInput::make('mime_type')
//                     ->maxLength(255)
//                     ->default(null),
//                 Forms\Components\TextInput::make('disk')
//                     ->required()
//                     ->maxLength(255),
//                 Forms\Components\TextInput::make('conversions_disk')
//                     ->maxLength(255)
//                     ->default(null),
//                 Forms\Components\TextInput::make('size')
//                     ->required()
//                     ->numeric(),
//                 Forms\Components\Textarea::make('manipulations')
//                     ->required()
//                     ->columnSpanFull(),
//                 Forms\Components\Textarea::make('custom_properties')
//                     ->required()
//                     ->columnSpanFull(),
//                 Forms\Components\Textarea::make('generated_conversions')
//                     ->required()
//                     ->columnSpanFull(),
//                 Forms\Components\Textarea::make('responsive_images')
//                     ->required()
//                     ->columnSpanFull(),
//                 Forms\Components\TextInput::make('order_column')
//                     ->numeric()
//                     ->default(null),
//             ]);
//     }

//     public static function table(Table $table): Table
//     {
//         return $table
//             ->columns([
//                 Tables\Columns\TextColumn::make('model_type')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('model_id')
//                     ->numeric()
//                     ->sortable(),
//                 Tables\Columns\TextColumn::make('uuid')
//                     ->label('UUID')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('collection_name')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('name')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('file_name')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('mime_type')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('disk')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('conversions_disk')
//                     ->searchable(),
//                 Tables\Columns\TextColumn::make('size')
//                     ->numeric()
//                     ->sortable(),
//                 Tables\Columns\TextColumn::make('order_column')
//                     ->numeric()
//                     ->sortable(),
//                 Tables\Columns\TextColumn::make('created_at')
//                 ->label(__('Created At'))
//                     ->dateTime()
//                     ->sortable()
//                     ->toggleable(isToggledHiddenByDefault: true),
//                 Tables\Columns\TextColumn::make('updated_at')
//                 ->label(__('Updated At'))
//                     ->dateTime()
//                     ->sortable()
//                     ->toggleable(isToggledHiddenByDefault: true),
//             ])
//             ->filters([
//                 //
//             ])
//             ->actions([
//                 Tables\Actions\ViewAction::make(),
//                 Tables\Actions\EditAction::make(),
//             ])
//             ->bulkActions([
//                 Tables\Actions\BulkActionGroup::make([
//                     Tables\Actions\DeleteBulkAction::make(),
//                 ]),
//             ]);
//     }

//     public static function getRelations(): array
//     {
//         return [
//             //
//         ];
//     }

//     public static function getPages(): array
//     {
//         return [
//             'index' => Pages\ListMedia::route('/'),
//             'create' => Pages\CreateMedia::route('/create'),
//             'view' => Pages\ViewMedia::route('/{record}'),
//             'edit' => Pages\EditMedia::route('/{record}/edit'),
//         ];
//     }
// }
