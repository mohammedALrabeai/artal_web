<x-filament::widget>
    <x-filament::card>
        <div class="grid grid-cols-5 gap-4 text-center rtl">
            <div>
                <div class="text-xl font-bold">{{ $data['total'] }}</div>
                <div class="text-sm text-gray-600">الموظفين الكلي</div>
            </div>
            <div>
                <div class="text-xl font-bold text-green-600">{{ $data['present'] }}</div>
                <div class="text-sm text-gray-600">الحاضرين</div>
            </div>
            <div>
                <div class="text-xl font-bold text-blue-600">{{ $data['coverage'] }}</div>
                <div class="text-sm text-gray-600">التغطيات</div>
            </div>
            <div>
                <div class="text-xl font-bold text-yellow-600">{{ $data['off'] }}</div>
                <div class="text-sm text-gray-600">الإجازات</div>
            </div>
            <div>
                <div class="text-xl font-bold text-red-600">{{ $data['absent'] }}</div>
                <div class="text-sm text-gray-600">الغياب</div>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
