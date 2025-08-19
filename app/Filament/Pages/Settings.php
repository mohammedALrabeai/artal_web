<?php
namespace App\Filament\Pages;

use Filament\Forms;
use App\Models\Setting;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Settings extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    // protected static ?string $navigationGroup = 'System Settings';
    protected static string $view = 'filament.pages.settings';

    public $settings = [];

    public static function getNavigationLabel(): string
    {
        return __('Settings');
    }
    
    public static function getPluralLabel(): string
    {
        return __('Settings');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('System Settings');
    }

    public function mount()
    {
        $this->settings = Setting::pluck('value', 'key')->toArray();

        // التأكد من وجود جميع المفاتيح الافتراضية
        $defaultSettings = [
            'offline_mode' => false,
            'coverages_enabled' => false,
            'show_attendance_log' => false,
            'force_update' => false,
            'whatsapp_notifications' => false,
                'show_secure_code_widget' => false, // ✅ جديد

                 'face_verification_enabled' => false,
    'face_verification_required' => false,

        ];
    
        $this->settings = array_merge($defaultSettings, $this->settings);
    
        \Log::info('Loaded settings: ' . json_encode($this->settings));
    }

    public function save()
    {
        \Log::info('Saving settings: ' . json_encode($this->settings));

    foreach ($this->settings as $key => $value) {
        \Log::info("Key: {$key}, Value: " . json_encode($value));

        if ($value === 'true') {
            $value = true;
        } elseif ($value === 'false') {
            $value = false;
        }

        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }   
    

        \Filament\Notifications\Notification::make()
        ->title('Settings saved successfully!')
        ->success()
        ->send();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('settings.app_mode')
                ->label('وضع التطبيق')
                ->options([
                    'maintenance' => 'الصيانة',
                    'normal' => 'طبيعي',
                    'external_source' => 'مصدر خارجي',
                ])
                ->default($this->settings['app_mode'] ?? 'normal'),

            Forms\Components\Toggle::make('settings.whatsapp_notifications')
                ->label('اشعارات واتساب')
                ->default($this->settings['whatsapp_notifications']?? false)
                ->reactive(),

                Forms\Components\Toggle::make('settings.offline_mode')
                ->label('امكانية التحضير Offline')
                ->default($this->settings['offline_mode'])
                ->reactive(),
            
            Forms\Components\Toggle::make('settings.coverages_enabled')
                ->label('تفعيل التغطيات')
                ->default($this->settings['coverages_enabled'])
                ->reactive(),

                Forms\Components\Toggle::make('settings.face_verification_enabled')
    ->label('تفعيل التحقق بالوجه')
    ->default($this->settings['face_verification_enabled'] ?? false)
    ->reactive(),

Forms\Components\Toggle::make('settings.face_verification_required')
    ->label('التحقق بالوجه إجباري')
    ->helperText('عند التفعيل، لن يُسمح بالحضور بدون بصمة الوجه')
    ->default($this->settings['face_verification_required'] ?? false)
    ->visible(fn ($get) => $get('settings.face_verification_enabled') === true)
    ->reactive(),

            
            Forms\Components\Toggle::make('settings.show_attendance_log')
                ->label('عرض سجل التحضير')
                ->default($this->settings['show_attendance_log'])
                ->reactive(),
                Forms\Components\Toggle::make('settings.show_secure_code_widget')
    ->label('عرض ودجت كود التحقق')
    ->default($this->settings['show_secure_code_widget'] ?? false)
    ->reactive(),

            Forms\Components\Select::make('settings.otp_type')
                ->label('نوع OTP')
                ->options([
                    'whatsapp' => 'واتساب',
                    'sms' => 'SMS',
                ])
                ->default($this->settings['otp_type'] ?? 'sms'),

            Forms\Components\TextInput::make('settings.coordinate_sync_duration')
                ->label('مدة مزامنة الاحداثيات (بالدقائق)')
                ->numeric()
                ->default($this->settings['coordinate_sync_duration'] ?? 60),

            Forms\Components\TextInput::make('settings.coordinate_sync_distance')
                ->label('مسافة مزامنة الاحداثيات')
                ->numeric()
                ->default($this->settings['coordinate_sync_distance'] ?? 100),

                //
                Forms\Components\TextInput::make('settings.attendance_duration')
                ->label(' مدة التحضير (بالدقائق) لاحتساب حالة التاخير')
                ->numeric()
                ->default($this->settings['attendance_duration'] ?? 15),

            Forms\Components\TextInput::make('settings.latest_android_version')
                ->label('آخر نسخة للأندرويد')
                ->numeric()
                ->default($this->settings['latest_android_version'] ?? 1),

            Forms\Components\TextInput::make('settings.latest_ios_version')
                ->label('آخر نسخة للآيفون')
                ->numeric()
                ->default($this->settings['latest_ios_version'] ?? 1),

    
                Forms\Components\Toggle::make('settings.force_update')
                ->label('تحديث إجباري')
                ->default($this->settings['force_update']?? false)
                ->reactive(),

            Forms\Components\TextInput::make('settings.mobile')
                ->label('موبايل')
                ->default($this->settings['mobile'] ?? ''),

            Forms\Components\TextInput::make('settings.phone')
                ->label('هاتف')
                ->default($this->settings['phone'] ?? ''),

            Forms\Components\TextInput::make('settings.whatsapp')
                ->label('واتساب')
                ->default($this->settings['whatsapp'] ?? ''),

        ];
    }
}
