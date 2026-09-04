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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
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
                    
                    <!-- Media Library -->
                    <li><a href="#" @click.prevent="changeView('media')" :class="currentView==='media' ? 'bg-primary text-white shadow-md' : 'text-gray-300 hover:bg-sidebar-hover'" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all text-sm">
                        <i class="fas fa-images w-5 text-center"></i> Galeri Media
                    </a></li>
                    
                    <li class="pt-4 pb-1 px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest">E-Commerce</li>
                    
                    <!-- Pesanan -->
                    <li><a href="#" @click.prevent="changeView('orders')" :class="currentView==='orders' ? 'bg-primary text-white shadow-md' : 'text-gray-300 hover:bg-sidebar-hover'" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all text-sm">
                        <i class="fas fa-box-open w-5 text-center"></i> Pesanan Masuk
                        <span x-show="ecommerceStats.pending_payment > 0" class="ml-auto bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full" x-text="ecommerceStats.pending_payment"></span>
                    </a></li>
                    
                    <!-- Pembayaran -->
                    <li><a href="#" @click.prevent="changeView('payments')" :class="currentView==='payments' ? 'bg-primary text-white shadow-md' : 'text-gray-300 hover:bg-sidebar-hover'" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all text-sm">
                        <i class="fas fa-money-check-alt w-5 text-center"></i> Verifikasi Pembayaran
                        <span x-show="ecommerceStats.pending_verifications > 0" class="ml-auto bg-orange-500 text-white text-[10px] px-1.5 py-0.5 rounded-full" x-text="ecommerceStats.pending_verifications"></span>
                    </a></li>
                    
                    <!-- Pengiriman -->
                    <li><a href="#" @click.prevent="changeView('shipments')" :class="currentView==='shipments' ? 'bg-primary text-white shadow-md' : 'text-gray-300 hover:bg-sidebar-hover'" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all text-sm">
                        <i class="fas fa-truck w-5 text-center"></i> Pengiriman & Resi
                        <span x-show="ecommerceStats.active_shipments > 0" class="ml-auto bg-blue-500 text-white text-[10px] px-1.5 py-0.5 rounded-full" x-text="ecommerceStats.active_shipments"></span>
                    </a></li>
                    
                    <!-- Pelanggan -->
                    <li><a href="#" @click.prevent="changeView('customers')" :class="currentView==='customers' ? 'bg-primary text-white shadow-md' : 'text-gray-300 hover:bg-sidebar-hover'" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all text-sm">
                        <i class="fas fa-users w-5 text-center"></i> Data Pelanggan
                        <span x-show="ecommerceStats.total_customers > 0" class="ml-auto bg-purple-500/80 text-white text-[10px] px-1.5 py-0.5 rounded-full" x-text="ecommerceStats.total_customers"></span>
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="card p-4 border-l-4 border-l-blue-500 flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center"><i class="fas fa-eye text-base"></i></div>
            <div><p class="text-xs text-gray-500 font-medium">Pengunjung</p><p class="text-xl font-bold" x-text="analytics.visitors||0"></p></div>
        </div>
        <div class="card p-4 border-l-4 border-l-green-500 flex items-center gap-3">
            <div class="w-10 h-10 bg-green-50 text-green-500 rounded-full flex items-center justify-center"><i class="fab fa-whatsapp text-base"></i></div>
            <div><p class="text-xs text-gray-500 font-medium">Klik WA</p><p class="text-xl font-bold" x-text="analytics.wa_clicks||0"></p></div>
        </div>
        <div class="card p-4 border-l-4 border-l-purple-500 flex items-center gap-3">
            <div class="w-10 h-10 bg-purple-50 text-purple-500 rounded-full flex items-center justify-center"><i class="fas fa-box text-base"></i></div>
            <div><p class="text-xs text-gray-500 font-medium">Total Produk</p><p class="text-xl font-bold" x-text="products.length"></p></div>
        </div>
        <div class="card p-4 border-l-4 border-l-amber-500 flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center"><i class="fas fa-newspaper text-base"></i></div>
            <div><p class="text-xs text-gray-500 font-medium">Total Artikel</p><p class="text-xl font-bold" x-text="articles.length"></p></div>
        </div>
        <div class="card p-4 border-l-4 border-l-emerald-500 flex items-center gap-3 cursor-pointer hover:bg-emerald-50/20 transition-colors" @click="changeView('customers')">
            <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center"><i class="fas fa-users text-base"></i></div>
            <div><p class="text-xs text-gray-500 font-medium">Pelanggan</p><p class="text-xl font-bold text-emerald-600" x-text="ecommerceStats.total_customers||0"></p></div>
        </div>
    </div>
    
    <!-- Analytics Chart -->
    <div class="card mb-8">
        <div class="card-header"><h3 class="font-bold text-gray-700"><i class="fas fa-chart-line text-blue-500 mr-2"></i>Tren Pengunjung & Interaksi (30 Hari Terakhir)</h3></div>
        <div class="card-body p-4 relative" style="height: 350px;">
            <canvas id="analyticsChart"></canvas>
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
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative w-72">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                <input type="text" x-model="searchQuery" placeholder="Cari nama atau ID produk..." class="form-input !pl-10">
            </div>
            <select x-model="productCategoryFilter" class="form-input !w-auto text-sm">
                <option value="">Semua Kategori</option>
                <option value="Mesin Industri">Mesin Industri</option>
                <option value="Mesin Pengolahan">Mesin Pengolahan</option>
                <option value="Mesin Pengemasan">Mesin Pengemasan</option>
                <option value="Mesin Pertanian">Mesin Pertanian</option>
                <option value="Mesin Lainnya">Mesin Lainnya</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <!-- Shopee-style Update Massal Button -->
            <button @click="openBulkModal('download')" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg transition-all shadow-sm flex items-center gap-2 text-sm">
                <i class="fas fa-file-excel text-base"></i>
                <span>Update Massal</span>
                <span class="bg-emerald-800/60 text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded">Excel</span>
            </button>
            <button @click="openAddProduct()" class="btn-primary text-sm !py-2"><i class="fas fa-plus"></i> Tambah Produk</button>
        </div>
    </div>

    <!-- Active Selection Floating Bar (Shopee Style) -->
    <div x-show="selectedProductIds.length > 0" x-cloak class="mb-4 bg-gradient-to-r from-emerald-50 to-purple-50 border border-emerald-200 rounded-xl p-3 px-4 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-2.5">
            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold" x-text="selectedProductIds.length"></span>
            <span class="text-sm font-semibold text-gray-700">Produk terpilih untuk perubahan massal</span>
        </div>
        <div class="flex items-center gap-2">
            <button @click="openBulkModal('download', 'selected')" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-xs">
                <i class="fas fa-download"></i> Unduh Template Excel (<span x-text="selectedProductIds.length"></span>)
            </button>
            <button @click="openBulkModal('upload')" class="text-xs bg-primary hover:bg-primary-hover text-white font-semibold px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-xs">
                <i class="fas fa-upload"></i> Unggah Hasil Edit
            </button>
            <button @click="selectedProductIds = []" class="text-xs bg-white hover:bg-gray-100 text-gray-600 border border-gray-200 px-3 py-1.5 rounded-lg transition-colors">
                Batal Pilihan
            </button>
        </div>
    </div>

    <div class="card overflow-hidden">
        <table class="w-full">
            <thead><tr class="bg-gray-50 text-xs text-gray-500 uppercase border-b border-gray-200">
                <th class="p-3 text-center font-medium w-10">
                    <input type="checkbox" :checked="isAllSelected()" @change="toggleSelectAll($event.target.checked)" class="rounded border-gray-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer" title="Pilih Semua Produk">
                </th>
                <th class="p-3 text-center font-medium w-12">No</th>
                <th class="p-3 text-left font-medium w-16">Gambar</th>
                <th class="p-3 text-left font-medium">Nama Produk</th>
                <th class="p-3 text-left font-medium">Kategori</th>
                <th class="p-3 text-right font-medium">Harga</th>
                <th class="p-3 text-center font-medium">Aksi</th>
            </tr></thead>
            <tbody>
                <template x-for="(p, index) in filteredProducts()" :key="p.id || Math.random()">
                    <tr class="border-t border-gray-100 hover:bg-primary-50/30 transition-colors" :class="selectedProductIds.includes(p.id) ? 'bg-emerald-50/40' : ''">
                        <td class="p-3 text-center">
                            <input type="checkbox" :value="p.id" x-model="selectedProductIds" class="rounded border-gray-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer">
                        </td>
                        <td class="p-3 text-center text-sm font-semibold text-gray-500" x-text="index + 1"></td>
                        <td class="p-3"><img :src="p.images && p.images.length > 0 ? (p.images[0].startsWith('http') || p.images[0].startsWith('data:') ? p.images[0] : '../'+p.images[0]) : ''" class="w-14 h-14 object-cover rounded-lg border" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2256%22 height=%2256%22><rect fill=%22%23eee%22 width=%2256%22 height=%2256%22/><text x=%2228%22 y=%2232%22 text-anchor=%22middle%22 fill=%22%23aaa%22 font-size=%2212%22>No img</text></svg>'"></td>
                        <td class="p-3">
                            <div class="font-medium text-sm text-gray-900" x-text="p.name"></div>
                            <div class="text-[11px] text-gray-400 font-mono mt-0.5">ID: <span x-text="p.id"></span></div>
                        </td>
                        <td class="p-3 text-sm text-gray-500" x-text="p.category"></td>
                        <td class="p-3 text-sm text-right font-semibold text-gray-900" x-text="'Rp '+Number(p.price).toLocaleString('id-ID')"></td>
                        <td class="p-3 text-center whitespace-nowrap">
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

        <!-- SEO Optimasi -->
        <div class="card card-body bg-gray-50 border-gray-200 mt-4">
            <h3 class="section-title text-base"><i class="fas fa-search text-blue-500"></i> Optimasi SEO (Pencarian Google)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group"><label class="form-label">Meta Title</label><input type="text" x-model="productForm.meta_title" class="form-input" placeholder="Kosongkan untuk pakai Nama Produk"></div>
                <div class="form-group"><label class="form-label">Slug URL</label><input type="text" x-model="productForm.slug" class="form-input" placeholder="otomatis-dibuat-jika-kosong"></div>
                <div class="form-group md:col-span-2"><label class="form-label">Meta Description</label><textarea x-model="productForm.meta_description" class="form-textarea" rows="2" placeholder="Tuliskan deskripsi singkat untuk pencarian Google..."></textarea></div>
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
            <input type="text" x-model="articleSearch" placeholder="Cari artikel..." class="form-input !pl-10">
        </div>
        <button @click="openAddArticle()" class="btn-primary"><i class="fas fa-plus"></i> Tulis Artikel</button>
    </div>
    <div class="card overflow-hidden">
        <table class="w-full">
            <thead><tr class="bg-gray-50 text-xs text-gray-500 uppercase">
                <th class="p-4 text-center font-medium w-16">No</th>
                <th class="p-4 text-left font-medium">Judul</th>
                <th class="p-4 text-left font-medium">Kategori</th>
                <th class="p-4 text-left font-medium">Tanggal</th>
                <th class="p-4 text-center font-medium">Dilihat</th>
                <th class="p-4 text-center font-medium">Aksi</th>
            </tr></thead>
            <tbody>
                <template x-for="(a, index) in filteredArticles()" :key="a.id || Math.random()">
                    <tr class="border-t border-gray-100 hover:bg-primary-50/30">
                        <td class="p-4 text-center text-sm font-semibold text-gray-500" x-text="index + 1"></td>
                        <td class="p-4 font-medium text-sm" x-text="a.title"></td>
                        <td class="p-4 text-sm"><span class="bg-primary-light text-primary px-2 py-1 rounded-full text-xs font-medium" x-text="a.category||'Umum'"></span></td>
                        <td class="p-4 text-sm text-gray-500" x-text="a.publish_date||'-'"></td>
                        <td class="p-4 text-center text-sm text-gray-500 font-semibold"><i class="far fa-eye mr-1"></i><span x-text="a.views||0"></span></td>
                        <td class="p-4 text-center flex items-center justify-center gap-1">
                            <a :href="'../article.html?id=' + a.id" target="_blank" class="text-green-500 hover:text-green-700 p-1.5" title="Lihat di Website"><i class="fas fa-external-link-alt"></i></a>
                            <a :href="'https://www.google.com/search?q=site:asianindomachine.com+' + encodeURIComponent(a.title)" target="_blank" class="text-blue-500 hover:text-blue-700 p-1.5" title="Cari di Google"><i class="fab fa-google"></i></a>
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
        <!-- SEO Optimasi -->
        <div class="card card-body bg-gray-50 border-gray-200 mt-4">
            <h3 class="section-title text-base"><i class="fas fa-search text-blue-500"></i> Optimasi SEO (Pencarian Google)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group"><label class="form-label">Meta Title</label><input type="text" x-model="articleForm.meta_title" class="form-input" placeholder="Kosongkan untuk pakai Judul Artikel"></div>
                <div class="form-group"><label class="form-label">Slug URL</label><input type="text" x-model="articleForm.slug" class="form-input" placeholder="otomatis-dibuat-jika-kosong"></div>
                <div class="form-group md:col-span-2"><label class="form-label">Meta Description</label><textarea x-model="articleForm.meta_description" class="form-textarea" rows="2" placeholder="Tuliskan deskripsi singkat untuk pencarian Google..."></textarea></div>
            </div>
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
<!-- MEDIA LIBRARY -->
<!-- ================================================================ -->
<div x-show="currentView==='media'">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div class="relative w-full md:w-96">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            <input type="text" x-model="mediaSearch" class="form-input pl-10" placeholder="Cari nama gambar...">
        </div>
        <div class="flex items-center gap-2">
            <label class="btn-primary cursor-pointer relative overflow-hidden">
                <i class="fas fa-upload" x-show="!isUploadingMedia"></i>
                <i class="fas fa-spinner fa-spin" x-show="isUploadingMedia"></i>
                <span x-text="isUploadingMedia ? 'Mengunggah...' : 'Unggah Gambar'"></span>
                <input type="file" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" @change="uploadMediaToLibrary" :disabled="isUploadingMedia">
            </label>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div x-show="filteredMedia().length === 0" class="text-center py-12 text-gray-500">
            <i class="fas fa-images text-4xl mb-3 text-gray-300"></i>
            <p>Belum ada gambar atau tidak ditemukan.</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <template x-for="m in filteredMedia()" :key="m.name">
                <div class="group relative rounded-lg border border-gray-200 overflow-hidden bg-gray-50 hover:shadow-md transition-all flex flex-col">
                    <div class="h-32 w-full overflow-hidden flex items-center justify-center bg-gray-100">
                        <img :src="'../' + m.path" class="object-cover h-full w-full" loading="lazy">
                    </div>
                    <div class="p-2 text-xs flex-1 border-t border-gray-100">
                        <p class="font-medium text-gray-800 truncate" :title="m.name" x-text="m.name"></p>
                        <p class="text-gray-500 mt-0.5" x-text="formatBytes(m.size)"></p>
                    </div>
                    
                    <!-- Overlay Actions -->
                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-3 backdrop-blur-sm">
                        <div class="flex gap-2">
                            <button @click="copyToClipboard(window.location.origin + '/' + m.path)" class="w-9 h-9 rounded-full bg-white text-gray-800 hover:text-blue-500 hover:scale-110 transition-transform flex items-center justify-center shadow-lg" title="Salin URL Gambar">
                                <i class="fas fa-link"></i>
                            </button>
                            <button @click="deleteMedia(m.name)" class="w-9 h-9 rounded-full bg-red-500 text-white hover:bg-red-600 hover:scale-110 transition-transform flex items-center justify-center shadow-lg" title="Hapus Permanen">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

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

        <!-- Midtrans Payment Gateway Settings -->
        <div class="card card-body mb-6">
            <h3 class="section-title"><i class="fas fa-credit-card text-purple-600"></i> Payment Gateway Midtrans</h3>
            <p class="text-xs text-gray-500 mb-4">Integrasi pembayaran otomatis Virtual Account (BCA, Mandiri, BRI, BNI, Permata, CIMB), QRIS (GoPay, ShopeePay, m-Banking), & Kartu Kredit. Biaya fee dibebankan ke pembeli.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="form-group">
                    <label class="form-label">Midtrans Server Key</label>
                    <input type="password" x-model="settings.midtrans_server_key" class="form-input" placeholder="SB-Mid-server-xxxx... atau Mid-server-xxxx...">
                </div>
                <div class="form-group">
                    <label class="form-label">Midtrans Client Key</label>
                    <input type="text" x-model="settings.midtrans_client_key" class="form-input" placeholder="SB-Mid-client-xxxx... atau Mid-client-xxxx...">
                </div>
                <div class="form-group">
                    <label class="form-label">Mode Lingkungan (Environment)</label>
                    <select x-model="settings.midtrans_environment" class="form-input">
                        <option value="sandbox">Sandbox (Testing / Uji Coba)</option>
                        <option value="production">Production (Live Transaksi Nyata)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">URL Notification / Webhook (Salin ke Dashboard Midtrans)</label>
                    <div class="flex gap-2">
                        <input type="text" readonly :value="window.location.origin + '/midtrans_callback.php'" class="form-input bg-gray-100 text-xs font-mono select-all">
                        <button type="button" @click="copyToClipboard(window.location.origin + '/midtrans_callback.php')" class="btn-secondary text-xs px-3" title="Salin Webhook URL"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
            </div>
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

        <!-- SEO Beranda -->
        <div class="card card-body mb-6 bg-gray-50 border-gray-200">
            <h3 class="section-title"><i class="fas fa-search text-blue-500"></i> Optimasi SEO Beranda</h3>
            <div class="form-group"><label class="form-label">Meta Title</label><input type="text" x-model="homepage.meta_title" class="form-input" placeholder="CV Asianindo - Solusi Mesin Industri..."></div>
            <div class="form-group"><label class="form-label">Meta Description</label><textarea x-model="homepage.meta_description" class="form-textarea" rows="2" placeholder="Deskripsi untuk pencarian Google..."></textarea></div>
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

        <!-- SEO Tentang Kami -->
        <div class="card card-body mb-6 bg-gray-50 border-gray-200">
            <h3 class="section-title"><i class="fas fa-search text-blue-500"></i> Optimasi SEO Tentang Kami</h3>
            <div class="form-group"><label class="form-label">Meta Title</label><input type="text" x-model="about.meta_title" class="form-input" placeholder="Tentang Kami - CV Asianindo"></div>
            <div class="form-group"><label class="form-label">Meta Description</label><textarea x-model="about.meta_description" class="form-textarea" rows="2" placeholder="Deskripsi untuk pencarian Google..."></textarea></div>
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

        <!-- SEO Kontak -->
        <div class="card card-body mb-6 bg-gray-50 border-gray-200">
            <h3 class="section-title"><i class="fas fa-search text-blue-500"></i> Optimasi SEO Kontak</h3>
            <div class="form-group"><label class="form-label">Meta Title</label><input type="text" x-model="contact.meta_title" class="form-input" placeholder="Hubungi Kami - CV Asianindo"></div>
            <div class="form-group"><label class="form-label">Meta Description</label><textarea x-model="contact.meta_description" class="form-textarea" rows="2" placeholder="Deskripsi untuk pencarian Google..."></textarea></div>
        </div>

        <button @click="saveModule('contact')" class="btn-success"><i class="fas fa-save"></i> Simpan Konten Kontak</button>
    </div>
