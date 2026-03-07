<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

initSecureSession();
setSecurityHeaders();

if (!validateAdminSession()) {
    redirect('login');
}

$currentYear = intval(date('Y'));
$currentMonth = intval(date('m'));

// Get available years from orders (only years with actual data)
$pdo = db();
$stmt = $pdo->query("SELECT DISTINCT YEAR(created_at) as year FROM orders WHERE created_at IS NOT NULL ORDER BY year DESC");
$availableYears = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Always include current year
if (!in_array($currentYear, $availableYears)) {
    array_unshift($availableYears, $currentYear);
}

// Sort years descending
rsort($availableYears);

$arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 
                 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 التقارير والإحصائيات - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= av('css/main.css') ?>">
    <link rel="stylesheet" href="<?= av('css/admin.css') ?>">
    <link rel="icon" href="../images/logo.jpg">
    <style>
        .reports-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .filter-bar {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-bar select {
            padding: 12px 20px;
            border-radius: var(--radius-full);
            border: 2px solid rgba(233, 30, 140, 0.2);
            background: white;
            font-weight: 700;
            font-size: 0.95rem;
            min-width: 130px;
            cursor: pointer;
            transition: var(--transition);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23E91E8C' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 15px center;
            padding-left: 35px;
        }
        
        .filter-bar select:hover {
            border-color: var(--primary);
            box-shadow: 0 2px 10px rgba(233, 30, 140, 0.15);
        }
        
        .filter-bar select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(233, 30, 140, 0.1);
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-label {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .premium-select {
            padding: 12px 20px;
            border-radius: var(--radius-lg);
            border: 2px solid rgba(233, 30, 140, 0.15);
            background: linear-gradient(135deg, #fff 0%, #fafafa 100%);
            font-weight: 600;
            font-size: 0.95rem;
            min-width: 150px;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23E91E8C' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 15px center;
            padding-left: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        
        .premium-select:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(233, 30, 140, 0.15);
            transform: translateY(-2px);
        }
        
        .premium-select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(233, 30, 140, 0.1), 0 4px 15px rgba(233, 30, 140, 0.15);
        }
        
        .premium-select option {
            padding: 12px;
            font-weight: 500;
        }
        
        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-box::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 4px;
            background: var(--bg-hero);
        }
        
        .stat-box.green::before { background: linear-gradient(90deg, #4CAF50, #81C784); }
        .stat-box.blue::before { background: linear-gradient(90deg, #2196F3, #64B5F6); }
        .stat-box.orange::before { background: linear-gradient(90deg, #FF9800, #FFB74D); }
        .stat-box.purple::before { background: linear-gradient(90deg, #9C27B0, #BA68C8); }
        
        .stat-box .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-box .value {
            font-size: 2rem;
            font-weight: 900;
            color: var(--text-dark);
            line-height: 1.2;
        }
        
        .stat-box .label {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .stat-box .change {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        
        .stat-box .change.positive { background: #E8F5E9; color: #2E7D32; }
        .stat-box .change.negative { background: #FFEBEE; color: #C62828; }
        
        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1024px) {
            .charts-grid { grid-template-columns: 1fr; }
        }
        
        .chart-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        
        .chart-card-header {
            padding: 20px 25px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chart-card-title {
            font-size: 1.1rem;
            font-weight: 700;
        }
        
        .chart-card-body {
            padding: 25px;
        }
        
        /* Bar Chart */
        .bar-chart {
            display: flex;
            gap: 8px;
            height: 200px;
            align-items: flex-end;
            padding-bottom: 30px;
            position: relative;
        }
        
        .bar-chart::before {
            content: '';
            position: absolute;
            bottom: 25px;
            left: 0;
            right: 0;
            height: 1px;
            background: #eee;
        }
        
        .bar {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        
        .bar-fill {
            width: 100%;
            max-width: 40px;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 6px 6px 0 0;
            min-height: 4px;
            transition: height 0.5s ease;
            cursor: pointer;
            position: relative;
        }
        
        .bar-fill:hover {
            opacity: 0.8;
        }
        
        .bar-fill .tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--text-dark);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            z-index: 10;
        }
        
        .bar-fill:hover .tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        .bar-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 8px;
            text-align: center;
        }
        
        /* Donut Chart */
        .donut-chart {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .donut-chart svg {
            transform: rotate(-90deg);
        }
        
        .donut-legend {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }
        
        /* Products Table */
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th,
        .products-table td {
            padding: 15px;
            text-align: right;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .products-table th {
            background: var(--bg-main);
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .products-table tr:hover {
            background: rgba(233, 30, 140, 0.02);
        }
        
        .product-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .product-cell img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .rank-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.8rem;
        }
        
        .rank-1 { background: linear-gradient(135deg, #FFD700, #FFA500); color: white; }
        .rank-2 { background: linear-gradient(135deg, #C0C0C0, #A0A0A0); color: white; }
        .rank-3 { background: linear-gradient(135deg, #CD7F32, #8B4513); color: white; }
        .rank-other { background: #f0f0f0; color: var(--text-muted); }
        
        /* Recent Orders */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--bg-main);
            border-radius: var(--radius-md);
            transition: var(--transition);
        }
        
        .order-item:hover {
            background: rgba(233, 30, 140, 0.05);
        }
        
        .order-info h4 {
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .order-info small {
            color: var(--text-muted);
        }
        
        .order-value {
            text-align: left;
        }
        
        .order-value .amount {
            font-weight: 800;
            color: var(--primary);
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }
        
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .loader {
            width: 50px;
            height: 50px;
            border: 4px solid #f0f0f0;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Refresh Button */
        .refresh-btn {
            background: var(--bg-main);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .refresh-btn:hover {
            background: var(--primary-light);
            transform: rotate(180deg);
        }
        
        .refresh-btn.spinning {
            animation: spin 1s linear infinite;
        }
        
        @media (max-width: 768px) {
            .reports-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .stats-row {
                grid-template-columns: 1fr 1fr;
            }
            
            .bar-chart {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <!-- Header -->
            <div class="reports-header">
                <div>
                    <h1 class="admin-title">📊 التقارير والإحصائيات</h1>
                    <p style="color: var(--text-muted); margin-top: 5px;">تحليل شامل للمبيعات والأداء</p>
                </div>
                
                <div class="filter-bar">
                    <div class="filter-group">
                        <span class="filter-label">📅 السنة</span>
                        <select id="yearFilter" class="premium-select">
                            <?php foreach ($availableYears as $y): ?>
                            <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <span class="filter-label">📆 الشهر</span>
                        <select id="monthFilter" class="premium-select">
                            <option value="0">📊 كل السنة</option>
                            <?php foreach ($arabicMonths as $i => $name): ?>
                            <option value="<?= $i + 1 ?>" <?= ($i + 1) == $currentMonth ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button class="refresh-btn" id="refreshBtn" title="تحديث البيانات">
                        <span class="refresh-icon">🔄</span>
                    </button>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-row" id="statsCards">
                <div class="stat-box">
                    <div class="icon">💰</div>
                    <div class="value" id="totalRevenue">---</div>
                    <div class="label">إجمالي المبيعات</div>
                    <div class="change positive" id="revenueChange">---</div>
                </div>
                
                <div class="stat-box green">
                    <div class="icon">📦</div>
                    <div class="value" id="totalOrders">---</div>
                    <div class="label">عدد الطلبات</div>
                </div>
                
                <div class="stat-box blue">
                    <div class="icon">📊</div>
                    <div class="value" id="avgOrder">---</div>
                    <div class="label">متوسط الطلب</div>
                </div>
                
                <div class="stat-box orange">
                    <div class="icon">⏳</div>
                    <div class="value" id="pendingOrders">---</div>
                    <div class="label">طلبات معلقة</div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="charts-grid">
                <!-- Monthly Sales Chart -->
                <div class="chart-card">
                    <div class="chart-card-header">
                        <h3 class="chart-card-title">📈 المبيعات الشهرية</h3>
                    </div>
                    <div class="chart-card-body">
                        <div class="bar-chart" id="monthlyChart"></div>
                    </div>
                </div>
                
                <!-- Categories Chart -->
                <div class="chart-card">
                    <div class="chart-card-header">
                        <h3 class="chart-card-title">🎯 المبيعات حسب القسم</h3>
                    </div>
                    <div class="chart-card-body">
                        <div class="donut-legend" id="categoryLegend"></div>
                    </div>
                </div>
            </div>
            
            <!-- Products & Orders -->
            <div class="charts-grid">
                <!-- Top Products -->
                <div class="chart-card">
                    <div class="chart-card-header">
                        <h3 class="chart-card-title">🏆 المنتجات الأكثر مبيعاً</h3>
                    </div>
                    <div class="chart-card-body" style="padding: 0;">
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المنتج</th>
                                    <th>الكمية</th>
                                    <th>الإيرادات</th>
                                </tr>
                            </thead>
                            <tbody id="topProducts"></tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="chart-card">
                    <div class="chart-card-header">
                        <h3 class="chart-card-title">🕐 آخر الطلبات</h3>
                        <a href="orders" style="color: var(--primary); font-size: 0.9rem;">عرض الكل ←</a>
                    </div>
                    <div class="chart-card-body">
                        <div class="orders-list" id="recentOrders"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loader"></div>
    </div>
    
    <script src="<?= av('js/api-helper.js') ?>"></script>
    <script src="<?= av('js/admin.js') ?>"></script>
    <script>
        const yearFilter = document.getElementById('yearFilter');
        const monthFilter = document.getElementById('monthFilter');
        const refreshBtn = document.getElementById('refreshBtn');
        const loading = document.getElementById('loadingOverlay');
        
        // Format price
        function formatPrice(price) {
            return new Intl.NumberFormat('en-US').format(price) + ' د.ع';
        }
        
        // Format number
        function formatNum(num) {
            return new Intl.NumberFormat('en-US').format(num);
        }
        
        // Show/Hide loading
        function showLoading() {
            loading.classList.add('active');
            refreshBtn.classList.add('spinning');
        }
        
        function hideLoading() {
            loading.classList.remove('active');
            refreshBtn.classList.remove('spinning');
        }
        
        // Fetch data from API with proper error handling
        async function fetchStats(action, params = {}) {
            const url = new URL('../api/stats.php', window.location.href);
            url.searchParams.set('action', action);
            url.searchParams.set('year', yearFilter.value);
            url.searchParams.set('month', monthFilter.value);
            
            for (const [key, value] of Object.entries(params)) {
                url.searchParams.set(key, value);
            }
            
            try {
                // Use AbortController for timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 30000);
                
                const response = await fetch(url, { signal: controller.signal });
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text.substring(0, 200));
                    throw new Error('استجابة غير صالحة من الخادم');
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    throw new Error('انتهت مهلة الاتصال');
                }
                throw error;
            }
        }
        
        // Show error message in container
        function showErrorInContainer(container, message) {
            if (!container) return;
            container.innerHTML = `
                <div style="text-align: center; padding: 30px; color: #c62828;">
                    <span style="font-size: 2rem; display: block; margin-bottom: 10px;">⚠️</span>
                    <p style="margin-bottom: 15px;">${message}</p>
                    <button onclick="loadAllData()" style="background: var(--primary); color: white; border: none; padding: 10px 25px; border-radius: 25px; cursor: pointer; font-weight: 600;">
                        🔄 إعادة المحاولة
                    </button>
                </div>
            `;
        }
        
        // Load summary stats
        async function loadSummary() {
            try {
                const result = await fetchStats('summary');
                if (!result.success) {
                    console.warn('Summary API returned error:', result.error);
                    return;
                }
                
                const data = result.data;
                
                document.getElementById('totalRevenue').textContent = formatPrice(data.total_revenue);
                document.getElementById('totalOrders').textContent = formatNum(data.total_orders);
                document.getElementById('avgOrder').textContent = formatPrice(data.avg_order_value);
                document.getElementById('pendingOrders').textContent = formatNum(data.pending_orders);
                
                const changeEl = document.getElementById('revenueChange');
                if (data.growth_percentage >= 0) {
                    changeEl.className = 'change positive';
                    changeEl.textContent = '↑ ' + data.growth_percentage + '%';
                } else {
                    changeEl.className = 'change negative';
                    changeEl.textContent = '↓ ' + Math.abs(data.growth_percentage) + '%';
                }
            } catch (error) {
                console.error('Error loading summary:', error);
                document.getElementById('totalRevenue').textContent = '---';
                document.getElementById('totalOrders').textContent = '---';
            }
        }
        
        // Load monthly chart
        async function loadMonthlyChart() {
            const container = document.getElementById('monthlyChart');
            try {
                const result = await fetchStats('monthly');
                if (!result.success) return;
                
                const data = result.data;
                const maxRevenue = Math.max(...data.map(d => d.revenue)) || 1;
                
                container.innerHTML = data.map(d => {
                    const height = (d.revenue / maxRevenue) * 160;
                    return `
                        <div class="bar">
                            <div class="bar-fill" style="height: ${Math.max(height, 4)}px;">
                                <div class="tooltip">
                                    <strong>${d.name}</strong><br>
                                    ${formatNum(d.orders)} طلب<br>
                                    ${formatPrice(d.revenue)}
                                </div>
                            </div>
                            <span class="bar-label">${d.name.substring(0, 3)}</span>
                        </div>
                    `;
                }).join('');
            } catch (error) {
                console.error('Error loading monthly chart:', error);
                showErrorInContainer(container, 'تعذر تحميل الرسم البياني');
            }
        }
        
        // Load category chart
        async function loadCategoryChart() {
            const container = document.getElementById('categoryLegend');
            try {
                const result = await fetchStats('categories');
                if (!result.success) return;
                
                const data = result.data.filter(d => d.revenue > 0);
                const total = data.reduce((sum, d) => sum + d.revenue, 0) || 1;
                
                const colors = ['#E91E8C', '#FF6B9D', '#9C27B0', '#2196F3', '#4CAF50', '#FF9800'];
                
                container.innerHTML = data.map((d, i) => {
                    const percent = ((d.revenue / total) * 100).toFixed(1);
                    return `
                        <div class="legend-item">
                            <span class="legend-color" style="background: ${colors[i % colors.length]}"></span>
                            <span style="flex: 1;">${d.icon} ${d.name}</span>
                            <strong>${percent}%</strong>
                        </div>
                    `;
                }).join('') || '<p style="color: var(--text-muted); text-align: center;">لا توجد بيانات</p>';
            } catch (error) {
                console.error('Error loading category chart:', error);
                showErrorInContainer(container, 'تعذر تحميل البيانات');
            }
        }
        
        // Load top products
        async function loadTopProducts() {
            const container = document.getElementById('topProducts');
            try {
                const result = await fetchStats('products', { limit: 5 });
                if (!result.success) return;
                
                if (result.data.length === 0) {
                    container.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 30px; color: var(--text-muted);">لا توجد بيانات</td></tr>';
                    return;
                }
                
                container.innerHTML = result.data.map((p, i) => {
                    const rankClass = i < 3 ? `rank-${i + 1}` : 'rank-other';
                    return `
                        <tr>
                            <td><span class="rank-badge ${rankClass}">${i + 1}</span></td>
                            <td>
                                <div class="product-cell">
                                    <img src="../images/${p.image}" alt="" onerror="this.src='../images/logo.jpg'">
                                    <span>${p.product_name}</span>
                                </div>
                            </td>
                            <td><strong>${formatNum(p.total_sold)}</strong></td>
                            <td style="color: var(--primary);">${formatPrice(p.total_revenue)}</td>
                        </tr>
                    `;
                }).join('');
            } catch (error) {
                console.error('Error loading top products:', error);
                container.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 30px; color: #c62828;">⚠️ تعذر تحميل البيانات</td></tr>';
            }
        }
        
        // Load recent orders
        async function loadRecentOrders() {
            const container = document.getElementById('recentOrders');
            try {
                const result = await fetchStats('recent', { limit: 5 });
                if (!result.success) return;
                
                if (result.data.length === 0) {
                    container.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 20px;">لا توجد طلبات</p>';
                    return;
                }
                
                container.innerHTML = result.data.map(o => `
                    <a href="orders?view=${o.id}" class="order-item">
                        <div class="order-info">
                            <h4>#${o.order_number}</h4>
                            <small>${o.customer_name || 'زائر'}</small>
                        </div>
                        <div class="order-value">
                            <span class="status ${o.status_class}" style="font-size: 0.8rem; padding: 4px 10px;">${o.status_icon}</span>
                            <div class="amount">${formatPrice(o.total)}</div>
                        </div>
                    </a>
                `).join('');
            } catch (error) {
                console.error('Error loading recent orders:', error);
                showErrorInContainer(container, 'تعذر تحميل الطلبات');
            }
        }
        
        // Load all data with comprehensive error handling
        async function loadAllData() {
            showLoading();
            
            // Set as last action for retry
            if (window.apiHelper) {
                window.apiHelper.setLastAction(loadAllData);
            }
            
            let hasErrors = false;
            
            try {
                // Load all data in parallel with individual error handling
                const results = await Promise.allSettled([
                    loadSummary(),
                    loadMonthlyChart(),
                    loadCategoryChart(),
                    loadTopProducts(),
                    loadRecentOrders()
                ]);
                
                // Check for any failures
                hasErrors = results.some(r => r.status === 'rejected');
                
                if (hasErrors) {
                    console.warn('Some data failed to load');
                }
            } catch (error) {
                console.error('Error loading data:', error);
                hasErrors = true;
            }
            
            hideLoading();
            
            // If all requests failed, show a global error
            if (hasErrors && document.getElementById('totalRevenue').textContent === '---') {
                if (window.apiHelper) {
                    window.apiHelper.showError('تعذر تحميل البيانات - تحقق من اتصالك بالإنترنت');
                }
            }
        }
        
        // Make loadAllData globally available for retry
        window.loadAllData = loadAllData;
        
        // Event listeners
        refreshBtn.addEventListener('click', loadAllData);
        yearFilter.addEventListener('change', loadAllData);
        monthFilter.addEventListener('change', loadAllData);
        
        // Initial load
        document.addEventListener('DOMContentLoaded', loadAllData);
        
        // Auto refresh every 2 minutes
        setInterval(loadAllData, 120000);
    </script>
</body>
</html>
