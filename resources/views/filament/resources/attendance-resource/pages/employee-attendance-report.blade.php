<x-filament-panels::page>
    <div>
        <h1>{{ __('Employee Attendance Report') }}</h1>
    
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr>
                    <th class="px-4 py-2">{{ __('Date') }}</th>
                    <th class="px-4 py-2">{{ __('Location') }}</th>
                    <th class="px-4 py-2">{{ __('Check In') }}</th>
                    <th class="px-4 py-2">{{ __('Check Out') }}</th>
                    <th class="px-4 py-2">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->getAttendanceRecords() as $record)
                    <tr>
                        <td class="border px-4 py-2">{{ $record->date }}</td>
                        <td class="border px-4 py-2">{{ $record->location }}</td>
                        <td class="border px-4 py-2">{{ $record->check_in }}</td>
                        <td class="border px-4 py-2">{{ $record->check_out }}</td>
                        <td class="border px-4 py-2">{{ $record->status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
</x-filament-panels::page>
