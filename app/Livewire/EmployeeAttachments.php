<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Attachment;

class EmployeeAttachments extends Component
{
    public $employee;

    public function mount($employee)
    {
        $this->employee = $employee;
    }

    public function render()
    {
        return view('livewire.employee-attachments', [
            'attachments' => Attachment::where('employee_id', $this->employee->id)->get(),
        ]);
    }
}
