<x-filament::page>
    <x-filament::section>
        <x-slot name="header">خيارات الفلترة</x-slot>

        <form wire:submit.prevent>
            <div class="flex flex-wrap gap-4">
                <div class="inline-block min-w-[220px] grow">
                    {{ $this->form->getComponent('projectId') }}
                </div>
                <div class="inline-block min-w-[220px] grow">
                    {{ $this->form->getComponent('zoneId') }}
                </div>
                <div class="inline-block min-w-[220px] grow">
                    {{ $this->form->getComponent('shiftId') }}
                </div>
                <div class="inline-block min-w-[220px] grow">
                    {{ $this->form->getComponent('month') }}
                </div>
            </div>
        </form>
    </x-filament::section>

    @if ($this->employees->isNotEmpty())
        <div class="mt-4 overflow-auto border rounded-lg">
            <table class="min-w-full text-sm text-center rtl:text-right">
                <thead class="text-xs bg-gray-100">
                    <tr>
                        <th class="p-2 border">الموظف</th>
                        @foreach ($this->days as $day)
                            <th class="p-2 border whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($day)->day }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->employees as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="p-2 font-medium text-gray-800 border whitespace-nowrap">
                                {{ $row->projectRecord->employee->name }}
                            </td>

                            @foreach ($this->days as $day)
                                @php
                                    $attendance = $row->attendances->firstWhere('date', $day);
                                    $record = $row->projectRecord;
                                    $workPattern = app(
                                        \App\Filament\Pages\ManualAttendancePage::class,
                                    )->getWorkPatternLabel($record, $day);

                                    $start = $record->start_date;
                                    $end = $record->end_date;

                                    $isBefore = $start && $day < $start;
                                    $isAfter = $end && $day > $end;

                                    $status = $attendance?->status ?? $workPattern;

                                    $colors = [
                                        'present' => 'text-white',
                                        'absent' => 'text-white',
                                        'coverage' => 'text-black',
                                        'M' => 'text-black',
                                        'N' => 'text-white',
                                        'leave' => 'text-white',
                                        'UV' => 'text-white',
                                        'W' => 'text-white',
                                        'OFF' => 'text-black',
                                    ];

                                    $backgrounds = [
                                        'present' => '#2E7D32',
                                        'absent' => '#D32F2F',
                                        'coverage' => '#F9A825',
                                        'M' => '#D9D9D9',
                                        'N' => '#999999',
                                        'leave' => '#388E3C',
                                        'UV' => '#F57C00',
                                        'W' => '#795548',
                                        'OFF' => '#FFC7CE',
                                    ];

                                    $bgColor = $isBefore
                                        ? '#C8E6C9'
                                        : ($isAfter
                                            ? '#FFCDD2'
                                            : $backgrounds[$status] ?? '#ECEFF1');

                                    $textColor = $colors[$status] ?? 'text-black';

                                    $isEditing = $this->editingEmployeeId === $row->id && $this->editingDate === $day;
                                @endphp

                               <td
    class="p-1 border text-xs text-center align-middle {{ $textColor }} {{ $day === $this->editableDate ? 'cursor-pointer' : 'cursor-default' }}"
    style="background-color: {{ $bgColor }}"
    @if ($day === $this->editableDate)
        wire:click="editCell({{ $row->id }}, '{{ $day }}')"
    @endif
>
    @if ($isEditing && $day === $this->editableDate)
        <select
            wire:change="saveStatus({{ $row->id }}, '{{ $day }}', $event.target.value)"
            class="w-full p-1 text-xs rounded"
        >
            <option value="">اختر</option>
            @foreach (['present', 'absent', 'leave', 'coverage', 'UV', 'W'] as $option)
                <option value="{{ $option }}" @selected($status === $option)>
                    {{ strtoupper($option) }}
                </option>
            @endforeach
        </select>
    @else
        {{ $status }}
    @endif
</td>

                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament::page>
