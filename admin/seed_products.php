<?php
/**
 * Surprise! Store - Product Seeder Script
 * هذا السكريبت لإضافة كل المنتجات إلى قاعدة البيانات
 * يتم تشغيله مرة واحدة فقط
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

initSecureSession();
setSecurityHeaders();

// Check if admin is logged in
if (!validateAdminSession()) {
    die('غير مسموح - يجب تسجيل الدخول كمدير');
}

// Product data with all details
$products = [
    // 1. طقم ساعة رولكس مع حفر الاسم (علبة خشبية حمراء)
    [
        'name' => 'طقم ساعة رولكس فاخر مع حفر الاسم',
        'description' => 'طقم ساعة رولكس فاخر في علبة خشبية أنيقة باللون الأحمر الداكن مع بطانة جلدية. يشمل ساعة رولكس ذهبية وفضية مع حفر الاسم بالعربي على واجهة الساعة، إضافة إلى سبحة أنيقة. الهدية المثالية لرجل الأعمال والمناسبات الخاصة. يمكن تخصيص الاسم والتصميم حسب رغبتك.',
        'price' => 125000,
        'old_price' => 150000,
        'category' => 'watches',
        'images' => [
            'products/photo_2025-08-23_13-21-42.jpg',
            'products/photo_2025-08-23_13-21-43.jpg'
        ],
        'customizable' => true,
        'featured' => true,
        'status' => 'available'
    ],
    
    // 2. طقم ساعة رولكس أخضر كامل
    [
        'name' => 'طقم رولكس VIP الكامل',
        'description' => 'طقم رولكس الفاخر الكامل في علبة خضراء رسمية. يشمل ساعة رولكس ذهبية مع حفر الاسم، سبحة فاخرة، أزرار قميص كريستال، وقلم فاخر. الطقم المتكامل للرجل الأنيق. جميع القطع مصممة بجودة عالية ويمكن تخصيص الاسم على جميع القطع.',
        'price' => 185000,
        'old_price' => 220000,
        'category' => 'watches',
        'images' => [
            'products/photo_2025-08-23_13-24-42.jpg'
        ],
        'customizable' => true,
        'featured' => true,
        'status' => 'available'
    ],
    
    // 3. قبة زجاجية مضيئة مخصصة بالاسم
    [
        'name' => 'قبة زجاجية مضيئة مخصصة بالاسم',
        'description' => 'قبة زجاجية أنيقة مع إضاءة LED ساحرة وقاعدة خشبية. يمكن كتابة اسمك أو رسالتك المفضلة عليها بخط عربي جميل. مثالية للهدايا الرومانسية، أعياد الميلاد، أو كتذكار مميز. الإضاءة الدافئة تضيف لمسة سحرية للغرفة.',
        'price' => 28000,
        'old_price' => 35000,
        'category' => 'decorations',
        'images' => [
            'products/photo_2025-11-08_23-26-05.jpg',
            'products/photo_2025-11-08_23-26-06.jpg'
        ],
        'customizable' => true,
        'featured' => true,
        'status' => 'available'
    ],
    
    // 4. كوب سيراميك مطبوع بتصميم أنمي
    [
        'name' => 'كوب سيراميك مطبوع - تصميم أنمي',
        'description' => 'كوب سيراميك عالي الجودة مع طباعة احترافية لتصميم أنمي رائع. الطباعة مقاومة للخدش والغسل. مثالي لمحبي الأنمي والفن الياباني. يمكنك طلب أي تصميم أنمي مفضل لديك أو صورتك الشخصية بستايل أنمي.',
        'price' => 12000,
        'old_price' => 0,
        'category' => 'printing',
        'images' => [
            'products/photo_2025-11-21_09-51-37.jpg',
            'products/photo_2025-11-21_09-51-38.jpg'
        ],
        'customizable' => true,
        'featured' => false,
        'status' => 'available'
    ],
    
    // 5. بوكس الأركيلة الفخمة
    [
        'name' => 'بوكس الأركيلة الفخم',
        'description' => 'بوكس هدية فاخر يحتوي على أركيلة مزخرفة بتصميم أنيق باللون الأسود والفضي، مع ورود حمراء وتغليف احترافي. الهدية المثالية لمحبي الأركيلة. يشمل بطاقة اهداء مخصصة مع رسالتك الشخصية.',
        'price' => 75000,
        'old_price' => 90000,
        'category' => 'boxes',
        'images' => [
            'products/photo_2025-12-08_12-07-34.jpg',
            'products/photo_2025-12-08_12-07-36.jpg'
        ],
        'customizable' => true,
        'featured' => true,
        'status' => 'available'
    ],
    
    // 6. أسطوانة زهور مضيئة
    [
        'name' => 'أسطوانة زهور طبيعية مضيئة',
        'description' => 'أسطوانة زجاجية أنيقة تحتوي على باقة زهور طبيعية مجففة بألوان زهرية رقيقة مع إضاءة LED ساحرة. تحفة ديكورية مميزة تدوم طويلاً. مثالية كهدية لعيد الأم، أو للتعبير عن الحب والتقدير.',
        'price' => 22000,
        'old_price' => 28000,
        'category' => 'decorations',
        'images' => [
            'products/photo_2025-12-17_00-11-20.jpg',
            'products/photo_2025-12-17_00-11-25.jpg'
        ],
        'customizable' => false,
        'featured' => false,
        'status' => 'available'
    ],
    
    // 7. قبة طلب الزواج - Proposal Dome
    [
        'name' => 'قبة طلب الزواج المضيئة',
        'description' => 'قبة زجاجية ساحرة تحتوي على مجسم عروس وعريس يطلب يدها، مع قلوب وردية لامعة وإضاءة LED ملونة. الهدية المثالية لطلب الزواج أو للاحتفال بالذكرى السنوية. تصميم رومانسي لا يُنسى يعبر عن أجمل اللحظات.',
        'price' => 32000,
        'old_price' => 40000,
        'category' => 'decorations',
        'images' => [
            'products/photo_2025-12-17_00-11-40.jpg',
            'products/photo_2025-12-17_00-13-50.jpg'
        ],
        'customizable' => false,
        'featured' => true,
        'status' => 'available'
    ],
    
    // 8. تحفة الهلال المضيئة
    [
        'name' => 'تحفة الهلال المضيئة - I Love You',
        'description' => 'تحفة فنية رائعة على شكل هلال من الريزن الشفاف مع زهور طبيعية مجففة بداخلها، ومجسم زوجين رومانسي. قاعدة خشبية منقوش عليها "I Love You" مع إضاءة LED دافئة. الهدية المثالية للتعبير عن الحب.',
        'price' => 35000,
        'old_price' => 45000,
        'category' => 'decorations',
        'images' => [
            'products/photo_2025-12-17_00-15-19.jpg',
            'products/photo_2025-12-17_00-15-20.jpg'
        ],
        'customizable' => false,
        'featured' => true,
        'status' => 'available'
    ],
    
    // 9. بوكي شوكولاتة وورد
    [
        'name' => 'بوكيه شوكولاتة كندر مع الورد',
        'description' => 'بوكيه فاخر من شوكولاتة كندر بوينو وكندر ديليشيز مع ورود حمراء صناعية جميلة. مغلف بقماش أحمر مخملي فاخر مع بطاقة اهداء. الهدية المثالية لمحبي الشوكولاتة والحلويات. تجمع بين الحلاوة والجمال.',
        'price' => 38000,
        'old_price' => 45000,
        'category' => 'boxes',
        'images' => [
            'products/photo_2025-12-19_22-43-00.jpg',
            'products/photo_2025-12-19_22-43-09.jpg'
        ],
        'customizable' => true,
        'featured' => false,
        'status' => 'available'
    ],
    
    // 10. بوكس عيد الميلاد كريسماس
    [
        'name' => 'بوكس هدايا الكريسماس الفاخر',
        'description' => 'بوكس هدايا متكامل بأجواء كريسماس ساحرة! يشمل كرة ثلج بابا نويل، باقة ورد صغيرة، عطر، زجاجة مزخرفة، جوارب كريسماس، وبطاقات معايدة. كل ما تحتاجه للاحتفال بأعياد نهاية السنة في علبة واحدة أنيقة.',
        'price' => 55000,
        'old_price' => 70000,
        'category' => 'boxes',
        'images' => [
            'products/photo_2025-12-19_22-43-44.jpg',
            'products/photo_2025-12-19_22-43-54.jpg'
        ],
        'customizable' => true,
        'featured' => false,
        'status' => 'available'
    ],
    
    // 11. محفظة جلد مع حفر الاسم
    [
        'name' => 'محفظة جلد فاخرة مع حفر الاسم',
        'description' => 'محفظة جلد طبيعي فاخرة باللون الأسود مع حفر ليزر للاسم بالخط العربي الذهبي. تصميم عصري وأنيق مع جيوب متعددة للبطاقات والنقود. الهدية العملية المثالية للرجال. جودة عالية وخامة متينة.',
        'price' => 28000,
        'old_price' => 35000,
        'category' => 'printing',
        'images' => [
            'products/photo_2025-12-25_13-46-32.jpg'
        ],
        'customizable' => true,
        'featured' => false,
        'status' => 'available'
    ],
    
    // 12. فانوس الأم المضيء
    [
        'name' => 'فانوس الأم المضيء - أمي الغالية',
        'description' => 'فانوس خشبي على شكل بيت صغير مع رسمة أم تحتضن أطفالها. سقف لامع باللون الوردي مع إضاءة LED دافئة. منقوش عليه "أمي الغالية". الهدية المثالية لعيد الأم للتعبير عن الحب والتقدير للأم الحنونة.',
        'price' => 18000,
        'old_price' => 0,
        'category' => 'decorations',
        'images' => [
            'products/photo_2025-12-28_18-18-21.jpg'
        ],
        'customizable' => false,
        'featured' => false,
        'status' => 'available'
    ],
    
    // 13. بوكس هدايا رومانسي كامل
    [
        'name' => 'بوكس هدايا رومانسي متكامل',
        'description' => 'بوكس هدايا متكامل للمحبين يشمل: ساعة يد أنيقة، ترمس شخصي بالاسم، باقة ورد، عطر فاخر، سلسلة ذهبية، شوكولاتة، وبطاقة مع صورة الزوجين. كل ما تحتاجه للتعبير عن حبك في علبة واحدة مميزة.',
        'price' => 95000,
        'old_price' => 120000,
        'category' => 'boxes',
        'images' => [
            'products/photo_2025-12-28_18-18-39.jpg',
            'products/photo_2025-12-28_18-19-13.jpg'
        ],
        'customizable' => true,
        'featured' => true,
        'status' => 'available'
    ],
    
    // 14. كرة ثلج Love Story
    [
        'name' => 'كرة ثلج Love Story الموسيقية',
        'description' => 'كرة ثلج موسيقية ساحرة بتصميم رومانسي يظهر شاب يقدم المظلة لحبيبته تحت الثلج. قاعدة مزينة برسومات المدينة وكتابة "Love Story". تعمل بالموسيقى وتتسلل الثلج بداخلها. متوفرة بألوان متعددة: أزرق ووردي.',
        'price' => 25000,
        'old_price' => 30000,
        'category' => 'decorations',
        'images' => [
            'products/photo_2025-12-29_13-14-32.jpg',
            'products/photo_2025-12-29_13-14-33.jpg'
        ],
        'customizable' => false,
        'featured' => false,
        'status' => 'available'
    ],
    
    // 15. فانوس كيوبيد المضيء
    [
        'name' => 'فانوس كيوبيد المضيء - I Love You',
        'description' => 'فانوس عصري على شكل قوس مع ملاك كيوبيد الصغير يحمل بالونات الحب. إضاءة LED بألوان دافئة مع وردة حمراء في الأسفل. مكتوب عليه "HappyHost". تحفة رومانسية مميزة للتعبير عن الحب.',
        'price' => 22000,
        'old_price' => 0,
        'category' => 'decorations',
        'images' => [
            'products/photo_2025-12-29_13-14-33 (2).jpg',
            'products/photo_2025-12-29_13-14-34.jpg'
        ],
        'customizable' => false,
        'featured' => false,
        'status' => 'available'
    ],
    
    // 16. بوكس يونيكورن الفاخر
    [
        'name' => 'بوكس يونيكورن الفاخر',
        'description' => 'بوكس هدايا ساحر يحتوي على دمية يونيكورن ضخمة بألوان قوس قزح، ساعة يد أنيقة، محفظة جلد مع حفر الاسم، سلسلة ذهبية، وورود حمراء. التغليف الفاخر مع شريط ساتان يجعله أروع هدية للبنات ومحبي اليونيكورن.',
        'price' => 85000,
        'old_price' => 100000,
        'category' => 'boxes',
        'images' => [
            'products/photo_2026-01-13_15-28-55.jpg',
            'products/photo_2026-01-13_15-34-06.jpg',
            'products/photo_2026-01-13_15-34-14.jpg',
            'products/photo_2026-01-13_15-34-16.jpg'
        ],
        'customizable' => true,
        'featured' => true,
        'status' => 'available'
    ],
    
    // 17. ألبوم صور فاخر
    [
        'name' => 'ألبوم صور الذكريات الفاخر',
        'description' => 'ألبوم صور عالي الجودة بتصميم رومانسي مع إطار قلب ذهبي وخلفيات ورود ملونة. يتسع لـ 100 صورة بحجم 4×6. متوفر بثلاثة تصاميم: أزرق سماوي، وردي بالورد، وأصفر بالشاي. الهدية المثالية لحفظ الذكريات الجميلة.',
        'price' => 15000,
        'old_price' => 0,
        'category' => 'printing',
        'images' => [
            'products/photo_2026-01-13_15-34-39.jpg'
        ],
        'customizable' => false,
        'featured' => false,
        'status' => 'available'
    ],
    
    // 18. بوكيه ورد مع هدية
    [
        'name' => 'بوكيه ورد فاخر مع علبة هدية',
        'description' => 'باقة ورد حمراء أنيقة مغلفة بورق أخضر فاتح مع شريط ساتان أنيق، مرفقة بعلبة هدية صغيرة. التغليف الاحترافي والألوان المتناسقة تجعلها الهدية المثالية للمناسبات الرومانسية وأعياد الميلاد.',
        'price' => 25000,
        'old_price' => 0,
        'category' => 'decorations',
        'images' => [
            'products/photo_2026-01-13_15-34-51.jpg',
            'products/photo_2026-01-13_15-34-53.jpg'
        ],
        'customizable' => false,
        'featured' => false,
        'status' => 'available'
    ]
];

// Start seeding
$success = 0;
$errors = [];

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إضافة المنتجات - Surprise!</title>
    <link rel='stylesheet' href='../css/main.css'>
    <link rel='stylesheet' href='../css/admin.css'>
    <style>
        body { padding: 30px; background: #f5f5f5; font-family: 'Cairo', sans-serif; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        h1 { color: #e91e8c; text-align: center; margin-bottom: 30px; }
        .product-row { padding: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px; }
        .product-row:last-child { border-bottom: none; }
        .product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 10px; }
        .product-info { flex: 1; }
        .product-name { font-weight: bold; color: #333; }
        .product-price { color: #e91e8c; font-weight: 600; }
        .status-success { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .summary { text-align: center; padding: 20px; margin-top: 20px; background: linear-gradient(135deg, #e91e8c, #ff69b4); color: white; border-radius: 10px; }
        .btn-back { display: inline-block; margin-top: 20px; padding: 12px 30px; background: #e91e8c; color: white; text-decoration: none; border-radius: 25px; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🎁 إضافة المنتجات إلى قاعدة البيانات</h1>";

foreach ($products as $index => $product) {
    try {
        // Check if product already exists by checking if same images exist
        $existingCheck = db()->prepare("SELECT COUNT(*) FROM products WHERE name = ?");
        $existingCheck->execute([$product['name']]);
        
        if ($existingCheck->fetchColumn() > 0) {
            echo "<div class='product-row'>
                <img src='../{$product['images'][0]}' class='product-img' alt=''>
                <div class='product-info'>
                    <div class='product-name'>{$product['name']}</div>
                    <div class='product-price'>" . number_format($product['price']) . " د.ع</div>
                </div>
                <span class='status-error'>⚠️ موجود مسبقاً (تم تخطيه)</span>
            </div>";
            continue;
        }
        
        // Prepare product data
        $productData = [
            'name' => $product['name'],
            'description' => $product['description'],
            'price' => $product['price'],
            'old_price' => $product['old_price'],
            'category' => $product['category'],
            'images' => $product['images'],
            'customizable' => $product['customizable'],
            'featured' => $product['featured'],
            'status' => $product['status']
        ];
        
        // Save product
        if (saveProduct($productData)) {
            $success++;
            echo "<div class='product-row'>
                <img src='../{$product['images'][0]}' class='product-img' alt=''>
                <div class='product-info'>
                    <div class='product-name'>{$product['name']}</div>
                    <div class='product-price'>" . number_format($product['price']) . " د.ع</div>
                </div>
                <span class='status-success'>✅ تمت الإضافة بنجاح</span>
            </div>";
        } else {
            $errors[] = $product['name'];
            echo "<div class='product-row'>
                <img src='../{$product['images'][0]}' class='product-img' alt=''>
                <div class='product-info'>
                    <div class='product-name'>{$product['name']}</div>
                </div>
                <span class='status-error'>❌ فشل في الإضافة</span>
            </div>";
        }
    } catch (Exception $e) {
        $errors[] = $product['name'] . ': ' . $e->getMessage();
        echo "<div class='product-row'>
            <div class='product-info'>
                <div class='product-name'>{$product['name']}</div>
                <div style='color:red;font-size:12px;'>{$e->getMessage()}</div>
            </div>
            <span class='status-error'>❌ خطأ</span>
        </div>";
    }
}

$totalProducts = count($products);
echo "<div class='summary'>
    <h2>📊 ملخص العملية</h2>
    <p>تمت إضافة <strong>{$success}</strong> منتج من أصل <strong>{$totalProducts}</strong></p>
    " . (count($errors) > 0 ? "<p>⚠️ فشل في إضافة: " . count($errors) . " منتج</p>" : "") . "
</div>

<div style='text-align: center;'>
    <a href='products.php' class='btn-back'>🛍️ عرض المنتجات</a>
    <a href='index.php' class='btn-back' style='background: #333; margin-right: 10px;'>🏠 لوحة التحكم</a>
</div>

</div>
</body>
</html>";
?>
