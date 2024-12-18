
<x-filament::page>
      <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <div id="map" style="height: 500px; width: 100%;"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // إعداد الخريطة
            const map = L.map('map').setView([26.9924219, 49.6538872], 13);

            // إضافة طبقة OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // معرف الموظف من الصفحة
            const employeeId = "{{ $employeeId }}";

            // جلب بيانات المسار
            fetch(`/filament/employee-route/${employeeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.features && data.features.length > 0) {
                        // تحويل الإحداثيات ورسم المسار
                        const latlngs = data.features.map(feature => {
                            const [lng, lat] = feature.geometry.coordinates;
                            return [parseFloat(lat), parseFloat(lng)];
                        });

                        // إضافة المسار إلى الخريطة
                        const polyline = L.polyline(latlngs, { color: 'blue' }).addTo(map);

                        // ضبط عرض الخريطة ليتناسب مع المسار
                        map.fitBounds(polyline.getBounds());
                    } else {
                        alert('لا توجد بيانات مسار متوفرة.');
                    }
                })
                .catch(error => console.error('Error fetching route data:', error));
        });
    </script>
</x-filament::page>
