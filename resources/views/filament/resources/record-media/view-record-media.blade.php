<x-filament::page>
    <div class="space-y-6">
        <!-- عنوان المرفق -->
        <header class="border-b pb-4">
            <h1 class="text-3xl font-bold">{{ $record->title }}</h1>
        </header>

        <!-- تفاصيل أساسية للمرفق -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <strong>{{ __('Notes') }}:</strong>
                <p>{{ $record->notes ?: '-' }}</p>
            </div>

            <div>
                <strong>{{ __('Expiry Date') }}:</strong>
                <p>
                    @if($record->expiry_date)
                        {{ \Illuminate\Support\Carbon::parse($record->expiry_date)->format('Y-m-d') }}
                    @else
                        <span class="text-gray-500">{{ __('No Expiry Date') }}</span>
                    @endif
                </p>
            </div>
        </div>

        <!-- عرض تفاصيل الموديل المرتبط -->
        @if($relatedRecord)
            <section class="mt-8 border-t pt-6">
                <h2 class="text-2xl font-semibold mb-4">{{ __('Related Record Details') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <strong>{{ __('Model Type') }}:</strong>
                        <p>{{ class_basename($relatedRecord::class) }}</p>
                    </div>

                    <div>
                        <strong>{{ __('Record ID') }}:</strong>
                        <p>{{ $relatedRecord->id }}</p>
                    </div>

                    <!-- تفاصيل السجل التجاري إذا كان المرفق مرتبطًا به -->
                    @if($relatedRecord instanceof \App\Models\CommercialRecord)
                        <div>
                            <strong>{{ __('Record Number') }}:</strong>
                            <p>{{ $relatedRecord->record_number }}</p>
                        </div>

                        <div>
                            <strong>{{ __('Entity Name') }}:</strong>
                            <p>{{ $relatedRecord->entity_name }}</p>
                        </div>

                        <div>
                            <strong>{{ __('City') }}:</strong>
                            <p>{{ $relatedRecord->city }}</p>
                        </div>
                    @endif

                    <!-- تفاصيل العنوان الوطني إذا كان المرفق مرتبطًا به -->
                    @if($relatedRecord instanceof \App\Models\NationalAddress)
                        <div>
                            <strong>{{ __('Address') }}:</strong>
                            <p>{{ $relatedRecord->address }}</p>
                        </div>

                        <div>
                            <strong>{{ __('Postal Code') }}:</strong>
                            <p>{{ $relatedRecord->postal_code }}</p>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        <!-- عرض الملف/المرفق -->
        <section class="mt-8 border-t pt-6">
            <h2 class="text-2xl font-semibold mb-4">{{ __('Attachment Preview') }}</h2>
            @php
                $media = $record->getMedia('record_media')->first();
            @endphp

            @if($media)
                @if(in_array($media->mime_type, ['image/png', 'image/jpeg', 'image/gif']))
                    <!-- عرض الصورة -->
                    <img src="{{ $media->getTemporaryUrl(now()->addMinutes(30)) }}"
                         alt="{{ $record->title }}"
                         class="rounded shadow-lg w-full max-w-md mx-auto">
                @elseif($media->mime_type === 'application/pdf')
                    <!-- عرض ملف PDF داخل iframe -->
                    <iframe src="{{ $media->getTemporaryUrl(now()->addMinutes(30)) }}"
                            class="w-full h-96 border rounded"
                            frameborder="0">
                    </iframe>
                @elseif(in_array($media->mime_type, ['video/mp4', 'video/mpeg']))
                    <!-- عرض الفيديو -->
                    <video class="w-full rounded" controls>
                        <source src="{{ $media->getTemporaryUrl(now()->addMinutes(30)) }}"
                                type="{{ $media->mime_type }}">
                        {{ __('Your browser does not support the video tag.') }}
                    </video>
                @else
                    <!-- رابط لتحميل الملف أو فتحه -->
                    <div class="text-center">
                        <a href="{{ $media->getTemporaryUrl(now()->addMinutes(30)) }}"
                           target="_blank"
                           class="inline-block px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700">
                            {{ __('View / Download Attachment') }}
                        </a>
                    </div>
                @endif
            @else
                <p class="text-gray-500">{{ __('No File Available') }}</p>
            @endif
        </section>
    </div>
</x-filament::page>
