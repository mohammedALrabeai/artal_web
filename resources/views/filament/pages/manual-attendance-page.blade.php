<x-filament::page>
    {{-- العنصر الحاوي الوحيد الذي يلتف حول كل شيء لحل مشكلة Livewire --}}
    <div>

        {{-- 1. قسم الفلاتر --}}
        <x-filament::section>
            <x-slot name="header">خيارات الفلترة</x-slot>
            <form wire:submit.prevent>
                <div class="flex flex-wrap gap-4">
                    <div class="inline-block min-w-[220px] grow">{{ $this->form->getComponent('projectId') }}</div>
                    <div class="inline-block min-w-[220px] grow">{{ $this->form->getComponent('zoneId') }}</div>
                    <div class="inline-block min-w-[220px] grow">{{ $this->form->getComponent('shiftId') }}</div>
                    <div class="inline-block min-w-[220px] grow">{{ $this->form->getComponent('month') }}</div>
                </div>
            </form>
        </x-filament::section>
<div id="save-status" class="px-3 py-1 mt-2 text-sm text-gray-700 bg-gray-100 border border-gray-300 rounded">
    حالة التعديل: لا يوجد تعديل بعد.
</div>

        {{-- 2. حاوية جدول AG Grid --}}
        <div id="ag-grid-container" class="mt-4" wire:ignore>
            <div id="myGrid" class="ag-theme-alpine" style="height: 70vh; width: 100%;"></div>
        </div>

        {{-- 3. الأصول (Assets) والـ Meta Tags --}}
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
            <script>
                document.addEventListener('livewire:navigated', () => setTimeout(setupGrid, 50));

                async function setupGrid() {
                    let gridApi;
                    const gridDiv = document.querySelector('#myGrid');
                    if (!gridDiv) return;

                    const component = Livewire.find(gridDiv.closest('[wire\\:id]').getAttribute('wire:id'));

                    // محرر القائمة المنسدلة المخصص والمضمون
                    class SelectCellEditor {
                        init(params) {
                            this.eGui = document.createElement('select');
                            this.eGui.classList.add('w-full', 'h-full', 'border-none', 'p-0', 'bg-white');

                            params.values.forEach(value => {
                                const option = document.createElement('option');
                                option.value = value;
                                option.text = value.toUpperCase();
                                this.eGui.appendChild(option);
                            });

                            this.eGui.value = params.value;

                            this.eGui.addEventListener('change', () => params.stopEditing());
                        }
                        getGui() {
                            return this.eGui;
                        }
                        getValue() {
                            return this.eGui.value;
                        }
                        isPopup() {
                            return false;
                        }
                    }

                    function createColumnDefs(monthStr, editableDateStr) {
                        const year = parseInt(monthStr.substring(0, 4), 10);
                        const monthIndex = parseInt(monthStr.substring(5, 7), 10) - 1;
                        const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();

                        const staticColumns = [
                         {
        headerName: '#',
        valueGetter: 'node.rowIndex + 1',
        width: 60,
        pinned: 'left',
        cellStyle: { textAlign: 'center', fontWeight: 'bold' }
    },    
                        {
                                headerName: 'الموظف',
                                field: 'name',
                                pinned: 'left',
                                width: 200,
                                cellStyle: {
                                    'font-weight': 'bold'
                                }
                            },
                            {
                                headerName: 'حضور',
                                field: 'stats.present',
                                width: 90,
                                pinned: 'left'
                            },
                            {
                                headerName: 'غياب',
                                field: 'stats.absent',
                                width: 90,
                                pinned: 'left'
                            },
                        ];

                        const dayColumns = [];
                        for (let i = 1; i <= daysInMonth; i++) {
                            const day = String(i).padStart(2, '0');
                            const currentMonthPadded = String(monthIndex + 1).padStart(2, '0');
                            const currentDateStr = `${year}-${currentMonthPadded}-${day}`;
                            const isEditable = currentDateStr === editableDateStr;
                            //                             console.log('currentDateStr:', currentDateStr);
                            // console.log('editableDateStr:', editableDateStr);
                            // console.log('isEditable:', currentDateStr === editableDateStr);

                            dayColumns.push({
                                headerName: day,
                                field: `attendance.${day}`,
                                width: isEditable ? 100 : 70,
                                editable: isEditable,
                                cellEditor: SelectCellEditor,
                                cellEditorParams: {
                                    values: ['present', 'absent', 'leave', 'coverage', 'UV', 'W']
                                },
                                cellStyle: params => {
                                    const status = params.value;
                                    const backgrounds = {
                                        'present': '#2E7D32',
                                        'absent': '#D32F2F',
                                        'coverage': '#F9A825',
                                        'M': '#D9D9D9',
                                        'N': '#999999',
                                        'leave': '#388E3C',
                                        'UV': '#F57C00',
                                        'W': '#795548',
                                        'OFF': '#FFC7CE',
                                        'BEFORE': '#C8E6C9',
                                        'AFTER': '#FFCDD2'
                                    };
                                    const colors = {
                                        'present': 'white',
                                        'absent': 'white',
                                        'N': 'white',
                                        'leave': 'white',
                                        'UV': 'white',
                                        'W': 'white'
                                    };
                                    const style = {
                                        backgroundColor: backgrounds[status] || '#ECEFF1',
                                        color: colors[status] || 'black',
                                        textAlign: 'center'
                                    };
                                    if (isEditable) {
                                        style.cursor = 'pointer';
                                        style.border = '2px solid #0d6efd';
                                        style.fontWeight = 'bold';
                                    }
                                    return style;
                                },
                                onCellValueChanged: params => {
                                    const employeeId = params.data.id;
                                    const newValue = params.newValue;

                                    const statusBox = document.getElementById('save-status');
                                    if (statusBox) statusBox.textContent = '🟡 جاري الحفظ...';

                                    if (newValue) {
                                        component.saveStatus(employeeId, currentDateStr, newValue)
                                            .then(() => {
                                                if (statusBox) statusBox.textContent = '✅ تم الحفظ بنجاح';
                                            })
                                            .catch(() => {
                                                if (statusBox) statusBox.textContent = '❌ فشل الحفظ';
                                            });
                                    }
                                }

                            });
                        }
                        return [...staticColumns, ...dayColumns];
                    }

                    // ✅ الكود الكامل لمصدر البيانات
                    const datasource = {
                        getRows: (params) => {
                            component.getFilterData().then(livewireFilters => {
                                fetch('/api/attendance-data', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector(
                                                'meta[name="csrf-token"]').getAttribute('content')
                                        },
                                        body: JSON.stringify({
                                            offset: params.startRow,
                                            limit: params.endRow - params.startRow,
                                            month: livewireFilters.month,
                                            filters: livewireFilters
                                        })
                                    })
                                    .then(response => response.ok ? response.json() : response.json().then(
                                        err => Promise.reject(err)))
                                    .then(data => params.successCallback(data.rows, data.total))
                                    .catch(error => {
                                        console.error('خطأ في جلب البيانات:', error);
                                        params.failCallback();
                                    });
                            });
                        }
                    };

                    try {
                        const filterData = await component.getFilterData();

                        const gridOptions = {
                            columnDefs: createColumnDefs(filterData.month, filterData.today),
                            rowModelType: 'infinite',
                            datasource: datasource,
                            singleClickEdit: true,
                            stopEditingWhenCellsLoseFocus: true,
                            components: {
                                SelectCellEditor: SelectCellEditor
                            },
                            paginationPageSize: 50,
                            cacheBlockSize: 50,
                            rowHeight: 35,
                            defaultColDef: {
                                resizable: true
                            },
                            onGridReady: (params) => {
                                gridApi = params.api;
                            },
                        };
                        agGrid.createGrid(gridDiv, gridOptions);
                    } catch (e) {
                        console.error("خطأ فادح أثناء إعداد الجدول:", e);
                    }

                    // ✅ الكود الكامل للاستماع لتغييرات الفلاتر
                    Livewire.hook('message.processed', (message, componentHook) => {
                        if (componentHook.id === component.id && gridApi) {
                            component.getFilterData().then(filterData => {
                                const newColumns = createColumnDefs(filterData.month, filterData.today);
                                gridApi.setGridOption('columnDefs', newColumns);
                                gridApi.setGridOption('datasource', datasource);
                            });
                        }
                    });
                }
            </script>
        @endpush

        @push('styles')
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-alpine.css">
            <style>
                .ag-header-cell-label {
                    justify-content: center;
                }
            </style>
        @endpush

        @section('meta')
            <meta name="csrf-token" content="{{ csrf_token() }}">
        @endsection

    </div>


</x-filament::page>
