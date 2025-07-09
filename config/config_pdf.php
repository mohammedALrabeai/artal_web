<?php

// ملف إعدادات PDF - يوضع في config/pdf.php

return [
    /*
    |--------------------------------------------------------------------------
    | إعدادات عرض PDF
    |--------------------------------------------------------------------------
    |
    | هذه الإعدادات تتحكم في طريقة عرض ملفات PDF وحقول النص
    |
    */

    'viewer' => [
        // الحد الأقصى لحجم ملف PDF (بالميجابايت)
        'max_file_size' => 50,

        // أنواع الملفات المسموحة
        'allowed_mime_types' => [
            'application/pdf',
        ],

        // إعدادات العرض الافتراضية
        'default_scale' => 1.2,
        'min_scale' => 0.4,
        'max_scale' => 3.0,

        // إعدادات الصفحة
        'default_page' => 1,
        'pages_per_load' => 1,

        // إعدادات التخزين المؤقت
        'cache_enabled' => true,
        'cache_duration' => 3600, // ساعة واحدة
    ],

    'text_fields' => [
        // أنواع الحقول المدعومة
        'supported_types' => [
            'text' => 'نص',
            'textarea' => 'نص متعدد الأسطر',
            'number' => 'رقم',
            'date' => 'تاريخ',
            'email' => 'بريد إلكتروني',
            'phone' => 'رقم هاتف',
            'url' => 'رابط',
        ],

        // الخطوط المدعومة
        'supported_fonts' => [
            'Arial' => 'Arial',
            'Times New Roman' => 'Times New Roman',
            'Helvetica' => 'Helvetica',
            'Courier' => 'Courier',
            'Georgia' => 'Georgia',
            'Verdana' => 'Verdana',
        ],

        // أحجام الخط المدعومة
        'font_sizes' => [
            8, 9, 10, 11, 12, 14, 16, 18, 20, 24, 28, 32, 36, 48, 72
        ],

        // الألوان الافتراضية
        'default_colors' => [
            '#000000', // أسود
            '#333333', // رمادي غامق
            '#666666', // رمادي متوسط
            '#999999', // رمادي فاتح
            '#007bff', // أزرق
            '#28a745', // أخضر
            '#dc3545', // أحمر
            '#ffc107', // أصفر
        ],

        // الحد الأدنى والأقصى للأحجام (كنسبة مئوية)
        'min_width' => 1,
        'max_width' => 100,
        'min_height' => 1,
        'max_height' => 100,

        // الحد الأدنى والأقصى للمواقع (كنسبة مئوية)
        'min_position' => 0,
        'max_position' => 100,
    ],

    'validation' => [
        // قواعد التحقق من البيانات
        'rules' => [
            'text' => 'string|max:255',
            'textarea' => 'string|max:1000',
            'number' => 'numeric',
            'date' => 'date',
            'email' => 'email|max:255',
            'phone' => 'string|max:20',
            'url' => 'url|max:255',
        ],

        // رسائل الخطأ المخصصة
        'messages' => [
            'text.max' => 'النص طويل جداً (الحد الأقصى 255 حرف)',
            'textarea.max' => 'النص طويل جداً (الحد الأقصى 1000 حرف)',
            'number.numeric' => 'يجب أن يكون الحقل رقماً',
            'date.date' => 'يجب أن يكون الحقل تاريخاً صحيحاً',
            'email.email' => 'يجب أن يكون الحقل بريداً إلكترونياً صحيحاً',
            'url.url' => 'يجب أن يكون الحقل رابطاً صحيحاً',
        ],
    ],

    'security' => [
        // تشفير البيانات الحساسة
        'encrypt_data' => true,

        // التحقق من الصلاحيات
        'check_permissions' => true,

        // السماح بالتحميل للضيوف
        'allow_guest_access' => false,

        // الحد الأقصى لمحاولات الحفظ في الدقيقة
        'save_rate_limit' => 60,

        // مدة انتهاء صلاحية الجلسة (بالدقائق)
        'session_timeout' => 120,
    ],

    'performance' => [
        // تفعيل ضغط البيانات
        'enable_compression' => true,

        // استخدام CDN للملفات الثابتة
        'use_cdn' => false,
        'cdn_url' => '',

        // تحسين الصور
        'optimize_images' => true,
        'image_quality' => 85,

        // التحميل التدريجي
        'lazy_loading' => true,

        // حجم ذاكرة التخزين المؤقت (بالميجابايت)
        'cache_size_limit' => 100,
    ],

    'ui' => [
        // اللغة الافتراضية
        'default_language' => 'ar',

        // الاتجاه الافتراضي
        'default_direction' => 'rtl',

        // السمة الافتراضية
        'default_theme' => 'light',

        // إظهار شريط الأدوات
        'show_toolbar' => true,

        // إظهار معلومات الصفحة
        'show_page_info' => true,

        // إظهار أزرار التكبير
        'show_zoom_controls' => true,

        // إظهار زر الطباعة
        'show_print_button' => true,

        // إظهار زر الحفظ
        'show_save_button' => true,

        // تفعيل وضع التحرير
        'enable_edit_mode' => true,

        // إظهار التلميحات
        'show_tooltips' => true,

        // تفعيل الاختصارات
        'enable_shortcuts' => true,
    ],

    'print' => [
        // جودة الطباعة
        'print_quality' => 'high',

        // حجم الورق الافتراضي
        'default_paper_size' => 'A4',

        // اتجاه الطباعة الافتراضي
        'default_orientation' => 'portrait',

        // الهوامش (بالمليمتر)
        'margins' => [
            'top' => 10,
            'right' => 10,
            'bottom' => 10,
            'left' => 10,
        ],

        // تضمين الخلفية
        'include_background' => true,

        // تضمين الصور
        'include_images' => true,

        // ضغط PDF المطبوع
        'compress_output' => true,
    ],

    'export' => [
        // تنسيقات التصدير المدعومة
        'supported_formats' => [
            'pdf' => 'PDF',
            'png' => 'صورة PNG',
            'jpg' => 'صورة JPG',
            'json' => 'بيانات JSON',
        ],

        // جودة الصور المصدرة
        'image_quality' => 90,

        // دقة الصور (DPI)
        'image_resolution' => 300,

        // تضمين البيانات الوصفية
        'include_metadata' => true,
    ],

    'backup' => [
        // تفعيل النسخ الاحتياطي التلقائي
        'auto_backup' => true,

        // تكرار النسخ الاحتياطي (بالساعات)
        'backup_frequency' => 24,

        // عدد النسخ الاحتياطية المحفوظة
        'backup_retention' => 30,

        // مسار النسخ الاحتياطية
        'backup_path' => 'backups/pdf-data',

        // ضغط النسخ الاحتياطية
        'compress_backups' => true,
    ],

    'logging' => [
        // تفعيل تسجيل الأنشطة
        'enable_logging' => true,

        // مستوى التسجيل
        'log_level' => 'info',

        // تسجيل عمليات العرض
        'log_views' => true,

        // تسجيل عمليات الحفظ
        'log_saves' => true,

        // تسجيل عمليات الطباعة
        'log_prints' => true,

        // تسجيل الأخطاء
        'log_errors' => true,

        // مدة الاحتفاظ بالسجلات (بالأيام)
        'log_retention' => 90,
    ],
];

