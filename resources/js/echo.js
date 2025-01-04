import Echo from 'laravel-echo';
 
import Pusher from 'pusher-js';
window.Pusher = Pusher;
 
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});



// import Echo from 'laravel-echo';
// import Pusher from 'pusher-js';

// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY, // Dynamically set key
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER, // Dynamically set cluster
//     forceTLS: import.meta.env.VITE_PUSHER_SCHEME === 'https', // Use scheme from env
// });

// // Use the `window.Echo` instance for subscribing to channels
// var channel = window.Echo.channel('try-channel'); // Fix here
// channel.listen('.try-event', function(data) {
//     alert(JSON.stringify(data));
// });
