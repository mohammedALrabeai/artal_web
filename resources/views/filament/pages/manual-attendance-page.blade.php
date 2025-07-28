<x-filament::page>
    {{-- ✅ [تم التعديل] إضافة x-data لتهيئة مكون AlpineJS --}}
    <div x-data="agGridManager()">

        {{-- 1. قسم الفلاتر والبحث --}}
        <x-filament::section>
            <x-slot name="header">خيارات الفلترة والبحث</x-slot>
            <div class="flex flex-wrap items-end justify-between gap-4">
                {{-- ✅ [تم التعديل] إضافة wire:ignore للحفاظ على حالة الفلاتر --}}
                <form wire:submit.prevent="applyFilters" wire:ignore class="flex flex-wrap items-end gap-4 grow">
                    <div class="inline-block min-w-[200px] grow">{{ $this->form->getComponent('projectId') }}</div>
                    <div class="inline-block min-w-[200px] grow">{{ $this->form->getComponent('zoneId') }}</div>
                    <div class="inline-block min-w-[200px] grow">{{ $this->form->getComponent('shiftId') }}</div>
                    <div class="inline-block min-w-[200px] grow">{{ $this->form->getComponent('month') }}</div>
                    <div class="inline-block">
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold text-white rounded-lg bg-primary-600 transition-colors duration-200 hover:bg-primary-700 focus-visible:outline-none focus-visible:ring-0">
                            <span>تطبيق</span>
                        </button>
                    </div>
                </form>
                <div class="relative min-w-[250px]">
                    {{-- ✅ [تم التعديل] ربط حقل البحث بـ AlpineJS باستخدام x-model --}}
                    <input type="text" id="quickFilterInput" x-model.debounce.300ms="quickFilterText"
                        class="block w-full py-2 pl-10 pr-3 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500"
                        placeholder="بحث سريع بالاسم أو الهوية...">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- الأزرار وحالة الحفظ --}}
        <div class="flex items-center justify-between mt-4">
            <button id="toggleSummaryBtn" @click="toggleSummary( )"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50">
                إظهار الملخص
            </button>
            <div id="save-status" class="px-3 py-1 text-sm text-gray-700 bg-gray-100 border border-gray-300 rounded">
                حالة التعديل: لا يوجد تعديل بعد.
            </div>
        </div>

        {{-- حاوية الجدول --}}
        {{-- ✅ [تم التعديل] إضافة x-ref و x-init لربط العنصر بـ AlpineJS --}}
        <div id="ag-grid-container" class="mt-4" wire:ignore>
            <div id="myGrid" x-ref="grid" class="ag-theme-alpine" style="height: 70vh; width: 100%;"></div>
        </div>

        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
            <script>
                // ✅ [تم الإصلاح] تغليف كل المنطق داخل دالة AlpineJS
                function agGridManager( ) {
                    return {
                        gridApi: null,
                        quickFilterText: '',
                        summaryVisible: false,

                        // دالة التهيئة الرئيسية
                        init() {
                            // مشاهدة التغييرات على حقل البحث
                            this.$watch('quickFilterText', (value) => {
                                if (this.gridApi) {
                                    this.gridApi.setGridOption('quickFilterText', value);
                                }
                            });

                            // الاستماع لحدث تطبيق الفلاتر من Livewire
                            this.$wire.on('filtersApplied', ({ filters }) => {
                                if (this.gridApi) {
                                    this.quickFilterText = ''; // مسح البحث
                                    const newColumns = this.createColumnDefs(filters.month, filters.today);
                                    this.gridApi.setGridOption('columnDefs', newColumns);
                                    this.fetchDataAndUpdateGrid(filters);
                                }
                            });

                            this.setupGrid();
                        },

                        // دالة إعداد الجدول
                        setupGrid() {
                            const gridOptions = {
                                rowModelType: 'clientSide',
                                columnDefs: this.createColumnDefs(this.$wire.get('filtersForGrid.month'), this.$wire.get('filtersForGrid.today')),
                                rowData: [],
                                rowHeight: 35,
                                headerHeight: 45,
                                getRowId: params => String(params.data.id),
                                singleClickEdit: true,
                                stopEditingWhenCellsLoseFocus: true,
                                defaultColDef: { resizable: true },
                                components: { SelectCellEditor: this.getSelectCellEditor() },
                                getRowStyle: params => params.data?.is_english ? { background: '#f9f9f9' } : {},
                                rowClassRules: {
                                    'employee-color-1': params => Math.floor(params.node.rowIndex / 2) % 2 === 0,
                                    'employee-color-2': params => Math.floor(params.node.rowIndex / 2) % 2 === 1,
                                },
                                onGridReady: params => {
                                    this.gridApi = params.api;
                                    const summaryColIds = ['summary_off', 'summary_present', 'summary_coverage', 'summary_medical', 'summary_paid_leave', 'summary_unpaid_leave', 'summary_absent', 'summary_total'];
                                    this.gridApi.setColumnsVisible(summaryColIds, false);
                                    this.fetchDataAndUpdateGrid(this.$wire.get('filtersForGrid'));
                                },
                            };

                            // إنشاء الجدول
                            agGrid.createGrid(this.$refs.grid, gridOptions);
                        },

                        // دالة جلب البيانات
                        fetchDataAndUpdateGrid(filters) {
                            if (!this.gridApi) return;
                            this.gridApi.setGridOption("loading", true);
                            fetch('/api/attendance-data', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                                body: JSON.stringify({ month: filters.month, filters: filters })
                            })
                            .then(r => r.ok ? r.json() : r.json().then(err => Promise.reject(err)))
                            .then(data => {
                                this.gridApi.setGridOption('rowData', data.rows);
                                this.gridApi.setGridOption("loading", false);
                            })
                            .catch((error) => {
                                console.error('Error fetching data:', error);
                                this.gridApi.setGridOption("loading", false);
                            });
                        },

                        // دالة إظهار/إخفاء الملخص
                        toggleSummary() {
                            if (!this.gridApi) return;
                            this.summaryVisible = !this.summaryVisible;
                            const summaryColIds = ['summary_off', 'summary_present', 'summary_coverage', 'summary_medical', 'summary_paid_leave', 'summary_unpaid_leave', 'summary_absent', 'summary_total'];
                            this.gridApi.setColumnsVisible(summaryColIds, this.summaryVisible);
                            document.getElementById('toggleSummaryBtn').textContent = this.summaryVisible ? 'إخفاء الملخص' : 'إظهار الملخص';
                        },

                        // --- دوال مساعدة (تبقى كما هي) ---
                        createColumnDefs(monthStr, editableDateStr) {
                            // ... (الكود الكامل للدالة بدون تغيير)
                            if (!monthStr) return [];
                            const year = parseInt(monthStr.substring(0, 4), 10);
                            const monthIndex = parseInt(monthStr.substring(5, 7), 10) - 1;
                            const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();
                            const staticColumns = [
                                { headerName: '#', valueGetter: params => !params.data?.is_english ? Math.floor(params.node.rowIndex / 2) + 1 : '', width: 60, pinned: 'left', cellStyle: { textAlign: 'center', fontWeight: 'bold' } },
                                { headerName: 'الاسم', field: 'name', pinned: 'left', width: 200, cellRenderer: params => params.data?.is_english ? `<i>${params.value ?? ''}</i>` : `<b>${params.value ?? ''}</b>`, cellStyle: params => ({ fontStyle: params.data?.is_english ? 'italic' : 'normal', fontWeight: params.data?.is_english ? 'normal' : 'bold' }) },
                                { headerName: 'الهوية', field: 'national_id', pinned: 'left', width: 150, cellStyle: { textAlign: 'center' } },
                                { headerName: 'موقع العمل\nUTILIZED PROJECT', field: 'project_utilized', width: 240, pinned: 'left', cellRenderer: params => params.value ?? '', cellStyle: { textAlign: 'center', whiteSpace: 'pre-line', fontWeight: 'bold', fontSize: '11px' } },
                                { headerName: 'الراتب\nSalary', field: 'salary', pinned: 'left', width: 120, cellRenderer: params => !params.data?.is_english ? `${params.value ?? ''}` : '', cellStyle: { textAlign: 'center', whiteSpace: 'pre-line' } }
                            ];
                            const dayColumns = [];
                            for (let i = 1; i <= daysInMonth; i++) {
                                const day = String(i).padStart(2, '0');
                                const currentMonthPadded = String(monthIndex + 1).padStart(2, '0');
                                const currentDateStr = `${year}-${currentMonthPadded}-${day}`;
                                const isEditable = currentDateStr === editableDateStr;
                                dayColumns.push({
                                    headerName: day,
                                    field: `attendance.${day}`,
                                    width: isEditable ? 100 : 70,
                                    editable: params => isEditable,
                                    cellEditorParams: params => ({ values: params.data?.is_english ? ['', 'COV'] : ['present', 'absent', 'leave', 'UV', 'W'] }),
                                    cellEditor: 'SelectCellEditor',
                                    cellStyle: params => {
                                        const status = params.value;
                                        const backgrounds = { 'present': '#2E7D32', 'absent': '#D32F2F', 'COV': '#F9A825', 'M': '#D9D9D9', 'N': '#999999', 'leave': '#388E3C', 'UV': '#F57C00', 'W': '#795548', 'OFF': '#FFC7CE', 'BEFORE': '#C8E6C9', 'AFTER': '#FFCDD2' };
                                        const colors = { 'present': 'white', 'absent': 'white', 'N': 'white', 'leave': 'white', 'UV': 'white', 'W': 'white' };
                                        const style = { backgroundColor: backgrounds[status] || '#ECEFF1', color: colors[status] || 'black', textAlign: 'center' };
                                        if (isEditable) {
                                            style.cursor = 'pointer';
                                            style.border = '2px solid #0d6efd';
                                            style.fontWeight = 'bold';
                                        }
                                        return style;
                                    },
                                    onCellValueChanged: (params) => {
                                        const isEnglishRow = params.data.is_english;
                                        const employeeId = params.data.id.toString().replace('-en', '');
                                        const newValue = params.newValue;
                                        const statusBox = document.getElementById('save-status');
                                        if (statusBox) statusBox.textContent = '🟡 جاري الحفظ.';
                                        if (newValue !== undefined) {
                                            const saveFn = isEnglishRow ? 'saveCoverage' : 'saveStatus';
                                            this.$wire[saveFn](employeeId, currentDateStr, newValue)
                                                .then(() => { if (statusBox) statusBox.textContent = '✅ تم الحفظ بنجاح'; })
                                                .catch(() => { if (statusBox) statusBox.textContent = '❌ فشل الحفظ'; });
                                        }
                                    }
                                });
                            }
                            const summaryValueGetter = (status) => (params) => {
                                if (params.data?.is_english) return '';
                                const attendance = params.data.attendance || {};
                                return Object.values(attendance).filter(val => val === status).length;
                            };
                            const totalValueGetter = (params) => {
                                if (params.data?.is_english) return '';
                                const attendance = params.data.attendance || {};
                                const validStates = ['present', 'coverage', 'leave', 'UV', 'absent'];
                                return Object.values(attendance).filter(val => validStates.includes(val)).length;
                            };
                            const statusColors = { 'present': { bg: '#2E7D32', text: 'white' }, 'absent': { bg: '#D32F2F', text: 'white' }, 'coverage': { bg: '#F9A825', text: 'black' }, 'M': { bg: '#D9D9D9', text: 'black' }, 'leave': { bg: '#388E3C', text: 'white' }, 'UV': { bg: '#F57C00', text: 'white' }, 'OFF': { bg: '#FFC7CE', text: 'black' } };
                            const summaryColumns = [
                                { headerName: "أوف\nOFF", valueGetter: summaryValueGetter('OFF'), colId: 'summary_off', width: 80, cellStyle: { backgroundColor: statusColors.OFF.bg, color: statusColors.OFF.text, textAlign: 'center' } },
                                { headerName: "عمل\nP", valueGetter: summaryValueGetter('present'), colId: 'summary_present', width: 80, cellStyle: { backgroundColor: statusColors.present.bg, color: statusColors.present.text, textAlign: 'center' } },
                                { headerName: "إضافي\nCOV", valueGetter: summaryValueGetter('coverage'), colId: 'summary_coverage', width: 80, cellStyle: { backgroundColor: statusColors.coverage.bg, color: statusColors.coverage.text, textAlign: 'center' } },
                                { headerName: "مرضي\nM", valueGetter: summaryValueGetter('M'), colId: 'summary_medical', width: 80, cellStyle: { backgroundColor: statusColors.M.bg, color: statusColors.M.text, textAlign: 'center' } },
                                { headerName: "إجازة مدفوعة\nPV", valueGetter: summaryValueGetter('leave'), colId: 'summary_paid_leave', width: 100, cellStyle: { backgroundColor: statusColors.leave.bg, color: statusColors.leave.text, textAlign: 'center' } },
                                { headerName: "إجازة غير مدفوعة\nUV", valueGetter: summaryValueGetter('UV'), colId: 'summary_unpaid_leave', width: 120, cellStyle: { backgroundColor: statusColors.UV.bg, color: statusColors.UV.text, textAlign: 'center' } },
                                { headerName: "غياب\nA", valueGetter: summaryValueGetter('absent'), colId: 'summary_absent', width: 80, cellStyle: { backgroundColor: statusColors.absent.bg, color: statusColors.absent.text, textAlign: 'center' } },
                                { headerName: "الإجمالي\nTotal", valueGetter: totalValueGetter, colId: 'summary_total', width: 90, cellStyle: { fontWeight: 'bold', textAlign: 'center' } }
                            ];
                            return [...staticColumns, ...dayColumns, ...summaryColumns];
                        },
                        getSelectCellEditor() {
                            class SelectCellEditor {
                                init(params) {
                                    this.eGui = document.createElement('select');
                                    this.eGui.classList.add('w-full', 'h-full', 'border-none', 'p-0', 'bg-white');
                                    const source = params.data?.is_english ? ['', 'COV'] : params.values;
                                    source.forEach(value => {
                                        const opt = document.createElement('option');
                                        opt.value = value;
                                        opt.text = (value || '—').toUpperCase();
                                        this.eGui.appendChild(opt);
                                    });
                                    this.eGui.value = params.value;
                                    this.eGui.addEventListener('change', () => params.stopEditing());
                                }
                                getGui() { return this.eGui; }
                                getValue() { return this.eGui.value; }
                                isPopup() { return false; }
                            }
                            return SelectCellEditor;
                        }
                    }
                }
            </script>
        @endpush

        @push('styles')
            {{-- أكواد CSS (لا تغيير هنا) --}}
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
                .summary-header-present { background-color: #2E7D32 !important; color: white !important; }
                .summary-header-absent { background-color: #D32F2F !important; color: white !important; }
                .summary-header-coverage { background-color: #F9A825 !important; color: black !important; }
                .summary-header-m { background-color: #D9D9D9 !important; color: black !important; }
                .summary-header-leave { background-color: #388E3C !important; color: white !important; }
                .summary-header-uv { background-color: #F57C00 !important; color: white !important; }
                .summary-header-off { background-color: #FFC7CE !important; color: black !important; }
                .ag-header-cell-label { white-space: pre-line !important; text-align: center; line-height: 1.2; padding: 4px 2px; }
            </style>
        @endpush

        @section('meta')
            <meta name="csrf-token" content="{{ csrf_token() }}">
        @endsection

    </div>
</x-filament::page>
