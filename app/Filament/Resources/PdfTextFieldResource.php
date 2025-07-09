<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PdfTextFieldResource\Pages;
use App\Models\PdfTextField;
use App\Models\PdfDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PdfTextFieldResource extends Resource
{
    protected static ?string $model = PdfTextField::class;

    protected static ?string $navigationIcon = 'heroicon-o-cursor-arrow-rays';

    protected static ?string $navigationLabel = 'حقول النص';

    protected static ?string $modelLabel = 'حقل نص';

    protected static ?string $pluralModelLabel = 'حقول النص';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('pdf_document_id')
                    ->label('مستند PDF')
                    ->options(PdfDocument::pluck('title', 'id'))
                    ->required()
                    ->searchable(),
                
                Forms\Components\TextInput::make('field_name')
                    ->label('اسم الحقل')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('field_label')
                    ->label('تسمية الحقل')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('x_position')
                            ->label('الموقع الأفقي (%)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100),
                        
                        Forms\Components\TextInput::make('y_position')
                            ->label('الموقع العمودي (%)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100),
                    ]),
                
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('width')
                            ->label('العرض (%)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100),
                        
                        Forms\Components\TextInput::make('height')
                            ->label('الارتفاع (%)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100),
                    ]),
                
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('page_number')
                            ->label('رقم الصفحة')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(1),
                        
                        Forms\Components\TextInput::make('font_size')
                            ->label('حجم الخط')
                            ->numeric()
                            ->required()
                            ->minValue(8)
                            ->maxValue(72)
                            ->default(12),
                        
                        Forms\Components\Select::make('font_family')
                            ->label('نوع الخط')
                            ->options([
                                'Arial' => 'Arial',
                                'Times New Roman' => 'Times New Roman',
                                'Helvetica' => 'Helvetica',
                                'Courier' => 'Courier',
                            ])
                            ->default('Arial'),
                    ]),
                
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\ColorPicker::make('text_color')
                            ->label('لون النص')
                            ->default('#000000'),
                        
                        Forms\Components\Select::make('field_type')
                            ->label('نوع الحقل')
                            ->options([
                                'text' => 'نص',
                                'textarea' => 'نص متعدد الأسطر',
                                'number' => 'رقم',
                                'date' => 'تاريخ',
                            ])
                            ->default('text'),
                    ]),
                
                Forms\Components\TextInput::make('placeholder')
                    ->label('النص التوضيحي')
                    ->maxLength(255),
                
                Forms\Components\Toggle::make('is_required')
                    ->label('حقل مطلوب')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pdfDocument.title')
                    ->label('المستند')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('field_label')
                    ->label('تسمية الحقل')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('field_name')
                    ->label('اسم الحقل')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('page_number')
                    ->label('الصفحة')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('field_type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'gray',
                        'textarea' => 'info',
                        'number' => 'warning',
                        'date' => 'success',
                    }),
                
                Tables\Columns\IconColumn::make('is_required')
                    ->label('مطلوب')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pdf_document_id')
                    ->label('المستند')
                    ->options(PdfDocument::pluck('title', 'id')),
                
                Tables\Filters\SelectFilter::make('field_type')
                    ->label('نوع الحقل')
                    ->options([
                        'text' => 'نص',
                        'textarea' => 'نص متعدد الأسطر',
                        'number' => 'رقم',
                        'date' => 'تاريخ',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_required')
                    ->label('حقل مطلوب'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListPdfTextFields::route('/'),
            'create' => Pages\CreatePdfTextField::route('/create'),
            'edit' => Pages\EditPdfTextField::route('/{record}/edit'),
        ];
    }
}

