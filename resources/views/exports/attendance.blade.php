<table>
    <thead>
        <tr>
            <th>Employee Name</th>
            <th>National ID</th>
            <th>Mobile Number</th>
            @foreach (range(strtotime($startDate), strtotime($endDate), 86400) as $date)
                <th>{{ date('Y-m-d', $date) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($employees as $employee)
            <tr>
                <td>{{ $employee->first_name }} {{ $employee->family_name }}</td>
                <td>{{ $employee->national_id }}</td>
                <td>{{ $employee->mobile_number }}</td>
                @foreach (range(strtotime($startDate), strtotime($endDate), 86400) as $date)
                    @php
                        $attendance = $employee->attendances->firstWhere('date', date('Y-m-d', $date));
                    @endphp
                    <td>
                        {{ $attendance ? ucfirst($attendance->status) : 'Absent' }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
