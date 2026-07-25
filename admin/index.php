<?php
session_start();
$is_admin = isset($_SESSION['is_admin']) ? true : false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asianindo Seller Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .shopee-bg { background-color: #f6f6f6; }
        .shopee-primary { background-color: #ee4d2d; color: white; }
        .shopee-primary:hover { background-color: #d73211; }
        .shopee-text { color: #ee4d2d; }
    </style>
</head>
<body class="shopee-bg text-gray-800 font-sans" x-data="adminApp()" x-init="initApp()">

    <!-- LOGIN SCREEN -->
    <div x-show="!isLoggedIn" id="loginScreen">
        <div class="min-h-screen flex items-center justify-center bg-gray-100">
            <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold shopee-text">Asianindo</h1>
                    <p class="text-gray-500 mt-2">Seller Center</p>
                </div>
                <form @submit.prevent="login">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                        <input x-model="loginForm.username" type="text" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-[#ee4d2d]">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                        <input x-model="loginForm.password" type="password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-[#ee4d2d]">
                    </div>
                    <p x-show="loginError" x-text="loginError" class="text-red-500 text-sm mb-4"></p>
                    <button type="submit" class="w-full shopee-primary py-3 rounded-lg font-bold transition-colors">
                        <span x-show="!isLoading">Masuk</span>
                        <span x-show="isLoading">Memuat...</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- DASHBOARD -->
    <div x-show="isLoggedIn" x-cloak id="dashboardScreen">
        <div class="flex h-screen overflow-hidden">
            <!-- Sidebar -->
            <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-2xl font-bold shopee-text">Asianindo</h2>
                    <p class="text-xs text-gray-500">Seller Center</p>
                </div>
                <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
                    <a href="#" class="flex items-center gap-3 px-4 py-3 bg-red-50 text-[#ee4d2d] rounded-lg font-medium">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        Produk Saya
                    </a>
                    <!-- More menus can be added here -->
                </nav>
                <div class="p-4 border-t border-gray-200">
                    <button @click="logout" class="w-full flex items-center justify-center gap-2 px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Keluar
                    </button>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 flex flex-col h-screen relative">
                <!-- Topbar -->
                <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-8">
                    <h1 class="text-xl font-bold text-gray-800" x-text="currentView === 'list' ? 'Produk Saya' : (editingId ? 'Edit Produk' : 'Tambah Produk Baru')"></h1>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600">Admin CV Asianindo</span>
                        <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold">A</div>
                    </div>
                </header>

                <!-- Content Area -->
                <div class="flex-1 overflow-y-auto p-8">
                    
                    <!-- PRODUCT LIST -->
                    <div x-show="currentView === 'list'" x-cloak>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                                <div class="relative w-64">
                                    <input type="text" x-model="searchQuery" placeholder="Cari nama produk..." class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:border-[#ee4d2d]">
                                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <button @click="openAddForm" class="shopee-primary px-6 py-2 rounded-lg font-medium flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Tambah Produk Baru
                                </button>
                            </div>
                            
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-600 text-sm">
                                        <th class="p-4 border-b font-medium">Info Produk</th>
                                        <th class="p-4 border-b font-medium">Kategori</th>
                                        <th class="p-4 border-b font-medium">Harga</th>
                                        <th class="p-4 border-b font-medium text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="p in filteredProducts" :key="p.id">
                                        <tr class="hover:bg-gray-50 border-b border-gray-100 transition-colors">
                                            <td class="p-4">
                                                <div class="flex items-center gap-4">
                                                    <img :src="'../' + p.image" class="w-16 h-16 object-cover rounded-lg border">
                                                    <div>
                                                        <p class="font-medium text-gray-800 line-clamp-2" x-text="p.name"></p>
                                                        <p class="text-xs text-gray-500 mt-1" x-text="'ID: ' + p.id"></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-4 text-gray-600" x-text="p.category"></td>
                                            <td class="p-4 font-medium text-gray-800" x-text="'Rp' + p.price.toLocaleString('id-ID')"></td>
                                            <td class="p-4 text-right">
                                                <button @click="openEditForm(p)" class="text-blue-600 hover:text-blue-800 mr-3 text-sm font-medium">Ubah</button>
                                                <button @click="deleteProduct(p.id)" class="text-red-600 hover:text-red-800 text-sm font-medium">Hapus</button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="filteredProducts.length === 0">
                                        <td colspan="4" class="p-8 text-center text-gray-500">Tidak ada produk ditemukan.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- PRODUCT FORM -->
                    <div x-show="currentView === 'form'" x-cloak class="max-w-4xl mx-auto pb-20">
                        <button @click="currentView = 'list'" class="flex items-center gap-2 text-gray-600 hover:text-[#ee4d2d] mb-6 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Kembali ke Daftar
                        </button>

                        <form @submit.prevent="saveProduct" class="space-y-6">
                            
                            <!-- Informasi Dasar -->
                            <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
                                <h3 class="text-lg font-bold text-gray-800 mb-6">Informasi Dasar</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk *</label>
                                        <input required x-model="form.name" type="text" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-[#ee4d2d]">
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                                            <input x-model="form.category" type="text" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-[#ee4d2d]">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Sub Kategori</label>
                                            <input x-model="form.subCategory" type="text" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-[#ee4d2d]">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Harga (Angka) *</label>
                                            <input required x-model="form.price" type="number" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-[#ee4d2d]">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Tampilan Harga (Teks)</label>
                                            <input x-model="form.priceRange" type="text" placeholder="Misal: Rp10.000.000 - Rp20.000.000" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-[#ee4d2d]">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Produk</label>
                                        <textarea x-model="form.description" rows="5" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-[#ee4d2d]"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Media -->
                            <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
                                <h3 class="text-lg font-bold text-gray-800 mb-6">Media Produk</h3>
                                
                                <div class="space-y-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto Produk (Maks 2MB) *</label>
                                        <div class="flex items-center gap-6">
                                            <div class="w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center relative overflow-hidden bg-gray-50">
                                                <template x-if="imagePreview || form.existing_image">
                                                    <img :src="imagePreview ? imagePreview : '../' + form.existing_image" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!imagePreview && !form.existing_image">
                                                    <div class="text-center text-gray-400">
                                                        <svg class="mx-auto h-8 w-8" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="flex-1">
                                                <input type="file" x-ref="imageInput" @change="handleImage" accept="image/jpeg, image/png, image/webp" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-[#ee4d2d] hover:file:bg-red-100">
                                                <p class="mt-2 text-xs text-gray-500">Format: JPG, PNG. Maks: 2MB.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border-t pt-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Video Produk (Maks 15MB) - Opsional</label>
                                        <div class="flex items-center gap-6">
                                            <div class="w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center relative overflow-hidden bg-gray-50">
                                                <template x-if="videoPreview || form.existing_video">
                                                    <div class="text-[#ee4d2d] flex flex-col items-center">
                                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"></path></svg>
                                                        <span class="text-xs mt-1">Video Ready</span>
                                                    </div>
                                                </template>
                                                <template x-if="!videoPreview && !form.existing_video">
                                                    <div class="text-center text-gray-400">
                                                        <svg class="mx-auto h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="flex-1">
                                                <input type="file" x-ref="videoInput" @change="handleVideo" accept="video/mp4, video/webm" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                                                <p class="mt-2 text-xs text-gray-500">Format: MP4, WEBM. Maks: 15MB.</p>
                                                <template x-if="form.existing_video">
                                                    <p class="text-xs text-green-600 mt-1">Video saat ini sudah tersimpan. Unggah baru untuk mengganti.</p>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fitur & Spesifikasi -->
                            <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
                                <h3 class="text-lg font-bold text-gray-800 mb-6">Fitur & Spesifikasi</h3>
                                
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fitur Utama</label>
                                    <template x-for="(feat, index) in form.features" :key="index">
                                        <div class="flex gap-2 mb-2">
                                            <input type="text" x-model="form.features[index]" class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-[#ee4d2d]" placeholder="Keunggulan mesin...">
                                            <button type="button" @click="form.features.splice(index, 1)" class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </template>
                                    <button type="button" @click="form.features.push('')" class="text-sm text-[#ee4d2d] font-medium mt-2">+ Tambah Fitur</button>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tabel Spesifikasi</label>
                                    <template x-for="(spec, index) in form.specs" :key="index">
                                        <div class="flex gap-2 mb-2">
                                            <input type="text" x-model="spec.key" class="w-1/3 px-4 py-2 border rounded-lg focus:outline-none focus:border-[#ee4d2d]" placeholder="Kapasitas / Dimensi">
                                            <input type="text" x-model="spec.val" class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-[#ee4d2d]" placeholder="Nilai (Cth: 100 kg/jam)">
                                            <button type="button" @click="form.specs.splice(index, 1)" class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </template>
                                    <button type="button" @click="form.specs.push({key:'', val:''})" class="text-sm text-[#ee4d2d] font-medium mt-2">+ Tambah Spesifikasi</button>
                                </div>
                            </div>

                            <!-- Floating Action Bar -->
                            <div class="fixed bottom-0 left-64 right-0 bg-white border-t p-4 flex justify-end gap-4 z-10 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                                <button type="button" @click="currentView = 'list'" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50">Batal</button>
                                <button type="submit" class="shopee-primary px-8 py-2 rounded-lg font-medium shadow-md">
                                    <span x-show="!isSaving">Simpan Produk</span>
                                    <span x-show="isSaving">Menyimpan...</span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        function adminApp() {
            return {
                isLoggedIn: <?php echo $is_admin ? 'true' : 'false'; ?>,
                loginForm: { username: '', password: '' },
                loginError: '',
                isLoading: false,
                isSaving: false,
                currentView: 'list', // 'list' or 'form'
                products: [],
                searchQuery: '',
                editingId: null,
                form: this.getEmptyForm(),
                imagePreview: null,
                videoPreview: null,
                imageFile: null,
                videoFile: null,

                initApp() {
                    if (this.isLoggedIn) {
                        this.loadProducts();
                    }
                },

                get filteredProducts() {
                    if (this.searchQuery === '') return this.products;
                    return this.products.filter(p => p.name.toLowerCase().includes(this.searchQuery.toLowerCase()));
                },

                getEmptyForm() {
                    return {
                        id: '', name: '', category: 'Mesin Industri', subCategory: '', price: '', priceRange: '',
                        description: '', features: [''], specs: [{key: '', val: ''}], existing_image: '', existing_video: ''
                    };
                },

                async login() {
                    this.isLoading = true;
                    this.loginError = '';
                    const fd = new FormData();
                    fd.append('username', this.loginForm.username);
                    fd.append('password', this.loginForm.password);
                    
                    try {
                        let res = await fetch('api.php?action=login', { method: 'POST', body: fd });
                        let data = await res.json();
                        if (data.success) {
                            this.isLoggedIn = true;
                            this.loadProducts();
                        } else {
                            this.loginError = data.error || 'Gagal login';
                        }
                    } catch (e) {
                        this.loginError = 'Terjadi kesalahan jaringan';
                    }
                    this.isLoading = false;
                },

                async logout() {
                    await fetch('api.php?action=logout');
                    this.isLoggedIn = false;
                    this.loginForm.password = '';
                },

                async loadProducts() {
                    try {
                        let res = await fetch('api.php?action=get_products');
                        this.products = await res.json();
                    } catch (e) {
                        alert('Gagal memuat produk');
                    }
                },

                openAddForm() {
                    this.editingId = null;
                    this.form = this.getEmptyForm();
                    this.imagePreview = null;
                    this.videoPreview = null;
                    this.imageFile = null;
                    this.videoFile = null;
                    if(this.$refs.imageInput) this.$refs.imageInput.value = '';
                    if(this.$refs.videoInput) this.$refs.videoInput.value = '';
                    this.currentView = 'form';
                },

                openEditForm(p) {
                    this.editingId = p.id;
                    let specsArr = [];
                    if (p.specs) {
                        for (let k in p.specs) specsArr.push({key: k, val: p.specs[k]});
                    }
                    if (specsArr.length === 0) specsArr.push({key: '', val: ''});
                    
                    this.form = {
                        id: p.id,
                        name: p.name,
                        category: p.category,
                        subCategory: p.subCategory,
                        price: p.price,
                        priceRange: p.priceRange || '',
                        description: p.description || '',
                        features: (p.features && p.features.length) ? [...p.features] : [''],
                        specs: specsArr,
                        existing_image: p.image || '',
                        existing_video: p.video || ''
                    };
                    this.imagePreview = null;
                    this.videoPreview = null;
                    this.imageFile = null;
                    this.videoFile = null;
                    if(this.$refs.imageInput) this.$refs.imageInput.value = '';
                    if(this.$refs.videoInput) this.$refs.videoInput.value = '';
                    this.currentView = 'form';
                },

                handleImage(e) {
                    let file = e.target.files[0];
                    if (!file) return;
                    if (file.size > 2 * 1024 * 1024) {
                        alert('Ukuran gambar maksimal 2MB!');
                        e.target.value = '';
                        return;
                    }
                    this.imageFile = file;
                    this.imagePreview = URL.createObjectURL(file);
                },

                handleVideo(e) {
                    let file = e.target.files[0];
                    if (!file) return;
                    if (file.size > 15 * 1024 * 1024) {
                        alert('Ukuran video maksimal 15MB!');
                        e.target.value = '';
                        return;
                    }
                    this.videoFile = file;
                    this.videoPreview = true;
                },

                async saveProduct() {
                    if (!this.form.name || !this.form.price) {
                        alert('Mohon isi nama dan harga produk');
                        return;
                    }
                    if (!this.editingId && !this.imageFile) {
                        alert('Mohon unggah foto produk untuk produk baru!');
                        return;
                    }

                    this.isSaving = true;
                    let fd = new FormData();
                    fd.append('id', this.form.id);
                    fd.append('name', this.form.name);
                    fd.append('category', this.form.category);
                    fd.append('subCategory', this.form.subCategory);
                    fd.append('price', this.form.price);
                    fd.append('priceRange', this.form.priceRange);
                    fd.append('description', this.form.description);
                    fd.append('existing_image', this.form.existing_image);
                    fd.append('existing_video', this.form.existing_video);

                    if (this.imageFile) fd.append('image', this.imageFile);
                    if (this.videoFile) fd.append('video', this.videoFile);

                    this.form.features.forEach(f => {
                        if (f.trim()) fd.append('features[]', f);
                    });

                    this.form.specs.forEach(s => {
                        if (s.key.trim() && s.val.trim()) {
                            fd.append('specs_keys[]', s.key);
                            fd.append('specs_vals[]', s.val);
                        }
                    });

                    try {
                        let res = await fetch('api.php?action=save_product', { method: 'POST', body: fd });
                        let data = await res.json();
                        if (res.ok && data.success) {
                            alert('Produk berhasil disimpan!');
                            this.loadProducts();
                            this.currentView = 'list';
                        } else {
                            alert(data.error || 'Gagal menyimpan produk');
                        }
                    } catch (e) {
                        alert('Terjadi kesalahan jaringan');
                    }
                    this.isSaving = false;
                },

                async deleteProduct(id) {
                    if (confirm('Yakin ingin menghapus produk ini? Tindakan ini tidak bisa dibatalkan.')) {
                        let fd = new FormData();
                        fd.append('id', id);
                        try {
                            let res = await fetch('api.php?action=delete_product', { method: 'POST', body: fd });
                            if (res.ok) {
                                this.loadProducts();
                            }
                        } catch (e) {
                            alert('Gagal menghapus produk');
                        }
                    }
                }
            }
        }
    </script>
</body>
</html>
