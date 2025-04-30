<div class="space-y-4">
    <table class="w-full text-sm border table-auto">
        <thead>
            <tr class="text-right bg-gray-100">
                <th class="px-3 py-2 border">#</th>
                <th class="px-3 py-2 border">رقم الموظف</th>
                {{-- <th class="px-3 py-2 border">الاسم</th> --}}
                <th class="px-3 py-2 border">الاسم</th>
                <th class="px-3 py-2 border">الوردية</th>
                <th class="px-3 py-2 border">الموقع</th>
                {{-- <th class="px-3 py-2 border">الحالة</th> --}}
            </tr>
        </thead>
        <tbody>
            @forelse ($employees as $index => $record)
                <tr>
                    <td class="px-3 py-2 border">{{ $loop->iteration }}</td>

                    <td class="px-3 py-2 border">{{ $record->employee->id}}</td>
                    <td class="px-3 py-2 border">{{ $record->employee->name ?? '-' }}</td>
                    <td class="px-3 py-2 border">{{ $record->shift->name ?? '-' }}</td>
                    <td class="px-3 py-2 border">{{ $record->zone->name ?? '-' }}</td>
                    {{-- <td class="px-3 py-2 border">{{ $record->status ?? '-' }}</td> --}}
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-2 text-center text-gray-500 border">لا يوجد موظفين مسندين</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
