function adminApp() {
    return {
        isLoggedIn: false,
        loginForm: { username: '', password: '' },
        loginError: '',
        isLoading: false,
        isSaving: false,
        currentView: 'dashboard', // dashboard, products, settings, articles, homepage, about, contact

        // Data Models
        analytics: { visitors: 0, wa_clicks: 0, messages: 0, product_views: {} },
        products: [],
        searchQuery: '',
        
        settings: { company_name: '', tagline: '', whatsapp: '', email: '', address: '', hours: '', year: '', youtube: '', tiktok: '', instagram: '', facebook: '', maps_url: '' },
        articles: [],
        homepage: { hero_title: '', hero_subtitle: '', stats_sold: '', stats_years: '', stats_warranty: '' },
        about: { title: '', description: '', vision: '', mission: '' },
        contact: { address: '', phone: '', maps_url: '' },

        // Forms
        editingId: null,
        productForm: { id: '', name: '', category: 'Mesin Industri', subCategory: '', price: '', priceRange: '', description: '', features: [''], specs: [{key: '', val: ''}], existing_image: '', existing_video: '' },
        articleForm: { id: '', title: '', category: '', publish_date: '', excerpt: '', content: '', existing_image: '' },

        imagePreview: null,
        videoPreview: null,
        imageFile: null,
        videoFile: null,

        initApp() {
            // Check session via PHP variable injected in index.php
            if (window.IS_LOGGED_IN) {
                this.isLoggedIn = true;
                this.loadAllData();
            }
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
                    this.loadAllData();
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

        changeView(view) {
            this.currentView = view;
        },

        async loadAllData() {
            this.loadAnalytics();
            this.loadProducts();
            this.loadModule('settings');
            this.loadModule('articles');
            this.loadModule('homepage');
            this.loadModule('about');
            this.loadModule('contact');
        },

        async loadAnalytics() {
            try {
                let res = await fetch('api.php?action=get_analytics');
                this.analytics = await res.json();
            } catch(e) {}
        },

        async loadProducts() {
            try {
                let res = await fetch('api.php?action=get_products');
                this.products = await res.json();
            } catch(e) {}
        },

        async loadModule(module) {
            try {
                let res = await fetch('api.php?action=get_module&module=' + module);
                this[module] = await res.json();
            } catch(e) {}
        },

        async saveModule(module) {
            this.isSaving = true;
            try {
                let res = await fetch('api.php?action=save_module&module=' + module, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this[module])
                });
                if (res.ok) alert('Berhasil disimpan!');
                else alert('Gagal menyimpan');
            } catch(e) {
                alert('Kesalahan jaringan');
            }
            this.isSaving = false;
        },

        // --- PRODUCT MANAGEMENT ---
        get filteredProducts() {
            if (this.searchQuery === '') return this.products;
            return this.products.filter(p => p.name.toLowerCase().includes(this.searchQuery.toLowerCase()));
        },

        openAddProduct() {
            this.editingId = null;
            this.productForm = { id: '', name: '', category: 'Mesin Industri', subCategory: '', price: '', priceRange: '', description: '', features: [''], specs: [{key: '', val: ''}], existing_image: '', existing_video: '' };
            this.resetMediaInputs();
            this.changeView('product_form');
        },

        openEditProduct(p) {
            this.editingId = p.id;
            let specsArr = [];
            if (p.specs) {
                for (let k in p.specs) specsArr.push({key: k, val: p.specs[k]});
            }
            if (specsArr.length === 0) specsArr.push({key: '', val: ''});
            
            this.productForm = {
                id: p.id, name: p.name, category: p.category, subCategory: p.subCategory, price: p.price, priceRange: p.priceRange || '',
                description: p.description || '', features: (p.features && p.features.length) ? [...p.features] : [''],
                specs: specsArr, existing_image: p.image || '', existing_video: p.video || ''
            };
            this.resetMediaInputs();
            this.changeView('product_form');
        },

        resetMediaInputs() {
            this.imagePreview = null;
            this.videoPreview = null;
            this.imageFile = null;
            this.videoFile = null;
            let imgInput = document.getElementById('imageInput');
            let vidInput = document.getElementById('videoInput');
            if(imgInput) imgInput.value = '';
            if(vidInput) vidInput.value = '';
        },

        handleImage(e) {
            let file = e.target.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) { alert('Ukuran gambar maksimal 2MB!'); e.target.value = ''; return; }
            this.imageFile = file;
            this.imagePreview = URL.createObjectURL(file);
        },

        handleVideo(e) {
            let file = e.target.files[0];
            if (!file) return;
            if (file.size > 15 * 1024 * 1024) { alert('Ukuran video maksimal 15MB!'); e.target.value = ''; return; }
            this.videoFile = file;
            this.videoPreview = true;
        },

        async saveProduct() {
            if (!this.productForm.name || !this.productForm.price) { alert('Mohon isi nama dan harga produk'); return; }
            this.isSaving = true;
            let fd = new FormData();
            fd.append('id', this.productForm.id);
            fd.append('name', this.productForm.name);
            fd.append('category', this.productForm.category);
            fd.append('price', this.productForm.price);
            fd.append('description', this.productForm.description);
            fd.append('existing_image', this.productForm.existing_image);
            if (this.imageFile) fd.append('image', this.imageFile);

            try {
                let res = await fetch('api.php?action=save_product', { method: 'POST', body: fd });
                if (res.ok) {
                    alert('Produk berhasil disimpan!');
                    this.loadProducts();
                    this.changeView('products');
                }
            } catch (e) { alert('Terjadi kesalahan jaringan'); }
            this.isSaving = false;
        },

        async deleteProduct(id) {
            if (confirm('Yakin hapus produk ini?')) {
                let fd = new FormData(); fd.append('id', id);
                await fetch('api.php?action=delete_product', { method: 'POST', body: fd });
                this.loadProducts();
            }
        },

        // --- ARTICLE MANAGEMENT ---
        openAddArticle() {
            this.editingId = null;
            this.articleForm = { id: '', title: '', category: '', publish_date: new Date().toISOString().split('T')[0], excerpt: '', content: '', existing_image: '' };
            this.resetMediaInputs();
            this.changeView('article_form');
        },

        openEditArticle(a) {
            this.editingId = a.id;
            this.articleForm = { ...a };
            this.resetMediaInputs();
            this.changeView('article_form');
        },

        async saveArticle() {
            this.isSaving = true;
            let id = this.editingId || 'art_' + Date.now();
            let article = { ...this.articleForm, id: id };

            if (this.imageFile) {
                let fd = new FormData();
                fd.append('file', this.imageFile);
                let res = await fetch('api.php?action=upload_media', { method: 'POST', body: fd });
                let data = await res.json();
                if (data.success) article.existing_image = data.path;
            }

            let articles = [...this.articles];
            let idx = articles.findIndex(a => a.id === id);
            if (idx > -1) articles[idx] = article;
            else articles.push(article);

            this.articles = articles;
            await this.saveModule('articles');
            this.changeView('articles');
            this.isSaving = false;
        },

        async deleteArticle(id) {
            if (confirm('Yakin hapus artikel ini?')) {
                this.articles = this.articles.filter(a => a.id !== id);
                await this.saveModule('articles');
            }
        },

        getPopularProducts() {
            let views = this.analytics.product_views || {};
            let arr = [];
            for (let id in views) {
                let product = this.products.find(p => p.id === id);
                if (product) arr.push({ name: product.name, views: views[id] });
            }
            arr.sort((a,b) => b.views - a.views);
            return arr.slice(0, 5);
        }
    }
}