</div>
<!-- ================================================================ -->
<!-- 10. E-COMMERCE: ORDERS -->
<!-- ================================================================ -->
<div x-show="currentView === 'orders' || currentView === 'payments' || currentView === 'shipments'">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <h2 class="text-xl font-bold flex items-center gap-2">
            <i class="fas" :class="{'fa-box-open text-primary': currentView==='orders', 'fa-money-check-alt text-orange-500': currentView==='payments', 'fa-truck text-blue-500': currentView==='shipments'}"></i>
            <span x-text="currentView === 'orders' ? 'Semua Pesanan' : (currentView === 'payments' ? 'Verifikasi Pembayaran' : 'Pengiriman & Resi')"></span>
        </h2>
        <div class="relative w-full md:w-72">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            <input type="text" x-model="orderSearch" @input.debounce.500ms="loadOrders()" placeholder="Cari No. Pesanan / Nama..." class="form-input !pl-10">
        </div>
    </div>

    <!-- Filters (Only for 'orders' view) -->
    <div x-show="currentView === 'orders'" class="flex overflow-x-auto gap-2 pb-2 mb-4 no-scrollbar">
        <template x-for="stat in ['all', 'pending_payment', 'payment_uploaded', 'payment_verified', 'processing', 'shipped', 'delivered', 'cancelled']">
            <button @click="orderFilter = stat; loadOrders()" 
                    :class="orderFilter === stat ? 'bg-primary text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                    class="px-4 py-2 rounded-full text-xs font-semibold whitespace-nowrap transition-colors">
                <span x-text="getOrderStatusLabel(stat)"></span>
            </button>
        </template>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm text-left whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="p-4 font-medium">No. Pesanan / Tgl</th>
                    <th class="p-4 font-medium">Pelanggan</th>
                    <th class="p-4 font-medium">Metode Pembayaran</th>
                    <th class="p-4 font-medium text-right">Total</th>
                    <th class="p-4 font-medium text-center">Status</th>
                    <th class="p-4 font-medium text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="o in orders" :key="o.id">
                    <tr class="hover:bg-primary-50/30 transition-colors">
                        <td class="p-4">
                            <div class="font-bold text-primary" x-text="o.order_number"></div>
                            <div class="text-xs text-gray-500 mt-1" x-text="formatDate(o.created_at)"></div>
                        </td>
                        <td class="p-4">
                            <div class="font-medium" x-text="o.customer_name"></div>
                            <div class="text-xs text-gray-500" x-text="o.item_count + ' Item'"></div>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-semibold text-gray-800" x-text="o.bank_name || 'Bank BCA'"></span>
                                <span x-show="o.payment_gateway === 'midtrans'" class="text-[10px] bg-purple-100 text-primary font-bold px-1.5 py-0.5 rounded">Midtrans</span>
                            </div>
                            <div class="text-[11px] text-gray-400 mt-0.5" x-show="o.payment_fee > 0" x-text="'Fee: ' + formatRupiah(o.payment_fee)"></div>
                        </td>
                        <td class="p-4 text-right">
                            <div class="font-bold text-gray-900" x-text="formatRupiah(o.total_amount || o.total)"></div>
                            <div class="text-[11px] text-gray-400" x-show="o.bill_amount && o.bill_amount !== o.total" x-text="'Tagihan: ' + formatRupiah(o.bill_amount)"></div>
                        </td>
                        <td class="p-4 text-center">
                            <span class="px-2.5 py-1 text-[11px] font-bold rounded-full"
                                  :class="getOrderStatusBadgeClass(o.status)"
                                  x-text="getOrderStatusLabel(o.status)"></span>
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button @click="openOrderDetailModal(o.id)" class="text-gray-500 hover:text-primary bg-gray-100 hover:bg-primary-50 w-8 h-8 rounded-lg flex items-center justify-center transition-colors" title="Detail Pesanan">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <button x-show="o.status === 'payment_uploaded' || currentView === 'payments'" 
                                        @click="openPaymentModal(o.id)" 
                                        class="text-orange-500 hover:text-white bg-orange-50 hover:bg-orange-500 w-8 h-8 rounded-lg flex items-center justify-center transition-colors" title="Verifikasi Pembayaran">
                                    <i class="fas fa-money-check"></i>
                                </button>

                                <button x-show="['payment_verified','processing','shipped'].includes(o.status) || currentView === 'shipments'" 
                                        @click="openShipmentModal(o.id)" 
                                        class="text-blue-500 hover:text-white bg-blue-50 hover:bg-blue-500 w-8 h-8 rounded-lg flex items-center justify-center transition-colors" title="Input / Update Resi">
                                    <i class="fas fa-truck-loading"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr x-show="orders.length === 0">
                    <td colspan="6" class="p-8 text-center text-gray-400">Tidak ada data pesanan.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- Payment Verification Modal -->
