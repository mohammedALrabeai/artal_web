@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
@endpush

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
@endpush

<x-filament::page>
        {{-- <x-filament::section>
        <div class="w-full h-[180vh] overflow-hidden rounded-xl">
            <iframe 
                src="https://docs.google.com/spreadsheets/d/e/2PACX-1vSz68Uxox3TBuiVQUCisYXqToHZckkC24dxiaXRDOM7_q-GH1VZFkAIbL5XYQevJMftj3Ph3dy5CzIh/pubhtml?gid=925806691&amp;single=true&amp;widget=true&amp;headers=false"
                class="w-full h-full border-0"
                frameborder="0" 
                allowfullscreen>
            </iframe>
        </div>
    </x-filament::section> --}}
    <x-filament::section>
        <x-slot name="heading">تقرير المتغيرات</x-slot>
        <x-slot name="description">تصدير الموظفين الذين تم توظيفهم خلال فترة معينة مع تحديد الموظف المستبدل إن
            وجد.</x-slot>

        <form method="POST" action="{{ route('exports.employee-changes') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="from" class="text-sm font-medium text-gray-700 dark:text-gray-300">من تاريخ</label>
                    <input type="date" id="from" name="from" required
                        class="w-full border-gray-300 rounded-lg shadow-sm filament-input dark:border-gray-700" />
                </div>

                <div class="space-y-2">
                    <label for="to" class="text-sm font-medium text-gray-700 dark:text-gray-300">إلى تاريخ</label>
                    <input type="date" id="to" name="to" required
                        class="w-full border-gray-300 rounded-lg shadow-sm filament-input dark:border-gray-700" />
                </div>
            </div>

            <div class="mt-6">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 text-white transition rounded-lg filament-button bg-primary-600 hover:bg-primary-700">
                    <x-heroicon-o-arrow-down-tray class="w-5 h-5 mr-2" />
                    تصدير تقرير المتغيرات
                </button>
            </div>
        </form>
    </x-filament::section>
    <x-filament::section>
        <x-slot name="heading">تقرير جدول التشغيل (نمط العمل)</x-slot>
        <x-slot name="description">تصدير نمط العمل لمدة 30 يومًا للمشاريع المختارة حسب التاريخ.</x-slot>

        <form method="POST" action="{{ route('exports.work-schedule') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div x-data x-init="const tom = new TomSelect($refs.projectSelect, {
                    plugins: ['remove_button'],
                    placeholder: 'اختر مشروعًا أو أكثر...',
                    onItemAdd(value) {
                        if (value === 'all') {
                            this.items
                                .filter(v => v !== 'all')
                                .forEach(v => this.removeItem(v));
                        } else if (this.items.includes('all')) {
                            this.removeItem('all');
                        }
                    }
                });" class="space-y-2">

                    <label for="projects" class="text-sm font-medium text-gray-700 dark:text-gray-300">اختر
                        المشاريع</label>

                    <select x-ref="projectSelect" id="projects" name="projects[]" multiple required
                        class="w-full border-gray-300 rounded-lg filament-input dark:border-gray-700">
                        <option value="all">جميع المشاريع</option>
                        @foreach (\App\Models\Project::where('status', true)->orderBy('name')->get() as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>



                <div class="space-y-2">
                    <label for="start_date" class="text-sm font-medium text-gray-700 dark:text-gray-300">تاريخ
                        البداية</label>
                    <input type="date" id="start_date" name="start_date" required
                        value="{{ now('Asia/Riyadh')->toDateString() }}"
                        class="w-full border-gray-300 rounded-lg shadow-sm filament-input dark:border-gray-700" />
                </div>
            </div>

            <div class="mt-6">
                <x-filament::button type="submit" icon="heroicon-o-calendar-days" color="primary">
                    تصدير جدول التشغيل
                </x-filament::button>

            </div>
        </form>
    </x-filament::section>

    {{-- <x-filament::section>
    <x-slot name="heading">تقرير جدول التشغيل وتحضيرات الرواتب</x-slot>
    <x-slot name="description">
        تصدير تقرير نمط العمل لكافة الموظفين والشواغر في جميع المشاريع النشطة، لمدة شهر كامل.
    </x-slot>

    <form method="POST" action="{{ route('exports.work-pattern-payroll') }}">
        @csrf

        <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray" color="primary">
            تصدير التقرير الشهري لجميع المشاريع
        </x-filament::button>
    </form>
</x-filament::section> --}}

    <x-filament::section>
        <x-slot name="heading">
            تقارير العمليات الشهرية
        </x-slot>

        <x-slot name="description">
            ▪︎ تقرير جدول التشغيل وتحضيرات الرواتب
            ▪︎ تصدير جميع الموظفين
        </x-slot>

        {{-- صف أفقي بعمودين يبدأ عمودياً على الشاشات الصغيرة --}}
        <div class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-2">
            {{-- الزر الأول --}}
            {{ $this->exportWorkPatternPayroll() }}

            {{-- الزر الثاني --}}
            {{ $this->exportAllEmployees() }}
        </div>
    </x-filament::section>



</x-filament::page>
