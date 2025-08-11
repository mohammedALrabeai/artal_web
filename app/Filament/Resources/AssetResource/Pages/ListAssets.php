<?php

namespace App\Filament\Resources\AssetResource\Pages;

use App\Models\Asset;
use Filament\Actions;
use App\Enums\AssetStatus;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Resources\Components\Tab;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Filament\Resources\AssetResource;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    // ربط التبويب بالرابط (?activeTab=...) والعمل مع SPA
    #[Url(as: 'activeTab', history: true, keep: true)]
    public ?string $activeTab = null;

    public function getDefaultActiveTab(): string
    {
        return 'all';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()->label(__('All')),
            'available' => Tab::make()->label(__('Available')),
            'assigned' => Tab::make()->label(__('Assigned')),
            'charged' => Tab::make()->label(__('Charged Assets')),
        ];
    }

    // الفلترة الفعلية حسب التبويب النشط (من الخاصية المرتبطة بالرابط)
    protected function getTableQuery(): Builder
    {
        $q = Asset::query()->select('assets.*');
        $active = $this->activeTab ?? $this->getDefaultActiveTab();

        return match ($active) {
            'available' => $q
                ->where('status', AssetStatus::AVAILABLE->value)
                ->whereDoesntHave('assignments', fn (Builder $a) => $a->whereNull('returned_date')),

            'assigned' => $q
                ->whereHas('assignments', fn (Builder $a) => $a->whereNull('returned_date')),

            'charged' => $q
                ->where('status', AssetStatus::CHARGED->value),

            default => $q,
        };
    }

    // عند تغيير التبويب في SPA: أعد ضبط الجدول لإعادة التحميل فورًا
  public function updated($name, $value): void
{
    if ($name === 'activeTab') {
        $this->resetTable();
        $this->resetPage();
    }
}

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(), // الزر الافتراضي
            Actions\Action::make('bulkAddAssets')
                ->label(__('Bulk Add Assets'))
                ->icon('heroicon-o-plus-circle')
                ->modalHeading(__('Bulk Add Assets'))
                ->form([
                    Section::make(__('Basic'))
                        ->schema([
                            TextInput::make('base_name')
                                ->label(__('Base Name'))
                                ->default('جاكت')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('quantity')
                                ->label(__('Quantity'))
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required(),

                            TextInput::make('start_from')
                                ->label(__('Start From'))
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->helperText(__('Starting index for the name numbering (e.g., جاكت-1)')),

                            TextInput::make('name_separator')
                                ->label(__('Name Separator'))
                                ->default('-')
                                ->maxLength(2)
                                ->helperText(__('Will produce: {name}{sep}{n} e.g. جاكت-1')),

                            Select::make('digits_style')
                                ->label(__('Digits Style'))
                                ->options([
                                    'western' => 'Western (1,2,3)',
                                    'arabic'  => 'Arabic-Indic (١،٢،٣)',
                                ])
                                ->default('western'),
                        ])
                        ->columns(2),

                    Section::make(__('Serial Number'))
                        ->schema([
                            TextInput::make('serial_prefix')
                                ->label(__('Serial Prefix'))
                                ->default('JKT')
                                ->required()
                                ->maxLength(20),

                            TextInput::make('serial_pad')
                                ->label(__('Serial Pad Length'))
                                ->numeric()
                                ->minValue(1)
                                ->default(6)
                                ->helperText(__('JKT-') . '<code>' . __('000001') . '</code>'),

                            TextInput::make('serial_separator')
                                ->label(__('Serial Separator'))
                                ->default('-')
                                ->maxLength(2),
                        ])
                        ->columns(3),

                    Section::make(__('Optional Fields'))
                        ->schema([
                            Textarea::make('description')
                                ->label(__('Description'))
                                ->rows(3),

                            DatePicker::make('purchase_date')
                                ->label(__('Purchase Date')),

                            TextInput::make('value')
                                ->label(__('Asset Value'))
                                ->numeric(),

                            TextInput::make('condition')
                                ->label(__('Condition')),

                            TextInput::make('status')
                                ->label(__('Status'))
                                ->default('Available'),
                        ])
                        ->collapsible()
                        ->collapsed(),
                ])
                ->action(function (array $data) {
                    $baseName       = trim($data['base_name']);
                    $quantity       = (int) $data['quantity'];
                    $startFrom      = (int) ($data['start_from'] ?? 1);
                    $nameSep        = $data['name_separator'] ?? '-';
                    $digitsStyle    = $data['digits_style'] ?? 'western';

                    $serialPrefix   = trim($data['serial_prefix']);
                    $serialPad      = (int) ($data['serial_pad'] ?? 6);
                    $serialSep      = $data['serial_separator'] ?? '-';

                    $desc           = $data['description'] ?? null;
                    $purchaseDate   = $data['purchase_date'] ?? null;
                    $value          = $data['value'] ?? null;
                    $condition      = $data['condition'] ?? null;
                    $status = $data['status'] ?? AssetStatus::AVAILABLE->value;

                    // لو الحقل موجود كنص حر، صحّحه:
                    $status = strtolower(trim($status));
                    if (! in_array($status, array_keys(AssetStatus::labels()), true)) {
                        $status = AssetStatus::AVAILABLE->value;
                    }

                    // تحويل للأرقام العربية إذا لزم
                    $toArabicIndic = static function (int $n): string {
                        $map = ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩'];
                        return strtr((string) $n, $map);
                    };

                    DB::transaction(function () use (
                        $baseName,
                        $quantity,
                        $startFrom,
                        $nameSep,
                        $digitsStyle,
                        $serialPrefix,
                        $serialPad,
                        $serialSep,
                        $desc,
                        $purchaseDate,
                        $value,
                        $condition,
                        $status,
                        $toArabicIndic
                    ) {
                        $created = 0;
                        $i = max(1, $startFrom);

                        // تحسين: كاش للأسماء والسيريالات الموجودة لتقليل الاستعلامات
                        $existingSerials = Asset::query()
                            ->where('serial_number', 'like', $serialPrefix . $serialSep . '%')
                            ->pluck('serial_number')
                            ->all();
                        $serialSet = array_fill_keys($existingSerials, true);

                        $existingNames = Asset::query()
                            ->where('asset_name', 'like', $baseName . $nameSep . '%')
                            ->pluck('asset_name')
                            ->all();
                        $nameSet = array_fill_keys($existingNames, true);

                        while ($created < $quantity) {
                            // ابحث عن أقرب رقم غير مستخدم (اسم + سيريال)
                            $candidateFound = false;
                            while (! $candidateFound) {
                                $serialNumRaw = str_pad((string) $i, $serialPad, '0', STR_PAD_LEFT);
                                $serial       = $serialPrefix . $serialSep . $serialNumRaw;

                                $nameNumber   = ($digitsStyle === 'arabic') ? $toArabicIndic($i) : (string) $i;
                                $assetName    = $baseName . $nameSep . $nameNumber;

                                // تحقق التعارض محلياً ثم قاعدة البيانات (ضمان)
                                $serialTaken  = isset($serialSet[$serial])
                                    || Asset::query()->where('serial_number', $serial)->exists();

                                $nameTaken    = isset($nameSet[$assetName])
                                    || Asset::query()->where('asset_name', $assetName)->exists();

                                if ($serialTaken || $nameTaken) {
                                    $i++;
                                    continue;
                                }

                                $candidateFound = true;

                                $payload = [
                                    'asset_name'    => $assetName,
                                    'serial_number' => $serial,
                                    'description'   => $desc,
                                    'purchase_date' => $purchaseDate,
                                    'value'         => $value,
                                    'condition'     => $condition,
                                    'status'        => $status,
                                ];

                                // إن كان لديك عمود inventory_code ونبغاه يساوي السيريال
                                if (Schema::hasColumn('assets', 'inventory_code')) {
                                    $payload['inventory_code'] = $serial;
                                }

                                Asset::query()->create($payload);

                                // حدّث الكاش
                                $serialSet[$serial]   = true;
                                $nameSet[$assetName]  = true;

                                $created++;
                                $i++;
                            }
                        }
                    });

                    Notification::make()
                        ->title(__('Assets created successfully'))
                        ->success()
                        ->send();
                })
                ->modalSubmitActionLabel(__('Create')),
        ];
    }
}
