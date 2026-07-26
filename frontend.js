/**
 * Asianindo CMS Frontend Loader & Analytics Tracker
 * Loads CMS data and dynamically updates page content
 */
(function() {
    'use strict';

    const API_BASE = '/admin/api.php';

    // ======================== ANALYTICS TRACKING ========================
    function trackEvent(eventName, productId) {
        fetch(API_BASE + '?action=track_event', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event: eventName, product_id: productId || '' })
        }).catch(function(){});
    }

    // Track page visit
    trackEvent('visitor');

    // Track product view
    if (window.location.pathname.indexOf('product.html') !== -1) {
        var params = new URLSearchParams(window.location.search);
        var id = params.get('id');
        if (id) trackEvent('product_view', id);
    }

    // Track WA clicks
    document.addEventListener('click', function(e) {
        var a = e.target.closest ? e.target.closest('a') : null;
        if (a && a.href) {
            if (a.href.indexOf('wa.me') !== -1 || a.href.indexOf('whatsapp') !== -1) {
                trackEvent('wa_click');
            }
        }
    });

    // ======================== CMS DATA LOADER ========================
    function fetchCMS(module) {
        return fetch(API_BASE + '?action=get_public&module=' + module)
            .then(function(r) { return r.json(); })
            .catch(function() { return null; });
    }

    function setText(id, text) {
        var el = document.getElementById(id);
        if (el && text) el.textContent = text;
    }

    function setHTML(id, html) {
        var el = document.getElementById(id);
        if (el && html) el.innerHTML = html;
    }

    function setAttr(id, attr, val) {
        var el = document.getElementById(id);
        if (el && val) el.setAttribute(attr, val);
    }

    // ======================== GLOBAL SETTINGS (all pages) ========================
    function applySettings(s) {
        if (!s || !s.company_name) return;

        // Update all WA links
        var waNum = (s.whatsapp || '').replace(/[^0-9]/g, '');
        var waMsg = encodeURIComponent(s.wa_message || 'Halo, saya ingin konsultasi');
        document.querySelectorAll('a[href*="wa.me"]').forEach(function(a) {
            a.href = 'https://wa.me/' + waNum + '?text=' + waMsg;
        });

        // Footer updates
        setText('cms-footer-company', s.company_name);
        setText('cms-footer-bio', s.tagline || s.description);
        setText('cms-footer-address', s.address);
        setText('cms-footer-phone', s.whatsapp);
        setText('cms-footer-email', s.email);
        setText('cms-footer-hours', s.hours);
        setText('cms-footer-copyright', s.copyright);

        // Social media links
        if (s.youtube) setAttr('cms-social-youtube', 'href', s.youtube);
        if (s.tiktok) setAttr('cms-social-tiktok', 'href', s.tiktok);
        if (s.instagram) setAttr('cms-social-instagram', 'href', s.instagram);
        if (s.facebook) setAttr('cms-social-facebook', 'href', s.facebook);
    }

    // ======================== HOMEPAGE ========================
    function applyHomepage(data) {
        if (!data || !data.hero_title) return;

        setText('cms-hero-title', data.hero_title);
        setText('cms-hero-subtitle', data.hero_subtitle);

        // Stats
        if (data.stats && data.stats.length) {
            var statsEl = document.getElementById('cms-stats');
            if (statsEl) {
                var html = '';
                data.stats.forEach(function(st) {
                    html += '<div class="text-center"><p class="text-3xl md:text-4xl font-bold text-white">' + st.value + '</p><p class="text-sm text-white/80 mt-1">' + st.label + '</p></div>';
                });
                statsEl.innerHTML = html;
            }
        }

        // About section on homepage
        setText('cms-home-about-badge', data.about_badge);
        setText('cms-home-about-title', data.about_title);
        setText('cms-home-about-desc', data.about_desc);

        // Advantages
        if (data.advantages && data.advantages.length) {
            var advEl = document.getElementById('cms-advantages');
            if (advEl) {
                var html = '';
                data.advantages.forEach(function(adv) {
                    html += '<div class="bg-white rounded-2xl p-6 shadow-md hover:shadow-lg transition-shadow border border-gray-100">' +
                        '<div class="w-14 h-14 rounded-xl flex items-center justify-center mb-4" style="background-color:#e9ddff">' +
                        '<i class="' + adv.icon + ' text-xl" style="color:#330e7a"></i></div>' +
                        '<h3 class="text-lg font-bold text-gray-800 mb-2">' + adv.title + '</h3>' +
                        '<p class="text-sm text-gray-600">' + adv.desc + '</p></div>';
                });
                advEl.innerHTML = html;
            }
        }

        // Testimonials
        if (data.testimonials && data.testimonials.length) {
            var testEl = document.getElementById('cms-testimonials');
            if (testEl) {
                var html = '';
                data.testimonials.forEach(function(t) {
                    if (!t.name) return;
                    var stars = '';
                    for (var i = 0; i < (t.rating || 5); i++) stars += '<i class="fas fa-star text-yellow-400 text-sm"></i>';
                    html += '<div class="bg-white rounded-2xl p-6 shadow-md border border-gray-100">' +
                        '<div class="flex gap-1 mb-3">' + stars + '</div>' +
                        '<p class="text-gray-600 text-sm mb-4 italic">"' + t.text + '"</p>' +
                        '<div class="border-t pt-3"><p class="font-bold text-gray-800">' + t.name + '</p>' +
                        '<p class="text-xs text-gray-500">' + (t.title || '') + '</p></div></div>';
                });
                testEl.innerHTML = html;
            }
        }

        // CTA
        setText('cms-cta-title', data.cta_title);
        setText('cms-cta-subtitle', data.cta_subtitle);
        setText('cms-cta-button', data.cta_button);
    }

    // ======================== ABOUT PAGE ========================
    function applyAbout(data) {
        if (!data || !data.hero_title) return;

        setText('cms-about-hero-title', data.hero_title);
        setText('cms-about-hero-desc', data.hero_desc);

        // Quick info
        if (data.quick_info) {
            setText('cms-about-qi-name', data.quick_info.name);
            setText('cms-about-qi-year', data.quick_info.year);
            setText('cms-about-qi-address', data.quick_info.address);
            setText('cms-about-qi-scope', data.quick_info.scope);
        }

        // Profile
        if (data.profile_text) setHTML('cms-about-profile', data.profile_text);

        // Vision & Mission
        if (data.vision) setText('cms-about-vision', data.vision);
        if (data.missions && data.missions.length) {
            var mEl = document.getElementById('cms-about-missions');
            if (mEl) {
                var html = '';
                data.missions.forEach(function(m) {
                    if (m) html += '<li class="flex items-start gap-3"><i class="fas fa-check-circle text-green-500 mt-1"></i><span>' + m + '</span></li>';
                });
                mEl.innerHTML = html;
            }
        }
    }

    // ======================== CONTACT PAGE ========================
    function applyContact(data) {
        if (!data || !data.hero_title) return;

        setText('cms-contact-hero-title', data.hero_title);
        setText('cms-contact-hero-desc', data.hero_desc);
        setText('cms-contact-address', data.office_address);
        setText('cms-contact-phone', data.phone);
        setText('cms-contact-email', data.email);
        setText('cms-contact-cs-name', data.cs_name);
        setText('cms-contact-cs-hours', data.cs_hours);

        // Maps embed
        if (data.maps_embed) {
            var mapsEl = document.getElementById('cms-contact-maps');
            if (mapsEl) {
                mapsEl.src = data.maps_embed;
            }
        }
    }

    // ======================== BLOG / ARTICLES ========================
    function applyArticles(articles) {
        if (!articles || !Array.isArray(articles) || !articles.length) return;

        // Set global for article.html to use
        window.CMS_ARTICLES = articles;

        // If we're on blog.html, render the article list
        var blogGrid = document.getElementById('cms-blog-grid');
        if (blogGrid) {
            var html = '';
            articles.forEach(function(a) {
                html += '<a href="article.html?id=' + a.id + '" class="bg-white rounded-2xl shadow-md overflow-hidden hover:shadow-lg transition-shadow border border-gray-100 block">' +
                    (a.existing_image ? '<img src="' + a.existing_image + '" class="w-full h-48 object-cover" alt="' + a.title + '">' : '<div class="w-full h-48 bg-gray-100 flex items-center justify-center"><i class="fas fa-image text-4xl text-gray-300"></i></div>') +
                    '<div class="p-5">' +
                    '<span class="text-xs font-semibold px-2 py-1 rounded-full" style="background-color:#e9ddff;color:#330e7a">' + (a.category || 'Umum') + '</span>' +
                    '<h3 class="text-lg font-bold text-gray-800 mt-3 mb-2">' + a.title + '</h3>' +
                    '<p class="text-sm text-gray-500 mb-3">' + (a.excerpt || '') + '</p>' +
                    '<p class="text-xs text-gray-400">' + (a.publish_date || '') + '</p>' +
                    '</div></a>';
            });
            blogGrid.innerHTML = html;
        }

        // If on article.html, render single article
        if (window.location.pathname.indexOf('article.html') !== -1) {
            var aParams = new URLSearchParams(window.location.search);
            var aId = aParams.get('id');
            if (aId) {
                var article = articles.find(function(a) { return a.id === aId; });
                if (article) {
                    setText('cms-article-title', article.title);
                    setText('cms-article-category', article.category);
                    setText('cms-article-date', article.publish_date);
                    setHTML('cms-article-content', article.content);
                    if (article.existing_image) {
                        var coverEl = document.getElementById('cms-article-cover');
                        if (coverEl) coverEl.src = article.existing_image;
                    }
                    // Update page title
                    document.title = article.title + ' - CV Asianindo';
                }
            }
        }
    }

    // ======================== INIT ========================
    document.addEventListener('DOMContentLoaded', function() {
        var path = window.location.pathname.toLowerCase();

        // Always load settings for header/footer
        fetchCMS('settings').then(applySettings);

        // Page-specific loaders
        if (path.indexOf('index.html') !== -1 || path.endsWith('/') || path.endsWith('.com') || path.endsWith('.com/')) {
            fetchCMS('homepage').then(applyHomepage);
        }
        else if (path.indexOf('about.html') !== -1) {
            fetchCMS('about').then(applyAbout);
        }
        else if (path.indexOf('kontak.html') !== -1) {
            fetchCMS('contact').then(applyContact);
        }
        else if (path.indexOf('blog.html') !== -1 || path.indexOf('article.html') !== -1) {
            fetchCMS('articles').then(applyArticles);
        }
    });
})();
