<div class="w-full overflow-auto">
    <table class="border text-sm whitespace-nowrap min-w-[2000px]">
        <thead class="text-right bg-gray-100">
            <tr>
                <th class="px-3 py-2 border">#</th>
                <th class="px-3 py-2 border">رقم الموظف</th>
                <th class="px-3 py-2 border">الاسم</th>
                <th class="px-3 py-2 border">الهوية</th>
                <th class="px-3 py-2 border">الوردية</th>
                <th class="px-3 py-2 border">نمط العمل (30 يوم)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($assignments as $index => $record)
                <tr>
                    <td class="px-3 py-2 border">{{ $loop->iteration }}</td>
                    <td class="px-3 py-2 border">{{ $record->employee->id }}</td>
                    <td class="px-3 py-2 border">{{ $record->employee->name ?? '-' }}</td>
                    <td class="px-3 py-2 border">{{ $record->employee->national_id ?? '-' }}</td>
                    <td class="px-3 py-2 border">{{ $record->shift->name ?? '-' }}</td>
                    <td class="px-3 py-2 border">
                        {!! ($calculateWorkPattern)($record) !!}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
