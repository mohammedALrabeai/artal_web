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

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label(__('Title'))
                ->required(),

            // ✅ استخدام `EmployeeSelect` لاختيار الموظف مع البحث المتقدم
            // EmployeeSelect::make('model_id')
            //     ->label(__('Employee'))
            //     ->required(),

            // ✅ تعيين نوع الموديل تلقائيًا ليكون `Employee`
            Forms\Components\Hidden::make('model_type')
                ->default('App\Models\Employee'),

            Forms\Components\DatePicker::make('expiry_date')
                ->label(__('Expiry Date'))
                ->nullable(),

            Forms\Components\Textarea::make('notes')
                ->label(__('Notes'))
                ->nullable(),

            // ✅ استخدام Spatie Media Library لرفع جميع أنواع الملفات
            Forms\Components\SpatieMediaLibraryFileUpload::make('attachments')
                ->label(__('Upload File'))
                ->collection('attachments')
                ->disk('s3')
                // ->preserveFilenames()
                ->multiple()
                ->maxFiles(5)
                ->maxSize(10240),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
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
                Tables\Actions\CreateAction::make(),

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
