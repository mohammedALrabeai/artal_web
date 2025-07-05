@if($zones->count())
    @foreach($zones as $zone)
        <div class="p-3 mb-4 rounded shadow bg-gray-50">
            <h2 class="mb-2 text-lg font-bold">📍 {{ $zone->name }}</h2>
            @foreach($zone->shifts as $shift)
                <div class="p-2 mb-2 bg-white border rounded">
                    <div class="mb-1 font-semibold">🕒 {{ $shift->name }}</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($shift->slots as $slot)
                            <div class="px-3 py-2 bg-gray-100 border rounded">
                                <span class="text-sm font-bold">مكان رقم {{ $slot->slot_number }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
@else
    <div class="font-bold text-center text-red-600">لا توجد مواقع أو ورديات أو شواغر متاحة لهذا المشروع</div>
@endif
