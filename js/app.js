/**
 * Surprise! Store - Main JavaScript
 */

// DOM Ready
document.addEventListener('DOMContentLoaded', function () {
    try { initHeader(); } catch (e) { console.warn('Header init error:', e); }
    try { initSlider(); } catch (e) { console.warn('Slider init error:', e); }
    try { initMobileMenu(); } catch (e) { console.warn('Mobile menu init error:', e); }
    try { initHeaderSearch(); } catch (e) { console.warn('Header search init error:', e); }
    try { initAnimations(); } catch (e) { console.warn('Animations init error:', e); }
    try { initProductSliders(); } catch (e) { console.warn('Product sliders init error:', e); }
    try { initLazyLoading(); } catch (e) { console.warn('Lazy loading init error:', e); }
});

// Header scroll effect
function initHeader() {
    const header = document.getElementById('header');
    if (!header) return;

    window.addEventListener('scroll', function () {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
}

// Hero Slider
function initSlider() {
    const slider = document.getElementById('heroSlider');
    const dotsContainer = document.getElementById('heroDots');

    if (!slider || !dotsContainer) return;

    const slides = slider.querySelectorAll('.hero-slide');
    if (slides.length === 0) return;

    let currentSlide = 0;

    // Create dots
    slides.forEach((_, index) => {
        const dot = document.createElement('span');
        dot.className = 'hero-dot' + (index === 0 ? ' active' : '');
        dot.addEventListener('click', () => goToSlide(index));
        dotsContainer.appendChild(dot);
    });

    const dots = dotsContainer.querySelectorAll('.hero-dot');

    function goToSlide(index) {
        currentSlide = index;
        slider.style.transform = `translateX(${index * 100}%)`;

        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        goToSlide(currentSlide);
    }

    // Auto-play
    setInterval(nextSlide, 5000);

    // Touch support
    let touchStartX = 0;
    let touchEndX = 0;

    slider.addEventListener('touchstart', e => {
        touchStartX = e.changedTouches[0].screenX;
    });

    slider.addEventListener('touchend', e => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });

    function handleSwipe() {
        const diff = touchStartX - touchEndX;
        if (Math.abs(diff) > 50) {
            if (diff > 0) {
                // Swipe left - next (RTL)
                currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            } else {
                // Swipe right - prev (RTL)
                currentSlide = (currentSlide + 1) % slides.length;
            }
            goToSlide(currentSlide);
        }
    }
}

// Mobile Menu - Clean Implementation
function initMobileMenu() {
    const toggle = document.getElementById('menuToggle');
    const navLinks = document.getElementById('navLinks');
    const overlay = document.getElementById('navOverlay');

    // Exit if elements don't exist
    if (!toggle || !navLinks) {
        console.warn('Mobile menu: Required elements not found');
        return;
    }

    // If overlay doesn't exist in HTML, create it (fallback)
    let overlayElement = overlay;
    if (!overlayElement) {
        overlayElement = document.createElement('div');
        overlayElement.className = 'nav-overlay';
        overlayElement.id = 'navOverlay';
        document.body.insertBefore(overlayElement, document.body.firstChild);
    }

    // Track menu state
    let isMenuOpen = false;

    // Open menu function
    function openMenu() {
        if (isMenuOpen) return;
        isMenuOpen = true;

        navLinks.classList.add('active');
        toggle.classList.add('active');
        overlayElement.classList.add('active');
        document.body.classList.add('menu-open');
        document.body.style.overflow = 'hidden';
    }

    // Close menu function
    function closeMenu() {
        if (!isMenuOpen) return;
        isMenuOpen = false;

        navLinks.classList.remove('active');
        toggle.classList.remove('active');
        overlayElement.classList.remove('active');
        document.body.classList.remove('menu-open');
        document.body.style.overflow = '';
    }

    // Toggle function
    function toggleMenu(e) {
        e.preventDefault();
        e.stopPropagation();

        if (isMenuOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    }

    // Menu toggle button - click
    toggle.addEventListener('click', toggleMenu);

    // Overlay click - close menu
    overlayElement.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeMenu();
    });

    // Close menu on link click - ALL links should close menu first, then navigate
    navLinks.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function (e) {
            const href = this.getAttribute('href');

            // If it's a hash link on same page
            if (href && href.startsWith('#')) {
                closeMenu();
                return;
            }

            // If it's onclick handler (like contact modal)
            if (this.hasAttribute('onclick')) {
                closeMenu();
                return;
            }

            // For normal navigation links, close menu then navigate
            if (href && href !== '#') {
                e.preventDefault();
                closeMenu();
                // Small delay to ensure menu closes before navigation
                setTimeout(() => {
                    window.location.href = href;
                }, 100);
            }
        });
    });

    // Close menu on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isMenuOpen) {
            closeMenu();
        }
    });

    // Handle window resize - close menu if window becomes wide
    window.addEventListener('resize', function () {
        if (window.innerWidth > 1024 && isMenuOpen) {
            closeMenu();
        }
    });
}

