
        function adminApp() {
            return {
                isLoggedIn: false,
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
    
