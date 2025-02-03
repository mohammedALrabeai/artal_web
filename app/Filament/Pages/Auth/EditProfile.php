<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Contracts\HasForms;

class EditProfile extends Page implements HasForms
{
    use Forms\Concerns\InteractsWithForms;

    // أيقونة وعنوان الصفحة في قائمة التنقل (اختياري)
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'تعديل الملف الشخصي';

    // اسم ملف العرض (يجب إنشاء الملف في resources/views/filament/pages/auth/edit-profile.blade.php)
    protected static string $view = 'filament.pages.auth.edit-profile';

    // المتغيرات التي سيتم ربطها مع الحقول في النموذج
    public $name;
    public $email;
    public $password;

    /**
     * عند تحميل الصفحة، يتم ملء الحقول ببيانات المستخدم الحالي.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name  = $user->name;
        $this->email = $user->email;
    }

    /**
     * تعريف حقول النموذج.
     */
    protected function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('الاسم')
                ->required(),

            TextInput::make('email')
                ->label('البريد الإلكتروني')
                ->email()
                ->required(),

            TextInput::make('password')
                ->label('كلمة المرور الجديدة')
                ->password()
                // إذا ترك الحقل فارغاً فلن يتم تغيير كلمة المرور
                ->dehydrated(fn($state) => filled($state))
                ->helperText('اترك الحقل فارغاً إذا لم ترغب بتغيير كلمة المرور'),
        ];
    }

    /**
     * عند الضغط على زر الحفظ، يتم تحديث بيانات المستخدم.
     */
    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        $user->name  = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        $this->notify('success', 'تم تحديث الملف الشخصي بنجاح.');
    }
}
