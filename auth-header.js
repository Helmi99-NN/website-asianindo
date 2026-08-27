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

// Inject Dropdown Styles once
(function injectDropdownStyles() {
    if (document.getElementById('user-dropdown-style')) return;
    const style = document.createElement('style');
    style.id = 'user-dropdown-style';
    style.textContent = `
        .user-dropdown-container {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        .user-dropdown-bubble {
            position: absolute;
            right: 0;
            top: 100%;
            padding-top: 6px;
            width: 13.5rem;
            z-index: 99999;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-4px) scale(0.96);
            transition: opacity 0.2s cubic-bezier(0.16, 1, 0.3, 1), transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.2s;
            pointer-events: none;
        }
        .user-dropdown-container.is-active .user-dropdown-bubble,
        .user-dropdown-container:hover .user-dropdown-bubble {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) scale(1) !important;
            pointer-events: auto !important;
        }
        .user-dropdown-container.is-active #user-dropdown-chevron,
        .user-dropdown-container:hover #user-dropdown-chevron {
            transform: rotate(180deg);
        }
    `;
    document.head.appendChild(style);
})();

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
                <div id="user-dropdown-wrapper" class="user-dropdown-container">
                    <button type="button" id="user-dropdown-btn" class="flex items-center gap-1 sm:gap-2 bg-primary/5 hover:bg-primary/10 border border-primary/20 text-primary px-2 sm:px-3.5 py-1 sm:py-1.5 rounded-xl text-[11px] sm:text-xs md:text-sm font-semibold transition-all shrink-0 cursor-pointer select-none active:scale-98">
                        <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-primary text-white flex items-center justify-center text-[10px] sm:text-xs font-bold shrink-0 shadow-sm">
                            ${firstName.charAt(0).toUpperCase()}
                        </div>
                        <span class="max-w-[50px] sm:max-w-[100px] md:max-w-[120px] truncate">${firstName}</span>
                        <span id="user-dropdown-chevron" class="material-symbols-outlined text-[14px] sm:text-[18px] transition-transform duration-200 pointer-events-none">expand_more</span>
                    </button>

                    <!-- Bubble Menu with seamless padding bridge -->
                    <div id="user-dropdown-menu" class="user-dropdown-bubble">
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

            attachUserDropdownEvents();
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

// Attach User Dropdown Events
function attachUserDropdownEvents() {
    const wrapper = document.getElementById('user-dropdown-wrapper');
    const btn = document.getElementById('user-dropdown-btn');
    const menu = document.getElementById('user-dropdown-menu');
    if (!wrapper || !btn || !menu) return;

    let isLocked = false;
    let hoverTimeout = null;

    // Toggle on Button Click
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        isLocked = !isLocked;
        if (isLocked) {
            wrapper.classList.add('is-active');
        } else {
            wrapper.classList.remove('is-active');
        }
    });

    // Hover Enter
    wrapper.addEventListener('mouseenter', () => {
        if (hoverTimeout) clearTimeout(hoverTimeout);
        wrapper.classList.add('is-active');
    });

    // Hover Leave (Graceful Delay)
    wrapper.addEventListener('mouseleave', () => {
        if (hoverTimeout) clearTimeout(hoverTimeout);
        if (!isLocked) {
            hoverTimeout = setTimeout(() => {
                wrapper.classList.remove('is-active');
            }, 300); // 300ms buffer allows smooth mouse movement
        }
    });

    // Prevent closing when clicking inside the bubble
    menu.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    // Close when clicking anywhere outside
    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) {
            isLocked = false;
            wrapper.classList.remove('is-active');
        }
    });
}

async function handleLogout() {
    if (confirm('Apakah Anda yakin ingin keluar?')) {
        await fetch('auth_api.php?action=logout');
        window.location.reload();
    }
}
window.handleLogout = handleLogout;
