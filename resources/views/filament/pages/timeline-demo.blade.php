<x-filament::page>
  <form method="GET" action="{{ route('timeline.slots') }}" class="flex flex-wrap items-end gap-4 mb-6">
    <div>
        <label for="project_id" class="block mb-1 font-bold">المشروع</label>
        <select name="project_id" id="project_id" required class="w-48 px-2 py-1 border rounded">
            <option value="">اختر المشروع</option>
            @foreach(\App\Models\Project::orderBy('name')->get() as $project)
                <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                    {{ $project->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="start_date" class="block mb-1 font-bold">من تاريخ</label>
        <input type="date" name="start_date" id="start_date"
               max="{{ now()->toDateString() }}"
               value="{{ request('start_date', now()->subMonth()->toDateString()) }}"
               required
               class="w-40 px-2 py-1 border rounded">
    </div>
    <div>
        <label for="end_date" class="block mb-1 font-bold">إلى تاريخ</label>
        <input type="date" name="end_date" id="end_date"
               min="{{ request('start_date', now()->subMonth()->toDateString()) }}"
               max="{{ now()->toDateString() }}"
               value="{{ request('end_date', now()->toDateString()) }}"
               required
               class="w-40 px-2 py-1 border rounded">
    </div>
    <div>
        <button type="submit" class="px-6 py-2 font-bold text-white bg-blue-600 rounded">عرض الجدول</button>
    </div>
</form>

</x-filament::page>
