<div class="bg-gray-50 text-gray-800">

    {{-- HERO SECTION FULL SCREEN --}}
    <section class="relative w-full h-screen flex items-center justify-center">

        <!-- Background Image -->
        <img src="{{ asset('front/assets/img/hero.jpg') }}" 
             alt="Hipertensi Hero"
             class="absolute inset-0 w-full h-full object-cover">

        <!-- Overlay -->
        <div class="absolute inset-0 bg-black bg-opacity-60"></div>

        <!-- Content -->
        <div class="relative text-center text-white max-w-3xl px-6 animate-fadeIn">
            <h1 class="text-4xl md:text-6xl font-extrabold leading-tight drop-shadow-xl">
                Sistem Prediksi Tingkat Hipertensi
            </h1>

            <p class="text-lg md:text-2xl mt-4 opacity-90 drop-shadow-lg">
                Analisis data, peta persebaran, dan edukasi kesehatan berbasis data prediktif.
            </p>

            <a href="#info"
               class="mt-10 inline-block bg-red-600 hover:bg-red-700 transition text-white font-semibold px-8 py-3 rounded-full shadow-lg">
                Pelajari Lebih Lanjut
            </a>
        </div>
    </section>

    {{-- ANIMATION --}}
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 1.5s ease-out forwards;
        }
    </style>



    {{-- ABOUT HIPERTENSI --}}
    <section id="info" class="section py-16 px-6 max-w-5xl mx-auto">
        <h2 class="text-3xl font-bold text-center mb-6">Apa itu Hipertensi?</h2>

        <div class="grid md:grid-cols-2 gap-10 items-center">
            <img src="{{ asset('front/assets/img/about.jpg') }}"
                 class="rounded-xl shadow-md object-cover"
                 alt="About Hipertensi">

            <p class="text-lg leading-relaxed">
                Hipertensi atau tekanan darah tinggi adalah kondisi ketika tekanan darah terhadap dinding arteri meningkat.
                Jika tidak ditangani, hipertensi dapat menyebabkan komplikasi serius seperti:
                <br><br>
                • Serangan jantung  
                • Stroke  
                • Gagal ginjal  
                • Kerusakan pembuluh darah  
            </p>
        </div>
    </section>



    {{-- MAP SECTION --}}
    <section id="map-section" class="section py-16 px-6 max-w-6xl mx-auto">
        <h2 class="text-3xl font-bold text-center mb-6">Peta Prediksi Hipertensi</h2>

        <div class="filters text-center mb-4">
            <select id="filter-route" class="px-4 py-2 border rounded-md">
                <option value="">-- Filter Rute --</option>
                @foreach($routes as $route)
                    <option value="{{ $route }}">{{ $route }}</option>
                @endforeach
            </select>

            <select id="filter-year" class="px-4 py-2 border rounded-md">
                <option value="">-- Filter Tahun --</option>
                @foreach($years as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>

        <div id="map" class="rounded-xl shadow-lg" style="height: 500px; width: 100%;"></div>
    </section>



    {{-- TIPS --}}
    <section class="py-16 px-6 max-w-6xl mx-auto bg-white">
        <h2 class="text-3xl font-bold text-center mb-10">Cara Mencegah Hipertensi</h2>

        <div class="grid md:grid-cols-3 gap-8">
            <div class="p-6 border rounded-xl shadow hover:shadow-lg transition bg-gray-50">
                <h3 class="text-xl font-semibold mb-2">Kurangi Garam</h3>
                <p>Batasi konsumsi garam dan makanan olahan untuk menjaga tekanan darah.</p>
            </div>

            <div class="p-6 border rounded-xl shadow hover:shadow-lg transition bg-gray-50">
                <h3 class="text-xl font-semibold mb-2">Olahraga Rutin</h3>
                <p>Aktivitas fisik minimal 30 menit per hari menjaga kesehatan jantung.</p>
            </div>

            <div class="p-6 border rounded-xl shadow hover:shadow-lg transition bg-gray-50">
                <h3 class="text-xl font-semibold mb-2">Kelola Stres</h3>
                <p>Istirahat cukup, meditasi, dan relaksasi membantu menormalkan tekanan darah.</p>
            </div>
        </div>
    </section>



    {{-- JOURNAL --}}
    <section class="py-16 px-6 max-w-6xl mx-auto">
        <h2 class="text-3xl font-bold text-center mb-10">Jurnal Penting Tentang Hipertensi</h2>

        <div class="space-y-6">

            {{-- Journal 1 --}}
            <div class="p-6 border rounded-xl shadow bg-white">
                <h3 class="font-bold text-xl">
                    1. WHO - Hypertension
                    <a href="https://www.who.int/news-room/fact-sheets/detail/hypertension"
                       target="_blank" class="text-blue-500 underline">Baca</a>
                </h3>
                <p class="mt-2 text-gray-700">
                    • 1,3 miliar orang mengalami hipertensi  
                    • Penyebab utama stroke & serangan jantung  
                    • 46% penderita tidak sadar mengidap hipertensi  
                </p>
            </div>

            {{-- Journal 2 --}}
            <div class="p-6 border rounded-xl shadow bg-white">
                <h3 class="font-bold text-xl">
                    2. NCBI - Hypertension Review
                    <a href="https://www.ncbi.nlm.nih.gov/pmc/articles/PMC5463130/"
                       target="_blank" class="text-blue-500 underline">Baca</a>
                </h3>
                <p class="mt-2 text-gray-700">
                    • Faktor risiko: genetik, obesitas, diet buruk  
                    • Hipertensi sering tidak menunjukkan gejala  
                    • Deteksi dini adalah langkah terbaik  
                </p>
            </div>

            {{-- Journal 3 --}}
            <div class="p-6 border rounded-xl shadow bg-white">
                <h3 class="font-bold text-xl">
                    3. American Heart Association
                    <a href="https://www.ahajournals.org/journal/hyp"
                       target="_blank" class="text-blue-500 underline">Baca</a>
                </h3>
                <p class="mt-2 text-gray-700">
                    • Pencegahan melalui gaya hidup sehat  
                    • Obat antihipertensi untuk risiko tinggi  
                    • Monitoring tekanan darah di rumah sangat efektif  
                </p>
            </div>

        </div>
    </section>



    {{-- FOOTER --}}
    <footer class="text-center py-6 bg-gray-800 text-white mt-10">
        &copy; {{ date('Y') }} Sistem Prediksi Hipertensi - All Rights Reserved
    </footer>



    {{-- Leaflet --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>



    {{-- MAP SCRIPT FIX LIVEWIRE --}}
    <script>
        document.addEventListener("livewire:navigated", () => initMap());
        document.addEventListener("livewire:load", () => initMap());

        function initMap() {
            if (window.mapLoaded) return;
            window.mapLoaded = true;

            const map = L.map('map').setView([-6.2, 106.8], 10);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

            const predictions = @json($predictions);
            let markers = [];

            function renderMarkers(routeFilter = '', yearFilter = '') {
                markers.forEach(m => map.removeLayer(m));
                markers = [];

                const filtered = predictions.filter(item =>
                    (!routeFilter || item.predicted_route === routeFilter) &&
                    (!yearFilter || item.tahun == yearFilter)
                );

                filtered.forEach(item => {
                    const marker = L.circleMarker([item.lat, item.lon], {
                        radius: 6,
                        color: item.prioritas === 'Prioritas' ? 'red' : 'blue',
                        fillOpacity: 0.7
                    }).bindPopup(`
                        <b>${item.kecamatan}</b><br>
                        Wilayah: ${item.wilayah}<br>
                        Tahun: ${item.tahun}<br>
                        Persentase: ${item.persentase}%<br>
                    `).addTo(map);

                    markers.push(marker);
                });

                if (markers.length) {
                    map.fitBounds(L.featureGroup(markers).getBounds().pad(0.2));
                }
            }

            renderMarkers();

            document.getElementById('filter-route').addEventListener('change', () =>
                renderMarkers(
                    document.getElementById('filter-route').value,
                    document.getElementById('filter-year').value
                )
            );

            document.getElementById('filter-year').addEventListener('change', () =>
                renderMarkers(
                    document.getElementById('filter-route').value,
                    document.getElementById('filter-year').value
                )
            );
        }
    </script>

</div>
