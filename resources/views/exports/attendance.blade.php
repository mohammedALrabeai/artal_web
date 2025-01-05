<table>
    <thead>
        <tr>
            <th>"الاسم Name"</th>
            <th> رقم الهوية National ID</th>
            <th>Mobile Number</th>
            <th>رصيد الإجازات السنوية</th>
            <th>رصيد الإجازات المرضية</th>
            <th>موقع العمل</th>
            <th>الراتب</th>
            <th>Present Count</th>
            <th>Absent Count</th>
            <th>Coverage Count</th>
            <th>Leave Count</th>
            @php
                $currentDate = strtotime($startDate);
                $endDateTimestamp = strtotime($endDate);
            @endphp
            @while ($currentDate <= $endDateTimestamp)
                <th>{{ date('Y-m-d', $currentDate) }}</th>
                @php
                    $currentDate = strtotime('+1 day', $currentDate);
                @endphp
            @endwhile
            {{-- <th style="background-color: #FFCDD2;">غياب</th> <!-- New column header with red background --> --}}
        </tr>
    </thead>
    <tbody>
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
                @php
                    $currentDate = strtotime($startDate);
                @endphp
                @while ($currentDate <= $endDateTimestamp)
                    @php
                        $attendance = $employee->attendances->firstWhere('date', date('Y-m-d', $currentDate));
                    @endphp
                    <td>
                        {{ $attendance ? $attendance->status : 'absent' }}
                    </td>
                    @php
                        $currentDate = strtotime('+1 day', $currentDate);
                    @endphp
                @endwhile
            </tr>
        @endforeach
    </tbody>
    
</table>