// Header Search - Simple Inline Expandable (NO OVERLAY)
function initHeaderSearch() {
    const wrapper = document.getElementById('headerSearchWrapper');
    const toggle = document.getElementById('headerSearchToggle');
    const form = document.getElementById('headerSearchForm');
    const input = document.getElementById('headerSearchInput');
    const closeBtn = document.getElementById('headerSearchClose');

    // Exit if elements don't exist
    if (!wrapper || !form || !input) {
        return;
    }

    // Check if we need mobile behavior
    const isMobile = () => window.innerWidth <= 768;

    let isSearchOpen = false;

    // Open search - just toggle class, NO overlay
    function openSearch() {
        if (isSearchOpen || !isMobile()) return;
        isSearchOpen = true;

        wrapper.classList.add('active');

        // Focus input after a short delay for CSS animation
        setTimeout(() => {
            input.focus();
        }, 50);
    }

    // Close search - just toggle class
    function closeSearch() {
        if (!isSearchOpen) return;
        isSearchOpen = false;

        wrapper.classList.remove('active');
        input.blur();
    }

    // Toggle button - only on mobile
    if (toggle) {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (isMobile()) {
                openSearch();
            }
        });

        // Touch support for toggle button
        toggle.addEventListener('touchend', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (isMobile()) {
                openSearch();
            }
        }, { passive: false });
    }

    // Close button
    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            closeSearch();
        });

        closeBtn.addEventListener('touchend', function (e) {
            e.preventDefault();
            e.stopPropagation();
            closeSearch();
        }, { passive: false });
    }

    // Close on clicking outside (document level)
    document.addEventListener('click', function (e) {
        if (isSearchOpen && !wrapper.contains(e.target)) {
            closeSearch();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isSearchOpen) {
            closeSearch();
        }
    });

    // Handle window resize - close search if window becomes wide
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768 && isSearchOpen) {
            closeSearch();
        }
    });

    // Prevent form from closing when interacting
    form.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    form.addEventListener('touchend', function (e) {
        e.stopPropagation();
    }, { passive: true });

    // ═══════════════════════════════════════════════════════════════
    // AUTOCOMPLETE - Amazon Style Search Suggestions
    // ═══════════════════════════════════════════════════════════════

    console.log('🔍 Autocomplete: Initializing...');

    // Ensure wrapper has position relative for absolute positioning of dropdown
    wrapper.style.position = 'relative';

    // Create autocomplete dropdown - attach to WRAPPER (not form) for proper positioning
    let autocompleteDropdown = document.getElementById('searchAutocomplete');
    if (!autocompleteDropdown) {
        autocompleteDropdown = document.createElement('div');
        autocompleteDropdown.id = 'searchAutocomplete';
        autocompleteDropdown.className = 'search-autocomplete';
        autocompleteDropdown.setAttribute('dir', 'rtl');
        wrapper.appendChild(autocompleteDropdown); // Append to wrapper, not form
        console.log('🔍 Autocomplete: Dropdown created and attached to wrapper');
    }

    // Create clear button
    let clearBtn = form.querySelector('.header-search-clear');
    if (!clearBtn) {
        clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'header-search-clear';
        clearBtn.innerHTML = '✕';
        clearBtn.style.display = 'none';
        clearBtn.setAttribute('aria-label', 'مسح البحث');
        // Insert before submit button
        const submitBtn = form.querySelector('.header-search-submit');
        if (submitBtn) {
            form.insertBefore(clearBtn, submitBtn);
        } else {
            form.appendChild(clearBtn);
        }
    }

    let debounceTimer = null;
    let currentQuery = '';
    let selectedIndex = -1;

    // Normalize Arabic text for matching (client-side)
    function normalizeArabic(text) {
        if (!text) return '';

        // Remove diacritics
        text = text.replace(/[\u064B-\u065F\u0670]/g, '');

        // Normalize Alef variants
        text = text.replace(/[أإآٱ]/g, 'ا');

        // Normalize Yaa with Alef Maksura
        text = text.replace(/ى/g, 'ي');

        // Normalize Taa Marbuta
        text = text.replace(/ة/g, 'ه');

        // Normalize Hamza on Waw and Yaa
        text = text.replace(/ؤ/g, 'و');
        text = text.replace(/ئ/g, 'ي');

        return text.trim().toLowerCase();
    }

    // Format price
    function formatSuggestionPrice(price) {
        return new Intl.NumberFormat('en-US').format(price) + ' د.ع';
    }

    // Show autocomplete dropdown
    function showAutocomplete(data) {
        console.log('🔍 showAutocomplete called with:', data);

        if (!data || (!data.products?.length && !data.suggestions?.length)) {
            console.log('🔍 No products/suggestions, hiding autocomplete');
            hideAutocomplete();
            return;
        }

        let html = '';

        // Product results
        if (data.products && data.products.length > 0) {
            html += '<div class="autocomplete-section">';
            html += '<div class="autocomplete-section-title">🎁 منتجات مقترحة</div>';

            data.products.forEach((product, index) => {
                // Build image URL - handle different formats
                // API returns: "products/filename.jpg" so we prepend "images/"
                let imageUrl = 'images/products/default.png';
                if (product.image) {
                    if (product.image.startsWith('http')) {
                        imageUrl = product.image;
                    } else if (product.image.startsWith('images/')) {
                        imageUrl = product.image;
                    } else if (product.image.startsWith('products/')) {
                        // API returns "products/filename.jpg"
                        imageUrl = `images/${product.image}`;
                    } else {
                        imageUrl = `images/products/${product.image}`;
                    }
                }

                const priceHtml = product.old_price > product.price ?
                    `<span class="autocomplete-old-price">${formatSuggestionPrice(product.old_price)}</span>
                     <span class="autocomplete-price">${formatSuggestionPrice(product.price)}</span>` :
                    `<span class="autocomplete-price">${formatSuggestionPrice(product.price)}</span>`;

                html += `
                    <a href="/product?id=${product.id}" class="autocomplete-item autocomplete-product" data-index="${index}">
                        <img src="${imageUrl}" alt="${product.name}" class="autocomplete-thumb" loading="lazy">
                        <div class="autocomplete-info">
                            <div class="autocomplete-name">${highlightMatch(product.name, currentQuery)}</div>
                            <div class="autocomplete-meta">${priceHtml}</div>
                        </div>
                    </a>
                `;
            });

            html += '</div>';
        }

        // Search suggestions (text only)
        if (data.suggestions && data.suggestions.length > 0 && data.suggestions.length !== data.products?.length) {
            html += '<div class="autocomplete-section">';
            html += '<div class="autocomplete-section-title">🔍 اقتراحات البحث</div>';

            data.suggestions.forEach((suggestion, index) => {
                const suggestionIndex = (data.products?.length || 0) + index;
                html += `
                    <div class="autocomplete-item autocomplete-suggestion" data-query="${suggestion}" data-index="${suggestionIndex}">
                        <span class="autocomplete-icon">🔍</span>
                        <span class="autocomplete-text">${highlightMatch(suggestion, currentQuery)}</span>
                    </div>
                `;
            });

            html += '</div>';
        }

        // View all results link
        if (currentQuery.length > 0) {
            html += `
                <a href="/products?q=${encodeURIComponent(currentQuery)}" class="autocomplete-view-all">
                    عرض جميع النتائج لـ "${currentQuery}" ←
                </a>
            `;
        }

        autocompleteDropdown.innerHTML = html;
        autocompleteDropdown.classList.add('active');
        selectedIndex = -1;

        // Add click handlers for suggestions
        autocompleteDropdown.querySelectorAll('.autocomplete-suggestion').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const query = item.dataset.query;
                input.value = query;
                hideAutocomplete();
                form.submit();
            });
        });

        // Add hover handlers
        autocompleteDropdown.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('mouseenter', () => {
                selectedIndex = parseInt(item.dataset.index) || 0;
                updateSelection();
            });
        });
    }

    // Highlight matching text
    function highlightMatch(text, query) {
        if (!query) return text;

        const normalizedText = normalizeArabic(text);
        const normalizedQuery = normalizeArabic(query);

        // Find match position in normalized text
        const matchIndex = normalizedText.indexOf(normalizedQuery);
        if (matchIndex === -1) return text;

        // Apply highlight to original text at same position
        // This is approximate but works for most cases
        const before = text.substring(0, matchIndex);
        const match = text.substring(matchIndex, matchIndex + query.length);
        const after = text.substring(matchIndex + query.length);

        return `${before}<mark>${match}</mark>${after}`;
    }

    // Hide autocomplete
    function hideAutocomplete() {
        autocompleteDropdown.classList.remove('active');
        autocompleteDropdown.innerHTML = '';
        selectedIndex = -1;
    }

    // Update keyboard selection
    function updateSelection() {
        const items = autocompleteDropdown.querySelectorAll('.autocomplete-item');
        items.forEach((item, index) => {
            item.classList.toggle('selected', index === selectedIndex);
        });
    }

    // Fetch suggestions from API
    async function fetchSuggestions(query) {
        if (query.length < 1) {
            hideAutocomplete();
            return;
        }

        try {
            // Get base URL for API calls
            const baseUrl = window.location.pathname.includes('/admin/') ? '../' : '';
            const apiUrl = `${baseUrl}api/search-suggestions.php?q=${encodeURIComponent(query)}&limit=6`;
            console.log('🔍 API Call:', apiUrl);

            const response = await fetch(apiUrl);
            const data = await response.json();

            console.log('🔍 API Response:', data);

            if (data.success) {
                showAutocomplete(data);
            } else {
                console.warn('🔍 API returned success=false');
                hideAutocomplete();
            }
        } catch (error) {
            console.error('🔍 Search suggestions error:', error);
            hideAutocomplete();
        }
    }

    // Input event - debounced search (triggers from FIRST character)
    input.addEventListener('input', function () {
        const query = this.value.trim();
        currentQuery = query;

        console.log('🔍 Input event:', query, 'Length:', query.length);

        // Show/hide clear button
        clearBtn.style.display = query.length > 0 ? 'flex' : 'none';

        // Debounce API calls
        clearTimeout(debounceTimer);

        if (query.length < 1) {
            hideAutocomplete();
            return;
        }

        // Fetch immediately for first character, then debounce
        debounceTimer = setTimeout(() => {
            console.log('🔍 Fetching suggestions for:', query);
            fetchSuggestions(query);
        }, 150); // 150ms debounce - faster for better UX
    });

    // Clear button
    clearBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        input.value = '';
        currentQuery = '';
        clearBtn.style.display = 'none';
        hideAutocomplete();
        input.focus();
    });

    // Keyboard navigation
    input.addEventListener('keydown', function (e) {
        const items = autocompleteDropdown.querySelectorAll('.autocomplete-item');

        if (!autocompleteDropdown.classList.contains('active') || items.length === 0) {
            return;
        }

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateSelection();
                break;

            case 'ArrowUp':
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection();
                break;

            case 'Enter':
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    e.preventDefault();
                    const item = items[selectedIndex];
                    if (item.classList.contains('autocomplete-suggestion')) {
                        input.value = item.dataset.query;
                        hideAutocomplete();
                        form.submit();
                    } else if (item.href) {
                        window.location.href = item.href;
                    }
                }
                break;

            case 'Escape':
                hideAutocomplete();
                break;
        }
    });

    // Focus event - show suggestions if there's a query
    input.addEventListener('focus', function () {
        if (currentQuery.length > 0 || this.value.trim().length > 0) {
            currentQuery = this.value.trim();
            if (currentQuery.length >= 1) {
                fetchSuggestions(currentQuery);
            }
        }
        // Update clear button visibility
        clearBtn.style.display = this.value.trim().length > 0 ? 'flex' : 'none';
    });

    // Click outside to close
    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) {
            hideAutocomplete();
        }
    });

    // Prevent dropdown clicks from bubbling
    autocompleteDropdown.addEventListener('click', function (e) {
        e.stopPropagation();
    });
}


