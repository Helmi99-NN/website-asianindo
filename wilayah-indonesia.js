/**
 * Dataset Lengkap 38 Provinsi & 514 Kota/Kabupaten Se-Indonesia
 * CV Asianindo Industrial Machinery
 */

const WILAYAH_INDONESIA = {
    "Aceh": [
        "Kabupaten Aceh Barat", "Kabupaten Aceh Barat Daya", "Kabupaten Aceh Besar", "Kabupaten Aceh Jaya", 
        "Kabupaten Aceh Selatan", "Kabupaten Aceh Singkil", "Kabupaten Aceh Tamiang", "Kabupaten Aceh Tengah", 
        "Kabupaten Aceh Tenggara", "Kabupaten Aceh Timur", "Kabupaten Aceh Utara", "Kabupaten Bener Meriah", 
        "Kabupaten Bireuen", "Kabupaten Gayo Lues", "Kabupaten Nagan Raya", "Kabupaten Pidie", 
        "Kabupaten Pidie Jaya", "Kabupaten Simeulue", "Kota Banda Aceh", "Kota Langsa", 
        "Kota Lhokseumawe", "Kota Sabang", "Kota Subulussalam"
    ],
    "Sumatera Utara": [
        "Kabupaten Asahan", "Kabupaten Batu Bara", "Kabupaten Dairi", "Kabupaten Deli Serdang", 
        "Kabupaten Humbang Hasundutan", "Kabupaten Karo", "Kabupaten Labuhanbatu", "Kabupaten Labuhanbatu Selatan", 
        "Kabupaten Labuhanbatu Utara", "Kabupaten Langkat", "Kabupaten Mandailing Natal", "Kabupaten Nias", 
        "Kabupaten Nias Barat", "Kabupaten Nias Selatan", "Kabupaten Nias Utara", "Kabupaten Padang Lawas", 
        "Kabupaten Padang Lawas Utara", "Kabupaten Pakpak Bharat", "Kabupaten Samosir", "Kabupaten Serdang Bedagai", 
        "Kabupaten Simalungun", "Kabupaten Tapanuli Selatan", "Kabupaten Tapanuli Tengah", "Kabupaten Tapanuli Utara", 
        "Kabupaten Toba", "Kota Binjai", "Kota Gunungsitoli", "Kota Medan", 
        "Kota Padangsidimpuan", "Kota Pematangsiantar", "Kota Sibolga", "Kota Tanjungbalai", "Kota Tebing Tinggi"
    ],
    "Sumatera Barat": [
        "Kabupaten Agam", "Kabupaten Dharmasraya", "Kabupaten Kepulauan Mentawai", "Kabupaten Lima Puluh Kota", 
        "Kabupaten Padang Pariaman", "Kabupaten Pasaman", "Kabupaten Pasaman Barat", "Kabupaten Pesisir Selatan", 
        "Kabupaten Sijunjung", "Kabupaten Solok", "Kabupaten Solok Selatan", "Kabupaten Tanah Datar", 
        "Kota Bukittinggi", "Kota Padang", "Kota Padang Panjang", "Kota Pariaman", 
        "Kota Payakumbuh", "Kota Sawahlunto", "Kota Solok"
    ],
    "Riau": [
        "Kabupaten Bengkalis", "Kabupaten Indragiri Hilir", "Kabupaten Indragiri Hulu", "Kabupaten Kampar", 
        "Kabupaten Kepulauan Meranti", "Kabupaten Kuantan Singingi", "Kabupaten Pelalawan", "Kabupaten Rokan Hilir", 
        "Kabupaten Rokan Hulu", "Kabupaten Siak", "Kota Dumai", "Kota Pekanbaru"
    ],
    "Kepulauan Riau": [
        "Kabupaten Bintan", "Kabupaten Karimun", "Kabupaten Kepulauan Anambas", "Kabupaten Lingga", 
        "Kabupaten Natuna", "Kota Batam", "Kota Tanjungpinang"
    ],
    "Jambi": [
        "Kabupaten Batanghari", "Kabupaten Bungo", "Kabupaten Kerinci", "Kabupaten Merangin", 
        "Kabupaten Muaro Jambi", "Kabupaten Sarolangun", "Kabupaten Tanjung Jabung Barat", "Kabupaten Tanjung Jabung Timur", 
        "Kabupaten Tebo", "Kota Jambi", "Kota Sungai Penuh"
    ],
    "Sumatera Selatan": [
        "Kabupaten Banyuasin", "Kabupaten Empat Lawang", "Kabupaten Lahat", "Kabupaten Muara Enim", 
        "Kabupaten Musi Banyuasin", "Kabupaten Musi Rawas", "Kabupaten Musi Rawas Utara", "Kabupaten Ogan Ilir", 
        "Kabupaten Ogan Komering Ilir", "Kabupaten Ogan Komering Ulu", "Kabupaten Ogan Komering Ulu Selatan", "Kabupaten Ogan Komering Ulu Timur", 
        "Kabupaten Penukal Abab Lematang Ilir", "Kota Lubuklinggau", "Kota Pagar Alam", "Kota Palembang", "Kota Prabumulih"
    ],
    "Kepulauan Bangka Belitung": [
        "Kabupaten Bangka", "Kabupaten Bangka Barat", "Kabupaten Bangka Selatan", "Kabupaten Bangka Tengah", 
        "Kabupaten Belitung", "Kabupaten Belitung Timur", "Kota Pangkalpinang"
    ],
    "Bengkulu": [
        "Kabupaten Bengkulu Selatan", "Kabupaten Bengkulu Tengah", "Kabupaten Bengkulu Utara", "Kabupaten Kaur", 
        "Kabupaten Kepahiang", "Kabupaten Lebong", "Kabupaten Mukomuko", "Kabupaten Rejang Lebong", 
        "Kabupaten Seluma", "Kota Bengkulu"
    ],
    "Lampung": [
        "Kabupaten Lampung Barat", "Kabupaten Lampung Selatan", "Kabupaten Lampung Tengah", "Kabupaten Lampung Timur", 
        "Kabupaten Lampung Utara", "Kabupaten Mesuji", "Kabupaten Pesawaran", "Kabupaten Pesisir Barat", 
        "Kabupaten Pringsewu", "Kabupaten Tanggamus", "Kabupaten Tulang Bawang", "Kabupaten Tulang Bawang Barat", 
        "Kabupaten Way Kanan", "Kota Bandar Lampung", "Kota Metro"
    ],
    "DKI Jakarta": [
        "Kabupaten Kepulauan Seribu", "Kota Jakarta Barat", "Kota Jakarta Pusat", "Kota Jakarta Selatan", 
        "Kota Jakarta Timur", "Kota Jakarta Utara"
    ],
    "Jawa Barat": [
        "Kabupaten Bandung", "Kabupaten Bandung Barat", "Kabupaten Bekasi", "Kabupaten Bogor", 
        "Kabupaten Ciamis", "Kabupaten Cianjur", "Kabupaten Cirebon", "Kabupaten Garut", 
        "Kabupaten Indramayu", "Kabupaten Karawang", "Kabupaten Kuningan", "Kabupaten Majalengka", 
        "Kabupaten Pangandaran", "Kabupaten Purwakarta", "Kabupaten Subang", "Kabupaten Sukabumi", 
        "Kabupaten Sumedang", "Kabupaten Tasikmalaya", "Kota Bandung", "Kota Banjar", 
        "Kota Bekasi", "Kota Bogor", "Kota Cimahi", "Kota Cirebon", 
        "Kota Depok", "Kota Sukabumi", "Kota Tasikmalaya"
    ],
    "Banten": [
        "Kabupaten Lebak", "Kabupaten Pandeglang", "Kabupaten Serang", "Kabupaten Tangerang", 
        "Kota Cilegon", "Kota Serang", "Kota Tangerang", "Kota Tangerang Selatan"
    ],
    "Jawa Tengah": [
        "Kabupaten Banjarnegara", "Kabupaten Banyumas", "Kabupaten Batang", "Kabupaten Blora", 
        "Kabupaten Boyolali", "Kabupaten Brebes", "Kabupaten Cilacap", "Kabupaten Demak", 
        "Kabupaten Grobogan", "Kabupaten Jepara", "Kabupaten Karanganyar", "Kabupaten Kebumen", 
        "Kabupaten Kendal", "Kabupaten Klaten", "Kabupaten Kudus", "Kabupaten Magelang", 
        "Kabupaten Pati", "Kabupaten Pekalongan", "Kabupaten Pemalang", "Kabupaten Purbalingga", 
        "Kabupaten Purworejo", "Kabupaten Rembang", "Kabupaten Semarang", "Kabupaten Sragen", 
        "Kabupaten Sukoharjo", "Kabupaten Tegal", "Kabupaten Temanggung", "Kabupaten Wonogiri", 
        "Kabupaten Wonosobo", "Kota Magelang", "Kota Pekalongan", "Kota Salatiga", 
        "Kota Semarang", "Kota Surakarta (Solo)", "Kota Tegal"
    ],
    "DI Yogyakarta": [
        "Kabupaten Bantul", "Kabupaten Gunungkidul", "Kabupaten Kulon Progo", "Kabupaten Sleman", 
        "Kota Yogyakarta"
    ],
    "Jawa Timur": [
        "Kabupaten Bangkalan", "Kabupaten Banyuwangi", "Kabupaten Blitar", "Kabupaten Bojonegoro", 
        "Kabupaten Bondowoso", "Kabupaten Gresik", "Kabupaten Jember", "Kabupaten Jombang", 
        "Kabupaten Kediri", "Kabupaten Lamongan", "Kabupaten Lumajang", "Kabupaten Madiun", 
        "Kabupaten Magetan", "Kabupaten Malang", "Kabupaten Mojokerto", "Kabupaten Nganjuk", 
        "Kabupaten Ngawi", "Kabupaten Pacitan", "Kabupaten Pamekasan", "Kabupaten Pasuruan", 
        "Kabupaten Ponorogo", "Kabupaten Probolinggo", "Kabupaten Sampang", "Kabupaten Sidoarjo", 
        "Kabupaten Situbondo", "Kabupaten Sumenep", "Kabupaten Trenggalek", "Kabupaten Tuban", 
        "Kabupaten Tulungagung", "Kota Batu", "Kota Blitar", "Kota Kediri", 
        "Kota Madiun", "Kota Malang", "Kota Mojokerto", "Kota Pasuruan", 
        "Kota Probolinggo", "Kota Surabaya"
    ],
    "Bali": [
        "Kabupaten Badung", "Kabupaten Bangli", "Kabupaten Buleleng", "Kabupaten Gianyar", 
        "Kabupaten Jembrana", "Kabupaten Karangasem", "Kabupaten Klungkung", "Kabupaten Tabanan", 
        "Kota Denpasar"
    ],
    "Nusa Tenggara Barat": [
        "Kabupaten Bima", "Kabupaten Dompu", "Kabupaten Lombok Barat", "Kabupaten Lombok Tengah", 
        "Kabupaten Lombok Timur", "Kabupaten Lombok Utara", "Kabupaten Sumbawa", "Kabupaten Sumbawa Barat", 
        "Kota Bima", "Kota Mataram"
    ],
    "Nusa Tenggara Timur": [
        "Kabupaten Alor", "Kabupaten Belu", "Kabupaten Ende", "Kabupaten Flores Timur", 
        "Kabupaten Kupang", "Kabupaten Lembata", "Kabupaten Malaka", "Kabupaten Manggarai", 
        "Kabupaten Manggarai Barat", "Kabupaten Manggarai Timur", "Kabupaten Nagekeo", "Kabupaten Ngada", 
        "Kabupaten Rote Ndao", "Kabupaten Sabu Raijua", "Kabupaten Sikka", "Kabupaten Sumba Barat", 
        "Kabupaten Sumba Barat Daya", "Kabupaten Sumba Tengah", "Kabupaten Sumba Timur", "Kabupaten Timor Tengah Selatan", 
        "Kabupaten Timor Tengah Utara", "Kota Kupang"
    ],
    "Kalimantan Barat": [
        "Kabupaten Bengkayang", "Kabupaten Kapuas Hulu", "Kabupaten Kayong Utara", "Kabupaten Ketapang", 
        "Kabupaten Kubu Raya", "Kabupaten Landak", "Kabupaten Melawi", "Kabupaten Mempawah", 
        "Kabupaten Sambas", "Kabupaten Sanggau", "Kabupaten Sekadau", "Kabupaten Sintang", 
        "Kota Pontianak", "Kota Singkawang"
    ],
    "Kalimantan Tengah": [
        "Kabupaten Barito Selatan", "Kabupaten Barito Timur", "Kabupaten Barito Utara", "Kabupaten Gunung Mas", 
        "Kabupaten Kapuas", "Kabupaten Katingan", "Kabupaten Kotawaringin Barat", "Kabupaten Kotawaringin Timur", 
        "Kabupaten Lamandau", "Kabupaten Murung Raya", "Kabupaten Pulang Pisau", "Kabupaten Sukamara", 
        "Kabupaten Seruyan", "Kota Palangka Raya"
    ],
    "Kalimantan Selatan": [
        "Kabupaten Balangan", "Kabupaten Banjar", "Kabupaten Barito Kuala", "Kabupaten Hulu Sungai Selatan", 
        "Kabupaten Hulu Sungai Tengah", "Kabupaten Hulu Sungai Utara", "Kabupaten Kotabaru", "Kabupaten Tabalong", 
        "Kabupaten Tanah Bumbu", "Kabupaten Tanah Laut", "Kabupaten Tapin", "Kota Banjarbaru", "Kota Banjarmasin"
    ],
    "Kalimantan Timur": [
        "Kabupaten Berau", "Kabupaten Kutai Barat", "Kabupaten Kutai Kartanegara", "Kabupaten Kutai Timur", 
        "Kabupaten Mahakam Ulu", "Kabupaten Paser", "Kabupaten Penajam Paser Utara", "Kota Balikpapan", 
        "Kota Bontang", "Kota Samarinda"
    ],
    "Kalimantan Utara": [
        "Kabupaten Bulungan", "Kabupaten Malinau", "Kabupaten Nunukan", "Kabupaten Tana Tidung", 
        "Kota Tarakan"
    ],
    "Sulawesi Utara": [
        "Kabupaten Bolaang Mongondow", "Kabupaten Bolaang Mongondow Selatan", "Kabupaten Bolaang Mongondow Timur", "Kabupaten Bolaang Mongondow Utara", 
        "Kabupaten Kepulauan Sangihe", "Kabupaten Kepulauan Siau Tagulandang Biaro", "Kabupaten Kepulauan Talaud", "Kabupaten Minahasa", 
        "Kabupaten Minahasa Selatan", "Kabupaten Minahasa Tenggara", "Kabupaten Minahasa Utara", "Kota Bitung", 
        "Kota Kotamobagu", "Kota Manado", "Kota Tomohon"
    ],
    "Gorontalo": [
        "Kabupaten Boalemo", "Kabupaten Bone Bolango", "Kabupaten Gorontalo", "Kabupaten Gorontalo Utara", 
        "Kabupaten Pohuwato", "Kota Gorontalo"
    ],
    "Sulawesi Tengah": [
        "Kabupaten Banggai", "Kabupaten Banggai Kepulauan", "Kabupaten Banggai Laut", "Kabupaten Buol", 
        "Kabupaten Donggala", "Kabupaten Morowali", "Kabupaten Morowali Utara", "Kabupaten Parigi Moutong", 
        "Kabupaten Poso", "Kabupaten Sigi", "Kabupaten Tojo Una-Una", "Kabupaten Tolitoli", "Kota Palu"
    ],
    "Sulawesi Barat": [
        "Kabupaten Majene", "Kabupaten Mamasa", "Kabupaten Mamuju", "Kabupaten Mamuju Tengah", 
        "Kabupaten Pasangkayu", "Kabupaten Polewali Mandar"
    ],
    "Sulawesi Selatan": [
        "Kabupaten Bantaeng", "Kabupaten Barru", "Kabupaten Bone", "Kabupaten Bulukumba", 
        "Kabupaten Enrekang", "Kabupaten Gowa", "Kabupaten Jeneponto", "Kabupaten Kepulauan Selayar", 
        "Kabupaten Luwu", "Kabupaten Luwu Timur", "Kabupaten Luwu Utara", "Kabupaten Maros", 
        "Kabupaten Pangkajene dan Kepulauan", "Kabupaten Pinrang", "Kabupaten Sidenreng Rappang", "Kabupaten Sinjai", 
        "Kabupaten Soppeng", "Kabupaten Takalar", "Kabupaten Tana Toraja", "Kabupaten Toraja Utara", 
        "Kabupaten Wajo", "Kota Makassar", "Kota Palopo", "Kota Parepare"
    ],
    "Sulawesi Tenggara": [
        "Kabupaten Bombana", "Kabupaten Buton", "Kabupaten Buton Selatan", "Kabupaten Buton Tengah", 
        "Kabupaten Buton Utara", "Kabupaten Kolaka", "Kabupaten Kolaka Timur", "Kabupaten Kolaka Utara", 
        "Kabupaten Konawe", "Kabupaten Konawe Kepulauan", "Kabupaten Konawe Selatan", "Kabupaten Konawe Utara", 
        "Kabupaten Muna", "Kabupaten Muna Barat", "Kabupaten Wakatobi", "Kota Baubau", "Kota Kendari"
    ],
    "Maluku": [
        "Kabupaten Buru", "Kabupaten Buru Selatan", "Kabupaten Kepulauan Aru", "Kabupaten Kepulauan Tanimbar", 
        "Kabupaten Maluku Barat Daya", "Kabupaten Maluku Tengah", "Kabupaten Maluku Tenggara", "Kabupaten Seram Bagian Barat", 
        "Kabupaten Seram Bagian Timur", "Kota Ambon", "Kota Tual"
    ],
    "Maluku Utara": [
        "Kabupaten Halmahera Barat", "Kabupaten Halmahera Tengah", "Kabupaten Halmahera Timur", "Kabupaten Halmahera Selatan", 
        "Kabupaten Halmahera Utara", "Kabupaten Kepulauan Sula", "Kabupaten Pulau Morotai", "Kabupaten Pulau Taliabu", 
        "Kota Ternate", "Kota Tidore Kepulauan"
    ],
    "Papua": [
        "Kabupaten Biak Numfor", "Kabupaten Jayapura", "Kabupaten Keerom", "Kabupaten Kepulauan Yapen", 
        "Kabupaten Mamberamo Raya", "Kabupaten Sarmi", "Kabupaten Supiori", "Kabupaten Waropen", "Kota Jayapura"
    ],
    "Papua Barat": [
        "Kabupaten Fakfak", "Kabupaten Kaimana", "Kabupaten Manokwari", "Kabupaten Manokwari Selatan", 
        "Kabupaten Pegunungan Arfak", "Kabupaten Teluk Bintuni", "Kabupaten Teluk Wondama"
    ],
    "Papua Selatan": [
        "Kabupaten Asmat", "Kabupaten Boven Digoel", "Kabupaten Mappi", "Kabupaten Merauke"
    ],
    "Papua Tengah": [
        "Kabupaten Deiyai", "Kabupaten Dogiyai", "Kabupaten Intan Jaya", "Kabupaten Mimika", 
        "Kabupaten Nabire", "Kabupaten Paniai", "Kabupaten Puncak", "Kabupaten Puncak Jaya"
    ],
    "Papua Pegunungan": [
        "Kabupaten Jayawijaya", "Kabupaten Lanny Jaya", "Kabupaten Mamberamo Tengah", "Kabupaten Nduga", 
        "Kabupaten Pegunungan Bintang", "Kabupaten Tolikara", "Kabupaten Yalimo", "Kabupaten Yahukimo"
    ],
    "Papua Barat Daya": [
        "Kabupaten Maybrat", "Kabupaten Raja Ampat", "Kabupaten Sorong", "Kabupaten Sorong Selatan", 
        "Kabupaten Tambrauw", "Kota Sorong"
    ]
};

