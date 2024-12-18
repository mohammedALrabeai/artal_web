<x-filament::page>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <div class="mb-4">
        <h1 class="text-2xl font-bold">مسارات الموظف: {{ $employeeName }}</h1>
    </div>

    <div>
        <label for="date">اختر التاريخ:</label>
        <input type="date" id="date" value="{{ $currentDate }}" class="p-1 rounded border" />
    </div>

    <div id="map" style="height: 500px; width: 100%; margin-top: 20px;"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
    const map = L.map('map').setView([26.9924219, 49.6538872], 13);

    // إضافة طبقة OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    const employeeId = "{{ $employeeId }}";
    const dateInput = document.getElementById('date');
    let zoneCircle; // دائرة المنطقة (zone)

    function fetchRoute() {
        const selectedDate = dateInput.value;

        fetch(`/filament/employee-route/${employeeId}?date=${selectedDate}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                // تنظيف الخريطة
                map.eachLayer(layer => {
                    if (layer instanceof L.Polyline || layer instanceof L.Marker) map.removeLayer(layer);
                });

                if (zoneCircle) {
                    map.removeLayer(zoneCircle);
                }

                if (data.route.length > 0) {
                    // عرض المسار
                    const latlngs = data.route.map(coord => [coord.latitude, coord.longitude]);
                    const polyline = L.polyline(latlngs, { color: 'blue' }).addTo(map);

                    // عرض النقاط مع التوقيت
                    data.route.forEach(coord => {
                        L.marker([coord.latitude, coord.longitude])
                            .bindPopup(`Time: ${coord.timestamp}`)
                            .addTo(map);
                    });

                    map.fitBounds(polyline.getBounds());
                } else {
                    alert('لا توجد بيانات متوفرة لهذا اليوم.');
                }

                // عرض المنطقة (zone) إذا كانت موجودة
                if (data.zone) {
                    zoneCircle = L.circle([data.zone.lat, data.zone.longg], {
                        color: 'red',
                        fillColor: '#f03',
                        fillOpacity: 0.5,
                        radius: data.zone.area
                    }).addTo(map).bindPopup('Zone Area');
                }
            })
            .catch(error => console.error('Error fetching route data:', error));
    }

    // جلب المسار الافتراضي
    fetchRoute();

    // تحديث الخريطة عند تغيير التاريخ
    dateInput.addEventListener('change', fetchRoute);
});

    </script>
</x-filament::page>
