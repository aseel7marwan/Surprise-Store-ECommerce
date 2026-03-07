<?php
/**
 * Surprise! Store - SEO Functions
 * كلمات مفتاحية ومعلومات SEO للموقع
 */

// ============ SEO CONFIGURATION ============

// اسم الموقع الرسمي الثابت - يظهر في Google ونتائج البحث
define('SITE_BRAND_NAME', 'بيج سبرايز | Surprise page');
define('SITE_BRAND_NAME_AR', 'بيج سبرايز');
define('SITE_BRAND_NAME_EN', 'Surprise page');
define('SITE_TAGLINE', 'متجر هدايا عراقي أونلاين');
define('SITE_DESCRIPTION_FULL', 'بيج سبرايز | Surprise page - متجر هدايا عراقي أونلاين - هدايا فخمة ومميزة لجميع المناسبات: هدايا عيد ميلاد، هدايا زواج، هدايا تخرج، هدايا عيد الحب. تحف مضيئة، ساعات محفورة، أكواب مطبوعة، بوكسات هدايا، إكسسوارات، عطور. توصيل داخل العراق لجميع المحافظات: بغداد، البصرة، أربيل، كربلاء، النجف.');

// الكلمات المفتاحية الرئيسية - عربية
define('SEO_KEYWORDS_AR', array(
    // ═══════════════════════════════════════
    // اسم المتجر والعلامة التجارية
    // ═══════════════════════════════════════
    'بيج سبرايز',
    'Surprise page',
    'بيج سبرايز متجر هدايا',
    'Surprise page Iraq',
    'سبرايز',
    'متجر سبرايز',
    'سبرايز العراق',
    'بيج سربرايز',
    'صفحة سبرايز',
    
    // ═══════════════════════════════════════
    // بيج / بيجات عراقية (كلمات محلية مهمة جداً)
    // ═══════════════════════════════════════
    'بيج هدايا',
    'بيجات هدايا',
    'بيج عراقي',
    'بيجات عراقية',
    'بيج هدايا عراقي',
    'بيج هدايا بغداد',
    'بيج هدية',
    'بيج هديات',
    'بيج بيع هدايا',
    'بيج متجر',
    'بيج متجر عراقي',
    'بيج تجاري',
    'بيج اونلاين',
    'بيج للهدايا',
    'بيج شراء هدايا',
    'بيج هدايا اكسبلور',
    'بيجات انستغرام هدايا',
    'بيج انستقرام هدايا',
    
    // ═══════════════════════════════════════
    // متجر / محل عراقي
    // ═══════════════════════════════════════
    'متجر هدايا',
    'متجر هدايا في العراق',
    'متجر هدايا عراقي',
    'متجر إلكتروني عراقي',
    'متجر هدايا أونلاين',
    'متجر الكتروني عراقي',
    'متجر عراقي اونلاين',
    'متجر اون لاين عراقي',
    'متجر هديا عراقي',
    'محل هدايا',
    'محل هدايا عراقي',
    'محل هدايا في بغداد',
    'محل هداية',
    'دكان هدايا',
    'شراء هدايا',
    'شراء هدايا أونلاين',
    'شراء هديه',
    'اشتري هدية',
    'تسوق أونلاين',
    'تسوق إلكتروني',
    'تسوق عراقي',
    'تسوق هدايا',
    'موقع هدايا عراقي',
    'موقع بيع هدايا',
    'موقع شراء هدايا',
    'متجر هدايا الكتروني',
    
    // ═══════════════════════════════════════
    // الهدايا العراقية
    // ═══════════════════════════════════════
    'هدايا عراقية',
    'هديات عراقية',
    'هدايا محلية عراقية',
    'منتجات عراقية',
    'منتجات محلية عراقية',
    'هدايا العراق',
    'هدية عراقية',
    'هداية عراقية',
    
    // ═══════════════════════════════════════
    // المناسبات
    // ═══════════════════════════════════════
    'هدايا عيد ميلاد',
    'هدية عيد ميلاد',
    'هديه برثدي',
    'birthday gift',
    'هدايا تخرج',
    'هديه تخرج',
    'هدايا زواج',
    'هدايا خطوبة',
    'هدايا عرس',
    'هدية عرس',
    'هدايا عيد الحب',
    'هدية عيد الحب',
    'هدايا فالنتاين',
    'valentine',
    'هدايا عيد الأم',
    'هدية عيد الام',
    'هدايا عيد الفطر',
    'هدايا عيد الأضحى',
    'هدايا مناسبات',
    'هدايا مناسبات خاصة',
    'هدايا رأس السنة',
    'هدايا مواليد',
    'هدية مولود',
    'هدايا نجاح',
    'هدايا رومانسية',
    'هدية رومانسية',
    'هدية حب',
    
    // ═══════════════════════════════════════
    // حسب الفئة
    // ═══════════════════════════════════════
    'هدايا نسائية',
    'هدايا رجالية',
    'هدايا أطفال',
    'هدايا بنات',
    'هدايا شباب',
    'هدايا للرجال',
    'هدايا للنساء',
    'هدية لها',
    'هدية له',
    'هدية للحبيب',
    'هدية للحبيبة',
    'هدية للصديق',
    'هدية للصديقة',
    'هدية للزوج',
    'هدية للزوجة',
    'هدية للام',
    'هدية للاب',
    
    // ═══════════════════════════════════════
    // أنواع الهدايا
    // ═══════════════════════════════════════
    'هدايا فخمة',
    'هدايا مميزة',
    'هدايا شخصية',
    'هدايا مخصصة',
    'هدايا خاصة',
    'أفكار هدايا',
    'افكار هداية',
    'هدايا حديثة',
    'هدايا راقية',
    'هدايا فاخرة',
    'افضل هدية',
    'احلى هدية',
    'اجمل هدية',
    'هدية مميزة',
    'هدية فريدة',
    'فكرة هدية',
    'افكار هدايا',
    'هدايا جديدة',
    'هدايا حلوة',
    'هدايا رخيصة',
    'هدايا بأسعار مناسبة',
    
    // ═══════════════════════════════════════
    // المنتجات
    // ═══════════════════════════════════════
    'عطور',
    'عطور عراق',
    'إكسسوارات',
    'اكسسوارات',
    'منتجات تجميل',
    'هدايا تجميل',
    'منتجات نسائية',
    'منتجات رجالية',
    'تحف مضيئة',
    'تحفة مضيئة',
    'تحف ضوء',
    'لمبة مخصصة',
    'لمبة بالصورة',
    'lamp',
    'ساعة محفورة',
    'ساعة مخصصة',
    'ساعة بالاسم',
    'ساعة حفر',
    'ساعة ليزر',
    'ساعات هدايا',
    'كوب مطبوع',
    'كوب بالصورة',
    'مج مطبوع',
    'اكواب مخصصة',
    'مج بالصورة',
    'mug',
    'بوكس هدية',
    'بوكس هداية',
    'صندوق هدايا',
    'صندوق هدية',
    'بوكسات هدايا',
    'gift box',
    'ميدالية مخصصة',
    'ميدالية بالاسم',
    'سلسلة بالاسم',
    'طباعة على الهدايا',
    'طباعة صور',
    'هدايا بالصورة',
    'هدايا مطبوعة',
    'حفر بالليزر',
    'حفر ليزر',
    'نقش بالليزر',
    
    // ═══════════════════════════════════════
    // الشراء والتوصيل
    // ═══════════════════════════════════════
    'توصيل داخل العراق',
    'توصيل هدايا',
    'توصيل هديا بغداد',
    'طلب هدايا',
    'طلب هدايا أونلاين',
    'شحن داخل العراق',
    'هدايا توصيل',
    'توصيل هدايا العراق',
    'توصيل سريع',
    'توصيل بغداد',
    'دفع عند الاستلام',
    
    // ═══════════════════════════════════════
    // المدن العراقية - موسع
    // ═══════════════════════════════════════
    'هدايا بغداد',
    'بيج هدايا بغداد',
    'متجر هدايا بغداد',
    'هدايا البصرة',
    'بيج هدايا البصرة',
    'هدايا أربيل',
    'هدايا اربيل',
    'بيج هدايا اربيل',
    'هدايا النجف',
    'هدايا كربلاء',
    'هدايا الموصل',
    'هدايا السليمانية',
    'هدايا نينوى',
    'هدايا الانبار',
    'هدايا ديالى',
    'هدايا صلاح الدين',
    'هدايا كركوك',
    'هدايا واسط',
    'هدايا ذي قار',
    'هدايا ميسان',
    'هدايا بابل',
    'هدايا الحلة',
    'هدايا الناصرية',
    'هدايا العمارة',
    'هدايا دهوك',
    'هدايا السماوة',
    'هدايا الكوت',
    'هدايا الديوانية',
    'توصيل المحافظات'
));

