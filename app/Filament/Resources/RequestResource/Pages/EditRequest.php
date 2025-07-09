<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Models\Employee;
use App\Models\Policy;
use Filament\Actions;
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
    protected function beforeFill(): void
{
    if (in_array($this->record->status, ['approved', 'rejected'])) {
        abort(403, __('Cannot edit an approved or rejected request.'));
    }
}

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->type === 'leave' && $this->record->leave) {
            $data['start_date'] = $this->record->leave->start_date;
            $data['end_date'] = $this->record->leave->end_date;
            $data['leave_type'] = $this->record->leave->type;
            $data['reason'] = $this->record->leave->reason;
        }

        if ($this->record->type === 'exclusion' && $this->record->exclusion) {
            $data['employee_id'] = $this->record->employee_id;
            $data['exclusion_type'] = $this->record->exclusion->type;
            $data['exclusion_date'] = $this->record->exclusion->exclusion_date;
            $data['description'] = $this->record->exclusion->reason;
            $data['employee_project_record_id'] = $this->record->exclusion->employee_project_record_id;

        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['type'] = $this->record->type; // 🔒 منع تغيير النوع

        $policy = Policy::where('policy_type', $data['type'])->first();
        if (! $policy) {
            throw new \Exception(__('No policy defined for this request type.'));
        }

        $conditions = is_array($policy->conditions) ? $policy->conditions : json_decode($policy->conditions, true);
        if (! $conditions) {
            throw new \Exception(__('Policy conditions are invalid.'));
        }

        switch ($data['type']) {
            case 'leave':
                $employee = Employee::find($data['employee_id']) ?? $this->record->employee;
                $duration = \Carbon\Carbon::parse($data['start_date'])->diffInDays($data['end_date']) + 1;
                $data['duration'] = $duration;

                if (isset($conditions['max_duration']) && $duration > $conditions['max_duration']) {
                    throw new \Exception(__('Requested duration exceeds the maximum allowed.'));
                }

                if ($this->record->leave) {
                    $this->record->leave->update([
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'type' => $data['leave_type'],
                        'reason' => $data['reason'],
                    ]);
                }
                break;

            case 'exclusion':
                if ($this->record->exclusion) {
                    $this->record->exclusion->update([
                        'employee_id' => $data['employee_id'],
                        'type' => $data['exclusion_type'],
                        'exclusion_date' => $data['exclusion_date'],
                        'reason' => $data['description'],
                        'employee_project_record_id' => $data['employee_project_record_id'],
                    ]);
                }
                break;

            case 'loan':
                if (isset($conditions['max_amount']) && $data['amount'] > $conditions['max_amount']) {
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

    protected function afterSave(): void
    {
        foreach ($this->record->attachments as $attachment) {
            if (! $attachment->employee_id) {
                $attachment->update(['employee_id' => $this->record->employee_id]);
            }
        }
    }
}
