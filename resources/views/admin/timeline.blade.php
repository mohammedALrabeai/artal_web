<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>جدول زمني للشواغر</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/vis-timeline/7.7.0/vis-timeline-graph2d.min.css" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vis-timeline/7.7.0/vis-timeline-graph2d.min.js"></script>
    <style>
        body { font-family: Tahoma, Arial, sans-serif }
        .table-container { margin-top: 32px; }
        table { border-collapse: collapse; width: 100%; font-size: 15px; background: #fff }
        th, td { border: 1px solid #c8d3df; padding: 5px 8px; text-align: center }
        th { background: #f3f5f7; }
        .gray-cell { background: #f7fafc; color: #999 }
        .green-cell { background: #e6ffe6; color: #256029 }
        .employee-cell { font-weight: bold; color: #1072e7 }
    </style>
</head>
<body dir="rtl">

<div style="max-width:1200px; margin:auto; padding: 24px 0">
   <div style="font-size:20px;font-weight:bold;margin-bottom:20px;">جدول الشواغر الزمني</div>
    <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:end;">
        <div>
            <label for="project_id" style="font-weight:bold;">المشروع</label>
            <select name="project_id" id="project_id" required style="width:180px;padding:6px 8px;border:1px solid #bbb;border-radius:4px;">
                <option value="">اختر المشروع</option>
                @foreach(\App\Models\Project::orderBy('name')->get() as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="start_date" style="font-weight:bold;">من تاريخ</label>
            <input type="date" id="start_date"
                   value="{{ now()->startOfMonth()->toDateString() }}"
                   style="width:150px;padding:6px 8px;border:1px solid #bbb;border-radius:4px;">
        </div>
        <div>
            <label for="end_date" style="font-weight:bold;">إلى تاريخ</label>
            <input type="date" id="end_date"
                   value="{{ now()->toDateString() }}"
                   style="width:150px;padding:6px 8px;border:1px solid #bbb;border-radius:4px;">
        </div>
        <button id="fetch-btn" style="padding:10px 26px;font-size:16px;border:none;background:#1066c6;color:#fff;font-weight:bold;border-radius:5px;cursor:pointer;">جلب البيانات</button>
    </div>
    <div id="timeline-table" class="table-container"></div>
</div>

<script>
document.getElementById('fetch-btn').onclick = async function() {
    const projectId = document.getElementById('project_id').value;
    const startDate = document.getElementById('start_date').value;
    const endDate   = document.getElementById('end_date').value;
    if (!projectId) { alert('يرجى اختيار المشروع'); return; }

    const url = `/timeline-slots?project_id=${projectId}&start_date=${startDate}&end_date=${endDate}`;
    document.getElementById('timeline-table').innerHTML = '<div style="padding:30px;text-align:center;">... جارٍ التحميل ...</div>';

    try {
        const res = await fetch(url);
        if (!res.ok) throw new Error('فشل جلب البيانات');
        const data = await res.json();
        renderTimelineTable(data);
    } catch (e) {
        document.getElementById('timeline-table').innerHTML = '<div style="color:red;padding:30px;">تعذر جلب البيانات!</div>';
    }
};

// دالة لرسم الجدول الزمني
function renderTimelineTable(data) {
    const $area = document.getElementById('timeline-table');
    if (!data || !data.length) {
        $area.innerHTML = '<div style="padding:30px;color:#c00;text-align:center;">لا توجد بيانات!</div>';
        return;
    }

    // جلب الأيام (من أول شاغر - مرن)
    let days = [];
    if (data[0]?.shifts?.[0]?.slots?.[0]?.days) {
        days = data[0].shifts[0].slots[0].days.map(d => d.date);
    }

    let html = '<table><thead><tr>';
    html += '<th>الموقع</th><th>الوردية</th><th>الشاغر</th>';
    days.forEach(day => html += `<th>${day}</th>`);
    html += '</tr></thead><tbody>';

    data.forEach(zone => {
        let zoneRowSpan = 0;
        zone.shifts.forEach(shift => zoneRowSpan += shift.slots.length);
        let firstZone = true;
        zone.shifts.forEach(shift => {
            let firstShift = true;
            shift.slots.forEach(slot => {
                html += '<tr>';
                if (firstZone) {
                    html += `<td rowspan="${zoneRowSpan}" class="gray-cell">${zone.name}</td>`;
                    firstZone = false;
                }
                if (firstShift) {
                    html += `<td rowspan="${shift.slots.length}" class="gray-cell">${shift.name}</td>`;
                    firstShift = false;
                }
                html += `<td class="gray-cell">${slot.number}</td>`;
                slot.days.forEach(day => {
                    if (day.employee) {
                        html += `<td class="green-cell employee-cell" title="${day.employee}">${day.employee}</td>`;
                    } else {
                        html += `<td class="gray-cell">-</td>`;
                    }
                });
                html += '</tr>';
            });
        });
    });
    html += '</tbody></table>';
    $area.innerHTML = html;
}
</script>
</body>
</html>
