<div x-data="{ tab: 'shift' }" class="w-full space-y-4 overflow-auto">
    <!-- شريط التبديل -->
    <div class="flex items-center gap-2">
        <button type="button"
                class="px-3 py-1.5 text-sm rounded-md"
                :class="tab === 'shift' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700'"
                @click="tab = 'shift'">
            ترتيب حسب الوردية
        </button>
        <button type="button"
                class="px-3 py-1.5 text-sm rounded-md"
                :class="tab === 'zone' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700'"
                @click="tab = 'zone'">
            ترتيب حسب الموقع
        </button>

        <div class="text-sm text-gray-500 ms-auto">
            مشروع: <span class="font-semibold">{{ $project->name }}</span>
        </div>
    </div>

    {{-- جدول حسب الوردية --}}
    <div x-show="tab === 'shift'" x-cloak class="w-full overflow-auto">
        <table class="border text-sm whitespace-nowrap min-w-[2000px] w-full">
            <thead class="text-right bg-gray-100">
                <tr>
                    <th class="px-3 py-2 border">#</th>
                    <th class="px-3 py-2 border">رقم الموظف</th>
                    <th class="px-3 py-2 border">الاسم</th>
                    <th class="px-3 py-2 border">الهوية</th>
                    <th class="px-3 py-2 border">الوردية</th>
                    <th class="px-3 py-2 border">الموقع</th>
                    <th class="px-3 py-2 border">نمط العمل (30 يوم)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employeesByShift as $record)
                    <tr>
                        <td class="px-3 py-2 border">{{ $loop->iteration }}</td>
                        <td class="px-3 py-2 border">{{ $record->employee->id }}</td>
                        <td class="px-3 py-2 border">{{ $record->employee->name ?? '-' }}</td>
                        <td class="px-3 py-2 border">{{ $record->employee->national_id ?? '-' }}</td>
                        <td class="px-3 py-2 border">{{ $record->shift->name ?? '-' }}</td>
                        <td class="px-3 py-2 border">{{ $record->zone->name ?? '-' }}</td>
                        <td class="px-3 py-2 border">
                            {!! is_callable($calculateWorkPattern) ? $calculateWorkPattern($record) : '—' !!}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-2 text-center text-gray-500 border">لا يوجد موظفون مسندون</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- جدول حسب الموقع --}}
    <div x-show="tab === 'zone'" x-cloak class="w-full overflow-auto">
        <table class="border text-sm whitespace-nowrap min-w-[2000px] w-full">
            <thead class="text-right bg-gray-100">
                <tr>
                    <th class="px-3 py-2 border">#</th>
                    <th class="px-3 py-2 border">رقم الموظف</th>
                    <th class="px-3 py-2 border">الاسم</th>
                    <th class="px-3 py-2 border">الهوية</th>
                    <th class="px-3 py-2 border">الموقع</th>
                    <th class="px-3 py-2 border">الوردية</th>
                    <th class="px-3 py-2 border">نمط العمل (30 يوم)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employeesByZone as $record)
                    <tr>
                        <td class="px-3 py-2 border">{{ $loop->iteration }}</td>
                        <td class="px-3 py-2 border">{{ $record->employee->id }}</td>
                        <td class="px-3 py-2 border">{{ $record->employee->name ?? '-' }}</td>
                        <td class="px-3 py-2 border">{{ $record->employee->national_id ?? '-' }}</td>
                        <td class="px-3 py-2 border">{{ $record->zone->name ?? '-' }}</td>
                        <td class="px-3 py-2 border">{{ $record->shift->name ?? '-' }}</td>
                        <td class="px-3 py-2 border">
                            {!! is_callable($calculateWorkPattern) ? $calculateWorkPattern($record) : '—' !!}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-2 text-center text-gray-500 border">لا يوجد موظفون مسندون</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
