<?php

return [

    /*
     * The disk on which to store added files and derived images by default. Choose
     * one or more of the disks you've configured in config/filesystems.php.
     */
    'disk_name' => env('MEDIA_DISK', 's3'), // تم تغيير `public` إلى `s3`

    /*
     * The maximum file size of an item in bytes.
     * Adding a larger file will result in an exception.
     */
    'max_file_size' => 1024 * 1024 * 200, // 200MB

    /*
     * This queue connection will be used to generate derived and responsive images.
     * Leave empty to use the default queue connection.
     */
    'queue_connection_name' => env('QUEUE_CONNECTION', 'sync'),

    /*
     * This queue will be used to generate derived and responsive images.
     * Leave empty to use the default queue.
     */
    'queue_name' => env('MEDIA_QUEUE', ''),

    /*
     * By default all conversions will be performed on a queue.
     */
    'queue_conversions_by_default' => env('QUEUE_CONVERSIONS_BY_DEFAULT', true),

    /*
     * The fully qualified class name of the media model.
     */
    'media_model' => Spatie\MediaLibrary\MediaCollections\Models\Media::class,

    /*
     * The fully qualified class name of the media observer.
     */
    'media_observer' => Spatie\MediaLibrary\MediaCollections\Models\Observers\MediaObserver::class,

    /*
     * URL Generator to use S3.
     */
    // 'url_generator' => Spatie\MediaLibrary\Support\UrlGenerator\S3UrlGenerator::class,

    /*
     * Moves media on updating to keep path consistent. Enable it only with a custom
     * PathGenerator that uses, for example, the media UUID.
     */
    'moves_media_on_update' => true,

    /*
     * Whether to activate versioning when urls to files get generated.
     * When activated, this attaches a ?v=xx query string to the URL.
     */
    'version_urls' => true,

    /*
     * Extra Headers for S3 Upload
     */
    'remote' => [
        'extra_headers' => [
            'CacheControl' => 'max-age=31536000', // سنة واحدة
            'ACL' => 'public-read',
        ],
    ],

    /*
     * Responsive images settings.
     */
    'responsive_images' => [
        'width_calculator' => Spatie\MediaLibrary\ResponsiveImages\WidthCalculator\FileSizeOptimizedWidthCalculator::class,
        'use_tiny_placeholders' => true,
        'tiny_placeholder_generator' => Spatie\MediaLibrary\ResponsiveImages\TinyPlaceholderGenerator\Blurred::class,
    ],

    /*
     * Prefix for storing all media.
     */
    'prefix' => env('MEDIA_PREFIX', 'media'),

    /*
     * Force Lazy Loading
     */
    'force_lazy_loading' => env('FORCE_MEDIA_LIBRARY_LAZY_LOADING', true),
];
