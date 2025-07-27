<x-filament::page>
    {{-- العنصر الحاوي الوحيد الذي يلتف حول كل شيء لحل مشكلة Livewire --}}
    <div>

        {{-- 1. قسم الفلاتر مع الزر الجديد والتصميم الأفقي --}}
        <x-filament::section>
            <x-slot name="header">خيارات الفلترة</x-slot>
            
            <form wire:submit.prevent="applyFilters">
                {{-- ✅ [تم التعديل] العودة إلى التصميم الأفقي باستخدام Flexbox --}}
                <div class="flex flex-wrap items-end gap-4">
                    
                    {{-- عرض كل حقل فلتر على حدة للتحكم في التنسيق --}}
                    <div class="inline-block min-w-[220px] grow">{{ $this->form->getComponent('projectId') }}</div>
                    <div class="inline-block min-w-[220px] grow">{{ $this->form->getComponent('zoneId') }}</div>
                    <div class="inline-block min-w-[220px] grow">{{ $this->form->getComponent('shiftId') }}</div>
                    <div class="inline-block min-w-[220px] grow">{{ $this->form->getComponent('month') }}</div>
                    
                    {{-- زر تطبيق الفلاتر --}}
                   <div class="inline-block">
    <button
        type="submit"
        class="inline-flex items-center justify-center gap-2
               px-6 py-2.5 text-sm font-semibold text-white
               rounded-lg bg-primary-600 transition-colors duration-200
               hover:bg-primary-700
               focus-visible:outline-none focus-visible:ring-0"
    >
        <!-- حالة التحميل -->
        <span wire:loading wire:target="applyFilters">جارٍ التحميل…</span>
        <!-- الحالة العاديّة -->
        <span wire:loading.remove wire:target="applyFilters">تطبيق</span>
    </button>
