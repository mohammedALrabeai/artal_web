<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PdfDocumentResource\Pages;
use App\Models\PdfDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PdfDocumentResource extends Resource
{
    protected static ?string $model = PdfDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'مستندات PDF';

    protected static ?string $modelLabel = 'مستند PDF';

    protected static ?string $pluralModelLabel = 'مستندات PDF';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('العنوان')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\FileUpload::make('file_path')
                    ->label('ملف PDF')
                    ->acceptedFileTypes(['application/pdf'])
                    ->directory('pdf-documents')
                    // ->visibility('public')
                    ->disk('public')
                    ->required(),
                
                Forms\Components\Textarea::make('description')
                    ->label('الوصف')
                    ->rows(3),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('file_path')
                    ->label('الملف')
                    ->formatStateUsing(fn (string $state): string => basename($state)),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('textFields_count')
                    ->label('عدد الحقول')
                    ->counts('textFields'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_pdf')
                    ->label('عرض PDF')
                    ->icon('heroicon-o-eye')
                    ->url(fn (PdfDocument $record): string => route('pdf.viewer', $record))
                    ->openUrlInNewTab(),
                
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
            'index' => Pages\ListPdfDocuments::route('/'),
            'create' => Pages\CreatePdfDocument::route('/create'),
            'edit' => Pages\EditPdfDocument::route('/{record}/edit'),
        ];
    }
}

