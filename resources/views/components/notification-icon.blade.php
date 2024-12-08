@php
    $notifications = Auth::user()->unreadNotifications ?? [];
@endphp

<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14V11a6 6 0 00-9.33-4.972M9 21h6M12 17v4" />
        </svg>
        @if ($notifications->count() > 0)
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">
                {{ $notifications->count() }}
            </span>
        @endif
    </button>

    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg z-50">
        <ul>
            @forelse ($notifications as $notification)
                <li class="px-4 py-2 text-sm border-b hover:bg-gray-100">
                    <p>{{ $notification->data['message'] }}</p>
                    <small class="text-gray-500">{{ $notification->created_at->diffForHumans() }}</small>
                </li>
            @empty
                <li class="px-4 py-2 text-sm text-gray-500">لا توجد إشعارات جديدة</li>
            @endforelse
        </ul>
    </div>
</div>
