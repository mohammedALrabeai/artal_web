<x-filament-widgets::widget>
    <x-filament::section>
        <div>
            <h2>{{ __('Expiring Contracts') }}</h2>
        
            @if(isset($expiringContracts) && count($expiringContracts) > 0)
                <ul>
                    @foreach ($expiringContracts as $contract)
                        <li>
                            {{ $contract->first_name ?? 'No Name' }} {{ $contract->family_name ?? '' }} - 
                            {{ $contract->contract_end ?? 'No Contract End' }}
                        </li>
                    @endforeach
                </ul>
            @else
                <p>{{ __('No contracts expiring soon.') }}</p>
            @endif
        </div>
            {{-- <pre>{{ var_export($expiringContracts, true) }}</pre> --}}

        
    </x-filament::section>
</x-filament-widgets::widget>
