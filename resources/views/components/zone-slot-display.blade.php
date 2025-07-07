@foreach ($zones as $zone)
    <x-filament::section>
        <x-slot name="heading">📍 {{ $zone['name'] }}</x-slot>

        @foreach ($zone['shifts'] as $shift)
            <x-filament::section class="bg-gray-50" :collapsible="true">
                <x-slot name="heading">🕒 {{ $shift['name'] }}</x-slot>

                <ul class="mt-2 space-y-2">
                    @foreach ($shift['slots'] as $slot)
                        <li class="text-sm">
                            🔢 شاغر #{{ $slot['id'] }} — 👤 {{ $slot['employee_name'] ?? 'غير مخصص' }}
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endforeach
    </x-filament::section>
@endforeach
