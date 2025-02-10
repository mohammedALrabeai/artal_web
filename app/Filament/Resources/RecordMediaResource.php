<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecordMediaResource\Pages;
use App\Models\RecordMedia;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class RecordMediaResource extends Resource
{
    protected static ?string $model = RecordMedia::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        // ✅ إخفاء العدد عن المستخدمين غير الإداريين
        if (!auth()->user()?->hasRole('admin')) {
            return null;
        }
    
        return static::getModel()::count();
    }
    

    public static function getNavigationLabel(): string
    {
        return __('Administration Attachments');
    }

    public static function getPluralLabel(): string
    {
        return __('Administration Attachments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Business & License Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                // ✅ عرض نوع الملف بأيقونة مختلفة
                IconColumn::make('file_type')
                    ->label(__('Type'))
                    ->icon(fn ($record) => match ($record->getFirstMedia()?->mime_type ?? 'unknown') {
                        'application/pdf' => 'heroicon-o-document-text',
                        'image/png', 'image/jpeg', 'image/gif' => 'heroicon-o-photograph',
                        'video/mp4', 'video/mpeg' => 'heroicon-o-video-camera',
                        'application/zip' => 'heroicon-o-archive',
                        'text/plain' => 'heroicon-o-document',
                        'unknown' => 'heroicon-o-x-circle', // ❌ أيقونة عند عدم وجود ملف
                        default => 'heroicon-o-folder', // 📁 أيقونة افتراضية
                    })
                    ->color(fn ($record) => match ($record->getFirstMedia()?->mime_type ?? 'unknown') {
                        'application/pdf' => 'gray',
                        'image/png', 'image/jpeg', 'image/gif' => 'blue',
                        'video/mp4', 'video/mpeg' => 'red',
                        'application/zip' => 'yellow',
                        'text/plain' => 'green',
                        'unknown' => 'red', // ❌ لون عند عدم وجود ملف
                        default => 'gray',
                    }),

                TextColumn::make('title')->label(__('Title'))->sortable()->searchable(),

                TextColumn::make('notes')->label(__('Notes'))->limit(50),

                TextColumn::make('expiry_date')->label(__('Expiry Date'))->date()->sortable(),

                TextColumn::make('recordable_type')
                    ->label(__('Model Type'))
                    ->sortable()
                    ->formatStateUsing(fn ($state) => class_basename($state)),

                TextColumn::make('recordable_id')->label(__('Record ID'))->sortable(),

                // ✅ عرض صورة مصغرة للصور فقط، وإلا يتم عرض أيقونة نوع الملف
                SpatieMediaLibraryImageColumn::make('record_media')
                    ->label(__('Preview'))
                    ->collection('record_media')
                    ->conversion('thumb')
                    ->size(50)
                    ->disk('s3')
                    ->defaultImageUrl(url('/default-placeholder.png'))
                    ->url(fn ($record) => $record->getMedia('record_media')->first()?->getTemporaryUrl(now()->addMinutes(30))),

                // ✅ عرض رابط "عرض الملف" مع فتحه في نافذة جديدة أو داخل Lightbox
                TextColumn::make('record_media2')
                    ->label(__('File'))
                    ->formatStateUsing(fn ($record) => $record->getMedia('record_media')->first()
                        ? match ($record->getMedia('record_media')->first()->mime_type) {
                            'application/pdf' => '<a href="'.$record->getMedia('record_media')->first()->getTemporaryUrl(now()->addMinutes(30)).'" target="_blank" class="font-bold text-primary">📂 View PDF</a>',
                            'image/png', 'image/jpeg', 'image/gif' => '<a href="'.$record->getMedia('record_media')->first()->getTemporaryUrl(now()->addMinutes(30)).'" data-lightbox="gallery" class="font-bold text-primary">🖼️ View Image</a>',
                            default => '<a href="'.$record->getMedia('record_media')->first()->getTemporaryUrl(now()->addMinutes(30)).'" target="_blank" class="font-bold text-primary">📂 Download</a>',
                        }
                        : '<span class="text-gray-500">No File</span>') // ❌ عرض "No File" عند عدم وجود ملف
                    ->html(),
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
            ->actions([
                // ✅ زر لمعاينة الملف داخل `Livewire Modal`
                Action::make('preview')
                    ->label(__('Preview'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading('File Preview')
                    ->modalContent(fn ($record) => $record->getMedia('record_media')->first()
                        ? new HtmlString(
                            match ($record->getMedia('record_media')->first()->mime_type) {
                                'application/pdf' => '
                                <iframe src="'.$record->getMedia('record_media')->first()->getTemporaryUrl(now()->addMinutes(30)).'" width="100%" height="500px"></iframe>
                                <div class="mt-4 text-center">
                                    <a href="'.$record->getMedia('record_media')->first()->getTemporaryUrl(now()->addMinutes(30)).'" target="_blank" class="mr-2 btn btn-primary">🔗 Open in New Tab</a>
                                    <a href="'.$record->getMedia('record_media')->first()->getTemporaryUrl(now()->addMinutes(30)).'" download class="btn btn-success">⬇ Download</a>
                                </div>',

                                'image/png', 'image/jpeg', 'image/gif' => '
                                <img src="'.$record->getMedia('record_media')->first()->getTemporaryUrl(now()->addMinutes(30)).'" style="max-width:100%; border-radius: 5px;" />
                                <div class="mt-4 text-center">
                                    <a href="'.$record->getMedia('record_media')->first()->getTemporaryUrl(now()->addMinutes(30)).'" target="_blank" class="mr-2 btn btn-primary">🔗 Open in New Tab</a>
                                    <a href="'.$record->getMedia('record_media')->first()->getTemporaryUrl(now()->addMinutes(30)).'" download class="btn btn-success">⬇ Download</a>
                                </div>',

                                'video/mp4', 'video/mpeg' => '
                                <video width="100%" height="auto" controls>
                                    <source src="'.$record->getMedia('record_media')->first()->getTemporaryUrl(now()->addMinutes(30)).'" type="video/mp4">
                                </video>
                                <div class="mt-4 text-center">
                                    <a href="'.$record->getMedia('record_media')->first()->getTemporaryUrl(now()->addMinutes(30)).'" target="_blank" class="mr-2 btn btn-primary">🔗 Open in New Tab</a>
                                    <a href="'.$record->getMedia('record_media')->first()->getTemporaryUrl(now()->addMinutes(30)).'" download class="btn btn-success">⬇ Download</a>
                                </div>',

                                default => '
                                <div class="text-center">
                                    <p class="text-gray-700">📂 File Available</p>
                                    <a href="'.$record->getMedia('record_media')->first()->getTemporaryUrl(now()->addMinutes(30)).'" target="_blank" class="mr-2 btn btn-primary">🔗 Open in New Tab</a>
                                    <a href="'.$record->getMedia('record_media')->first()->getTemporaryUrl(now()->addMinutes(30)).'" download class="btn btn-success">⬇ Download</a>
                                </div>',
                            }
                        )
                        : new HtmlString('<span class="text-gray-500">No File Available</span>') // ❌ عرض "No File Available" عند عدم وجود ملف
                    )
                    ->modalButton(__('Close')),
                Action::make('view')
                    ->label(__('View'))
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => RecordMediaResource::getUrl('view', ['record' => $record])),

                // Tables\Actions\EditAction::make(),
            ])
            ->filters([
                // 📁 تصفية حسب نوع الملف
                // Tables\Filters\SelectFilter::make('file_type')
                //     ->label(__('File Type'))
                //     ->options([
                //         'application/pdf' => '📄 PDF',
                //         'image/png' => '🖼️ PNG',
                //         'image/jpeg' => '🖼️ JPEG',
                //         'image/gif' => '🖼️ GIF',
                //         'video/mp4' => '🎥 MP4 Video',
                //         'application/zip' => '📦 ZIP',
                //         'text/plain' => '📜 Text File',
                //     ])
                //     ->query(fn ($query, $value) => $query->whereHas('media', fn ($q) => $q->where('mime_type', $value))),

                // 📅 تصفية حسب تاريخ الانتهاء (منتهي، سينتهي قريبًا، صالح)
                Filter::make('expiry_status')
                    ->form([
                        Select::make('expiry_status')
                            ->label(__('Expiry Status'))
                            ->options([
                                'expired' => '⛔ Expired',
                                'expiring_soon' => '⚠️ Expiring Soon (30 days)',
                                'valid' => '✅ Valid',
                            ])
                            ->native(false), // لتفعيل المظهر الجميل للـ Filament Select
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['expiry_status'] ?? null) {
                        'expired' => $query->whereNotNull('expiry_date')->where('expiry_date', '<', now()),
                        'expiring_soon' => $query->whereNotNull('expiry_date')->whereBetween('expiry_date', [now(), now()->addDays(30)]),
                        'valid' => $query->whereNotNull('expiry_date')->where('expiry_date', '>', now()->addDays(30)),
                        default => $query,
                    }),

                // 📂 تصفية حسب الموديل المرتبط
                SelectFilter::make('recordable_type')
                    ->label(__('Model Type'))
                    ->options([
                        'App\Models\CommercialRecord' => '🏢 Commercial Record',
                        'App\Models\Employee' => '👨‍💼 Employee',
                        'App\Models\Request' => '📋 Request',
                        'App\Models\MunicipalLicense' => '🏛️ Municipal License',
                        'App\Models\NationalAddress' => '📍 National Address',
                        'App\Models\PostalSubscription' => '📬 Postal Subscription',
                        'App\Models\PrivateLicense' => '🔖 Private License',
                        'App\Models\InsuranceCompany' => '🏦 Insurance Company',
                    ]),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label(__('Created From')),
                        DatePicker::make('created_until')->label(__('Created Until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),

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
            'index' => Pages\ListRecordMedia::route('/'),
            'view' => Pages\ViewRecordMedia::route('/{record}'), // ✅ استخدام `view` بدلاً من `edit`

            'create' => Pages\CreateRecordMedia::route('/create'),
            // 'edit' => Pages\EditRecordMedia::route('/{record}/edit'),

        ];
    }
}
