{{-- resources/views/exports/enhanced_attendance.blade.php --}}
<table>
    <thead>
        <tr>
            <th style="background-color: #4CAF50; color: white; font-weight: bold; text-align: center; padding: 8px;">الرقم</th>
            <th style="background-color: #4CAF50; color: white; font-weight: bold; text-align: center; padding: 8px;">اسم الموظف</th>
            <th style="background-color: #4CAF50; color: white; font-weight: bold; text-align: center; padding: 8px;">رقم الهوية</th>
            <th style="background-color: #4CAF50; color: white; font-weight: bold; text-align: center; padding: 8px;">المنطقة الحالية</th>
            <th style="background-color: #4CAF50; color: white; font-weight: bold; text-align: center; padding: 8px;">تاريخ بداية العقد</th>
            <th style="background-color: #4CAF50; color: white; font-weight: bold; text-align: center; padding: 8px;">تاريخ نهاية العقد</th>
            <th style="background-color: #4CAF50; color: white; font-weight: bold; text-align: center; padding: 8px;">الراتب الأساسي</th>
            <th style="background-color: #4CAF50; color: white; font-weight: bold; text-align: center; padding: 8px;">بدل السكن</th>
            <th style="background-color: #4CAF50; color: white; font-weight: bold; text-align: center; padding: 8px;">البدلات الأخرى</th>
            <th style="background-color: #4CAF50; color: white; font-weight: bold; text-align: center; padding: 8px;">إجمالي الراتب</th>
            
            {{-- أعمدة التواريخ --}}
            @foreach ($dates as $date)
                <th style="background-color: #4CAF50; color: white; font-weight: bold; text-align: center; padding: 4px; font-size: 10px;">
                    {{ \Carbon\Carbon::parse($date)->format('d/m') }}
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($employeeData as $index => $data)
            @php
                $employee = $data['employee'];
                $attendanceData = $data['attendance_data'];
                $statistics = $data['statistics'];
            @endphp
            <tr>
                <td style="text-align: center; padding: 4px;">{{ $index + 1 }}</td>
                <td style="padding: 4px;">{{ $employee->name }}</td>
                <td style="text-align: center; padding: 4px;">{{ $employee->national_id }}</td>
                <td style="text-align: center; padding: 4px;">{{ $employee->currentZone->name ?? 'غير محدد' }}</td>
                <td style="text-align: center; padding: 4px;">{{ $employee->contract_start ? \Carbon\Carbon::parse($employee->contract_start)->format('Y/m/d') : '' }}</td>
                <td style="text-align: center; padding: 4px;">{{ $employee->contract_end ? \Carbon\Carbon::parse($employee->contract_end)->format('Y/m/d') : '' }}</td>
                <td style="text-align: center; padding: 4px;">{{ number_format($employee->basic_salary ?? 0, 0) }}</td>
                <td style="text-align: center; padding: 4px;">{{ number_format($employee->living_allowance ?? 0, 0) }}</td>
                <td style="text-align: center; padding: 4px;">{{ number_format($employee->other_allowances ?? 0, 0) }}</td>
                <td style="text-align: center; padding: 4px;">{{ number_format(($employee->basic_salary ?? 0) + ($employee->living_allowance ?? 0) + ($employee->other_allowances ?? 0), 0) }}</td>
                
                {{-- خلايا الحضور للتواريخ --}}
                @foreach ($dates as $date)
                    @php
                        $dayData = $attendanceData[$date] ?? null;
                        $status = $dayData['status'] ?? 'absent';
                        $assignmentStatus = $dayData['assignment_status'] ?? 'no_assignment';
                        $attendance = $dayData['attendance'] ?? null;
                        
                        // تحديد النص والخلفية بناءً على الحالة
                        $displayText = '';
                        $backgroundColor = '';
                        $textColor = '#000000';
                        $cellValue = '';
                        
                        // تحديد الرمز الأساسي
                        switch ($status) {
                            case 'present':
                                $displayText = 'ح';
                                $cellValue = 'ح';
                                break;
                            case 'absent':
                                $displayText = 'غ';
                                $cellValue = 'غ';
                                break;
                            case 'coverage':
                                $displayText = 'ت';
                                $cellValue = 'ت';
                                break;
                            case 'leave':
                                $displayText = 'إ';
                                $cellValue = 'إ';
                                break;
                            default:
                                $displayText = '-';
                                $cellValue = '-';
                                break;
                        }
                        
                        // تعديل الرمز والخلفية بناءً على حالة الإسناد
                        if ($assignmentStatus !== 'active') {
                            $displayText .= '*';
                            $cellValue .= '*';
                            
                            switch ($assignmentStatus) {
                                case 'before_assignment':
                                    $backgroundColor = '#FFF3E0'; // برتقالي فاتح
                                    $textColor = '#E65100';
                                    break;
                                case 'after_assignment':
                                    $backgroundColor = '#F3E5F5'; // بنفسجي فاتح
                                    $textColor = '#7B1FA2';
                                    break;
                                case 'no_assignment':
                                default:
                                    $backgroundColor = '#FFE5E5'; // أحمر فاتح جداً
                                    $textColor = '#D32F2F';
                                    break;
                            }
                        } else {
                            // ألوان الحالات العادية (داخل فترة الإسناد)
                            switch ($status) {
                                case 'present':
                                    $backgroundColor = '#C8E6C9'; // أخضر فاتح
                                    $textColor = '#2E7D32';
                                    break;
                                case 'absent':
                                    $backgroundColor = '#FFCDD2'; // أحمر فاتح
                                    $textColor = '#C62828';
                                    break;
                                case 'coverage':
                                    $backgroundColor = '#BBDEFB'; // أزرق فاتح
                                    $textColor = '#1565C0';
                                    break;
                                case 'leave':
                                    $backgroundColor = '#FFF9C4'; // أصفر فاتح
                                    $textColor = '#F57F17';
                                    break;
                                default:
                                    $backgroundColor = '#F5F5F5';
                                    $textColor = '#757575';
                                    break;
                            }
                        }
                    @endphp
                    
                    <td style="background-color: {{ $backgroundColor }}; color: {{ $textColor }}; text-align: center; font-weight: bold; padding: 2px; font-size: 11px;">
                        {{ $cellValue }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

{{-- شرح الرموز والألوان --}}
<div style="margin-top: 20px; padding: 15px; border: 2px solid #ddd; background-color: #f9f9f9; border-radius: 8px;">
    <h3 style="margin-bottom: 15px; color: #333; text-align: center;">دليل الرموز والألوان</h3>
    
    {{-- رموز الحضور --}}
    <div style="margin-bottom: 15px;">
        <h4 style="margin-bottom: 8px; color: #555;">رموز الحضور:</h4>
        <div style="display: flex; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="background-color: #C8E6C9; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #2E7D32; border: 1px solid #4CAF50;">ح</span>
                <span>حضور</span>
            </div>
            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="background-color: #FFCDD2; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #C62828; border: 1px solid #F44336;">غ</span>
                <span>غياب</span>
            </div>
            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="background-color: #BBDEFB; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #1565C0; border: 1px solid #2196F3;">ت</span>
                <span>تغطية</span>
            </div>
            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="background-color: #FFF9C4; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #F57F17; border: 1px solid #FFEB3B;">إ</span>
                <span>إجازة</span>
            </div>
        </div>
    </div>
    
    {{-- حالات الإسناد --}}
    <div style="margin-bottom: 15px;">
        <h4 style="margin-bottom: 8px; color: #555;">حالات الإسناد:</h4>
        <div style="display: flex; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="background-color: #FFE5E5; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #D32F2F; border: 1px solid #F44336;">ح*</span>
                <span>خارج فترة الإسناد</span>
            </div>
            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="background-color: #FFF3E0; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #E65100; border: 1px solid #FF9800;">ح*</span>
                <span>قبل بداية الإسناد</span>
            </div>
            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="background-color: #F3E5F5; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: #7B1FA2; border: 1px solid #9C27B0;">ح*</span>
                <span>بعد انتهاء الإسناد</span>
            </div>
        </div>
    </div>
    
    {{-- ملاحظات مهمة --}}
    <div style="background-color: #E3F2FD; padding: 10px; border-radius: 6px; border-left: 4px solid #2196F3;">
        <h4 style="margin-bottom: 8px; color: #1565C0;">ملاحظات مهمة:</h4>
        <ul style="margin: 0; padding-left: 20px; color: #333;">
            <li>الرمز (*) يشير إلى أن الحضور/الغياب حدث خارج فترة إسناد الموظف للمشروع</li>
            <li>الخلفية الملونة تساعد في التمييز السريع بين الحالات المختلفة</li>
            <li>يتم عرض الموظفين الذين لديهم حضور خلال الفترة المختارة فقط</li>
            <li>التواريخ معروضة بصيغة يوم/شهر لتوفير المساحة</li>
        </ul>
    </div>
</div>

{{-- معلومات التقرير --}}
<div style="margin-top: 15px; padding: 15px; border: 2px solid #4CAF50; background-color: #E8F5E8; border-radius: 8px;">
    <h4 style="margin-bottom: 10px; color: #2E7D32; text-align: center;">معلومات التقرير</h4>
    <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
        <div style="text-align: center;">
            <strong style="color: #2E7D32;">فترة التقرير:</strong><br>
            <span style="font-size: 14px;">من {{ \Carbon\Carbon::parse($startDate)->format('Y/m/d') }} إلى {{ \Carbon\Carbon::parse($endDate)->format('Y/m/d') }}</span>
        </div>
        <div style="text-align: center;">
            <strong style="color: #2E7D32;">عدد الموظفين:</strong><br>
            <span style="font-size: 14px;">{{ $totalEmployees }} موظف</span>
        </div>
        <div style="text-align: center;">
            <strong style="color: #2E7D32;">عدد الأيام:</strong><br>
            <span style="font-size: 14px;">{{ $dateRange }} يوم</span>
        </div>
        <div style="text-align: center;">
            <strong style="color: #2E7D32;">تاريخ الإنشاء:</strong><br>
            <span style="font-size: 14px;">{{ \Carbon\Carbon::now()->format('Y/m/d H:i') }}</span>
        </div>
    </div>
</div>

{{-- إحصائيات عامة --}}
<div style="margin-top: 15px; padding: 15px; border: 2px solid #FF9800; background-color: #FFF8E1; border-radius: 8px;">
    <h4 style="margin-bottom: 10px; color: #E65100; text-align: center;">ملخص الإحصائيات</h4>
    <p style="text-align: center; margin: 5px 0; color: #333; font-size: 12px;">
        يتم حساب الإحصائيات تلقائياً في أعمدة منفصلة في ملف Excel المُصدَّر
    </p>
    <div style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; margin-top: 10px;">
        <span style="background-color: #C8E6C9; padding: 3px 8px; border-radius: 3px; font-size: 12px; color: #2E7D32;">أيام العمل</span>
        <span style="background-color: #FFCDD2; padding: 3px 8px; border-radius: 3px; font-size: 12px; color: #C62828;">أيام الغياب</span>
        <span style="background-color: #BBDEFB; padding: 3px 8px; border-radius: 3px; font-size: 12px; color: #1565C0;">التغطيات</span>
        <span style="background-color: #FFF9C4; padding: 3px 8px; border-radius: 3px; font-size: 12px; color: #F57F17;">الإجازات</span>
        <span style="background-color: #FFE5E5; padding: 3px 8px; border-radius: 3px; font-size: 12px; color: #D32F2F;">خارج الإسناد</span>
    </div>
</div>

