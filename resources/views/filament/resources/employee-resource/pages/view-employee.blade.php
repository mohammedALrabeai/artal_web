<x-filament::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- القسم الأول: المعلومات الشخصية --}}
            <x-filament::card>
                <h2 class="text-lg font-bold">{{ __('Personal Information') }}</h2>
                <dl class="mt-4 space-y-2">
                    <div>
                        <dt class="font-medium">{{ __('First Name') }}</dt>
                        <dd>{{ $record->first_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ __('Father Name') }}</dt>
                        <dd>{{ $record->father_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ __('Grandfather Name') }}</dt>
                        <dd>{{ $record->grandfather_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ __('Family Name') }}</dt>
                        <dd>{{ $record->family_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ __('Birth Date') }}</dt>
                        <dd>{{ $record->birth_date }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ __('National ID') }}</dt>
                        <dd>{{ $record->national_id }}</dd>
                    </div>
                </dl>
            </x-filament::card>

            {{-- القسم الثاني: المعلومات الوظيفية --}}
            <x-filament::card>
                <h2 class="text-lg font-bold">{{ __('Job Information') }}</h2>
                <dl class="mt-4 space-y-2">
                    <div>
                        <dt class="font-medium">{{ __('Contract Start') }}</dt>
                        <dd>{{ $record->contract_start }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ __('Contract End') }}</dt>
                        <dd>{{ $record->contract_end }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ __('Basic Salary') }}</dt>
                        <dd>{{ $record->basic_salary }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium">{{ __('Health Insurance Status') }}</dt>
                        <dd>{{ $record->health_insurance_status }}</dd>
                    </div>
                </dl>
            </x-filament::card>
        </div>

        {{-- القسم الثالث: وسائل التواصل --}}
        <x-filament::card>
            <h2 class="text-lg font-bold">{{ __('Contact Information') }}</h2>
            <dl class="mt-4 space-y-2">
                <div>
                    <dt class="font-medium">{{ __('Mobile Number') }}</dt>
                    <dd>{{ $record->mobile_number }}</dd>
                </div>
                <div>
                    <dt class="font-medium">{{ __('Email') }}</dt>
                    <dd>{{ $record->email }}</dd>
                </div>
            </dl>
        </x-filament::card>
    </div>
</x-filament::page>
