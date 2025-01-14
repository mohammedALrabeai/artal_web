<table border="1" style="border-collapse: collapse; width: 100%;">
    <thead>
        <!-- الصف الأول: الأعمدة الأساسية والتواريخ -->
        <tr>
            <th rowspan="2">Name</th>
            <th rowspan="2">National ID</th>
            <th rowspan="2">Mobile Number</th>
            <th rowspan="2">رصيد الإجازات السنوية</th>
            <th rowspan="2">رصيد الإجازات المرضية</th>
            <th rowspan="2">موقع العمل</th>
            <th rowspan="2">الراتب</th>
            <th rowspan="2">Present Count</th>
            <th rowspan="2">Absent Count</th>
            <th rowspan="2">Coverage Count</th>
            <th rowspan="2">Leave Count</th>
            @php
                $currentDate = strtotime($startDate);
                $endDateTimestamp = strtotime($endDate);
            @endphp
            @while ($currentDate <= $endDateTimestamp)
                <th colspan="3">{{ date('Y-m-d', $currentDate) }}</th>
                @php
                    $currentDate = strtotime('+1 day', $currentDate);
                @endphp
            @endwhile
        </tr>
        <!-- الصف الثاني: تقسيم الحقول لكل تاريخ -->
        <tr>
            @php
                $currentDate = strtotime($startDate);
            @endphp
            @while ($currentDate <= $endDateTimestamp)
                <th>Coverage</th>
                <th>Work Hours</th>
                <th>Status</th>
                @php
                    $currentDate = strtotime('+1 day', $currentDate);
                @endphp
            @endwhile
        </tr>
    </thead>
    <tbody>
        <!-- البيانات الخاصة بكل موظف -->
        @foreach ($employees as $employee)
            @php
                $presentCount = $employee->attendances
                    ->where('status', 'present')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->count();

                $absentCount = $employee->attendances
                    ->where('status', 'absent')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->count();

                $coverageCount = $employee->attendances
                    ->where('status', 'coverage')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->count();

                $leaveCount = $employee->attendances
                    ->where('status', 'leave')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->count();

                $leaveBalance = $employee->leaveBalances->where('leave_type', 'annual')->first()->calculateAnnualLeaveBalance();
                $sickLeaveBalance = $employee->leaveBalances->where('leave_type', 'sick')->sum('balance');
                $currentZone = $employee->currentZone ? $employee->currentZone->name : 'غير محدد';
                $salary = $employee->basic_salary + $employee->living_allowance + $employee->other_allowances;
            @endphp
            <tr>
                <!-- بيانات الموظف -->
                <td>{{ $employee->first_name }} {{ $employee->family_name }}</td>
                <td>{{ $employee->national_id }}</td>
                <td>{{ $employee->mobile_number }}</td>
                <td>{{ $leaveBalance }}</td>
                <td>{{ $sickLeaveBalance }}</td>
                <td>{{ $currentZone }}</td>
                <td>{{ $salary }}</td>
                <td>{{ $presentCount }}</td>
                <td>{{ $absentCount }}</td>
                <td>{{ $coverageCount }}</td>
                <td>{{ $leaveCount }}</td>
                <!-- بيانات الحضور لكل تاريخ -->
                @php
                    $currentDate = strtotime($startDate);
                @endphp
                @while ($currentDate <= $endDateTimestamp)
                    @php
                        $attendance = $employee->attendances->firstWhere('date', date('Y-m-d', $currentDate));
                        $coverage = $attendance && $attendance->is_coverage ? 'COV' : '';
                        $workHours = $attendance ? $attendance->work_hours : 'N/A';
                        $status = $attendance ? $attendance->status : 'N/A';
                    @endphp
                    <td style="text-align: center; background-color: {{ $coverage ? '#BBDEFB' : '#FFFFFF' }};">
                        {{ $coverage }}
                    </td>
                    <td style="text-align: center;">{{ $workHours }}</td>
                    <td style="text-align: center;">{{ $status }}</td>
                    @php
                        $currentDate = strtotime('+1 day', $currentDate);
                    @endphp
                @endwhile
            </tr>
        @endforeach
    </tbody>
</table>