// Scroll animations
function initAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.product-card').forEach(card => {
        observer.observe(card);
    });
}

// Format price
function formatPrice(price) {
    return new Intl.NumberFormat('en-US').format(price) + ' د.ع';
}

// Show notification
function showNotification(message, type = 'success', showCartBtn = false) {
    // Remove existing notifications
    document.querySelectorAll('.notification').forEach(n => n.remove());

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;

    let cartBtnHtml = '';
    if (showCartBtn && type === 'success') {
        cartBtnHtml = `<a href="/cart" style="background: white; color: #4CAF50; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 700; margin-right: 10px; font-size: 0.85rem;">🛒 عرض السلة</a>`;
    }

    notification.innerHTML = `
        <span>${message}</span>
        <div style="display: flex; align-items: center; gap: 10px;">
            ${cartBtnHtml}
            <button onclick="this.closest('.notification').remove()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">×</button>
        </div>
    `;

    notification.style.cssText = `
        position: fixed;
        bottom: calc(90px + env(safe-area-inset-bottom));
        left: 20px;
        right: 20px;
        max-width: 450px;
        margin: 0 auto;
        padding: 18px 22px;
        background: ${type === 'success' ? 'linear-gradient(135deg, #4CAF50, #45a049)' : 'linear-gradient(135deg, #F44336, #d32f2f)'};
        color: white;
        border-radius: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 8px 30px rgba(0,0,0,0.25);
        z-index: 10001;
        animation: slideUp 0.3s ease;
        font-weight: 600;
    `;

    document.body.appendChild(notification);

    setTimeout(() => notification.remove(), showCartBtn ? 5000 : 3000);
}