<div x-show="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-cloak>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-lg"><i class="fas fa-money-check-alt text-orange-500 mr-2"></i>Verifikasi Pembayaran</h3>
            <button @click="showPaymentModal = false" class="text-gray-400 hover:text-red-500"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6 overflow-y-auto flex-1" x-show="activeOrder">
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Bukti Transfer -->
                <div class="w-full md:w-1/2">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Bukti Transfer</p>
                    <div class="border rounded-xl overflow-hidden bg-gray-100 flex items-center justify-center cursor-pointer min-h-[300px]">
                        <img :src="activeOrder?.payment?.proof_image ? '../' + activeOrder.payment.proof_image : ''" 
                             class="max-w-full h-auto object-contain max-h-[400px]" 
                             onerror="this.style.display='none'"
                             alt="Bukti Transfer">
                        <span x-show="!activeOrder?.payment?.proof_image" class="text-gray-400">Belum ada bukti upload</span>
                    </div>
                </div>
                <!-- Info Order -->
                <div class="w-full md:w-1/2 space-y-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">No. Pesanan</p>
                        <p class="font-bold text-lg text-primary" x-text="activeOrder?.order_number"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Total Harus Dibayar</p>
                        <p class="font-bold text-xl text-green-600" x-text="activeOrder ? formatRupiah(activeOrder.total_amount) : ''"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Bank Tujuan</p>
                        <p class="font-medium" x-text="activeOrder?.payment?.payment_method || '-'"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Tanggal Upload</p>
                        <p class="font-medium" x-text="activeOrder?.payment?.created_at ? formatDate(activeOrder.payment.created_at) : '-'"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase mb-1">Catatan Admin (Opsional)</p>
                        <textarea x-model="paymentNotes" class="form-textarea text-sm w-full" rows="2" placeholder="Alasan penolakan / catatan acc..."></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-3">
            <button @click="verifyPayment('rejected')" class="btn-danger"><i class="fas fa-times"></i> Tolak Bukti</button>
            <button @click="verifyPayment('verified')" class="btn-success"><i class="fas fa-check"></i> Terima Pembayaran</button>
        </div>
    </div>
