<table>
    <thead>
        <tr>
            <th>Employee Name</th>
            <th>National ID</th>
            <th>Mobile Number</th>
            
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
            @endphp
            <tr>
                <td>{{ $employee->first_name }} {{ $employee->family_name }}</td>
                <td>{{ $employee->national_id }}</td>
                <td>{{ $employee->mobile_number }}</td>
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
