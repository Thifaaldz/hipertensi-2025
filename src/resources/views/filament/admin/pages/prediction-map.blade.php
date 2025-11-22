<x-filament::page>
    <style>
        #map {
            height: 600px;
            width: 100%;
            border-radius: 8px;
        }
        .filters {
            margin-bottom: 16px;
        }
        .filters select {
            padding: 4px 8px;
            margin-right: 8px;
            border-radius: 4px;
        }
    </style>

    <div class="filters">
        <select id="filter-route">
            <option value="">-- Filter Rute --</option>
            @foreach(array_unique(array_column($predictions, 'predicted_route')) as $route)
                <option value="{{ $route }}">{{ $route ?? '-' }}</option>
            @endforeach
        </select>

        <select id="filter-year">
            <option value="">-- Filter Tahun --</option>
            @foreach(array_unique(array_column($predictions, 'tahun')) as $year)
                <option value="{{ $year }}">{{ $year }}</option>
            @endforeach
        </select>
    </div>

    <div id="map"></div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const map = L.map('map').setView([-6.2, 106.8], 10);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const predictions = @json($predictions);
            let markers = [];

            function renderMarkers(routeFilter = '', yearFilter = '') {
                // hapus marker lama
                markers.forEach(m => map.removeLayer(m));
                markers = [];

                const filtered = predictions.filter(item => {
                    const routeMatch = !routeFilter || item.predicted_route === routeFilter;
                    const yearMatch = !yearFilter || item.tahun == yearFilter;
                    return routeMatch && yearMatch;
                });

                filtered.forEach(item => {
                    if (item.lat && item.lon) {
                        const marker = L.circleMarker([item.lat, item.lon], {
                            radius: 6,
                            color: item.prioritas === 'Prioritas' ? 'red' : 'blue',
                            fillOpacity: 0.7
                        }).bindPopup(`
                            <b>${item.kecamatan}</b><br>
                            Wilayah: ${item.wilayah}<br>
                            Tahun: ${item.tahun}<br>
                            Persentase: ${parseFloat(item.persentase).toFixed(2)}%<br>
                            Prioritas: ${item.prioritas}<br>
                            Rute: ${item.predicted_route ?? '-'}<br>
                            Fokus: ${item.focus_date ?? '-'}
                        `).addTo(map);

                        markers.push(marker);
                    }
                });

                if (markers.length > 0) {
                    const group = new L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.2));
                }
            }

            // render awal
            renderMarkers();

            // event filter
            document.getElementById('filter-route').addEventListener('change', function() {
                renderMarkers(this.value, document.getElementById('filter-year').value);
            });

            document.getElementById('filter-year').addEventListener('change', function() {
                renderMarkers(document.getElementById('filter-route').value, this.value);
            });
        });
    </script>
</x-filament::page>
