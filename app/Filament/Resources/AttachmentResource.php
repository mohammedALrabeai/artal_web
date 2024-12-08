<?php
namespace App\Filament\Resources;

use App\Models\Attachment;
use App\Models\Employee;
use App\Models\User;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Resources\AttachmentResource\Pages;
use Closure;
use Filament\Forms\Get;



class AttachmentResource extends Resource
{
    protected static ?string $model = Attachment::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

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
                ->options(Employee::all()->pluck('first_name', 'id'))
                ->searchable()
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
    
            Forms\Components\Fieldset::make(__('Content'))
                ->schema([
                    Forms\Components\Textarea::make('content_text')
                        ->label(__('Content (Text)'))
                        ->visible(fn (Get $get) => $get('type') === 'text')
                        ->afterStateUpdated(fn ($state, $set) => $set('content', $state)),
    
                    Forms\Components\TextInput::make('content_link')
                        ->label(__('Content (Link)'))
                        ->url()
                        ->visible(fn (Get $get) => $get('type') === 'link')
                        ->afterStateUpdated(fn ($state, $set) => $set('content', $state)),
    
                    Forms\Components\FileUpload::make('content_image')
                        ->label(__('Content (Image)'))
                        ->image()
                        ->visible(fn (Get $get) => $get('type') === 'image')
                        ->afterStateUpdated(fn ($state, $set) => $set('content', $state)),
    
                    Forms\Components\FileUpload::make('content_video')
                        ->label(__('Content (Video)'))
                        ->acceptedFileTypes(['video/*'])
                        ->visible(fn (Get $get) => $get('type') === 'video')
                        ->afterStateUpdated(fn ($state, $set) => $set('content', $state)),
    
                    Forms\Components\FileUpload::make('content_file')
                        ->label(__('Content (File)'))
                        ->visible(fn (Get $get) => $get('type') === 'file')
                        ->afterStateUpdated(fn ($state, $set) => $set('content', $state)),
                ])
                ->columns(1),
    
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
                    ->label(__('Content'))
                    ->getStateUsing(function ($record) {
                        switch ($record->type) {
                            case 'text':
                            case 'link':
                                return $record->content;
                            case 'image':
                                return '<img src="' . asset($record->content) . '" width="50" />';
                            case 'video':
                            case 'file':
                                return '<a href="' . asset($record->content) . '" target="_blank">' . __('Download') . '</a>';
                            default:
                                return '';
                        }
                    })
                    ->html(), // لعرض الروابط أو الصور بصيغة HTML
    
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
