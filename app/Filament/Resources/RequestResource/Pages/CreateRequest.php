<?php

namespace App\Filament\Resources\RequestResource\Pages;

use Filament\Actions;
use App\Models\Policy;
use App\Models\Employee;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\RequestResource;

class CreateRequest extends CreateRecord
{
    protected static string $resource = RequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $policy = Policy::where('policy_type', $data['type'])->first();
    
        if (!$policy) {
            throw new \Exception(__('No policy defined for this request type.'));
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
                if ($data['duration'] > $policy->conditions['max_duration']) {
                    throw new \Exception(__('Requested duration exceeds the maximum allowed.'));
                }
                break;
    
            case 'loan':
                if ($data['amount'] > $policy->conditions['max_amount']) {
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
                if ($data['duration'] > $policy->conditions['max_hours']) {
                    throw new \Exception(__('Overtime hours exceed the maximum allowed.'));
                }
                break;
    
            default:
                throw new \Exception(__('Invalid request type.'));
        }
    
        return $data;
    }
    
    
    
}
