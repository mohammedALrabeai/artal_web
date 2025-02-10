<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttachmentResource\Pages;
use App\Forms\Components\EmployeeSelect;
use App\Models\Attachment;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AttachmentResource extends Resource
{
    protected static ?string $model = Attachment::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        // ✅ إخفاء العدد عن المستخدمين غير الإداريين
        if (! auth()->user()?->hasRole('admin')) {
            return null;
        }

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

            // ✅ استخدام `EmployeeSelect` لاختيار الموظف مع البحث المتقدم
            EmployeeSelect::make('model_id')
                ->label(__('Employee'))
                ->required(),

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->sortable()
                    ->searchable(),

                // TextColumn::make('full_name')
                // ->label(__('Employee'))
                // ->getStateUsing(fn ($record) => $record->employee->first_name.' '.
                //     $record->employee->father_name.' '.
                //     $record->employee->grandfather_name.' '.
                //     $record->employee->family_name
                // )
                // ->searchable(query: function ($query, $search) {
                //     return $query->whereHas('employee', function ($subQuery) use ($search) {
                //         $subQuery->where('first_name', 'like', "%{$search}%")
                //             ->orWhere('father_name', 'like', "%{$search}%")
                //             ->orWhere('grandfather_name', 'like', "%{$search}%")
                //             ->orWhere('family_name', 'like', "%{$search}%")
                //             ->orWhere('national_id', 'like', "%{$search}%");
                //     });
                // })
                // ->sortable(),

                // TextColumn::make('employee.national_id')
                // ->label(__('National ID'))
                // ->searchable(),
                TextColumn::make('model_type')
                    ->label(__('Model Type'))
                    ->sortable()
                    ->formatStateUsing(fn ($state) => class_basename($state)),

                TextColumn::make('model_id')->label(__('Record ID'))->sortable(),
                // ✅ عرض اسم الموظف إذا كان نوع المرفق `Employee`
                Tables\Columns\TextColumn::make('model.full_name')
                    ->label(__('Employee'))
                    ->sortable()
                    ->searchable(query: function ($query, $search) {
                        return $query->where(function ($query) use ($search) {
                            // ✅ البحث عندما يكون `model_type = Employee`
                            $query->where(function ($query) use ($search) {
                                $query->where('model_type', 'App\Models\Employee')
                                    ->whereHasMorph('model', ['App\Models\Employee'], function ($q) use ($search) {
                                        $q->where('first_name', 'like', "%{$search}%")
                                            ->orWhere('father_name', 'like', "%{$search}%")
                                            ->orWhere('grandfather_name', 'like', "%{$search}%")
                                            ->orWhere('family_name', 'like', "%{$search}%")
                                            ->orWhere('national_id', 'like', "%{$search}%");
                                    });
                            });

                            // ✅ البحث عندما يكون `model_type = Request` ولكن داخل الموظف المرتبط
                            $query->orWhere(function ($query) use ($search) {
                                $query->where('model_type', 'App\Models\Request')
                                    ->whereHasMorph('model', ['App\Models\Request'], function ($q) use ($search) {
                                        $q->whereHas('employee', function ($employeeQuery) use ($search) {
                                            $employeeQuery->where('first_name', 'like', "%{$search}%")
                                                ->orWhere('father_name', 'like', "%{$search}%")
                                                ->orWhere('grandfather_name', 'like', "%{$search}%")
                                                ->orWhere('family_name', 'like', "%{$search}%")
                                                ->orWhere('national_id', 'like', "%{$search}%");
                                        });
                                    });
                            });
                        });
                    })
                    ->formatStateUsing(fn ($record) => match ($record->model_type) {
                        'App\Models\Employee' => "{$record->model?->first_name} {$record->model?->father_name} {$record->model?->grandfather_name} {$record->model?->family_name} - {$record->model?->national_id} ({$record->model?->id})",
                        'App\Models\Request' => "{$record->model?->employee?->first_name} {$record->model?->employee?->father_name} {$record->model?->employee?->grandfather_name} {$record->model?->employee?->family_name} - {$record->model?->employee?->national_id} ({$record->model?->employee?->id})",
                        default => '-'
                    })
                    ->default('-'),
                // ✅ عرض المرفقات كصور أو روابط تحميل
                SpatieMediaLibraryImageColumn::make('attachments')
                    ->label(__('Preview'))
                    ->collection('attachments')
                    ->disk('s3')
                    ->size(50)
                    ->defaultImageUrl(url('/default-placeholder.png'))
                    ->url(fn ($record) => $record->getFirstMediaUrl('attachments')),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label(__('Expiry Date')),

                Tables\Columns\TextColumn::make('addedBy.name')
                    ->label(__('Added By')),

                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(50), // ✅ تحديد عدد الأحرف بـ 50 حرفًا فقط

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('recordable_type')
                    ->label(__('Model Type'))
                    ->options([
                        'App\Models\Employee' => __('Employee'),
                        'App\Models\Request' => __('Request'),
                        // 'App\Models\CommercialRecord' => __('Commercial Record'),
                        // 'App\Models\Project' => __('Project'),
                    ]),
                SelectFilter::make('employee_id')
                    ->label(__('Employee'))
                    ->options(Employee::all()->pluck('first_name', 'id')),
                // Tables\Filters\Filter::make('employee_search')
                //     ->label(__('Search Employee'))
                //     ->query(fn ($query, $value) => $query->where(function ($query) use ($value) {
                //         $query->where(function ($query) use ($value) {
                //             $query->where('model_type', 'App\Models\Employee')
                //                 ->whereHas('model', fn ($q) => $q->where('first_name', 'like', "%{$value}%")
                //                     ->orWhere('father_name', 'like', "%{$value}%")
                //                     ->orWhere('grandfather_name', 'like', "%{$value}%")
                //                     ->orWhere('family_name', 'like', "%{$value}%")
                //                     ->orWhere('national_id', 'like', "%{$value}%"));
                //         })->orWhere(function ($query) use ($value) {
                //             $query->where('model_type', 'App\Models\Request')
                //                 ->whereHas('model.employee', fn ($q) => $q->where('first_name', 'like', "%{$value}%")
                //                     ->orWhere('father_name', 'like', "%{$value}%")
                //                     ->orWhere('grandfather_name', 'like', "%{$value}%")
                //                     ->orWhere('family_name', 'like', "%{$value}%")
                //                     ->orWhere('national_id', 'like', "%{$value}%"));
                //         });
                //     })),
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