/**
 * Helper untuk menginisialisasi Cascading Dropdown (Provinsi -> Kota/Kabupaten)
 */
function initWilayahDropdown(provSelectId, citySelectId, defaultProv = '', defaultCity = '', onChangeCallback = null) {
    const provEl = document.getElementById(provSelectId);
    const cityEl = document.getElementById(citySelectId);
    if (!provEl || !cityEl) return;

    // Bersihkan dan isi daftar Provinsi
    provEl.innerHTML = '<option value="">-- Pilih Provinsi --</option>';
    Object.keys(WILAYAH_INDONESIA).forEach(prov => {
        const opt = document.createElement('option');
        opt.value = prov;
        opt.textContent = prov;
        if (defaultProv && prov.toLowerCase() === defaultProv.toLowerCase()) {
            opt.selected = true;
        }
        provEl.appendChild(opt);
    });

    // Fungsi update pilihan Kota/Kabupaten
    function populateCities(provName, chosenCity = '') {
        cityEl.innerHTML = '<option value="">-- Pilih Kota / Kabupaten --</option>';
        if (!provName || !WILAYAH_INDONESIA[provName]) {
            cityEl.disabled = true;
            cityEl.classList.add('bg-gray-100', 'cursor-not-allowed');
            return;
        }
        cityEl.disabled = false;
        cityEl.classList.remove('bg-gray-100', 'cursor-not-allowed');

        const cities = WILAYAH_INDONESIA[provName];
        cities.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            // Cek kecocokan default city (bisa mengandung kata atau exact)
            if (chosenCity && (c.toLowerCase() === chosenCity.toLowerCase() || c.toLowerCase().includes(chosenCity.toLowerCase()) || chosenCity.toLowerCase().includes(c.toLowerCase()))) {
                opt.selected = true;
            }
            cityEl.appendChild(opt);
        });
    }

    // Set initial state
    if (defaultProv && WILAYAH_INDONESIA[defaultProv]) {
        populateCities(defaultProv, defaultCity);
    } else if (provEl.value && WILAYAH_INDONESIA[provEl.value]) {
        populateCities(provEl.value, defaultCity);
    } else {
        cityEl.disabled = true;
        cityEl.classList.add('bg-gray-100', 'cursor-not-allowed');
    }

    // Event listener change provinsi
    provEl.addEventListener('change', function() {
        populateCities(this.value);
        if (typeof onChangeCallback === 'function') {
            onChangeCallback();
        }
    });

    // Event listener change kota
    cityEl.addEventListener('change', function() {
        if (typeof onChangeCallback === 'function') {
            onChangeCallback();
        }
    });
}

