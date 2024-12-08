<div>
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
</script>