// Product Image Sliders - Auto-sliding with touch support
// Detects page context and applies appropriate behavior
function initProductSliders() {
    const sliders = document.querySelectorAll('.product-image-slider');

    // Check if we're on a product detail page (single product view)
    const isProductDetailPage = document.querySelector('.product-gallery') !== null ||
        document.querySelector('.product-detail') !== null ||
        window.location.pathname.includes('/product');

    sliders.forEach(slider => {
        const images = slider.querySelectorAll('.slider-img');
        const dots = slider.querySelectorAll('.slider-dot');

        if (images.length <= 1) return;

        let currentIndex = 0;
        let autoSlideInterval = null;
        let touchStartX = 0;
        let touchEndX = 0;
        let touchStartY = 0;
        let touchEndY = 0;

        // Show specific image with smooth transition
        function showImage(index) {
            if (index < 0) index = images.length - 1;
            if (index >= images.length) index = 0;

            images.forEach((img, i) => {
                img.classList.remove('active', 'slide-left', 'slide-right');
                if (i === index) {
                    img.classList.add('active');
                }
            });

            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });

            currentIndex = index;
        }

        // Next image
        function nextImage() {
            showImage(currentIndex + 1);
        }

        // Previous image
        function prevImage() {
            showImage(currentIndex - 1);
        }

        // Click on dots - works for both modes
        dots.forEach((dot, index) => {
            dot.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                showImage(index);
            });
        });

        // Touch swipe support - works for both modes
        slider.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
        }, { passive: true });

        slider.addEventListener('touchmove', (e) => {
            // Prevent vertical scroll when swiping horizontally
            const deltaX = Math.abs(e.changedTouches[0].screenX - touchStartX);
            const deltaY = Math.abs(e.changedTouches[0].screenY - touchStartY);
            if (deltaX > deltaY && deltaX > 10) {
                e.preventDefault();
            }
        }, { passive: false });

        slider.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            touchEndY = e.changedTouches[0].screenY;
            handleSwipe();
        }, { passive: true });

        function handleSwipe() {
            const diffX = touchStartX - touchEndX;
            const diffY = Math.abs(touchStartY - touchEndY);

            // Only handle horizontal swipes (ignore vertical scrolling)
            if (Math.abs(diffX) > 50 && Math.abs(diffX) > diffY) {
                if (diffX > 0) {
                    // Swipe left - next (RTL: previous)
                    prevImage();
                } else {
                    // Swipe right - prev (RTL: next)
                    nextImage();
                }
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // PRODUCT DETAIL PAGE - Manual navigation only (NO auto-sliding)
        // ═══════════════════════════════════════════════════════════════
        if (isProductDetailPage) {
            // No auto-sliding at all
            // Just manual navigation via dots, swipe, and clicks

            // Click to navigate (left side = prev, right side = next)
            slider.addEventListener('click', (e) => {
                if (e.target.classList.contains('slider-dot')) return;

                const rect = slider.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const halfWidth = rect.width / 2;

                if (clickX > halfWidth) {
                    // Click on left side (RTL) - next image
                    nextImage();
                } else {
                    // Click on right side (RTL) - prev image
                    prevImage();
                }
            });

            return; // Exit early - no auto-sliding setup
        }

        // ═══════════════════════════════════════════════════════════════
        // HOMEPAGE / PRODUCTS PAGE - Auto-sliding with pause on interaction
        // ═══════════════════════════════════════════════════════════════
        let isPaused = false;

        // Start auto-sliding
        function startAutoSlide() {
            if (autoSlideInterval) clearInterval(autoSlideInterval);
            autoSlideInterval = setInterval(() => {
                if (!isPaused) {
                    nextImage();
                }
            }, 3500);
        }

        // Pause slider
        function pauseSlider() {
            isPaused = true;
            slider.classList.add('paused');
        }

        // Resume slider
        function resumeSlider() {
            isPaused = false;
            slider.classList.remove('paused');
        }

        // Mouse interactions - pause on hover
        slider.addEventListener('mouseenter', () => {
            pauseSlider();
        });

        slider.addEventListener('mouseleave', () => {
            resumeSlider();
        });

        // Update touch handlers for auto-slide mode
        slider.addEventListener('touchstart', () => {
            pauseSlider();
        }, { passive: true });

        slider.addEventListener('touchend', () => {
            // Resume after 5 seconds
            setTimeout(resumeSlider, 5000);
        }, { passive: true });

        // Click on slider (pause/resume toggle)
        slider.addEventListener('click', (e) => {
            // Don't toggle if clicking dots or badges
            if (e.target.classList.contains('slider-dot') ||
                e.target.classList.contains('product-badge') ||
                e.target.classList.contains('bestseller-badge')) {
                return;
            }

            if (isPaused) {
                resumeSlider();
            } else {
                pauseSlider();
                setTimeout(resumeSlider, 5000);
            }
        });

        // Start auto-sliding for homepage/products
        startAutoSlide();
    });
}

// ═══════════════════════════════════════════════════════════
// LAZY LOADING - تحميل الصور عند الحاجة
// ═══════════════════════════════════════════════════════════

function initLazyLoading() {
    // Check if IntersectionObserver is supported
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[data-src], img.lazy');

        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;

                    // Load the image
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }

                    // Add loaded class for fade-in effect
                    img.classList.add('lazy-loaded');
                    img.classList.remove('lazy');

                    // Stop observing
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px', // Load images 50px before they enter viewport
            threshold: 0.01
        });

        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for browsers without IntersectionObserver
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
}

// Lazy load new images added dynamically
function lazyLoadImage(img) {
    if (img.dataset.src) {
        img.src = img.dataset.src;
        img.removeAttribute('data-src');
        img.classList.add('lazy-loaded');
    }
}

