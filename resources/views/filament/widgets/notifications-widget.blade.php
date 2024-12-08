<div>
    <h2 class="text-lg font-bold">الإشعارات</h2>
    @if (isset($notifications) && $notifications->isNotEmpty())
        <ul>
            @foreach ($notifications as $notification)
                <li class="py-2">
                    <p>{{ $notification->data['message'] }}</p>
                    <p><small>نوع الإشعار: {{ $notification->data['type'] }}</small></p>
                    <small class="text-gray-500">{{ $notification->created_at->diffForHumans() }}</small>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-gray-500">لا توجد إشعارات جديدة</p>
    @endif
</div>
