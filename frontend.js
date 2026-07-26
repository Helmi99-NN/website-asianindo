// Frontend global script for Asianindo CMS Integration & Tracking

document.addEventListener('DOMContentLoaded', () => {
    
    // 1. ANALYTICS TRACKING
    function trackEvent(eventName, productId = '') {
        // Find the absolute path to api.php (assuming frontend is in root, admin is in /admin)
        let apiPath = window.location.pathname.includes('/admin/') ? 'api.php' : 'admin/api.php';
        
        fetch(apiPath + '?action=track_event', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event: eventName, product_id: productId })
        }).catch(err => console.error('Tracking error:', err));
    }

    // A. Track Page Visitor
    trackEvent('visitor');

    // B. Track Product View
    if (window.location.pathname.includes('product.html')) {
        let params = new URLSearchParams(window.location.search);
        let id = params.get('id');
        if (id) {
            trackEvent('product_view', id);
        }
    }

    // C. Track WA Clicks
    document.body.addEventListener('click', function(e) {
        // Find closest anchor tag
        let a = e.target.closest('a');
        if (a && a.href && (a.href.includes('wa.me') || a.href.includes('api.whatsapp.com'))) {
            trackEvent('wa_click');
        }
        
        // Also track if any button has text "Kirim Pesan"
        if (a && a.textContent && a.textContent.toLowerCase().includes('kirim pesan')) {
            trackEvent('message');
        }
    });

    // 2. LOAD GLOBAL SETTINGS (Optional implementation for header/footer)
    // If the page needs global settings injected, it can call this
    window.loadGlobalSettings = async function() {
        try {
            let res = await fetch('admin/api.php?action=get_module&module=settings');
            let settings = await res.json();
            
            // Apply to common elements if they have specific IDs or data attributes
            document.querySelectorAll('[data-cms="company_name"]').forEach(el => el.textContent = settings.company_name);
            document.querySelectorAll('[data-cms="whatsapp"]').forEach(el => {
                if (el.tagName === 'A') {
                    // Update wa link
                    let phone = settings.whatsapp.replace(/[^0-9]/g, '');
                    if(phone.startsWith('0')) phone = '62' + phone.substring(1);
                    let url = new URL(el.href);
                    el.href = `https://wa.me/${phone}${url.search}`;
                } else {
                    el.textContent = settings.whatsapp;
                }
            });
            document.querySelectorAll('[data-cms="email"]').forEach(el => {
                if (el.tagName === 'A') el.href = `mailto:${settings.email}`;
                el.textContent = settings.email;
            });
            
            // Note: more specific DOM replacements can be done per page
        } catch(err) {
            console.error('Failed to load settings:', err);
        }
    }
    
    // Auto-load settings if requested by body class
    if (document.body.classList.contains('cms-enabled')) {
        window.loadGlobalSettings();
    }
});
