<div>
    @if(auth()->user()->unreadNotifications->count() > 0)
        <ul>
            @foreach(auth()->user()->unreadNotifications as $notification)
                <li>
                    <p><strong>{{ $notification->data['title'] ?? 'No Title' }}</strong></p>
                    <p>{{ $notification->data['message'] ?? 'No Message' }}</p>
                </li>
            @endforeach
        </ul>
    @else
        <p>لا توجد إشعارات حالياً.</p>
    @endif
</div>
