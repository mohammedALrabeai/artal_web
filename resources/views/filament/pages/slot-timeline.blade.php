<?php
// resources/views/filament/pages/slot-timeline.blade.php
?>
<x-filament::page>
    <style>
        /* تحسينات CSS للجدول مع نظام التولتيب المحسن */
        .table-cell-enhanced {
            position: relative;
            min-height: 45px;
            vertical-align: middle;
        }
        
        .employee-period-indicator {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 6px;
            border-radius: 0 0 4px 4px;
            z-index: 5;
        }
        
        .employee-period-odd .employee-period-indicator {
            background: linear-gradient(90deg, #1976d2, #42a5f5);
            border-top: 1px solid #1565c0;
        }
        
        .employee-period-even .employee-period-indicator {
            background: linear-gradient(90deg, #7b1fa2, #ba68c8);
            border-top: 1px solid #6a1b9a;
        }
        
        .employee-period-start .employee-period-indicator {
            border-left: 3px solid;
            border-radius: 0 0 0 6px;
        }
        
        .employee-period-odd.employee-period-start .employee-period-indicator {
            border-left-color: #0d47a1;
        }
        
        .employee-period-even.employee-period-start .employee-period-indicator {
            border-left-color: #4a148c;
        }
        
        .employee-period-end .employee-period-indicator {
            border-right: 3px solid;
            border-radius: 0 0 6px 0;
        }
        
        .employee-period-odd.employee-period-end .employee-period-indicator {
            border-right-color: #0d47a1;
        }
        
        .employee-period-even.employee-period-end .employee-period-indicator {
            border-right-color: #4a148c;
        }
        
        .employee-period-single .employee-period-indicator {
            border-left: 3px solid;
            border-right: 3px solid;
            border-radius: 0 0 6px 6px;
        }
        
        .employee-period-odd.employee-period-single .employee-period-indicator {
            border-left-color: #0d47a1;
            border-right-color: #0d47a1;
        }
        
        .employee-period-even.employee-period-single .employee-period-indicator {
            border-left-color: #4a148c;
            border-right-color: #4a148c;
        }
        
        /* مؤشرات الحضور - الكرات الصفراء */
        .attendance-indicators {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 4px;
            margin-top: 2px;
            margin-bottom: 4px;
        }
        
        .attendance-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ffd700;
            border: 2px solid #ffb300;
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .attendance-dot:hover {
            transform: scale(1.2);
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.5);
        }
        
        .attendance-dot.check-in {
            background: linear-gradient(135deg, #ffd700, #ffeb3b);
        }
        
        .attendance-dot.check-out {
            background: linear-gradient(135deg, #ff9800, #ffc107);
        }
        
        /* مؤشر التغطيات - النقطة */
        .coverage-indicators {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2px;
            margin-bottom: 8px;
        }
        
        .coverage-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4caf50, #66bb6a);
            border: 1px solid #388e3c;
            box-shadow: 0 1px 3px rgba(76, 175, 80, 0.4);
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .coverage-dot:hover {
            transform: scale(1.3);
            box-shadow: 0 3px 6px rgba(76, 175, 80, 0.6);
        }
        
        /* منطقة التولتيب الرئيسية للخلية */
        .cell-tooltip-area {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 8px; /* ترك مساحة للشريط السفلي */
            z-index: 1;
            pointer-events: none; /* السماح للعناصر الأخرى بالتفاعل */
        }
        
        .cell-tooltip-area.active {
            pointer-events: auto;
        }
        
        /* تولتيب أوقات الحضور - z-index عالي */
        .attendance-tooltip {
            position: fixed; /* استخدام fixed بدلاً من absolute */
            background: rgba(0, 0, 0, 0.95);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
            z-index: 9999; /* أعلى z-index */
            min-width: 80px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
        }
        
        .attendance-tooltip.check-in-tooltip {
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.95), rgba(56, 142, 60, 0.95));
            border-color: rgba(76, 175, 80, 0.3);
        }
        
        .attendance-tooltip.check-out-tooltip {
            background: linear-gradient(135deg, rgba(216, 67, 21, 0.95), rgba(245, 124, 0, 0.95));
            border-color: rgba(255, 152, 0, 0.3);
        }
        
        /* تولتيب التغطيات - z-index عالي جداً */
        .coverage-tooltip {
            position: fixed; /* استخدام fixed بدلاً من absolute */
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.95), rgba(56, 142, 60, 0.95));
            color: white;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            white-space: nowrap;
            z-index: 10000; /* أعلى من تولتيب الحضور */
            min-width: 120px;
            text-align: center;
            max-width: 200px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(76, 175, 80, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .coverage-item {
            margin-bottom: 6px;
            padding: 3px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .coverage-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        /* تولتيب الخلية الرئيسي - z-index متوسط */
        .cell-main-tooltip {
            position: fixed; /* استخدام fixed بدلاً من absolute */
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
            z-index: 9998; /* أقل من تولتيب الحضور والتغطيات */
            text-align: center;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(6px);
        }
        
        /* عرض اسم الموظف في بداية الفترة فقط */
        .employee-name-display {
            font-size: 10px;
            font-weight: bold;
            color: #333;
            text-align: center;
            margin-bottom: 4px;
            line-height: 1.2;
        }
        
        .table-cell-enhanced:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }
        
        /* حالة عدم وجود حضور */
        .no-attendance {
            color: #999;
            font-size: 10px;
            text-align: center;
        }
        
        /* تحسين للشاشات الصغيرة */
        @media (max-width: 768px) {
            .attendance-dot {
                width: 10px;
                height: 10px;
            }
            
            .coverage-dot {
                width: 6px;
                height: 6px;
            }
            
            .employee-name-display {
                font-size: 8px;
            }
            
            .employee-period-indicator {
                height: 4px;
            }
            
            .attendance-tooltip, .coverage-tooltip, .cell-main-tooltip {
                font-size: 9px;
                padding: 6px 8px;
            }
        }
        
        /* تحسينات للطباعة */
        @media print {
            .employee-period-indicator {
                border-width: 2px !important;
            }
            
            .attendance-dot, .coverage-dot {
                background: #000 !important;
                border-color: #000 !important;
            }
            
            .attendance-tooltip, .coverage-tooltip, .cell-main-tooltip {
                display: none !important;
            }
        }
    </style>

    <div class="space-y-6" x-data="tooltipManager()">
        <h2 class="text-xl font-bold">المخطط الزمني للشواغر</h2>

        <form method="get" class="flex flex-col gap-4 md:flex-row md:items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">المشروع</label>
                <select name="project_id" class="block w-full mt-1 rounded-md shadow-sm form-input">
                    <option value="">اختر مشروع</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">من</label>
                <input type="date" name="from" value="{{ request('from', $from) }}" class="block w-full mt-1 rounded-md shadow-sm form-input">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">إلى</label>
                <input type="date" name="to" value="{{ request('to', $to) }}" class="block w-full mt-1 rounded-md shadow-sm form-input">
            </div>

            <div class="pt-1">
                <button type="submit" class="px-4 py-2 mt-6 text-white transition rounded-md bg-primary-600 hover:bg-primary-700">
                    جلب البيانات
                </button>
            </div>
        </form>

        @if ($projectId && count($data))
            @foreach ($data as $zoneBlock)
                <div class="p-4 bg-white border rounded shadow">
                    <h3 class="mb-2 text-lg font-semibold text-blue-800">📍 {{ $zoneBlock['zone']->name }}</h3>

                    @foreach ($zoneBlock['shifts'] as $shiftBlock)
                        <h4 class="mt-4 mb-2 font-semibold text-gray-700 text-md">
                            🕒 {{ $shiftBlock['shift']->name }} ({{ $shiftBlock['shift']->start_time }} - {{ $shiftBlock['shift']->end_time }})
                        </h4>

                        <div class="overflow-x-auto">
                            <table class="min-w-full border table-auto">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-2 py-1 text-sm border">الشاغر</th>
                                        @foreach ($days as $day)
                                            <th class="px-2 py-1 text-sm text-center border">
                                                {{ \Carbon\Carbon::parse($day)->format('d') }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($shiftBlock['slots'] as $slot)
                                        <tr>
                                            <td class="px-2 py-1 text-sm font-bold text-center border">
                                                شاغر {{ $slot['slot_number'] }}
                                            </td>
                                            @php
                                                $lastEmployee = null;
                                                $currentPeriodStart = null;
                                                $periodIndex = 0;
                                                $processedCells = [];
                                                
                                                // معالجة البيانات لتحديد فترات الموظفين
                                                foreach ($slot['days'] as $index => $cell) {
                                                    $current = $cell['employee']['name'] ?? null;
                                                    
                                                    if ($current !== $lastEmployee) {
                                                        // إنهاء الفترة السابقة
                                                        if ($lastEmployee && $currentPeriodStart !== null) {
                                                            for ($i = $currentPeriodStart; $i < $index; $i++) {
                                                                $processedCells[$i]['period_type'] = $periodIndex % 2 === 0 ? 'even' : 'odd';
                                                                $processedCells[$i]['period_start'] = $i === $currentPeriodStart;
                                                                $processedCells[$i]['period_end'] = $i === $index - 1;
                                                                $processedCells[$i]['period_single'] = $currentPeriodStart === $index - 1;
                                                            }
                                                            $periodIndex++;
                                                        }
                                                        
                                                        // بدء فترة جديدة
                                                        if ($current) {
                                                            $currentPeriodStart = $index;
                                                        } else {
                                                            $currentPeriodStart = null;
                                                        }
                                                        
                                                        $lastEmployee = $current;
                                                    }
                                                    
                                                    $processedCells[$index] = $cell;
                                                    $processedCells[$index]['employee_name'] = $current;
                                                }
                                                
                                                // إنهاء الفترة الأخيرة
                                                if ($lastEmployee && $currentPeriodStart !== null) {
                                                    for ($i = $currentPeriodStart; $i < count($slot['days']); $i++) {
                                                        $processedCells[$i]['period_type'] = $periodIndex % 2 === 0 ? 'even' : 'odd';
                                                        $processedCells[$i]['period_start'] = $i === $currentPeriodStart;
                                                        $processedCells[$i]['period_end'] = $i === count($slot['days']) - 1;
                                                        $processedCells[$i]['period_single'] = $currentPeriodStart === count($slot['days']) - 1;
                                                    }
                                                }
                                            @endphp
                                            
                                            @foreach ($processedCells as $index => $cell)
                                                @php
                                                    $classes = ['relative', 'px-2', 'py-1', 'text-center', 'border', 'table-cell-enhanced'];
                                                    
                                                    // إضافة كلاسات الفترة
                                                    if (isset($cell['period_type']) && $cell['employee_name']) {
                                                        $classes[] = 'employee-period-' . $cell['period_type'];
                                                        
                                                        if ($cell['period_start']) {
                                                            $classes[] = 'employee-period-start';
                                                        }
                                                        if ($cell['period_end']) {
                                                            $classes[] = 'employee-period-end';
                                                        }
                                                        if ($cell['period_single']) {
                                                            $classes[] = 'employee-period-single';
                                                        }
                                                    }
                                                    
                                                    $cellId = 'cell_' . $zoneBlock['zone']->id . '_' . $shiftBlock['shift']->id . '_' . $slot['slot_number'] . '_' . $index;
                                                @endphp
                                                
                                                <td class="{{ implode(' ', $classes) }}" 
                                                    style="background-color: {{ $cell['is_working_day'] ? '#d1e7dd' : '#f8d7da' }}"
                                                    @mouseenter="showCellTooltip($event, '{{ $cell['employee_name'] ?? '' }}', '{{ \Carbon\Carbon::parse($cell['date'])->format('Y/m/d') }}')"
                                                    @mouseleave="hideAllTooltips()">
                                                    
                                                    @if ($cell['employee_name'])
                                                        <!-- عرض اسم الموظف في بداية كل فترة فقط -->
                                                        @if (isset($cell['period_start']) && $cell['period_start'])
                                                            <div class="employee-name-display">
                                                                {{ $cell['employee_name'] }}
                                                            </div>
                                                        @endif
                                                        
                                                        <!-- مؤشرات الحضور - الكرات الصفراء -->
                                                        @if ($cell['attendance'])
                                                            <div class="attendance-indicators">
                                                                @if ($cell['attendance']['check_in'])
                                                                    <div class="attendance-dot check-in" 
                                                                         @mouseenter="showAttendanceTooltip($event, 'حضور: {{ $cell['attendance']['check_in_time'] }}', 'check-in')"
                                                                         @mouseleave="hideAllTooltips()">
                                                                    </div>
                                                                @endif
                                                                
                                                                @if ($cell['attendance']['check_out'])
                                                                    <div class="attendance-dot check-out" 
                                                                         @mouseenter="showAttendanceTooltip($event, 'انصراف: {{ $cell['attendance']['check_out_time'] }}', 'check-out')"
                                                                         @mouseleave="hideAllTooltips()">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                        
                                                        <!-- مؤشرات التغطيات - النقاط الخضراء -->
                                                        @if (count($cell['coverages']) > 0)
                                                            <div class="coverage-indicators">
                                                                @foreach ($cell['coverages'] as $coverageIndex => $coverage)
                                                                    @php
                                                                        $coverageText = $coverage['zone_name'];
                                                                        if ($coverage['check_in_time']) {
                                                                            $coverageText .= ' - حضور: ' . $coverage['check_in_time'];
                                                                        }
                                                                        if ($coverage['check_out_time']) {
                                                                            $coverageText .= ' - انصراف: ' . $coverage['check_out_time'];
                                                                        }
                                                                    @endphp
                                                                    <div class="coverage-dot" 
                                                                         @mouseenter="showCoverageTooltip($event, '{{ $coverageText }}')"
                                                                         @mouseleave="hideAllTooltips()">
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                        
                                                        <!-- شريط الألوان أسفل الخلية -->
                                                        <div class="employee-period-indicator"></div>
                                                    @else
                                                        <div class="text-xs text-gray-400">—</div>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @elseif($projectId)
            <div class="mt-4 text-center text-gray-600">لا توجد بيانات متاحة للفترة المحددة.</div>
        @endif

        <!-- التولتيب المتحرك -->
        <div x-show="tooltip.show" 
             x-transition
             :class="tooltip.class"
             :style="`left: ${tooltip.x}px; top: ${tooltip.y}px;`"
             x-text="tooltip.text">
        </div>
    </div>

    <script>
        function tooltipManager() {
            return {
                tooltip: {
                    show: false,
                    text: '',
                    x: 0,
                    y: 0,
                    class: 'cell-main-tooltip'
                },

                showCellTooltip(event, employeeName, date) {
                    if (!employeeName) return;
                    
                    // منع ظهور التولتيب إذا كان المؤشر على عنصر فرعي
                    if (event.target !== event.currentTarget) return;
                    
                    this.hideAllTooltips();
                    
                    const rect = event.target.getBoundingClientRect();
                    this.tooltip = {
                        show: true,
                        text: `${employeeName} - ${date}`,
                        x: rect.left + (rect.width / 2) - 75, // توسيط التولتيب
                        y: rect.top - 10, // فوق الخلية
                        class: 'cell-main-tooltip'
                    };
                },

                showAttendanceTooltip(event, text, type) {
                    event.stopPropagation(); // منع انتشار الحدث للخلية الأب
                    this.hideAllTooltips();
                    
                    const rect = event.target.getBoundingClientRect();
                    this.tooltip = {
                        show: true,
                        text: text,
                        x: rect.left + (rect.width / 2) - 40,
                        y: rect.top - 10,
                        class: `attendance-tooltip ${type}-tooltip`
                    };
                },

                showCoverageTooltip(event, text) {
                    event.stopPropagation(); // منع انتشار الحدث للخلية الأب
                    this.hideAllTooltips();
                    
                    const rect = event.target.getBoundingClientRect();
                    this.tooltip = {
                        show: true,
                        text: text,
                        x: rect.left + (rect.width / 2) - 60,
                        y: rect.top - 10,
                        class: 'coverage-tooltip'
                    };
                },

                hideAllTooltips() {
                    this.tooltip.show = false;
                }
            }
        }
    </script>
</x-filament::page>

