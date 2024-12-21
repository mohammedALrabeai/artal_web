import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: '2c32b9a302dd88a3f847',  // استخدام المفتاح مباشرة
    cluster: 'eu',  // استخدام المنطقة الصحيحة
    forceTLS: true
});

window.Echo.channel('areas')
    .listen('areas-updated', (e) => {
        console.log('Received areas update:', e);  // إضافة سجل للتأكد من وصول الحدث
        // Update your UI with the new data
        if (typeof window.updateAreasData === 'function') {
            window.updateAreasData(e.areas);
        }
    });
