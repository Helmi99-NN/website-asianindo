<?php
session_start();
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asianindo CMS Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#330e7a',
                        'primary-hover': '#4a2c91',
                        'primary-light': '#e9ddff',
                        'primary-50': '#f5f0ff',
                        sidebar: '#2D2354',
                        'sidebar-hover': '#3d3364',
                    }
                }
            }
        }
        window.IS_LOGGED_IN = <?php echo $is_admin ? 'true' : 'false'; ?>;
    </script>
    <style type="text/tailwindcss">
        [x-cloak] { display: none !important; }
        .form-label { @apply block text-sm font-semibold text-gray-700 mb-1; }
        .form-input { @apply w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all; }
        .form-textarea { @apply w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none transition-all resize-y; }
        .btn-primary { @apply bg-primary hover:bg-primary-hover text-white font-semibold py-2.5 px-6 rounded-lg transition-colors inline-flex items-center gap-2; }
        .btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 px-6 rounded-lg transition-colors inline-flex items-center gap-2; }
        .btn-danger { @apply bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors inline-flex items-center gap-2 text-sm; }
        .btn-success { @apply bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 px-6 rounded-lg transition-colors inline-flex items-center gap-2; }
        .card { @apply bg-white rounded-xl shadow-sm border border-gray-100; }
        .card-header { @apply px-6 py-4 border-b border-gray-100 flex items-center justify-between; }
        .card-body { @apply p-6; }
        .section-title { @apply text-lg font-bold text-gray-800 mb-4 flex items-center gap-2; }
        .form-group { @apply mb-5; }
        .dynamic-row { @apply flex items-center gap-3 mb-2; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans" x-data="adminApp()" x-init="initApp()">

    <!-- ======================== LOGIN SCREEN ======================== -->
    <div x-show="!isLoggedIn" x-cloak class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary/5 to-primary-light/30">
        <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-gray-100">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-primary-light rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-primary/10">
                    <i class="fas fa-cogs text-3xl text-primary"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Asianindo CMS</h1>
                <p class="text-sm text-gray-500 mt-1">Content Management System</p>
            </div>
            <div x-show="loginError" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm" x-text="loginError"></div>
            <form @submit.prevent="login">
                <div class="mb-4">
                    <label class="form-label">Username</label>
                    <input type="text" x-model="loginForm.username" class="form-input" placeholder="Masukkan username" required>
                </div>
                <div class="mb-6">
                    <label class="form-label">Password</label>
                    <input type="password" x-model="loginForm.password" class="form-input" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn-primary w-full justify-center" :disabled="isLoading">
                    <span x-show="!isLoading">Masuk ke Dashboard</span>
                    <span x-show="isLoading"><i class="fas fa-spinner fa-spin"></i> Loading...</span>
                </button>
            </form>
        </div>
    </div>

    <!-- ======================== MAIN CMS LAYOUT ======================== -->
    <div x-show="isLoggedIn" x-cloak class="flex h-screen overflow-hidden">
        
        <!-- ======== SIDEBAR ======== -->
        <aside class="w-64 bg-sidebar text-white flex flex-col flex-shrink-0">
            <div class="p-5 border-b border-white/10">
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-cogs text-primary-light"></i> CMS Admin
                </h2>
                <p class="text-xs text-gray-400 mt-1">CV Asianindo</p>
            </div>
            <nav class="flex-1 overflow-y-auto py-4">
                <ul class="space-y-0.5 px-3">
                    <!-- Dashboard -->
                    <li><a href="#" @click.prevent="changeView('dashboard')" :class="currentView==='dashboard' ? 'bg-primary text-white shadow-md' : 'text-gray-300 hover:bg-sidebar-hover'" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all text-sm">
                        <i class="fas fa-chart-pie w-5 text-center"></i> Dashboard
                    </a></li>
                    
                    <li class="pt-4 pb-1 px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Konten</li>
                    
                    <!-- Produk -->
                    <li><a href="#" @click.prevent="changeView('products')" :class="currentView==='products'||currentView==='product_form' ? 'bg-primary text-white shadow-md' : 'text-gray-300 hover:bg-sidebar-hover'" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all text-sm">
                        <i class="fas fa-box w-5 text-center"></i> Produk
                        <span class="ml-auto bg-white/20 text-xs px-2 py-0.5 rounded-full" x-text="products.length"></span>
                    </a></li>
                    
                    <!-- Artikel -->
                    <li><a href="#" @click.prevent="changeView('articles')" :class="currentView==='articles'||currentView==='article_form' ? 'bg-primary text-white shadow-md' : 'text-gray-300 hover:bg-sidebar-hover'" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all text-sm">
                        <i class="fas fa-newspaper w-5 text-center"></i> Artikel Blog
                        <span class="ml-auto bg-white/20 text-xs px-2 py-0.5 rounded-full" x-text="articles.length"></span>
                    </a></li>
                    
                    <li class="pt-4 pb-1 px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Halaman Web</li>
                    
                    <!-- Beranda -->
                    <li><a href="#" @click.prevent="changeView('homepage')" :class="currentView==='homepage' ? 'bg-primary text-white shadow-md' : 'text-gray-300 hover:bg-sidebar-hover'" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all text-sm">
                        <i class="fas fa-home w-5 text-center"></i> Beranda
                    </a></li>
                    
                    <!-- Tentang -->
                    <li><a href="#" @click.prevent="changeView('about')" :class="currentView==='about' ? 'bg-primary text-white shadow-md' : 'text-gray-300 hover:bg-sidebar-hover'" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all text-sm">
                        <i class="fas fa-info-circle w-5 text-center"></i> Tentang Kami
                    </a></li>
                    
                    <!-- Kontak -->
                    <li><a href="#" @click.prevent="changeView('contact')" :class="currentView==='contact' ? 'bg-primary text-white shadow-md' : 'text-gray-300 hover:bg-sidebar-hover'" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all text-sm">
                        <i class="fas fa-envelope w-5 text-center"></i> Kontak
                    </a></li>
                    
                    <li class="pt-4 pb-1 px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Sistem</li>
                    
                    <!-- Settings -->
                    <li><a href="#" @click.prevent="changeView('settings')" :class="currentView==='settings' ? 'bg-primary text-white shadow-md' : 'text-gray-300 hover:bg-sidebar-hover'" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all text-sm">
                        <i class="fas fa-cog w-5 text-center"></i> Pengaturan Umum
                    </a></li>
                </ul>
            </nav>
            <div class="p-4 border-t border-white/10">
                <button @click="logout" class="flex items-center gap-2 text-gray-400 hover:text-white w-full px-4 py-2 hover:bg-sidebar-hover rounded-lg transition-all text-sm">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </button>
            </div>
        </aside>

        <!-- ======== MAIN CONTENT ======== -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Header Bar -->
            <header class="bg-white shadow-sm h-14 flex items-center justify-between px-8 border-b border-gray-200 flex-shrink-0">
                <h1 class="text-lg font-bold text-gray-800 capitalize" x-text="currentView.replace('_', ' ')"></h1>
                <a href="../" target="_blank" class="text-sm text-primary hover:text-primary-hover flex items-center gap-1.5 font-medium">
                    <i class="fas fa-external-link-alt text-xs"></i> Lihat Website
                </a>
            </header>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto p-6 lg:p-8">
                <!-- Saving Overlay -->
                <div x-show="isSaving" class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center backdrop-blur-sm">
                    <div class="bg-white rounded-xl p-8 shadow-2xl flex flex-col items-center">
                        <i class="fas fa-spinner fa-spin text-4xl text-primary mb-4"></i>
                        <p class="text-lg font-semibold">Menyimpan...</p>
                    </div>
                </div>

<!-- ================================================================ -->
<!-- 1. DASHBOARD -->
<!-- ================================================================ -->
<div x-show="currentView==='dashboard'">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="card p-5 border-l-4 border-l-blue-500 flex items-center gap-4">
            <div class="w-11 h-11 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center"><i class="fas fa-eye text-lg"></i></div>
            <div><p class="text-xs text-gray-500 font-medium">Total Pengunjung</p><p class="text-2xl font-bold" x-text="analytics.visitors||0"></p></div>
        </div>
        <div class="card p-5 border-l-4 border-l-green-500 flex items-center gap-4">
            <div class="w-11 h-11 bg-green-50 text-green-500 rounded-full flex items-center justify-center"><i class="fab fa-whatsapp text-lg"></i></div>
            <div><p class="text-xs text-gray-500 font-medium">Klik WA / Telepon</p><p class="text-2xl font-bold" x-text="analytics.wa_clicks||0"></p></div>
        </div>
        <div class="card p-5 border-l-4 border-l-purple-500 flex items-center gap-4">
            <div class="w-11 h-11 bg-purple-50 text-purple-500 rounded-full flex items-center justify-center"><i class="fas fa-box text-lg"></i></div>
            <div><p class="text-xs text-gray-500 font-medium">Total Produk</p><p class="text-2xl font-bold" x-text="products.length"></p></div>
        </div>
        <div class="card p-5 border-l-4 border-l-amber-500 flex items-center gap-4">
            <div class="w-11 h-11 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center"><i class="fas fa-newspaper text-lg"></i></div>
            <div><p class="text-xs text-gray-500 font-medium">Total Artikel</p><p class="text-2xl font-bold" x-text="articles.length"></p></div>
        </div>
    </div>
    
    <!-- Storage Info -->
    <div class="card mb-8">
        <div class="card-header"><h3 class="font-bold text-gray-700"><i class="fas fa-hdd text-blue-500 mr-2"></i>Kapasitas Penyimpanan Server (Storage)</h3></div>
        <div class="card-body">
            <div class="flex justify-between items-end mb-2">
                <div>
                    <p class="text-sm text-gray-500">Terpakai</p>
                    <p class="text-2xl font-bold text-gray-800"><span x-text="storage.used_gb"></span> <span class="text-sm font-normal text-gray-500">GB</span></p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Tersisa</p>
                    <p class="text-2xl font-bold text-green-600"><span x-text="storage.free_gb"></span> <span class="text-sm font-normal text-green-600">GB</span></p>
                </div>
            </div>
            
            <div class="w-full bg-gray-200 rounded-full h-4 mb-2 overflow-hidden shadow-inner">
                <div class="h-4 rounded-full transition-all duration-1000 relative" 
                     :class="storage.percent_used > 90 ? 'bg-red-500' : (storage.percent_used > 75 ? 'bg-amber-500' : 'bg-primary')"
                     :style="`width: ${storage.percent_used}%`">
                </div>
            </div>
            
            <div class="flex justify-between text-xs text-gray-500 font-medium">
                <span x-text="storage.percent_used + '% Digunakan'"></span>
                <span>Total: <span x-text="storage.total_gb"></span> GB</span>
            </div>
        </div>
    </div>
    
    <!-- Popular Products Table -->
    <div class="card">
        <div class="card-header"><h3 class="font-bold text-gray-700"><i class="fas fa-fire text-orange-500 mr-2"></i>Produk Terpopuler</h3></div>
        <table class="w-full">
            <thead><tr class="bg-gray-50 text-xs text-gray-500 uppercase"><th class="p-4 text-left font-medium">Nama Produk</th><th class="p-4 text-right font-medium">Dilihat</th></tr></thead>
            <tbody>
                <template x-for="p in getPopularProducts()" :key="p.name">
                    <tr class="border-t border-gray-50 hover:bg-gray-50/50"><td class="p-4 text-sm" x-text="p.name"></td><td class="p-4 text-sm text-right font-semibold text-primary" x-text="p.views+' kali'"></td></tr>
                </template>
                <tr x-show="getPopularProducts().length===0"><td colspan="2" class="p-8 text-center text-gray-400 text-sm">Belum ada data kunjungan produk</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ================================================================ -->
<!-- 2. PRODUCTS LIST -->
<!-- ================================================================ -->
<div x-show="currentView==='products'">
    <div class="flex items-center justify-between mb-6">
        <div class="relative w-72">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            <input type="text" x-model="searchQuery" placeholder="Cari produk..." class="form-input pl-10">
        </div>
        <button @click="openAddProduct()" class="btn-primary"><i class="fas fa-plus"></i> Tambah Produk</button>
    </div>
    <div class="card overflow-hidden">
        <table class="w-full">
            <thead><tr class="bg-gray-50 text-xs text-gray-500 uppercase">
                <th class="p-4 text-left font-medium">Gambar</th>
                <th class="p-4 text-left font-medium">Nama Produk</th>
                <th class="p-4 text-left font-medium">Kategori</th>
                <th class="p-4 text-right font-medium">Harga</th>
                <th class="p-4 text-center font-medium">Aksi</th>
            </tr></thead>
            <tbody>
                <template x-for="p in filteredProducts()" :key="p.id || Math.random()">
                    <tr class="border-t border-gray-100 hover:bg-primary-50/30">
                        <td class="p-3"><img :src="p.images && p.images.length > 0 ? (p.images[0].startsWith('http') || p.images[0].startsWith('data:') ? p.images[0] : '../'+p.images[0]) : ''" class="w-14 h-14 object-cover rounded-lg border" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2256%22 height=%2256%22><rect fill=%22%23eee%22 width=%2256%22 height=%2256%22/><text x=%2228%22 y=%2232%22 text-anchor=%22middle%22 fill=%22%23aaa%22 font-size=%2212%22>No img</text></svg>'"></td>
                        <td class="p-3 font-medium text-sm" x-text="p.name"></td>
                        <td class="p-3 text-sm text-gray-500" x-text="p.category"></td>
                        <td class="p-3 text-sm text-right font-semibold" x-text="'Rp '+Number(p.price).toLocaleString('id-ID')"></td>
                        <td class="p-3 text-center">
                            <button @click="openEditProduct(p)" class="text-primary hover:text-primary-hover p-1.5" title="Edit"><i class="fas fa-edit"></i></button>
                            <button @click="deleteProduct(p.id)" class="text-red-500 hover:text-red-700 p-1.5 ml-1" title="Hapus"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        <div x-show="filteredProducts().length===0" class="p-8 text-center text-gray-400">Tidak ada produk ditemukan</div>
    </div>
</div>

<!-- ================================================================ -->
<!-- 3. PRODUCT FORM -->
<!-- ================================================================ -->
<div x-show="currentView==='product_form'">
    <div class="flex items-center gap-3 mb-6">
        <button @click="changeView('products')" class="btn-secondary text-sm"><i class="fas fa-arrow-left"></i> Kembali</button>
        <h2 class="text-xl font-bold" x-text="editingId ? 'Edit Produk' : 'Tambah Produk Baru'"></h2>
    </div>
    <div class="card card-body max-w-4xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="form-group"><label class="form-label">Nama Produk *</label><input type="text" x-model="productForm.name" class="form-input" placeholder="Contoh: Mesin Vacuum Frying"></div>
            <div class="form-group"><label class="form-label">Kategori</label>
                <select x-model="productForm.category" class="form-input">
                    <option>Mesin Industri</option><option>Mesin Pengolahan</option><option>Mesin Pengemasan</option><option>Mesin Pertanian</option><option>Mesin Lainnya</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Sub Kategori</label><input type="text" x-model="productForm.subCategory" class="form-input" placeholder="Contoh: Vacuum Frying"></div>
            <div class="form-group"><label class="form-label">Harga (Rp) *</label><input type="number" x-model="productForm.price" class="form-input" placeholder="Contoh: 15000000"></div>
            <div class="form-group md:col-span-2"><label class="form-label">Rentang Harga</label><input type="text" x-model="productForm.priceRange" class="form-input" placeholder="Contoh: Rp 10.000.000 - Rp 25.000.000"></div>
        </div>
        
        <div class="form-group"><label class="form-label">Deskripsi Produk</label><textarea x-model="productForm.description" class="form-textarea" rows="4" placeholder="Tuliskan deskripsi lengkap produk..."></textarea></div>
        
        <!-- Features -->
        <div class="form-group">
            <label class="form-label">Fitur Unggulan</label>
            <template x-for="(feat, i) in productForm.features" :key="i">
                <div class="dynamic-row">
                    <input type="text" x-model="productForm.features[i]" class="form-input" placeholder="Contoh: Kapasitas besar hingga 50kg">
                    <button type="button" @click="productForm.features.splice(i,1)" class="text-red-400 hover:text-red-600 flex-shrink-0" x-show="productForm.features.length>1"><i class="fas fa-times-circle"></i></button>
                </div>
            </template>
            <button type="button" @click="productForm.features.push('')" class="text-primary text-sm font-medium hover:underline mt-1"><i class="fas fa-plus mr-1"></i>Tambah Fitur</button>
        </div>
        
        <!-- Specs -->
        <div class="form-group">
            <label class="form-label">Spesifikasi Teknis</label>
            <template x-for="(spec, i) in productForm.specs" :key="i">
                <div class="dynamic-row">
                    <input type="text" x-model="productForm.specs[i].key" class="form-input" placeholder="Label (cth: Kapasitas)">
                    <input type="text" x-model="productForm.specs[i].val" class="form-input" placeholder="Nilai (cth: 50 kg/batch)">
                    <button type="button" @click="productForm.specs.splice(i,1)" class="text-red-400 hover:text-red-600 flex-shrink-0" x-show="productForm.specs.length>1"><i class="fas fa-times-circle"></i></button>
                </div>
            </template>
            <button type="button" @click="productForm.specs.push({key:'',val:''})" class="text-primary text-sm font-medium hover:underline mt-1"><i class="fas fa-plus mr-1"></i>Tambah Spesifikasi</button>
        </div>

        <!-- Image Upload -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="form-group md:col-span-2">
                <label class="form-label">Galeri Foto Produk (Bisa lebih dari 1, Maks 2MB/file)</label>
                <input type="file" accept="image/*" multiple @change="handleMultipleImages($event)" class="form-input text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-primary-light file:text-primary file:font-medium file:cursor-pointer">
                
                <div class="mt-3 flex flex-wrap gap-3" x-show="productForm.images.length > 0">
                    <template x-for="(img, idx) in productForm.images" :key="idx">
                        <div class="relative group">
                            <img :src="img.startsWith('http') || img.startsWith('data:') ? img : ('../'+img)" class="w-32 h-32 object-cover rounded-lg border shadow-sm">
                            <button type="button" @click="productForm.images.splice(idx, 1)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-md">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                    </template>
                </div>
                <p class="text-xs text-gray-400 mt-2"><i class="fas fa-info-circle"></i> Gambar pertama akan menjadi foto sampul utama (cover).</p>
            </div>
            <div class="form-group">
                <label class="form-label">Video Produk (Maks 15MB)</label>
                <input type="file" accept="video/*" @change="handleVideo($event)" class="form-input text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-primary-light file:text-primary file:font-medium file:cursor-pointer">
                <p x-show="videoPreview || productForm.existing_video" class="mt-2 text-sm text-green-600"><i class="fas fa-check-circle mr-1"></i>Video tersedia</p>
            </div>
        </div>

        <div class="flex gap-3 mt-6 pt-6 border-t">
            <button @click="saveProduct()" class="btn-primary"><i class="fas fa-save"></i> Simpan Produk</button>
            <button @click="changeView('products')" class="btn-secondary">Batal</button>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- 4. ARTICLES LIST -->
<!-- ================================================================ -->
<div x-show="currentView==='articles'">
    <div class="flex items-center justify-between mb-6">
        <div class="relative w-72">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            <input type="text" x-model="articleSearch" placeholder="Cari artikel..." class="form-input pl-10">
        </div>
        <button @click="openAddArticle()" class="btn-primary"><i class="fas fa-plus"></i> Tulis Artikel</button>
    </div>
    <div class="card overflow-hidden">
        <table class="w-full">
            <thead><tr class="bg-gray-50 text-xs text-gray-500 uppercase">
                <th class="p-4 text-left font-medium">Judul</th>
                <th class="p-4 text-left font-medium">Kategori</th>
                <th class="p-4 text-left font-medium">Tanggal</th>
                <th class="p-4 text-center font-medium">Aksi</th>
            </tr></thead>
            <tbody>
                <template x-for="a in filteredArticles()" :key="a.id || Math.random()">
                    <tr class="border-t border-gray-100 hover:bg-primary-50/30">
                        <td class="p-4 font-medium text-sm" x-text="a.title"></td>
                        <td class="p-4 text-sm"><span class="bg-primary-light text-primary px-2 py-1 rounded-full text-xs font-medium" x-text="a.category||'Umum'"></span></td>
                        <td class="p-4 text-sm text-gray-500" x-text="a.publish_date||'-'"></td>
                        <td class="p-4 text-center flex items-center justify-center gap-1">
                            <a :href="'../article.html?id=' + a.id" target="_blank" class="text-green-500 hover:text-green-700 p-1.5" title="Lihat di Website"><i class="fas fa-external-link-alt"></i></a>
                            <a :href="'https://www.google.com/search?q=site:asianindomesin.com+' + encodeURIComponent(a.title)" target="_blank" class="text-blue-500 hover:text-blue-700 p-1.5" title="Cari di Google"><i class="fab fa-google"></i></a>
                            <button @click="openEditArticle(a)" class="text-primary hover:text-primary-hover p-1.5" title="Edit"><i class="fas fa-edit"></i></button>
                            <button @click="deleteArticle(a.id)" class="text-red-500 hover:text-red-700 p-1.5" title="Hapus"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        <div x-show="filteredArticles().length===0" class="p-8 text-center text-gray-400">Belum ada artikel</div>
    </div>
</div>

<!-- ================================================================ -->
<!-- 5. ARTICLE FORM -->
<!-- ================================================================ -->
<div x-show="currentView==='article_form'">
    <div class="flex items-center gap-3 mb-6">
        <button @click="changeView('articles')" class="btn-secondary text-sm"><i class="fas fa-arrow-left"></i> Kembali</button>
        <h2 class="text-xl font-bold" x-text="editingId ? 'Edit Artikel' : 'Tulis Artikel Baru'"></h2>
    </div>
    <div class="card card-body max-w-4xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="form-group md:col-span-2"><label class="form-label">Judul Artikel *</label><input type="text" x-model="articleForm.title" class="form-input" placeholder="Judul artikel..."></div>
            <div class="form-group"><label class="form-label">Kategori</label>
                <select x-model="articleForm.category" class="form-input">
                    <option>Edukasi</option><option>Tips & Trik</option><option>Berita</option><option>Panduan</option><option>Teknologi</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Tanggal Publish</label><input type="date" x-model="articleForm.publish_date" class="form-input"></div>
        </div>
        <div class="form-group"><label class="form-label">Ringkasan / Excerpt</label><textarea x-model="articleForm.excerpt" class="form-textarea" rows="2" placeholder="Tuliskan ringkasan singkat artikel..."></textarea></div>
        <div class="form-group">
            <label class="form-label">Cover Image (Maks 2MB)</label>
            <input type="file" accept="image/*" @change="handleImage($event)" class="form-input text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-primary-light file:text-primary file:font-medium file:cursor-pointer">
            <div class="mt-2" x-show="imagePreview || articleForm.existing_image">
                <img :src="imagePreview || ('../'+articleForm.existing_image)" class="w-full max-w-md h-48 object-cover rounded-lg border">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Konten Artikel (HTML)</label>
            <textarea x-model="articleForm.content" class="form-textarea font-mono text-xs" rows="15" placeholder="<h2>Judul Section</h2><p>Isi artikel...</p>"></textarea>
            <p class="text-xs text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Anda bisa menggunakan tag HTML untuk memformat artikel.</p>
        </div>
        <div class="flex gap-3 mt-6 pt-6 border-t">
            <button @click="saveArticle()" class="btn-primary"><i class="fas fa-save"></i> Simpan Artikel</button>
            <button @click="changeView('articles')" class="btn-secondary">Batal</button>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- 6. SETTINGS (Pengaturan Umum) -->
<!-- ================================================================ -->
<div x-show="currentView==='settings'">
    <div class="max-w-4xl">
        <h2 class="text-xl font-bold mb-6"><i class="fas fa-cog text-primary mr-2"></i>Pengaturan Umum Perusahaan</h2>
        
        <!-- Company Info -->
        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-building text-primary"></i> Informasi Perusahaan</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group"><label class="form-label">Nama Perusahaan</label><input type="text" x-model="settings.company_name" class="form-input"></div>
                <div class="form-group"><label class="form-label">Tahun Berdiri</label><input type="text" x-model="settings.year" class="form-input"></div>
                <div class="form-group md:col-span-2"><label class="form-label">Tagline</label><input type="text" x-model="settings.tagline" class="form-input"></div>
                <div class="form-group md:col-span-2"><label class="form-label">Deskripsi Singkat</label><textarea x-model="settings.description" class="form-textarea" rows="3"></textarea></div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-phone-alt text-green-600"></i> Kontak & Komunikasi</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group"><label class="form-label">Nomor WhatsApp</label><input type="text" x-model="settings.whatsapp" class="form-input" placeholder="6285335850517"></div>
                <div class="form-group"><label class="form-label">Email Resmi</label><input type="email" x-model="settings.email" class="form-input"></div>
                <div class="form-group md:col-span-2"><label class="form-label">Pesan Default WA Konsultasi</label><input type="text" x-model="settings.wa_message" class="form-input"></div>
                <div class="form-group md:col-span-2"><label class="form-label">Alamat Lengkap</label><textarea x-model="settings.address" class="form-textarea" rows="2"></textarea></div>
                <div class="form-group"><label class="form-label">Jam Operasional</label><input type="text" x-model="settings.hours" class="form-input"></div>
                <div class="form-group"><label class="form-label">URL Google Maps Embed</label><input type="text" x-model="settings.maps_url" class="form-input" placeholder="https://www.google.com/maps/embed?..."></div>
            </div>
        </div>

        <!-- Social Media -->
        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-share-alt text-blue-600"></i> Media Sosial</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group"><label class="form-label"><i class="fab fa-youtube text-red-500 mr-1"></i>YouTube</label><input type="url" x-model="settings.youtube" class="form-input" placeholder="https://youtube.com/@channel"></div>
                <div class="form-group"><label class="form-label"><i class="fab fa-tiktok mr-1"></i>TikTok</label><input type="url" x-model="settings.tiktok" class="form-input" placeholder="https://tiktok.com/@username"></div>
                <div class="form-group"><label class="form-label"><i class="fab fa-instagram text-pink-500 mr-1"></i>Instagram</label><input type="url" x-model="settings.instagram" class="form-input" placeholder="https://instagram.com/username"></div>
                <div class="form-group"><label class="form-label"><i class="fab fa-facebook text-blue-600 mr-1"></i>Facebook</label><input type="url" x-model="settings.facebook" class="form-input" placeholder="https://facebook.com/page"></div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="card card-body mb-6">
            <div class="form-group"><label class="form-label">Teks Copyright Footer</label><input type="text" x-model="settings.copyright" class="form-input"></div>
        </div>

        <button @click="saveModule('settings')" class="btn-success"><i class="fas fa-save"></i> Simpan Pengaturan</button>
    </div>
</div>

<!-- ================================================================ -->
<!-- 7. HOMEPAGE -->
<!-- ================================================================ -->
<div x-show="currentView==='homepage'">
    <div class="max-w-4xl">
        <h2 class="text-xl font-bold mb-6"><i class="fas fa-home text-primary mr-2"></i>Konten Halaman Beranda</h2>
        
        <!-- Hero Section -->
        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-image text-indigo-500"></i> Hero Banner</h3>
            <div class="form-group"><label class="form-label">Judul Utama (Headline)</label><input type="text" x-model="homepage.hero_title" class="form-input"></div>
            <div class="form-group"><label class="form-label">Sub Judul</label><textarea x-model="homepage.hero_subtitle" class="form-textarea" rows="2"></textarea></div>
        </div>
        
        <!-- Stats -->
        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-chart-bar text-green-600"></i> Counter Stats</h3>
            <template x-for="(stat, i) in homepage.stats" :key="i">
                <div class="dynamic-row">
                    <input type="text" x-model="homepage.stats[i].value" class="form-input w-32" placeholder="500+">
                    <input type="text" x-model="homepage.stats[i].label" class="form-input" placeholder="Mesin Terjual">
                    <button @click="removeStat(i)" class="text-red-400 hover:text-red-600 flex-shrink-0" x-show="homepage.stats.length>1"><i class="fas fa-times-circle"></i></button>
                </div>
            </template>
            <button @click="addStat()" class="text-primary text-sm font-medium hover:underline mt-1"><i class="fas fa-plus mr-1"></i>Tambah Stat</button>
        </div>

        <!-- About Preview -->
        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-info text-blue-500"></i> Section Tentang (di Beranda)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group"><label class="form-label">Badge</label><input type="text" x-model="homepage.about_badge" class="form-input" placeholder="Tentang Kami"></div>
                <div class="form-group"><label class="form-label">Judul</label><input type="text" x-model="homepage.about_title" class="form-input"></div>
            </div>
            <div class="form-group"><label class="form-label">Deskripsi Singkat</label><textarea x-model="homepage.about_desc" class="form-textarea" rows="3"></textarea></div>
            
            <label class="form-label mt-4">Fitur Highlight</label>
            <template x-for="(f, i) in homepage.about_features" :key="i">
                <div class="dynamic-row">
                    <input type="text" x-model="homepage.about_features[i].icon" class="form-input w-40" placeholder="fas fa-star">
                    <input type="text" x-model="homepage.about_features[i].title" class="form-input" placeholder="Judul">
                    <input type="text" x-model="homepage.about_features[i].desc" class="form-input" placeholder="Deskripsi">
                    <button @click="removeAboutFeature(i)" class="text-red-400 hover:text-red-600 flex-shrink-0" x-show="homepage.about_features.length>1"><i class="fas fa-times-circle"></i></button>
                </div>
            </template>
            <button @click="addAboutFeature()" class="text-primary text-sm font-medium hover:underline mt-1"><i class="fas fa-plus mr-1"></i>Tambah Fitur</button>
        </div>

        <!-- Advantages -->
        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-trophy text-yellow-500"></i> Keunggulan (Why Choose Us)</h3>
            <template x-for="(adv, i) in homepage.advantages" :key="i">
                <div class="p-4 bg-gray-50 rounded-lg mb-3">
                    <div class="dynamic-row">
                        <input type="text" x-model="homepage.advantages[i].icon" class="form-input w-40" placeholder="fas fa-shield-alt">
                        <input type="text" x-model="homepage.advantages[i].title" class="form-input" placeholder="Judul Keunggulan">
                        <button @click="removeAdvantage(i)" class="text-red-400 hover:text-red-600 flex-shrink-0"><i class="fas fa-times-circle"></i></button>
                    </div>
                    <input type="text" x-model="homepage.advantages[i].desc" class="form-input mt-2" placeholder="Deskripsi keunggulan...">
                </div>
            </template>
            <button @click="addAdvantage()" class="text-primary text-sm font-medium hover:underline mt-1"><i class="fas fa-plus mr-1"></i>Tambah Keunggulan</button>
        </div>

        <!-- Testimonials -->
        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-quote-left text-pink-500"></i> Testimonial Pelanggan</h3>
            <template x-for="(t, i) in homepage.testimonials" :key="i">
                <div class="p-4 bg-gray-50 rounded-lg mb-3">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-2">
                        <div><label class="text-xs text-gray-500">Nama</label><input type="text" x-model="homepage.testimonials[i].name" class="form-input" placeholder="Nama pelanggan"></div>
                        <div><label class="text-xs text-gray-500">Jabatan/Lokasi</label><input type="text" x-model="homepage.testimonials[i].title" class="form-input" placeholder="Pemilik UD. Jaya"></div>
                        <div><label class="text-xs text-gray-500">Rating (1-5)</label><input type="number" x-model.number="homepage.testimonials[i].rating" min="1" max="5" class="form-input"></div>
                    </div>
                    <textarea x-model="homepage.testimonials[i].text" class="form-textarea" rows="2" placeholder="Isi testimonial pelanggan..."></textarea>
                    <button @click="removeTestimonial(i)" class="text-red-400 text-sm hover:text-red-600 mt-2"><i class="fas fa-trash mr-1"></i>Hapus</button>
                </div>
            </template>
            <button @click="addTestimonial()" class="text-primary text-sm font-medium hover:underline mt-1"><i class="fas fa-plus mr-1"></i>Tambah Testimonial</button>
        </div>

        <!-- CTA -->
        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-bullhorn text-orange-500"></i> CTA Banner</h3>
            <div class="form-group"><label class="form-label">Judul CTA</label><input type="text" x-model="homepage.cta_title" class="form-input"></div>
            <div class="form-group"><label class="form-label">Sub Judul CTA</label><input type="text" x-model="homepage.cta_subtitle" class="form-input"></div>
            <div class="form-group"><label class="form-label">Teks Tombol</label><input type="text" x-model="homepage.cta_button" class="form-input"></div>
        </div>

        <button @click="saveModule('homepage')" class="btn-success"><i class="fas fa-save"></i> Simpan Konten Beranda</button>
    </div>
</div>

<!-- ================================================================ -->
<!-- 8. ABOUT US -->
<!-- ================================================================ -->
<div x-show="currentView==='about'">
    <div class="max-w-4xl">
        <h2 class="text-xl font-bold mb-6"><i class="fas fa-info-circle text-primary mr-2"></i>Konten Halaman Tentang Kami</h2>
        
        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-heading text-indigo-500"></i> Header</h3>
            <div class="form-group"><label class="form-label">Judul Halaman</label><input type="text" x-model="about.hero_title" class="form-input"></div>
            <div class="form-group"><label class="form-label">Deskripsi Header</label><textarea x-model="about.hero_desc" class="form-textarea" rows="2"></textarea></div>
        </div>

        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-id-card text-blue-500"></i> Quick Info</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group"><label class="form-label">Nama Perusahaan</label><input type="text" x-model="about.quick_info.name" class="form-input"></div>
                <div class="form-group"><label class="form-label">Tahun Berdiri</label><input type="text" x-model="about.quick_info.year" class="form-input"></div>
                <div class="form-group"><label class="form-label">Lokasi</label><input type="text" x-model="about.quick_info.address" class="form-input"></div>
                <div class="form-group"><label class="form-label">Bidang Usaha</label><input type="text" x-model="about.quick_info.scope" class="form-input"></div>
            </div>
        </div>

        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-file-alt text-green-600"></i> Profil Perusahaan</h3>
            <div class="form-group"><label class="form-label">Teks Profil (HTML)</label><textarea x-model="about.profile_text" class="form-textarea font-mono text-xs" rows="8" placeholder="<p>Paragraf profil perusahaan...</p>"></textarea></div>
        </div>

        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-eye text-purple-600"></i> Visi & Misi</h3>
            <div class="form-group"><label class="form-label">Visi</label><textarea x-model="about.vision" class="form-textarea" rows="3" placeholder="Visi perusahaan..."></textarea></div>
            <label class="form-label">Misi</label>
            <template x-for="(m, i) in about.missions" :key="i">
                <div class="dynamic-row">
                    <input type="text" x-model="about.missions[i]" class="form-input" placeholder="Poin misi...">
                    <button @click="removeMission(i)" class="text-red-400 hover:text-red-600 flex-shrink-0" x-show="about.missions.length>1"><i class="fas fa-times-circle"></i></button>
                </div>
            </template>
            <button @click="addMission()" class="text-primary text-sm font-medium hover:underline mt-1"><i class="fas fa-plus mr-1"></i>Tambah Misi</button>
        </div>

        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-star text-yellow-500"></i> Produk Unggulan (Highlight)</h3>
            <template x-for="(h, i) in about.highlights" :key="i">
                <div class="p-4 bg-gray-50 rounded-lg mb-3">
                    <div class="dynamic-row">
                        <input type="text" x-model="about.highlights[i].name" class="form-input" placeholder="Nama mesin">
                        <input type="text" x-model="about.highlights[i].desc" class="form-input" placeholder="Deskripsi singkat">
                        <button @click="removeHighlight(i)" class="text-red-400 hover:text-red-600 flex-shrink-0"><i class="fas fa-times-circle"></i></button>
                    </div>
                </div>
            </template>
            <button @click="addHighlight()" class="text-primary text-sm font-medium hover:underline mt-1"><i class="fas fa-plus mr-1"></i>Tambah Highlight</button>
        </div>

        <button @click="saveModule('about')" class="btn-success"><i class="fas fa-save"></i> Simpan Konten Tentang Kami</button>
    </div>
</div>

<!-- ================================================================ -->
<!-- 9. CONTACT -->
<!-- ================================================================ -->
<div x-show="currentView==='contact'">
    <div class="max-w-4xl">
        <h2 class="text-xl font-bold mb-6"><i class="fas fa-envelope text-primary mr-2"></i>Konten Halaman Kontak</h2>
        
        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-heading text-indigo-500"></i> Header</h3>
            <div class="form-group"><label class="form-label">Judul Halaman</label><input type="text" x-model="contact.hero_title" class="form-input"></div>
            <div class="form-group"><label class="form-label">Deskripsi Header</label><textarea x-model="contact.hero_desc" class="form-textarea" rows="2"></textarea></div>
        </div>

        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-map-marker-alt text-red-500"></i> Alamat & Lokasi</h3>
            <div class="form-group"><label class="form-label">Alamat Kantor</label><textarea x-model="contact.office_address" class="form-textarea" rows="2"></textarea></div>
            <div class="form-group"><label class="form-label">Alamat Workshop (opsional)</label><textarea x-model="contact.workshop_address" class="form-textarea" rows="2"></textarea></div>
            <div class="form-group"><label class="form-label">URL Google Maps Embed</label><input type="text" x-model="contact.maps_embed" class="form-input" placeholder="https://www.google.com/maps/embed?..."></div>
        </div>

        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-headset text-green-600"></i> Customer Service</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group"><label class="form-label">Nama CS</label><input type="text" x-model="contact.cs_name" class="form-input"></div>
                <div class="form-group"><label class="form-label">Jam CS</label><input type="text" x-model="contact.cs_hours" class="form-input"></div>
                <div class="form-group"><label class="form-label">Telepon / WA</label><input type="text" x-model="contact.phone" class="form-input"></div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" x-model="contact.email" class="form-input"></div>
            </div>
        </div>

        <button @click="saveModule('contact')" class="btn-success"><i class="fas fa-save"></i> Simpan Konten Kontak</button>
    </div>
</div>

            </div><!-- end scrollable content -->
        </main>
    </div><!-- end flex layout -->

    <script src="app.js"></script>
</body>
</html>
