document.addEventListener('DOMContentLoaded', async () => {
    // Get product ID from URL query param ?id=...
    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get('id');

    let products = window.CATALOG_PRODUCTS || [];
    
    // Fetch live products to bypass cache
    try {
        const res = await fetch('data/products.json?v=' + new Date().getTime());
        const data = await res.json();
        if (Array.isArray(data) && data.length > 0) {
            products = data;
            window.CATALOG_PRODUCTS = data; // Update global state for related products
        }
    } catch (err) {
        console.error("Failed to fetch live products", err);
    }

    if (!productId || products.length === 0) {
        document.getElementById('product-name').innerText = "Produk tidak ditemukan.";
        return;
    }

    // Find the product
    const product = products.find(p => p.id === productId || p.slug === productId);

    if (!product) {
        document.getElementById('product-name').innerText = "Produk tidak ditemukan.";
        return;
    }

    // Record product view locally & to server analytics
    try {
        const pKey = product.id || product.slug || productId;
        let localViews = JSON.parse(localStorage.getItem('asianindo_product_views') || '{}');
        localViews[pKey] = (localViews[pKey] || 0) + 1;
        if (product.slug && product.id && product.slug !== product.id) {
            localViews[product.slug] = localViews[pKey];
            localViews[product.id] = localViews[pKey];
        }
        localStorage.setItem('asianindo_product_views', JSON.stringify(localViews));
        
        fetch('admin/api.php?action=track_event', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event: 'product_view', product_id: pKey })
        }).catch(() => {});
    } catch(e) {}

    // Populate data
    if (typeof window.updateSEO === 'function') {
        window.updateSEO(product.meta_title || `${product.name} - CV Asianindo`, product.meta_description || product.description || product.desc);
    } else {
        document.title = product.meta_title || `${product.name} - CV Asianindo`;
    }
    document.getElementById('breadcrumb-product-name').innerText = product.name;
    document.getElementById('product-name').innerText = product.name;
    document.getElementById('product-price').innerText = product.priceDisplay;
    function sanitizeProductImage(img, productId, index = 0) {
        if (!img || typeof img !== 'string') return `images/products/${productId}_0.webp`;
        const trimmed = img.trim();
        if (trimmed.includes('media_6ac') || trimmed.includes('media_6ac7')) {
            return `images/products/${productId}_${index}.webp`;
        }
        return trimmed;
    }

    function sanitizeProductVideo(video) {
        if (!video || typeof video !== 'string') return '';
        return video.trim();
    }

    if (Array.isArray(product.images) && product.images.length > 0) {
        product.images = product.images.map((img, idx) => sanitizeProductImage(img, product.id, idx));
    } else {
        product.images = [`images/products/${product.id}_0.webp`];
    }

    // Check for product video (Shopee style: placed at the very beginning)
    let validVideo = sanitizeProductVideo(product.video);

    // Build unified media items array: Video is always index 0 if available!
    const mediaItems = [];
    if (validVideo) {
        mediaItems.push({
            type: 'video',
            src: validVideo,
            poster: product.images[0] || 'images/placeholder.webp'
        });
    }
    (product.images || []).forEach(img => {
        mediaItems.push({
            type: 'image',
            src: img
        });
    });

    const mainImg = document.getElementById('product-image');
    const mainVideo = document.getElementById('product-video');
    const prevBtn = document.getElementById('gallery-prev');
    const nextBtn = document.getElementById('gallery-next');
    const thumbContainer = document.getElementById('product-thumbnails');
    const unmuteBtn = document.getElementById('video-unmute-btn');

    let currentMediaIndex = 0;

    function updateUnmuteBtnState() {
        if (!unmuteBtn || !mainVideo || mainVideo.classList.contains('hidden')) {
            if (unmuteBtn) { unmuteBtn.classList.add('hidden'); unmuteBtn.classList.remove('flex'); }
            return;
        }
        if (mainVideo.muted) {
            unmuteBtn.classList.remove('hidden');
            unmuteBtn.classList.add('flex');
        } else {
            unmuteBtn.classList.add('hidden');
            unmuteBtn.classList.remove('flex');
        }
    }

    function hideUnmuteBtn() {
        if (unmuteBtn) {
            unmuteBtn.classList.add('hidden');
            unmuteBtn.classList.remove('flex');
        }
    }

    if (mainVideo) {
        mainVideo.addEventListener('error', function(e) {
            console.warn('Video failed to load:', mainVideo.src, e);
        });
    }

    function playProductVideo(isUserClick = false) {
        if (!mainVideo) return;
        mainVideo.controls = true;
        if (isUserClick) {
            mainVideo.muted = false;
        }
        const promise = mainVideo.play();
        if (promise !== undefined) {
            promise.then(() => {
                updateUnmuteBtnState();
            }).catch(() => {
                // If browser restricts unmuted autoplay on initial load, play muted immediately
                mainVideo.muted = true;
                mainVideo.play().then(() => {
                    updateUnmuteBtnState();
                }).catch(e => {
                    console.warn('Autoplay error:', e);
                });
            });
        }
    }

    if (unmuteBtn && mainVideo) {
        unmuteBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            mainVideo.muted = false;
            hideUnmuteBtn();
            mainVideo.play().catch(() => {});
        });
        mainVideo.addEventListener('volumechange', () => {
            updateUnmuteBtnState();
        });
        mainVideo.addEventListener('click', () => {
            if (mainVideo.muted) {
                mainVideo.muted = false;
                hideUnmuteBtn();
            }
        });
    }

    if (mainImg) {
        mainImg.onerror = function() {
            if (!this.dataset.fallback) {
                this.dataset.fallback = '1';
                this.src = `images/products/${product.id}_0.webp`;
            } else {
                this.onerror = null;
                this.src = 'images/placeholder.webp';
            }
        };
    }

    function showMedia(newIndex, isUserClick = false) {
        if (newIndex < 0 || newIndex >= mediaItems.length) return;
        currentMediaIndex = newIndex;
        const currentItem = mediaItems[currentMediaIndex];

        if (currentItem.type === 'video') {
            // Show video player
            if (mainImg) mainImg.classList.add('hidden');
            if (mainVideo) {
                mainVideo.classList.remove('hidden');
                if (mainVideo.src !== currentItem.src && !mainVideo.src.endsWith(currentItem.src)) {
                    mainVideo.src = currentItem.src;
                }
                if (currentItem.poster) mainVideo.poster = currentItem.poster;

                // Play video immediately upon opening
                playProductVideo(isUserClick);
            }
        } else {
            // Show photo image
            if (mainVideo) {
                mainVideo.pause();
                mainVideo.classList.add('hidden');
                hideUnmuteBtn();
            }
            if (mainImg) {
                mainImg.classList.remove('hidden');
                mainImg.style.opacity = '0';
                mainImg.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    mainImg.src = currentItem.src;
                    mainImg.style.opacity = '1';
                    mainImg.style.transform = 'scale(1)';
                }, 100);
            }
        }

        // Highlight active thumbnail
        const thumbs = document.querySelectorAll('#product-thumbnails > div');
        thumbs.forEach((t, i) => {
            if (i === currentMediaIndex) {
                t.classList.replace('border-gray-200', 'border-primary');
                t.classList.add('ring-2', 'ring-primary/40');
            } else {
                t.classList.replace('border-primary', 'border-gray-200');
                t.classList.remove('ring-2', 'ring-primary/40');
            }
        });

        // Update arrow button disabled state
        if (prevBtn) prevBtn.disabled = (currentMediaIndex === 0);
        if (nextBtn) nextBtn.disabled = (currentMediaIndex === mediaItems.length - 1);
    }

    // Render thumbnails gallery
    if (thumbContainer && mediaItems.length > 1) {
        let thumbHtml = '';
        mediaItems.forEach((item, idx) => {
            let borderClass = idx === 0 ? 'border-primary ring-2 ring-primary/40' : 'border-gray-200';
            if (item.type === 'video') {
                thumbHtml += `
                    <div class="flex-shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 ${borderClass} cursor-pointer transition-all hover:border-primary relative group/vthumb bg-black/90 shadow-sm" 
                         data-index="${idx}" title="Tonton Video Produk">
                        <img src="${item.poster}" alt="Thumbnail Video" class="w-full h-full object-cover opacity-80 group-hover/vthumb:opacity-95 transition-opacity">
                        <div class="absolute inset-0 bg-black/40 flex flex-col items-center justify-center text-white pointer-events-none">
                            <span class="material-symbols-outlined text-2xl drop-shadow text-white" style="font-variation-settings: 'FILL' 1;">play_circle</span>
                            <span class="text-[9px] font-bold tracking-wider uppercase mt-[-2px] bg-red-600 text-white px-1.5 py-0.5 rounded-full shadow-sm leading-none">Video</span>
                        </div>
                    </div>
                `;
            } else {
                thumbHtml += `
                    <div class="flex-shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 ${borderClass} cursor-pointer transition-all hover:border-primary bg-white shadow-sm" 
                         data-index="${idx}">
                        <img src="${item.src}" 
                             onerror="this.onerror=null; this.src='images/products/${product.id}_0.webp';" 
                             alt="Thumbnail ${idx+1}" 
                             class="w-full h-full object-cover">
                    </div>
                `;
            }
        });
        thumbContainer.innerHTML = thumbHtml;

        // Bind click events on thumbnails
        thumbContainer.querySelectorAll('[data-index]').forEach(thumb => {
            thumb.addEventListener('click', () => {
                showMedia(parseInt(thumb.dataset.index), true);
            });
        });
    }

    // Navigation arrows & keyboard listeners
    if (mediaItems.length > 1) {
        if (prevBtn) { prevBtn.style.display = 'flex'; prevBtn.disabled = true; }
        if (nextBtn) { nextBtn.style.display = 'flex'; }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (currentMediaIndex > 0) showMedia(currentMediaIndex - 1, true);
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                if (currentMediaIndex < mediaItems.length - 1) showMedia(currentMediaIndex + 1, true);
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft' && currentMediaIndex > 0) showMedia(currentMediaIndex - 1, true);
            if (e.key === 'ArrowRight' && currentMediaIndex < mediaItems.length - 1) showMedia(currentMediaIndex + 1, true);
        });
    }

    // Show initial item: Video first if available, else first image!
    showMedia(0, false);
    document.getElementById('product-category').innerText = product.category || '';
    document.getElementById('product-capacity').innerText = product.capacity || '';
    
    // Format description (guarantee vertical line breaks for bullets)
    let rawDesc = product.desc || product.description || '';
    if (rawDesc.includes('<li>')) {
        rawDesc = rawDesc.replace(/<ul[^>]*>/gi, '').replace(/<\/ul>/gi, '');
        let parts = rawDesc.split(/<\/li>/gi);
        let items = [];
        parts.forEach(p => {
            let cleaned = p.replace(/<li[^>]*>/gi, '').replace(/<[^>]*>/g, '').trim();
            if (cleaned) items.push(cleaned);
        });
        rawDesc = items.join('\n');
    } else if (rawDesc.includes('<br')) {
        rawDesc = rawDesc.replace(/<br\s*\/?>/gi, '\n');
    }

    let descLines = rawDesc.split('\n');
    let finalItems = [];
    descLines.forEach(line => {
        let trimmed = line.replace(/<[^>]*>/g, '').trim();
        if (!trimmed) return;
        if (trimmed.includes('·')) {
            let subItems = trimmed.split('·').map(s => s.trim()).filter(Boolean);
            subItems.forEach(item => {
                finalItems.push(item.startsWith('•') || item.startsWith('·') ? item : '• ' + item);
            });
        } else {
            finalItems.push(trimmed.startsWith('•') || trimmed.startsWith('·') ? trimmed : '• ' + trimmed);
        }
    });

    if (finalItems.length > 0) {
        document.getElementById('product-description').innerHTML = 
            '<ul class="list-disc pl-5 space-y-2 text-on-surface-variant">' + 
            finalItems.map(item => `<li>${item.replace(/^[•·]\s*/, '')}</li>`).join('') + 
            '</ul>';
    } else {
        document.getElementById('product-description').innerHTML = '<p>' + rawDesc + '</p>';
    }

    // Badge
    const badgeEl = document.getElementById('product-badge');
    if (product.badge) {
        badgeEl.innerText = product.badge;
        badgeEl.className = `px-3 py-1 text-xs font-bold rounded-full ${product.badgeColor}`;
        document.getElementById('product-badge-container').classList.remove('hidden');
    }

    // Rating
    let starsHtml = '';
    const rScore = product.rating || 5;
    for (let i = 0; i < rScore; i++) {
        starsHtml += '<span class="material-symbols-outlined" style="font-variation-settings: \'FILL\' 1;">star</span>';
    }
    document.getElementById('product-rating').innerHTML = starsHtml;
    const revCount = product.reviews || 0;
    const soldCount = product.sold || revCount;
    document.getElementById('product-reviews').innerHTML = `<span class="font-bold text-on-surface">${revCount}</span> Penilaian <span class="text-outline-variant/60 mx-1.5">|</span> <span class="font-bold text-on-surface">${soldCount}</span> Terjual`;

    // Capacity Variants Selector (Smart Family Matcher)
    function parseCapValue(p) {
        const name = (p.name || '').toLowerCase();
        const cap = (p.capacity || '').toLowerCase();
        const str = name + ' ' + cap;
        let m = str.match(/(\d+(?:[.,]\d+)?)\s*(?:-|s\/d|sampai)?\s*(?:\d+(?:[.,]\d+)?)?\s*ton\b/i);
        if (m) return parseFloat(m[1].replace(',', '.')) * 1000;
        m = str.match(/(\d+(?:[.,]\d+)?)\s*(?:-|s\/d|sampai)?\s*(?:\d+(?:[.,]\d+)?)?\s*kg\b/i);
        if (m) return parseFloat(m[1].replace(',', '.'));
        m = str.match(/(\d+(?:[.,]\d+)?)\s*(?:-|s\/d|sampai)?\s*(?:\d+(?:[.,]\d+)?)?\s*(?:liter|ltr|l)\b/i);
        if (m) return parseFloat(m[1].replace(',', '.'));
        m = str.match(/(\d+)\s*(?:rak|loyang|butir)\b/i);
        if (m) return parseFloat(m[1]);
        return 99999;
    }

    function renderCapacityVariants() {
        const container = document.getElementById('capacity-variants-container');
        const listEl = document.getElementById('capacity-variants-list');
        if (!container || !listEl) return;

        const nameLower = (product.name || '').toLowerCase();
        let familyKeyword = '';
        const keywords = ['vacuum frying', 'pirolisis', 'spray dryer', 'roasting kopi', 'pasteurisasi', 'evaporator', 'retort', 'pencacah', 'dehydrator', 'vacuum drying', 'penepung', 'pelet', 'peniris minyak', 'spinner'];
        for (let kw of keywords) {
            if (nameLower.includes(kw)) {
                familyKeyword = kw;
                break;
            }
        }
        if (!familyKeyword && product.subCategory) {
            familyKeyword = product.subCategory.toLowerCase();
        }
        if (!familyKeyword) return;

        const allProducts = window.CATALOG_PRODUCTS || products;
        const siblings = allProducts.filter(p => {
            const sName = (p.name || '').toLowerCase();
            const sPrice = typeof p.price === 'number' ? p.price : parseInt(String(p.price || '0').replace(/\D/g, ''), 10);
            const isAcc = (sPrice > 0 && sPrice < 1500000) && /\b(karet|seal|sparepart|onderdil|baut|selang)\b/i.test(sName);
            if (isAcc) return false;
            return sName.includes(familyKeyword);
        });

        if (siblings.length <= 1) return;

        siblings.sort((a, b) => parseCapValue(a) - parseCapValue(b));

        const seenCap = new Set();
        const uniqueVariants = [];
        siblings.forEach(s => {
            let capLabel = s.capacity || '';
            const m = (s.name || '').match(/(\d+(?:[.,]\d+)?\s*(?:kg|liter|ton|rak|loyang))/i);
            if (m) capLabel = m[1].replace('.', ',');
            if (!capLabel) capLabel = 'Standar';
            const capKey = capLabel.toLowerCase();
            if (!seenCap.has(capKey)) {
                seenCap.add(capKey);
                uniqueVariants.push({ product: s, label: capLabel });
            }
        });

        if (uniqueVariants.length <= 1) return;

        let html = '';
        uniqueVariants.forEach(item => {
            const isCurrent = (item.product.id === product.id || item.product.slug === product.id || item.product.id === product.slug);
            const targetId = item.product.slug || item.product.id;
            if (isCurrent) {
                html += `<span class="px-3 py-1.5 rounded-xl bg-primary text-white text-xs font-bold shadow-sm border border-primary flex items-center gap-1 cursor-default"><span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'FILL' 1;">check_circle</span><span>${item.label}</span></span>`;
            } else {
                html += `<a href="product.html?id=${targetId}" class="px-3 py-1.5 rounded-xl bg-white hover:bg-primary/5 text-gray-800 hover:text-primary text-xs font-semibold border border-gray-300 hover:border-primary transition-all shadow-2xs hover:shadow-xs">${item.label}</a>`;
            }
        });

        listEl.innerHTML = html;
        container.classList.remove('hidden');
    }

    renderCapacityVariants();

        // Action buttons & Formatted WhatsApp Lead Message (Direct fast order, NO LOGIN REQUIRED)
    const waNumber = "6285335850517";
    const productPriceText = product.priceDisplay || (product.price ? 'Rp ' + Number(product.price).toLocaleString('id-ID') : 'Hubungi Kami');
    
    // Clean and detect capacity
    let productCapText = (product.capacity || '').replace(/<[^>]*>?/gm, '').trim();
    if (!productCapText || productCapText === '1' || productCapText === '3' || /^\d+$/.test(productCapText)) {
        const decMatch = (product.name || '').match(/(\d+[.,]\d+)\s*(?:kg|kilo|liter|ltr|\bl\b|ton\b|rak|loyang)/i);
        if (decMatch) productCapText = decMatch[0];
        else {
            const unitMatch = (product.name || '').match(/(\d+)\s*(?:kg|kilo|liter|ltr|rak|loyang|butir|ton)/i);
            if (unitMatch) productCapText = unitMatch[0];
        }
    }
    productCapText = (productCapText || '-').replace(/\s*\(\s*on proses\s*\)/i, '').replace(/\s*on proses/i, '').trim();
    if (!productCapText) productCapText = '-';

    const currentUrl = window.location.href;
    const waOrderMessage = `*FORM PEMESANAN MESIN - CV ASIANINDO*\n` +
        `----------------------------------------\n` +
        `Halo Admin Asianindo, saya ingin memesan / konsultasi mesin berikut:\n\n` +
        `• *Nama Produk:* ${product.name}\n` +
        `• *Kapasitas:* ${productCapText}\n` +
        `• *Harga Pabrik:* ${productPriceText}\n` +
        `• *Jumlah:* 1 Unit\n` +
        `• *Link Produk:* ${currentUrl}\n\n` +
        `Boleh minta info ketersediaan stok dan estimasi waktu fabrikasi serta ongkos kirim ke alamat saya, terima kasih.`;

    const waBtn = document.getElementById('btn-whatsapp');
    if (waBtn) {
        waBtn.href = `https://wa.me/${waNumber}?text=${encodeURIComponent(waOrderMessage)}`;
    }

    // Direct Buy Now -> Direct Fast Order to WhatsApp without login barrier!
    const btnBuyNow = document.getElementById('btn-buy-now');
    if (btnBuyNow) {
        btnBuyNow.addEventListener('click', (e) => {
            e.preventDefault();
            window.open(`https://wa.me/${waNumber}?text=${encodeURIComponent(waOrderMessage)}`, '_blank');
        });
    }

    // Update ALL WhatsApp buttons on the product page (including floating button wa-pulse)
    document.querySelectorAll('a[href*="wa.me"]').forEach(link => {
        link.href = `https://wa.me/${waNumber}?text=${encodeURIComponent(waOrderMessage)}`;
    });

    const btnAddCart = document.getElementById('btn-add-cart');
    if (btnAddCart) {
        btnAddCart.addEventListener('click', () => {
            if (typeof window.addToCart === 'function') {
                window.addToCart(product, 1);
            }
        });
    }

});

// Smart Back Navigation
function goBackToKatalog() {
    if (document.referrer.includes('katalog.html') && window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = 'katalog.html';
    }
}
