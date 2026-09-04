function adminApp() {
    return {
        isLoggedIn: false,
        loginForm: { username: '', password: '' },
        loginError: '',
        isLoading: false,
        isSaving: false,
        currentView: 'dashboard',

        // Data Models
        analytics: { visitors: 0, wa_clicks: 0, messages: 0, product_views: {} },
        chartInstance: null,
        products: [],
        searchQuery: '',
        articleSearch: '',
        mediaItems: [],
        isUploadingMedia: false,
        mediaSearch: '',

        // Mass / Bulk Product Update States (Shopee Concept)
        selectedProductIds: [],
        productCategoryFilter: '',
        showBulkModal: false,
        bulkActiveTab: 'download',
        bulkExportScope: 'selected',
        bulkCategoryFilter: 'all',
        bulkFileName: '',
        bulkPreviewData: [],
        bulkStats: { total: 0, changed: 0, unchanged: 0, errors: 0 },
        bulkShowOnlyChanged: false,
        isProcessingBulk: false,
        
        settings: {
            company_name: 'CV Asianindo',
            tagline: 'Solusi Mesin Industri UMKM & Manufaktur',
            description: '',
            whatsapp: '6285335850517',
            wa_message: 'Halo, saya tertarik dengan produk mesin Asianindo. Bisa konsultasi?',
            email: 'cvasianindo@gmail.com',
            address: 'Jl. Kyai Parseh Jaya No.50, Bumiayu, Kec. Kedungkandang, Kota Malang, Jawa Timur',
            hours: 'Senin-Sabtu pukul 09.00-16.00 WIB',
            year: '2018',
            youtube: '',
            tiktok: '',
            instagram: '',
            facebook: '',
            maps_url: '',
            copyright: '© 2024 CV Asianindo Industrial Machinery. All rights reserved.',
            midtrans_server_key: 'SB-Mid-server-TEST_DUMMY_KEY_123456',
            midtrans_client_key: 'SB-Mid-client-TEST_DUMMY_KEY_123456',
            midtrans_environment: 'sandbox'
        },

        articles: [],

        homepage: {
            hero_title: 'Solusi Mesin Industri UMKM & Manufaktur Indonesia',
            hero_subtitle: 'CV Asianindo menyediakan mesin berkualitas tinggi untuk mendukung produktivitas bisnis Anda.',
            hero_image: '',
            stats: [
                { value: '500+', label: 'Mesin Terjual' },
                { value: '9+', label: 'Tahun Pengalaman' },
                { value: '100%', label: 'Garansi Service' }
            ],
            about_badge: 'Tentang Kami',
            about_title: 'Produsen Mesin Industri Terpercaya',
            about_desc: '',
            about_image: 'images/factory_workshop.webp',
            about_features: [
                { icon: 'fas fa-certificate', title: 'TKDN', desc: 'Produk lokal berkualitas' },
                { icon: 'fas fa-users', title: '1000+ Klien', desc: 'Dipercaya seluruh Indonesia' },
                { icon: 'fas fa-industry', title: 'Produksi Lokal', desc: 'Workshop sendiri di Malang' },
                { icon: 'fas fa-headset', title: 'After Sales', desc: 'Layanan purna jual terbaik' }
            ],
            advantages: [
                { icon: 'fas fa-shield-alt', title: 'Garansi Produk', desc: 'Garansi mesin hingga 1 tahun penuh' },
                { icon: 'fas fa-industry', title: 'Produksi Sendiri', desc: 'Diproduksi langsung di workshop kami' },
                { icon: 'fas fa-tags', title: 'Harga Kompetitif', desc: 'Harga terjangkau langsung dari produsen' },
                { icon: 'fas fa-tools', title: 'Dukungan Teknisi', desc: 'Tim teknisi profesional siap membantu' },
                { icon: 'fas fa-book', title: 'Buku Panduan', desc: 'Panduan penggunaan lengkap' },
                { icon: 'fas fa-handshake', title: 'After Sales Service', desc: 'Layanan purna jual terjamin' }
            ],
            testimonials: [
                { rating: 5, text: '', name: '', title: '' }
            ],
            cta_title: 'Siap Meningkatkan Produktivitas Bisnis Anda?',
            cta_subtitle: 'Konsultasikan kebutuhan mesin industri Anda dengan tim kami',
            cta_button: 'Konsultasi Gratis via WhatsApp'
        },

        about: {
            hero_title: 'Tentang CV Asianindo',
            hero_desc: 'Mitra terpercaya untuk kebutuhan mesin industri skala UMKM hingga manufaktur di seluruh Indonesia.',
            quick_info: {
                name: 'CV. Asianindo',
                year: '2018',
                address: 'Kota Malang, Jawa Timur',
                scope: 'Mesin Industri & Pengolahan'
            },
            profile_text: '',
            vision: '',
            missions: [''],
            highlights: [
                { name: '', desc: '', image: '' }
            ],
            workshop_image: 'images/factory_workshop.webp'
        },

        contact: {
            hero_title: 'Hubungi Kami',
            hero_desc: 'Kami siap membantu Anda menemukan solusi mesin industri yang tepat.',
            office_address: 'Jl. Kyai Parseh Jaya No.50, Bumiayu, Kec. Kedungkandang, Kota Malang, Jawa Timur',
            workshop_address: '',
            phone: '+62 853-3585-0517',
            email: 'cvasianindo@gmail.com',
            maps_embed: '',
            cs_name: 'Customer Service Asianindo',
            cs_hours: 'Senin-Sabtu pukul 09.00-16.00 WIB'
        },

        // Product Form
        editingId: null,
        productForm: { id: '', name: '', category: 'Mesin Industri', subCategory: '', price: '', priceRange: '', description: '', features: [''], specs: [{key: '', val: ''}], images: [], existing_video: '', meta_title: '', meta_description: '', slug: '' },
        
        // Article Form
        articleForm: { id: '', title: '', category: '', publish_date: '', excerpt: '', content: '', existing_image: '', meta_title: '', meta_description: '', slug: '' },

        imagePreview: null,
        videoPreview: null,
        imageFile: null,
        videoFile: null,

        // E-Commerce
        orders: [],
        ecommerceStats: { total_orders: 0, pending_payment: 0, pending_verifications: 0, active_shipments: 0, total_sales: 0, total_customers: 0 },
        orderSearch: '',
        orderFilter: 'all',
        activeOrder: null,
        showPaymentModal: false,
        showShipmentModal: false,
        showOrderDetailModal: false,
        paymentNotes: '',
        orderStatusUpdate: '',
        shipmentForm: { expedition: 'Indah Kargo', tracking_number: '', status: 'preparing', estimated_arrival: '', notes: '' },

        // Pelanggan / Customers
        customers: [],
        customerSearch: '',
        selectedCustomer: null,
        showCustomerModal: false,
        isLoadingCustomer: false,

        // ==================== INITIALIZATION ====================
        initApp() {
            if (window.IS_LOGGED_IN) {
                this.isLoggedIn = true;
                this.loadAllData();
            }
        },

        // ==================== AUTH ====================
        async login() {
            this.isLoading = true;
            this.loginError = '';
            const fd = new FormData();
            fd.append('username', this.loginForm.username);
            fd.append('password', this.loginForm.password);
            try {
                let res = await fetch('api.php?action=login', { method: 'POST', body: fd });
                let data = await res.json();
                if (data.success) { this.isLoggedIn = true; this.loadAllData(); }
                else { this.loginError = data.error || 'Gagal login'; }
            } catch (e) { this.loginError = 'Terjadi kesalahan jaringan'; }
            this.isLoading = false;
        },

        async logout() {
            await fetch('api.php?action=logout');
            this.isLoggedIn = false;
            this.loginForm.password = '';
        },

        generateSlug(text) {
            if (!text) return '';
            return text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
        },

        changeView(view) { 
            this.currentView = view; 
            if (view === 'orders') {
                this.orderFilter = 'all';
                this.loadOrders();
            } else if (view === 'payments') {
                this.orderFilter = 'payment_uploaded';
                this.loadOrders();
            } else if (view === 'shipments') {
                this.orderFilter = 'processing'; // or something to show shippable orders
                this.loadOrders();
            } else if (view === 'customers') {
                this.loadCustomers();
            }
        },

        // ==================== DATA LOADING ====================
        async loadAllData() {
            await Promise.all([
                this.loadAnalytics(),
                this.loadProducts(),
                this.loadMedia(),
                this.loadModule('settings'),
                this.loadModule('articles'),
                this.loadModule('homepage'),
                this.loadModule('about'),
                this.loadModule('contact'),
                this.loadEcommerceStats(),
                this.loadOrders(),
                this.loadCustomers()
            ]);
        },

        async loadAnalytics() {
            try { 
                let r = await fetch('api.php?action=get_analytics'); 
                this.analytics = await r.json(); 
                this.$nextTick(() => { this.renderChart(); });
            } catch(e) {}
        },

        renderChart() {
            let ctx = document.getElementById('analyticsChart');
            if(!ctx) return;
            
            let labels = [];
            let visitorsData = [];
            let waData = [];
            
            // Get last 30 days
            let today = new Date();
            for(let i = 29; i >= 0; i--) {
                let d = new Date(today);
                d.setDate(d.getDate() - i);
                let dateStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                let displayDate = d.toLocaleDateString('id-ID', {day: 'numeric', month: 'short'});
                labels.push(displayDate);
                
                if(this.analytics.history && this.analytics.history[dateStr]) {
                    visitorsData.push(this.analytics.history[dateStr].visitors || 0);
                    waData.push(this.analytics.history[dateStr].wa_clicks || 0);
                } else {
                    visitorsData.push(0);
                    waData.push(0);
                }
            }

            if(this.chartInstance) {
                this.chartInstance.destroy();
            }

            this.chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Pengunjung',
                            data: visitorsData,
                            borderColor: '#3b82f6', // blue-500
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Klik WA',
                            data: waData,
                            borderColor: '#22c55e', // green-500
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        },

        async loadProducts() {
            try { let r = await fetch('api.php?action=get_products'); this.products = await r.json(); } catch(e) {}
        },

        async loadMedia() {
            try { let r = await fetch('api.php?action=get_media'); this.mediaItems = await r.json(); } catch(e) {}
        },
        async deleteMedia(filename) {
            if(!confirm('Hapus gambar ini secara permanen?')) return;
            try {
                let r = await fetch('api.php?action=delete_media', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({filename: filename})
                });
                let res = await r.json();
                if(res.success) {
                    this.loadMedia();
                }
            } catch(e) {}
        },
        async uploadMediaToLibrary(event) {
            let files = event.target.files;
            if(!files || files.length === 0) return;
            this.isUploadingMedia = true;
            for(let i=0; i<files.length; i++) {
                let fd = new FormData();
                fd.append('file', files[i]);
                try {
                    await fetch('api.php?action=upload_media', {method: 'POST', body: fd});
                } catch(e) {}
            }
            this.isUploadingMedia = false;
            this.loadMedia();
            event.target.value = ''; 
        },
        copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            alert('URL disalin: ' + text);
        },
        filteredMedia() {
            if(!this.mediaSearch) return this.mediaItems;
            let q = this.mediaSearch.toLowerCase();
            return this.mediaItems.filter(m => m.name.toLowerCase().includes(q));
        },
        formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024, dm = 2, sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        },

        async loadModule(mod) {
            try {
                let r = await fetch('api.php?action=get_module&module=' + mod);
                let data = await r.json();
                // Deep merge with defaults to keep structure
                if (data && typeof data === 'object' && !Array.isArray(data)) {
                    this[mod] = Object.assign({}, this[mod], data);
                } else if (Array.isArray(data)) {
                    this[mod] = data;
                }
            } catch(e) {}
        },

        async saveModule(mod) {
            this.isSaving = true;
            try {
                let res = await fetch('api.php?action=save_module&module=' + mod, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this[mod])
                });
                if (res.ok) alert('Berhasil disimpan!');
                else alert('Gagal menyimpan');
            } catch(e) { alert('Kesalahan jaringan'); }
            this.isSaving = false;
        },

        // ==================== E-COMMERCE FUNCTIONS ====================
        async loadEcommerceStats() {
            try {
                let r = await fetch('api.php?action=get_ecommerce_stats');
                this.ecommerceStats = await r.json();
            } catch(e) {}
        },

        async loadOrders() {
            try {
                let url = `api.php?action=get_admin_orders&status=${this.orderFilter}`;
                if (this.orderSearch) url += `&search=${encodeURIComponent(this.orderSearch)}`;
                let r = await fetch(url);
                this.orders = await r.json();
            } catch(e) {}
        },

        async openPaymentModal(orderId) {
            await this.loadOrderDetail(orderId);
            this.paymentNotes = this.activeOrder?.payment?.admin_notes || '';
            this.showPaymentModal = true;
        },

        async verifyPayment(status) {
            if (!this.activeOrder) return;
            if (status === 'rejected' && !confirm('Yakin menolak bukti transfer ini?')) return;
            if (status === 'verified' && !confirm('Terima pembayaran dan update status?')) return;

            this.isSaving = true;
            try {
                let res = await fetch('api.php?action=verify_payment', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: this.activeOrder.id,
                        status: status,
                        admin_notes: this.paymentNotes
                    })
                });
                let data = await res.json();
                if (data.success) {
                    alert('Pembayaran berhasil diupdate!');
                    this.showPaymentModal = false;
                    this.loadOrders();
                    this.loadEcommerceStats();
                } else {
                    alert(data.error || 'Gagal verifikasi pembayaran');
                }
            } catch(e) { alert('Kesalahan jaringan'); }
            this.isSaving = false;
        },

        async openShipmentModal(orderId) {
            await this.loadOrderDetail(orderId);
            let s = this.activeOrder?.shipment || {};
            this.shipmentForm = {
                expedition: s.expedition || 'Indah Kargo',
                tracking_number: s.tracking_number || '',
                status: s.status || 'preparing',
                estimated_arrival: s.estimated_arrival || '',
                notes: s.notes || ''
            };
            this.showShipmentModal = true;
        },

        async saveShipment() {
            if (!this.activeOrder) return;
            this.isSaving = true;
            try {
                let res = await fetch('api.php?action=update_shipment', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: this.activeOrder.id,
                        ...this.shipmentForm
                    })
                });
                let data = await res.json();
                if (data.success) {
                    alert('Data pengiriman berhasil disimpan!');
                    this.showShipmentModal = false;
                    this.loadOrders();
                    this.loadEcommerceStats();
                } else {
                    alert(data.error || 'Gagal menyimpan pengiriman');
                }
            } catch(e) { alert('Kesalahan jaringan'); }
            this.isSaving = false;
        },

        async openOrderDetailModal(orderId) {
            await this.loadOrderDetail(orderId);
            this.orderStatusUpdate = this.activeOrder.status;
            this.showOrderDetailModal = true;
        },

        async loadOrderDetail(orderId) {
            try {
                let r = await fetch(`api.php?action=get_admin_order_detail&order_id=${orderId}`);
                this.activeOrder = await r.json();
            } catch(e) { alert('Gagal memuat detail pesanan'); }
        },

        async updateOrderStatus() {
            if (!this.activeOrder) return;
            if (!confirm(`Update status pesanan menjadi: ${this.getOrderStatusLabel(this.orderStatusUpdate)}?`)) return;
            
            this.isSaving = true;
            try {
                let res = await fetch('api.php?action=update_order_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: this.activeOrder.id,
                        status: this.orderStatusUpdate
                    })
                });
                let data = await res.json();
                if (data.success) {
                    alert('Status pesanan berhasil diupdate!');
                    this.activeOrder.status = this.orderStatusUpdate;
                    this.loadOrders();
                    this.loadEcommerceStats();
                } else {
                    alert(data.error || 'Gagal update status');
                }
            } catch(e) { alert('Kesalahan jaringan'); }
            this.isSaving = false;
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute:'2-digit' });
        },

        getOrderStatusLabel(status) {
            const map = {
                'all': 'Semua',
                'pending_payment': 'Menunggu Bayar',
                'payment_uploaded': 'Bukti Diunggah',
                'payment_verified': 'Diproses (Lunas)',
                'processing': 'Sedang Diproses',
                'shipped': 'Dikirim',
                'delivered': 'Selesai',
                'completed': 'Selesai',
                'cancelled': 'Dibatalkan'
            };
            return map[status] || status;
        },

        getOrderStatusBadgeClass(status) {
            const map = {
                'pending_payment': 'bg-red-100 text-red-700',
                'payment_uploaded': 'bg-orange-100 text-orange-700',
                'payment_verified': 'bg-blue-100 text-blue-700',
                'processing': 'bg-blue-100 text-blue-700',
                'shipped': 'bg-purple-100 text-purple-700',
                'delivered': 'bg-green-100 text-green-700',
                'completed': 'bg-green-100 text-green-700',
                'cancelled': 'bg-gray-200 text-gray-700'
            };
            return map[status] || 'bg-gray-100 text-gray-700';
        },

        formatRupiah(amount) {
            if (!amount) return 'Rp 0';
            return 'Rp ' + Number(amount).toLocaleString('id-ID');
        },

        // ==================== CUSTOMER MANAGEMENT ====================
        async loadCustomers() {
            try {
                let url = 'api.php?action=get_admin_customers';
                if (this.customerSearch) {
                    url += '&search=' + encodeURIComponent(this.customerSearch);
                }
                let res = await fetch(url);
                this.customers = await res.json();
            } catch(e) {
                console.error('Failed to load customers:', e);
            }
        },

        filteredCustomers() {
            if (!Array.isArray(this.customers)) return [];
            if (!this.customerSearch) return this.customers;
            const q = this.customerSearch.toLowerCase().trim();
            return this.customers.filter(c => 
                (c.name && c.name.toLowerCase().includes(q)) ||
                (c.email && c.email.toLowerCase().includes(q)) ||
                (c.phone && c.phone.includes(q)) ||
                (c.city && c.city.toLowerCase().includes(q)) ||
                (c.province && c.province.toLowerCase().includes(q))
            );
        },

        async openCustomerModal(c) {
            this.selectedCustomer = { ...c, orders: [] };
            this.showCustomerModal = true;
            try {
                let res = await fetch(`api.php?action=get_admin_customer_detail&customer_id=${c.id}`);
                let data = await res.json();
                if (data && data.id) {
                    this.selectedCustomer = data;
                }
            } catch(e) {
                console.error('Failed to load customer detail:', e);
            }
        },

        async deleteCustomer(id, name) {
            if (!confirm(`Yakin ingin menghapus akun pelanggan "${name}"? Tindakan ini tidak dapat dibatalkan.`)) return;
            try {
                let res = await fetch('api.php?action=delete_customer', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ customer_id: id })
                });
                let data = await res.json();
                if (data.success) {
                    alert('Akun pelanggan berhasil dihapus!');
                    this.loadCustomers();
                    this.loadEcommerceStats();
                } else {
                    alert(data.error || 'Gagal menghapus pelanggan');
                }
            } catch(e) {
                alert('Terjadi kesalahan jaringan');
            }
        },

        // ==================== PRODUCT CRUD & BULK UPDATE (ALA SHOPEE) ====================
        filteredProducts() {
            let list = Array.isArray(this.products) ? this.products : Object.values(this.products);
            list = list.filter(p => p && typeof p === 'object');
            if (this.productCategoryFilter) {
                list = list.filter(p => p.category === this.productCategoryFilter);
            }
            if (this.searchQuery) {
                let q = this.searchQuery.toLowerCase();
                list = list.filter(p => (p.name || '').toLowerCase().includes(q) || String(p.id || '').toLowerCase().includes(q));
            }
            return list;
        },

        toggleSelectAll(checked) {
            let visibleProducts = this.filteredProducts();
            if (checked) {
                let ids = visibleProducts.map(p => String(p.id));
                this.selectedProductIds = Array.from(new Set([...this.selectedProductIds, ...ids]));
            } else {
                let visibleIds = new Set(visibleProducts.map(p => String(p.id)));
                this.selectedProductIds = this.selectedProductIds.filter(id => !visibleIds.has(String(id)));
            }
        },

        isAllSelected() {
            let visible = this.filteredProducts();
            if (!visible.length) return false;
            return visible.every(p => this.selectedProductIds.includes(String(p.id)));
        },

        openBulkModal(tab = 'download', scope = null) {
            this.bulkActiveTab = tab;
            if (scope) {
                this.bulkExportScope = scope;
            } else {
                this.bulkExportScope = this.selectedProductIds.length > 0 ? 'selected' : 'all';
            }
            this.showBulkModal = true;
        },

        getExportProductsCount() {
            if (this.bulkExportScope === 'selected') {
                return this.selectedProductIds.length;
            }
            if (this.bulkCategoryFilter && this.bulkCategoryFilter !== 'all') {
                return this.products.filter(p => p && p.category === this.bulkCategoryFilter).length;
            }
            return this.products.length;
        },

        exportBulkTemplate(format = 'xlsx') {
            let itemsToExport = [];
            if (this.bulkExportScope === 'selected') {
                let idSet = new Set(this.selectedProductIds.map(String));
                itemsToExport = this.products.filter(p => p && idSet.has(String(p.id)));
            } else {
                itemsToExport = [...this.products];
                if (this.bulkCategoryFilter && this.bulkCategoryFilter !== 'all') {
                    itemsToExport = itemsToExport.filter(p => p && p.category === this.bulkCategoryFilter);
                }
            }

            if (itemsToExport.length === 0) {
                alert('Tidak ada produk yang dipilih untuk diunduh.');
                return;
            }

            const headers = [
                'ID Produk (Jangan Diubah)',
                'Nama Produk',
                'Harga (Rp)',
                'Kategori',
                'Sub Kategori',
                'Kapasitas',
                'Ukuran Kapasitas (small/medium/large)',
                'Rentang Harga',
                'Deskripsi Produk'
            ];

            const rows = [headers];
            itemsToExport.forEach(p => {
                let rawDesc = p.desc || p.description || '';
                let cleanDesc = rawDesc.replace(/<[^>]*>/g, '').trim();

                rows.push([
                    String(p.id || ''),
                    p.name || '',
                    Number(p.price) || 0,
                    p.category || '',
                    p.subCategory || '',
                    p.capacity || '',
                    p.capacitySize || 'medium',
                    p.priceRange || '',
                    cleanDesc
                ]);
            });

            const now = new Date();
            const dateStr = now.getFullYear() + String(now.getMonth()+1).padStart(2,'0') + String(now.getDate()).padStart(2,'0');

            if (format === 'csv') {
                let csvContent = '\uFEFF';
                rows.forEach(row => {
                    let formattedRow = row.map(val => {
                        let s = String(val ?? '');
                        if (s.includes('"') || s.includes(',') || s.includes('\n') || s.includes('\r')) {
                            return '"' + s.replace(/"/g, '""') + '"';
                        }
                        return s;
                    }).join(',');
                    csvContent += formattedRow + '\r\n';
                });

                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `template_update_produk_asianindo_${dateStr}.csv`;
                link.click();
                return;
            }

            if (typeof XLSX === 'undefined') {
                alert('Library Excel belum siap. Silakan refresh halaman.');
                return;
            }

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(rows);

            ws['!cols'] = [
                { wch: 22 }, // ID Produk
                { wch: 45 }, // Nama Produk
                { wch: 18 }, // Harga (Rp)
                { wch: 20 }, // Kategori
                { wch: 18 }, // Sub Kategori
                { wch: 22 }, // Kapasitas
                { wch: 25 }, // Ukuran Kapasitas
                { wch: 25 }, // Rentang Harga
                { wch: 60 }  // Deskripsi Produk
            ];

            XLSX.utils.book_append_sheet(wb, ws, 'DATA_PRODUK');

            const guideRows = [
                ['PETUNJUK UPDATE PRODUK MASSAL (ALA SHOPEE) - CV ASIANINDO'],
                [''],
                ['1. Kolom ID Produk (Jangan Diubah)', 'Penanda unik produk. JANGAN menghapus atau mengubah angka pada kolom ini.'],
                ['2. Kolom Harga (Rp)', 'Wajib berupa angka bulat murni tanpa titik, koma, atau teks "Rp" (contoh: 25000000).'],
                ['3. Kolom Nama Produk', 'Nama lengkap produk/mesin.'],
                ['4. Kolom Kategori', 'Pilihan: Mesin Industri, Mesin Pengolahan, Mesin Pengemasan, Mesin Pertanian, Mesin Lainnya.'],
                ['5. Kolom Ukuran Kapasitas', 'Pilihan: small, medium, atau large.'],
                ['6. Foto & Video', 'Foto dan video produk Anda tetap tersimpan aman di server dan tidak akan terhapus saat melakukan update massal text/harga.'],
                ['7. Cara Upload', 'Setelah selesai mengubah data di Excel, simpan file (.xlsx atau .csv) lalu buka CMS Asianindo > Update Massal > Tab Unggah & Tinjau Perubahan.']
            ];
            const wsGuide = XLSX.utils.aoa_to_sheet(guideRows);
            wsGuide['!cols'] = [{ wch: 35 }, { wch: 85 }];
            XLSX.utils.book_append_sheet(wb, wsGuide, 'PETUNJUK_PENGISIAN');

            XLSX.writeFile(wb, `template_update_produk_asianindo_${dateStr}.xlsx`);
        },

        handleBulkExcelDrop(e) {
            let dt = e.dataTransfer;
            let files = dt.files;
            if (files && files.length > 0) {
                this.parseExcelFile(files[0]);
            }
        },

        handleBulkExcelUpload(e) {
            let files = e.target.files;
            if (files && files.length > 0) {
                this.parseExcelFile(files[0]);
            }
        },

        resetBulkUpload() {
            this.bulkFileName = '';
            this.bulkPreviewData = [];
            this.bulkStats = { total: 0, changed: 0, unchanged: 0, errors: 0 };
        },

        parseExcelFile(file) {
            if (!file) return;
            this.bulkFileName = file.name;
            this.bulkStats = { total: 0, changed: 0, unchanged: 0, errors: 0 };
            this.bulkPreviewData = [];

            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const data = new Uint8Array(e.target.result);
                    if (typeof XLSX === 'undefined') {
                        alert('Library XLSX belum termuat. Periksa koneksi internet dan refresh halaman.');
                        return;
                    }
                    const workbook = XLSX.read(data, { type: 'array' });
                    let sheetName = workbook.SheetNames.includes('DATA_PRODUK') ? 'DATA_PRODUK' : workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[sheetName];
                    const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });

                    if (!rows || rows.length < 2) {
                        alert('File Excel kosong atau tidak memiliki baris data.');
                        return;
                    }

                    // 1. Locate header row
                    let headerIndex = -1;
                    for (let r = 0; r < Math.min(rows.length, 10); r++) {
                        const rowStr = rows[r].map(c => String(c).toLowerCase()).join(' ');
                        if (rowStr.includes('id') && (rowStr.includes('nama') || rowStr.includes('harga') || rowStr.includes('kategori'))) {
                            headerIndex = r;
                            break;
                        }
                    }

                    if (headerIndex === -1) {
                        headerIndex = 0;
                    }

                    const headers = rows[headerIndex].map(h => String(h).trim().toLowerCase());
                    const colMap = {};
                    headers.forEach((h, idx) => {
                        if (/id(\s*produk)?/i.test(h) && colMap.id === undefined) colMap.id = idx;
                        else if (/nama(\s*produk)?/i.test(h) && colMap.name === undefined) colMap.name = idx;
                        else if (/harga/i.test(h) && colMap.price === undefined) colMap.price = idx;
                        else if (/kategori/i.test(h) && !/sub/i.test(h) && colMap.category === undefined) colMap.category = idx;
                        else if (/sub\s*kategori/i.test(h) && colMap.subCategory === undefined) colMap.subCategory = idx;
                        else if (/ukuran\s*kapasitas/i.test(h) && colMap.capacitySize === undefined) colMap.capacitySize = idx;
                        else if (/kapasitas/i.test(h) && colMap.capacity === undefined) colMap.capacity = idx;
                        else if (/rentang\s*harga/i.test(h) && colMap.priceRange === undefined) colMap.priceRange = idx;
                        else if (/deskripsi/i.test(h) && colMap.desc === undefined) colMap.desc = idx;
                    });

                    // Fallback to position if not detected
                    if (colMap.id === undefined) colMap.id = 0;
                    if (colMap.name === undefined) colMap.name = 1;
                    if (colMap.price === undefined) colMap.price = 2;
                    if (colMap.category === undefined) colMap.category = 3;
                    if (colMap.subCategory === undefined) colMap.subCategory = 4;
                    if (colMap.capacity === undefined) colMap.capacity = 5;
                    if (colMap.capacitySize === undefined) colMap.capacitySize = 6;
                    if (colMap.priceRange === undefined) colMap.priceRange = 7;
                    if (colMap.desc === undefined) colMap.desc = 8;

                    // Build lookup map of current products by ID
                    const currentProductsMap = new Map();
                    this.products.forEach(p => {
                        if (p && p.id) {
                            currentProductsMap.set(String(p.id).trim(), p);
                        }
                    });

                    const previewRows = [];
                    let total = 0, changed = 0, unchanged = 0, errors = 0;

                    for (let r = headerIndex + 1; r < rows.length; r++) {
                        const row = rows[r];
                        if (!row || row.every(c => c === '' || c === null || c === undefined)) {
                            continue;
                        }

                        total++;
                        const rawId = String(row[colMap.id] ?? '').trim();
                        if (!rawId) {
                            errors++;
                            previewRows.push({
                                id: '-',
                                name: String(row[colMap.name] ?? ''),
                                isError: true,
                                errorMsg: 'Baris tidak memiliki ID Produk',
                                isChanged: false,
                                diffs: []
                            });
                            continue;
                        }

                        const existing = currentProductsMap.get(rawId);
                        if (!existing) {
                            errors++;
                            previewRows.push({
                                id: rawId,
                                name: String(row[colMap.name] ?? ''),
                                isError: true,
                                errorMsg: 'ID Produk tidak ditemukan di katalog',
                                isChanged: false,
                                diffs: []
                            });
                            continue;
                        }

                        // Extract new values
                        const newName = String(row[colMap.name] ?? '').trim();
                        let rawPriceVal = row[colMap.price];
                        let cleanPriceStr = String(rawPriceVal ?? '').replace(/[^0-9]/g, '');
                        const newPrice = cleanPriceStr !== '' ? parseInt(cleanPriceStr, 10) : (Number(existing.price) || 0);
                        const newCategory = String(row[colMap.category] ?? '').trim();
                        const newSubCategory = String(row[colMap.subCategory] ?? '').trim();
                        const newCapacity = String(row[colMap.capacity] ?? '').trim();
                        const newCapacitySize = String(row[colMap.capacitySize] ?? '').trim();
                        const newPriceRange = String(row[colMap.priceRange] ?? '').trim();
                        const newDesc = String(row[colMap.desc] ?? '').trim();

                        // Detect Diffs
                        const diffs = [];

                        // 1. Price
                        const oldPrice = Number(existing.price) || 0;
                        if (newPrice > 0 && newPrice !== oldPrice) {
                            diffs.push({
                                field: 'price',
                                label: 'Harga',
                                oldVal: 'Rp ' + oldPrice.toLocaleString('id-ID'),
                                newVal: 'Rp ' + newPrice.toLocaleString('id-ID')
                            });
                        }

                        // 2. Name
                        const oldName = (existing.name || '').trim();
                        if (newName && newName !== oldName) {
                            diffs.push({
                                field: 'name',
                                label: 'Nama Produk',
                                oldVal: oldName,
                                newVal: newName
                            });
                        }

                        // 3. Category
                        const oldCategory = (existing.category || '').trim();
                        if (newCategory && newCategory !== oldCategory) {
                            diffs.push({
                                field: 'category',
                                label: 'Kategori',
                                oldVal: oldCategory,
                                newVal: newCategory
                            });
                        }

                        // 4. Sub Category
                        const oldSubCategory = (existing.subCategory || '').trim();
                        if (newSubCategory && newSubCategory !== oldSubCategory) {
                            diffs.push({
                                field: 'subCategory',
                                label: 'Sub Kategori',
                                oldVal: oldSubCategory,
                                newVal: newSubCategory
                            });
                        }

                        // 5. Capacity
                        const oldCapacity = (existing.capacity || '').trim();
                        if (newCapacity && newCapacity !== oldCapacity) {
                            diffs.push({
                                field: 'capacity',
                                label: 'Kapasitas',
                                oldVal: oldCapacity,
                                newVal: newCapacity
                            });
                        }

                        // 6. Capacity Size
                        const oldCapacitySize = (existing.capacitySize || 'medium').trim();
                        if (newCapacitySize && newCapacitySize !== oldCapacitySize) {
                            diffs.push({
                                field: 'capacitySize',
                                label: 'Ukuran Kapasitas',
                                oldVal: oldCapacitySize,
                                newVal: newCapacitySize
                            });
                        }

                        // 7. Price Range
                        const oldPriceRange = (existing.priceRange || '').trim();
                        if (newPriceRange && newPriceRange !== oldPriceRange) {
                            diffs.push({
                                field: 'priceRange',
                                label: 'Rentang Harga',
                                oldVal: oldPriceRange || '(Kosong)',
                                newVal: newPriceRange
                            });
                        }

                        // 8. Description
                        const oldDesc = (existing.desc || existing.description || '').replace(/<[^>]*>/g, '').trim();
                        if (newDesc && newDesc !== oldDesc) {
                            diffs.push({
                                field: 'desc',
                                label: 'Deskripsi',
                                oldVal: oldDesc.substring(0, 30) + (oldDesc.length > 30 ? '...' : ''),
                                newVal: newDesc.substring(0, 30) + (newDesc.length > 30 ? '...' : '')
                            });
                        }

                        const isChanged = diffs.length > 0;
                        if (isChanged) changed++;
                        else unchanged++;

                        previewRows.push({
                            id: rawId,
                            name: newName || existing.name,
                            category: newCategory || existing.category,
                            subCategory: newSubCategory || existing.subCategory,
                            price: newPrice,
                            capacity: newCapacity || existing.capacity,
                            capacitySize: newCapacitySize || existing.capacitySize,
                            priceRange: newPriceRange || existing.priceRange,
                            desc: newDesc || existing.desc,
                            isError: false,
                            isChanged: isChanged,
                            diffs: diffs
                        });
                    }

                    this.bulkStats = { total, changed, unchanged, errors };
                    this.bulkPreviewData = previewRows;
                    this.bulkActiveTab = 'upload';
                } catch (err) {
                    console.error('Error reading Excel:', err);
                    alert('Gagal membaca file Excel. Pastikan format file valid (.xlsx, .xls, .csv)');
                }
            };
            reader.readAsArrayBuffer(file);
        },

        filteredBulkPreviewRows() {
            if (!this.bulkShowOnlyChanged) return this.bulkPreviewData;
            return this.bulkPreviewData.filter(r => r.isChanged || r.isError);
        },

        async applyBulkUpdate() {
            let changedRows = this.bulkPreviewData.filter(r => r.isChanged && !r.isError);
            if (changedRows.length === 0) {
                alert('Tidak ada perubahan produk yang perlu disimpan.');
                return;
            }

            if (!confirm(`Yakin ingin menerapkan perubahan massal untuk ${changedRows.length} produk? Perubahan akan langsung tersimpan dan tampil di website.`)) {
                return;
            }

            this.isProcessingBulk = true;
            try {
                let payload = changedRows.map(r => ({
                    id: r.id,
                    name: r.name,
                    price: r.price,
                    category: r.category,
                    subCategory: r.subCategory,
                    capacity: r.capacity,
                    capacitySize: r.capacitySize,
                    priceRange: r.priceRange,
                    desc: r.desc
                }));

                let res = await fetch('api.php?action=bulk_update_products', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ products: payload })
                });

                let data = await res.json();
                if (res.ok && data.success) {
                    alert(`Berhasil! ${data.updated_count} produk telah diperbarui secara massal.`);
                    await this.loadProducts();
                    this.showBulkModal = false;
                    this.selectedProductIds = [];
                    this.resetBulkUpload();
                } else {
                    alert(data.error || 'Gagal memperbarui produk.');
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan jaringan saat menyimpan perubahan massal.');
            } finally {
                this.isProcessingBulk = false;
            }
        },

        openAddProduct() {
            this.editingId = null;
            this.productForm = { id: '', name: '', category: 'Mesin Industri', subCategory: '', price: '', priceRange: '', description: '', features: [''], specs: [{key: '', val: ''}], images: [], existing_video: '', meta_title: '', meta_description: '', slug: '' };
            this.resetMedia();
            this.changeView('product_form');
        },

        cleanDesc(raw) {
            if (!raw) return '';
            let str = raw;
            if (str.includes('<li>')) {
                str = str.replace(/<ul[^>]*>/gi, '').replace(/<\/ul>/gi, '');
                let parts = str.split(/<\/li>/gi);
                let items = [];
                parts.forEach(p => {
                    let cleaned = p.replace(/<li[^>]*>/gi, '').replace(/<[^>]*>/g, '').trim();
                    if (cleaned) items.push(cleaned);
                });
                str = items.join('\n');
            } else if (str.includes('<br')) {
                str = str.replace(/<br\s*\/?>/gi, '\n');
            }

            let lines = str.split('\n');
            let finalItems = [];
            lines.forEach(line => {
                let trimmed = line.replace(/<[^>]*>/g, '').trim();
                if (!trimmed) return;
                if (trimmed.includes('·')) {
                    let subItems = trimmed.split('·').map(s => s.trim()).filter(Boolean);
                    subItems.forEach(item => {
                        finalItems.push(item.startsWith('·') || item.startsWith('•') ? item : '· ' + item);
                    });
                } else {
                    finalItems.push(trimmed.startsWith('·') || trimmed.startsWith('•') ? trimmed : '· ' + trimmed);
                }
            });
            return finalItems.join('\n');
        },

        openEditProduct(p) {
            this.editingId = p.id;
            let specs = [];
            if (p.specs) { for (let k in p.specs) specs.push({key: k, val: p.specs[k]}); }
            if (!specs.length) specs.push({key: '', val: ''});
            let rawDesc = p.description || p.desc || '';
            this.productForm = {
                id: p.id, name: p.name, category: p.category || 'Mesin Industri', subCategory: p.subCategory || '',
                price: p.price, priceRange: p.priceRange || '', description: this.cleanDesc(rawDesc),
                features: (p.features && p.features.length) ? [...p.features] : [''],
                specs: specs, images: p.images && p.images.length ? [...p.images] : (p.image ? [p.image] : []), existing_video: p.video || '',
                meta_title: p.meta_title || '', meta_description: p.meta_description || '', slug: p.slug || ''
            };
            this.resetMedia();
            this.changeView('product_form');
        },

        resetMedia() {
            this.videoPreview = null;
            this.videoFile = null;
        },

        async handleMultipleImages(e) {
            let files = e.target.files;
            if (!files || files.length === 0) return;
            this.isSaving = true; // Show loading
            
            for (let i = 0; i < files.length; i++) {
                let f = files[i];
                if (f.size > 2*1024*1024) { alert('Ukuran gambar maksimal 2MB per file!'); continue; }
                
                let fd = new FormData();
                fd.append('file', f);
                try {
                    let res = await fetch('api.php?action=upload_media', { method: 'POST', body: fd });
                    let json = await res.json();
                    if (json.success) {
                        this.productForm.images.push(json.path);
                    } else {
                        alert('Gagal unggah: ' + f.name);
                    }
                } catch(err) {
                    console.error(err);
                }
            }
            e.target.value = ''; // Reset input
            this.isSaving = false;
        },

        handleVideo(e) {
            let f = e.target.files[0]; if (!f) return;
            if (f.size > 15*1024*1024) { alert('Ukuran video maksimal 15MB!'); e.target.value=''; return; }
            this.videoFile = f; this.videoPreview = true;
        },

        async saveProduct() {
            if (!this.productForm.name || !this.productForm.price) { alert('Mohon isi nama dan harga produk'); return; }
            if (this.productForm.images.length === 0) { alert('Mohon unggah minimal 1 foto produk'); return; }
            this.isSaving = true;
            let fd = new FormData();
            for (let key of ['id','name','category','subCategory','price','priceRange','description','existing_video','meta_title','meta_description','slug']) {
                fd.append(key, this.productForm[key] || '');
            }
            fd.append('images', JSON.stringify(this.productForm.images));
            if (this.videoFile) fd.append('video', this.videoFile);
            if (this.videoFile) fd.append('video', this.videoFile);
            this.productForm.features.forEach(f => { if (f.trim()) fd.append('features[]', f); });
            this.productForm.specs.forEach(s => {
                if (s.key.trim() && s.val.trim()) { fd.append('specs_keys[]', s.key); fd.append('specs_vals[]', s.val); }
            });
            try {
                let res = await fetch('api.php?action=save_product', { method: 'POST', body: fd });
                let data = await res.json();
                if (res.ok && data.success) { alert('Produk berhasil disimpan!'); this.loadProducts(); this.changeView('products'); }
                else alert(data.error || 'Gagal menyimpan');
            } catch (e) { alert('Kesalahan jaringan'); }
            this.isSaving = false;
        },

        async deleteProduct(id) {
            if (!confirm('Yakin hapus produk ini?')) return;
            let fd = new FormData(); fd.append('id', id);
            await fetch('api.php?action=delete_product', { method: 'POST', body: fd });
            this.loadProducts();
        },

        // ==================== ARTICLE CRUD ====================
        filteredArticles() {
            let list = Array.isArray(this.articles) ? this.articles : Object.values(this.articles);
            list = list.filter(a => a && typeof a === 'object');
            if (!this.articleSearch) return list;
            return list.filter(a => (a.title || '').toLowerCase().includes(this.articleSearch.toLowerCase()));
        },

        openAddArticle() {
            this.editingId = null;
            this.articleForm = { id: '', title: '', category: 'Edukasi', publish_date: new Date().toISOString().split('T')[0], excerpt: '', content: '', existing_image: '', meta_title: '', meta_description: '', slug: '' };
            this.resetMedia();
            this.changeView('article_form');
        },

        openEditArticle(a) {
            this.editingId = a.id;
            this.articleForm = { ...a, meta_title: a.meta_title || '', meta_description: a.meta_description || '', slug: a.slug || '' };
            this.resetMedia();
            this.changeView('article_form');
        },

        async saveArticle() {
            if (!this.articleForm.title) { alert('Mohon isi judul artikel'); return; }
            this.isSaving = true;
            if (!this.articleForm.slug) {
                this.articleForm.slug = this.generateSlug(this.articleForm.title);
            }
            let id = this.editingId || 'art_' + Date.now();
            let article = { ...this.articleForm, id: id };

            if (this.imageFile) {
                let fd = new FormData(); fd.append('file', this.imageFile);
                try {
                    let res = await fetch('api.php?action=upload_media', { method: 'POST', body: fd });
                    let data = await res.json();
                    if (data.success) article.existing_image = data.path;
                } catch(e) {}
            }

            let list = [...this.articles];
            let idx = list.findIndex(a => a.id === id);
            if (idx > -1) list[idx] = article; else list.push(article);
            this.articles = list;
            await this.saveModule('articles');
            this.changeView('articles');
            this.isSaving = false;
        },

        async deleteArticle(id) {
            if (!confirm('Yakin hapus artikel ini?')) return;
            this.articles = this.articles.filter(a => a.id !== id);
            await this.saveModule('articles');
        },

        // ==================== HOMEPAGE HELPERS ====================
        addStat() { this.homepage.stats.push({ value: '', label: '' }); },
        removeStat(i) { this.homepage.stats.splice(i, 1); },
        addAdvantage() { this.homepage.advantages.push({ icon: 'fas fa-star', title: '', desc: '' }); },
        removeAdvantage(i) { this.homepage.advantages.splice(i, 1); },
        addTestimonial() { this.homepage.testimonials.push({ rating: 5, text: '', name: '', title: '' }); },
        removeTestimonial(i) { this.homepage.testimonials.splice(i, 1); },
        addAboutFeature() { this.homepage.about_features.push({ icon: 'fas fa-star', title: '', desc: '' }); },
        removeAboutFeature(i) { this.homepage.about_features.splice(i, 1); },

        // ==================== ABOUT HELPERS ====================
        addMission() { this.about.missions.push(''); },
        removeMission(i) { this.about.missions.splice(i, 1); },
        addHighlight() { this.about.highlights.push({ name: '', desc: '', image: '' }); },
        removeHighlight(i) { this.about.highlights.splice(i, 1); },

        // ==================== ANALYTICS ====================
        getPopularProducts() {
            let views = this.analytics.product_views || {};
            let arr = [];
            for (let id in views) {
                let product = this.products.find(p => p.id === id);
                arr.push({ name: product ? product.name : id, views: views[id] });
            }
            arr.sort((a,b) => b.views - a.views);
            return arr.slice(0, 10);
        }
    }
}
