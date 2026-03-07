/**
 * Orders Filters - AJAX Live Search & Filtering
 * Surprise! Store v2.5.4 - FINAL FIX
 * 
 * This file handles all filtering, searching, and pagination for the orders page.
 * Uses the same approach as products and reports pages for consistency.
 */

(function () {
    'use strict';

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOrdersFilters);
    } else {
        initOrdersFilters();
    }

    function initOrdersFilters() {
        // =====================================
        // ELEMENT REFERENCES
        // =====================================
        const liveSearch = document.getElementById('liveSearch');
        const filterStatus = document.getElementById('filterStatus');
        const filterYear = document.getElementById('filterYear');
        const filterMonth = document.getElementById('filterMonth');
        const clearFilters = document.getElementById('clearFilters');
        const searchResults = document.getElementById('searchResults');

        // Check if we're on the orders page with filters
        if (!filterStatus && !filterYear && !filterMonth && !liveSearch) {
            return; // Not on orders list page
        }

        console.log('[OrdersFilters] Initializing AJAX filters...');

        // =====================================
        // STATE VARIABLES
        // =====================================
        let searchTimeout = null;
        let isLoading = false;
        let currentPage = 1;

        // Get CSRF token from page
        const csrfInput = document.querySelector('input[name="csrf_token"]');
        const csrf = csrfInput ? csrfInput.value : '';

        // =====================================
        // UTILITY FUNCTIONS
        // =====================================

        function formatPrice(price) {
            if (!price && price !== 0) return '0 د.ع';
            return new Intl.NumberFormat('en-US').format(price) + ' د.ع';
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showSearchStatus(message, type) {
            if (!searchResults) return;

            let color = '#666';
            if (type === 'loading') color = 'var(--primary, #E91E8C)';
            else if (type === 'success') color = '#28a745';
            else if (type === 'error') color = '#dc3545';

            searchResults.innerHTML = '<span style="color: ' + color + ';">' + message + '</span>';
        }

        // =====================================
        // MAIN SEARCH FUNCTION
        // =====================================

        function doSearch(page) {
            if (isLoading) return;
            isLoading = true;

            // Reset to page 1 if not specified
            currentPage = page || 1;

            // Build parameters from current filter values
            const params = new URLSearchParams();
            params.set('type', 'orders');
            params.set('search', liveSearch ? liveSearch.value.trim() : '');

            // Handle status - empty string means "all"
            const statusValue = filterStatus ? filterStatus.value : '';
            params.set('status', statusValue);

            params.set('year', filterYear ? filterYear.value : '');
            params.set('month', filterMonth ? filterMonth.value : '0');
            params.set('page', currentPage.toString());

            console.log('[OrdersFilters] Searching with:', params.toString());
            showSearchStatus('⏳ جاري البحث...', 'loading');

            // Make AJAX request
            fetch('../api/admin-search.php?' + params.toString())
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function (data) {
                    isLoading = false;
                    console.log('[OrdersFilters] Response:', data);

                    if (data.success) {
                        showSearchStatus('✅ تم العثور على ' + data.count + ' طلب', 'success');
                        renderOrdersTable(data.orders || []);
                        renderOrdersMobileCards(data.orders || []);
                        renderPagination(data.pagination || null);
                    } else {
                        showSearchStatus('❌ ' + (data.error || 'خطأ في البحث'), 'error');
                    }
                })
                .catch(function (error) {
                    isLoading = false;
                    console.error('[OrdersFilters] Error:', error);
                    showSearchStatus(
                        '❌ خطأ في الاتصال ' +
                        '<button onclick="window.ordersDoSearch()" style="margin-right: 10px; background: var(--primary, #E91E8C); color: white; border: none; padding: 5px 15px; border-radius: 15px; cursor: pointer;">🔄 إعادة المحاولة</button>',
                        'error'
                    );
                });
        }

        // Expose globally for retry button
        window.ordersDoSearch = function (page) {
            doSearch(page);
        };

        // =====================================
        // RENDER TABLE
        // =====================================

        function renderOrdersTable(orders) {
            const tableBody = document.querySelector('.admin-table tbody');
            if (!tableBody) return;

            if (!orders || orders.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #999;">📋 لا توجد نتائج</td></tr>';
                return;
            }

            let html = '';
            for (let i = 0; i < orders.length; i++) {
                const order = orders[i];
                html += '<tr>';
                html += '<td><strong>#' + escapeHtml(order.order_number) + '</strong></td>';
                html += '<td>' + escapeHtml(order.customer_name || '(غير محدد)');
                if (order.customer_phone) {
                    html += '<br><small style="color: #999;">' + escapeHtml(order.customer_phone) + '</small>';
                }
                html += '</td>';
                html += '<td>-</td>';
                html += '<td><strong style="color: var(--primary, #E91E8C);">' + formatPrice(order.total) + '</strong></td>';
                html += '<td><span class="status ' + escapeHtml(order.status_class || '') + '">' + (order.status_icon || '') + ' ' + escapeHtml(order.status_label || order.status) + '</span></td>';
                html += '<td style="font-size: 0.85rem;">' + escapeHtml(order.created_at_formatted || order.created_at) + '</td>';
                html += '<td>';
                html += '<div class="action-btns">';
                html += '<a href="orders?view=' + order.id + '" class="action-btn action-btn-view" title="عرض">👁️</a>';
                html += '<a href="orders?delete=' + order.id + '&token=' + csrf + '" class="action-btn action-btn-delete" title="حذف" onclick="return confirm(\'هل أنت متأكد من حذف هذا الطلب؟\')">🗑️</a>';
                html += '</div>';
                html += '</td>';
                html += '</tr>';
            }

            tableBody.innerHTML = html;
        }

        // =====================================
        // RENDER MOBILE CARDS
        // =====================================

        function renderOrdersMobileCards(orders) {
            // Remove existing AJAX-generated mobile cards
            const existingCards = document.querySelectorAll('.mobile-order-card.ajax-generated');
            existingCards.forEach(function (card) {
                card.remove();
            });

            // Also hide the original PHP-generated mobile cards
            const originalCards = document.querySelectorAll('.mobile-order-card:not(.ajax-generated)');
            originalCards.forEach(function (card) {
                card.style.display = 'none';
            });

            if (!orders || orders.length === 0) return;

            const adminCard = document.querySelector('.admin-card');
            const table = adminCard ? adminCard.querySelector('.admin-table') : null;
            if (!table) return;

            let html = '';
            for (let i = 0; i < orders.length; i++) {
                const order = orders[i];
                html += '<div class="mobile-order-card ajax-generated">';
                html += '<div class="mobile-order-header">';
                html += '<span class="mobile-order-number">#' + escapeHtml(order.order_number) + '</span>';
                html += '<span class="status ' + escapeHtml(order.status_class || '') + '">' + (order.status_icon || '') + ' ' + escapeHtml(order.status_label || order.status) + '</span>';
                html += '</div>';
                html += '<div class="mobile-order-body">';
                html += '<div class="mobile-order-item"><span class="label">👤 الزبون</span><span class="value">' + escapeHtml(order.customer_name || '(غير محدد)') + '</span></div>';
                html += '<div class="mobile-order-item"><span class="label">💰 الإجمالي</span><span class="value" style="color: var(--primary, #E91E8C);">' + formatPrice(order.total) + '</span></div>';
                html += '<div class="mobile-order-item"><span class="label">📱 الهاتف</span><span class="value">' + escapeHtml(order.customer_phone || '-') + '</span></div>';
                html += '<div class="mobile-order-item"><span class="label">📅 التاريخ</span><span class="value">' + escapeHtml(order.created_at_formatted || order.created_at) + '</span></div>';
                html += '</div>';
                html += '<div class="mobile-order-footer">';
                html += '<a href="orders?view=' + order.id + '" class="btn btn-primary">👁️ عرض التفاصيل</a>';
                html += '<a href="orders?delete=' + order.id + '&token=' + csrf + '" class="btn btn-danger" onclick="return confirm(\'هل أنت متأكد من حذف هذا الطلب؟\')">🗑️ حذف</a>';
                html += '</div>';
                html += '</div>';
            }

            table.insertAdjacentHTML('afterend', html);
        }

        // =====================================
        // RENDER PAGINATION
        // =====================================

        function renderPagination(pagination) {
            const paginationContainer = document.querySelector('.pagination');

            if (!pagination || pagination.totalPages <= 1) {
                if (paginationContainer) {
                    paginationContainer.style.display = 'none';
                }
                return;
            }

            if (paginationContainer) {
                paginationContainer.style.display = 'flex';

                let html = '';

                // Previous button
                if (pagination.currentPage > 1) {
                    html += '<a href="javascript:void(0)" onclick="window.ordersDoSearch(' + (pagination.currentPage - 1) + ')">« السابق</a>';
                }

                // Page numbers
                const startPage = Math.max(1, pagination.currentPage - 2);
                const endPage = Math.min(pagination.totalPages, pagination.currentPage + 2);

                for (let i = startPage; i <= endPage; i++) {
                    if (i === pagination.currentPage) {
                        html += '<span class="active">' + i + '</span>';
                    } else {
                        html += '<a href="javascript:void(0)" onclick="window.ordersDoSearch(' + i + ')">' + i + '</a>';
                    }
                }

                // Next button
                if (pagination.currentPage < pagination.totalPages) {
                    html += '<a href="javascript:void(0)" onclick="window.ordersDoSearch(' + (pagination.currentPage + 1) + ')">التالي »</a>';
                }

                paginationContainer.innerHTML = html;
            }
        }

        // =====================================
        // CLEAR FILTERS FUNCTION
        // =====================================

        function clearAllFilters() {
            console.log('[OrdersFilters] Clearing all filters');

            // Reset search input
            if (liveSearch) liveSearch.value = '';

            // Reset status to "all" (empty value)
            if (filterStatus) filterStatus.value = '';

            // Reset year to first option (current year)
            if (filterYear) filterYear.selectedIndex = 0;

            // Reset month to "all" (0)
            if (filterMonth) filterMonth.value = '0';

            // Clear search results
            if (searchResults) searchResults.innerHTML = '';

            // Reset page and do search
            currentPage = 1;
            doSearch(1);
        }

        // =====================================
        // BIND EVENT LISTENERS
        // =====================================

        // Live search input
        if (liveSearch) {
            liveSearch.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    doSearch(1);
                }, 400);
            });

            liveSearch.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchTimeout);
                    doSearch(1);
                }
            });
            console.log('[OrdersFilters] ✓ liveSearch bound');
        }

        // Status filter
        if (filterStatus) {
            filterStatus.addEventListener('change', function () {
                doSearch(1);
            });
            console.log('[OrdersFilters] ✓ filterStatus bound');
        }

        // Year filter
        if (filterYear) {
            filterYear.addEventListener('change', function () {
                doSearch(1);
            });
            console.log('[OrdersFilters] ✓ filterYear bound');
        }

        // Month filter
        if (filterMonth) {
            filterMonth.addEventListener('change', function () {
                doSearch(1);
            });
            console.log('[OrdersFilters] ✓ filterMonth bound');
        }

        // Clear filters button
        if (clearFilters) {
            clearFilters.addEventListener('click', function () {
                clearAllFilters();
            });
            console.log('[OrdersFilters] ✓ clearFilters bound');
        }

        // =====================================
        // STATUS TABS (Quick filter buttons)
        // =====================================

        const statusTabs = document.querySelectorAll('.stats-mini .stats-mini-item[data-status]');
        statusTabs.forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                const clickedStatus = this.getAttribute('data-status');
                console.log('[OrdersFilters] Status tab clicked:', clickedStatus);

                // Update dropdown: "all" -> empty string, others stay as-is
                if (filterStatus) {
                    filterStatus.value = (clickedStatus === 'all') ? '' : clickedStatus;
                }

                // Execute AJAX search
                doSearch(1);
            });
        });

        if (statusTabs.length > 0) {
            console.log('[OrdersFilters] ✓ statusTabs bound (' + statusTabs.length + ' tabs)');
        }

        console.log('[OrdersFilters] ✓ Initialization complete!');
    }
})();
