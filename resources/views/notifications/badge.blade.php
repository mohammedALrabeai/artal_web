@if(isset($debug))
    <!-- للتصحيح فقط -->
    <span class="text-xs text-gray-500">Debug: {{ $count }}</span>
@endif

@if($count > 0)
    <span class="ml-auto rtl:ml-0 rtl:mr-auto min-h-4 min-w-4 inline-flex items-center justify-center rounded-full bg-danger-500 px-2 py-0.5 text-xs font-medium text-white">
        {{ $count }}
    </span>
@endif