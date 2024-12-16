<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SlideResource\Pages;
use App\Models\Slide;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\{TextInput, Textarea, Toggle, FileUpload};

class SlideResource extends Resource
{
    protected static ?string $model = Slide::class;

    // protected static ?string $navigationIcon = 'heroicon-o-photograph';

    protected static ?string $navigationLabel = 'السلايدات';
    protected static ?string $pluralModelLabel = 'السلايدات';
    protected static ?string $modelLabel = 'سلايد';

    protected static ?string $slug = 'slides';

    public static function getNavigationGroup(): ?string
    {
        return __('الإعدادات');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label(__('title')) // مفتاح الترجمة
                    ->required()
                    ->placeholder(__('title_placeholder')) // مفتاح placeholder
                    ->maxLength(255),
    
                Textarea::make('description')
                    ->label(__('description')) // مفتاح الترجمة
                    ->placeholder(__('description_placeholder')) // مفتاح placeholder
                    ->maxLength(255),
    
                FileUpload::make('image_url')
                    ->label(__('image_url'))
                    ->image()
                    ->directory('slides')
                    ->visibility('public')
                    ->required(),
    
                Toggle::make('is_active')
                    ->label(__('is_active'))
                    ->default(true),
            ]);
    }
    

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            Tables\Columns\TextColumn::make('id')
                ->label(__('id')),

            Tables\Columns\TextColumn::make('title')
                ->label(__('title'))
                ->sortable()
                ->searchable(),

       

            Tables\Columns\BooleanColumn::make('is_active')
                ->label(__('is_active')),

            Tables\Columns\TextColumn::make('created_at')
                ->label(__('created_at'))
                ->dateTime('Y-m-d H:i'),
        ])
            ->filters([
                Filter::make('is_active')
                    ->label(__('.active'))
                    ->query(fn (Builder $query) => $query->where('is_active', true)),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSlides::route('/'),
            'create' => Pages\CreateSlide::route('/create'),
            'edit' => Pages\EditSlide::route('/{record}/edit'),
        ];
    }
}
