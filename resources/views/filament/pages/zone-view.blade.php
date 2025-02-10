@extends('filament::layouts.app')


@section('content')
    <div class="filament-page">
        <div class="filament-header">
            <h1 class="text-xl font-bold">{{ __('Zone Details') }}</h1>
        </div>

        <div class="space-y-4 filament-main-content">
            <div class="p-4 bg-white rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold">{{ $zone->name }}</h2>
                <p><strong>{{ __('Project:') }}</strong> {{ $zone->project->name }}</p>
                <p><strong>{{ __('Pattern:') }}</strong> {{ $zone->pattern->name }}</p>
                <p><strong>{{ __('Start Date:') }}</strong> {{ $zone->start_date }}</p>
                <p><strong>{{ __('Latitude:') }}</strong> {{ $zone->lat }}</p>
                <p><strong>{{ __('Longitude:') }}</strong> {{ $zone->longg }}</p>
            </div>

            <div id="map" class="w-full rounded-lg shadow-sm h-96"></div>
        </div>
    </div>

    <script>
        function initMap() {
            var zoneLocation = { lat: parseFloat({{ $zone->lat }}), lng: parseFloat({{ $zone->longg }}) };
            var map = new google.maps.Map(document.getElementById('map'), {
                zoom: 15,
                center: zoneLocation
            });

            new google.maps.Marker({
                position: zoneLocation,
                map: map
            });
        }
    </script>

    <script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap"></script>
@endsection
