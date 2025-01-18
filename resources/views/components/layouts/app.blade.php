<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>

        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>

        @filamentStyles
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@latest/dist/tailwind.min.css" rel="stylesheet">
    </head>
    <body class="antialiased">
        {{ $slot }}

        @livewire('notifications')
        @livewireScripts
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
        @filamentScripts
        @livewireScriptConfig
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // مسار ملف الصوت
                const notificationSound = new Audio('/sounds/notification.mp3');
                notificationSound.volume = 0.5; // تقليل مستوى الصوت
                notificationSound.loop = false; // عدم التكرار
    
                // استهداف الأيقونة الرئيسية
                const notificationButton = document.querySelector('.fi-topbar-database-notifications-btn');
                if (!notificationButton) {
                    console.warn('Notification button not found. Make sure the selector is correct.');
                    return;
                }
    
                // استهداف العنصر الذي يعرض عدد الإشعارات
                const badgeContainer = notificationButton.querySelector('.fi-icon-btn-badge-ctn');
                if (!badgeContainer) {
                    console.warn('Badge container not found. Make sure the selector is correct.');
                    return;
                }
    
                let previousCount = parseInt(badgeContainer.textContent.trim() || '0');
    
                // مراقبة التغيرات باستخدام MutationObserver
                const observer = new MutationObserver(() => {
                    const currentCount = parseInt(badgeContainer.textContent.trim() || '0');
    
                    // تشغيل الصوت إذا تغير العدد وأصبح أكبر من السابق
                    if (currentCount > previousCount) {
                        notificationSound.play();
                    }
    
                    previousCount = currentCount; // تحديث العدد السابق
                });
    
                // بدء المراقبة
                observer.observe(badgeContainer, { childList: true, subtree: true });
            });
            </script>

    </body>
</html>
