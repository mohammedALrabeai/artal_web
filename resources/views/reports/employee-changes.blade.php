{{-- resources/views/reports/employee-changes.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>تقرير المتغيرات ({{ $from }} إلى {{ $to }})</title>

  {{-- Tailwind CDN (خفيف وسريع لهذه الصفحة المستقلة) --}}
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: separate; border-spacing: 0; }
    thead th {
      background: #111827; color: #fff; font-weight: 600; font-size: .875rem;
      text-align: center; position: sticky; top: 0; z-index: 1;
    }
    th, td { padding: .75rem; border-bottom: 1px solid #e5e7eb; text-align: center; white-space: nowrap; }
    tbody tr:nth-child(even){ background: #f9fafb; }
    tbody tr:hover { background: #f3f4f6; }
    .card { background:#fff; border:1px solid rgba(0,0,0,.06); border-radius:.75rem; box-shadow:0 1px 2px rgba(0,0,0,.04); }
    .fi-input { display:block; width:100%; border-radius:.5rem; border:1px solid #d1d5db; background:#fff; color:#111827; padding:.5rem .75rem; }
    .fi-label { display:block; font-size:.875rem; color:#374151; margin-bottom:.25rem; }
    .btn     { display:inline-flex; align-items:center; gap:.5rem; padding:.5rem .9rem; border-radius:.65rem; font-weight:600; }
    .btn-primary { background:#4f46e5; color:#fff; }
    .btn-primary:hover { background:#4338ca; }
    .btn-ghost { border:1px solid #e5e7eb; background:#fff; }
    .btn-ghost:hover { background:#f9fafb; }
  </style>
</head>
<body class="text-gray-900 bg-gray-50">
  <div class="px-4 py-6 mx-auto space-y-6 max-w-7xl sm:px-6 lg:px-8">

    {{-- الهيدر --}}
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight">تقرير المتغيرات</h1>
        <p class="mt-1 text-sm text-gray-600">
          الفترة: <span class="font-medium">{{ $from }}</span> → <span class="font-medium">{{ $to }}</span>
        </p>
      </div>

      <form method="POST" action="{{ route('exports.employee-changes') }}" target="_blank" class="flex gap-2">
        @csrf
        <input type="hidden" name="from" value="{{ $from }}">
        <input type="hidden" name="to" value="{{ $to }}">
        <button type="submit" class="btn btn-primary">
          {{-- أيقونة تنزيل بسيطة --}}
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/>
          </svg>
          تصدير (Excel)
        </button>
      </form>
    </div>

    {{-- شريط الفلاتر --}}
    <div class="p-4 card">
      <form method="GET" action="{{ route('reports.employee-changes') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
          <label class="fi-label">من تاريخ</label>
          <input class="fi-input" type="date" name="from" value="{{ $from }}" required />
        </div>

        <div>
          <label class="fi-label">إلى تاريخ</label>
          <input class="fi-input" type="date" name="to" value="{{ $to }}" required />
        </div>

        <div>
          <label class="fi-label">بحث</label>
          <input class="fi-input" type="search" name="q" value="{{ $q }}"
                 placeholder="ابحث باسم الموظف / الهوية / المشروع / الموقع / الوردية / البديل..." />
        </div>

        <div>
          <label class="fi-label">عدد الصفوف</label>
          <select name="per_page" class="fi-input">
            @foreach ([25,50,100,200] as $n)
              <option value="{{ $n }}" @selected($n == $perPage)>{{ $n }}</option>
            @endforeach
          </select>
        </div>

        <div class="flex items-center gap-2 pt-1 col-span-full">
          <button type="submit" class="btn btn-primary">
            {{-- أيقونة بحث --}}
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
            </svg>
            تطبيق
          </button>

          <a href="{{ route('reports.employee-changes', ['from' => $from, 'to' => $to]) }}" class="btn btn-ghost">
            إعادة ضبط
          </a>
        </div>
      </form>
    </div>

    {{-- الجدول --}}
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>اسم الموظف</th>
              <th>رقم الهوية</th>
              <th>تاريخ التوظيف</th>
              <th>المشروع</th>
              <th>الموقع</th>
              <th>الوردية</th>
              <th>بديل عن</th>
              <th>تاريخ خروج البديل</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($paginator as $r)
              <tr>
                <td>{{ $r['employee_name'] }}</td>
                <td>{{ $r['national_id'] }}</td>
                <td>{{ $r['hired_at'] }}</td>
                <td>{{ $r['project_name'] }}</td>
                <td>{{ $r['zone_name'] }}</td>
                <td>{{ $r['shift_name'] }}</td>
                <td>{{ $r['replaced_by'] }}</td>
                <td>{{ $r['replaced_end_at'] }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="py-6 text-gray-500">لا توجد بيانات ضمن المعايير المحددة.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="p-4">
        {{ $paginator->withQueryString()->links() }}
      </div>
    </div>
  </div>
</body>
</html>
