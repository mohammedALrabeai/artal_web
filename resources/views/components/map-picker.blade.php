{{-- <div>
    <div id="map" style="height: 400px; width: 100%; margin-bottom: 1rem;"></div>
</div>

<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const map = L.map('map').setView([24.7136, 46.6753], 13); // الرياض كموقع افتراضي

        // طبقة البلاط للخرائط
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // العلامة (Marker) القابلة للسحب
        const marker = L.marker([24.7136, 46.6753], {
            draggable: true
        }).addTo(map);

        // تحديث الحقول عند النقر أو السحب
        function updateFields(lat, lng) {
            // const latField = document.querySelector('input[name="data.lat"]');
            // const longField = document.querySelector('input[name="data.long"]');

            // if (latField && longField) {
            //     latField.value = lat.toFixed(6);
            //     longField.value = lng.toFixed(6);

            //     // إشعار Laravel Filament بتحديث القيم
            //     latField.dispatchEvent(new Event('input'));
            //     longField.dispatchEvent(new Event('input'));
       
            // }
            document.getElementById('lat').value =lat;
            document.getElementById('longg').value =lng;
        }

        // عند النقر على الخريطة
        map.on('click', function (e) {
            const latlng = e.latlng;
            marker.setLatLng(latlng);
            updateFields(latlng.lat, latlng.lng);
        });

        // عند سحب العلامة
        marker.on('dragend', function (e) {
            const latlng = e.target.getLatLng();
            updateFields(latlng.lat, latlng.lng);
        });

        // إذا كانت هناك قيم محفوظة مسبقًا، تعيين الموقع الافتراضي
        const storedLat = parseFloat(document.querySelector('input[name="data.lat"]').value);
        const storedLong = parseFloat(document.querySelector('input[name="data.long"]').value);

        if (!isNaN(storedLat) && !isNaN(storedLong)) {
            map.setView([storedLat, storedLong], 13);
            marker.setLatLng([storedLat, storedLong]);
        }
    });
</script> --}}

@props([
    'lat' => '',
    'longg' => '',
])

<div wire:ignore>
    
    <!-- نستخدم hidden inputs لتمرير قيم الإحداثيات -->
    <input type="hidden" id="lat" name="lat" value="{{ $lat }}">
    <input type="hidden" id="longg" name="longg" value="{{ $longg }}">
    <div id="map" style="height: 400px; width: 100%;"></div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    function initMap() {
        let latInput = document.getElementById("lat");
        let lngInput = document.getElementById("longg");

        // قراءة القيم المخزنة من المدخلات، وإذا كانت فارغة نستخدم قيمة افتراضية
        let savedLat = parseFloat(latInput.value);
        let savedLng = parseFloat(lngInput.value);

        let initialPosition = {
            lat: !isNaN(savedLat) ? savedLat : 24.713612,
            lng: !isNaN(savedLng) ? savedLng : 46.675298
        };

        let map = new google.maps.Map(document.getElementById("map"), {
            zoom: 12,
            center: initialPosition
        });

        let marker = new google.maps.Marker({
            position: initialPosition,
            map: map,
            draggable: true
        });

        function updateLatLng(lat, lng) {
            // تحديث المدخلات مع القيمة الكاملة (دون استخدام toFixed)
            latInput.value = lat;
            lngInput.value = lng;

             // إطلاق حدث input لتحديث حالة Livewire (والـ AlpineJS إذا كان مستخدمًا)
             latInput.dispatchEvent(new Event('input'));
            lngInput.dispatchEvent(new Event('input'));


            // إرسال القيمة إلى Livewire لتحديث المتغيرات
            if (window.Livewire) {
                Livewire.emit('updateLatLng', lat, lng);
            }
        }

        // عند سحب المؤشر
        marker.addListener('dragend', function (event) {
            updateLatLng(event.latLng.lat(), event.latLng.lng());
        });

        // عند النقر على الخريطة
        map.addListener('click', function (event) {
            marker.setPosition(event.latLng);
            updateLatLng(event.latLng.lat(), event.latLng.lng());
        });
    }

    // تأكد من أن الدالة متاحة عالميًا
    window.initMap = initMap;
    let script = document.createElement("script");
    script.src = "https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initMap";
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);
});
</script>