<x-filament::page>
    <style>
        #map {
            height: 600px;
            width: 100%;
            border-radius: 8px;
        }
    </style>

    <div id="map"></div>

    {{-- Leaflet CSS & JS tanpa integrity --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const map = L.map('map').setView([-6.2, 106.8], 10);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const predictions = @json($this->getPredictions());
            console.log("Predictions data:", predictions);

            if (predictions.length === 0) {
                alert("⚠️ Tidak ada data prediksi dengan lat/lon");
                return;
            }

            const markers = [];
            predictions.forEach(item => {
                if (item.lat && item.lon) {
                    const marker = L.circleMarker([item.lat, item.lon], {
                        radius: 6,
                        color: item.prioritas === 'Prioritas' ? 'red' : 'blue',
                        fillOpacity: 0.7
                    })
                    .bindPopup(`
                        <b>${item.kecamatan}</b><br>
                        Wilayah: ${item.wilayah}<br>
                        Tahun: ${item.tahun}<br>
                        Persentase: ${parseFloat(item.persentase).toFixed(2)}%<br>
                        Prioritas: ${item.prioritas}<br>
                        Rute: ${item.predicted_route ?? '-'}<br>
                        Fokus: ${item.focus_date ?? '-'}
                    `)
                    .addTo(map);

                    markers.push(marker);
                }
            });

            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.2));
            }
        });
    </script>
</x-filament::page>