</div>

                </div>
            </form>
        </x-filament::section>

        {{-- حالة الحفظ (لا تغيير هنا) --}}
        <div id="save-status" class="px-3 py-1 mt-2 text-sm text-gray-700 bg-gray-100 border border-gray-300 rounded">
            حالة التعديل: لا يوجد تعديل بعد.
        </div>

        {{-- 2. حاوية جدول AG Grid (لا تغيير هنا) --}}
        <div id="ag-grid-container" class="mt-4" wire:ignore>
            <div id="myGrid" class="ag-theme-alpine" style="height: 70vh; width: 100%;"></div>
        </div>

        {{-- 3. الأصول (Assets) والـ Meta Tags (لا تغيير هنا) --}}
        @push('scripts')
            {{-- الكود الكامل لـ JavaScript يبقى كما هو بدون أي تغيير --}}
            <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
            <script>
                document.addEventListener('livewire:navigated', ( ) => setTimeout(setupGrid, 50));

                async function setupGrid() {
                    let gridApi;
                    const gridDiv = document.querySelector('#myGrid');
                    if (!gridDiv) return;

                    const component = Livewire.find(gridDiv.closest('[wire\\:id]').getAttribute('wire:id'));

                    // --- محرر القائمة المنسدلة (لا تغيير هنا) ---
                    class SelectCellEditor {
                        init(params) { this.eGui = document.createElement('select'); this.eGui.classList.add('w-full', 'h-full', 'border-none', 'p-0', 'bg-white'); params.values.forEach(value => { const option = document.createElement('option'); option.value = value; option.text = value.toUpperCase(); this.eGui.appendChild(option); }); this.eGui.value = params.value; this.eGui.addEventListener('change', () => params.stopEditing()); }
                        getGui() { return this.eGui; }
                        getValue() { return this.eGui.value; }
                        isPopup() { return false; }
                    }

                    // --- دالة إنشاء الأعمدة (لا تغيير هنا) ---
                    function createColumnDefs(monthStr, editableDateStr) {
                        if (!monthStr) { console.error("Month string is missing!"); return []; }
                        const year = parseInt(monthStr.substring(0, 4), 10);
                        const monthIndex = parseInt(monthStr.substring(5, 7), 10) - 1;
                        const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();
                        const staticColumns = [
                            { headerName: '#', valueGetter: params => !params.data?.is_english ? Math.floor(params.node.rowIndex / 2) + 1 : '', width: 60, pinned: 'left', cellStyle: { textAlign: 'center', fontWeight: 'bold' } },
                            { headerName: 'الاسم', field: 'name', pinned: 'left', width: 200, cellRenderer: params => params.data?.is_english ? `<i>${params.value ?? ''}</i>` : `<b>${params.value ?? ''}</b>`, cellStyle: params => ({ fontStyle: params.data?.is_english ? 'italic' : 'normal', fontWeight: params.data?.is_english ? 'normal' : 'bold' }) },
                            { headerName: 'الهوية', field: 'national_id', pinned: 'left', width: 150, cellStyle: { textAlign: 'center' } },
                            { headerName: 'موقع العمل\nUTILIZED PROJECT', field: 'project_utilized', width: 240, pinned: 'left', cellRenderer: params => params.value ?? '', cellStyle: { textAlign: 'center', whiteSpace: 'pre-line', fontWeight: 'bold', fontSize: '11px' } },
                            { headerName: 'الراتب\nSalary', field: 'salary', pinned: 'left', width: 120, cellRenderer: params => !params.data?.is_english ? `${params.value ?? ''}` : '', cellStyle: { textAlign: 'center', whiteSpace: 'pre-line' } },
                            { headerName: 'حضور', field: 'stats.present', width: 90, cellStyle: { textAlign: 'center' } },
                            { headerName: 'غياب', field: 'stats.absent', width: 90, cellStyle: { textAlign: 'center' } },
                        ];
                        const dayColumns = [];
                        for (let i = 1; i <= daysInMonth; i++) {
                            const day = String(i).padStart(2, '0');
                            const currentMonthPadded = String(monthIndex + 1).padStart(2, '0');
                            const currentDateStr = `${year}-${currentMonthPadded}-${day}`;
                            const isEditable = currentDateStr === editableDateStr;
                            dayColumns.push({
                                headerName: day, field: `attendance.${day}`, width: isEditable ? 100 : 70, editable: params => isEditable && !params.data?.is_english, cellEditor: SelectCellEditor, cellEditorParams: { values: ['present', 'absent', 'leave', 'coverage', 'UV', 'W'] },
                                cellStyle: params => {
                                    const status = params.value;
                                    const backgrounds = { 'present': '#2E7D32', 'absent': '#D32F2F', 'coverage': '#F9A825', 'M': '#D9D9D9', 'N': '#999999', 'leave': '#388E3C', 'UV': '#F57C00', 'W': '#795548', 'OFF': '#FFC7CE', 'BEFORE': '#C8E6C9', 'AFTER': '#FFCDD2' };
                                    const colors = { 'present': 'white', 'absent': 'white', 'N': 'white', 'leave': 'white', 'UV': 'white', 'W': 'white' };
                                    const style = { backgroundColor: backgrounds[status] || '#ECEFF1', color: colors[status] || 'black', textAlign: 'center' };
                                    if (isEditable) { style.cursor = 'pointer'; style.border = '2px solid #0d6efd'; style.fontWeight = 'bold'; }
                                    return style;
                                },
                                onCellValueChanged: params => {
                                    const employeeId = params.data.id;
                                    const newValue = params.newValue;
                                    const statusBox = document.getElementById('save-status');
                                    if (statusBox) statusBox.textContent = '🟡 جاري الحفظ...';
                                    if (newValue) {
                                        component.saveStatus(employeeId, currentDateStr, newValue)
                                            .then(() => { if (statusBox) statusBox.textContent = '✅ تم الحفظ بنجاح'; })
                                            .catch(() => { if (statusBox) statusBox.textContent = '❌ فشل الحفظ'; });
                                    }
                                }
                            });
                        }
                        return [...staticColumns, ...dayColumns];
                    }

                    const datasource = {
                        getRows: params => {
                            const filters = component.get('filtersForGrid');
                            fetch('/api/attendance-data', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                                body: JSON.stringify({
                                    offset: params.startRow,
                                    limit: params.endRow - params.startRow,
                                    month: filters.month,
                                    filters: filters
                                })
                            })
                            .then(r => r.ok ? r.json() : r.json().then(err => Promise.reject(err)))
                            .then(data => params.successCallback(data.rows, data.total))
                            .catch(() => params.failCallback());
                        }
                    };

                    try {
                        const initialFilters = component.get('filtersForGrid');
                        const gridOptions = {
                            columnDefs: createColumnDefs(initialFilters.month, initialFilters.today),
                            rowModelType: 'infinite',
                            datasource: datasource,
                            cacheBlockSize: 200, maxBlocksInCache: 2, paginationPageSize: 200, rowBuffer: 10, rowHeight: 35,
                            getRowId: params => params.data.id,
                            singleClickEdit: true, stopEditingWhenCellsLoseFocus: true,
                            defaultColDef: { resizable: true },
                            components: { SelectCellEditor },
                            getRowStyle: params => params.data?.is_english ? { background: '#f9f9f9' } : {},
                            rowClassRules: {
                                'employee-color-1': params => Math.floor(params.node.rowIndex / 2) % 2 === 0,
                                'employee-color-2': params => Math.floor(params.node.rowIndex / 2) % 2 === 1,
                            },
                            onGridReady: params => { gridApi = params.api; },
                        };
                        agGrid.createGrid(gridDiv, gridOptions);
                    } catch (e) { console.error("خطأ فادح أثناء إعداد الجدول:", e); }

                    Livewire.on('filtersApplied', ({ filters }) => {
                        if (gridApi) {
                            console.log("Event 'filtersApplied' received. Refreshing grid with new filters:", filters);
                            const newColumns = createColumnDefs(filters.month, filters.today);
                            gridApi.setGridOption('columnDefs', newColumns);
                            gridApi.purgeInfiniteCache();
                        }
                    });
                }
            </script>
        @endpush

        @push('styles')
            {{-- أكواد CSS تبقى كما هي --}}
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-alpine.css">
            <style>
                .ag-header-cell-label { justify-content: center; }
                .ag-theme-alpine .ag-cell, .ag-theme-alpine .ag-header-cell { border: none !important; }
                .ag-theme-alpine .ag-row { border: none !important; }
                .ag-theme-alpine .ag-cell { padding: 6px 8px !important; font-size: 13px; }
                .ag-row.employee-color-1 { background-color: #ffffff !important; }
                .ag-row.employee-color-2 { background-color: #f9f9f9 !important; }
                .ag-row.english-row .ag-cell { border-top: none !important; }
                .ag-row:hover { filter: brightness(0.98 ); }
                .ag-row.ag-row-selected .ag-cell { background-color: inherit !important; }
                .ag-theme-alpine .ag-cell { white-space: pre-line !important; }
                .ag-cell[col-id="project_utilized"] { font-size: 11px !important; }
            </style>
        @endpush

        @section('meta')
            <meta name="csrf-token" content="{{ csrf_token() }}">
        @endsection

    </div>
</x-filament::page>
