{{-- resources/views/reports/employee-changes.blade.php --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>تقرير المتغيرات ({{ $from }} إلى {{ $to }})</title>

  {{-- Tailwind CDN --}}
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    :root {
      --th-bg: #111827;
      --th-fg: #ffffff;
      --row-alt: #f9fafb;
      --row-hover: #f3f4f6;
      --border: #e5e7eb;
      --card-border: rgba(0,0,0,.06);
    }

    /* استخدم كامل الشاشة */
    body { background:#f9fafb; color:#111827; }
    .page-wrap { width:100%; max-width:none; padding-inline: .75rem; }
    @media (min-width: 640px){ .page-wrap { padding-inline: 1rem; } }
    @media (min-width: 1024px){ .page-wrap { padding-inline: 1.5rem; } }

    .card {
      background:#fff; border:1px solid var(--card-border);
      border-radius:.75rem; box-shadow:0 1px 2px rgba(0,0,0,.04);
    }

    /* الجدول يملأ العرض بالكامل بدون تمرير أفقي */
    .table-wrap { overflow-x: visible; }
    table {
      width: 100%;
      border-collapse: separate; border-spacing: 0;
      table-layout: fixed; /* توزيع الأعمدة حسب colgroup */
      font-size: .9rem;
    }

    thead th {
      background: var(--th-bg); color: var(--th-fg);
      font-weight: 600; font-size: .85rem;
      text-align: center; position: sticky; top: 0; z-index: 1;
      padding: .6rem .5rem;
    }

    th, td {
      padding: .55rem .5rem;
      border-bottom: 1px solid var(--border);
      text-align: center;

      /* اسمح بالتفاف النص وكسره عند الحاجة */
      white-space: normal;
      word-break: break-word;
      overflow-wrap: anywhere;
    }

    tbody tr:nth-child(even){ background: var(--row-alt); }
    tbody tr:hover { background: var(--row-hover); }

    /* تحسين كثافة الصفوف على الشاشات الصغيرة */
    @media (max-width: 640px){
      table { font-size: .82rem; }
      th, td { padding: .45rem .4rem; }
    }

    .fi-input { display:block; width:100%; border-radius:.5rem; border:1px solid #d1d5db; background:#fff; color:#111827; padding:.5rem .75rem; }
    .fi-label { display:block; font-size:.875rem; color:#374151; margin-bottom:.25rem; }
    .btn     { display:inline-flex; align-items:center; gap:.5rem; padding:.5rem .9rem; border-radius:.65rem; font-weight:600; }
    .btn-primary { background:#4f46e5; color:#fff; }
    .btn-primary:hover { background:#4338ca; }
    .btn-ghost { border:1px solid #e5e7eb; background:#fff; }
    .btn-ghost:hover { background:#f9fafb; }
  </style>
</head>
<body>
  <div class="py-6 space-y-6 page-wrap">

    {{-- الهيدر --}}
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
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

        <div class="md:col-span-2">
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

        <div class="flex items-center gap-2 pt-1 md:col-span-3">
          <button type="submit" class="btn btn-primary">
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
          {{-- توزيع الأعمدة ليملأ 100% من العرض --}}
          <colgroup>
            <col style="width:15%">
            <col style="width:8%">
            <col style="width:10%">
            <col style="width:14%">
            <col style="width:14%">
            <col style="width:12%">
            <col style="width:15%">
            <col style="width:8%">
          </colgroup>

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
