<!-- Mobile Menu Toggle -->
<button class="admin-menu-toggle" id="adminMenuToggle">
    <span></span>
    <span></span>
    <span></span>
</button>

<!-- Sidebar Overlay -->
<div class="admin-sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="admin-sidebar" id="sidebar">
    <button class="admin-sidebar-close" id="sidebarClose">×</button>
    <div class="admin-sidebar-header">
        <img src="../images/logo.jpg" alt="<?= SITE_NAME ?>">
        <div>
            <h2>لوحة التحكم</h2>
            <?php if (isStaff()): ?>
            <small style="color: #888; font-size: 0.75rem;"><?= htmlspecialchars($_SESSION['staff_name'] ?? '') ?></small>
            <?php endif; ?>
        </div>
    </div>
    <ul class="admin-nav">
        <?php 
        $currentPage = basename($_SERVER['PHP_SELF'], '.php');
        ?>
        <?php if (hasPermission('dashboard')): ?>
        <li><a href="./" data-pjax class="<?= $currentPage === 'index' ? 'active' : '' ?>"><span class="icon">🏠</span><span>الرئيسية</span></a></li>
        <?php endif; ?>
        <?php if (hasPermission('reports')): ?>
        <li><a href="reports" data-pjax class="<?= $currentPage === 'reports' ? 'active' : '' ?>"><span class="icon">📊</span><span>التقارير</span></a></li>
        <?php endif; ?>
        <?php if (hasPermission('sales')): ?>
        <li><a href="sales" data-pjax class="<?= $currentPage === 'sales' ? 'active' : '' ?>"><span class="icon">💰</span><span>المبيعات</span></a></li>
        <?php endif; ?>
        <?php if (hasPermission('orders')): ?>
        <li><a href="orders" data-pjax class="<?= $currentPage === 'orders' ? 'active' : '' ?>"><span class="icon">📋</span><span>الطلبات</span></a></li>
        <?php endif; ?>
        <?php if (hasPermission('products')): ?>
        <li><a href="products" data-pjax class="<?= $currentPage === 'products' ? 'active' : '' ?>"><span class="icon">📦</span><span>المنتجات</span></a></li>
        <?php endif; ?>
        <?php if (hasPermission('categories')): ?>
        <li><a href="categories" data-pjax class="<?= $currentPage === 'categories' ? 'active' : '' ?>"><span class="icon">📂</span><span>الأقسام</span></a></li>
        <?php endif; ?>
        <?php if (hasPermission('coupons')): ?>
        <li><a href="coupons" data-pjax class="<?= $currentPage === 'coupons' ? 'active' : '' ?>"><span class="icon">🎟️</span><span>الكوبونات</span></a></li>
        <?php endif; ?>
        <?php if (hasPermission('reviews')): ?>
        <li><a href="reviews" data-pjax class="<?= $currentPage === 'reviews' ? 'active' : '' ?>"><span class="icon">⭐</span><span>التقييمات</span></a></li>
        <?php endif; ?>
        <?php if (hasPermission('banners')): ?>
        <li><a href="banners" data-pjax class="<?= $currentPage === 'banners' ? 'active' : '' ?>"><span class="icon">🖼️</span><span>البانرات</span></a></li>
        <?php endif; ?>
        <?php /* if (hasPermission('backup')): ?>
        <li><a href="backup" data-pjax class="<?= $currentPage === 'backup' ? 'active' : '' ?>"><span class="icon">💾</span><span>النسخ الاحتياطي</span></a></li>
        <?php endif; */ ?>
        <?php if (hasPermission('staff_manage')): ?>
        <!-- إدارة الموظفين - للمدير أو من لديه صلاحية -->
        <li><a href="staff" data-pjax class="<?= $currentPage === 'staff' ? 'active' : '' ?>"><span class="icon">👥</span><span>إدارة الموظفين</span></a></li>
        <?php endif; ?>
        <?php if (hasPermission('settings_manage')): ?>
        <!-- الإعدادات - للمدير أو من لديه صلاحية -->
        <li><a href="settings" data-pjax class="<?= $currentPage === 'settings' ? 'active' : '' ?>"><span class="icon">⚙️</span><span>الإعدادات</span></a></li>
        <?php endif; ?>
        <?php if (hasPermission('security_log')): ?>
        <!-- سجل الأمان - حسب الصلاحية -->
        <li><a href="security-log" data-pjax class="<?= $currentPage === 'security-log' ? 'active' : '' ?>"><span class="icon">🛡️</span><span>سجل الأمان</span></a></li>
        <?php endif; ?>
        <li><a href="../" target="_blank"><span class="icon">🌐</span><span>عرض الموقع</span></a></li>
        <li><a href="logout" style="color: #ff6b6b;"><span class="icon">🚪</span><span>تسجيل الخروج</span></a></li>
    </ul>
</aside>
