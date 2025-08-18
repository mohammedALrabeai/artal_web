{{-- Scroll UX + Sticky + Arrows --}}
<style>
    .scrollbar-visible::-webkit-scrollbar { height: 10px; }
    .scrollbar-visible::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
    .scrollbar-visible::-webkit-scrollbar-track { background: #f1f5f9; }

    th.col-idx, td.col-idx { width: 60px; min-width: 60px; }
    th.col-id,  td.col-id  { width: 140px; min-width: 140px; }

    thead th.sticky-left-1, tbody td.sticky-left-1 { position: sticky; left: 0; z-index: 30; }
    thead th.sticky-left-2, tbody td.sticky-left-2 { position: sticky; left: 60px; z-index: 30; }

    thead th { background: #f3f4f6; } /* bg-gray-100 */
    tbody td.sticky-left-1, tbody td.sticky-left-2 { background: #ffffff; }
</style>

@php
    // التحكم بعناوين وترتيب عمودي (وردية / موقع) حسب $first
    $firstLabel  = $first === 'shift' ? 'الوردية' : 'الموقع';
    $secondLabel = $first === 'shift' ? 'الموقع' : 'الوردية';

    $firstValue = function($r) use ($first) {
        return $first === 'shift' ? ($r->shift->name ?? '—') : ($r->zone->name ?? '—');
    };
    $secondValue = function($r) use ($first) {
        return $first === 'shift' ? ($r->zone->name ?? '—') : ($r->shift->name ?? '—');
    };
@endphp

<div
    x-data="{
        leftShadow:false, rightShadow:false,
        syncFromTop(e){ $refs['{{ $prefix }}-scroller'].scrollLeft = e.target.scrollLeft; updateShadows(); },
        syncFromBottom(e){ $refs['{{ $prefix }}-topbar'].scrollLeft = e.target.scrollLeft; updateShadows(); },
        updateShadows(){
            const el = $refs['{{ $prefix }}-scroller'];
            if(!el) return;
            this.leftShadow  = el.scrollLeft > 0;
            this.rightShadow = Math.ceil(el.scrollLeft + el.clientWidth) < el.scrollWidth;
        },
        scrollBy(px){
            const el = $refs['{{ $prefix }}-scroller'];
            const tb = $refs['{{ $prefix }}-topbar'];
            if(!el || !tb) return;
            el.scrollLeft += px;
            tb.scrollLeft = el.scrollLeft;
            this.updateShadows();
        },
        mounted(){ this.$nextTick(() => this.updateShadows()); }
    }"
    x-init="mounted()"
    class="w-full space-y-2"
>
    {{-- شريط تمرير علوي متزامن --}}
    <div class="h-3 overflow-x-auto scrollbar-visible" x-ref="{{ $prefix }}-topbar" @scroll="syncFromTop">
        <div :style="{ width: ($refs['{{ $prefix }}-scroller']?.scrollWidth || 2000) + 'px' }"></div>
    </div>

    <div class="relative">
        {{-- تدرّجات جانبية لإيحاء وجود أعمدة خارج الشاشة --}}
        <div x-show="leftShadow"  class="absolute inset-y-0 left-0 w-8 pointer-events-none bg-gradient-to-r from-white to-transparent"></div>
        <div x-show="rightShadow" class="absolute inset-y-0 right-0 w-8 pointer-events-none bg-gradient-to-l from-white to-transparent"></div>

        {{-- أزرار تنقّل يمين/يسار --}}
        <div class="absolute right-0 flex items-center gap-2 -top-8">
            <button type="button" class="px-2 py-1 text-gray-700 bg-gray-100 border rounded" @click="scrollBy(-400)">◀︎</button>
            <button type="button" class="px-2 py-1 text-gray-700 bg-gray-100 border rounded" @click="scrollBy( 400)">▶︎</button>
        </div>

        {{-- الحاوية القابلة للتمرير (السفلي) --}}
        <div
            x-ref="{{ $prefix }}-scroller"
            class="overflow-x-auto scrollbar-visible"
            @scroll="syncFromBottom"
        >
            <table class="min-w-[2000px] w-max border text-sm whitespace-nowrap">
                <thead class="sticky top-0 z-20 text-right bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 border col-idx sticky-left-1">#</th>
                        <th class="px-3 py-2 border col-id sticky-left-2">رقم الموظف</th>
                        <th class="px-3 py-2 border">الاسم</th>
                        <th class="px-3 py-2 border">الهوية</th>
                        <th class="px-3 py-2 border">{{ $firstLabel }}</th>
                        <th class="px-3 py-2 border">{{ $secondLabel }}</th>
                        <th class="px-3 py-2 border">نمط العمل (30 يوم)</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($rows as $record)
                    <tr>
                        <td class="px-3 py-2 border col-idx sticky-left-1">{{ $loop->iteration }}</td>
                        <td class="px-3 py-2 border col-id sticky-left-2">{{ $record->employee->id }}</td>
                        <td class="px-3 py-2 border">{{ $record->employee->name ?? '—' }}</td>
                        <td class="px-3 py-2 border">{{ $record->employee->national_id ?? '—' }}</td>
                        <td class="px-3 py-2 border">{{ $firstValue($record) }}</td>
                        <td class="px-3 py-2 border">{{ $secondValue($record) }}</td>
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

    <div class="flex items-center gap-2 text-xs text-gray-500">
        اسحب الشريط العلوي أو السفلي يمين/يسار، أو استخدم الأزرار ◀︎ ▶︎ للتنقّل.
    </div>
</div>
