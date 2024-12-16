
{{ dd('Notification header is loading') }}

<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" class="relative">
        <x-heroicon-o-bell class="w-6 h-6 text-gray-600" />
        <span
            class="absolute top-0 right-0 inline-flex items-center justify-center w-4 h-4 text-xs font-bold text-white bg-red-500 rounded-full"
            x-show="{{ auth()->user()->unreadNotifications->count() > 0 }}"
        >
            {{ auth()->user()->unreadNotifications->count() }}
        </span>
    </button>
    <div
        x-show="open"
        @click.away="open = false"
        class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg"
    >
        <ul>
            @foreach(auth()->user()->unreadNotifications as $notification)
                <li class="px-4 py-2 text-sm text-gray-700 border-b">
                    {{ $notification->data['message'] }}
                    <a href="{{ $notification->data['url'] ?? '#' }}" class="text-blue-500 hover:underline">View</a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