// الكلمات المفتاحية الإنجليزية
define('SEO_KEYWORDS_EN', array(
    // Brand
    'Surprise page',
    'Surprise page Iraq',
    'Surprise Iraq',
    'surprise store iraq',
    
    // Online Gift Shop
    'online gift shop',
    'online gift shop iraq',
    'gift shop Iraq',
    'gift store Iraq',
    'gifts online',
    'gifts online iraq',
    'buy gifts online iraq',
    'iraqi gift store',
    'iraqi online store',
    'iraq gift shop',
    'iraq online store',
    
    // Gift Types
    'surprise gifts',
    'personalized gifts',
    'custom gifts',
    'personalized gifts iraq',
    'custom gifts iraq',
    'unique gifts iraq',
    'special gifts iraq',
    'luxury gifts iraq',
    
    // Occasions
    'birthday gifts iraq',
    'birthday gift baghdad',
    'wedding gifts iraq',
    'valentine gifts iraq',
    'valentine day gifts iraq',
    'graduation gifts iraq',
    'anniversary gifts iraq',
    'mother day gifts iraq',
    
    // Products
    'custom mugs iraq',
    'photo gifts iraq',
    'photo printing iraq',
    'engraved gifts',
    'engraved gifts iraq',
    'laser engraving iraq',
    'led lamp custom',
    'custom led lamp iraq',
    'gift box iraq',
    'custom watch iraq',
    'personalized watch',
    
    // Cities
    'baghdad gifts',
    'basra gifts',
    'erbil gifts',
    'gifts delivery iraq',
    'gift delivery baghdad',
    'iraq gift delivery',
    'nationwide delivery iraq'
));

