<table>
    <thead>
        <tr>
            <th rowspan="2">Project</th>
            <th rowspan="2">Zone</th>
            @foreach ($dates as $dateData)
                <th colspan="4">
                    {{ $dateData['date'] }} ({{ $dateData['day'] }})
                </th>
            @endforeach
        </tr>
        <tr>
            @foreach ($dates as $dateData)
                <th>Present</th>
                <th>Absent</th>
                <th>Leave</th>
                <th>Coverage</th>
            @endforeach
        </tr>
    </thead>
    
    <tbody>
        @foreach ($projects as $project)
            <tr>
                <td rowspan="{{ $project->zones->count() + 2 }}">{{ $project->name }}</td> <!-- زيادة صف للإجماليات -->
            </tr>
            @foreach ($project->zones as $zone)
                <tr>
                    <td>{{ $zone->name }}</td>
                    @foreach ($dates as $dateData)
                        @php
                            $attendances = $zone->attendances->where('date', $dateData['date']);
                            $presentCount = $attendances->where('status', 'present')->count();
                            $absentCount = $attendances->where('status', 'absent')->count();
                            $leaveCount = $attendances->where('status', 'leave')->count();
                            $coverageCount = $attendances->where('status', 'coverage')->count();
                        @endphp
                        <td>{{ $presentCount }}</td>
                        <td>{{ $absentCount }}</td>
                        <td>{{ $leaveCount }}</td>
                        <td>{{ $coverageCount }}</td>
                    @endforeach
                </tr>
            @endforeach
    
            <!-- صف الإجماليات لكل موقع -->
            <tr>
                <td><strong>Total</strong></td>
                @foreach ($dates as $dateData)
                    @php
                        $zoneAttendances = $project->zones->flatMap(function ($zone) use ($dateData) {
                            return $zone->attendances->where('date', $dateData['date']);
                        });
                        $presentTotal = $zoneAttendances->where('status', 'present')->count();
                        $absentTotal = $zoneAttendances->where('status', 'absent')->count();
                        $leaveTotal = $zoneAttendances->where('status', 'leave')->count();
                        $coverageTotal = $zoneAttendances->where('status', 'coverage')->count();
                    @endphp
                    <td>{{ $presentTotal }}</td>
                    <td>{{ $absentTotal }}</td>
                    <td>{{ $leaveTotal }}</td>
                    <td>{{ $coverageTotal }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
    
    