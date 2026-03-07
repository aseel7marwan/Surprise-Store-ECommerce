/**
 * API Helper - Unified Request Handler
 * Handles all fetch/AJAX requests with:
 * - Timeout handling
 * - Automatic retry on failure
 * - Proper error handling
 * - User-friendly error messages
 * - Event delegation for dynamic content
 */

(function (window) {
    'use strict';

    // ═══════════════════════════════════════════════════════════════
    // CONFIGURATION
    // ═══════════════════════════════════════════════════════════════

    const CONFIG = {
        timeout: 30000,           // 30 seconds timeout
        retryAttempts: 1,         // Retry once on failure
        retryDelay: 1000,         // 1 second before retry
        baseUrl: '',              // Will be auto-detected
        showNotifications: true   // Show error notifications to user
    };

    // Detect base URL
    const currentPath = window.location.pathname;
    if (currentPath.includes('/admin/')) {
        CONFIG.baseUrl = '../api/';
    } else {
        CONFIG.baseUrl = 'api/';
    }

    // ═══════════════════════════════════════════════════════════════
    // ERROR TRACKING
    // ═══════════════════════════════════════════════════════════════

    let lastError = null;
    let errorCount = 0;

    // ═══════════════════════════════════════════════════════════════
    // MAIN API REQUEST FUNCTION
    // ═══════════════════════════════════════════════════════════════

    /**
     * Unified API Request Function
     * @param {string} url - API endpoint (relative to base URL or full URL)
     * @param {Object} options - Fetch options (method, body, headers, etc.)
     * @param {Object} config - Additional config (timeout, retries, silent)
     * @returns {Promise} - Resolves with data or rejects with error
     */
    async function apiRequest(url, options = {}, config = {}) {
        const mergedConfig = { ...CONFIG, ...config };

        // Build full URL if relative
        let fullUrl = url;
        if (!url.startsWith('http') && !url.startsWith('/')) {
            fullUrl = mergedConfig.baseUrl + url;
        }

        // Default headers
        const defaultHeaders = {
            'Accept': 'application/json'
        };

        // Only set Content-Type for non-FormData bodies
        if (options.body && !(options.body instanceof FormData)) {
            defaultHeaders['Content-Type'] = 'application/json';
        }

        const fetchOptions = {
            method: options.method || 'GET',
            headers: { ...defaultHeaders, ...options.headers },
            credentials: 'same-origin', // Include cookies/session
            ...options
        };

        // If body is object and not FormData, stringify
        if (fetchOptions.body && typeof fetchOptions.body === 'object' && !(fetchOptions.body instanceof FormData)) {
            fetchOptions.body = JSON.stringify(fetchOptions.body);
        }

        let lastError = null;
        let attempts = 0;

        while (attempts <= mergedConfig.retryAttempts) {
            attempts++;

            try {
                // Create abort controller for timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), mergedConfig.timeout);

                fetchOptions.signal = controller.signal;

                const response = await fetch(fullUrl, fetchOptions);
                clearTimeout(timeoutId);

                // Check for HTTP errors
                if (!response.ok) {
                    const errorText = await response.text();
                    let errorData;
                    try {
                        errorData = JSON.parse(errorText);
                    } catch (e) {
                        errorData = { error: errorText || `HTTP Error ${response.status}` };
                    }

                    throw new ApiError(
                        errorData.error || errorData.message || `خطأ في الاتصال (${response.status})`,
                        response.status,
                        errorData
                    );
                }

                // Parse response
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();

                    // Reset error count on success
                    errorCount = 0;

                    return data;
                } else {
                    // Return text for non-JSON responses
                    return await response.text();
                }

            } catch (error) {
                lastError = error;

                // Don't retry on authentication errors
                if (error.status === 401 || error.status === 403) {
                    break;
                }

                // Timeout error - might be worth retrying
                if (error.name === 'AbortError') {
                    lastError = new ApiError('انتهت مهلة الاتصال', 408, { timeout: true });
                }

                // Network error - might be worth retrying
                if (error.name === 'TypeError' && error.message.includes('fetch')) {
                    lastError = new ApiError('فشل الاتصال بالخادم', 0, { network: true });
                }

                // Wait before retry (if not last attempt)
                if (attempts <= mergedConfig.retryAttempts) {
                    await sleep(mergedConfig.retryDelay);
                }
            }
        }

        // All attempts failed
        errorCount++;

        // Show notification to user (unless silent)
        if (!config.silent && CONFIG.showNotifications) {
            showErrorNotification(lastError.message, lastError);
        }

        throw lastError;
    }

    // ═══════════════════════════════════════════════════════════════
    // SPECIALIZED REQUEST METHODS
    // ═══════════════════════════════════════════════════════════════

    /**
     * GET Request
     */
    async function apiGet(url, params = {}, config = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;
        return apiRequest(fullUrl, { method: 'GET' }, config);
    }

    /**
     * POST Request (JSON)
     */
    async function apiPost(url, data = {}, config = {}) {
        return apiRequest(url, { method: 'POST', body: data }, config);
    }

    /**
     * POST Request (FormData)
     */
    async function apiPostForm(url, formData, config = {}) {
        return apiRequest(url, { method: 'POST', body: formData }, config);
    }

    // ═══════════════════════════════════════════════════════════════
    // ERROR CLASS
    // ═══════════════════════════════════════════════════════════════

    class ApiError extends Error {
        constructor(message, status = 0, data = {}) {
            super(message);
            this.name = 'ApiError';
            this.status = status;
            this.data = data;
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // UTILITY FUNCTIONS
    // ═══════════════════════════════════════════════════════════════

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Show Error Notification to User
     */
    function showErrorNotification(message, error = null) {
        // Remove existing error notifications
        document.querySelectorAll('.api-error-notification').forEach(n => n.remove());

        const notification = document.createElement('div');
        notification.className = 'api-error-notification';
        notification.innerHTML = `
            <div class="api-error-content">
                <span class="api-error-icon">⚠️</span>
                <div class="api-error-text">
                    <strong>${message}</strong>
                    <p>تأكد من اتصالك بالإنترنت وحاول مرة أخرى</p>
                </div>
                <button class="api-error-retry" onclick="window.apiHelper.retryLastAction()">🔄 إعادة المحاولة</button>
                <button class="api-error-close" onclick="this.closest('.api-error-notification').remove()">✕</button>
            </div>
        `;

        // Add styles if not already added
        addNotificationStyles();

        document.body.appendChild(notification);

        // Auto-remove after 10 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 10000);
    }

    /**
     * Add notification styles to document
     */
    function addNotificationStyles() {
        if (document.getElementById('api-helper-styles')) return;

        const style = document.createElement('style');
        style.id = 'api-helper-styles';
        style.textContent = `
            .api-error-notification {
                position: fixed;
                bottom: 20px;
                left: 20px;
                right: 20px;
                max-width: 500px;
                margin: 0 auto;
                background: linear-gradient(135deg, #f44336, #d32f2f);
                color: white;
                border-radius: 16px;
                box-shadow: 0 8px 30px rgba(0,0,0,0.25);
                z-index: 99999;
                animation: slideUpError 0.3s ease;
            }
            
            @keyframes slideUpError {
                from { transform: translateY(100px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            
            .api-error-content {
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 15px 20px;
            }
            
            .api-error-icon {
                font-size: 1.8rem;
                flex-shrink: 0;
            }
            
            .api-error-text {
                flex: 1;
            }
            
            .api-error-text strong {
                display: block;
                font-size: 1rem;
                margin-bottom: 3px;
            }
            
            .api-error-text p {
                font-size: 0.85rem;
                opacity: 0.9;
                margin: 0;
            }
            
            .api-error-retry {
                background: white;
                color: #d32f2f;
                border: none;
                padding: 10px 18px;
                border-radius: 25px;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.2s ease;
                flex-shrink: 0;
            }
            
            .api-error-retry:hover {
                transform: scale(1.05);
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            }
            
            .api-error-close {
                background: transparent;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                opacity: 0.7;
                transition: opacity 0.2s;
                padding: 5px;
            }
            
            .api-error-close:hover {
                opacity: 1;
            }
            
            @media (max-width: 600px) {
                .api-error-content {
                    flex-wrap: wrap;
                    justify-content: center;
                    text-align: center;
                }
                
                .api-error-text {
                    width: 100%;
                }
                
                .api-error-retry {
                    order: 3;
                    margin-top: 10px;
                }
            }
            
            /* Loading state for elements */
            .api-loading {
                position: relative;
                pointer-events: none;
            }
            
            .api-loading::after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 24px;
                height: 24px;
                margin: -12px 0 0 -12px;
                border: 3px solid rgba(233, 30, 140, 0.2);
                border-top-color: #E91E8C;
                border-radius: 50%;
                animation: apiSpin 0.8s linear infinite;
            }
            
            @keyframes apiSpin {
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }

    // ═══════════════════════════════════════════════════════════════
    // RETRY MECHANISM
    // ═══════════════════════════════════════════════════════════════

    let lastAction = null;

    function setLastAction(action) {
        lastAction = action;
    }

    function retryLastAction() {
        // Remove notification
        document.querySelectorAll('.api-error-notification').forEach(n => n.remove());

        if (lastAction && typeof lastAction === 'function') {
            lastAction();
        } else {
            // Fallback: reload data if available
            if (typeof window.loadAllData === 'function') {
                window.loadAllData();
            } else if (typeof window.doSearch === 'function') {
                window.doSearch();
            } else if (typeof window.doProductSearch === 'function') {
                window.doProductSearch();
            } else {
                // Ultimate fallback
                window.location.reload();
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // ENHANCED FETCH WRAPPER (for existing code compatibility)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Enhanced Fetch - Drop-in replacement for fetch with error handling
     * Usage: window.safeFetch(url, options).then(data => ...).catch(err => ...)
     */
    async function safeFetch(url, options = {}) {
        try {
            const result = await apiRequest(url, options, { silent: true });
            return { success: true, data: result };
        } catch (error) {
            console.error('SafeFetch Error:', error);
            return { success: false, error: error.message, status: error.status };
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // EVENT DELEGATION FOR DYNAMIC CONTENT
    // ═══════════════════════════════════════════════════════════════

    /**
     * Setup event delegation for filters and dynamic elements
     * This ensures filters work even after AJAX content updates
     */
    function setupEventDelegation() {
        // Handle filter changes using event delegation
        document.addEventListener('change', function (e) {
            const target = e.target;

            // Product filters
            if (target.id === 'productSearch' || target.id === 'productCategory' || target.id === 'productStatus') {
                if (typeof window.doProductSearch === 'function') {
                    clearTimeout(window.searchTimeout);
                    window.searchTimeout = setTimeout(window.doProductSearch, 300);
                }
            }

            // Order filters
            if (target.id === 'filterStatus' || target.id === 'filterYear' || target.id === 'filterMonth') {
                if (typeof window.doSearch === 'function') {
                    window.doSearch();
                }
            }

            // Report filters
            if (target.id === 'yearFilter' || target.id === 'monthFilter') {
                if (typeof window.loadAllData === 'function') {
                    window.loadAllData();
                }
            }
        });

        // Handle input events for live search
        document.addEventListener('input', function (e) {
            const target = e.target;

            if (target.id === 'productSearch' || target.id === 'liveSearch') {
                const searchFunc = target.id === 'productSearch' ? window.doProductSearch : window.doSearch;
                if (typeof searchFunc === 'function') {
                    clearTimeout(window.searchTimeout);
                    window.searchTimeout = setTimeout(searchFunc, 300);
                }
            }
        });

        // Handle click events
        document.addEventListener('click', function (e) {
            const target = e.target;

            // Clear filters buttons
            if (target.id === 'clearProductFilters' || target.id === 'clearFilters') {
                e.preventDefault();
                clearFilters();
            }

            // Refresh button
            if (target.id === 'refreshBtn' || target.closest('#refreshBtn')) {
                e.preventDefault();
                if (typeof window.loadAllData === 'function') {
                    window.loadAllData();
                }
            }
        });
    }

    /**
     * Clear all filter inputs and reload data
     */
    function clearFilters() {
        // Product page filters
        const productSearch = document.getElementById('productSearch');
        const productCategory = document.getElementById('productCategory');
        const productStatus = document.getElementById('productStatus');

        if (productSearch) productSearch.value = '';
        if (productCategory) productCategory.value = '';
        if (productStatus) productStatus.value = '';

        // Orders page filters
        const liveSearch = document.getElementById('liveSearch');
        const filterStatus = document.getElementById('filterStatus');
        const filterYear = document.getElementById('filterYear');
        const filterMonth = document.getElementById('filterMonth');

        if (liveSearch) liveSearch.value = '';
        if (filterStatus) filterStatus.value = '';
        if (filterMonth) filterMonth.value = '0';

        // Trigger search/reload
        if (typeof window.doProductSearch === 'function') {
            window.doProductSearch();
        } else if (typeof window.doSearch === 'function') {
            window.doSearch();
        } else if (typeof window.loadAllData === 'function') {
            window.loadAllData();
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // INITIALIZE
    // ═══════════════════════════════════════════════════════════════

    function init() {
        addNotificationStyles();
        setupEventDelegation();

        console.log('🔧 API Helper initialized');
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ═══════════════════════════════════════════════════════════════
    // EXPORT TO GLOBAL SCOPE
    // ═══════════════════════════════════════════════════════════════

    window.apiHelper = {
        request: apiRequest,
        get: apiGet,
        post: apiPost,
        postForm: apiPostForm,
        safeFetch: safeFetch,
        setLastAction: setLastAction,
        retryLastAction: retryLastAction,
        showError: showErrorNotification,
        config: CONFIG
    };

    // Also expose as standalone functions for convenience
    window.apiRequest = apiRequest;
    window.apiGet = apiGet;
    window.apiPost = apiPost;
    window.safeFetch = safeFetch;

})(window);
