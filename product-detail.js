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

    // Populate data
    if (typeof window.updateSEO === 'function') {
        window.updateSEO(product.meta_title || `${product.name} - CV Asianindo`, product.meta_description || product.description || product.desc);
    } else {
        document.title = product.meta_title || `${product.name} - CV Asianindo`;
    }
    document.getElementById('breadcrumb-product-name').innerText = product.name;
    document.getElementById('product-name').innerText = product.name;
    document.getElementById('product-price').innerText = product.priceDisplay;
    if (product.images && product.images.length > 0) {
        document.getElementById('product-image').src = product.images[0];
        document.getElementById('product-image').alt = product.name;
        
        // Gallery navigation state
        let currentImageIndex = 0;
        const images = product.images;
        const mainImg = document.getElementById('product-image');
        const prevBtn = document.getElementById('gallery-prev');
        const nextBtn = document.getElementById('gallery-next');

        function updateGallery(newIndex) {
            currentImageIndex = newIndex;
            // Smooth transition
            mainImg.style.opacity = '0';
            mainImg.style.transform = 'scale(0.95)';
            setTimeout(() => {
                mainImg.src = images[currentImageIndex];
                mainImg.style.opacity = '1';
                mainImg.style.transform = 'scale(1)';
            }, 150);
            
            // Update thumbnail highlight
            const thumbs = document.querySelectorAll('#product-thumbnails > div');
            thumbs.forEach((t, i) => {
                if (i === currentImageIndex) {
                    t.classList.replace('border-gray-200', 'border-primary');
                } else {
                    t.classList.replace('border-primary', 'border-gray-200');
                }
            });

            // Update arrow disabled state
            if (prevBtn) prevBtn.disabled = (currentImageIndex === 0);
            if (nextBtn) nextBtn.disabled = (currentImageIndex === images.length - 1);
        }

        // Show arrows if more than 1 image
        if (images.length > 1) {
            if (prevBtn) { prevBtn.style.display = 'flex'; prevBtn.disabled = true; }
            if (nextBtn) { nextBtn.style.display = 'flex'; }
            
            prevBtn.addEventListener('click', () => {
                if (currentImageIndex > 0) updateGallery(currentImageIndex - 1);
            });
            nextBtn.addEventListener('click', () => {
                if (currentImageIndex < images.length - 1) updateGallery(currentImageIndex + 1);
            });

            // Keyboard arrow support
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft' && currentImageIndex > 0) updateGallery(currentImageIndex - 1);
                if (e.key === 'ArrowRight' && currentImageIndex < images.length - 1) updateGallery(currentImageIndex + 1);
            });
        }

        // Render thumbnails if more than 1 image
        const thumbContainer = document.getElementById('product-thumbnails');
        if (thumbContainer && product.images.length > 1) {
            let thumbHtml = '';
            product.images.forEach((img, idx) => {
                let borderClass = idx === 0 ? 'border-primary' : 'border-gray-200';
                thumbHtml += `
                    <div class="flex-shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 ${borderClass} cursor-pointer transition-all hover:border-primary" 
                         data-index="${idx}">
                        <img src="${img}" alt="Thumbnail ${idx+1}" class="w-full h-full object-cover">
                    </div>
                `;
            });
            thumbContainer.innerHTML = thumbHtml;

            // Bind thumbnail clicks
            thumbContainer.querySelectorAll('[data-index]').forEach(thumb => {
                thumb.addEventListener('click', () => {
                    updateGallery(parseInt(thumb.dataset.index));
                });
            });
        }
    } else if (product.image) {
        document.getElementById('product-image').src = product.image;
        document.getElementById('product-image').alt = product.name;
    }
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

    document.getElementById('product-description').innerHTML = finalItems.join('<br/>');

    // Badge
    const badgeEl = document.getElementById('product-badge');
    if (product.badge) {
        badgeEl.innerText = product.badge;
        badgeEl.className = `px-3 py-1 text-xs font-bold rounded-full ${product.badgeColor}`;
        document.getElementById('product-badge-container').classList.remove('hidden');
    }

    // Rating
    let starsHtml = '';
    for (let i = 0; i < product.rating; i++) {
        starsHtml += '<span class="material-symbols-outlined" style="font-variation-settings: \'FILL\' 1;">star</span>';
    }
    document.getElementById('product-rating').innerHTML = starsHtml;
    document.getElementById('product-reviews').innerText = `${product.reviews} Penilaian`;

    // Action buttons
    const waNumber = "6285335850517";
    const waMessage = encodeURIComponent(product.waMsg || `Halo, saya tertarik dengan ${product.name}`);
    document.getElementById('btn-whatsapp').href = `https://wa.me/${waNumber}?text=${waMessage}`;
    

});

// Smart Back Navigation
function goBackToKatalog() {
    if (document.referrer.includes('katalog.html') && window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = 'katalog.html';
    }
}