// Add animation keyframe
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from { transform: translateY(100px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    /* Lazy loading styles */
    img.lazy {
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    img.lazy-loaded {
        opacity: 1;
    }
`;
document.head.appendChild(style);

// Iraqi Governorates and Districts Data
const iraqDistricts = {
    "بغداد": ["الكرخ", "الرصافة", "الكاظمية", "الأعظمية", "المنصور", "الشعب", "مدينة الصدر", "الدورة", "أبو غريب", "المحمودية", "الطارمية", "المدائن"],
    "البصرة": ["البصرة المركز", "الزبير", "أبو الخصيب", "القرنة", "شط العرب", "الفاو", "المدينة"],
    "نينوى": ["الموصل", "تلعفر", "سنجار", "الحمدانية", "تلكيف", "الشيخان", "البعاج", "الحضر", "مخمور"],
    "أربيل": ["أربيل المركز", "سوران", "شقلاوة", "كويسنجق", "ميركه سور", "خبات"],
    "النجف": ["النجف المركز", "الكوفة", "المناذرة", "المشخاب"],
    "كربلاء": ["كربلاء المركز", "الحر", "الهندية", "عين التمر"],
    "ذي قار": ["الناصرية", "الرفاعي", "سوق الشيوخ", "الشطرة", "الجبايش", "الفهود", "الفجر"],
    "الأنبار": ["الرمادي", "الفلوجة", "هيت", "حديثة", "عنه", "القائم", "الرطبة", "راوه"],
    "ديالى": ["بعقوبة", "المقدادية", "خانقين", "بلدروز", "الخالص", "كفري"],
    "كركوك": ["كركوك المركز", "الحويجة", "داقوق", "دبس"],
    "صلاح الدين": ["تكريت", "سامراء", "بيجي", "الدور", "بلد", "الشرقاط", "طوزخورماتو", "الإسحاقي"],
    "بابل": ["الحلة", "المسيب", "المحاويل", "الهاشمية", "القاسم"],
    "واسط": ["الكوت", "الحي", "الصويرة", "النعمانية", "بدرة", "العزيزية"],
    "ميسان": ["العمارة", "المجر الكبير", "علي الغربي", "قلعة صالح", "الكحلاء", "الميمونة"],
    "المثنى": ["السماوة", "الرميثة", "الخضر", "الوركاء", "السلمان"],
    "القادسية": ["الديوانية", "عفك", "الشامية", "الحمزة", "السنية"],
    "دهوك": ["دهوك المركز", "زاخو", "العمادية", "عقرة", "سيميل", "بردرش"],
    "السليمانية": ["السليمانية المركز", "حلبجة", "رانية", "دوكان", "بنجوين", "شهربازار", "كلار", "كفري"],
    "حلبجة": ["حلبجة المركز", "شهربازار", "بينجوين"]
};

// Neighborhoods data - district -> neighborhoods[]
const iraqNeighborhoods = {
    // بغداد
    "الكرخ": ["حي العامل", "حي البياع", "حي السيدية", "حي الشرطة", "حي الإسكان", "حي الجهاد", "حي الخضراء", "حي حطين", "حي الكفاءات", "حي المنصور"],
    "الرصافة": ["حي الكرادة", "حي بغداد الجديدة", "حي زيونة", "حي المشتل", "حي الوزيرية", "حي البتاوين", "حي الشعب", "حي الأمين", "حي فلسطين", "حي الحبيبية"],
    "الكاظمية": ["حي الكاظمية", "حي الشماسية", "حي العطيفية", "حي الفيصلية", "حي الراشدية"],
    "الأعظمية": ["حي الأعظمية", "حي الوزيرية", "حي الصليخ", "حي شارع فلسطين", "حي الكسرة"],
    "المنصور": ["حي المنصور", "حي اليرموك", "حي العدل", "حي الجامعة", "حي الخضراء", "حي حي الحرية"],
    "الشعب": ["حي الشعب", "حي الحبيبية", "حي بغداد الجديدة", "حي الصدرية"],
    "مدينة الصدر": ["القطاع الأول", "القطاع الثاني", "القطاع الثالث", "القطاع الرابع", "الجميلة"],
    "الدورة": ["حي الدورة", "حي الميكانيك", "حي الصناعة", "حي السومر"],

    // نينوى
    "الموصل": ["حي الزهور", "حي العربي", "حي النبي يونس", "حي الدواسة", "حي الميدان", "حي السكر", "حي المحاربين", "حي الحدباء", "حي الشرطة", "حي الرفاعي"],
    "تلعفر": ["حي القادسية", "حي النور", "حي السلام", "حي الشهداء", "حي الجزائر"],
    "سنجار": ["حي الشهداء", "حي السلام", "حي النور", "مركز سنجار"],
    "الحمدانية": ["قرقوش", "برطلة", "كرمليس", "بعشيقة"],
    "تلكيف": ["مركز تلكيف", "باطنايا", "ألقوش"],

    // البصرة
    "البصرة المركز": ["حي الجزائر", "حي العشار", "حي الحيانية", "حي الجبيلة", "حي الميناء", "حي التميمية", "حي الساعي"],
    "الزبير": ["مركز الزبير", "حي النجمي", "حي السلام"],
    "أبو الخصيب": ["مركز أبو الخصيب", "السيبة", "الفاو"],

    // النجف
    "النجف المركز": ["حي الحنانة", "حي المهندسين", "حي الجديدة", "حي الأمير", "حي العوالي", "حي السلام", "حي الإسكان"],
    "الكوفة": ["مركز الكوفة", "حي النهضة", "حي الجامعة"],

    // كربلاء
    "كربلاء المركز": ["حي الحسين", "حي العباس", "حي الإسكان", "حي النقيب", "حي المعلمين", "حي الجامعة", "حي الصناعي"],
    "الهندية": ["مركز الهندية", "حي الرشيد"],

    // أربيل
    "أربيل المركز": ["عينكاوة", "شورش", "روناكي", "إسكان", "ريزكاري", "كوران", "شوره باغ"],

    // السليمانية
    "السليمانية المركز": ["مركز المدينة", "باختياري", "سالم", "رابرين", "نوروز"],

    // ذي قار
    "الناصرية": ["حي الحبوبي", "حي المعلمين", "حي الجزائر", "حي العسكري", "حي الرسالة"],

    // الأنبار
    "الرمادي": ["حي المعلمين", "حي العزيزية", "حي الورار", "حي الحوز", "حي الخمسة كيلو"],
    "الفلوجة": ["الجولان", "نزال", "الجبيل", "الشهداء", "الرسالة"],

    // ديالى
    "بعقوبة": ["حي المعلمين", "حي تحرير", "حي الصناعة", "حي المصطفى", "حي السراي"],

    // كركوك
    "كركوك المركز": ["حي القورية", "حي الواسطي", "حي علي جواد", "حي تسعين", "الملا عبدالله"],

    // صلاح الدين
    "تكريت": ["العوجة", "القادسية", "الضباط", "المعلمين"],
    "سامراء": ["المدينة المنورة", "الإمام", "القادسية"],

    // بابل
    "الحلة": ["حي الجامعة", "حي المعلمين", "حي الإسكان", "حي المهندسين", "حي النسيج", "حي الثورة"],

    // واسط
    "الكوت": ["حي الأمير", "حي العصري", "حي المعلمين", "حي الحسين"],

    // ميسان
    "العمارة": ["حي المعلمين", "حي الأمير", "حي الجادرية", "حي السراي", "حي الحسين"],

    // المثنى
    "السماوة": ["حي الجمهورية", "حي المحاربين", "حي المعلمين", "حي النهضة"],

    // القادسية
    "الديوانية": ["حي الوحدة", "حي الجمهورية", "حي السلام", "حي الحسين", "حي العروبة"],

    // دهوك
    "دهوك المركز": ["مالطا", "شندوخا", "جليك", "كورده", "كاني"]
};

// Update districts dropdown based on selected governorate
// Now using combined area combobox instead of separate dropdowns
function updateDistricts() {
    const citySelect = document.getElementById('customerCity');
    const areaGroup = document.getElementById('areaGroup');
    const combobox = document.getElementById('areaCombobox');
    const comboboxText = document.getElementById('areaComboboxText');
    const areaHidden = document.getElementById('customerArea');

    if (!citySelect) return;

    const selectedCity = citySelect.value;

    // Hide area group if no city selected or "أخرى"
    if (!selectedCity || selectedCity === 'أخرى' || !iraqDistricts[selectedCity]) {
        if (areaGroup) areaGroup.style.display = 'none';
        if (areaHidden) areaHidden.value = '';
        return;
    }

    // Show area group and initialize combobox
    if (areaGroup) areaGroup.style.display = 'block';

    // Reset selection
    if (comboboxText) {
        comboboxText.textContent = 'اختر المنطقة / الحي...';
        comboboxText.classList.add('placeholder');
    }
    if (areaHidden) areaHidden.value = '';
    if (combobox) combobox.classList.remove('disabled');

    // Initialize combobox
    initAreaCombobox(selectedCity);
}

// Combined Area Options for city - with neighborhoods and manual entry fallback
function getCombinedAreaOptions(city) {
    const options = [];
    const districts = iraqDistricts[city] || [];

    districts.forEach(district => {
        const neighborhoods = iraqNeighborhoods[district] || [];

        if (neighborhoods.length > 0) {
            // Add district + neighborhood combinations
            neighborhoods.forEach(neighborhood => {
                options.push({
                    value: `${district} — ${neighborhood}`,
                    displayText: `${district} — ${neighborhood}`,
                    searchText: `${district} ${neighborhood}`,
                    isManual: false
                });
            });
        } else {
            // District only (no neighborhoods available)
            options.push({
                value: district,
                displayText: district,
                searchText: district,
                isManual: false
            });
        }
    });

    // Always add manual entry option at the end
    options.push({
        value: '__MANUAL__',
        displayText: '✏️ لم أجد منطقتي — أكتبها',
        searchText: 'لم أجد منطقتي أكتبها يدوي',
        isManual: true
    });

    return options;
}

// Normalize Arabic text for search
function normalizeArabicSearch(text) {
    if (!text) return '';

    // Remove diacritics
    text = text.replace(/[\u064B-\u065F\u0670]/g, '');

    // Normalize alef variants
    text = text.replace(/[أإآٱ]/g, 'ا');

    // Normalize yaa/alef maksura
    text = text.replace(/ى/g, 'ي');

    // Normalize taa marbuta
    text = text.replace(/ة/g, 'ه');

    // Normalize waw/yaa with hamza
    text = text.replace(/ؤ/g, 'و');
    text = text.replace(/ئ/g, 'ي');

    return text.toLowerCase().trim();
}

// Initialize Area Combobox with search functionality
// Uses a global abort controller to clean up old event listeners
let areaComboboxAbortController = null;
let manualEntryMode = false;

function initAreaCombobox(city) {
    const combobox = document.getElementById('areaCombobox');
    const comboboxText = document.getElementById('areaComboboxText');
    const areaHidden = document.getElementById('customerArea');
    const dropdown = document.getElementById('areaDropdown');
    const searchInput = document.getElementById('areaSearchInput');
    const optionsList = document.getElementById('areaOptionsList');

    if (!combobox || !dropdown || !optionsList) return;

    // Abort previous listeners
    if (areaComboboxAbortController) {
        areaComboboxAbortController.abort();
    }
    areaComboboxAbortController = new AbortController();
    const signal = areaComboboxAbortController.signal;

    const options = getCombinedAreaOptions(city);
    let highlightedIndex = -1;
    let isOpen = false;
    manualEntryMode = false;

    // Render options
    function renderOptions(filter = '') {
        const normalizedFilter = normalizeArabicSearch(filter);

        let filtered = options;
        if (normalizedFilter) {
            filtered = options.filter(opt =>
                normalizeArabicSearch(opt.searchText).includes(normalizedFilter)
            );
        }

        if (filtered.length === 0) {
            optionsList.innerHTML = '<div class="area-dropdown-empty">لم يتم العثور على نتائج</div>';
            return;
        }

        optionsList.innerHTML = filtered.map((opt, idx) => {
            const itemClass = opt.isManual ? 'area-dropdown-item area-manual-entry' : 'area-dropdown-item';
            return `<div class="${itemClass}" data-value="${opt.value}" data-manual="${opt.isManual}" data-index="${idx}">${opt.displayText}</div>`;
        }).join('');

        highlightedIndex = -1;

        // Attach click handlers to items
        optionsList.querySelectorAll('.area-dropdown-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                const isManual = item.dataset.manual === 'true';
                if (isManual) {
                    showManualEntryForm();
                } else {
                    selectOption(item.dataset.value);
                }
            });
        });
    }

    // Show manual entry form
    function showManualEntryForm() {
        closeDropdown();

        // Create/show manual entry modal
        let modal = document.getElementById('areaManualModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'areaManualModal';
            modal.className = 'area-manual-modal';
            modal.innerHTML = `
                <div class="area-manual-content">
                    <h4>✏️ أدخل منطقتك يدوياً</h4>
                    <div class="area-manual-field">
                        <label>المنطقة / القضاء *</label>
                        <input type="text" id="manualDistrict" placeholder="مثال: الموصل" required>
                    </div>
                    <div class="area-manual-field">
                        <label>الحي / الناحية (اختياري)</label>
                        <input type="text" id="manualNeighborhood" placeholder="مثال: حي الزهور">
                    </div>
                    <div class="area-manual-buttons">
                        <button type="button" id="manualConfirmBtn" class="btn btn-primary">تأكيد</button>
                        <button type="button" id="manualCancelBtn" class="btn btn-secondary">إلغاء</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Add CSS if not exists
            if (!document.getElementById('areaManualStyles')) {
                const style = document.createElement('style');
                style.id = 'areaManualStyles';
                style.textContent = `
                    .area-manual-modal {
                        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                        background: rgba(0,0,0,0.5); z-index: 99999;
                        display: flex; align-items: center; justify-content: center;
                        padding: 20px;
                    }
                    .area-manual-content {
                        background: #fff; border-radius: 16px; padding: 25px;
                        max-width: 400px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.3);
                    }
                    .area-manual-content h4 {
                        margin: 0 0 20px; color: var(--primary, #E91E8C); font-size: 1.1rem; text-align: center;
                    }
                    .area-manual-field { margin-bottom: 15px; }
                    .area-manual-field label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; }
                    .area-manual-field input {
                        width: 100%; padding: 12px 14px; border: 2px solid #ddd; border-radius: 10px;
                        font-size: 1rem; direction: rtl; box-sizing: border-box;
                    }
                    .area-manual-field input:focus { border-color: var(--primary, #E91E8C); outline: none; }
                    .area-manual-buttons { display: flex; gap: 10px; margin-top: 20px; }
                    .area-manual-buttons button { flex: 1; padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; }
                    #manualConfirmBtn { background: var(--primary, #E91E8C); color: #fff; }
                    #manualCancelBtn { background: #eee; color: #333; }
                    .area-manual-entry { background: #fff0f5 !important; border-top: 2px dashed #E91E8C !important; font-weight: 600; color: var(--primary, #E91E8C) !important; }
                `;
                document.head.appendChild(style);
            }
        }

        modal.style.display = 'flex';
        document.getElementById('manualDistrict').value = '';
        document.getElementById('manualNeighborhood').value = '';
        document.getElementById('manualDistrict').focus();

        // Handlers
        document.getElementById('manualConfirmBtn').onclick = () => {
            const district = document.getElementById('manualDistrict').value.trim();
            const neighborhood = document.getElementById('manualNeighborhood').value.trim();

            if (!district) {
                document.getElementById('manualDistrict').focus();
                return;
            }

            const finalValue = neighborhood ? `${district} — ${neighborhood}` : district;
            selectOption(finalValue);
            modal.style.display = 'none';
        };

        document.getElementById('manualCancelBtn').onclick = () => {
            modal.style.display = 'none';
        };
    }

    // Select an option
    function selectOption(value) {
        comboboxText.textContent = value;
        comboboxText.classList.remove('placeholder');
        areaHidden.value = value;
        closeDropdown();
        areaHidden.dispatchEvent(new Event('change'));
    }

    // Open dropdown
    function openDropdown() {
        if (combobox.classList.contains('disabled')) return;
        isOpen = true;
        combobox.classList.add('active');
        dropdown.classList.add('show');
        renderOptions('');
        setTimeout(() => { if (searchInput) searchInput.focus(); }, 50);
    }

    // Close dropdown
    function closeDropdown() {
        isOpen = false;
        combobox.classList.remove('active');
        dropdown.classList.remove('show');
        if (searchInput) searchInput.value = '';
        highlightedIndex = -1;
    }

    // Toggle dropdown
    function toggleDropdown() {
        if (isOpen) closeDropdown();
        else openDropdown();
    }

    // Update highlight
    function updateHighlight() {
        const items = optionsList.querySelectorAll('.area-dropdown-item');
        items.forEach((item, idx) => {
            item.classList.toggle('highlighted', idx === highlightedIndex);
            if (idx === highlightedIndex) {
                item.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    // Click on combobox to toggle
    combobox.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleDropdown();
    }, { signal });

    // Keyboard on combobox
    combobox.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleDropdown();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            openDropdown();
        }
    }, { signal });

    // Search input handler
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            renderOptions(searchInput.value.trim());
        }, { signal });

        searchInput.addEventListener('click', (e) => {
            e.stopPropagation();
        }, { signal });

        searchInput.addEventListener('keydown', (e) => {
            const items = optionsList.querySelectorAll('.area-dropdown-item');

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    if (items.length > 0) {
                        highlightedIndex = Math.min(highlightedIndex + 1, items.length - 1);
                        updateHighlight();
                    }
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    if (items.length > 0) {
                        highlightedIndex = Math.max(highlightedIndex - 1, 0);
                        updateHighlight();
                    }
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (highlightedIndex >= 0 && items[highlightedIndex]) {
                        selectOption(items[highlightedIndex].dataset.value);
                    } else if (items.length === 1) {
                        selectOption(items[0].dataset.value);
                    } else if (items.length > 0) {
                        selectOption(items[0].dataset.value);
                    }
                    break;
                case 'Escape':
                    closeDropdown();
                    combobox.focus();
                    break;
            }
        }, { signal });
    }

    // Click outside to close
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.area-select-wrapper')) {
            closeDropdown();
        }
    }, { signal });
}

