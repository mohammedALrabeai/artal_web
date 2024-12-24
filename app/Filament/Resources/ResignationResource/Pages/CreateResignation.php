<?php
namespace App\Filament\Resources\ResignationResource\Pages;

use App\Filament\Resources\ResignationResource;
use App\Models\Loan;
use App\Models\Resignation;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\LoanNotificationMail;

class CreateResignation extends CreateRecord
{
    protected static string $resource = ResignationResource::class;

    public $loanDetails = []; // لتخزين تفاصيل القروض

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $employeeLoans = Loan::where('employee_id', $data['employee_id'])->with('bank')->get();

    //     if ($employeeLoans->isNotEmpty()) {
    //         $this->loanDetails = $employeeLoans->map(function ($loan) {
    //             return [
    //                 'bank' => $loan->bank->name,
    //                 'amount' => $loan->amount,
    //                 'start_date' => $loan->start_date,
    //                 'end_date' => $loan->end_date,
    //             ];
    //         })->toArray();

    //         // إيقاف العملية وعرض نافذة التأكيد
    //         $this->halt(); // توقف العملية هنا
    //     }

    //     return $data;
    // }

    // protected function getActions(): array
    // {
    //     return [
    //         Action::make('confirmResignation')
    //             ->label(__('Confirm Resignation (تأكيد الاستقالة)'))
    //             ->modalHeading(__('Active Loans Found (تم العثور على قروض نشطة)'))
    //             ->modalSubheading(__('The employee has active loans. Please review the details below before proceeding. (الموظف لديه قروض نشطة. يرجى مراجعة التفاصيل أدناه قبل المتابعة.)'))
    //             ->form([
    //                 Forms\Components\Repeater::make('loanDetails')
    //                     ->label(__('Loan Details (تفاصيل القرض)'))
    //                     ->schema([
    //                         Forms\Components\TextInput::make('bank')
    //                             ->label(__('Bank (البنك)'))
    //                             ->disabled(),
    //                         Forms\Components\TextInput::make('amount')
    //                             ->label(__('Amount (المبلغ)'))
    //                             ->disabled(),
    //                         Forms\Components\TextInput::make('start_date')
    //                             ->label(__('Start Date (تاريخ البدء)'))
    //                             ->disabled(),
    //                         Forms\Components\TextInput::make('end_date')
    //                             ->label(__('End Date (تاريخ الانتهاء)'))
    //                             ->disabled(),
    //                     ])
    //                     ->disableItemCreation()
    //                     ->disableItemDeletion()
    //                     ->columns(2),
    //             ])
    //             ->action(function () {
    //                 $this->saveResignation();
    //             })
    //             ->modalActions([
    //                 Action::make('cancel')
    //                     ->label(__('Cancel (إلغاء)'))
    //                     ->color('secondary'),
    //                 Action::make('proceed')
    //                     ->label(__('Proceed (متابعة)'))
    //                     ->color('primary')
    //                     ->action('saveResignation'),
    //             ]),
    //     ];
    // }

    // public function saveResignation(): void
    // {
    //     // حفظ بيانات الاستقالة
    //     $this->record = Resignation::create($this->form->getState());

    //     // إرسال بريد إلكتروني إلى البنك
    //     foreach ($this->loanDetails as $loan) {
    //         Mail::to($loan['bank'])->send(new LoanNotificationMail($loan));
    //     }

    //     // عرض إشعار نجاح
    //     Notification::make()
    //         ->title(__('Resignation Saved (تم حفظ الاستقالة)'))
    //         ->body(__('The resignation has been saved, and notifications have been sent to the banks. (تم حفظ الاستقالة، وتم إرسال الإشعارات إلى البنوك.)'))
    //         ->success()
    //         ->send();
    // }

    protected function afterCreate(): void
    {
        $employeeLoans = Loan::where('employee_id', $this->record->employee_id)->with('bank')->get();

        foreach ($employeeLoans as $loan) {
            $this->sendLoanNotification($loan);
        }
    }
    protected function sendLoanNotification($loan): void
    {
        $bankEmail = $loan->bank->email;

        if ($bankEmail) {
            Mail::raw("Employee resignation notice: Loan details for Employee ID {$loan->employee_id} are: Amount: {$loan->amount}, Start Date: {$loan->start_date}, End Date: {$loan->end_date}.", function ($message) use ($bankEmail) {
                $message->to($bankEmail)
                        ->subject('Employee Resignation Notification');
            });
        }
    }
}
