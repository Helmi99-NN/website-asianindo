/**
 * Asianindo CMS Frontend Loader & Analytics Tracker
 * Loads CMS data and dynamically updates page content preserving M3 design system.
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
            if (a.href.indexOf('wa.me') !== -1 || a.href.indexOf('whatsapp') !== -1 || a.href.indexOf('api.whatsapp.com') !== -1) {
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
        var waMsg = encodeURIComponent(s.wa_message || 'Halo CV Asianindo, saya ingin konsultasi mesin');
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

        // Stats - Preserve M3 classes
        if (data.stats && data.stats.length) {
            var statsEl = document.getElementById('cms-stats');
            if (statsEl) {
                var html = '';
                data.stats.forEach(function(st) {
                    html += '<div>' +
                            '<p class="font-headline-md text-headline-md text-white font-black">' + st.value + '</p>' +
                            '<p class="font-label-md text-label-md text-inverse-primary/80">' + st.label + '</p>' +
                            '</div>';
                });
                statsEl.innerHTML = html;
            }
        }

        // About section on homepage
        setText('cms-home-about-badge', data.about_badge);
        setText('cms-home-about-title', data.about_title);
        setText('cms-home-about-desc', data.about_desc);

        // Advantages - Preserve M3 classes
        if (data.advantages && data.advantages.length) {
            var advEl = document.getElementById('cms-advantages');
            if (advEl) {
                var html = '';
                data.advantages.forEach(function(adv, i) {
                    var icon = adv.icon.includes('fa-') ? adv.icon.split('-').pop() : adv.icon; // try to convert FA to material if needed, or just use as is if valid material icon. Let's assume user inputted valid material icon like "shield"
                    html += '<div class="feature-card bg-white rounded-2xl p-7 border border-outline-variant/15 reveal active">' +
                        '<div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-fixed to-secondary-container flex items-center justify-center mb-5">' +
                        '<span class="material-symbols-outlined text-primary-container" style="font-variation-settings: \'FILL\' 1; font-size: 28px;">' + icon + '</span></div>' +
                        '<h3 class="font-headline-sm text-headline-sm text-on-surface text-lg font-bold mb-3">' + adv.title + '</h3>' +
                        '<p class="font-body-md text-body-md text-on-surface-variant">' + adv.desc + '</p></div>';
                });
                advEl.innerHTML = html;
            }
        }

        // Testimonials - Preserve M3 classes
        if (data.testimonials && data.testimonials.length) {
            var testEl = document.getElementById('cms-testimonials');
            if (testEl) {
                var html = '';
                data.testimonials.forEach(function(t, i) {
                    if (!t.name) return;
                    var stars = '';
                    for (var j = 0; j < (t.rating || 5); j++) {
                        stars += '<span class="material-symbols-outlined" style="font-size: 18px; font-variation-settings: \'FILL\' 1; color: #FFB800;">star</span>';
                    }
                    var initial = t.name.charAt(0).toUpperCase();
                    html += '<div class="testimonial-card bg-white rounded-2xl p-7 border border-outline-variant/15 reveal active relative">' +
                        '<div class="absolute top-6 right-6 text-primary-fixed"><span class="material-symbols-outlined" style="font-size: 40px; font-variation-settings: \'FILL\' 1;">format_quote</span></div>' +
                        '<div class="flex gap-0.5 mb-4">' + stars + '</div>' +
                        '<p class="font-body-md text-body-md text-on-surface-variant mb-6 leading-relaxed">"' + t.text + '"</p>' +
                        '<div class="flex items-center gap-4 pt-4 border-t border-outline-variant/15">' +
                        '<div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-container to-primary flex items-center justify-center flex-shrink-0"><span class="text-white font-bold text-lg">' + initial + '</span></div>' +
                        '<div><p class="font-label-md text-label-md text-on-surface font-bold">' + t.name + '</p>' +
                        '<p class="text-on-surface-variant text-sm">' + (t.title || '') + '</p></div></div></div>';
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
                    if (m) html += '<li class="flex items-start gap-3"><span class="material-symbols-outlined text-primary shrink-0 mt-0.5">check_circle</span>' + m + '</li>';
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

        // If we're on blog.html, render the article list with proper M3 classes
        var blogGrid = document.getElementById('cms-blog-grid');
        if (blogGrid) {
            var html = '';
            articles.forEach(function(a, i) {
                var img = a.existing_image || 'images/default-article.jpg';
                html += '<a href="article.html?id=' + a.id + '" class="group bg-white rounded-3xl border border-outline-variant/20 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 reveal active flex flex-col">' +
                    '<div class="relative overflow-hidden h-56 bg-surface-container-low">' +
                    '<img src="' + img + '" alt="' + a.title + '" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />' +
                    '<div class="absolute top-4 left-4 bg-white/90 backdrop-blur-md px-3 py-1.5 rounded-full font-label-md text-xs font-bold text-primary shadow-sm">' + (a.category || 'Artikel') + '</div></div>' +
                    '<div class="p-6 flex flex-col flex-grow">' +
                    '<div class="flex items-center gap-2 mb-3 text-on-surface-variant text-sm font-medium"><span class="material-symbols-outlined" style="font-size: 16px;">calendar_today</span>' + (a.publish_date || '') + '</div>' +
                    '<h3 class="font-headline-sm text-xl font-bold text-on-surface mb-3 group-hover:text-primary transition-colors line-clamp-3 leading-snug">' + a.title + '</h3>' +
                    '<p class="text-on-surface-variant text-base line-clamp-3 leading-relaxed flex-grow">' + (a.excerpt || '') + '</p>' +
                    '<div class="mt-6 flex items-center text-primary font-bold text-sm group-hover:gap-2 transition-all">Baca Selengkapnya <span class="material-symbols-outlined" style="font-size: 18px;">arrow_forward</span></div>' +
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
                    // Track View
                    fetch('admin/api.php?action=track_article_view', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: aId })
                    }).catch(function(e) { console.error('Tracking error', e); });
                    
                    setText('cms-article-title', article.title);
                    setText('cms-article-category', article.category);
                    setText('cms-article-date', article.publish_date);
                    
                    var viewsEl = document.getElementById('cms-article-views');
                    if (viewsEl) viewsEl.innerText = article.views ? article.views + ' x dilihat' : '0 x dilihat';
                    
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