</div>

<!-- Shipment Modal -->
<div x-show="showShipmentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-cloak>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-lg"><i class="fas fa-truck text-blue-500 mr-2"></i>Update Pengiriman</h3>
            <button @click="showShipmentModal = false" class="text-gray-400 hover:text-red-500"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <div class="bg-blue-50 text-blue-800 p-3 rounded-lg text-sm mb-4">
                No. Pesanan: <strong x-text="activeOrder?.order_number"></strong>
            </div>
            <div class="form-group">
                <label class="form-label">Ekspedisi</label>
                <input type="text" x-model="shipmentForm.expedition" class="form-input" placeholder="Misal: Indah Kargo">
            </div>
            <div class="form-group">
                <label class="form-label">Nomor Resi</label>
                <input type="text" x-model="shipmentForm.tracking_number" class="form-input" placeholder="Masukkan nomor resi valid">
            </div>
            <div class="form-group">
                <label class="form-label">Status Pengiriman</label>
                <select x-model="shipmentForm.status" class="form-input">
                    <option value="preparing">Sedang Disiapkan</option>
                    <option value="shipped">Dikirim (Dalam Perjalanan)</option>
                    <option value="delivered">Tiba di Tujuan (Selesai)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Estimasi Tiba (Opsional)</label>
                <input type="date" x-model="shipmentForm.estimated_arrival" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Catatan Tambahan</label>
                <textarea x-model="shipmentForm.notes" class="form-textarea" rows="2" placeholder="Catatan logistik..."></textarea>
            </div>
        </div>
        <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-3">
            <button @click="showShipmentModal = false" class="btn-secondary">Batal</button>
            <button @click="saveShipment()" class="btn-primary"><i class="fas fa-save"></i> Simpan Resi</button>
        </div>
    </div>