/**
 * الحصول على الكلمات المفتاحية كنص
 */
function getSEOKeywords($lang = 'ar') {
    $keywords = $lang === 'en' ? SEO_KEYWORDS_EN : SEO_KEYWORDS_AR;
    return implode(', ', $keywords);
}

/**
 * الحصول على جميع الكلمات المفتاحية (عربي + إنجليزي)
 */
function getAllSEOKeywords() {
    return implode(', ', array_merge(SEO_KEYWORDS_AR, SEO_KEYWORDS_EN));
}

/**
 * إنشاء Meta Tags للصفحة
 */
function generateMetaTags($options = array()) {
    $defaults = array(
        'title' => SITE_BRAND_NAME . ' - ' . SITE_TAGLINE,
        'description' => SITE_DESCRIPTION_FULL,
        'keywords' => getAllSEOKeywords(),
        'image' => 'https://surprise-iq.com/images/logo.jpg',
        'url' => '',
        'type' => 'website',
        'locale' => 'ar_IQ'
    );
    
    $meta = array_merge($defaults, $options);
    
    // Canonical URL
    if (empty($meta['url'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $meta['url'] = $protocol . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
    }
    
    $output = '';
    
    // Basic Meta Tags
    $output .= '<meta name="description" content="' . htmlspecialchars($meta['description']) . '">' . "\n";
    $output .= '    <meta name="keywords" content="' . htmlspecialchars($meta['keywords']) . '">' . "\n";
    $output .= '    <meta name="author" content="' . SITE_BRAND_NAME . '">' . "\n";
    $output .= '    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">' . "\n";
    $output .= '    <meta name="googlebot" content="index, follow">' . "\n";
    $output .= '    <link rel="canonical" href="' . htmlspecialchars($meta['url']) . '">' . "\n";
    
    // Open Graph Tags (Facebook, WhatsApp, etc.)
    $output .= '    ' . "\n";
    $output .= '    <!-- Open Graph / Facebook -->' . "\n";
    $output .= '    <meta property="og:type" content="' . $meta['type'] . '">' . "\n";
    $output .= '    <meta property="og:url" content="' . htmlspecialchars($meta['url']) . '">' . "\n";
    $output .= '    <meta property="og:title" content="' . htmlspecialchars($meta['title']) . '">' . "\n";
    $output .= '    <meta property="og:description" content="' . htmlspecialchars($meta['description']) . '">' . "\n";
    $output .= '    <meta property="og:image" content="' . htmlspecialchars($meta['image']) . '">' . "\n";
    $output .= '    <meta property="og:locale" content="' . $meta['locale'] . '">' . "\n";
    $output .= '    <meta property="og:site_name" content="' . SITE_BRAND_NAME . '">' . "\n";
    
    // Twitter Card Tags
    $output .= '    ' . "\n";
    $output .= '    <!-- Twitter Card -->' . "\n";
    $output .= '    <meta name="twitter:card" content="summary_large_image">' . "\n";
    $output .= '    <meta name="twitter:url" content="' . htmlspecialchars($meta['url']) . '">' . "\n";
    $output .= '    <meta name="twitter:title" content="' . htmlspecialchars($meta['title']) . '">' . "\n";
    $output .= '    <meta name="twitter:description" content="' . htmlspecialchars($meta['description']) . '">' . "\n";
    $output .= '    <meta name="twitter:image" content="' . htmlspecialchars($meta['image']) . '">' . "\n";
    
    // Geographic Meta Tags for Iraq
    $output .= '    ' . "\n";
    $output .= '    <!-- Geographic Tags -->' . "\n";
    $output .= '    <meta name="geo.region" content="IQ">' . "\n";
    $output .= '    <meta name="geo.country" content="Iraq">' . "\n";
    $output .= '    <meta name="geo.placename" content="بغداد، العراق">' . "\n";
    
    // Language and Locale
    $output .= '    ' . "\n";
    $output .= '    <!-- Language -->' . "\n";
    $output .= '    <meta http-equiv="content-language" content="ar">' . "\n";
    $output .= '    <link rel="alternate" hreflang="ar" href="' . htmlspecialchars($meta['url']) . '">' . "\n";
    
    return $output;
}

/**
 * إنشاء Schema.org JSON-LD للمتجر
 */
function generateStoreSchema() {
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Store',
        'name' => SITE_BRAND_NAME,
        'alternateName' => array(SITE_BRAND_NAME_AR, SITE_BRAND_NAME_EN, 'سبرايز', 'Surprise Iraq', 'متجر سبرايز'),
        'description' => SITE_DESCRIPTION_FULL,
        'url' => 'https://surprise-iq.com',
        'logo' => 'https://surprise-iq.com/images/logo.jpg',
        'image' => 'https://surprise-iq.com/images/logo.jpg',
        'telephone' => '',
        'email' => '',
        'address' => array(
            '@type' => 'PostalAddress',
            'addressCountry' => 'IQ',
            'addressRegion' => 'بغداد'
        ),
        'geo' => array(
            '@type' => 'GeoCoordinates',
            'latitude' => '33.3152',
            'longitude' => '44.3661'
        ),
        'areaServed' => array(
            '@type' => 'Country',
            'name' => 'Iraq'
        ),
        'priceRange' => 'IQD',
        'currenciesAccepted' => 'IQD',
        'paymentAccepted' => 'Cash on Delivery',
        'openingHours' => 'Mo-Su 00:00-23:59',
        'sameAs' => array(
            'https://instagram.com/sur._prises',
            'https://t.me/surprises999'
        ),
        'hasOfferCatalog' => array(
            '@type' => 'OfferCatalog',
            'name' => 'هدايا Surprise!',
            'itemListElement' => array(
                array('@type' => 'OfferCatalog', 'name' => 'تحف مضيئة'),
                array('@type' => 'OfferCatalog', 'name' => 'ساعات مخصصة'),
                array('@type' => 'OfferCatalog', 'name' => 'أكواب مطبوعة'),
                array('@type' => 'OfferCatalog', 'name' => 'بوكسات هدايا')
            )
        )
    );
    
    return '<script type="application/ld+json">' . "\n" . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n" . '</script>';
}

/**
 * إنشاء Schema.org JSON-LD لمنتج
 */
function generateProductSchema($product) {
    if (empty($product)) return '';
    
    $baseUrl = 'https://surprise-iq.com';
    $image = !empty($product['images'][0]) ? $baseUrl . '/images/' . $product['images'][0] : $baseUrl . '/images/logo.jpg';
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product['name'],
        'description' => $product['description'],
        'image' => $image,
        'url' => $baseUrl . '/product?id=' . $product['id'],
        'brand' => array(
            '@type' => 'Brand',
            'name' => SITE_BRAND_NAME
        ),
        'offers' => array(
            '@type' => 'Offer',
            'url' => $baseUrl . '/product?id=' . $product['id'],
            'priceCurrency' => 'IQD',
            'price' => $product['price'],
            'availability' => $product['status'] === 'available' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'seller' => array(
                '@type' => 'Organization',
                'name' => SITE_BRAND_NAME
            ),
            'shippingDetails' => array(
                '@type' => 'OfferShippingDetails',
                'shippingDestination' => array(
                    '@type' => 'DefinedRegion',
                    'addressCountry' => 'IQ'
                )
            )
        )
    );
    
    return '<script type="application/ld+json">' . "\n" . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n" . '</script>';
}

