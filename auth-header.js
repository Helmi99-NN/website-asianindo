/**
 * CV Asianindo E-Commerce - Global Header & Auth Integration
 * Handles live login state, cart count badge, user dropdown, and quick add-to-cart across all pages.
 */

document.addEventListener('DOMContentLoaded', () => {
    initAuthHeader();
    updateCartCount();
});

// Toast notification helper
function showToast(message, type = 'success') {
    let toast = document.getElementById('global-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'global-toast';
        toast.className = 'fixed bottom-6 right-6 z-[100] transform transition-all duration-300 translate-y-20 opacity-0 pointer-events-none';
        document.body.appendChild(toast);
    }

    const bg = type === 'success' ? 'bg-emerald-600' : (type === 'error' ? 'bg-rose-600' : 'bg-primary');
    const icon = type === 'success' ? 'check_circle' : (type === 'error' ? 'error' : 'info');

    toast.innerHTML = `
        <div class="${bg} text-white px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 border border-white/20 pointer-events-auto backdrop-blur-md">
            <span class="material-symbols-outlined text-[20px]">${icon}</span>
            <span class="text-sm font-medium">${message}</span>
        </div>
    `;

    toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
    setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
    }, 3500);
}

// Update Cart Badge Count
async function updateCartCount() {
    try {
        const res = await fetch('cart_api.php?action=get_cart_count');
        const data = await res.json();
        const count = data.count || 0;
        
        document.querySelectorAll('.cart-count-badge').forEach(badge => {
            badge.textContent = count;
            if (count > 0) {
                badge.classList.remove('hidden');
                badge.classList.add('flex');
            } else {
                badge.classList.add('hidden');
                badge.classList.remove('flex');
            }
        });
    } catch (e) {
        // Silently fail if offline or not applicable
    }
}

// Global Add to Cart function
window.addToCart = async function(product, qty = 1) {
    try {
        const sessionRes = await fetch('auth_api.php?action=check_session');
        const session = await sessionRes.json();
        
        if (!session.logged_in) {
            window.location.href = 'login.html?redirect=' + encodeURIComponent(window.location.href);
            return;
        }

        const formData = new FormData();
        formData.append('product_id', product.id || product.slug || '');
        formData.append('product_name', product.name || '');
        formData.append('product_image', (product.images && product.images[0]) || product.image || 'images/placeholder.webp');
        formData.append('product_price', product.price || 0);
        formData.append('quantity', qty);
        formData.append('weight_grams', product.weight_grams || 25000); // default 25kg

        const res = await fetch('cart_api.php?action=add_to_cart', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            showToast(`"${product.name}" berhasil ditambahkan ke keranjang!`, 'success');
            updateCartCount();
        } else {
            showToast(data.error || 'Gagal menambahkan ke keranjang', 'error');
        }
    } catch (e) {
        showToast('Terjadi kesalahan koneksi', 'error');
    }
};

// Global Buy Now (Direct Checkout)
window.buyNow = async function(product, qty = 1) {
    try {
        const sessionRes = await fetch('auth_api.php?action=check_session');
        const session = await sessionRes.json();
        
        if (!session.logged_in) {
            window.location.href = 'login.html?redirect=' + encodeURIComponent('checkout.html?product_id=' + (product.id || product.slug) + '&qty=' + qty);
            return;
        }

        // Store direct buy item in sessionStorage
        sessionStorage.setItem('checkout_items', JSON.stringify([{
            product_id: product.id || product.slug,
            product_name: product.name,
            product_image: (product.images && product.images[0]) || product.image || 'images/placeholder.webp',
            price: product.price || 0,
            quantity: qty,
            weight_grams: product.weight_grams || 25000
        }]));

        window.location.href = 'checkout.html';
    } catch (e) {
        window.location.href = 'checkout.html?product_id=' + (product.id || product.slug) + '&qty=' + qty;
    }
};

// Initialize Auth Header & User Dropdowns
async function initAuthHeader() {
    try {
        const res = await fetch('auth_api.php?action=check_session');
        const data = await res.json();
        
        const container = document.getElementById('header-auth-container');
        if (!container) return;

        if (data.logged_in && data.customer) {
            const firstName = data.customer.name.split(' ')[0];
            container.innerHTML = `
                <div class="relative group">
                    <button class="flex items-center gap-1 sm:gap-2 bg-primary/5 hover:bg-primary/10 border border-primary/20 text-primary px-2 sm:px-3.5 py-1 sm:py-1.5 rounded-xl text-[11px] sm:text-xs md:text-sm font-semibold transition-all shrink-0">
                        <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-primary text-white flex items-center justify-center text-[10px] sm:text-xs font-bold shrink-0">
                            ${firstName.charAt(0).toUpperCase()}
                        </div>
                        <span class="max-w-[50px] sm:max-w-[100px] md:max-w-[120px] truncate">${firstName}</span>
                        <span class="material-symbols-outlined text-[14px] sm:text-[18px]">expand_more</span>
                    </button>
                    <div class="absolute right-0 top-full mt-2 w-48 bg-white rounded-2xl shadow-xl border border-outline-variant/20 py-2 hidden group-hover:block transition-all z-50">
                        <div class="px-4 py-2 border-b border-outline-variant/15 text-xs text-on-surface-variant">
                            Halo, <strong class="text-primary block text-sm font-bold truncate">${data.customer.name}</strong>
                        </div>
                        <a href="akun.html" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-on-surface hover:bg-surface-container-high transition-colors">
                            <span class="material-symbols-outlined text-[18px] text-primary">person</span>
                            Profil Saya
                        </a>
                        <a href="akun.html#pesanan" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-on-surface hover:bg-surface-container-high transition-colors">
                            <span class="material-symbols-outlined text-[18px] text-primary">package_2</span>
                            Pesanan Saya
                        </a>
                        <a href="keranjang.html" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-on-surface hover:bg-surface-container-high transition-colors">
                            <span class="material-symbols-outlined text-[18px] text-primary">shopping_cart</span>
                            Keranjang Belanja
                        </a>
                        <div class="border-t border-outline-variant/15 my-1"></div>
                        <button onclick="handleLogout()" class="w-full text-left flex items-center gap-2.5 px-4 py-2.5 text-xs text-rose-600 hover:bg-rose-50 transition-colors">
                            <span class="material-symbols-outlined text-[18px]">logout</span>
                            Keluar
                        </button>
                    </div>
                </div>
            `;
        } else {
            container.innerHTML = `
                <a href="login.html" class="flex items-center gap-1 bg-primary/5 hover:bg-primary/10 border border-primary/20 text-primary px-2.5 sm:px-3.5 py-1 sm:py-1.5 rounded-xl text-xs md:text-sm font-semibold transition-all shrink-0">
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">person</span>
                    <span>Masuk</span>
                </a>
            `;
        }
    } catch (e) {
        // Silently fail if error
    }
}

async function handleLogout() {
    if (confirm('Apakah Anda yakin ingin keluar?')) {
        await fetch('auth_api.php?action=logout');
        window.location.reload();
    }
}
window.handleLogout = handleLogout;
