<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14h-3v-3a6 6 0 00-9.33-4.662M9 21h6a2 2 0 002-2H7a2 2 0 002 2z" />
        </svg>
        @if(isset($unreadCount) && $unreadCount > 0)
            <span class="absolute top-0 right-0 block w-2 h-2 transform bg-red-500 rounded-full ring-2 ring-white"></span>
        @endif
    </button>

    <div x-show="open" @click.away="open = false" class="absolute right-0 z-50 w-48 mt-2 bg-white rounded-md shadow-lg">
        <div class="p-2">
            @forelse($notifications as $notification)
                <div class="p-2 border-b">
                    {{ $notification->data['message'] ?? 'No message available' }}
                </div>
            @empty
                <p class="p-2 text-gray-500">No new notifications</p>
            @endforelse
        </div>
    </div>
</div>