</div>

<!-- Order Detail Modal -->
<div x-show="showOrderDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-cloak>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-lg"><i class="fas fa-receipt text-primary mr-2"></i>Detail Pesanan <span x-text="activeOrder?.order_number"></span></h3>
            <button @click="showOrderDetailModal = false" class="text-gray-400 hover:text-red-500"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6 overflow-y-auto flex-1 bg-gray-50/50" x-show="activeOrder">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Kiri: Info Customer & Shipping -->
                <div class="md:col-span-1 space-y-6">
                    <div class="card card-body p-4 border border-gray-200 shadow-none">
                        <h4 class="font-bold text-sm uppercase text-gray-500 mb-3 border-b pb-2">Pelanggan</h4>
                        <p class="font-semibold text-gray-800" x-text="activeOrder?.customer_name"></p>
                        <p class="text-sm text-gray-600 mt-1"><i class="fas fa-phone mr-2"></i><span x-text="activeOrder?.customer_phone"></span></p>
                        <p class="text-sm text-gray-600 mt-1"><i class="fas fa-envelope mr-2"></i><span x-text="activeOrder?.customer_email"></span></p>
                    </div>

                    <div class="card card-body p-4 border border-gray-200 shadow-none">
                        <h4 class="font-bold text-sm uppercase text-gray-500 mb-3 border-b pb-2">Alamat Pengiriman</h4>
                        <p class="text-sm text-gray-700 leading-relaxed" x-text="activeOrder?.shipping_address"></p>
                        <p class="text-sm text-gray-700 mt-2 font-semibold">Kota/Kec: <span class="font-normal" x-text="activeOrder?.shipping_city"></span></p>
                        <p class="text-sm text-gray-700 font-semibold">Provinsi: <span class="font-normal" x-text="activeOrder?.shipping_province"></span></p>
                        <p class="text-sm text-gray-700 font-semibold">Kode Pos: <span class="font-normal" x-text="activeOrder?.shipping_postal_code"></span></p>
                    </div>

                    <div class="card card-body p-4 border border-gray-200 shadow-none">
                        <h4 class="font-bold text-sm uppercase text-gray-500 mb-3 border-b pb-2">Logistik & Pengiriman</h4>
                        <p class="text-sm text-gray-700 font-semibold">Kurir: <span class="font-normal" x-text="activeOrder?.shipping_courier || activeOrder?.shipment?.expedition || 'Indah Cargo'"></span></p>
                        <p class="text-sm text-gray-700 font-semibold mt-1">Resi: <span class="font-normal text-blue-600" x-text="activeOrder?.shipment?.tracking_number || 'Belum ada'"></span></p>
                        <p class="text-sm text-gray-700 font-semibold mt-1">Status: <span class="font-normal uppercase" x-text="activeOrder?.shipment?.status || '-'"></span></p>
                    </div>

                    <div class="card card-body p-4 border border-gray-200 shadow-none">
                        <h4 class="font-bold text-sm uppercase text-gray-500 mb-3 border-b pb-2">Pembayaran</h4>
                        <p class="text-sm text-gray-700 font-semibold">Metode: <span class="font-normal" x-text="activeOrder?.payment?.bank_name || activeOrder?.payment?.payment_method || 'Bank BCA'"></span></p>
                        <p class="text-sm text-gray-700 font-semibold mt-1" x-show="activeOrder?.payment?.midtrans_transaction_id">Tx ID Midtrans: <span class="font-normal text-primary font-mono text-xs" x-text="activeOrder?.payment?.midtrans_transaction_id"></span></p>
                        <p class="text-sm text-gray-700 font-semibold mt-1" x-show="activeOrder?.payment?.midtrans_snap_token">Snap Token: <span class="font-normal text-gray-600 font-mono text-xs truncate max-w-[200px] inline-block" x-text="activeOrder?.payment?.midtrans_snap_token"></span></p>
                        <p class="text-sm text-gray-700 font-semibold mt-1">Status Bayar: <span class="font-bold uppercase text-xs px-2 py-0.5 rounded" :class="activeOrder?.payment?.status === 'verified' ? 'bg-green-100 text-green-700' : (activeOrder?.payment?.status === 'uploaded' ? 'bg-orange-100 text-orange-700' : 'bg-yellow-100 text-yellow-700')" x-text="activeOrder?.payment?.status || 'pending'"></span></p>
                    </div>
                </div>

                <!-- Kanan: Items & Ringkasan -->
                <div class="md:col-span-2 space-y-6">
                    <div class="card border border-gray-200 shadow-none overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 text-gray-600">
                                <tr>
                                    <th class="p-3 text-left">Produk</th>
                                    <th class="p-3 text-center">Harga</th>
                                    <th class="p-3 text-center">Qty</th>
                                    <th class="p-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="item in activeOrder?.items" :key="item.id">
                                    <tr>
                                        <td class="p-3">
                                            <p class="font-medium text-gray-800" x-text="item.product_name"></p>
                                        </td>
                                        <td class="p-3 text-center text-gray-600" x-text="formatRupiah(item.price)"></td>
                                        <td class="p-3 text-center text-gray-600" x-text="item.quantity"></td>
                                        <td class="p-3 text-right font-medium text-gray-800" x-text="formatRupiah(item.subtotal || (item.price * item.quantity))"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50 border-t">
                                <tr>
                                    <td colspan="3" class="p-3 text-right text-gray-600">Subtotal Produk</td>
                                    <td class="p-3 text-right font-medium text-gray-800" x-text="formatRupiah((activeOrder?.total_amount || activeOrder?.total) - activeOrder?.shipping_cost)"></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="p-3 text-right text-gray-600 border-none pt-0">Ongkos Kirim</td>
                                    <td class="p-3 text-right font-medium text-gray-800 border-none pt-0" x-text="formatRupiah(activeOrder?.shipping_cost)"></td>
                                </tr>
                                <tr x-show="activeOrder?.payment?.payment_fee > 0">
                                    <td colspan="3" class="p-3 text-right text-gray-600 border-none pt-0">Biaya Layanan Payment Gateway</td>
                                    <td class="p-3 text-right font-medium text-purple-700 border-none pt-0" x-text="formatRupiah(activeOrder?.payment?.payment_fee)"></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="p-3 text-right font-bold text-primary text-base">Total Tagihan Dibayar</td>
                                    <td class="p-3 text-right font-bold text-primary text-base" x-text="formatRupiah(activeOrder?.payment?.amount || activeOrder?.total_amount || activeOrder?.total)"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="flex flex-wrap gap-2 justify-end">
                        <select x-model="orderStatusUpdate" class="form-input w-48 text-sm">
                            <option value="pending_payment">Menunggu Bayar</option>
                            <option value="payment_uploaded">Bukti Diunggah</option>
                            <option value="payment_verified">Pembayaran Terverifikasi</option>
                            <option value="processing">Sedang Diproses</option>
                            <option value="shipped">Dikirim</option>
                            <option value="delivered">Tiba di Tujuan</option>
                            <option value="completed">Selesai</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                        <button @click="updateOrderStatus()" class="btn-primary text-sm"><i class="fas fa-sync-alt"></i> Update Status</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- DATA PELANGGAN TERDAFTAR -->
