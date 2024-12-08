<div>
    <h3 class="text-lg font-bold">{{ __('Attachments') }}</h3>
    <table class="table-auto w-full border border-gray-300">
        <thead>
            <tr>
                <th class="border px-4 py-2">{{ __('Type') }}</th>
                <th class="border px-4 py-2">{{ __('Content') }}</th>
                <th class="border px-4 py-2">{{ __('Expiry Date') }}</th>
                <th class="border px-4 py-2">{{ __('Notes') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attachments as $attachment)
                <tr>
                    <td class="border px-4 py-2">{{ ucfirst($attachment->type) }}</td>
                    <td class="border px-4 py-2">{{ $attachment->content }}</td>
                    <td class="border px-4 py-2">{{ $attachment->expiry_date ?? __('No Expiry') }}</td>
                    <td class="border px-4 py-2">{{ $attachment->notes ?? __('No Notes') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="border px-4 py-2 text-center">{{ __('No Attachments Found') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
