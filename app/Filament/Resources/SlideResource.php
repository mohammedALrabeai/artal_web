<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Slide;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Log;
use App\Filament\Resources\SlideResource\Pages;
use Filament\Forms\Components\{TextInput, Textarea, Toggle, FileUpload};
use Filament\Tables\Actions\Action;

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
   public $view_image2 ;

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
                    ->label(__('Image URL'))
                    ->image() // لتفعيل رفع الصور فقط
                    ->disk('s3') // استخدام القرص S3
                    ->directory('slides') // تحديد المجلد في الحاوية
                    ->visibility('public') // ضبط الرؤية للملفات
                    ->preserveFilenames() // الاحتفاظ باسم الملف الأصلي
                    ->required(),// اجعل الحقل مطلوبًا
             
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

            Tables\Columns\ImageColumn::make('image_url')
                ->label(__('Image URL'))
                ->toggleable()
                ->url(fn ($record) => $record->image_url, true),
                // Tables\Columns\TextColumn::make('image_url2')
                // ->label(__('View Image'))
                // ->getStateUsing(fn ($record) => $record->image_url)
                // ->formatStateUsing(fn ($state, Slide $record) => '<a href="' . $record->image_url . '" target="_blank">' . __('View Image') . '</a>')
                // ->html(),
                // Tables\Columns\TextColumn::make('url')
                //     ->label('URL')
                //     ->url(fn ($record) => $record->image_url)
                //     ->formatStateUsing(fn ($state) => 'Open'),

          

            // Tables\Columns\TextColumn::make('view_image')
            //     ->label(__('View Image'))
            //     ->formatStateUsing(fn ($state, Slide $record) => '<a href="' . $record->image_url . '" target="_blank">' . __('View Image') . '</a>')
            //     ->html(),

            Tables\Columns\ToggleColumn::make('is_active')
                ->label(__('Active'))
                ,

            Tables\Columns\TextColumn::make('created_at')
                ->label(__('created_at'))
                ->dateTime('Y-m-d H:i'),
        ])
            ->filters([
                Filter::make('is_active')
                    ->label(__('.active'))
                    ->query(fn (Builder $query) => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
