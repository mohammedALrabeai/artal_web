<x-filament::page>
    <div>
        {{-- 1. قسم الفلاتر والبحث --}}
        <x-filament::section>
            <x-slot name="header">خيارات الفلترة والبحث</x-slot>
            <div class="flex flex-wrap items-end justify-between gap-4">
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
                    <input type="text" id="quickFilterInput"
                        class="block w-full py-2 pl-10 pr-3 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500"
                        placeholder="بحث سريع بالاسم أو الهوية...">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- الأزرار وحالة الحفظ --}}
        <div class="flex items-center justify-between mt-4">
            <div class="flex items-center gap-2">
                {{-- زر الملخص --}}
                <button id="toggleSummaryBtn"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50">
                    إظهار الملخص
                </button>

                {{-- زر ملء الشاشة --}}
                <button id="fullscreenBtn" title="عرض ملء الشاشة"
                    class="flex items-center justify-center w-10 h-10 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50">
                    {{-- أيقونات ملء الشاشة --}}
                    <svg id="fullscreen-icon-open" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 1v4m0 0h-4m4 0l-5-5" />
                    </svg>
                    <svg id="fullscreen-icon-close" xmlns="http://www.w3.org/2000/svg" class="hidden w-5 h-5"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M10 14l-5 5m0 0v-4m0 4h4m11-5l-5-5m0 0v4m0-4h-4" />
                    </svg>
                </button>

                {{-- ✨ [جديد] زر وقائمة اختيار الأعمدة --}}
                <div class="relative" id="column-chooser-container">
                    <button id="columnChooserBtn" title="اختيار الأعمدة"
                        class="flex items-center justify-center w-10 h-10 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m-6 10h6m-6 0V7" />
                        </svg>
                    </button>
                    {{-- سيتم ملء هذه القائمة بواسطة JavaScript --}}
                    <div id="columnChooserDropdown"
                        class="absolute right-0 z-20 hidden w-64 p-4 mt-2 bg-white border border-gray-200 rounded-lg shadow-xl">
                        <h4 class="pb-2 mb-2 font-bold border-b">إظهار/إخفاء الأعمدة</h4>
                        <div id="column-list" class="space-y-2 overflow-y-auto max-h-80">
                            {{-- Checkboxes will be inserted here --}}
                        </div>
                    </div>
                </div>
            </div>

            <div id="save-status" class="px-3 py-1 text-sm text-gray-700 bg-gray-100 border border-gray-300 rounded">
                حالة التعديل: لا يوجد تعديل بعد.
            </div>
        </div>

        {{-- حاوية الجدول --}}
        <div id="ag-grid-container" class="mt-4" wire:ignore>
            {{-- ✨ [الإصلاح 2/3] إضافة الكلاس هنا --}}
            <div id="myGrid" class="ag-theme-alpine" style="height: 70vh; width: 100%;"></div>
        </div>

        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    let gridApi;
                    const gridDiv = document.querySelector('#myGrid');
                    if (!gridDiv) return;
                    const STORAGE_KEY = 'agGridColumnVisibility';

                    function saveColumnState() {
                        if (!gridApi) return;
                        const columns = gridApi.getColumns();
                        const state = {};
                        columns.forEach(col => {
                            if (!col.getColDef().pinned) {
                                state[col.getColId()] = col.isVisible();
                            }
                        });
                        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
                    }

                    function applyColumnState() {
                        const savedState = JSON.parse(localStorage.getItem(STORAGE_KEY));
                        if (savedState && gridApi) {
                            const columnIds = Object.keys(savedState);
                            columnIds.forEach(colId => {
                                // ✨ [الإصلاح 1/3] تصحيح اسم الدالة
                                gridApi.setColumnsVisible([colId], savedState[colId]);
                            });
                        }
                    }

                    function populateColumnChooser() {
                        const columnListDiv = document.getElementById('column-list');
                        if (!gridApi || !columnListDiv) return;
                        columnListDiv.innerHTML = '';
                        const columns = gridApi.getColumns();

                        columns.forEach(col => {
                            const colDef = col.getColDef();
                            if (colDef.pinned || col.getColId() === '0') return;

                            const label = document.createElement('label');
                            label.className =
                                'flex items-center p-1 space-x-2 rounded cursor-pointer hover:bg-gray-100';
                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.checked = col.isVisible();
                            checkbox.className = 'rounded text-primary-600 focus:ring-primary-500';
                            const text = document.createTextNode(colDef.headerName);

                            label.appendChild(checkbox);
                            label.appendChild(text);
                            columnListDiv.appendChild(label);

                            checkbox.addEventListener('change', (e) => {
                                // ✨ [الإصلاح 1/3] تصحيح اسم الدالة
                                gridApi.setColumnsVisible([col.getColId()], e.target.checked);
                                saveColumnState();
                            });
                        });
                    }
                    const getEmployees = (() => {
                        let employeesPromise = null;
                        return () => {
                            if (!employeesPromise) {
                                employeesPromise = fetch('/api/employees-list')
                                    .then(response => {
                                        if (!response.ok) throw new Error('Network response was not ok');
                                        return response.json();
                                    })
                                    .catch(error => {
                                        console.error('Failed to fetch employees:', error);
                                        return []; // إرجاع مصفوفة فارغة في حالة الفشل
                                    });
                            }
                            return employeesPromise;
                        };
                    })();

                    function createColumnDefs(monthStr, editableDateStr) {
                        if (!monthStr) return [];
                        const year = parseInt(monthStr.substring(0, 4), 10);
                        const monthIndex = parseInt(monthStr.substring(5, 7), 10) - 1;
                        const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();

                        const staticColumns = [{
                                headerName: '#',
                                valueGetter: (params) => params.node.rowIndex + 1,
                                width: 60,
                                pinned: 'left',
                                cellStyle: {
                                    textAlign: 'center',
                                    fontWeight: 'bold'
                                }
                            },
                            {
                                headerName: 'الاسم',
                                field: 'name',
                                pinned: 'left',
                                width: 200,
                                cellRenderer: (p) => `<b>${p.value}</b>  <i>${p.data.english_name || ''}</i>`,
                                cellStyle: {
                                    lineHeight: '1.2',
                                    whiteSpace: 'pre-line'
                                }
                            },
                            {
                                headerName: 'الهوية',
                                field: 'national_id',
                                pinned: 'left',
                                width: 150,
                                cellStyle: {
                                    textAlign: 'center'
                                }
                            },
                            {
                                headerName: 'المشروع / الموقع',
                                field: 'project_utilized',
                                width: 240,
                                pinned: 'left',
                                cellStyle: {
                                    textAlign: 'center',
                                    whiteSpace: 'pre-line',
                                    fontSize: '11px'
                                }
                            },
                            {
                                headerName: 'الراتب',
                                field: 'salary',
                                pinned: 'left',
                                width: 120,
                                cellStyle: {
                                    textAlign: 'center'
                                }
                            }
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
                                editable: isEditable,
                                cellEditor: 'DayCellEditor', // استخدام اسم المكون مباشرة
                                cellEditorPopup: true,
                                cellEditorPopupPosition: 'under',
                                cellEditorParams: {
                                    currentDateStr: currentDateStr // نمرر التاريخ هنا
                                },
                                valueFormatter: params => params.value ? params.value.status : '',
                                valueParser: params => params.newValue,
                                cellClassRules: {
                                    'ag-cell-m': params => params.value?.status === 'M',
                                    'ag-cell-d': params => params.value?.status === 'D',
                                    'ag-cell-d8': params => params.value?.status === 'D8',
                                    'ag-cell-x': params => params.value?.status === 'X',
                                    'ag-cell-a': params => params.value?.status === 'A',
                                    'ag-cell-m8': params => params.value?.status === 'M8',
                                    'ag-cell-n8': params => params.value?.status === 'N8',
                                    'ag-cell-n12': params => params.value?.status === 'N12',
                                    'ag-cell-m12': params => params.value?.status === 'M12',
                                    'ag-cell-n': params => params.value?.status === 'N',
                                    'ag-cell-cov': params => params.value?.status === 'COV',
                                    'ag-cell-off': params => params.value?.status === 'OFF',
                                    'ag-cell-space': params => params.value?.status === ' ',
                                    'ag-cell-before': params => params.value?.status === 'BEFORE',
                                    'ag-cell-after': params => params.value?.status === 'AFTER',
                                    'ag-cell-pv': params => params.value?.status === 'PV',
                                    'ag-cell-uv': params => params.value?.status === 'UV',
                                    'ag-cell-sl': params => params.value?.status === 'SL',
                                    'ag-cell-ul': params => params.value?.status === 'UL',
                                    'ag-cell-has-coverage': params => params.value?.has_coverage,
                                    'ag-cell-editable-day': isEditable,
                                },
                            });
                        }

                        const summaryValueGetter = (status) => (params) => {
                            const attendance = params.data.attendance || {};
                            return Object.values(attendance).filter(val => val.status === status).length;
                        };
                        const totalValueGetter = (params) => {
                            const attendance = params.data.attendance || {};
                            const validStates = ['present', 'M', 'N', 'leave', 'UV', 'W', 'absent'];
                            return Object.values(attendance).filter(val => validStates.includes(val.status)).length;
                        };

                        const summaryColumns = [
                            {
                                headerName: "غياب\nX",
                                valueGetter: summaryValueGetter('X'),
                                colId: 'summary_x',
                                width: 80,
                                cellClass: 'ag-cell-x',
                                cellStyle: { textAlign: 'center' }
                            },
                            {
                                headerName: "مرضي\nM",
                                valueGetter: summaryValueGetter('M'),
                                colId: 'summary_m',
                                width: 80,
                                cellClass: 'ag-cell-m',
                                cellStyle: { textAlign: 'center' }
                            },
                            {
                                headerName: "تغطية\nCOV",
                                valueGetter: summaryValueGetter('COV'),
                                colId: 'summary_cov',
                                width: 80,
                                cellClass: 'ag-cell-cov',
                                cellStyle: { textAlign: 'center' }
                            },
                            {
                                headerName: "أوف\nOFF",
                                valueGetter: summaryValueGetter('OFF'),
                                colId: 'summary_off',
                                width: 80,
                                cellClass: 'ag-cell-off',
                                cellStyle: { textAlign: 'center' }
                            },
                            {
                                headerName: "عمل\nD",
                                valueGetter: summaryValueGetter('D'),
                                colId: 'summary_d',
                                width: 80,
                                cellClass: 'ag-cell-d',
                                cellStyle: { textAlign: 'center' }
                            },
                            {
                                headerName: "عمل\nD8",
                                valueGetter: summaryValueGetter('D8'),
                                colId: 'summary_d8',
                                width: 80,
                                cellClass: 'ag-cell-d8',
                                cellStyle: { textAlign: 'center' }
                            },
                            {
                                headerName: "قبل\nBEFORE",
                                valueGetter: summaryValueGetter('BEFORE'),
                                colId: 'summary_before',
                                width: 80,
                                cellClass: 'ag-cell-before',
                                cellStyle: { textAlign: 'center' }
                            },
                            {
                                headerName: "بعد\nAFTER",
                                valueGetter: summaryValueGetter('AFTER'),
                                colId: 'summary_after',
                                width: 80,
                                cellClass: 'ag-cell-after',
                                cellStyle: { textAlign: 'center' }
                            },
                            {
                                headerName: "إجازة مدفوعة\nPV",
                                valueGetter: summaryValueGetter('PV'),
                                colId: 'summary_pv',
                                width: 80,
                                cellClass: 'ag-cell-pv',
                                cellStyle: { textAlign: 'center' }
                            },
                            {
                                headerName: "إجازة غير مدفوعة\nUV",
                                valueGetter: summaryValueGetter('UV'),
                                colId: 'summary_uv',
                                width: 80,
                                cellClass: 'ag-cell-uv',
                                cellStyle: { textAlign: 'center' }
                            },
                            {
                                headerName: "إجازة مرضية\nSL",
                                valueGetter: summaryValueGetter('SL'),
                                colId: 'summary_sl',
                                width: 80,
                                cellClass: 'ag-cell-sl',
                                cellStyle: { textAlign: 'center' }
                            },
                            {
                                headerName: "إجازة غير محددة\nUL",
                                valueGetter: summaryValueGetter('UL'),
                                colId: 'summary_ul',
                                width: 80,
                                cellClass: 'ag-cell-ul',
                                cellStyle: { textAlign: 'center' }
                            },
                            {
                                headerName: "إجمالي\nالتغطية",
                                valueGetter: (p) => Object.values(p.data.attendance || {}).filter(v => v.has_coverage)
                                    .length,
                                colId: 'summary_total_coverage',
                                width: 90,
                                cellClass: 'summary-header-total-coverage', // <-- كلاس جديد
                                cellStyle: {
                                    textAlign: 'center',
                                    fontWeight: 'bold'
                                }
                            },
                            {
                                headerName: "الإجمالي\nTotal",
                                valueGetter: totalValueGetter, // الدالة totalValueGetter تحسب الإجمالي كما هي
                                colId: 'summary_total',
                                width: 90,
                                cellStyle: {
                                    fontWeight: 'bold',
                                    textAlign: 'center'
                                }
                            }
                        ];


                        return [...staticColumns, ...dayColumns, ...summaryColumns];
                    }

                    // ✨ [إصدار احترافي] محرر الخلية المعاد كتابته بالكامل
                    class DayCellEditor {
                        init(params) {
                            this.params = params;
                            this.currentDateStr = params.currentDateStr;
                            this.initialValue = params.value || {
                                status: '',
                                has_coverage: false,
                                coverage_employee_id: null,
                                coverage_employee_name: '',
                                notes: ''
                            };
                            this.state = {
                                ...this.initialValue
                            }; // العمل على نسخة من الحالة لتسهيل التراجع

                            this.createUI();
                            this.attachEventListeners();
                            this.updateUI();

                            // جلب الموظفين وتخزينهم
                            getEmployees().then(employees => {
                                this.allEmployees = employees;
                            });
                        }

                        createUI() {
                            this.eGui = document.createElement('div');
                            this.eGui.className = 'ag-custom-cell-editor';
                            this.eGui.innerHTML = `
                            <div class="p-4 space-y-4 bg-white border border-gray-300 rounded-lg shadow-lg" style="width: 350px;">
                                <div>
                                    <label class="block mb-1 font-semibold text-gray-700">الحالة</label>
                                    <select data-ref="statusSelect" class="w-full p-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"></select>
                                </div>
                                <div data-ref="coverageSection" class="p-3 space-y-3 border border-gray-200 rounded-md">
                                    <div class="flex items-center justify-between">
                                        <label class="font-semibold text-gray-700">تغطية (COV)</label>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" data-ref="coverageToggle" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-primary-300 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                        </label>
                                    </div>
                                    <div data-ref="employeeSearchContainer" class="hidden">
                                        <label class="block mb-1 font-semibold text-gray-700">الموظف البديل</label>
                                        <div class="relative">
                                            <input type="text" data-ref="employeeSearchInput" placeholder="ابحث بالاسم أو الهوية..." class="w-full p-2 border border-gray-300 rounded-md">
                                            <div data-ref="searchResults" class="absolute z-10 hidden w-full mt-1 overflow-y-auto bg-white border border-gray-300 rounded-md max-h-40"></div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block mb-1 font-semibold text-gray-700">ملاحظات</label>
                                    <textarea data-ref="notesTextarea" rows="3" placeholder="أضف ملاحظاتك هنا..." class="w-full p-2 border border-gray-300 rounded-md"></textarea>
                                </div>
                                <div class="flex justify-end space-x-2">
                                    <button data-ref="cancelButton" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">إلغاء</button>
                                    <button data-ref="saveButton" class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700">حفظ</button>
                                </div>
                            </div>
                        `;

                            // ربط عناصر الواجهة بالـ class
                            this.refs = {};
                            this.eGui.querySelectorAll('[data-ref]').forEach(el => {
                                this.refs[el.dataset.ref] = el;
                            });

                            // ملء قائمة الحالات
                            const statusOptions = ['M', 'D', 'D8', 'X', 'A', 'M8', 'N8', 'N12', 'M12', 'N', 'COV',
                                'OFF', 'BEFORE', 'AFTER', 'PV', 'UV', 'SL', 'UL', ' ', ''
                            ];

                            statusOptions.forEach(value => {
                                const opt = document.createElement('option');
                                opt.value = value;
                                opt.text = (value || '—').toUpperCase();
                                this.refs.statusSelect.appendChild(opt);
                            });
                        }

                        attachEventListeners() {
                            this.refs.saveButton.addEventListener('click', () => this.saveChanges());
                            this.refs.cancelButton.addEventListener('click', () => this.params.stopEditing(true));
                            this.refs.statusSelect.addEventListener('change', (e) => this.handleStateChange('status', e
                                .target.value));
                            this.refs.coverageToggle.addEventListener('change', (e) => this.handleStateChange(
                                'has_coverage', e.target.checked));
                            this.refs.notesTextarea.addEventListener('input', (e) => this.state.notes = e.target.value);
                            this.refs.employeeSearchInput.addEventListener('input', () => this.filterEmployees());
                            this.refs.employeeSearchInput.addEventListener('focus', () => this.filterEmployees());
                            document.addEventListener('click', this.handleDocumentClick.bind(this), true);
                        }

                        handleStateChange(key, value) {
                            this.state[key] = value;
                            if (key === 'status') {
                                const needsCoverage = ['absent', 'leave', 'UV', 'M', 'N'].includes(value);
                                if (!needsCoverage) {
                                    this.state.has_coverage = false;
                                }
                            }
                            if (key === 'has_coverage' && !value) {
                                this.state.coverage_employee_id = null;
                                this.state.coverage_employee_name = '';
                            }
                            this.updateUI();
                        }

                        updateUI() {
                            this.refs.statusSelect.value = this.state.status;
                            this.refs.notesTextarea.value = this.state.notes;
                            this.refs.coverageToggle.checked = this.state.has_coverage;
                            this.refs.employeeSearchInput.value = this.state.coverage_employee_name || '';

                            // إظهار خيار التغطية لجميع الحالات ما عدا COV
                            const canHaveCoverage = this.state.status !== 'COV';
                            this.refs.coverageSection.style.display = canHaveCoverage ? 'block' : 'none';
                            this.refs.employeeSearchContainer.style.display = this.state.has_coverage ? 'block' :
                                'none';
                            'none';
                        }

                        filterEmployees() {
                            const searchTerm = this.refs.employeeSearchInput.value.toLowerCase();
                            if (!this.allEmployees) return;

                            const filtered = this.allEmployees.filter(emp =>
                                emp.name.toLowerCase().includes(searchTerm) || emp.national_id.includes(searchTerm)
                            );

                            this.refs.searchResults.innerHTML = '';
                            if (filtered.length > 0) {
                                this.refs.searchResults.classList.remove('hidden');
                                filtered.forEach(emp => {
                                    const item = document.createElement('div');
                                    item.className = 'p-2 hover:bg-primary-100 cursor-pointer';
                                    item.textContent = `${emp.name} (${emp.national_id})`;
                                    item.addEventListener('click', () => {
                                        this.handleStateChange('coverage_employee_id', emp.id);
                                        this.handleStateChange('coverage_employee_name', emp.name);
                                        this.refs.searchResults.classList.add('hidden');
                                    });
                                    this.refs.searchResults.appendChild(item);
                                });
                            } else {
                                this.refs.searchResults.classList.add('hidden');
                            }
                        }

                        handleDocumentClick(event) {
                            if (!this.eGui.contains(event.target)) {
                                this.refs.searchResults.classList.add('hidden');
                            }
                        }

                        saveChanges() {
                            const livewireComponent = Livewire.find(gridDiv.closest('[wire\\:id]').getAttribute(
                                'wire:id'));
                            const statusBox = document.getElementById('save-status');
                            if (statusBox) statusBox.textContent = '🟡 جاري الحفظ...';

                            // استخدام التاريخ المخزن لضمان عدم كونه null
                            livewireComponent.call(
                                    'saveAttendanceDetails',
                                    this.params.data.id,
                                    this.currentDateStr, // <-- استخدام المتغير المخزن والمضمون
                                    this.state
                                )
                                .then(() => {
                                    if (statusBox) statusBox.textContent = '✅ تم الحفظ بنجاح';
                                    this.params.stopEditing(false);
                                })
                                .catch((error) => {
                                    console.error('Save failed:', error);
                                    if (statusBox) statusBox.textContent = '❌ فشل الحفظ';
                                    this.params.stopEditing(true);
                                });
                        }
                        getValue() {
                            return this.state;
                        }

                        getGui() {
                            return this.eGui;
                        }

                        destroy() {
                            document.removeEventListener('click', this.handleDocumentClick.bind(this), true);
                        }
                    }

                    const gridOptions = {
                        // className: 'ag-theme-alpine',
                        rowModelType: 'clientSide',
                        theme: 'legacy',
                        headerHeight: 40,
                        getRowId: params => String(params.data.id),
                        defaultColDef: {
                            resizable: true,
                            tooltipValueGetter: (p) => {
                                if (p.colDef.field && p.colDef.field.startsWith('attendance.')) {
                                    const val = p.value;
                                    if (!val) return '';
                                    let tooltip = `الحالة: ${val.status || 'فارغ'}`;
                                    if (val.has_coverage) {
                                        tooltip += ' (مع تغطية)';
                                        if (val.coverage_employee_name) {
                                            tooltip += `\nالبديل: ${val.coverage_employee_name}`;
                                        }
                                    }
                                    if (val.notes) tooltip += `\nملاحظات: ${val.notes}`;
                                    return tooltip;
                                }
                                return p.value;
                            },
                        },
                        // تسجيل المكون الجديد
                        components: {
                            DayCellEditor
                        },
                        onGridReady: params => {
                            gridApi = params.api;
                            const livewireComponent = Livewire.find(gridDiv.closest('[wire\\:id]').getAttribute(
                                'wire:id'));
                            if (livewireComponent) {
                                const initialFilters = livewireComponent.get('filtersForGrid');
                                const newColumns = createColumnDefs(initialFilters.month, initialFilters.today);
                                gridApi.setGridOption('columnDefs', newColumns);

                                // 3. تطبيق حالة الأعمدة المحفوظة
                                applyColumnState();

                                // 4. ملء قائمة اختيار الأعمدة
                                populateColumnChooser();
                                const summaryColIds = ['summary_off', 'summary_present', 'summary_coverage',
                                    'summary_medical', 'summary_paid_leave', 'summary_unpaid_leave',
                                    'summary_absent', 'summary_total'
                                ];
                                gridApi.setColumnsVisible(summaryColIds, false);
                                fetchDataAndUpdateGrid(initialFilters);
                            }
                        },
                        // ✨ [تحسين] تحديث الصف بعد التعديل مباشرة
                        onCellValueChanged: (event) => {
                            // هذا يضمن أن الخلية تعكس دائمًا أحدث البيانات بعد الإغلاق
                            event.api.refreshCells({
                                rowNodes: [event.node]
                            });
                        }
                    };

                    function fetchDataAndUpdateGrid(filters) {
                        if (!gridApi) return;
                        gridApi.setGridOption("loading", true);
                        fetch('/api/attendance-data', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    month: filters.month,
                                    filters: filters
                                })
                            })
                            .then(r => r.ok ? r.json() : r.json().then(err => Promise.reject(err)))
                            .then(data => {
                                gridApi.setGridOption('rowData', data.rows);
                                gridApi.setGridOption("loading", false);
                            })
                            .catch((error) => {
                                console.error('Error fetching data:', error);
                                gridApi.setGridOption("loading", false);
                            });
                    }

                    agGrid.createGrid(gridDiv, gridOptions);

                    document.getElementById('quickFilterInput').addEventListener('input', (event) => {
                        if (gridApi) gridApi.setGridOption('quickFilterText', event.target.value);
                    });

                    let summaryVisible = false;
                    document.getElementById('toggleSummaryBtn').addEventListener('click', () => {
                        if (!gridApi) return;
                        summaryVisible = !summaryVisible;
                        const summaryColIds = ['summary_off', 'summary_present', 'summary_coverage',
                            'summary_medical', 'summary_paid_leave', 'summary_unpaid_leave', 'summary_absent',
                            'summary_total'
                        ];
                        gridApi.setColumnsVisible(summaryColIds, summaryVisible);
                        document.getElementById('toggleSummaryBtn').textContent = summaryVisible ? 'إخفاء الملخص' :
                            'إظهار الملخص';
                    });

                    document.addEventListener('livewire:init', () => {
                        const livewireComponent = Livewire.find(gridDiv.closest('[wire\\:id]').getAttribute(
                            'wire:id'));
                        if (livewireComponent) {
                            livewireComponent.on('filtersApplied', ({
                                filters
                            }) => {
                                if (gridApi) {
                                    document.getElementById('quickFilterInput').value = '';
                                    const newColumns = createColumnDefs(filters.month, filters.today);
                                    gridApi.setGridOption('columnDefs', newColumns);
                                    fetchDataAndUpdateGrid(filters);
                                }
                            });
                        }
                    });


                    const columnChooserBtn = document.getElementById('columnChooserBtn');
                    const columnChooserDropdown = document.getElementById('columnChooserDropdown');
                    columnChooserBtn.addEventListener('click', (event) => {
                        event.stopPropagation();
                        columnChooserDropdown.classList.toggle('hidden');
                    });
                    document.addEventListener('click', (event) => {
                        if (!columnChooserDropdown.contains(event.target) && !columnChooserBtn.contains(event
                                .target)) {
                            columnChooserDropdown.classList.add('hidden');
                        }
                    });

                    // ربط حدث تحديث الفلاتر
                    const livewireComponent = Livewire.find(gridDiv.closest('[wire\\:id]').getAttribute('wire:id'));
                    if (livewireComponent) {
                        livewireComponent.on('filtersApplied', ({
                            filters
                        }) => {
                            setTimeout(() => {
                                const newColumns = createColumnDefs(filters.month, filters.today);
                                gridApi.setGridOption('columnDefs', newColumns);
                                applyColumnState();
                                populateColumnChooser();
                                fetchDataAndUpdateGrid(filters);
                            }, 200);
                        });
                    }

                    const fullscreenBtn = document.getElementById('fullscreenBtn');
                    const openIcon = document.getElementById('fullscreen-icon-open');
                    const closeIcon = document.getElementById('fullscreen-icon-close');

                    // حاوية الصفحة هي العنصر الذي نريد عرضه في وضع ملء الشاشة
                    const fullscreenElement = document.querySelector('.fi-page');

                    function toggleFullscreen() {
                        // التحقق مما إذا كنا حاليًا في وضع ملء الشاشة
                        if (!document.fullscreenElement) {
                            // الدخول إلى وضع ملء الشاشة
                            if (fullscreenElement.requestFullscreen) {
                                fullscreenElement.requestFullscreen();
                            } else if (fullscreenElement.mozRequestFullScreen) { // Firefox
                                fullscreenElement.mozRequestFullScreen();
                            } else if (fullscreenElement.webkitRequestFullscreen) { // Chrome, Safari and Opera
                                fullscreenElement.webkitRequestFullscreen();
                            } else if (fullscreenElement.msRequestFullscreen) { // IE/Edge
                                fullscreenElement.msRequestFullscreen();
                            }
                        } else {
                            // الخروج من وضع ملء الشاشة
                            if (document.exitFullscreen) {
                                document.exitFullscreen();
                            } else if (document.mozCancelFullScreen) { // Firefox
                                document.mozCancelFullScreen();
                            } else if (document.webkitExitFullscreen) { // Chrome, Safari and Opera
                                document.webkitExitFullscreen();
                            } else if (document.msExitFullscreen) { // IE/Edge
                                document.msExitFullscreen();
                            }
                        }
                    }

                    function updateFullscreenUI() {
                        const isFullscreen = !!document.fullscreenElement;

                        // إضافة/إزالة الكلاس لتطبيق الأنماط الخاصة بنا
                        fullscreenElement.classList.toggle('fullscreen-container', isFullscreen);

                        // تبديل الأيقونة والنص
                        openIcon.classList.toggle('hidden', isFullscreen);
                        closeIcon.classList.toggle('hidden', !isFullscreen);
                        fullscreenBtn.setAttribute('title', isFullscreen ? 'الخروج من وضع ملء الشاشة' : 'عرض ملء الشاشة');

                        // إعلام AG Grid بتغير الحجم
                        if (gridApi) {
                            // تأخير بسيط لإعطاء المتصفح فرصة لتغيير الأبعاد
                            setTimeout(() => gridApi.checkGridSize(), 150);
                        }
                    }

                    fullscreenBtn.addEventListener('click', toggleFullscreen);

                    // الاستماع إلى تغييرات وضع ملء الشاشة (مهم جدًا)
                    // هذا يضمن تحديث الواجهة حتى لو خرج المستخدم من ملء الشاشة باستخدام زر 'Esc'
                    document.addEventListener('fullscreenchange', updateFullscreenUI);
                    document.addEventListener('webkitfullscreenchange', updateFullscreenUI);
                    document.addEventListener('mozfullscreenchange', updateFullscreenUI);
                    document.addEventListener('MSFullscreenChange', updateFullscreenUI);


                });
            </script>
        @endpush

        @push('styles')
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-alpine.css">
            <style>
                /* "BEFORE" = أخضر تماماً */
                .ag-cell-before {
                    background-color: #2E7D32 !important;
                    color: white !important;
                }

                /* "AFTER" = أحمر تماماً */
                .ag-cell-after {
                    background-color: #D32F2F !important;
                    color: white !important;
                }

                /* "PV" = أزرق فاتح */
                .ag-cell-pv {
                    background-color: #1E88E5 !important;
                    color: white !important;
                }

                /* "UV" = برتقالي غامق */
                .ag-cell-uv {
                    background-color: #F57C00 !important;
                    color: white !important;
                }

                /* "SL" = أخضر فاتح */
                .ag-cell-sl {
                    background-color: #4CAF50 !important;
                    color: white !important;
                }

                /* "UL" = بنفسجي */
                .ag-cell-ul {
                    background-color: #9C27B0 !important;
                    color: white !important;
                }

                /* "M" = رمادي داكن */
                .ag-cell-m {
                    background-color: #696969 !important;
                    color: white !important;
                }

                /* "D" = برتقالي */
                .ag-cell-d {
                    background-color: #FFA500 !important;
                    color: black !important;
                }

                /* "D8" = بني */
                .ag-cell-d8 {
                    background-color: #A52A2A !important;
                    color: white !important;
                }

                /* "X" = أحمر */
                .ag-cell-x {
                    background-color: #FF0000 !important;
                    color: white !important;
                }

                /* "A" = أزرق بنفسجي */
                .ag-cell-a {
                    background-color: #8A2BE2 !important;
                    color: white !important;
                }

                /* "M8" = أزرق فاتح */
                .ag-cell-m8 {
                    background-color: #ADD8E6 !important;
                    color: black !important;
                }

                /* "N8" = أخضر فاتح */
                .ag-cell-n8 {
                    background-color: #90EE90 !important;
                    color: black !important;
                }

                /* "N12" = أخضر متوسط */
                .ag-cell-n12 {
                    background-color: #3CB371 !important;
                    color: white !important;
                }

                /* "M12" = سماوي (أزرق مائي) */
                .ag-cell-m12 {
                    background-color: #00FFFF !important;
                    color: black !important;
                }

                /* "N" = رمادي متوسط */
                .ag-cell-n {
                    background-color: #808080 !important;
                    color: white !important;
                }

                /* "COV" = برتقالي غامق مائل للبني */
                .ag-cell-cov {
                    background-color: #D2691E !important;
                    color: white !important;
                }

                /* "OFF" = رمادي فاتح مائل للبني */
                .ag-cell-off {
                    background-color: #D3C5BC !important;
                    color: black !important;
                }

                /* " " (فراغ) = رمادي فاتح جدًا (بيج) */
                .ag-cell-space {
                    background-color: #F5F5DC !important;
                    color: black !important;
                }

                .ag-cell-present {
                    background-color: #2E7D32 !important;
                    color: white !important;
                }

                .ag-cell-absent {
                    background-color: #D32F2F !important;
                    color: white !important;
                }

                .ag-cell-m {
                    background-color: #D9D9D9 !important;
                    color: black !important;
                }

                .ag-cell-n {
                    background-color: #999999 !important;
                    color: white !important;
                }

                .ag-cell-leave {
                    background-color: #388E3C !important;
                    color: white !important;
                }

                .ag-cell-uv {
                    background-color: #F57C00 !important;
                    color: white !important;
                }

                .ag-cell-w {
                    background-color: #795548 !important;
                    color: white !important;
                }

                .ag-cell-off {
                    background-color: #FFC7CE !important;
                    color: black !important;
                }

                .ag-cell-before {
                    background-color: #C8E6C9 !important;
                    color: black !important;
                }

                .ag-cell-after {
                    background-color: #FFCDD2 !important;
                    color: black !important;
                }

                .ag-cell-has-coverage {
                    position: relative;
                    overflow: hidden;
                }

                .ag-cell-has-coverage::after {
                    content: '';
                    position: absolute;
                    top: 0;
                    right: 0;
                    width: 0;
                    height: 0;
                    border-style: solid;
                    border-width: 0 10px 10px 0;
                    border-color: transparent #F9A825 transparent transparent;
                    z-index: 1;
                }

                .ag-header-cell-label {
                    justify-content: center;
                }

                .ag-theme-alpine .ag-cell,
                .ag-theme-alpine .ag-header-cell {
                    border: none !important;
                }

                .ag-theme-alpine .ag-row {
                    border: none !important;
                }

                .ag-theme-alpine .ag-cell {
                    padding: 6px 8px !important;
                    font-size: 13px;
                    text-align: center;
                }

                .ag-row:hover {
                    filter: brightness(0.98);
                }

                .ag-row.ag-row-selected .ag-cell {
                    background-color: inherit !important;
                }

                .ag-theme-alpine .ag-cell {
                    white-space: pre-line !important;
                }

                .ag-cell[col-id="project_utilized"] {
                    font-size: 11px !important;
                }

                .ag-header-cell-label {
                    white-space: pre-line !important;
                    text-align: center;
                    line-height: 1.2;
                    padding: 4px 2px;
                }

                .summary-header-present {
                    background-color: #2E7D32 !important;
                    color: white !important;
                }

                .summary-header-absent {
                    background-color: #D32F2F !important;
                    color: white !important;
                }

                .summary-header-coverage {
                    background-color: #F9A825 !important;
                    color: black !important;
                }

                .summary-header-m {
                    background-color: #D9D9D9 !important;
                    color: black !important;
                }

                .summary-header-leave {
                    background-color: #388E3C !important;
                    color: white !important;
                }

                .summary-header-uv {
                    background-color: #F57C00 !important;
                    color: white !important;
                }

                .summary-header-off {
                    background-color: #FFC7CE !important;
                    color: black !important;
                }

                .fi-page.fullscreen-container {
                    width: 100%;
                    height: 100%;
                    background-color: white;
                    padding: 1rem;
                    display: flex;
                    flex-direction: column;
                    /* لا حاجة لـ position: fixed أو z-index لأن المتصفح يتعامل مع ذلك */
                }

                .fi-page.fullscreen-container #ag-grid-container {
                    flex-grow: 1;
                    height: auto !important;
                }
            </style>
        @endpush

        @section('meta')
            <meta name="csrf-token" content="{{ csrf_token() }}">
        @endsection
    </div>
</x-filament::page>
