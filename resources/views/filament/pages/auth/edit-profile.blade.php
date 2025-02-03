<x-filament::page>
    <form wire:submit.prevent="save" class="space-y-4">
        {{-- عرض النموذج الذي تم تعريفه في الكلاس --}}
        {{ $this->form }}

        <x-filament::button type="submit">
            حفظ التعديلات
        </x-filament::button>
    </form>
</x-filament::page>