<!-- ================================================================ -->
<div x-show="currentView === 'customers'" x-cloak>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-xl font-bold flex items-center gap-2 text-gray-800">
                <i class="fas fa-users text-primary"></i>
                <span>Data Pelanggan Terdaftar</span>
                <span class="text-xs bg-purple-100 text-primary font-bold px-2.5 py-0.5 rounded-full" x-text="(customers ? customers.length : 0) + ' Akun'"></span>
            </h2>
            <p class="text-xs text-gray-500 mt-1">Daftar pelanggan yang telah membuat akun di website CV Asianindo.</p>
        </div>
        <div class="relative w-full md:w-80">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            <input type="text" x-model="customerSearch" @input.debounce.300ms="loadCustomers()" placeholder="Cari nama, email, HP, kota..." class="form-input !pl-10">
        </div>
    </div>

    <!-- Stats Summary Pill -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-purple-50 text-primary flex items-center justify-center text-lg"><i class="fas fa-user-check"></i></div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Total Akun Terdaftar</p>
                <p class="text-xl font-bold text-gray-800" x-text="ecommerceStats.total_customers || (customers ? customers.length : 0)"></p>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-green-50 text-green-600 flex items-center justify-center text-lg"><i class="fab fa-whatsapp"></i></div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Kontak WhatsApp Terhubung</p>
                <p class="text-xl font-bold text-gray-800" x-text="customers.filter(c => c.phone).length"></p>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg"><i class="fas fa-shopping-bag"></i></div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Pelanggan Telah Bertransaksi</p>
                <p class="text-xl font-bold text-gray-800" x-text="customers.filter(c => Number(c.order_count) > 0).length"></p>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card overflow-x-auto">
        <table class="w-full text-sm text-left whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="p-4 font-medium w-12 text-center">No</th>
                    <th class="p-4 font-medium">Pelanggan</th>
                    <th class="p-4 font-medium">Kontak WhatsApp / Telp</th>
                    <th class="p-4 font-medium">Alamat / Wilayah</th>
                    <th class="p-4 font-medium text-center">Total Order</th>
                    <th class="p-4 font-medium text-center">Tgl Daftar</th>
                    <th class="p-4 font-medium text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="(c, idx) in filteredCustomers()" :key="c.id">
                    <tr class="hover:bg-primary-50/30 transition-colors">
                        <td class="p-4 text-center text-gray-400 font-medium" x-text="idx + 1"></td>
                        <td class="p-4">
                            <div class="font-bold text-gray-800 flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center flex-shrink-0" x-text="c.name ? c.name.charAt(0).toUpperCase() : 'U'"></span>
                                <div>
                                    <div class="text-sm font-semibold" x-text="c.name"></div>
                                    <div class="text-xs text-gray-500 font-normal mt-0.5" x-text="c.email"></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-700" x-text="c.phone || '-'"></span>
                                <a x-show="c.phone" :href="'https://wa.me/' + (c.phone.startsWith('0') ? '62' + c.phone.slice(1) : c.phone.replace(/[^0-9]/g, ''))" target="_blank" class="inline-flex items-center gap-1 text-[11px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-semibold hover:bg-green-200 transition-colors" title="Hubungi via WhatsApp">
                                    <i class="fab fa-whatsapp text-xs"></i> Chat
                                </a>
                            </div>
                        </td>
                        <td class="p-4">
                            <div class="text-xs font-semibold text-gray-800 truncate max-w-xs" x-text="c.city ? (c.city + (c.province ? ', ' + c.province : '')) : (c.address || '-')"></div>
                            <div class="text-[11px] text-gray-400 truncate max-w-xs mt-0.5" x-show="c.address" x-text="c.address"></div>
                            <div class="text-[11px] font-mono text-primary font-semibold mt-0.5" x-show="c.postal_code" x-text="'Kode Pos: ' + c.postal_code"></div>
                        </td>
                        <td class="p-4 text-center">
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold" :class="Number(c.order_count) > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'" x-text="(c.order_count || 0) + ' Order'"></span>
                            <div class="text-[11px] text-gray-500 mt-0.5 font-medium" x-show="Number(c.total_spent) > 0" x-text="formatRupiah(c.total_spent)"></div>
                        </td>
                        <td class="p-4 text-center text-xs text-gray-500" x-text="formatDate(c.created_at)"></td>
                        <td class="p-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <button @click="openCustomerModal(c)" class="btn-secondary !py-1.5 !px-2.5 text-xs text-primary hover:bg-primary-50" title="Lihat Detail Profil">
                                    <i class="fas fa-eye mr-1"></i> Detail
                                </button>
                                <button x-show="Number(c.order_count) === 0" @click="deleteCustomer(c.id, c.name)" class="text-red-400 hover:text-red-600 p-1.5 text-xs" title="Hapus Akun">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredCustomers().length === 0">
                    <td colspan="7" class="p-12 text-center text-gray-400">
                        <i class="fas fa-user-slash text-3xl mb-2 text-gray-300"></i>
                        <p class="text-sm font-medium">Tidak ada data pelanggan yang cocok.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detail Pelanggan -->
