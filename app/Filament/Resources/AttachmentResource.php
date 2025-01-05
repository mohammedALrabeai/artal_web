<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttachmentResource\Pages;
use App\Models\Attachment;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AttachmentResource extends Resource
{
    protected static ?string $model = Attachment::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationLabel(): string
    {
        return __('Attachments');
    }

    public static function getPluralLabel(): string
    {
        return __('Attachments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Employee Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label(__('Title'))
                ->required(),

            Forms\Components\Select::make('employee_id')
                ->label(__('Employee'))
                ->options(Employee::all()->mapWithKeys(function ($employee) {
                    return [$employee->id => "{$employee->first_name} {$employee->family_name} ({$employee->id})"];
                }))
                ->required()
                ->searchable(),

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

          
                // Forms\Components\Fieldset::make(__('Image'))
                // ->schema([
                //     Forms\Components\FileUpload::make('image_url')
                //     ->label(__('Content (Image)'))
                //     ->image()
                //     ->nullable()
                //     ->disk('s3')
                //     ->directory('attachments/images')
                //     ->visibility('public'),
                // ]
                // )
                // ->visible(fn (Get $get) => $get('type') === 'image'),

                // Forms\Components\Fieldset::make(__('Video'))
                // ->schema([
                //     Forms\Components\FileUpload::make('video_url')
                //     ->label(__('Content (Video)'))
                //     ->disk('s3')
                //     ->nullable()
                //     ->directory('attachments/videos')
                //     ->visibility('public')
                //     ->acceptedFileTypes(['video/*']),
                //     // ->preserveFilenames(),
                // ])
                // ->visible(fn (Get $get) => $get('type') === 'video'),

                       Forms\Components\Textarea::make('content')
                        ->label(__('Content (Text)'))
                        ->default(''),

                // Forms\Components\Fieldset::make(__('File'))
                // ->schema([
                //     Forms\Components\FileUpload::make('file_url')
                //     ->label(__('Content (File)'))
                //     ->disk('s3')
                //     ->nullable()
                //     ->directory('attachments/files')
                //     ->visibility('public')
                //     ->acceptedFileTypes(['application/*']),
                //     // ->preserveFilenames(),
                // ])
                // ->visible(fn (Get $get) => $get('type') === 'file'),
                
                Forms\Components\Fieldset::make(__('Content'))
                ->schema([
                    // Forms\Components\Textarea::make('content')
                    //     ->label(__('Content (Text)'))
                    //     ->default(''),
                        // ->nullable(),
                        // ->visible(fn (Get $get) => $get('type') === 'text'),
            
                    // Forms\Components\TextInput::make('content')
                    //     ->label(__('Content (Link)'))
                    //     ->url()
                    //     ->nullable()
                    //     ->visible(fn (Get $get) => $get('type') === 'link'),
            
                    Forms\Components\FileUpload::make('image_url')
                    ->label(__('Content (Image)'))
                    ->image()
                    ->nullable()
                    ->disk('s3')
                    ->directory('attachments/images')
                    ->visibility('public')
                        ->visible(fn (Get $get) => $get('type') === 'image'),
            
                        Forms\Components\FileUpload::make('video_url')
                        ->label(__('Content (Video)'))
                        ->disk('s3')
                        ->nullable()
                        ->directory('attachments/videos')
                        ->visibility('public')
                        ->acceptedFileTypes(['video/*'])
                        ->visible(fn (Get $get) => $get('type') === 'video'),

                    Forms\Components\FileUpload::make('file_url')
                    ->label(__('Content (File)'))
                    ->disk('s3')
                    ->nullable()
                    ->directory('attachments/files')
                    ->visibility('public')
                    ->acceptedFileTypes(['application/*'])
                     ->visible(fn (Get $get) => $get('type') === 'file'),
            
                    // Forms\Components\FileUpload::make('file_url')
                    //     ->label(__('Content (File)'))
                    //     ->disk('s3')
                    //     ->nullable()
                    //     ->directory('attachments/files')
                    //     ->visibility('public')
                    //     ->acceptedFileTypes([
                    //         'application/pdf',
                    //         'application/msword',
                    //         'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    //         'application/zip',
                    //         'application/x-rar-compressed',
                    //     ])
                    //     ->preserveFilenames(),
                    //     // ->visible(fn (Get $get) => $get('type') === 'file'),
                ])
                ->columns(1),
            
                // Forms\Components\FileUpload::make('image_url')
                // ->label(__('Content (Image)'))
                // ->image()
              
                // ->disk('s3')
                // ->directory('attachments/images')
                // ->visibility('public'),
                // // ->preserveFilenames(),
                // // ->visible(fn (Get $get) => $get('type') === 'image'),
        
            Forms\Components\DatePicker::make('expiry_date')
                ->label(__('Expiry Date'))
                ->nullable(),

            Forms\Components\Textarea::make('notes')
                ->label(__('Notes'))
                ->nullable(),

            Forms\Components\Hidden::make('content')
                ->required(), // الحقل الفعلي الذي سيتم حفظه في قاعدة البيانات
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label(__('Employee'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->sortable(),
                    Tables\Columns\TextColumn::make('content')
                    ->label(__('Content (Text/Link)'))
                    ->getStateUsing(fn ($record) => $record->type === 'text' || $record->type === 'link' ? $record->content : null)
                    ->html()
                    
                    ->toggleable(),
                
                    Tables\Columns\ImageColumn::make('image_url')
                    ->label(__('Image URL'))
                    ->toggleable()
                    ->url(fn ($record) => $record->image_url, true),
                
                Tables\Columns\TextColumn::make('video_url')
                    ->label(__('Video'))
                    ->getStateUsing(fn ($record) => $record->video_url ? "<a href='{$record->video_url}' target='_blank'>".__('View Video')."</a>" : null)
                    ->html()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('file_url')
                    ->label(__('File'))
                    ->getStateUsing(fn ($record) => $record->file_url ? "<a href='{$record->file_url}' target='_blank'>".__('Download File')."</a>" : null)
                    ->html()
                    ->toggleable(),
                

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label(__('Expiry Date')),

                Tables\Columns\TextColumn::make('addedBy.name')
                    ->label(__('Added By')),

                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Notes')),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options([
                        'text' => __('Text'),
                        'link' => __('Link'),
                        'image' => __('Image'),
                        'video' => __('Video'),
                        'file' => __('File'),
                    ]),

                SelectFilter::make('employee_id')
                    ->label(__('Employee'))
                    ->options(Employee::all()->pluck('first_name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttachments::route('/'),
            'create' => Pages\CreateAttachment::route('/create'),
            'edit' => Pages\EditAttachment::route('/{record}/edit'),
        ];
    }
}
