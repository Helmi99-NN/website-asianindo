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
                        sidebar: '#2D2354',
                    }
                }
            }
        }
        window.IS_LOGGED_IN = <?php echo $is_admin ? 'true' : 'false'; ?>;
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .bg-asianindo { background-color: #f8f9fa; }
    </style>
</head>
<body class="bg-asianindo text-gray-800 font-sans" x-data="adminApp()" x-init="initApp()">

    <!-- LOGIN SCREEN -->
    <div x-show="!isLoggedIn" id="loginScreen" x-cloak class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-primary-light rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cogs text-3xl text-primary"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Asianindo CMS</h1>
                <p class="text-sm text-gray-500">Silakan login untuk mengelola konten</p>
            </div>
            
            <div x-show="loginError" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm" x-text="loginError"></div>
            
            <form @submit.prevent="login">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                    <input type="text" x-model="loginForm.username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <input type="password" x-model="loginForm.password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                </div>
                <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline flex items-center justify-center transition-colors" :disabled="isLoading">
                    <span x-show="!isLoading">Login</span>
                    <span x-show="isLoading"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
            </form>
        </div>
    </div>

    <!-- DASHBOARD CMS -->
    <div x-show="isLoggedIn" x-cloak id="dashboardScreen" class="flex h-screen overflow-hidden">
        
        <!-- Sidebar Navigation -->
        <aside class="w-64 bg-sidebar text-white flex flex-col transition-all duration-300">
            <div class="p-6 border-b border-white/10">
                <h2 class="text-2xl font-bold flex items-center gap-2">
                    <i class="fas fa-cogs text-primary-light"></i>
                    CMS Admin
                </h2>
            </div>
            
            <nav class="flex-1 overflow-y-auto py-4">
                <ul class="space-y-1 px-3">
                    <li>
                        <a href="#" @click.prevent="changeView('dashboard')" :class="currentView === 'dashboard' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white'" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                            <i class="fas fa-chart-pie w-5 text-center"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="#" @click.prevent="changeView('products')" :class="currentView === 'products' || currentView === 'product_form' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white'" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                            <i class="fas fa-box w-5 text-center"></i> Kelola Produk
                        </a>
                    </li>
                    <li>
                        <a href="#" @click.prevent="changeView('articles')" :class="currentView === 'articles' || currentView === 'article_form' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white'" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                            <i class="fas fa-newspaper w-5 text-center"></i> Artikel Blog
                        </a>
                    </li>
                    <li class="pt-4 pb-2 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Halaman Web</li>
                    <li>
                        <a href="#" @click.prevent="changeView('homepage')" :class="currentView === 'homepage' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white'" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                            <i class="fas fa-home w-5 text-center"></i> Beranda
                        </a>
                    </li>
                    <li>
                        <a href="#" @click.prevent="changeView('about')" :class="currentView === 'about' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white'" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                            <i class="fas fa-info-circle w-5 text-center"></i> Tentang Kami
                        </a>
                    </li>
                    <li>
                        <a href="#" @click.prevent="changeView('contact')" :class="currentView === 'contact' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white'" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                            <i class="fas fa-envelope w-5 text-center"></i> Kontak & Maps
                        </a>
                    </li>
                    <li class="pt-4 pb-2 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Sistem</li>
                    <li>
                        <a href="#" @click.prevent="changeView('settings')" :class="currentView === 'settings' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white'" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                            <i class="fas fa-cog w-5 text-center"></i> Pengaturan Umum
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="p-4 border-t border-white/10">
                <button @click="logout" class="flex items-center gap-2 text-gray-300 hover:text-white w-full px-4 py-2 hover:bg-white/10 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col bg-gray-50 overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 z-10 border-b border-gray-200">
                <h1 class="text-xl font-semibold text-gray-800 capitalize" x-text="currentView.replace('_', ' ')"></h1>
                <div class="flex items-center gap-4">
                    <a href="../" target="_blank" class="text-sm text-primary hover:text-primary-hover flex items-center gap-2">
                        <i class="fas fa-external-link-alt"></i> Lihat Website
                    </a>
                </div>
            </header>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto p-8 relative">
                
                <!-- LOADING OVERLAY FOR SAVING -->
                <div x-show="isSaving" class="absolute inset-0 bg-white/70 z-50 flex flex-col items-center justify-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-primary mb-4"></i>
                    <p class="text-lg font-medium text-gray-700">Menyimpan data...</p>
                </div>

                <!-- 1. DASHBOARD VIEW -->
                <div x-show="currentView === 'dashboard'">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Dashboard Analitik & Leads</h2>
                    
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4 border-l-4 border-blue-500">
                            <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center text-xl">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Total Pengunjung</p>
                                <p class="text-2xl font-bold text-gray-800" x-text="analytics.visitors || 0"></p>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4 border-l-4 border-green-500">
                            <div class="w-12 h-12 bg-green-50 text-green-500 rounded-full flex items-center justify-center text-xl">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Klik Telepon & WA</p>
                                <p class="text-2xl font-bold text-gray-800" x-text="analytics.wa_clicks || 0"></p>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4 border-l-4 border-purple-500">
                            <div class="w-12 h-12 bg-purple-50 text-purple-500 rounded-full flex items-center justify-center text-xl">
                                <i class="fas fa-box"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Total Produk</p>
                                <p class="text-2xl font-bold text-gray-800" x-text="products.length"></p>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4 border-l-4 border-orange-500">
                            <div class="w-12 h-12 bg-orange-50 text-orange-500 rounded-full flex items-center justify-center text-xl">
                                <i class="fas fa-newspaper"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Total Artikel</p>
                                <p class="text-2xl font-bold text-gray-800" x-text="articles.length"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Popular Products -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                            <h3 class="font-bold text-gray-700">Produk Terpopuler (Banyak Dilihat)</h3>
                        </div>
                        <div class="p-0">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-500 text-sm">
                                        <th class="p-4 font-medium border-b border-gray-100">Nama Produk</th>
                                        <th class="p-4 font-medium border-b border-gray-100 w-32 text-right">Dilihat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="p in getPopularProducts()" :key="p.name">
                                        <tr class="border-b border-gray-50 hover:bg-gray-50">
                                            <td class="p-4 text-gray-800" x-text="p.name"></td>
                                            <td class="p-4 text-gray-600 text-right font-medium" x-text="p.views + ' kali'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="getPopularProducts().length === 0">
                                        <td colspan="2" class="p-8 text-center text-gray-400">Belum ada data analitik</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- TEMP PLACEHOLDER FOR OTHER VIEWS -->
                <div x-show="currentView !== 'dashboard'" class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                    <i class="fas fa-tools text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">Modul Sedang Dibangun</h3>
                    <p class="text-gray-500">Form untuk <span x-text="currentView"></span> akan segera tersedia.</p>
                </div>
                
            </div>
        </main>
    </div>

    <script src="app.js"></script>
</body>
</html>
