<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommercialRecordResource\Pages;
use App\Models\CommercialRecord;
use App\Models\RecordMedia;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MohamedSabil83\FilamentHijriPicker\Forms\Components\HijriDatePicker;
// use Sabil83\FilamentHijriPicker\Forms\Components\HijriDatePicker;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class CommercialRecordResource extends Resource
{
    protected static ?string $model = CommercialRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

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
        return __('Commercial Records');
    }

    public static function getPluralLabel(): string
    {
        return __('Commercial Records');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Business & License Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('CommercialRecordTabs')
                ->tabs([

                    // 📌 تبويب المعلومات الأساسية
                    Forms\Components\Tabs\Tab::make(__('Basic Details'))
                        ->schema([

                            Forms\Components\TextInput::make('record_number')
                                ->label(__('Record Number'))
                                ->required()
                                // ->unique()
                                ->maxLength(20),

                            Forms\Components\TextInput::make('entity_name')
                                ->label(__('Entity Name'))
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Select::make('city')
                                ->label(__('City'))
                                ->options(
                                    collect(\App\Enums\City::cases())
                                        ->mapWithKeys(fn ($city) => [$city->value => $city->label()])
                                        ->toArray()
                                )
                                ->required()
                                ->searchable(),

                            Forms\Components\TextInput::make('entity_type')
                                ->label(__('Entity Type'))
                                ->required()
                                ->maxLength(50),

                            Forms\Components\TextInput::make('capital')
                                ->label(__('Capital'))
                                ->numeric()
                                ->required(),

                            Forms\Components\TextInput::make('insurance_number')
                                ->label(__('Insurance Number'))
                                ->nullable(),

                            Forms\Components\TextInput::make('labour_office_number')
                                ->label(__('Labour Office Number'))
                                ->nullable(),

                            Forms\Components\TextInput::make('unified_number')
                                ->label(__('Unified Number (700)'))
                                ->nullable(),

                            // Forms\Components\DatePicker::make('expiry_date_hijri')
                            //     ->label(__('Expiry Date (Hijri)'))
                            //     ->nullable(),
                            HijriDatePicker::make('expiry_date_hijri')
                                ->label(__('Expiry Date (Hijri)'))
                                ->nullable()
                            // ->required()
                            ,

                            Forms\Components\DatePicker::make('expiry_date_gregorian')
                                ->label(__('Expiry Date (Gregorian)'))
                                ->nullable(),

                            Forms\Components\TextInput::make('tax_authority_number')
                                ->label(__('Tax Authority Number'))
                                ->nullable(),

                            Forms\Components\Select::make('insurance_company_id')
                                ->label(__('Insurance Company M'))
                                ->relationship('insuranceCompany', 'name')
                                ->required()
                                ->preload()
                                ->searchable(),

                            Forms\Components\Select::make('parent_company_id')
                                ->label(__('Parent Company'))
                                ->relationship('parentCompany', 'entity_name')
                                ->nullable()
                                ->preload()
                                ->searchable(),
                        ])
                        ->columns(2),

                    // 📌 تبويب المرفقات
                    Forms\Components\Tabs\Tab::make(__('Attachments'))
                        ->schema([
                            Forms\Components\Repeater::make('recordMedia')
                                ->relationship('recordMedia')
                                ->schema([
                                    Forms\Components\TextInput::make('title')
                                        ->label(__('Title'))
                                        ->required()
                                        ->maxLength(255),

                                    Forms\Components\Textarea::make('notes')
                                        ->label(__('Notes'))
                                        ->nullable()
                                        ->rows(2),

                                    Forms\Components\DatePicker::make('expiry_date')
                                        ->label(__('Expiry Date'))
                                        ->nullable(),

                                    Forms\Components\SpatieMediaLibraryFileUpload::make('file')
                                        ->label(__('Upload File'))
                                        ->collection('record_media')
                                        ->multiple()
                                        ->disk('s3') // ✅ رفع الملفات مباشرة إلى S3
                                        ->preserveFilenames()
                                        ->maxFiles(5)
                                        ->maxSize(10240), // 10MB
                                ])
                                ->collapsible()
                                ->grid(2)
                                ->columns(2),
                        ]),
                ]),
        ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('record_number')
                    ->label(__('Record Number'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('entity_name')
                    ->label(__('Entity Name'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('city')
                    ->label(__('City'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('entity_type')
                    ->label(__('Entity Type')),

                Tables\Columns\TextColumn::make('capital')
                    ->label(__('Capital'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('unified_number')
                    ->label(__('Unified Number (700)')) // الرقم الموحد
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expiry_date_gregorian')
                    ->label(__('Expiry Date (Gregorian)'))
                    ->date() // يعرض التاريخ فقط بدون الوقت
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('days_remaining')
                    ->label(__('Days Remaining'))
                    ->state(function ($record) {
                        $expiryDate = $record->expiry_date_gregorian;
                        if (! $expiryDate) {
                            return __('N/A'); // في حالة عدم توفر تاريخ
                        }

                        $remainingDays = intval(now()->diffInDays($expiryDate, false)); // حساب الفرق كعدد صحيح

                        return $remainingDays;
                    })
                    ->badge()
                    ->sortable()
                    ->color(fn ($record) => match (true) {
                        $record->expiry_date_gregorian && now()->diffInDays($record->expiry_date_gregorian, false) <= 0 => 'danger', // أحمر عند انتهاء الصلاحية
                        $record->expiry_date_gregorian && now()->diffInDays($record->expiry_date_gregorian, false) <= 30 => 'warning', // برتقالي عند أقل من شهر
                        default => 'success', // أخضر افتراضيًا
                    }),

                Tables\Columns\TextColumn::make('parentCompany.entity_name')
                    ->label(__('Parent Company'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiry_date_hijri')
                    ->label(__('Expiry Date (Hijri)')) // نهاية السجل التجاري (هجري)
                    ->date() // يعرض التاريخ فقط بدون الوقت
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('d/m/Y'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('insuranceCompany.name')
                    ->label(__('Insurance Company M'))
                    ->sortable()
                    ->searchable(),

            ])
            ->filters([

                Tables\Filters\Filter::make('city')
                    ->label(__('Filter by City'))
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $query->where('city', $data['value']);
                        }
                    })
                    ->form([
                        Forms\Components\Select::make('value')
                            ->label(__('City'))
                            ->options(
                                collect(\App\Enums\City::cases())
                                    ->mapWithKeys(fn ($city) => [$city->value => $city->label()])
                                    ->toArray()
                            )
                            ->placeholder(__('All Cities')),
                    ]),
                Tables\Filters\Filter::make('expiry_soon')
                    ->label(__('Expiring Soon'))
                    ->query(fn (Builder $query) => $query->where('expiry_date_gregorian', '<=', now()->addMonth())),

                Tables\Filters\Filter::make('has_parent_company')
                    ->label(__('With Parent Company'))
                    ->query(fn (Builder $query) => $query->whereNotNull('parent_company_id')),

                Tables\Filters\Filter::make('capital_above_100k')
                    ->label(__('Capital Above 100k'))
                    ->query(fn (Builder $query) => $query->where('capital', '>', 100000)),
            ])
            ->actions([
                // // ✅ زر إضافة المرفقات
                // Action::make('add_attachment')
                //     ->label(__('📎 Add Attachment'))
                // // ->icon('heroicon-o-paper-clip')
                //     ->form(fn (Form $form) => [
                //         TextInput::make('title')
                //             ->label(__('Title'))
                //             ->required()
                //             ->maxLength(255),

                //         Textarea::make('notes')
                //             ->label(__('Notes'))
                //             ->nullable()
                //             ->rows(2),

                //         DatePicker::make('expiry_date')
                //             ->label(__('Expiry Date'))
                //             ->nullable(),

                //         // SpatieMediaLibraryFileUpload::make('file')
                //         //     ->label(__('Upload File'))
                //         //     ->collection('record_media')
                //         //     ->disk('s3') // ✅ رفع الملفات مباشرة إلى S3
                //         //     ->preserveFilenames()
                //         //     ->maxSize(10240) // 10MB
                //         //     ->model(RecordMedia::class) // ✅ التأكد من أن `RecordMedia` هو الموديل المستخدم
                //         //     ->visibility('private') // ✅ التأكد من أن الملفات لا تضيع بسبب إعدادات التخزين
                //         //     ->openable() // ✅ السماح للمستخدم بفتح الملفات بعد الرفع
                //         //     ->downloadable()
                //         //     ->live(), // 10MB

                //         FileUpload::make('file')
                //             ->label(__('Upload File'))
                //             ->disk('s3') // ✅ رفع الملفات مباشرة إلى S3
                //             ->preserveFilenames()
                //             ->model(RecordMedia::class)
                //             ->maxSize(10240) // ✅ 10MB
                //             ->visibility('private') // ✅ تأكد من أن الملفات لا تضيع بسبب إعدادات التخزين
                //             ->live() // ✅ تمكين `Livewire` للتعامل مع الملفات
                //             ->temporary(),
                //     ])
                //     ->action(function (array $data, Model $record) {
                //         dd(request()->all()); // ✅ تحقق مما إذا كان الملف يظهر هنا

                //         $file = $data['file'] ?? request()->file('file'); // ✅ استخدم `request()->file()` إذا لم يكن في `$data`

                //         dd([
                //             'title' => $data['title'],
                //             'notes' => $data['notes'],
                //             'expiry_date' => $data['expiry_date'],
                //             'file' => $file, // ✅ تحقق مما إذا كان الملف موجودًا
                //         ]);

                //         if (! $file) {
                //             throw new \Exception('File was not received.');
                //         }

                //         // ✅ إنشاء سجل جديد في `RecordMedia`
                //         $attachment = RecordMedia::create([
                //             'title' => $data['title'],
                //             'notes' => $data['notes'],
                //             'expiry_date' => $data['expiry_date'],
                //             'recordable_id' => $record->id,
                //             'recordable_type' => get_class($record),
                //         ]);

                //         // ✅ رفع الملف إلى `RecordMedia`
                //         $attachment->addMedia($file)->toMediaCollection('record_media');

                //     }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                ExportBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommercialRecords::route('/'),
            'create' => Pages\CreateCommercialRecord::route('/create'),
            'edit' => Pages\EditCommercialRecord::route('/{record}/edit'),
        ];
    }
}
