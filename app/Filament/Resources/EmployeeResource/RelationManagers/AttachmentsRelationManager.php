<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use App\Filament\Resources\AttachmentResource;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;





class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $recordTitleAttribute = 'type';

    protected static ?string $title = 'المرفقات'; // عنوان الجدول



    public  function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
            ->label(__('Title'))
            ->required(),
        
        Forms\Components\Select::make('type')
            ->label(__('Type'))
            ->options([
                'text' => __('Text'),
                'link' => __('Link'),
                'image' => __('Image'),
                'video' => __('Video'),
                'file' => __('File'),
            ])
            ->required()
            ->reactive(),

        Forms\Components\Textarea::make('content')
            ->label(__('Content (Text)'))
            ->nullable()
            ->visible(fn ($get) => $get('type') === 'text'),

        Forms\Components\TextInput::make('content')
            ->label(__('Content (Link)'))
            ->nullable()
            ->visible(fn ($get) => $get('type') === 'link')
            ->url(),
            // Forms\Components\TextInput::make('image_url')
        Forms\Components\FileUpload::make('image_url')
            ->label(__('Content (Image)'))
            // ->nullable()
            ->disk('s3')
            ->directory('attachments/images')
            ->visibility('public')
            ->visible(fn ($get) => $get('type') === 'image'),

        Forms\Components\FileUpload::make('video_url')
            ->label(__('Content (Video)'))
            ->nullable()
            ->disk('s3')
            ->directory('attachments/videos')
            ->visibility('public')
            ->acceptedFileTypes(['video/*'])
            ->visible(fn ($get) => $get('type') === 'video'),

        Forms\Components\FileUpload::make('file_url')
            ->label(__('Content (File)'))
            ->nullable()
            ->disk('s3')
            ->directory('attachments/files')
            ->visibility('public')
            ->acceptedFileTypes(['application/*'])
            ->visible(fn ($get) => $get('type') === 'file'),

        Forms\Components\DatePicker::make('expiry_date')
            ->label(__('Expiry Date'))
            ->nullable(),

        Forms\Components\Textarea::make('notes')
            ->label(__('Notes'))
            ->nullable(),
        ]);
    }

    public  function table(Tables\Table $table): Tables\Table
    {
        return AttachmentResource::table($table)
        ->modifyQueryUsing(function (Builder $query) {
            $query->orWhereHas('model', function ($subQuery) {
                $subQuery->where('employee_id', $this->getOwnerRecord()->id);
            });
        })
            // ->columns([
            //     Tables\Columns\TextColumn::make('type')
            //         ->label(__('Type')),
            //     Tables\Columns\TextColumn::make('content')
            //         ->label(__('Content')),
            //     Tables\Columns\TextColumn::make('expiry_date')
            //         ->label(__('Expiry Date'))
            //         ->date(),
            //     Tables\Columns\TextColumn::make('notes')
            //         ->label(__('Notes')),
            // ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
