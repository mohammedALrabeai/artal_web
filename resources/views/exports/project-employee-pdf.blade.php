<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير إسناد الموظفين</title>
    <style>
        @font-face {
            font-family: 'ArabicFont';
            src: url('{{ storage_path('fonts/Amiri-Regular.ttf') }}') format('truetype');
        }
    
        body {
        font-family: 'amiri', DejaVu Sans, sans-serif;
        direction: rtl;
        font-size: 10px;
    }
    
        table {
            width: 100%;
            border-collapse: collapse;
        }
    
        th, td {
            border: 1px solid #ccc;
            padding: 3px;
            text-align: center;
        }
    
        thead {
            background-color: #1F4E78;
            color: #fff;
        }
    
        .off { background-color: #FFC7CE; }
        .n { background-color: #999999; }
        .m { background-color: #D9D9D9; }
    </style>
    
    
</head>
<body>
    <h2 style="text-align: center;">تقرير نمط العمل - الموظفين</h2>

    <table>
        <thead>
            <tr>
                <th>الاسم</th>
                <th>رقم الهوية</th>
                <th>الوردية</th>
                @foreach($dates as $date)
                    <th>{{ $date->format('d M') }}</th>
                @endforeach
            </tr>
        </thead>
        
        <tbody>
            @foreach($records as $record)
                <tr>
                    <td>{{ $record->employee->name() }}</td>
                    <td>{{ $record->employee->national_id }}</td>
                    <td>{{ $record->shift->name ?? '-' }}</td>

                    @php
                        $pattern = $record->shift->zone->pattern ?? null;
                        $working = (int) ($pattern->working_days ?? 0);
                        $off = (int) ($pattern->off_days ?? 0);
                        $cycle = $working + $off;
                        $start = \Carbon\Carbon::parse($record->shift->start_date);

                        $today = now('Asia/Riyadh');
                    @endphp

                    @foreach($dates as $i => $target)
                        @php
                            $days = $start->diffInDays($target);
                            $inCycle = $days % $cycle;
                            $cycleNum = floor($days / $cycle) + 1;
                            $isWorkDay = $inCycle < $working;

                            $value = 'OFF';
                            if ($isWorkDay) {
                                $value = ($cycleNum % 2 === 1) ? 'M' : 'N';
                                switch ($record->shift->type) {
                                    case 'morning': $value = 'M'; break;
                                    case 'evening': $value = 'N'; break;
                                    case 'evening_morning': $value = ($cycleNum % 2 === 1) ? 'N' : 'M'; break;
                                }
                            }

                            $class = strtolower($value);
                        @endphp

                        <td class="{{ $class }}">{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
