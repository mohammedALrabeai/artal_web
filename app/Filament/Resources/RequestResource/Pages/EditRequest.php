<?php

namespace App\Filament\Resources\RequestResource\Pages;

use Filament\Actions;
use App\Models\Policy;
use App\Models\Employee;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\RequestResource;

class EditRequest extends EditRecord
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
   

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->type === 'leave' && $this->leave) {
            $this->leave->update([
                'start_date' => $data['leave']['start_date'],
                'end_date' => $data['leave']['end_date'],
                'type' => $data['leave']['type'],
                'reason' => $data['leave']['reason'],
            ]);
        }
        $policy = Policy::where('policy_type', $data['type'])->first();
    
        if (!$policy) {
            throw new \Exception(__('No policy defined for this request type.'));
        }
    
        // تحويل conditions إلى مصفوفة إذا كانت نصًا
        $conditions = is_array($policy->conditions) ? $policy->conditions : json_decode($policy->conditions, true);
    
        if (!$conditions) {
            throw new \Exception(__('Policy conditions are invalid.'));
        }
    
        switch ($data['type']) {
            case 'leave':
                $employee = Employee::find($data['employee_id']);
                if (!$employee) {
                    throw new \Exception(__('Employee not found.'));
                }
                if ($employee->leave_balance < $data['duration']) {
                    throw new \Exception(__('Insufficient leave balance.'));
                }
                if (isset($conditions['max_duration']) && $data['duration'] > $conditions['max_duration']) {
                    throw new \Exception(__('Requested duration exceeds the maximum allowed.'));
                }
                break;
    
            case 'loan':
                if (isset($conditions['max_amount']) && $data['amount'] > $conditions['max_amount']) {
                    \Filament\Notifications\Notification::make()
                        ->title(__('Amount Exceeds Maximum'))
                        ->body(__('Requested amount exceeds the maximum allowed: :max_amount.', ['max_amount' => $conditions['max_amount']]))
                        ->danger()
                        ->send();
    
                    throw new \Exception(__('Requested amount exceeds the maximum allowed.'));
                }
                break;
    
            case 'compensation':
                if (!isset($data['additional_data']['documentation'])) {
                    throw new \Exception(__('Documentation is required for compensation requests.'));
                }
                break;
    
            case 'transfer':
                if (!isset($data['target_location'])) {
                    throw new \Exception(__('Target location is required for transfer requests.'));
                }
                break;
    
            case 'overtime':
                if (isset($conditions['max_hours']) && $data['duration'] > $conditions['max_hours']) {
                    throw new \Exception(__('Overtime hours exceed the maximum allowed.'));
                }
                break;
    
            default:
                throw new \Exception(__('Invalid request type.'));
        }
    
        return $data;
    }
    
    
    
}