<div x-show="showCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" style="display: none;" x-cloak>
    <div @click.away="showCustomerModal = false" class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6 relative">
        <button @click="showCustomerModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100">
            <i class="fas fa-times text-lg"></i>
        </button>

        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
            <div class="w-12 h-12 rounded-full bg-primary/10 text-primary font-bold text-xl flex items-center justify-center" x-text="selectedCustomer?.name ? selectedCustomer.name.charAt(0).toUpperCase() : 'U'"></div>
            <div>
                <h3 class="text-lg font-bold text-gray-900" x-text="selectedCustomer?.name"></h3>
                <p class="text-xs text-gray-500" x-text="'Terdaftar sejak ' + formatDate(selectedCustomer?.created_at)"></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Email</p>
                <p class="text-sm font-semibold text-gray-800 break-all" x-text="selectedCustomer?.email || '-'"></p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Nomor HP / WhatsApp</p>
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-800" x-text="selectedCustomer?.phone || '-'"></p>
                    <a x-show="selectedCustomer?.phone" :href="'https://wa.me/' + (selectedCustomer.phone.startsWith('0') ? '62' + selectedCustomer.phone.slice(1) : selectedCustomer.phone.replace(/[^0-9]/g, ''))" target="_blank" class="text-xs bg-green-600 text-white px-2.5 py-1 rounded-lg font-medium flex items-center gap-1 hover:bg-green-700">
                        <i class="fab fa-whatsapp"></i> Chat
                    </a>
                </div>
            </div>
            <div class="md:col-span-2 bg-gray-50 p-4 rounded-xl border border-gray-100">
                <p class="text-xs text-gray-500 font-medium mb-1">Alamat Lengkap Pengiriman</p>
                <p class="text-sm text-gray-800" x-text="selectedCustomer?.address || 'Belum ada alamat disimpan'"></p>
                <div class="flex flex-wrap gap-2 mt-2" x-show="selectedCustomer?.city || selectedCustomer?.province || selectedCustomer?.postal_code">
                    <span class="text-xs bg-white px-2.5 py-1 rounded border text-gray-600" x-show="selectedCustomer?.city" x-text="selectedCustomer?.city"></span>
                    <span class="text-xs bg-white px-2.5 py-1 rounded border text-gray-600" x-show="selectedCustomer?.province" x-text="selectedCustomer?.province"></span>
                    <span class="text-xs bg-purple-100 text-primary font-mono font-bold px-2.5 py-1 rounded" x-show="selectedCustomer?.postal_code" x-text="'Kode Pos: ' + selectedCustomer?.postal_code"></span>
                </div>
            </div>
        </div>

        <!-- Riwayat Pesanan Pelanggan -->
        <div>
            <h4 class="font-bold text-sm text-gray-800 mb-3 flex items-center gap-2">
                <i class="fas fa-history text-primary"></i> Riwayat Pesanan Pelanggan
            </h4>
            <div class="border rounded-xl overflow-hidden">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase">
                        <tr>
                            <th class="p-3">No. Pesanan</th>
                            <th class="p-3">Tanggal</th>
                            <th class="p-3 text-right">Total</th>
                            <th class="p-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="ord in (selectedCustomer?.orders || [])" :key="ord.id">
                            <tr>
                                <td class="p-3 font-bold text-primary" x-text="ord.order_number"></td>
                                <td class="p-3 text-gray-500" x-text="formatDate(ord.created_at)"></td>
                                <td class="p-3 text-right font-bold" x-text="formatRupiah(ord.total)"></td>
                                <td class="p-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="getOrderStatusBadgeClass(ord.status)" x-text="getOrderStatusLabel(ord.status)"></span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!selectedCustomer?.orders || selectedCustomer.orders.length === 0">
                            <td colspan="4" class="p-6 text-center text-gray-400">Pelanggan ini belum pernah membuat pesanan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- MODAL: UPDATE PRODUK MASSAL (ALA SHOPEE) -->
