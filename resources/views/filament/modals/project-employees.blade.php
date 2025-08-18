<div x-data="{ tab: 'shift' }" class="w-full space-y-4">
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
    <div x-show="tab === 'shift'" x-cloak>
        @include('filament.modals.partials.employees-table', [
            'rows' => $employeesByShift,
            'calculateWorkPattern' => $calculateWorkPattern,
            'prefix' => 'shift',    // refs فريدة لـ Alpine
            'first'  => 'shift',    // ترتيب الأعمدة: أولاً الوردية ثم الموقع
        ])
    </div>

    {{-- جدول حسب الموقع --}}
    <div x-show="tab === 'zone'" x-cloak>
        @include('filament.modals.partials.employees-table', [
            'rows' => $employeesByZone,
            'calculateWorkPattern' => $calculateWorkPattern,
            'prefix' => 'zone',
            'first'  => 'zone',     // ترتيب الأعمدة: أولاً الموقع ثم الوردية
        ])
    </div>
</div>
