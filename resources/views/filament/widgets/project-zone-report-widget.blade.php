<x-filament::card>
    <h2 class="text-lg font-bold">{{ __('Project & Zone Report') }}</h2>
    <ul class="mt-4">
        <li>{{ __('Total Projects') }}: {{ $project_count }}</li>
        <li>{{ __('Total Zones') }}: {{ $zone_count }}</li>
    </ul>

    <h3 class="mt-6 text-lg font-bold">{{ __('Projects Details') }}</h3>
    <table class="table-auto w-full mt-4">
        <thead>
            <tr>
                <th>{{ __('Project Name') }}</th>
                <th>{{ __('Zone Count') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($project_data as $project)
                <tr>
                    <td>{{ $project->name }}</td>
                    <td>{{ $project->zones_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-filament::card>