<!-- ================================================================ -->
<div x-show="showBulkModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-cloak>
    <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[92vh] flex flex-col overflow-hidden border border-gray-100" @click.away="if(!isProcessingBulk) showBulkModal = false">
        <!-- Header -->
        <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50 to-emerald-50/50">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-xl shadow-sm">
                    <i class="fas fa-file-excel"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                        Update Produk Secara Massal
                        <span class="bg-emerald-100 text-emerald-800 text-xs px-2.5 py-0.5 rounded-full font-semibold">Ala Shopee</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Unduh template Excel dari pilihan produk, edit di spreadsheet, lalu unggah kembali.</p>
                </div>
            </div>
            <button @click="if(!isProcessingBulk) showBulkModal = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Tab Navigation (1. Unduh Template, 2. Unggah & Preview) -->
        <div class="flex border-b border-gray-100 px-6 bg-gray-50/50">
            <button @click="bulkActiveTab = 'download'" class="py-3 px-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2" :class="bulkActiveTab === 'download' ? 'border-emerald-600 text-emerald-700 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'">
                <span class="w-5 h-5 rounded-full text-[11px] flex items-center justify-center font-bold" :class="bulkActiveTab === 'download' ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-600'">1</span>
                Unduh Template Excel
            </button>
            <button @click="bulkActiveTab = 'upload'" class="py-3 px-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2" :class="bulkActiveTab === 'upload' ? 'border-primary text-primary bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'">
                <span class="w-5 h-5 rounded-full text-[11px] flex items-center justify-center font-bold" :class="bulkActiveTab === 'upload' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-600'">2</span>
                Unggah & Tinjau Perubahan
                <span x-show="bulkStats.changed > 0" class="bg-emerald-600 text-white text-[10px] font-bold px-1.5 py-0.2 rounded-full" x-text="bulkStats.changed"></span>
            </button>
        </div>

        <!-- Modal Body Content -->
        <div class="p-6 overflow-y-auto flex-1">
            <!-- TAB 1: UNDUH TEMPLATE -->
            <div x-show="bulkActiveTab === 'download'" class="space-y-6">
                <!-- Pilihan Lingkup Produk -->
                <div>
                    <label class="block text-sm font-bold text-gray-800 mb-2">Pilih Lingkup Produk yang Akan Diunduh:</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="border-2 rounded-xl p-4 cursor-pointer transition-all flex items-start gap-3" :class="bulkExportScope === 'selected' ? 'border-emerald-600 bg-emerald-50/40 shadow-xs' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" value="selected" x-model="bulkExportScope" :disabled="selectedProductIds.length === 0" class="mt-1 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold text-sm text-gray-800 block">Hanya Produk yang Dicentang</span>
                                <span class="text-xs text-gray-500 block mt-0.5" x-text="selectedProductIds.length > 0 ? (selectedProductIds.length + ' produk terpilih di tabel') : 'Belum ada produk yang dicentang di tabel'"></span>
                            </div>
                        </label>

                        <label class="border-2 rounded-xl p-4 cursor-pointer transition-all flex items-start gap-3" :class="bulkExportScope === 'all' ? 'border-emerald-600 bg-emerald-50/40 shadow-xs' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" value="all" x-model="bulkExportScope" class="mt-1 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="font-bold text-sm text-gray-800 block">Semua Produk Katalog</span>
                                <span class="text-xs text-gray-500 block mt-0.5" x-text="'Total ' + products.length + ' produk dalam katalog'"></span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Filter Kategori Tambahan jika Semua Produk -->
                <div x-show="bulkExportScope === 'all'" class="bg-gray-50 p-4 rounded-xl border border-gray-200/80">
                    <label class="block text-xs font-bold text-gray-700 mb-1">Filter Kategori Tertentu (Opsional):</label>
                    <select x-model="bulkCategoryFilter" class="form-input text-xs bg-white">
                        <option value="all">Semua Kategori Produk</option>
                        <option value="Mesin Industri">Mesin Industri</option>
                        <option value="Mesin Pengolahan">Mesin Pengolahan</option>
                        <option value="Mesin Pengemasan">Mesin Pengemasan</option>
                        <option value="Mesin Pertanian">Mesin Pertanian</option>
                        <option value="Mesin Lainnya">Mesin Lainnya</option>
                    </select>
                </div>

                <!-- Petunjuk Pengisian -->
                <div class="bg-amber-50/90 border border-amber-200 rounded-xl p-4 text-xs text-amber-900 space-y-2">
                    <div class="font-bold flex items-center gap-1.5 text-amber-800 text-sm">
                        <i class="fas fa-lightbulb text-amber-500"></i> Petunjuk Pengisian Template Excel Ala Shopee:
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-amber-900/90 leading-relaxed">
                        <li><strong>Kolom "ID Produk (Jangan Diubah)"</strong> adalah kunci produk. Jangan menghapus atau mengubah angka ID ini agar sistem mengenali produk yang diupdate.</li>
                        <li><strong>Kolom "Harga (Rp)"</strong> harus berupa angka murni (contoh: <code class="bg-amber-100 px-1 py-0.5 rounded font-mono font-bold">25000000</code>). Jangan gunakan titik atau tulisan "Rp".</li>
                        <li><strong>Gambar & Video</strong> produk Anda tetap aman dan tidak akan hilang saat Anda mengedit nama, harga, atau deskripsi di Excel.</li>
                        <li>Setelah selesai diedit di Excel, simpan file lalu buka tab <strong>"Unggah & Tinjau Perubahan"</strong>.</li>
                    </ul>
                </div>

                <!-- Tombol Download Template -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <div class="text-xs text-gray-500">
                        Siap mengunduh: <strong class="text-gray-800" x-text="getExportProductsCount() + ' produk'"></strong>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="exportBulkTemplate('csv')" class="btn-secondary text-sm !py-2.5">
                            <i class="fas fa-file-csv text-blue-600"></i> Unduh Format CSV
                        </button>
                        <button @click="exportBulkTemplate('xlsx')" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-6 rounded-lg transition-colors inline-flex items-center gap-2 text-sm shadow-sm">
                            <i class="fas fa-file-excel"></i> Unduh Template Excel (.xlsx)
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB 2: UNGGAH & PREVIEW -->
            <div x-show="bulkActiveTab === 'upload'" class="space-y-5">
                <!-- Dropzone / File Picker -->
                <div class="border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer relative bg-gray-50 hover:bg-emerald-50/20" :class="bulkPreviewData.length > 0 ? 'border-emerald-300' : 'border-gray-300 hover:border-emerald-500'" @dragover.prevent @drop.prevent="handleBulkExcelDrop($event)">
                    <input type="file" accept=".xlsx, .xls, .csv" @change="handleBulkExcelUpload($event)" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                    <div class="space-y-2">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto text-2xl shadow-inner">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div>
                            <p class="font-bold text-sm text-gray-800">
                                <span x-show="!bulkFileName">Tarik & Lepas File Excel ke Sini atau Klik untuk Memilih File</span>
                                <span x-show="bulkFileName" class="text-emerald-700 flex items-center justify-center gap-1.5 font-bold">
                                    <i class="fas fa-check-circle text-emerald-600"></i> <span x-text="bulkFileName"></span>
                                </span>
                            </p>
                            <p class="text-xs text-gray-400 mt-1">Mendukung format .xlsx, .xls, dan .csv</p>
                        </div>
                        <div x-show="bulkFileName" class="pt-1">
                            <span class="text-xs text-primary underline font-medium">Klik untuk ganti file</span>
                        </div>
                    </div>
                </div>

                <!-- Preview Stats Cards -->
                <div x-show="bulkPreviewData.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 text-center">
                        <span class="text-[11px] text-gray-500 font-medium block">Total Baris File</span>
                        <span class="text-lg font-bold text-gray-800" x-text="bulkStats.total"></span>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-center">
                        <span class="text-[11px] text-emerald-700 font-medium block">Akan Diperbarui</span>
                        <span class="text-lg font-bold text-emerald-700" x-text="bulkStats.changed"></span>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 text-center">
                        <span class="text-[11px] text-gray-500 font-medium block">Tidak Ada Perubahan</span>
                        <span class="text-lg font-bold text-gray-500" x-text="bulkStats.unchanged"></span>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-center">
                        <span class="text-[11px] text-red-700 font-medium block">ID Tidak Dikenal</span>
                        <span class="text-lg font-bold text-red-700" x-text="bulkStats.errors"></span>
                    </div>
                </div>

                <!-- Diff Comparison Table -->
                <div x-show="bulkPreviewData.length > 0">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-bold text-xs text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fas fa-list-check text-primary"></i> Rincian Perubahan Produk
                        </h4>
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-gray-600 flex items-center gap-1 cursor-pointer">
                                <input type="checkbox" x-model="bulkShowOnlyChanged" class="rounded border-gray-300 text-primary focus:ring-primary h-3.5 w-3.5">
                                Hanya tampilkan yang berubah
                            </label>
                        </div>
                    </div>

                    <div class="border rounded-xl overflow-hidden max-h-72 overflow-y-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-gray-50 text-gray-500 uppercase sticky top-0 z-10 border-b">
                                <tr>
                                    <th class="p-3">ID Produk</th>
                                    <th class="p-3">Nama Produk</th>
                                    <th class="p-3">Perubahan yang Terdeteksi</th>
                                    <th class="p-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="row in filteredBulkPreviewRows()" :key="row.id || Math.random()">
                                    <tr :class="row.isError ? 'bg-red-50/50' : (row.isChanged ? 'bg-emerald-50/40' : '')">
                                        <td class="p-3 font-mono font-medium text-gray-700" x-text="row.id"></td>
                                        <td class="p-3">
                                            <div class="font-medium text-gray-900" x-text="row.name"></div>
                                            <div class="text-[11px] text-gray-400" x-text="row.category"></div>
                                        </td>
                                        <td class="p-3">
                                            <template x-if="row.isError">
                                                <span class="text-red-600 font-medium" x-text="row.errorMsg"></span>
                                            </template>
                                            <template x-if="!row.isError && row.isChanged">
                                                <div class="space-y-1">
                                                    <template x-for="diff in row.diffs" :key="diff.field">
                                                        <div class="text-[11px]">
                                                            <span class="font-bold text-gray-700" x-text="diff.label + ': '"></span>
                                                            <span class="line-through text-gray-400 mr-1" x-text="diff.oldVal"></span>
                                                            <i class="fas fa-arrow-right text-[9px] text-emerald-600 mx-0.5"></i>
                                                            <span class="font-bold text-emerald-700" x-text="diff.newVal"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="!row.isError && !row.isChanged">
                                                <span class="text-gray-400 italic">Data sama, tidak ada perubahan</span>
                                            </template>
                                        </td>
                                        <td class="p-3 text-center whitespace-nowrap">
                                            <span x-show="row.isError" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">Error</span>
                                            <span x-show="!row.isError && row.isChanged" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Berubah</span>
                                            <span x-show="!row.isError && !row.isChanged" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500">Tetap</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <button @click="resetBulkUpload()" class="text-xs text-gray-500 hover:text-gray-700">
                        <i class="fas fa-redo-alt mr-1"></i> Reset / Bersihkan
                    </button>
                    <div class="flex items-center gap-2">
                        <button @click="showBulkModal = false" class="btn-secondary text-sm !py-2">Tutup</button>
                        <button @click="applyBulkUpdate()" :disabled="bulkStats.changed === 0 || isProcessingBulk" class="bg-primary hover:bg-primary-hover text-white font-semibold py-2 px-6 rounded-lg transition-colors inline-flex items-center gap-2 text-sm shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!isProcessingBulk"><i class="fas fa-check-circle"></i> Terapkan Perubahan Massal (<span x-text="bulkStats.changed"></span>)</span>
                            <span x-show="isProcessingBulk"><i class="fas fa-spinner fa-spin"></i> Menyimpan Perubahan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

            </div><!-- end scrollable content -->
        </main>
    </div><!-- end flex layout -->

    <script src="app.js?v=<?php echo time(); ?>"></script>
</body>
</html>