/**
 * Setup Searchable Combobox Wilayah (Ketik untuk mencari Kota / Provinsi)
 */
function setupSearchableWilayah(provInputId, cityInputId, defaultProv = '', defaultCity = '', onSelectCallback = null) {
    const provInput = document.getElementById(provInputId);
    const cityInput = document.getElementById(cityInputId);
    if (!provInput || !cityInput) return;

    const provDropdown = document.getElementById(`dropdown-${provInputId}`);
    const cityDropdown = document.getElementById(`dropdown-${cityInputId}`);
    if (!provDropdown || !cityDropdown) return;

    if (defaultProv) provInput.value = defaultProv;
    if (defaultCity) cityInput.value = defaultCity;

    function closeAll() {
        provDropdown.classList.add('hidden');
        cityDropdown.classList.add('hidden');
    }

    // Close on click outside
    document.addEventListener('click', function(e) {
        const inProv = provInput.contains(e.target) || provDropdown.contains(e.target);
        const inCity = cityInput.contains(e.target) || cityDropdown.contains(e.target);
        if (!inProv && !inCity) {
            closeAll();
        }
    });

    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeAll();
    });

    // ==========================================
    // 1. SEARCHABLE PROVINSI
    // ==========================================
    function renderProvinces(q = '') {
        provDropdown.innerHTML = '';
        const provinces = Object.keys(WILAYAH_INDONESIA);
        const search = q.toLowerCase().trim();
        const filtered = search === '' ? provinces : provinces.filter(p => p.toLowerCase().includes(search));

        if (filtered.length === 0) {
            provDropdown.innerHTML = '<div class="px-3 py-2 text-xs text-gray-400 italic">Provinsi tidak ditemukan</div>';
            provDropdown.classList.remove('hidden');
            return;
        }

        filtered.forEach(p => {
            const el = document.createElement('div');
            el.className = 'px-3 py-2 text-xs sm:text-sm text-gray-800 hover:bg-purple-50 hover:text-primary cursor-pointer transition-colors flex items-center justify-between';
            el.innerHTML = `<span>${p}</span>`;
            if (provInput.value === p) {
                el.classList.add('bg-purple-50', 'text-primary', 'font-semibold');
            }

            el.addEventListener('mousedown', function(e) {
                e.preventDefault();
                provInput.value = p;
                provDropdown.classList.add('hidden');

                // Jika kota yang sebelumnya dipilih bukan bagian dari provinsi ini, kosongkan
                if (cityInput.value) {
                    const citiesInProv = WILAYAH_INDONESIA[p] || [];
                    const found = citiesInProv.some(c => c.toLowerCase() === cityInput.value.toLowerCase());
                    if (!found) cityInput.value = '';
                }

                if (typeof onSelectCallback === 'function') onSelectCallback();
            });

            provDropdown.appendChild(el);
        });

        provDropdown.classList.remove('hidden');
    }

    provInput.addEventListener('focus', function() {
        cityDropdown.classList.add('hidden');
        renderProvinces(this.value);
    });

    provInput.addEventListener('input', function() {
        renderProvinces(this.value);
    });

    // ==========================================
    // 2. SEARCHABLE KOTA / KABUPATEN
    // ==========================================
    function renderCities(q = '') {
        cityDropdown.innerHTML = '';
        const curProv = provInput.value.trim();
        let list = [];

        if (curProv && WILAYAH_INDONESIA[curProv]) {
            list = WILAYAH_INDONESIA[curProv].map(c => ({ city: c, province: curProv }));
        } else {
            // Jika provinsi belum dipilih, cari di seluruh kota se-Indonesia
            Object.entries(WILAYAH_INDONESIA).forEach(([prov, cities]) => {
                cities.forEach(c => list.push({ city: c, province: prov }));
            });
        }

        const search = q.toLowerCase().trim();
        const filtered = search === '' 
            ? list.slice(0, 40)
            : list.filter(item => item.city.toLowerCase().includes(search) || item.province.toLowerCase().includes(search));

        if (filtered.length === 0) {
            cityDropdown.innerHTML = '<div class="px-3 py-2 text-xs text-gray-400 italic">Kota / Kabupaten tidak ditemukan</div>';
            cityDropdown.classList.remove('hidden');
            return;
        }

        filtered.slice(0, 50).forEach(item => {
            const el = document.createElement('div');
            el.className = 'px-3 py-2 text-xs sm:text-sm text-gray-800 hover:bg-purple-50 hover:text-primary cursor-pointer transition-colors flex items-center justify-between gap-2';
            el.innerHTML = `
                <span class="font-medium">${item.city}</span>
                <span class="text-[11px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded flex-shrink-0">${item.province}</span>
            `;

            if (cityInput.value === item.city) {
                el.classList.add('bg-purple-50', 'text-primary', 'font-bold');
            }

            el.addEventListener('mousedown', function(e) {
                e.preventDefault();
                cityInput.value = item.city;
                // Otomatis isi provinsi sesuai kota yang dipilih jika belum terisi atau berbeda
                if (provInput.value !== item.province) {
                    provInput.value = item.province;
                }
                cityDropdown.classList.add('hidden');

                if (typeof onSelectCallback === 'function') onSelectCallback();
            });

            cityDropdown.appendChild(el);
        });

        cityDropdown.classList.remove('hidden');
    }

    cityInput.addEventListener('focus', function() {
        provDropdown.classList.add('hidden');
        renderCities(this.value);
    });

    cityInput.addEventListener('input', function() {
        renderCities(this.value);
    });
}

if (typeof window !== 'undefined') {
    window.WILAYAH_INDONESIA = WILAYAH_INDONESIA;
    window.initWilayahDropdown = initWilayahDropdown;
    window.setupSearchableWilayah = setupSearchableWilayah;
}
if (typeof globalThis !== 'undefined') {
    globalThis.WILAYAH_INDONESIA = WILAYAH_INDONESIA;
    globalThis.initWilayahDropdown = initWilayahDropdown;
    globalThis.setupSearchableWilayah = setupSearchableWilayah;
}
