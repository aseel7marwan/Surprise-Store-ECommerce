# 📚 Surprise! Store - التوثيق الشامل

> **الإصدار:** 4.8.1  
> **آخر تحديث:** 2026-01-21  
> **الموقع:** https://surprise-iq.com

---

# 📋 فهرس المحتويات

1. [نظرة عامة](#-نظرة-عامة)
2. [نظام التغليف](#-نظام-التغليف)
3. [نظام الموظفين](#-نظام-الموظفين)
4. [خيارات المنتجات](#-خيارات-المنتجات)
5. [قاعدة البيانات](#-قاعدة-البيانات)
6. [الأمان والتقنية](#-الأمان-والتقنية)
7. [SEO](#-seo)
8. [النشر والتحديث](#-النشر-والتحديث)
9. [استكشاف الأخطاء](#-استكشاف-الأخطاء)

---

# 📊 نظرة عامة

## الحالة الفنية

| البند | الحالة | النسبة |
|-------|--------|--------|
| **التوافق** | ✅ PHP 7.4+ / MySQL 5.7+ | 100% |
| **الأمان** | ✅ CSRF, Rate Limiting, bcrypt | 95% |
| **التجاوب** | ✅ Mobile-First RTL | 100% |
| **الأداء** | ✅ Cache Busting, Gzip, Lazy Loading | 95% |
| **SEO** | ✅ Meta Tags, Schema.org, Sitemap | 100% |

## هيكل المشروع

```
surprise/
├── admin/              # لوحة التحكم
│   ├── staff.php       # 👥 إدارة الموظفين (جديد)
│   └── includes/       # sidebar.php
├── api/                # واجهات برمجية
├── includes/           # الملفات الأساسية
│   ├── config.php      # إعدادات قاعدة البيانات
│   ├── functions.php   # جميع الدوال
│   ├── security.php    # دوال الأمان + الموظفين
│   ├── seo.php         # دوال SEO
│   └── version.php     # إدارة الإصدار
├── css/                # ملفات التصميم
├── js/                 # سكريبتات JavaScript
├── data/               # بيانات JSON
├── images/             # الصور
├── docs/               # التوثيق
└── [صفحات الموقع]      # index, products, cart, etc.
```

---

# 🎁 نظام التغليف (Packaging System)

## نظرة عامة
نظام التغليف يتيح للمدير تفعيل خيار التغليف لكل منتج على حدة، مع تحديد سعر ووصف مخصص.

## كيفية العمل

### في لوحة التحكم (إدارة المنتجات)
1. افتح صفحة تعديل المنتج
2. فعّل "تفعيل التغليف" (checkbox)
3. أدخل **سعر التغليف** بالدينار العراقي
4. اختيارياً: أضف **وصف التغليف** (مثل: "تغليف فاخر مع شريطة")

### في صفحة المنتج (الزبون)
- يظهر خيار "إضافة تغليف" مع السعر
- الزبون يختار بالضغط على checkbox
- يتم حفظ الاختيار مع المنتج في السلة

### في السلة ومراجعة الطلب
- يظهر سعر التغليف مع كل منتج
- يظهر **إجمالي التغليف** منفصلاً في الملخص

### في لوحة التحكم (تفاصيل الطلب)
- يظهر "🎁 تغليف" بجانب كل منتج اختار الزبون التغليف له
- يظهر إجمالي التغليف في ملخص الطلب

### في إشعار Telegram
- يظهر "🎁 تغليف: نعم" مع السعر لكل منتج
- يظهر إجمالي التغليف في ملخص الطلب

## الحقول في قاعدة البيانات

| الجدول | الحقل | الوصف |
|--------|-------|-------|
| `products` | `packaging_enabled` | هل التغليف متاح (0/1) |
| `products` | `packaging_price` | سعر التغليف بالدينار |
| `products` | `packaging_description` | وصف التغليف |
| `order_items` | `packaging_selected` | الزبون اختار التغليف (0/1) |
| `order_items` | `packaging_price` | سعر التغليف وقت الطلب |
| `order_items` | `packaging_description` | وصف التغليف |
| `orders` | `packaging_total` | إجمالي التغليف للطلب |

---

# 👥 نظام الموظفين

## نظرة عامة
نظام إدارة الموظفين يتيح للمدير إنشاء حسابات لموظفين مع صلاحيات محددة. الموظفون يمكنهم الوصول فقط للصفحات المسموح لهم بها.

## الملفات المتعلقة
| الملف | الوظيفة |
|-------|---------|
| `admin/staff.php` | صفحة إدارة الموظفين (CRUD) |
| `includes/security.php` | دوال الموظفين والصلاحيات |
| `admin/includes/sidebar.php` | القائمة الجانبية مع الصلاحيات |
| `admin/login.php` | تسجيل دخول المدير والموظفين |
| `database_unified.sql` | جدول staff (مدمج) |

## الدوال المتاحة

### التحقق من الهوية
```php
isAdmin()        // هل المستخدم مدير؟
isStaff()        // هل المستخدم موظف؟
hasPermission($permission)  // هل لديه صلاحية معينة؟
checkPagePermission($permission)  // حماية الصفحة
```

### إدارة الموظفين
```php
getAllStaff()              // جلب جميع الموظفين
getStaffById($id)          // جلب موظف بالـ ID
getStaffByUsername($username)  // جلب موظف باسم المستخدم
saveStaff($data)           // حفظ/تحديث موظف
deleteStaff($id)           // حذف موظف
isUsernameUnique($username, $excludeId)  // التحقق من اسم فريد
```

### الجلسات
```php
createStaffSession($staffId, $staffData)  // إنشاء جلسة موظف
createAdminSession()       // إنشاء جلسة مدير
destroyAdminSession()      // تدمير الجلسة
```

### البيانات المساعدة
```php
getAvailablePermissions()  // قائمة الصلاحيات المتاحة
getIraqGovernorates()      // قائمة محافظات العراق
```

## الصلاحيات المتاحة

| المفتاح | الوصف | متاح للموظف |
|---------|-------|------------|
| `dashboard` | الرئيسية | ✅ |
| `reports` | التقارير | ✅ |
| `sales` | المبيعات | ✅ |
| `orders` | الطلبات | ✅ |
| `products` | المنتجات | ✅ |
| `categories` | الأقسام | ✅ |
| `coupons` | الكوبونات | ✅ |
| `reviews` | التقييمات | ✅ |
| `banners` | البانرات | ✅ |
| `backup` | النسخ الاحتياطي | ✅ |
| `settings` | الإعدادات | ❌ (مدير فقط) |
| `staff` | إدارة الموظفين | ❌ (مدير فقط) |

## استخدام الصلاحيات في الصفحات

```php
// في بداية كل صفحة إدارية
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

initSecureSession();

// حماية الصفحة - يجب أن يكون للمستخدم صلاحية 'orders'
checkPagePermission('orders');

// الآن الموظف أو المدير الذي لديه الصلاحية يمكنه الوصول
```

## هيكل جدول staff

```sql
CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,  -- مشفر بـ bcrypt
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    job_title VARCHAR(100),
    governorate VARCHAR(100),
    district VARCHAR(100),
    neighborhood VARCHAR(100),
    address TEXT,
    permissions JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## مثال على permissions JSON

```json
{
    "dashboard": true,
    "reports": true,
    "sales": true,
    "orders": true,
    "products": true,
    "categories": false,
    "coupons": false,
    "reviews": false,
    "banners": false,
    "backup": false
}
```

---

# 🎨 خيارات المنتجات

## أنواع الخيارات

| الخيار | الوصف |
|--------|-------|
| **sizes** | أحجام ثابتة (XXS - 6XL) |
| **colors** | ألوان مخصصة لكل منتج |
| **ages** | فئات عمرية |
| **customization** | تخصيص (نص + صور) |

## هيكل options JSON في products

```json
{
    "sizes": {
        "enabled": true,
        "required": true,
        "available_sizes": ["S", "M", "L", "XL", "XXL (2XL)"]
    },
    "colors": {
        "enabled": true,
        "values": ["أحمر", "أزرق", "أسود", "أبيض"],
        "required": true
    },
    "ages": {
        "enabled": false,
        "values": [],
        "required": false
    },
    "customization": {
        "enabled": true,
        "required": false,
        "text": {
            "enabled": true,
            "required": false,
            "label": "الاسم للطباعة",
            "placeholder": "أدخل الاسم...",
            "max_length": 50
        },
        "images": {
            "enabled": true,
            "required": false,
            "min_images": 0,
            "max_images": 5,
            "max_size_mb": 5
        }
    }
}
```

## هيكل selected_options JSON في order_items

```json
{
    "size": "L",
    "color": "أحمر",
    "age": null,
    "custom_text": "أحمد"
}
```

---

# 🗂️ قاعدة البيانات

## الجداول الرئيسية (14 جدول)

| # | الجدول | الوصف |
|---|--------|-------|
| 1 | products | المنتجات (مع options JSON) |
| 2 | categories | الأقسام |
| 3 | reviews | التقييمات |
| 4 | orders | الطلبات |
| 5 | order_items | عناصر الطلب (مع selected_options) |
| 6 | order_tracking | تتبع الشحن |
| 7 | coupons | الكوبونات |
| 8 | banners | البانرات |
| 9 | settings | الإعدادات |
| 10 | admins | المدراء |
| 11 | customer_uploads | رفعات الزبائن |
| 12 | sales_log | سجل المبيعات |
| 13 | security_log | سجل الأمان |
| 14 | **staff** | الموظفين والصلاحيات ⭐ NEW |

## حالات الطلب
| الحالة | الوصف | تأثير على المبيعات |
|--------|-------|-------------------|
| `pending` | ⏳ قيد الانتظار | ❌ لا يُحسب |
| `confirmed` | ✓ تم التأكيد | ✅ يُحسب |
| `processing` | 🔧 قيد التجهيز | ✅ يُحسب |
| `shipped` | 🚚 تم الشحن | ✅ يُحسب |
| `delivered` | ✅ تم التوصيل | ✅ يُحسب |
| `cancelled` | ✗ ملغي | ❌ لا يُحسب |

## استعلامات مفيدة

```sql
-- إجمالي المبيعات
SELECT SUM(oi.quantity * oi.price) as total_revenue
FROM order_items oi
INNER JOIN orders o ON oi.order_id = o.id
WHERE o.status IN ('confirmed', 'processing', 'shipped', 'delivered');

-- مبيعات كل منتج
SELECT oi.product_name, SUM(oi.quantity) as items_sold
FROM order_items oi
INNER JOIN orders o ON oi.order_id = o.id
WHERE o.status IN ('confirmed', 'processing', 'shipped', 'delivered')
GROUP BY oi.product_id ORDER BY items_sold DESC;

-- الموظفين النشطين
SELECT id, username, first_name, last_name, job_title, last_login
FROM staff WHERE is_active = 1;
```

---

# 🔐 الأمان والتقنية

## ميزات الأمان المُطبقة

| الميزة | الملف | الوصف |
|--------|-------|-------|
| CSRF Protection | `security.php` | رموز فريدة لكل جلسة |
| SQL Injection | `functions.php` | Prepared Statements |
| XSS Protection | `functions.php` | `sanitize()` + `htmlspecialchars()` |
| Rate Limiting | `security.php` | 5 محاولات / 15 دقيقة |
| Password Hash | bcrypt | `password_hash()` |
| Security Headers | `config.php` | X-Frame-Options, CSP |
| Staff Permissions | `security.php` | صلاحيات محددة لكل موظف |

## نظام Cache Busting

### الملف المركزي
```php
// includes/version.php
define('SITE_VERSION', '4.8.1');
```

### الدوال المتاحة
| الدالة | الاستخدام | النتيجة |
|--------|----------|---------|
| `v($path)` | صفحات Frontend | `css/main.css?v=2.5.0` |
| `av($path)` | صفحات Admin | `../css/main.css?v=2.5.0` |
| `getVersion()` | عرض الإصدار | `2.5.0` |

## API Endpoints

| Endpoint | Method | الوظيفة |
|----------|--------|---------|
| `/api/submit-order.php` | POST | إنشاء طلب جديد |
| `/api/validate-coupon.php` | POST | التحقق من كوبون |
| `/api/upload-image.php` | POST | رفع صورة |
| `/api/admin-search.php` | GET | بحث المنتجات/الطلبات |
| `/api/stats.php` | GET | إحصائيات التقارير |

---

# 🔍 SEO

## الحالة النهائية

| العنصر | الحالة |
|--------|--------|
| اسم الموقع `Surprise!` | ✅ صحيح |
| robots.txt | ✅ موجود |
| sitemap.xml | ✅ ديناميكي |
| Meta Tags | ✅ كاملة |
| Schema.org | ✅ مُفعّل |
| الكلمات المفتاحية | ✅ 100+ كلمة |
| Open Graph | ✅ مُفعّل |

---

# 🚀 النشر والتحديث

## طرق النشر

### سكريبت PowerShell (مُوصى به)
```powershell
.\deploy.ps1           # رفع كل الملفات
.\deploy.ps1 css       # رفع مجلد CSS فقط
.\deploy.ps1 index.php # رفع ملف واحد
```

### الملفات المُستثناة تلقائياً
- `database_unified.sql` - قاعدة البيانات
- `CREDENTIALS.md` - بيانات الدخول
- `*.log` - سجلات الأخطاء
- `backups/` - النسخ الاحتياطية

## قائمة التحقق للنشر

### قبل النشر
- [ ] تحديث `SITE_VERSION` في `version.php`
- [ ] تحديث بيانات DB في `config.php` (للاستضافة)
- [ ] استيراد `database_unified.sql` في phpMyAdmin
- [ ] تغيير كلمة مرور Admin

### بعد النشر
- [ ] اختبار سلة التسوق
- [ ] اختبار إتمام الطلب
- [ ] اختبار لوحة التحكم
- [ ] اختبار تسجيل دخول موظف
- [ ] فحص الـ Console للأخطاء

---

# 🆘 استكشاف الأخطاء

## مشاكل شائعة

### ❌ صفحة بيضاء أو خطأ 500
**الحل:**
1. تأكد من تشغيل Apache و MySQL
2. افحص `includes/config.php`
3. تحقق من سجل الأخطاء

### ❌ الصور لا تُرفع
**الحل:**
1. صلاحيات المجلدات: `chmod 755`
2. الحد الأقصى: 5MB
3. الصيغ المسموحة: JPG, PNG, GIF, WEBP

### ❌ التقارير تظهر أصفار
**الحل:**
1. تأكد من وجود طلبات بحالة "تم التأكيد" أو أعلى
2. تأكد من فلتر السنة/الشهر الصحيح

### ❌ الموظف لا يستطيع الدخول
**الحل:**
1. تأكد من أن حساب الموظف "نشط" (is_active = 1)
2. تأكد من أن جدول `staff` موجود في قاعدة البيانات
3. إذا لم يكن موجوداً، أعد استيراد `database_unified.sql`

### ❌ الموظف لا يرى صفحة معينة
**الحل:**
1. تأكد من أن الصلاحية مُفعّلة في حساب الموظف
2. عدّل الموظف من "إدارة الموظفين" وفعّل الصلاحية المطلوبة

### ❌ التصميم لا يتحدث
**الحل:**
1. غيّر رقم الإصدار في `version.php`
2. أو امسح الكاش: `Ctrl+Shift+R`

---

# ⚙️ إعدادات مهمة

## ملفات الإعداد

| الملف | الوظيفة |
|-------|---------|
| `data/categories.json` | الأقسام |
| `data/payment_methods.json` | طرق الدفع |
| `data/settings.json` | إعدادات عامة |

## إعدادات قاعدة البيانات

### محلي (XAMPP)
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'surprise_store');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### الاستضافة
```php
define('DB_HOST', 'localhost');
define('DB_NAME', '(من cPanel)');
define('DB_USER', '(من cPanel)');
define('DB_PASS', '(من cPanel)');
define('SITE_URL', 'https://surprise-iq.com');
```

---

## 📌 صيغة الإصدارات

نستخدم [Semantic Versioning](https://semver.org/):
- **MAJOR** (X.0.0) - تغييرات كبيرة غير متوافقة
- **MINOR** (0.X.0) - ميزات جديدة متوافقة
- **PATCH** (0.0.X) - إصلاحات أخطاء

---

© 2026 Surprise! Store - جميع الحقوق محفوظة
