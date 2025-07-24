<x-filament::page>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- قسم الفلاتر يبقى كما هو --}}
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

    {{-- حاوية الجدول الجديد --}}
    <div id="ag-grid-container" class="mt-4" wire:ignore>
        <div id="myGrid" class="ag-theme-alpine" style="height: 600px; width: 100%;"></div>
    </div>

    {{-- تضمين مكتبة AG Grid --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
    <script>
        document.addEventListener('livewire:load', function ( ) {
            // --- إعدادات AG Grid ---
            let gridOptions;
            let gridApi;

            // 1. تعريف الأعمدة
            function createColumnDefs(monthStr) {
                const month = new Date(monthStr);
                const daysInMonth = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();
                
                // الأعمدة الثابتة
                const staticColumns = [
                    { 
                        headerName: 'الموظف', 
                        field: 'name', 
                        pinned: 'left', // تثبيت عمود الاسم على اليسار
                        width: 200,
                        cellStyle: {'font-weight': 'bold'}
                    },
                    { headerName: 'حضور', field: 'stats.present', width: 90, pinned: 'left' },
                    { headerName: 'غياب', field: 'stats.absent', width: 90, pinned: 'left' },
                ];

                // إنشاء أعمدة الأيام بشكل ديناميكي
                const dayColumns = [];
                for (let i = 1; i <= daysInMonth; i++) {
                    const day = String(i).padStart(2, '0');
                    dayColumns.push({
                        headerName: day,
                        field: `attendance.${day}`, // الوصول للبيانات مثل: attendance.01, attendance.02
                        width: 70,
                        cellStyle: params => { // تلوين الخلية بناءً على القيمة
                            const status = params.value;
                            const backgrounds = {
                                'present': '#2E7D32', 'absent': '#D32F2F', 'coverage': '#F9A825',
                                'M': '#D9D9D9', 'N': '#999999', 'leave': '#388E3C',
                                'UV': '#F57C00', 'W': '#795548', 'OFF': '#FFC7CE',
                            };
                            const colors = {
                                'present': 'white', 'absent': 'white', 'N': 'white',
                                'leave': 'white', 'UV': 'white', 'W': 'white',
                            };
                            return {
                                backgroundColor: backgrounds[status] || '#ECEFF1',
                                color: colors[status] || 'black',
                                textAlign: 'center'
                            };
                        }
                    });
                }
                return [...staticColumns, ...dayColumns];
            }

            // 2. مصدر البيانات (Datasource) الذي يتصل بالـ API
            const datasource = {
                getRows: (params) => {
                    console.log('AG-Grid requesting rows:', params.startRow, 'to', params.endRow);
                    
                    // جلب الفلاتر الحالية من Livewire
                    const livewireFilters = @this.getFilterData();

                    fetch('/api/attendance-data', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            // مهم: إضافة CSRF token للحماية
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            offset: params.startRow,
                            limit: params.endRow - params.startRow,
                            month: livewireFilters.month,
                            filters: livewireFilters
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.rows) {
                            params.successCallback(data.rows, data.total);
                        } else {
                            params.failCallback();
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        params.failCallback();
                    });
                }
            };

            // 3. إعدادات الجدول الرئيسية
            const month = @this.get('month');
            gridOptions = {
                columnDefs: createColumnDefs(month),
                rowModelType: 'infinite', // هذا هو مفتاح التمرير الافتراضي (Virtual Scrolling)
                datasource: datasource,
                paginationPageSize: 50, // حجم الدفعة التي يتم طلبها
                cacheBlockSize: 50,     // نفس حجم الدفعة
                rowHeight: 35,
                defaultColDef: {
                    resizable: true,
                    sortable: false, // يمكن تفعيله إذا أردت
                },
                onGridReady: (params) => {
                    gridApi = params.api;
                }
            };

            // 4. إنشاء الجدول
            const gridDiv = document.querySelector('#myGrid');
            new agGrid.Grid(gridDiv, gridOptions);


            // 5. الاستماع لتغييرات الفلاتر في Livewire
            Livewire.hook('message.processed', (message, component) => {
                // إذا كان اسم المكون هو صفحتنا
                if (component.name === 'filament.pages.manual-attendance-page') {
                    console.log('Filters changed, refreshing grid...');
                    
                    // تحديث أعمدة الأيام إذا تغير الشهر
                    const newMonth = @this.get('month');
                    const newColumns = createColumnDefs(newMonth);
                    gridApi.setColumnDefs(newColumns);

                    // إعادة تعيين الجدول ليطلب البيانات من جديد مع الفلاتر المحدثة
                    gridApi.setDatasource(datasource);
                }
            });
        });
    </script>
    @endpush

    {{-- إضافة CSS الخاص بـ AG Grid --}}
    @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-alpine.css">
    <style>
        /* تعديلات بسيطة لتحسين المظهر */
        .ag-header-cell-label {
            justify-content: center;
        }
    </style>
    @endpush

    {{-- إضافة CSRF Token Meta Tag --}}
    @section('meta' )
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endsection

</x-filament::page>