/**
 * إنشاء Schema.org BreadcrumbList
 */
function generateBreadcrumbSchema($items) {
    if (empty($items)) return '';
    
    $listItems = array();
    foreach ($items as $index => $item) {
        $listItems[] = array(
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
            'item' => $item['url']
        );
    }
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $listItems
    );
    
    return '<script type="application/ld+json">' . "\n" . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n" . '</script>';
}

/**
 * إنشاء Schema.org للمؤسسة/الشركة
 */
function generateOrganizationSchema() {
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => SITE_BRAND_NAME,
        'alternateName' => SITE_BRAND_NAME_AR,
        'url' => 'https://surprise-iq.com',
        'logo' => 'https://surprise-iq.com/images/logo.jpg',
        'description' => SITE_DESCRIPTION_FULL,
        'foundingDate' => '2024',
        'foundingLocation' => array(
            '@type' => 'Place',
            'address' => array(
                '@type' => 'PostalAddress',
                'addressCountry' => 'Iraq'
            )
        ),
        'sameAs' => array(
            'https://instagram.com/sur._prises',
            'https://t.me/surprises999'
        ),
        'contactPoint' => array(
            '@type' => 'ContactPoint',
            'contactType' => 'customer service',
            'availableLanguage' => array('Arabic', 'English')
        )
    );
    
    return '<script type="application/ld+json">' . "\n" . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n" . '</script>';
}

/**
 * إنشاء Schema.org WebSite للبحث
 */
function generateWebsiteSchema() {
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => SITE_BRAND_NAME,
        'alternateName' => SITE_BRAND_NAME_AR . ' - متجر الهدايا',
        'url' => 'https://surprise-iq.com',
        'description' => SITE_DESCRIPTION_FULL,
        'inLanguage' => 'ar',
        'publisher' => array(
            '@type' => 'Organization',
            'name' => SITE_BRAND_NAME,
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => 'https://surprise-iq.com/images/logo.jpg'
            )
        )
    );
    
    return '<script type="application/ld+json">' . "\n" . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n" . '</script>';
}
