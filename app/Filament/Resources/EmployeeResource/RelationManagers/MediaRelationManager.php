<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use App\Forms\Components\ImageField;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\FileUpload;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

   

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('media') // Ensure the key name matches the backend
                ->label('Media File')
                ->required()
                ->disk('s3')
                ->directory('employees')
                ->visibility('public')
                ->preserveFilenames()
                ->multiple()
                ->columnSpanFull(),
                // ImageField::make('media')
                // ->label('Media File')
                // ->required()
                // ->columnSpanFull(),

                    Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),
             
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name') // Display the file name
                ->label('Name')
                ->sortable()
                ->searchable(), 
                Tables\Columns\TextColumn::make('file_name') // Display the file name
                ->label('file_name')
                ->sortable()
                ->searchable(),
                 Tables\Columns\ImageColumn::make('original_url') // Display the file name
                ->label('original_url')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('size') // File size
                ->label('Size')
                ->formatStateUsing(fn ($state) => round($state / 1024, 2) . ' KB'),
            Tables\Columns\TextColumn::make('mime_type') // File type
                ->label('Type'),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Uploaded At')
                ->dateTime(),
               
            ])
            ->filters([
                // Add filters if necessary
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                ->using(function (array $data ) {
                    // dump($data['media']);
                    // Get the owning model (e.g., Employee)
                    $employee = $this->getOwnerRecord();

                    if (!isset($data['media']) || empty($data['media'])) {
                        throw new \Exception('No media file provided.');
                    }

                    // Ensure file exists in S3 before adding to Media Library
                    $filePath = $data['media'];
                    if (!Storage::disk('s3')->exists($filePath)) {
                        throw new \Exception('The file does not exist on S3.');
                    }

                    // Add media to Spatie Media Library
                    return $employee
                        ->addMediaFromDisk($filePath, 's3') // Use 'addMediaFromDisk'
                        ->usingName($data['name']) // Assign custom name
                        ->toMediaCollection(); // Save to default media collection
                }),
        ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DetachAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DetachBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
