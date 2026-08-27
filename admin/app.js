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
        products: [],
        searchQuery: '',
        articleSearch: '',
        mediaItems: [],
        isUploadingMedia: false,
        mediaSearch: '',
        
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
            copyright: '© 2024 CV Asianindo Industrial Machinery. All rights reserved.'
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
        ecommerceStats: { total_orders: 0, pending_payment: 0, pending_verifications: 0, active_shipments: 0, total_sales: 0 },
        orderSearch: '',
        orderFilter: 'all',
        activeOrder: null,
        showPaymentModal: false,
        showShipmentModal: false,
        showOrderDetailModal: false,
        paymentNotes: '',
        orderStatusUpdate: '',
        shipmentForm: { expedition: 'Indah Kargo', tracking_number: '', status: 'preparing', estimated_arrival: '', notes: '' },

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
                this.loadOrders()
            ]);
        },

        async loadAnalytics() {
            try { let r = await fetch('api.php?action=get_analytics'); this.analytics = await r.json(); } catch(e) {}
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

        // ==================== PRODUCT CRUD ====================
        filteredProducts() {
            let list = Array.isArray(this.products) ? this.products : Object.values(this.products);
            list = list.filter(p => p && typeof p === 'object');
            if (!this.searchQuery) return list;
            return list.filter(p => (p.name || '').toLowerCase().includes(this.searchQuery.toLowerCase()));
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
