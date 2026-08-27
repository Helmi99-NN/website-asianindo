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
                <div id="user-dropdown-wrapper" class="relative group">
                    <button type="button" onclick="toggleUserMenu(event)" class="flex items-center gap-1 sm:gap-2 bg-primary/5 hover:bg-primary/10 border border-primary/20 text-primary px-2 sm:px-3.5 py-1 sm:py-1.5 rounded-xl text-[11px] sm:text-xs md:text-sm font-semibold transition-all shrink-0 cursor-pointer select-none active:scale-98">
                        <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-primary text-white flex items-center justify-center text-[10px] sm:text-xs font-bold shrink-0 shadow-sm">
                            ${firstName.charAt(0).toUpperCase()}
                        </div>
                        <span class="max-w-[50px] sm:max-w-[100px] md:max-w-[120px] truncate">${firstName}</span>
                        <span id="user-dropdown-chevron" class="material-symbols-outlined text-[14px] sm:text-[18px] transition-transform duration-200">expand_more</span>
                    </button>

                    <!-- Dropdown bridge wrapper (pt-2 creates continuous hover area without gap) -->
                    <div id="user-dropdown-menu" 
                         class="absolute right-0 top-full pt-2 w-52 z-50 hidden opacity-0 scale-95 translate-y-1 transition-all duration-150 origin-top-right group-hover:block group-hover:opacity-100 group-hover:scale-100 group-hover:translate-y-0"
                         onclick="event.stopPropagation()">
                        <div class="bg-white rounded-2xl shadow-2xl border border-outline-variant/20 py-2 overflow-hidden ring-1 ring-black/5">
                            <div class="px-4 py-2.5 bg-surface-container-low/60 border-b border-outline-variant/15 text-xs text-on-surface-variant">
                                <span class="text-[11px] text-gray-500 font-medium">Halo,</span>
                                <strong class="text-primary block text-sm font-bold truncate mt-0.5">${data.customer.name}</strong>
                            </div>
                            <div class="py-1">
                                <a href="akun.html" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-on-surface hover:bg-primary/5 hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-[18px] text-primary">person</span>
                                    Profil Saya
                                </a>
                                <a href="akun.html#pesanan" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-on-surface hover:bg-primary/5 hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-[18px] text-primary">package_2</span>
                                    Pesanan Saya
                                </a>
                                <a href="keranjang.html" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-on-surface hover:bg-primary/5 hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-[18px] text-primary">shopping_cart</span>
                                    Keranjang Belanja
                                </a>
                            </div>
                            <div class="border-t border-outline-variant/15 my-1"></div>
                            <div class="px-1 pb-1">
                                <button type="button" onclick="handleLogout()" class="w-full text-left flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer">
                                    <span class="material-symbols-outlined text-[18px]">logout</span>
                                    Keluar
                                </button>
                            </div>
                        </div>
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

// User Menu Toggle Handler
window.toggleUserMenu = function(event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('user-dropdown-menu');
    const chevron = document.getElementById('user-dropdown-chevron');
    if (!menu) return;

    const isHidden = menu.classList.contains('hidden');
    if (isHidden) {
        menu.classList.remove('hidden');
        requestAnimationFrame(() => {
            menu.classList.remove('opacity-0', 'scale-95', 'translate-y-1');
            menu.classList.add('opacity-100', 'scale-100', 'translate-y-0');
        });
        if (chevron) chevron.classList.add('rotate-180');
    } else {
        window.closeUserMenu();
    }
};

window.closeUserMenu = function() {
    const menu = document.getElementById('user-dropdown-menu');
    const chevron = document.getElementById('user-dropdown-chevron');
    if (!menu) return;

    menu.classList.remove('opacity-100', 'scale-100', 'translate-y-0');
    menu.classList.add('opacity-0', 'scale-95', 'translate-y-1');
    if (chevron) chevron.classList.remove('rotate-180');
    setTimeout(() => {
        if (menu.classList.contains('opacity-0')) {
            menu.classList.add('hidden');
        }
    }, 150);
};

// Global click outside listener to close dropdown
document.addEventListener('click', (e) => {
    const dropdownWrapper = document.getElementById('user-dropdown-wrapper');
    if (dropdownWrapper && !dropdownWrapper.contains(e.target)) {
        window.closeUserMenu();
    }
});

async function handleLogout() {
    if (confirm('Apakah Anda yakin ingin keluar?')) {
        await fetch('auth_api.php?action=logout');
        window.location.reload();
    }
}
window.handleLogout = handleLogout;