// Keep updateNeighborhoods for backward compatibility (now empty)
function updateNeighborhoods() {
    // No longer needed - combined into area selector
}

// ═══════════════════════════════════════════════════════════════
// CONTACT MODAL - نافذة اختيار التواصل
// ═══════════════════════════════════════════════════════════════

// Create contact modal HTML
function createContactModal() {
    // Check if modal already exists
    if (document.getElementById('contactModal')) return;

    // Get dynamic values from PHP (set in page footer)
    const instagramUrl = window.INSTAGRAM_URL || 'https://instagram.com/sur._prises';
    const telegramUrl = window.TELEGRAM_URL || 'https://t.me/sur_prisese';
    const instagramUser = window.INSTAGRAM_USER || 'sur._prises';
    const telegramUser = window.TELEGRAM_USER || 'sur_prisese';

    const modalHTML = `
        <div id="contactModal" class="contact-modal" onclick="closeContactModal(event)">
            <div class="contact-modal-content" onclick="event.stopPropagation()">
                <button class="contact-modal-close" onclick="closeContactModal()">&times;</button>
                <h3 class="contact-modal-title">📱 تواصل معنا</h3>
                <p class="contact-modal-subtitle">اختر طريقة التواصل المفضلة لديك</p>
                <div class="contact-modal-options">
                    <a href="${instagramUrl}" 
                       target="_blank" 
                       class="contact-option contact-option-instagram"
                       onclick="closeContactModal()">
                        <span class="contact-option-icon"><img src="images/icons/icon1.png" alt="Instagram" style="width: 28px; height: 28px; object-fit: contain;"></span>
                        <span class="contact-option-label">Instagram</span>
                        <span class="contact-option-handle">@${instagramUser}</span>
                    </a>
                    <a href="${telegramUrl}" 
                       target="_blank" 
                       class="contact-option contact-option-telegram"
                       onclick="closeContactModal()">
                        <span class="contact-option-icon"><img src="images/icons/icon2.png" alt="Telegram" style="width: 28px; height: 28px; object-fit: contain;"></span>
                        <span class="contact-option-label">Telegram</span>
                        <span class="contact-option-handle">@${telegramUser}</span>
                    </a>
                </div>
            </div>
        </div>
        <style>
            .contact-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.85);
                z-index: 99999;
                justify-content: center;
                align-items: center;
                padding: 20px;
                animation: fadeIn 0.2s ease;
            }
            .contact-modal.active {
                display: flex;
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            .contact-modal-content {
                background: linear-gradient(135deg, #1a1a1a, #111);
                border-radius: 24px;
                padding: 35px 30px;
                max-width: 400px;
                width: 100%;
                text-align: center;
                border: 2px solid rgba(201, 164, 73, 0.3);
                box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6);
                animation: slideUp 0.3s ease;
                position: relative;
            }
            @keyframes slideUp {
                from { transform: translateY(30px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            .contact-modal-close {
                position: absolute;
                top: 15px;
                left: 15px;
                width: 35px;
                height: 35px;
                border-radius: 50%;
                background: rgba(255,255,255,0.1);
                border: none;
                color: #aaa;
                font-size: 1.5rem;
                cursor: pointer;
                transition: all 0.3s ease;
                line-height: 1;
            }
            .contact-modal-close:hover {
                background: rgba(255,255,255,0.2);
                color: #fff;
            }
            .contact-modal-title {
                font-size: 1.5rem;
                color: #C9A449;
                margin-bottom: 10px;
                font-weight: 700;
            }
            .contact-modal-subtitle {
                color: #888;
                font-size: 0.95rem;
                margin-bottom: 25px;
            }
            .contact-modal-options {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }
            .contact-option {
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 18px 20px;
                border-radius: 16px;
                text-decoration: none;
                transition: all 0.3s ease;
                border: 2px solid transparent;
            }
            .contact-option-instagram {
                background: linear-gradient(135deg, #833AB4, #FD1D1D, #F77737);
                color: white;
            }
            .contact-option-telegram {
                background: linear-gradient(135deg, #0088cc, #00aced);
                color: white;
            }
            .contact-option:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            }
            .contact-option-icon {
                font-size: 2rem;
                width: 50px;
                height: 50px;
                background: rgba(255,255,255,0.2);
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .contact-option-label {
                font-size: 1.2rem;
                font-weight: 700;
            }
            .contact-option-handle {
                margin-right: auto;
                font-size: 0.85rem;
                opacity: 0.8;
            }
            @media (max-width: 480px) {
                .contact-modal-content {
                    padding: 25px 20px;
                }
                .contact-option {
                    padding: 15px;
                    gap: 12px;
                }
                .contact-option-icon {
                    width: 40px;
                    height: 40px;
                    font-size: 1.5rem;
                }
                .contact-option-label {
                    font-size: 1rem;
                }
            }
        </style>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Open contact modal
function openContactModal() {
    createContactModal();
    const modal = document.getElementById('contactModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

// Close contact modal
function closeContactModal(event) {
    if (event && event.target !== event.currentTarget) return;
    const modal = document.getElementById('contactModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close on ESC key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeContactModal();
    }
});

// Auto-attach to contact links
document.addEventListener('DOMContentLoaded', function () {
    // Find all contact links and convert them to open modal
    document.querySelectorAll('a[href*="instagram.com"], a[href*="t.me"]').forEach(function (link) {
        // Skip if it's in footer social links (icon-only links)
        if (link.classList.contains('social-link')) return;
        // Skip if it's already a direct platform link in the modal
        if (link.closest('.contact-modal')) return;
        // Skip order completion Telegram links
        if (link.closest('.checkout-telegram') || link.closest('#checkoutBtn') || link.id === 'checkoutBtn') return;
        // Skip specific direct links
        if (link.dataset.direct === 'true') return;

        // Check if it's a "contact us" type link
        const text = link.textContent.toLowerCase();
        if (text.includes('تواصل') || text.includes('contact')) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                openContactModal();
            });
        }
    });
});

