<x-filament::card>
    <h2 class="text-lg font-bold">{{ __('Attendance Report for Today') }}</h2>
    <ul class="mt-4">
        <li>{{ __('Present Employees') }}: {{ $present_count }}</li>
        <li>{{ __('Absent Employees') }}: {{ $absent_count }}</li>
        <li>{{ __('Employees on Leave') }}: {{ $leave_count }}</li>
    </ul>
</x-filament::card>
