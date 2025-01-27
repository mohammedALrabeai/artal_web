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
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->type === 'leave' && $this->record->leave) {
            $leave = $this->record->leave;
    
            // تحميل بيانات الإجازة
            $data['start_date'] = $leave->start_date;
            $data['end_date'] = $leave->end_date;
            $data['leave_type'] = $leave->type;
            $data['reason'] = $leave->reason;
        }

        if ($this->record->type === 'exclusion' && $this->record->exclusion) {
            $exclusion = $this->record->exclusion;
    
            // تحميل بيانات الاستبعاد
            $data['exclusion_type'] = $exclusion->type;
            $data['exclusion_date'] = $exclusion->exclusion_date;
            $data['exclusion_reason'] = $exclusion->reason;
            $data['exclusion_attachment'] = $exclusion->attachment;
            $data['exclusion_notes'] = $exclusion->notes;
        }
    
        return $data;
    }
    
    


   

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // معالجة الطلب من النوع "إجازة"
    if ($data['type'] === 'leave') {
     
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
                // if ($employee->leave_balance < $data['duration']) {
                //     throw new \Exception(__('Insufficient leave balance.'));
                // }
                if (isset($conditions['max_duration']) && $data['duration'] > $conditions['max_duration']) {
                    throw new \Exception(__('Requested duration exceeds the maximum allowed.'));
                }
                if ($this->record->leave) {
                    // تحديث بيانات الإجازة الموجودة
                    $this->record->leave->update([
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'type' => $data['leave_type'],
                        'reason' => $data['reason'],
                    ]);
                } else {
                    // إنشاء سجل جديد للإجازة وربطه بالطلب
                    $leave = \App\Models\Leave::create([
                        'employee_id' => $data['employee_id'],
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'type' => $data['leave_type'],
                        'reason' => $data['reason'],
                    ]);
                    $data['leave_id'] = $leave->id; // تحديث `leave_id` في الطلب
                }

                break;


                case 'exclusion':
                    if ($this->record->exclusion) {
                        // تحديث بيانات الاستبعاد
                        $this->record->exclusion->update([
                            'type' => $data['exclusion_type'],
                            'exclusion_date' => $data['exclusion_date'],
                            'reason' => $data['exclusion_reason'],
                            'notes' => $data['exclusion_notes'] ?? null,
                        ]);
                
                        // تحديث أو إضافة المرفقات
                        foreach ($data['attachments'] as $attachment) {
                            $this->record->exclusion->attachments()->create([
                                'file_url' => $attachment['file_url'],
                                'added_by' => auth()->id(),
                            ]);
                        }
                    } else {
                        // إنشاء استبعاد جديد
                        $exclusion = \App\Models\Exclusion::create([
                            'employee_id' => $data['employee_id'],
                            'type' => $data['exclusion_type'],
                            'exclusion_date' => $data['exclusion_date'],
                            'reason' => $data['exclusion_reason'],
                            'notes' => $data['exclusion_notes'] ?? null,
                        ]);
                
                        // إضافة المرفقات
                        foreach ($data['attachments'] as $attachment) {
                            $exclusion->attachments()->create([
                                'file_url' => $attachment['file_url'],
                                'added_by' => auth()->id(),
                            ]);
                        }
                
                        $data['exclusion_id'] = $exclusion->id;
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
