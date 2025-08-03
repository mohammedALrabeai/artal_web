<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Spatie\Permission\Models\Permission;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;


class ManageUserPermissions extends Page implements HasForms
{
    use InteractsWithForms;

    public ?int $user_id = null;

    public array $formData = [];

    protected static string $view = 'filament.pages.manage-user-permissions';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function updatedUserId($state): void
    {
        $user = User::find($state);

        if ($user) {
            $this->formData['selectedPermissions'] = $user->getPermissionNames()->toArray();

            $this->form->fill([
                'selectedPermissions' => $this->formData['selectedPermissions'],
            ]);
        } else {
            $this->formData['selectedPermissions'] = [];
            $this->form->fill(['selectedPermissions' => []]);
        }
    }

    public function save(): void
    {
        $user = User::find($this->user_id);

        if (!$user) {
            Notification::make()
                ->title('المستخدم غير موجود')
                ->body('يرجى اختيار مستخدم صالح.')
                ->danger()
                ->send();
        
            return;
        }

        $permissions = $this->formData['selectedPermissions'] ?? [];

        $user->syncPermissions($permissions);

        Notification::make()
            ->title('صلاحيات المستخدم')
            ->body('تم تحديث صلاحيات المستخدم بنجاح.')
            ->success()
            ->send();
       
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('user_id')
                ->label('اختر المستخدم')
                ->options(User::pluck('name', 'id'))
                ->reactive()
                ->searchable()
                ->required(),

            Forms\Components\CheckboxList::make('selectedPermissions')
                ->label('الصلاحيات المخصصة')
                ->options($this->getAvailablePermissions())
                ->columns(2)
                ->visible(fn () => filled($this->user_id))
                ->statePath('formData.selectedPermissions'), // ✅ هذا المفتاح هو الأساس
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('حفظ')
                ->submit('save'),
        ];
    }

    protected function getAvailablePermissions(): array
    {
        return [
            'edit_employee_status' => 'تعديل حالة الموظف',
            'edit_employee_bank' => 'تعديل بيانات البنك',
            // 'export_excel' => 'تحميل تقارير Excel',
        ];
    }
}
