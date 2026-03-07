<?php require_once 'includes/config.php'; ?>
<?php require_once 'includes/functions.php'; ?>
<?php require_once 'includes/seo.php'; ?>

<?php $settings = getSettings(); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بيج سبرايز | Surprise page</title>
    
    <!-- SEO Meta Tags -->
    <?= generateMetaTags(array(
        'title' => 'سلة التسوق - ' . SITE_BRAND_NAME . ' | إتمام الطلب',
        'description' => 'راجع سلة التسوق وأكمل طلبك من ' . SITE_BRAND_NAME . ' متجر الهدايا الفاخرة في العراق. توصيل سريع لجميع المحافظات العراقية. دفع عند الاستلام.',
        'type' => 'website'
    )) ?>
    
    <link rel="stylesheet" href="<?= v('css/main.css') ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="/images/logo.jpg">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/logo.jpg">
    <link rel="shortcut icon" href="/images/logo.jpg">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/logo.jpg">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#E91E8C">
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <div class="container">
            <div class="header-inner">
                <a href="/" class="logo">
                    <img src="images/logo.jpg" alt="<?= SITE_NAME ?>">
                </a>
                
                <nav class="nav">
                    <ul class="nav-links" id="navLinks">
                        <li><a href="/">الرئيسية</a></li>
                        <li><a href="/products">المنتجات</a></li>
                        <li><a href="/about">من نحن</a></li>
                        <li><a href="/track">تتبع طلبي</a></li>
                        <li><a href="#" onclick="openContactModal(); return false;">تواصل معنا</a></li>
                        <li><a href="/wishlist" class="wishlist-sidebar-link">المفضلة <img src="images/icons/wishlist.png" class="wishlist-sidebar-icon"> <span class="wishlist-count-badge">0</span></a></li>
                    </ul>
                    
                    <!-- Mobile Header Search -->
                    <div class="header-search-wrapper" id="headerSearchWrapper">
                        <button type="button" class="header-search-toggle" id="headerSearchToggle" aria-label="بحث">
                            🔍
                        </button>
                        <form action="/products" method="GET" class="header-search-form" id="headerSearchForm">
                            <input type="text" name="q" class="header-search-input" id="headerSearchInput" 
                                   placeholder="ابحث عن منتج..." autocomplete="off">
                            <button type="submit" class="header-search-submit">🔍</button>
                            <button type="button" class="header-search-close" id="headerSearchClose">✕</button>
                        </form>
                    </div>
                    
                    <a href="/cart" class="cart-icon">
                        🛒
                        <span class="cart-count" id="cartCount">0</span>
                    </a>
                    
                    <button class="menu-toggle" id="menuToggle">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </nav>
            </div>
        </div>
    </header>
    
    <!-- Mobile Menu Overlay -->
    <div class="nav-overlay" id="navOverlay"></div>

    <main style="margin-top: 100px; padding-bottom: 60px;">
        <div class="container">
            <div class="section-header">
                <h1 class="section-title">🛒 سلة التسوق</h1>
                <p class="section-subtitle">راجع طلباتك وأكمل عملية الشراء</p>
            </div>
            
            <div class="cart-container">
                <!-- Empty State -->
                <div class="empty-state" id="cartEmpty" style="display: none;">
                    <div class="empty-icon">🛒</div>
                    <h2 class="empty-title">السلة فارغة</h2>
                    <p class="empty-text">لم تضف أي منتجات للسلة بعد</p>
                    <a href="/products" class="btn btn-primary btn-lg">🛍️ تصفح المنتجات</a>
                </div>
                
                <!-- Cart Items -->
                <div id="cartItems"></div>
                
                <!-- Customer Info Form -->
                <div id="checkoutForm" style="display: none; margin-top: 35px; padding-top: 30px; border-top: 3px solid var(--primary);">
                    <h3 style="margin-bottom: 25px; color: var(--primary); font-size: 1.3rem; font-weight: 700;">📋 معلومات الطلب</h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        <div class="form-group">
                            <label class="form-label">الاسم الكامل *</label>
                            <input type="text" id="customerName" class="form-control" placeholder="اسمك الكريم" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">رقم الهاتف *</label>
                            <input type="tel" id="customerPhone" class="form-control" placeholder="07XX XXX XXXX" required 
                                   pattern="[0-9]*" inputmode="numeric" maxlength="15"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">المحافظة *</label>
                        <select id="customerCity" class="form-control" required onchange="updateDistricts()">
                            <option value="">-- اختر المحافظة --</option>
                            <option value="بغداد">بغداد</option>
                            <option value="البصرة">البصرة</option>
                            <option value="نينوى">نينوى</option>
                            <option value="أربيل">أربيل</option>
                            <option value="النجف">النجف</option>
                            <option value="كربلاء">كربلاء</option>
                            <option value="ذي قار">ذي قار</option>
                            <option value="الأنبار">الأنبار</option>
                            <option value="ديالى">ديالى</option>
                            <option value="كركوك">كركوك</option>
                            <option value="صلاح الدين">صلاح الدين</option>
                            <option value="بابل">بابل</option>
                            <option value="واسط">واسط</option>
                            <option value="ميسان">ميسان</option>
                            <option value="المثنى">المثنى</option>
                            <option value="القادسية">القادسية</option>
                            <option value="دهوك">دهوك</option>
                            <option value="السليمانية">السليمانية</option>
                            <option value="حلبجة">حلبجة</option>
                            <option value="أخرى">📍 أخرى (حدد في العنوان)</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="areaGroup" style="display: none;">
                        <label class="form-label">المنطقة / الحي *</label>
                        <div class="area-select-wrapper">
                            <div class="area-combobox" id="areaCombobox" tabindex="0">
                                <span class="area-combobox-text placeholder" id="areaComboboxText">اختر المحافظة أولاً...</span>
                                <span class="area-combobox-arrow">◀</span>
                            </div>
                            <input type="hidden" id="customerArea" name="customerArea" value="">
                            <div class="area-dropdown" id="areaDropdown">
                                <div class="area-search-box">
                                    <input type="text" id="areaSearchInput" class="area-search-input" placeholder="ابحث في المناطق..." autocomplete="off" dir="rtl">
                                </div>
                                <div class="area-options-list" id="areaOptionsList"></div>
                            </div>
                        </div>
                        <p style="font-size: 0.88rem; color: var(--text-muted); margin-top: 8px;">
                            🚚 التوصيل لجميع المحافظات: <?= formatPrice(isset($settings['delivery_price']) ? $settings['delivery_price'] : 5000) ?>
                        </p>
                    </div>
                    
                    <style>
                    /* ═══════════════════════════════════════════════════════════════
                       Area Combobox - Styled to match Governorate Select
                       ═══════════════════════════════════════════════════════════════ */
                    .area-select-wrapper {
                        position: relative;
                        width: 100%;
                    }
                    
                    /* Main combobox button - matches .form-control select */
                    .area-combobox {
                        width: 100%;
                        min-height: 52px;
                        padding: 14px 18px;
                        border: 2px solid #E9ECEF;
                        border-radius: var(--radius-md, 12px);
                        background: #fff;
                        cursor: pointer;
                        display: flex;
                        flex-direction: row-reverse;
                        justify-content: space-between;
                        align-items: center;
                        gap: 10px;
                        transition: border-color 0.3s, box-shadow 0.3s;
                        font-size: 1rem;
                        font-family: inherit;
                        box-sizing: border-box;
                    }
                    .area-combobox:hover:not(.disabled) {
                        border-color: var(--primary, #E91E8C);
                    }
                    .area-combobox:focus:not(.disabled),
                    .area-combobox.active:not(.disabled) {
                        border-color: var(--primary, #E91E8C);
                        box-shadow: 0 0 0 3px rgba(233, 30, 140, 0.15);
                        outline: none;
                    }
                    .area-combobox.disabled {
                        background: #f8f9fa;
                        cursor: not-allowed;
                        opacity: 0.65;
                        pointer-events: none;
                    }
                    
                    /* Text inside combobox */
                    .area-combobox-text {
                        flex: 1;
                        text-align: right;
                        color: #212529;
                        font-weight: 500;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    .area-combobox-text.placeholder {
                        color: #6c757d;
                        font-weight: 400;
                    }
                    
                    /* Arrow icon */
                    .area-combobox-arrow {
                        color: var(--primary, #E91E8C);
                        font-size: 0.7rem;
                        transition: transform 0.25s ease;
                        flex-shrink: 0;
                    }
                    .area-combobox.active .area-combobox-arrow {
                        transform: rotate(-90deg);
                    }
                    
                    /* Dropdown panel */
                    .area-dropdown {
                        display: none;
                        position: absolute;
                        top: calc(100% - 2px);
                        left: 0;
                        right: 0;
                        background: #fff;
                        border: 2px solid var(--primary, #E91E8C);
                        border-top: 1px solid #eee;
                        border-radius: 0 0 12px 12px;
                        z-index: 9999;
                        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
                        overflow: hidden;
                    }
                    .area-dropdown.show {
                        display: block;
                    }
                    
                    /* Search box */
                    .area-search-box {
                        padding: 12px;
                        background: #f8f9fa;
                        border-bottom: 1px solid #e9ecef;
                    }
                    .area-search-input {
                        width: 100%;
                        padding: 10px 14px;
                        border: 2px solid #dee2e6;
                        border-radius: 8px;
                        font-size: 0.95rem;
                        font-family: inherit;
                        text-align: right;
                        outline: none;
                        transition: border-color 0.2s;
                        box-sizing: border-box;
                    }
                    .area-search-input:focus {
                        border-color: var(--primary, #E91E8C);
                    }
                    .area-search-input::placeholder {
                        color: #adb5bd;
                    }
                    
                    /* Options list */
                    .area-options-list {
                        max-height: 200px;
                        overflow-y: auto;
                        overflow-x: hidden;
                    }
                    
                    /* Individual option item */
                    .area-dropdown-item {
                        display: block;
                        width: 100%;
                        padding: 12px 16px;
                        text-align: right;
                        cursor: pointer;
                        border-bottom: 1px solid #f1f3f4;
                        font-size: 0.95rem;
                        color: #212529;
                        transition: background 0.15s;
                        box-sizing: border-box;
                    }
                    .area-dropdown-item:last-child {
                        border-bottom: none;
                    }
                    .area-dropdown-item:hover,
                    .area-dropdown-item.highlighted {
                        background: linear-gradient(90deg, transparent, #fce4ec);
                    }
                    .area-dropdown-item .area-district {
                        color: var(--primary, #E91E8C);
                        font-weight: 600;
                    }
                    
                    /* Empty state */
                    .area-dropdown-empty {
                        padding: 24px 16px;
                        text-align: center;
                        color: #6c757d;
                        font-size: 0.9rem;
                    }
                    
                    /* Mobile adjustments */
                    @media (max-width: 576px) {
                        .area-combobox {
                            min-height: 48px;
                            padding: 12px 14px;
                        }
                        .area-options-list {
                            max-height: 180px;
                        }
                        .area-dropdown-item {
                            padding: 14px 14px;
                        }
                    }
                    </style>
                    
                    <div class="form-group">
                        <label class="form-label">العنوان التفصيلي / أقرب نقطة دالة *</label>
                        <textarea id="customerAddress" class="form-control" rows="3" required
                            placeholder="مثال: حي الكرادة - قرب مجمع الأمل التجاري - عمارة رقم 15 - الطابق الثالث"></textarea>
                        <p style="font-size: 0.88rem; color: var(--text-muted); margin-top: 8px;">
                            📍 اكتب أقرب نقطة دالة أو معلم معروف لتسهيل التوصيل
                        </p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">ملاحظات إضافية (اختياري)</label>
                        <textarea id="orderNotes" class="form-control" rows="2" placeholder="أي ملاحظات خاصة بالطلب أو التغليف أو وقت التوصيل المفضل..."></textarea>
                    </div>
                    
                    <!-- Payment Method Selection -->
                    <div class="form-group" style="margin-top: 25px;">
                        <label class="form-label" style="display: flex; align-items: center; gap: 8px; font-size: 1.1rem;">
                            💳 طريقة الدفع *
                        </label>
                        <?= renderPaymentMethodsHTML('select') ?>
                    </div>
                    
                    <!-- Contact Method Selection -->
                    <div class="form-group" style="margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #e3f2fd, #f3e5f5); border-radius: 16px; border: 2px solid rgba(233, 30, 140, 0.2);">
                        <label class="form-label" style="display: flex; align-items: center; gap: 8px; font-size: 1.1rem; color: var(--primary); margin-bottom: 15px;">
                            📱 كيف نتواصل معك؟ *
                        </label>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;">
                            اختر الوسيلة المفضلة لديك للتواصل معك بخصوص طلبك
                        </p>
                        
                        <div class="contact-methods-select" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 15px;">
                            <label class="contact-option" style="cursor: pointer;">
                                <input type="radio" name="contactMethod" value="instagram" onchange="showContactInput('instagram')">
                                <div class="contact-option-content" style="padding: 12px; background: #fff; border: 2px solid #E9ECEF; border-radius: 12px; text-align: center; transition: all 0.3s;">
                                    <img src="images/icons/icon1.png" alt="Instagram" style="width: 28px; height: 28px; display: block; margin: 0 auto 5px;">
                                    <span style="font-size: 0.85rem; font-weight: 600; color: #E1306C;">انستقرام</span>
                                </div>
                            </label>
                            <label class="contact-option" style="cursor: pointer;">
                                <input type="radio" name="contactMethod" value="whatsapp" onchange="showContactInput('whatsapp')">
                                <div class="contact-option-content" style="padding: 12px; background: #fff; border: 2px solid #E9ECEF; border-radius: 12px; text-align: center; transition: all 0.3s;">
                                    <img src="images/icons/whatsapp.png" alt="WhatsApp" style="width: 28px; height: 28px; display: block; margin: 0 auto 5px;">
                                    <span style="font-size: 0.85rem; font-weight: 600; color: #25D366;">واتساب</span>
                                </div>
                            </label>
                            <label class="contact-option" style="cursor: pointer;">
                                <input type="radio" name="contactMethod" value="telegram" onchange="showContactInput('telegram')">
                                <div class="contact-option-content" style="padding: 12px; background: #fff; border: 2px solid #E9ECEF; border-radius: 12px; text-align: center; transition: all 0.3s;">
                                    <img src="images/icons/icon2.png" alt="Telegram" style="width: 28px; height: 28px; display: block; margin: 0 auto 5px;">
                                    <span style="font-size: 0.85rem; font-weight: 600; color: #0088cc;">تيليجرام</span>
                                </div>
                            </label>
                        </div>
                        
                        <!-- Dynamic Input Fields -->
                        <div id="contactInputContainer" style="display: none;">
                            <!-- Instagram Input -->
                            <div id="instagramInput" style="display: none;">
                                <label class="form-label">اسم المستخدم (Instagram) *</label>
                                <input type="text" id="contactInstagram" class="form-control" 
                                       placeholder="@username" 
                                       pattern="^@?[a-zA-Z0-9_.]+$"
                                       oninput="validateInstagram(this)"
                                       style="direction: ltr; text-align: left;">
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px;">
                                    أحرف إنكليزية وأرقام و _ و . فقط
                                </p>
                            </div>
                            
                            <!-- WhatsApp Input -->
                            <div id="whatsappInput" style="display: none;">
                                <label class="form-label">رقم الواتساب *</label>
                                <input type="tel" id="contactWhatsapp" class="form-control" 
                                       placeholder="07XXXXXXXXX" 
                                       pattern="[0-9]+"
                                       inputmode="numeric"
                                       oninput="validateWhatsapp(this)"
                                       style="direction: ltr; text-align: left;">
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px;">
                                    أرقام فقط (0-9)
                                </p>
                            </div>
                            
                            <!-- Telegram Input -->
                            <div id="telegramInput" style="display: none;">
                                <label class="form-label">اسم المستخدم أو رقم الهاتف (Telegram) *</label>
                                <input type="text" id="contactTelegram" class="form-control" 
                                       placeholder="@username أو 07XXXXXXXXX" 
                                       oninput="validateTelegram(this)"
                                       style="direction: ltr; text-align: left;">
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px;">
                                    اسم مستخدم (إنكليزي) أو رقم هاتف
                                </p>
                            </div>
                            
                            <!-- Error Message -->
                            <div id="contactError" style="display: none; color: #dc3545; font-size: 0.85rem; margin-top: 8px; padding: 8px 12px; background: #fff5f5; border-radius: 8px;">
                                ⚠️ <span id="contactErrorText"></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <style>
                .contact-option input[type="radio"] {
                    position: absolute;
                    opacity: 0;
                    pointer-events: none;
                }
                .contact-option input[type="radio"]:checked + .contact-option-content {
                    border-color: var(--primary);
                    background: linear-gradient(135deg, #FFF0F5, #FCE4EC);
                    box-shadow: 0 0 15px rgba(233, 30, 140, 0.2);
                }
                .contact-option:hover .contact-option-content {
                    border-color: var(--primary);
                    background: #FFF0F5;
                }
                @media (max-width: 480px) {
                    .contact-methods-select {
                        grid-template-columns: 1fr !important;
                    }
                }
                </style>
                
                <!-- Cart Summary & Actions -->
                <div id="cartActions" style="display: none;">
                    <div class="cart-summary" style="background: #FFFFFF; padding: 25px; border-radius: 16px; border: 2px solid #E9ECEF; box-shadow: var(--shadow-md);">
                        <!-- Coupon Section -->
                        <div id="couponSection" style="margin-bottom: 20px; padding: 15px; background: linear-gradient(135deg, #e8f5e9, #f1f8e9); border-radius: 12px; border: 2px dashed #4CAF50;">
                            <label style="display: block; font-weight: 700; margin-bottom: 10px; color: #4CAF50;">🎁 هل لديك كود خصم؟</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="couponCode" class="form-control" placeholder="أدخل الكود هنا" style="flex: 1; text-transform: uppercase; background: #fff; border-color: #DEE2E6; color: var(--text-dark);">
                                <button type="button" id="applyCouponBtn" class="btn btn-primary" style="white-space: nowrap;">تطبيق</button>
                            </div>
                            <div id="couponMessage" style="margin-top: 10px; display: none;"></div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 1.1rem; color: var(--text-muted);">
                            <span>المجموع الفرعي:</span>
                            <span id="cartSubtotal" style="font-weight: 600; color: var(--text-dark);">0 د.ع</span>
                        </div>
                        
                        <!-- Discount Row (hidden by default) -->
                        <div id="discountRow" style="display: none; justify-content: space-between; margin-bottom: 15px; color: #4CAF50;">
                            <span>🎁 الخصم:</span>
                            <span id="discountAmount" style="font-weight: 600;">-0 د.ع</span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; color: var(--text-muted);">
                            <span>🚚 التوصيل:</span>
                            <span id="deliveryFee" style="color: var(--text-dark);"><?= formatPrice(isset($settings['delivery_price']) ? $settings['delivery_price'] : 5000) ?></span>
                        </div>

                        <!-- Packaging Total Row (hidden by default) -->
                        <div id="packagingRow" style="display: none; justify-content: space-between; margin-bottom: 15px; color: #9c27b0;">
                            <span>🎁 كلفة التغليف:</span>
                            <span id="packagingTotal" style="font-weight: 600;">0 د.ع</span>
                        </div>
                        
                        <div class="cart-total" style="padding-top: 20px; border-top: 2px solid #E9ECEF; display: flex; justify-content: space-between; font-size: 1.4rem; font-weight: 700;">
                            <span style="color: var(--text-dark);">الإجمالي:</span>
                            <span id="cartTotal" style="color: var(--primary); font-size: 1.5rem;">0 د.ع</span>
                        </div>
                        
                        <!-- Terms & Privacy Consent -->
                        <div id="consentSection" style="margin-top: 25px; padding: 18px; background: linear-gradient(135deg, #FFF5F8, #FFFFFF); border-radius: 12px; border: 2px solid rgba(233, 30, 140, 0.15);">
                            <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer; line-height: 1.7;">
                                <input type="checkbox" id="termsConsent" style="width: 22px; height: 22px; margin-top: 3px; accent-color: var(--primary); cursor: pointer; flex-shrink: 0;">
                                <span style="font-size: 0.9rem; color: #333;">
                                    أوافق على <a href="/terms" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: underline;">الشروط والأحكام</a> 
                                    و<a href="/privacy" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: underline;">سياسة الخصوصية</a>، 
                                    وأوافق على استخدام معلومات التواصل التي أدخلتها للتواصل معي بخصوص طلبي.
                                </span>
                            </label>
                            <div id="consentError" style="display: none; color: #dc3545; font-size: 0.85rem; margin-top: 10px; padding: 8px 12px; background: #fff5f5; border-radius: 8px;">
                                ⚠️ يجب الموافقة على الشروط والأحكام وسياسة الخصوصية للمتابعة
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 30px;">
                            <button class="btn btn-primary btn-lg" id="checkoutBtn" style="flex: 1; background: linear-gradient(135deg, #E91E8C 0%, #C2185B 100%); font-size: 1.1rem; padding: 18px;">
                                ✅ إتمام الطلب
                            </button>
                            <button class="btn btn-outline" id="clearCart">
                                🗑️ تفريغ السلة
                            </button>
                        </div>
                        
                        <p style="text-align: center; margin-top: 18px; font-size: 0.9rem; color: var(--text-muted);">
                            <?= getPaymentInfoText() ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- How to Order -->
            <div style="background: var(--bg-card); border-radius: var(--radius-lg); padding: 35px; margin-top: 35px; box-shadow: var(--shadow-sm); border: 1px solid rgba(233, 30, 140, 0.08);">
                <h3 style="color: var(--primary); margin-bottom: 20px; font-size: 1.3rem; font-weight: 700;">📋 طريقة الطلب</h3>
                <ol style="padding-right: 25px; line-height: 2.2; color: var(--text-light);">
                    <li>أضف المنتجات المطلوبة للسلة</li>
                    <li>أدخل معلومات التوصيل بالكامل</li>
                    <li>اضغط على "إتمام الطلب"</li>
                    <li>أكد طلبك واحصل على رقم الطلب</li>
                    <li>احفظ رقم الطلب لتتبع حالته</li>
                    <li>سنتواصل معك لتأكيد الموعد والتفاصيل</li>
                </ol>
            </div>

    <!-- Confirmation/Review Modal -->
    <div id="confirmModal" class="order-modal" style="display: none;">
        <div class="order-modal-overlay" onclick="closeConfirmModal()"></div>
        <div class="order-modal-content review-modal-content">
            <!-- Content will be dynamically populated by JavaScript -->
            <div class="order-modal-icon">📋</div>
            <h2 class="order-modal-title">مراجعة الطلب</h2>
            <p class="order-modal-text">جاري تحميل البيانات...</p>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="order-modal" style="display: none;">
        <div class="order-modal-overlay"></div>
        <div class="order-modal-content success">
            <div class="order-modal-icon success-icon">🎉</div>
            <h2 class="order-modal-title">تم استلام طلبك بنجاح!</h2>
            <p class="order-modal-text">شكراً لثقتك بنا</p>
            <div class="order-number-box">
                <span class="order-number-label">رقم طلبك</span>
                <span class="order-number-value" id="orderNumberDisplay">---</span>
                <button class="copy-btn" id="copyOrderBtn" onclick="copyOrderNumber()">📋 نسخ</button>
            </div>
            <p class="order-modal-warning">⚠️ احفظ رقم الطلب لتتبع طلبك لاحقاً</p>
            <div class="order-modal-buttons">
                <a href="/track" class="btn-track">📦 تتبع الطلب</a>
                <a href="/products" class="btn-continue">🛍️ متابعة التسوق</a>
            </div>
        </div>
    </div>

    <style>
    /* Order Modals */
    .order-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .order-modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
    }
    
    .order-modal-content {
        position: relative;
        background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 24px;
        padding: 40px 30px;
        max-width: 420px;
        width: 100%;
        text-align: center;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
        animation: modalSlideIn 0.4s ease;
    }
    
    .order-modal-content.success {
        background: linear-gradient(145deg, #e8f5e9 0%, #ffffff 100%);
        border: 3px solid #4CAF50;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: scale(0.8) translateY(-30px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    
    .order-modal-icon {
        font-size: 4rem;
        margin-bottom: 15px;
    }
    
    .success-icon {
        animation: bounce 0.6s ease;
    }
    
    @keyframes bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
    
    .order-modal-title {
        font-size: 1.6rem;
        font-weight: 800;
        color: #1a1a2e;
        margin-bottom: 10px;
    }
    
    .order-modal-text {
        font-size: 1.1rem;
        color: #555;
        margin-bottom: 5px;
    }
    
    .order-modal-subtext {
        font-size: 0.9rem;
        color: #888;
        margin-bottom: 25px;
    }
    
    .order-number-box {
        background: linear-gradient(135deg, #E91E8C 0%, #C2185B 100%);
        border-radius: 16px;
        padding: 20px;
        margin: 25px 0;
    }
    
    .order-number-label {
        display: block;
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    
    .order-number-value {
        display: block;
        color: #fff;
        font-size: 2rem;
        font-weight: 900;
        letter-spacing: 3px;
        margin-bottom: 12px;
    }
    
    .copy-btn {
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.5);
        color: #fff;
        padding: 8px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .copy-btn:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    .order-modal-warning {
        background: #fff3cd;
        color: #856404;
        padding: 12px;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 25px;
    }
    
    .order-modal-buttons {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .btn-confirm {
        flex: 1;
        min-width: 140px;
        padding: 15px 25px;
        background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
        color: #fff;
        border: none;
        border-radius: 12px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-confirm:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
    }
    
    .btn-confirm:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
    }
    
    .btn-cancel {
        flex: 1;
        min-width: 120px;
        padding: 15px 25px;
        background: #f5f5f5;
        color: #666;
        border: 2px solid #ddd;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-cancel:hover {
        background: #eee;
        border-color: #ccc;
    }
    
    .btn-track, .btn-continue {
        flex: 1;
        min-width: 130px;
        padding: 14px 20px;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 700;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s;
    }
    
    .btn-track {
        background: linear-gradient(135deg, #E91E8C 0%, #C2185B 100%);
        color: #fff;
    }
    
    .btn-track:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(233, 30, 140, 0.4);
    }
    
    .btn-continue {
        background: #f8f9fa;
        color: #555;
        border: 2px solid #ddd;
    }
    
    .btn-continue:hover {
        background: #eee;
    }
    
    @media (max-width: 480px) {
        .order-modal-content {
            padding: 30px 20px;
            margin: 10px;
        }
        
        .order-modal-icon {
            font-size: 3rem;
        }
        
        .order-modal-title {
            font-size: 1.3rem;
        }
        
        .order-number-value {
            font-size: 1.6rem;
        }
        
        .order-modal-buttons {
            flex-direction: column;
        }
        
        .btn-confirm, .btn-cancel, .btn-track, .btn-continue {
            width: 100%;
        }
    }
    </style>
            

        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-about">
                    <div class="footer-logo">
                        <img src="images/logo.jpg" alt="<?= SITE_NAME ?>">
                        <span><?= SITE_NAME ?></span>
                    </div>
                    <p>
                        متجر الهدايا الفاخرة في العراق. نقدم لكم أجمل التحف والهدايا المخصصة بأعلى جودة وأفضل الأسعار.
                    </p>
                    <div class="social-links">
                        <a href="<?= INSTAGRAM_URL ?>" target="_blank" class="social-link instagram" title="Instagram">
                            <span class="social-text"><?= INSTAGRAM_USER ?>@</span>
                            <span class="social-name">Instagram</span>
                            <img src="images/icons/icon1.png" alt="Instagram" class="social-icon">
                        </a>
                        <a href="<?= TELEGRAM_CHANNEL_URL ?>" target="_blank" class="social-link telegram" title="Telegram">
                            <span class="social-text"><?= TELEGRAM_CHANNEL ?>@</span>
                            <span class="social-name">Telegram</span>
                            <img src="images/icons/icon2.png" alt="Telegram" class="social-icon">
                        </a>
                    </div>
                </div>
                
                <div>
                    <h3 class="footer-title">روابط سريعة</h3>
                    <ul class="footer-links">
                        <li><a href="/">🏠 الرئيسية</a></li>
                        <li><a href="/products">🛍️ جميع المنتجات</a></li>
                        <li><a href="/cart">🛒 سلة التسوق</a></li>
                        <li><a href="/privacy">🔒 سياسة الخصوصية</a></li>
                        <li><a href="/terms">📜 الشروط والأحكام</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="footer-title">الأقسام</h3>
                    <ul class="footer-links">
                        <?php foreach (getCategories() as $key => $cat): ?>
                        <li><a href="/products?category=<?= $key ?>"><?= $cat['icon'] ?> <?= $cat['name'] ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div>
                    <h3 class="footer-title">التوصيل</h3>
                    <ul class="footer-links">
                        <li>📦 جميع المحافظات: <?= formatPrice(isset($settings['delivery_price']) ? $settings['delivery_price'] : 5000) ?></li>
                        <li>🚚 توصيل سريع وآمن</li>
                        <?= renderPaymentMethodsHTML('list') ?>
                        <li>📞 دعم على مدار الساعة</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>© <?= date('Y') ?> <?= SITE_NAME ?> - جميع الحقوق محفوظة 💖</p>
            </div>
        </div>
    </footer>

    <script>
        window.INSTAGRAM_URL = '<?= INSTAGRAM_URL ?>';
        window.INSTAGRAM_USER = '<?= INSTAGRAM_USER ?>';
        window.TELEGRAM_URL = '<?= TELEGRAM_CHANNEL_URL ?>';
        window.TELEGRAM_USER = '<?= TELEGRAM_ORDER_USERNAME ?>';
    </script>
    <script src="<?= v('js/api-helper.js') ?>"></script>
    <script src="<?= v('js/app.js') ?>"></script>
    
    <!-- Contact Method Validation -->
    <script>
    // التحقق من وجود أحرف عربية
    function hasArabic(str) {
        return /[\u0600-\u06FF]/.test(str);
    }
    
    // عرض خطأ
    function showContactError(msg) {
        const errorDiv = document.getElementById('contactError');
        const errorText = document.getElementById('contactErrorText');
        if (errorDiv && errorText) {
            errorText.textContent = msg;
            errorDiv.style.display = 'block';
        }
    }
    
    // إخفاء خطأ
    function hideContactError() {
        const errorDiv = document.getElementById('contactError');
        if (errorDiv) errorDiv.style.display = 'none';
    }
    
    // عرض حقل الإدخال حسب الاختيار
    function showContactInput(method) {
        const container = document.getElementById('contactInputContainer');
        const instagramDiv = document.getElementById('instagramInput');
        const whatsappDiv = document.getElementById('whatsappInput');
        const telegramDiv = document.getElementById('telegramInput');
        
        // إظهار الحاوية
        container.style.display = 'block';
        
        // إخفاء الكل
        instagramDiv.style.display = 'none';
        whatsappDiv.style.display = 'none';
        telegramDiv.style.display = 'none';
        hideContactError();
        
        // إظهار المطلوب
        if (method === 'instagram') {
            instagramDiv.style.display = 'block';
            document.getElementById('contactInstagram').focus();
        } else if (method === 'whatsapp') {
            whatsappDiv.style.display = 'block';
            document.getElementById('contactWhatsapp').focus();
        } else if (method === 'telegram') {
            telegramDiv.style.display = 'block';
            document.getElementById('contactTelegram').focus();
        }
    }
    
    // التحقق من انستقرام
    function validateInstagram(input) {
        let value = input.value;
        
        // منع العربي
        if (hasArabic(value)) {
            value = value.replace(/[\u0600-\u06FF]/g, '');
            input.value = value;
            showContactError('غير مسموح بالأحرف العربية');
            return false;
        }
        
        // السماح فقط بـ a-z A-Z 0-9 _ . @
        value = value.replace(/[^a-zA-Z0-9_.@]/g, '');
        input.value = value;
        
        if (value && !/^@?[a-zA-Z0-9_.]+$/.test(value)) {
            showContactError('اسم المستخدم يجب أن يحتوي فقط على أحرف إنكليزية وأرقام و _ و .');
            return false;
        }
        
        hideContactError();
        return true;
    }
    
    // التحقق من واتساب
    function validateWhatsapp(input) {
        let value = input.value;
        
        // منع العربي
        if (hasArabic(value)) {
            value = value.replace(/[\u0600-\u06FF]/g, '');
            input.value = value;
            showContactError('غير مسموح بالأحرف العربية');
            return false;
        }
        
        // أرقام فقط
        value = value.replace(/[^0-9]/g, '');
        input.value = value;
        
        hideContactError();
        return true;
    }
    
    // التحقق من تيليجرام
    function validateTelegram(input) {
        let value = input.value;
        
        // منع العربي
        if (hasArabic(value)) {
            value = value.replace(/[\u0600-\u06FF]/g, '');
            input.value = value;
            showContactError('غير مسموح بالأحرف العربية');
            return false;
        }
        
        // إذا يبدأ بـ @ أو حرف = اسم مستخدم
        if (/^[@a-zA-Z]/.test(value)) {
            // السماح فقط بـ a-z A-Z 0-9 _ @
            value = value.replace(/[^a-zA-Z0-9_@]/g, '');
            input.value = value;
            
            if (value && !/^@?[a-zA-Z0-9_]+$/.test(value)) {
                showContactError('اسم المستخدم يجب أن يحتوي فقط على أحرف إنكليزية وأرقام و _');
                return false;
            }
        } else {
            // رقم هاتف
            value = value.replace(/[^0-9]/g, '');
            input.value = value;
        }
        
        hideContactError();
        return true;
    }
    
    // جلب بيانات التواصل
    function getContactData() {
        const methodRadio = document.querySelector('input[name="contactMethod"]:checked');
        if (!methodRadio) {
            return { valid: false, error: 'يرجى اختيار وسيلة التواصل' };
        }
        
        const method = methodRadio.value;
        let value = '';
        let formattedValue = '';
        
        if (method === 'instagram') {
            value = document.getElementById('contactInstagram').value.trim();
            if (!value) return { valid: false, error: 'يرجى إدخال اسم المستخدم في انستقرام' };
            if (hasArabic(value)) return { valid: false, error: 'غير مسموح بالأحرف العربية' };
            if (!/^@?[a-zA-Z0-9_.]+$/.test(value)) return { valid: false, error: 'اسم مستخدم غير صالح' };
            formattedValue = value.startsWith('@') ? value : '@' + value;
        } else if (method === 'whatsapp') {
            value = document.getElementById('contactWhatsapp').value.trim();
            if (!value) return { valid: false, error: 'يرجى إدخال رقم الواتساب' };
            if (hasArabic(value)) return { valid: false, error: 'غير مسموح بالأحرف العربية' };
            if (!/^[0-9]+$/.test(value)) return { valid: false, error: 'رقم الواتساب يجب أن يحتوي أرقام فقط' };
            formattedValue = value;
        } else if (method === 'telegram') {
            value = document.getElementById('contactTelegram').value.trim();
            if (!value) return { valid: false, error: 'يرجى إدخال معرف أو رقم تيليجرام' };
            if (hasArabic(value)) return { valid: false, error: 'غير مسموح بالأحرف العربية' };
            
            // تحديد إذا كان اسم مستخدم أو رقم
            if (/^[0-9]+$/.test(value)) {
                formattedValue = value; // رقم
            } else if (/^@?[a-zA-Z0-9_]+$/.test(value)) {
                formattedValue = value.startsWith('@') ? value : '@' + value; // اسم مستخدم
            } else {
                return { valid: false, error: 'معرف أو رقم غير صالح' };
            }
        }
        
        return {
            valid: true,
            method: method,
            value: formattedValue,
            methodLabel: method === 'instagram' ? 'انستقرام' : (method === 'whatsapp' ? 'واتساب' : 'تيليجرام')
        };
    }
    
    // تصدير للاستخدام في cart.js
    window.getContactData = getContactData;
    </script>
    
    <script src="<?= v('js/cart.js') ?>"></script>
</body>
</html>
