@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">المخطط الزمني للشواغر</h1>

    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label>المشروع</label>
                <select name="project_id" class="form-control">
                    <option value="">اختر مشروع</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ $projectId == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>من</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label>إلى</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-block">جلب البيانات</button>
            </div>
        </div>
    </form>

    @foreach ($data as $zoneBlock)
        <h4 class="mt-4">📍 {{ $zoneBlock['zone']->name }}</h4>

        @foreach ($zoneBlock['shifts'] as $shiftBlock)
            <h5 class="mt-3">🕒 الوردية: {{ $shiftBlock['shift']->name }} ({{ $shiftBlock['shift']->start_time }} - {{ $shiftBlock['shift']->end_time }})</h5>
            
            <div class="table-responsive">
                <table class="table text-center table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>رقم الشاغر</th>
                            @foreach ($days as $day)
                                <th>{{ \Carbon\Carbon::parse($day)->format('d') }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($shiftBlock['slots'] as $slot)
                            <tr>
                                <td>شاغر {{ $slot['slot_number'] }}</td>
                                @foreach ($slot['days'] as $cell)
                                    <td style="background-color: {{ $cell['is_working_day'] ? '#d4edda' : '#f8d7da' }}">
                                        {{-- مستقبلاً: الموظف / الحضور / التغطية --}}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endforeach
</div>
@endsection
