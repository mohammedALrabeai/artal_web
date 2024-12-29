import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY, // Dynamically set key
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER, // Dynamically set cluster
    forceTLS: import.meta.env.VITE_PUSHER_SCHEME === 'https', // Use scheme from env
});

// Use the `window.Echo` instance for subscribing to channels
var channel = window.Echo.channel('try-channel'); // Fix here
channel.listen('.try-event', function(data) {
    alert(JSON.stringify(data));
});
