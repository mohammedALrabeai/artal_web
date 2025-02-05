<div class="w-full flex flex-col items-center">
    <div class="mt-4 flex gap-4">
        <a href="{{ $fileUrl }}" target="_blank"
           class="inline-block px-4 py-2 bg-blue-600 text-gray rounded hover:bg-blue-700 transition">
            🔍 {{ __('Open Full Screen') }}
        </a>
        <a href="{{ $fileUrl }}" download
           class="inline-block px-4 py-2 bg-green-600 text-gray rounded hover:bg-green-700 transition">
            ⬇ {{ __('Download PDF') }}
        </a>
    </div>
    <iframe src="{{ $fileUrl }}" class="w-full h-[600px] border rounded-lg shadow-md"></iframe>
   
</div>
