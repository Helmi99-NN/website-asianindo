document.addEventListener('DOMContentLoaded', () => {
    // Get product ID from URL query param ?id=...
    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get('id');

    if (!productId || !window.CATALOG_PRODUCTS) {
        document.getElementById('product-name').innerText = "Produk tidak ditemukan.";
        return;
    }

    // Find the product
    const product = window.CATALOG_PRODUCTS.find(p => p.id === productId || p.slug === productId);

    if (!product) {
        document.getElementById('product-name').innerText = "Produk tidak ditemukan.";
        return;
    }

    // Populate data
    document.title = `${product.name} - CV Asianindo`;
    document.getElementById('breadcrumb-product-name').innerText = product.name;
    document.getElementById('product-name').innerText = product.name;
    document.getElementById('product-price').innerText = product.priceDisplay;
    if (product.images && product.images.length > 0) {
        document.getElementById('product-image').src = product.images[0];
        document.getElementById('product-image').alt = product.name;
        
        // Render thumbnails if more than 1 image
        const thumbContainer = document.getElementById('product-thumbnails');
        if (thumbContainer && product.images.length > 1) {
            let thumbHtml = '';
            product.images.forEach((img, idx) => {
                let borderClass = idx === 0 ? 'border-primary' : 'border-gray-200';
                thumbHtml += `
                    <div class="flex-shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 ${borderClass} cursor-pointer transition-all hover:border-primary" 
                         onclick="
                            document.getElementById('product-image').src='${img}';
                            Array.from(this.parentElement.children).forEach(c => c.classList.replace('border-primary', 'border-gray-200'));
                            this.classList.replace('border-gray-200', 'border-primary');
                         ">
                        <img src="${img}" alt="Thumbnail ${idx+1}" class="w-full h-full object-cover">
                    </div>
                `;
            });
            thumbContainer.innerHTML = thumbHtml;
        }
    } else if (product.image) {
        document.getElementById('product-image').src = product.image;
        document.getElementById('product-image').alt = product.name;
    }
    document.getElementById('product-category').innerText = product.category;
    document.getElementById('product-capacity').innerText = product.capacity;
    
    // Format description (preserve line breaks)
    const formattedDesc = product.desc.replace(/\n/g, '<br/>');
    document.getElementById('product-description').innerHTML = formattedDesc;

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
    
    if (product.shopeeUrl) {
        document.getElementById('btn-shopee').href = product.shopeeUrl;
    } else {
        document.getElementById('btn-shopee').style.display = 'none';
    }
});
