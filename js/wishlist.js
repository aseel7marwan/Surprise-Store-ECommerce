/**
 * Surprise! Store - Wishlist (LocalStorage)
 * No database changes - all client-side
 */

const Wishlist = {
    KEY: 'surprise_wishlist',

    // Get all wishlist items
    getAll: function () {
        try {
            const data = localStorage.getItem(this.KEY);
            return data ? JSON.parse(data) : [];
        } catch (e) {
            return [];
        }
    },

    // Save wishlist
    save: function (items) {
        try {
            localStorage.setItem(this.KEY, JSON.stringify(items));
            this.updateUI();
        } catch (e) {
            console.error('Wishlist save error:', e);
        }
    },

    // Add item to wishlist
    add: function (product) {
        const items = this.getAll();

        // Check if already exists
        if (items.find(item => item.id === product.id)) {
            return false; // Already in wishlist
        }

        items.push({
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image,
            addedAt: new Date().toISOString()
        });

        this.save(items);
        this.showNotification('تمت الإضافة للمفضلة ❤️');
        return true;
    },

    // Remove item from wishlist
    remove: function (productId) {
        let items = this.getAll();
        items = items.filter(item => item.id !== productId);
        this.save(items);
        this.showNotification('تمت الإزالة من المفضلة');
        return true;
    },

    // Toggle item in wishlist
    toggle: function (product) {
        if (this.has(product.id)) {
            this.remove(product.id);
            return false;
        } else {
            this.add(product);
            return true;
        }
    },

    // Check if item is in wishlist
    has: function (productId) {
        return this.getAll().some(item => item.id === productId);
    },

    // Get wishlist count
    count: function () {
        return this.getAll().length;
    },

    // Clear all wishlist
    clear: function () {
        localStorage.removeItem(this.KEY);
        this.updateUI();
    },

    // Update UI counters and icons
    updateUI: function () {
        const count = this.count();

        // Update wishlist counters (old style)
        document.querySelectorAll('.wishlist-count').forEach(el => {
            el.textContent = count;
            el.style.display = count > 0 ? 'flex' : 'none';
        });

        // Update sidebar wishlist badge
        document.querySelectorAll('.wishlist-count-badge').forEach(el => {
            el.textContent = count;
            el.style.display = count > 0 ? 'inline-block' : 'none';
        });

        // Update heart icons on product cards and buttons
        document.querySelectorAll('[data-wishlist-btn]').forEach(btn => {
            const productId = btn.dataset.productId;
            const isInWishlist = productId && this.has(productId);

            if (isInWishlist) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    },

    // Show notification
    showNotification: function (message) {
        // Check if notification container exists
        let container = document.getElementById('wishlistNotification');
        if (!container) {
            container = document.createElement('div');
            container.id = 'wishlistNotification';
            container.style.cssText = `
                position: fixed;
                bottom: calc(90px + env(safe-area-inset-bottom));
                left: 50%;
                transform: translateX(-50%) translateY(100px);
                background: linear-gradient(135deg, #e91e8c, #c9a449);
                color: white;
                padding: 12px 24px;
                border-radius: 30px;
                font-weight: 600;
                font-size: 0.9rem;
                box-shadow: 0 4px 20px rgba(233, 30, 140, 0.4);
                z-index: 10001;
                opacity: 0;
                transition: all 0.3s ease;
                text-align: center;
            `;
            document.body.appendChild(container);
        }

        container.textContent = message;
        container.style.opacity = '1';
        container.style.transform = 'translateX(-50%) translateY(0)';

        setTimeout(() => {
            container.style.opacity = '0';
            container.style.transform = 'translateX(-50%) translateY(100px)';
        }, 2500);
    },

    // Initialize wishlist UI
    init: function () {
        this.updateUI();

        // Add click handlers for wishlist buttons
        document.addEventListener('click', (e) => {
            const wishlistBtn = e.target.closest('[data-wishlist-btn]');
            if (wishlistBtn) {
                e.preventDefault();
                e.stopPropagation();

                const productId = wishlistBtn.dataset.productId;
                const productName = wishlistBtn.dataset.productName || '';
                const productPrice = parseFloat(wishlistBtn.dataset.productPrice) || 0;
                const productImage = wishlistBtn.dataset.productImage || '';

                this.toggle({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    image: productImage
                });
            }
        });
    }
};

// Auto-initialize when DOM ready
document.addEventListener('DOMContentLoaded', function () {
    Wishlist.init();
});
