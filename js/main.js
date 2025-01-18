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
