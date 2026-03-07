# 📋 سجل التغييرات - Surprise! Store

جميع التغييرات الملحوظة في هذا المشروع موثقة هنا.

## [5.8.0] - 2026-02-11 - ✨ Premium UI Enhancement

### 🎨 تحسينات مرئية شاملة (CSS فقط)

- **البحث التلقائي**: ظلال أنعم متعددة الطبقات، حركات أسلس، دعم سطرين للنصوص العربية، حدود مميزة عند التمرير
- **صفحة السلة**: حاوية محسنة بظلال ناعمة، تأثيرات hover على عناصر السلة، أزرار كمية مع تأثير scale، ملخص متدرج premium
- **زر الحذف**: خلفية شفافة مع حدود خطأ، تأثير hover محسن
- **النماذج**: حلقة تركيز أنعم، سهم مخصص لقوائم select، تأثيرات upload area متدرجة
- **الحالة الفارغة**: خلفية متدرجة وأيقونة مع ظل
- **التنبيهات**: تدرجات أنعم بألوان محدثة (نجاح/خطأ/معلومات)
- **مراجعة الطلب**: أقسام بتأثير hover، عناصر منتجات محسنة، ملخص بصري أفضل
- **الفوتر**: خط تزييني أعرض، رسوم متحركة underline على الروابط
- **طرق الدفع**: إصلاح لون علامة الاختيار (أسود → أبيض)، ظلال محسنة
- **اتساق عام**: انتقالات cubic-bezier سلسة، استخدام متغيرات CSS موحدة

---

## [5.7.0] - 2026-02-11 - 💾 Backup UI Removal

### 🛡️ لوحة التحكم - إخفاء النسخ الاحتياطي

- **إخفاء رابط النسخ الاحتياطي** من القائمة الجانبية (Sidebar)
- **تعطيل الوصول المباشر** لصفحة `backup.php` وتحويلها للرئيسية
- **الاحتفاظ بالأكواد** - جميع وظائف النسخ الاحتياطي موجودة في الملفات ولكنها غير نشطة في الواجهة كإجراء احتياطي

---

## [5.3.0] - 2026-01-22 - 🚀 Performance & PWA Enhancements

### 📷 6. نظام تحسين الصور (Image Optimization)

- **ضغط تلقائي** للصور المرفوعة (جودة 85%)
- **تصغير تلقائي** للصور الكبيرة (max 1920px)
- **Thumbnails** تلقائية (400x400) لقوائم المنتجات
- **تحويل WebP** - توفير 30-50% من الحجم
- دالة `processProductImage()` للمعالجة الشاملة
- دالة `getOptimizedImagePath()` لاختيار الصورة المناسبة
- **ملف جديد**: `includes/images.php`

### 📝 7. نظام Logging الشامل

- **سجلات متعددة**: orders, api, errors, stock, activity
- **تدوير تلقائي** عند تجاوز 10MB
- **أرشفة تلقائية** للسجلات القديمة (30 يوم)
- وظائف: `logOrder()`, `logApi()`, `logError()`, `logStock()`
- **حماية** مجلد السجلات بـ .htaccess
- **ملف جديد**: `includes/logging.php`

### 📱 8. PWA كامل مع Offline Support

- **صفحة Offline** جديدة (`offline.html`) بتصميم أنيق
- زر **إعادة المحاولة** مع كشف تلقائي للاتصال
- **Service Worker v3** محدث:
  - Stale-while-revalidate للأصول الثابتة
  - Network-first للصفحات الديناميكية
  - Offline fallback للصفحات
  - دعم **Push Notifications** (جاهز)
- **manifest.json** محدث:
  - **Shortcuts** للوصول السريع (منتجات، سلة، تتبع)
  - أحجام أيقونات متعددة
  - Share Target للمشاركة

### 🔍 9. تحسينات SEO

- **robots.txt** شامل ومحدث:
  - حظر Bots ضارة (AhrefsBot, SemrushBot, etc)
  - قواعد خاصة لـ Googlebot و Bingbot
  - Crawl-delay للحماية
  - Clean URLs في Allow rules
- تحسين **CSP Headers**

### 📁 الملفات الجديدة:

- `includes/logging.php` - نظام Logging
- `includes/images.php` - تحسين الصور (مُحدّث)
- `offline.html` - صفحة Offline
- `sw.js` - Service Worker v3 (مُحدّث)

### 📁 الملفات المحدثة:

- `manifest.json` - PWA shortcuts
- `robots.txt` - قواعد SEO
- `api/upload-image.php` - ضغط تلقائي
- `includes/version.php` - الإصدار 5.3.0

---

## [5.2.0] - 2026-01-22 - 🔗 Clean URLs & Header Unification

### 🔗 Clean URLs - إزالة امتداد .php من جميع الروابط

#### 1. تحديث .htaccess

- إضافة **301 Redirects** من `/page.php` إلى `/page` للـ SEO
- Redirect تلقائي من `/index.php` إلى `/`
- استثناء `api/` و `admin/` من قواعد Redirect
- استثناء الملفات الثابتة (CSS, JS, Images) من قواعد Rewrite

#### 2. تحديث جميع الروابط في ملفات PHP

- تحويل `index.php` → `/`
- تحويل `products.php` → `/products`
- تحويل `product.php?id=X` → `/product?id=X`
- تحويل `about.php` → `/about`
- تحويل `cart.php` → `/cart`
- تحويل `wishlist.php` → `/wishlist`
- تحويل `track.php` → `/track`
- تحويل `privacy.php` → `/privacy`
- تحويل `terms.php` → `/terms`

#### 3. تحديث JavaScript

- تحديث روابط Autocomplete في `app.js`
- تحديث روابط إشعارات السلة
- تحديث فحص صفحة المنتج

#### 4. صفحات Admin و API

- **بدون تغيير** - تبقى تعمل بـ `.php`
- الـ 301 Redirects لا تؤثر عليها

### 🎯 توحيد الهيدر على الموبايل

#### إصلاح مشكلة صفحة المنتج

- إضافة عنصر البحث `header-search-wrapper` الناقص إلى `product.php`
- الآن جميع الصفحات لها نفس هيكل الهيدر
- السلة والهامبرجر بنفس المكان في كل الصفحات

### 📁 الملفات المعدلة:

- `.htaccess` - قواعد Clean URLs و 301 Redirects
- `index.php`, `products.php`, `product.php`, `about.php`, `cart.php`
- `wishlist.php`, `track.php`, `privacy.php`, `terms.php`
- `js/app.js` - روابط JavaScript
- `includes/version.php` - الإصدار 5.2.0

### ✅ ملاحظات SEO:

- 301 Redirects دائمة للحفاظ على ترتيب Google
- لا توجد صفحات مكررة
- Sitemap.xml يستخدم Clean URLs بالفعل

---

## [5.0.0] - 2026-01-21 - 🔒 Security Audit & Comprehensive Fixes

### 🔒 تدقيق أمان شامل

#### 1. حماية الملفات الحساسة

- إضافة قواعد `.htaccess` لحظر: `install.php`, `deploy*.bat/txt/ps1`, `database*.sql`, `.env`, `.git`
- نقل الملفات الحساسة إلى مجلد `.archive` محمي:
  - `database_unified.sql`
  - `deploy-winscp.txt` (كان يحتوي كلمة مرور FTP!)
  - `deploy-winscp.bat`
  - `deploy.bat`

#### 2. Security Headers (CSP, Referrer-Policy, Permissions-Policy)

- `Content-Security-Policy` مع سماحيات محددة للموارد الخارجية
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` لحظر الوصول للموقع الجغرافي/الميكروفون/الكاميرا
- `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection` محدثة

#### 3. إصلاح أخطاء Console

- إصلاح `manifest.json 403` - استثناء من حظر JSON
- إصلاح `logo.png 404` → تغيير إلى `logo.jpg`
- إصلاح `default.png 404` - إنشاء صورة افتراضية للمنتجات
- إصلاح `apple-mobile-web-app-capable` deprecated → `mobile-web-app-capable`
- تحديث Service Worker من `v1` إلى `v2` مع error handling

#### 4. تحسين Mobile Sidebar

- تصميم احترافي جديد مع Staggered Animation
- إخفاء البحث والسلة عند فتح القائمة الجانبية
- Premium Blur Backdrop للـ Overlay
- Cubic-bezier transitions للانتقالات السلسة

#### 5. تحسينات Favicon/SEO

- تحديث جميع ملفات PHP بـ favicon links صحيحة (48x48, 32x32, apple-touch-icon)
- تحديث `robots.txt` للسماح بفهرسة favicon

### 📁 الملفات المعدلة:

- `.htaccess` - Security rules + headers
- `sw.js` - Error handling + logo.jpg
- `index.php`, `product.php` - meta tag fix
- `css/main.css` - Mobile sidebar redesign
- `js/app.js` - Menu-open class for body

### 📁 الملفات المنقولة إلى .archive:

- `database_unified.sql`
- `deploy-winscp.txt`
- `deploy-winscp.bat`
- `deploy.bat`

### ⚠️ ملاحظات أمنية مهمة:

- **يجب تغيير Telegram Bot Token** (مكشوف سابقاً في config.php)
- **يجب تغيير كلمة مرور قاعدة البيانات الإنتاجية** (مكشوفة سابقاً)
- **يجب تغيير كلمة مرور FTP** (كانت في deploy-winscp.txt)

---

## [4.8.1] - 2026-01-21 - 🔧 Area Combobox Fix

### 🔧 إصلاح حقل المنطقة/الحي

تم تحويل الحقل من Input عادي إلى Combobox حقيقي (قائمة منسدلة + بحث).

### ✅ التحسينات:

#### 1. Combobox بدلاً من Input

- الحقل الآن يشبه Select عادي مع سهم ▼
- عند النقر تفتح القائمة المنسدلة مع جميع الخيارات
- **ممنوع الكتابة الحرة** - يجب اختيار من القائمة فقط

#### 2. بحث داخل القائمة

- حقل بحث في أعلى القائمة المنسدلة
- البحث يفلتر الخيارات فوراً
- Enter يختار أول نتيجة مطابقة

#### 3. تجربة مستخدم محسنة

- قبل اختيار المحافظة: الحقل معطل "اختر المحافظة أولاً..."
- بعد الاختيار: يصبح نشط مع placeholder "-- اختر المنطقة / الحي --"
- التنقل بلوحة المفاتيح (↑↓ Enter Escape)
- تصميم متوافق مع الموبايل

#### 4. Validation صارم

- ممنوع إرسال الطلب بدون اختيار المنطقة/الحي
- رسالة خطأ واضحة: "يرجى اختيار المنطقة / الحي من القائمة"

### 🛠️ الملفات المعدلة:

- `cart.php` - تحويل من input إلى combobox
- `js/app.js` - منطق initAreaCombobox الجديد
- `js/cart.js` - validation للحقل المدمج

---

## [4.8.0] - 2026-01-21 - 🗺️ Combined Area Field

### 🗺️ دمج حقل المنطقة/الحي في حقل واحد

تم دمج حقلي "المنطقة/القضاء" و"الحي/الناحية" في حقل واحد ذكي قابل للبحث.

### ✨ الميزات الجديدة:

#### 1. حقل مدمج واحد بدلاً من اثنين

- الحقل الجديد يعرض الخيارات بصيغة: `المنطقة — الحي`
- مثال: `الكرخ — حي العامل` أو `الرصافة — زيونة`
- يظهر تلقائياً عند اختيار المحافظة

#### 2. بحث ذكي داخل الحقل

- بحث فوري (Live Search) أثناء الكتابة
- دعم كامل للغة العربية مع تطبيع النص
- تصفية النتائج بناءً على المنطقة أو الحي

#### 3. تجربة مستخدم محسنة (UX)

- placeholder واضح: "اختر المحافظة أولاً..." ← "ابحث أو اختر المنطقة / الحي..."
- التنقل بلوحة المفاتيح (↑↓ Enter Escape)
- إغلاق تلقائي عند النقر خارج الحقل
- دعم الموبايل بشكل كامل

#### 4. التوافق مع جميع الأماكن

- ✅ صفحة السلة (cart.php)
- ✅ مراجعة الطلب (Review Modal)
- ✅ لوحة التحكم - تفاصيل الطلب
- ✅ إشعارات Telegram
- ✅ الطلبات القديمة تعمل بدون مشاكل

### 🛠️ الملفات المعدلة:

- `cart.php` - استبدال الحقلين بحقل واحد + CSS
- `js/app.js` - منطق البحث والاختيار
- `js/cart.js` - التعامل مع الحقل الجديد في الإرسال
- `api/submit-order.php` - معالجة customer_area
- `includes/functions.php` - تحديث saveOrder و notifyNewOrder
- `includes/version.php` - الإصدار 4.8.0

---

## [4.7.0] - 2026-01-21 - 🔒 Comprehensive Security Audit

### 🔒 جرد أمني شامل (Security Audit)

تم إجراء تدقيق شامل للمشروع بهدف التحقق من الاستقرار والأمان واتساق البيانات.

### ✅ نتائج الفحص - جميعها إيجابية:

#### 1. فحص بناء الجملة (Syntax Check)

- ✅ جميع ملفات PHP خالية من أخطاء بناء الجملة

#### 2. حماية من هجمات XSS

- ✅ استخدام `htmlspecialchars()` في جميع مخرجات المستخدم
- ✅ لا يوجد إخراج مباشر لـ `$_GET`, `$_POST` بدون تعقيم

#### 3. حماية CSRF

- ✅ جميع endpoints الحساسة (DELETE, POST) تتحقق من CSRF token
- ✅ `api/delete.php` محمي بالكامل

#### 4. حماية SQL Injection

- ✅ استخدام Prepared Statements في جميع استعلامات DB

#### 5. حماية صفحات Admin

- ✅ جميع صفحات `admin/` تستدعي `validateAdminSession()`
- ✅ الصفحات الحساسة (staff, settings, backup) تتحقق من `isAdmin()`

#### 6. Rate Limiting

- ✅ Login: 5 محاولات / 15 دقيقة
- ✅ API: 60 طلب / دقيقة
- ✅ Orders: 10 طلبات / ساعة
- ✅ Uploads: 20 رفع / ساعة

#### 7. أمان الملفات المرفوعة

- ✅ التحقق من Extension + MIME Type + Size
- ✅ أسماء ملفات آمنة (uniqid + timestamp)

#### 8. أمان الجلسات

- ✅ HttpOnly, Secure, SameSite=Lax
- ✅ session_regenerate_id() عند تسجيل الدخول

#### 9. Telegram Notifications

- ✅ Fail-safe: لا يوقف الطلب عند فشل الإرسال

#### 10. Backup System

- ✅ يعمل بشكل صحيح مع fail-safe error handling
- ✅ حماية الوصول عبر .htaccess

#### 11. Security Log

- ✅ يسجل الدخول/الخروج/الحظر
- ✅ Force logout يعمل فوراً

#### 12. منع NaN في السلة

- ✅ `cart.js` يستخدم `parseFloat`, `parseInt`, `isNaN` بشكل صحيح

### 📚 تحديث التوثيق

- **تحديث رقم الإصدار** في جميع الملفات إلى 4.7.0
- **الملفات المحدثة**:
  - `includes/version.php`
  - `README.md`
  - `docs/DOCS.md`
  - `docs/CREDENTIALS.md`
  - `docs/CHANGELOG.md`

---

## [4.6.1] - 2026-01-21 - 📚 Documentation Sync

### 📚 تحديث التوثيق

- **مزامنة رقم الإصدار** - تحديث جميع ملفات التوثيق لتتوافق مع `version.php` (4.6.1)
- **تحديث التاريخ** - 2026-01-21
- **الملفات المحدثة**:
  - `README.md` - الإصدار والتاريخ
  - `docs/DOCS.md` - الإصدار والتاريخ ومراجع الكود
  - `docs/CREDENTIALS.md` - مرجع الإصدار والتاريخ
  - `docs/CHANGELOG.md` - إضافة هذا السجل

---

## [3.8.0] - 2026-01-20 - 🛡️ Security Center

### 🛡️ مركز الأمان الجديد (Security Log)

صفحة جديدة في لوحة التحكم للمدير الرئيسي فقط:

- **سجل تسجيل الدخول الكامل**:
  - اسم المستخدم والدور (مدير/موظف)
  - الوقت والتاريخ
  - نوع الجهاز (📱هاتف/💻كمبيوتر/🧾غير معروف)
  - النظام والمتصفح
  - الدولة والمدينة (عبر IP geolocation)
  - عنوان IP

- **تنبيهات ذكية**:
  - ⚠️ جهاز جديد للمستخدم
  - 🌍 موقع جديد (دولة/مدينة)
  - إشعارات Telegram للتنبيهات الأمنية

- **إدارة الجلسات**:
  - 🚪 تسجيل خروج جلسة نشطة
  - ⛔ حظر جهاز (IP + Device Fingerprint)
  - ✅ إلغاء حظر جهاز
  - 🗑️ حذف سجل (مع إنهاء الجلسة)

- **فلاتر وبحث**:
  - بحث: اسم، IP، مدينة، دولة
  - تصفية: حالة، نوع جهاز، فترة، أجهزة جديدة

### 🗄️ جداول جديدة

```sql
-- login_sessions: سجل جميع تسجيلات الدخول
-- blocked_devices: الأجهزة المحظورة
-- admin_actions_log: سجل إجراءات الإدارة
```

### 📚 الملفات الجديدة/المحدثة

- `admin/security-log.php` - صفحة مركز الأمان (جديد)
- `includes/security.php` - دوال Login Sessions (~700 سطر جديد)
- `admin/login.php` - تسجيل الجلسات + فحص الحظر
- `admin/includes/sidebar.php` - إضافة رابط سجل الأمان
- `database_unified.sql` - 3 جداول جديدة

---

## [3.7.0] - 2026-01-20 - 🔐 Security Audit

### 🔐 تحديثات أمنية شاملة

- **إصلاح تسريب معلومات DB** - إخفاء أخطاء قاعدة البيانات من المستخدمين
- **حماية Telegram Token** - نقل البيانات الحساسة إلى إعدادات قاعدة البيانات
- **Rate Limiting محسّن** - حماية APIs من الإغراق (upload, submit-order)
- **التحقق من الأسعار Server-side** - إعادة حساب الأسعار على السيرفر لمنع التلاعب
- **إصلاح XSS** - تعقيم جميع المخرجات باستخدام htmlspecialchars

### 🔑 نظام "تذكرني" الآمن (Remember Me)

- **جلسة طويلة المدى** - 30 يوم بدلاً من 24 ساعة
- **توكنات آمنة** - Selector/Validator pattern مع تخزين hash فقط
- **تدوير التوكنات** - توكن جديد مع كل استخدام
- **إبطال تلقائي** - عند تغيير كلمة المرور أو تعطيل الحساب
- **حماية من السرقة** - اكتشاف محاولات سرقة التوكن

### 🗄️ جدول جديد

```sql
CREATE TABLE remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin', 'staff') NOT NULL,
    staff_id INT NULL,
    selector VARCHAR(24) NOT NULL UNIQUE,
    hashed_validator VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 📚 الملفات المحدثة

- `includes/security.php` - نظام Remember Me + تحديث الجلسات
- `includes/config.php` - جلسة 30 يوم + إصلاح تسريب DB
- `api/submit-order.php` - Rate Limiting + Server-side validation
- `api/upload-image.php` - Rate Limiting
- `admin/login.php` - إصلاح XSS
- `database_unified.sql` - جدول remember_tokens

---

## [3.6.4] - 2026-01-20

### 🎁 نظام التغليف المتقدم (Packaging System)

- **تغليف لكل منتج** - إمكانية تفعيل/تعطيل التغليف لكل منتج على حدة
- **سعر تغليف مخصص** - تحديد سعر التغليف بالدينار العراقي لكل منتج
- **وصف التغليف** - إضافة وصف اختياري لنوع التغليف
- **عرض التغليف للزبون** في صفحة المنتج (checkbox اختياري)
- **حفظ اختيار التغليف** مع كل منتج في السلة
- **إجمالي التغليف** يظهر منفصلاً في ملخص الطلب
- **عرض التغليف** في:
  - مودال مراجعة الطلب
  - إشعار Telegram بشكل واضح
  - لوحة التحكم (تفاصيل الطلب)

### 🎨 نظام الحقول الإضافية الموحد (Extra Fields)

- **دمج جميع الخيارات** (ألوان، أحجام، أعمار، نصوص، صور) في نظام موحد
- **خيارات مرنة** مع إمكانية تفعيل/إلزامية كل حقل
- **ربط الطباعة بالصور** - عند تفعيل خيار الطباعة، يظهر حقل رفع صور تلقائياً
- **عرض الخيارات** بشكل احترافي في صفحة المنتج

### 🔧 إصلاحات فنية مهمة

- **إصلاح مشكلة NaN** في حسابات السلة (تصادم المتغيرات مع IDs)
- **فحوصات أمان رقمية** (`isNaN`, `parseFloat`) لجميع الحسابات
- **إصلاح مسارات** `require_once` في `security-check.php`

### 📞 نظام التواصل المحسّن

- **حفظ طريقة التواصل** مع كل طلب (Instagram, WhatsApp, Telegram)
- **عرض أيقونات التواصل** في قائمة الطلبات ولوحة التحكم
- **تضمين بيانات التواصل** في إشعار Telegram

### 📚 ملفات محدثة

- `product.php` - عرض خيار التغليف + الحقول الإضافية
- `js/cart.js` - حسابات التغليف + إصلاح NaN
- `api/submit-order.php` - حفظ بيانات التغليف والتواصل
- `admin/products.php` - إعدادات التغليف والحقول الإضافية
- `admin/orders.php` - عرض التغليف والتواصل
- `database_unified.sql` - حقول التغليف الجديدة (v3.6.4)
- `admin/security-check.php` - إصلاح مسارات require

### 🗄️ Migration للقاعدة الحالية

```sql
-- Packaging للمنتجات (v3.6)
ALTER TABLE products ADD COLUMN packaging_enabled TINYINT(1) DEFAULT 0;
ALTER TABLE products ADD COLUMN packaging_price INT DEFAULT 0;
ALTER TABLE products ADD COLUMN packaging_description VARCHAR(255) DEFAULT '';

-- Packaging لعناصر الطلب
ALTER TABLE order_items ADD COLUMN packaging_selected TINYINT(1) DEFAULT 0;
ALTER TABLE order_items ADD COLUMN packaging_price INT DEFAULT 0;
ALTER TABLE order_items ADD COLUMN packaging_description VARCHAR(255) DEFAULT '';

-- إجمالي التغليف للطلب
ALTER TABLE orders ADD COLUMN packaging_total INT DEFAULT 0 AFTER subtotal;
```

---

## [2.9.0] - 2026-01-19

### 📋 تحديث سياسة الاستبدال والاسترجاع

- **إعادة صياغة كاملة** لقسم "سياسة الاستبدال والاسترجاع" في الشروط والأحكام
- **سياسة واضحة للمنتجات المخصصة**:
  - لا يوجد استرجاع أو استبدال بعد التنفيذ أو التسليم
  - لا يُقبل الترجيع بسبب تغيير رأي العميل
- **الاستثناء الوحيد**: خطأ من طرف المتجر (طباعة خاطئة، عيب تصنيع، تلف شحن)
- **شروط النظر في الحالة**: التواصل خلال 24 ساعة، صور واضحة، تحقق ميداني
- **تحديث SEO**: وصف الصفحة المحدث بدون كلمة "استرجاع"
- **privacy.php**: إزالة تفاصيل الاسترجاع وإضافة رابط للشروط والأحكام
- **Footer**: تغيير "🔄 استبدال سهل" إلى "✅ جودة مضمونة"

### 📚 ملفات محدثة

- `terms.php` - سياسة الاستبدال والاسترجاع الجديدة
- `privacy.php` - إزالة تفاصيل الاسترجاع
- `includes/version.php` - الإصدار 2.9.0

---

## [2.8.0] - 2026-01-19

### ✅ نظام الموافقة على الشروط والخصوصية (جديد)

- **Checkbox إلزامي** في صفحة السلة قبل إتمام الطلب
- **النص الرسمي**: "أوافق على الشروط والأحكام وسياسة الخصوصية..."
- **روابط مباشرة** إلى صفحات الشروط والخصوصية
- **لا يمكن إتمام الطلب** بدون الموافقة
- **تخزين الموافقة** مع الطلب في قاعدة البيانات:
  - `terms_consent` - حالة الموافقة (نعم/لا)
  - `consent_timestamp` - وقت الموافقة بتوقيت بغداد

### 🔔 إشعارات الموافقة

- **Telegram**: يتضمن سطر "✅ الموافقة على الشروط/الخصوصية: نعم"
- **لوحة التحكم**: عرض حالة الموافقة ووقتها في تفاصيل الطلب

### 📚 ملفات محدثة

- `cart.php` - إضافة Checkbox الموافقة
- `js/cart.js` - التحقق والإرسال
- `api/submit-order.php` - معالجة وحفظ الموافقة
- `includes/functions.php` - تحديث saveOrder
- `admin/orders.php` - عرض حالة الموافقة
- `database_unified.sql` - إضافة الحقول الجديدة (v3.5)

---

## [2.7.1] - 2026-01-19

### 🏷️ تثبيت اسم الموقع للـ SEO

- **اسم الموقع الرسمي**: `بيج سبرايز | Surprise page`
- **العنوان ثابت 100%** في جميع الصفحات (Title Tag)
- **Schema.org محدث** - WebSite, Organization, Store, Product
- **Open Graph** - `og:site_name` ثابت
- **الكلمات المفتاحية** محدثة (عربي + إنجليزي)

### 📄 صفحة "من نحن" جديدة

- **إعادة تصميم كاملة** تطابق هوية الموقع
- **الألوان والتصميم** متناسقة مع باقي الصفحات
- **قسم القيم** - بطاقات متحركة
- **الإحصائيات** - 1000+ عميل، 500+ منتج، 19 محافظة
- **أيقونات مخصصة** بدلاً من الإيموجي للتواصل
- **مربوطة بالإعدادات** - تتغير تلقائياً من لوحة التحكم

### 🔗 تحديث قوائم التنقل

- **رابط "من نحن"** في جميع الصفحات (Header + Footer)
- الصفحات المحدثة: index, products, product, cart, track, wishlist, privacy, terms, about

### 🔧 إصلاح مشكلة Google Indexing

- **إزالة redirect 301** من `.htaccess`
- حل مشكلة "Page with redirect" في Google Search Console
- الموقع يعمل بـ `.php` وبدونها معاً

### 🎨 تحسينات الأيقونات

- **أيقونات التواصل** - `icon1.png` (Instagram), `icon2.png` (Telegram)
- استبدال جميع إيموجيات التواصل بأيقونات مخصصة
- تطبيق في: cart.php, admin/orders.php, about.php

### 📚 ملفات محدثة

- `includes/seo.php` - الثوابت الجديدة: SITE_BRAND_NAME, SITE_BRAND_NAME_AR, SITE_BRAND_NAME_EN
- `includes/config.php` - تحديث SITE_NAME
- `includes/version.php` - الإصدار 2.7.1
- `.htaccess` - إزالة redirect rules
- جميع ملفات PHP الرئيسية - تحديث العناوين والقوائم

---

## [2.5.0] - 2026-01-18

### 👥 نظام إدارة الموظفين (جديد)

- **إضافة موظفين جدد** - اسم المستخدم، كلمة المرور، الاسم الكامل
- **نظام الصلاحيات** - تحديد صفحات يمكن للموظف الوصول إليها
- **بيانات الموظف** - المسمى الوظيفي، المحافظة، المنطقة، العنوان
- **تفعيل/تعطيل** - إمكانية تعطيل حساب موظف مؤقتاً
- **تسجيل الدخول** - الموظفون يستخدمون نفس صفحة تسجيل دخول المدير
- **حماية الصفحات** - الإعدادات وإدارة الموظفين للمدير فقط

### 🔧 تقنية

- إضافة `admin/staff.php` - صفحة إدارة الموظفين
- تحديث `includes/security.php` - دوال الموظفين والصلاحيات
- تحديث `admin/includes/sidebar.php` - إضافة رابط إدارة الموظفين
- تحديث `admin/login.php` - دعم تسجيل دخول الموظفين
- تحديث `database_unified.sql` - إضافة جدول staff + custom_images

### 📚 توثيق

- تحديث شامل لـ README.md
- تحديث CHANGELOG.md
- تحديث DOCS.md

---

## [2.1.6] - 2026-01-17

### 📱 تحسينات Mobile Sidebar

- **تصغير حجم القائمة الجانبية** من 100% إلى 85% (حد أقصى 320px)
- **قائمة قابلة للسكرول** - تدعم قوائم طويلة بدون تجاوز الشاشة
- **عنوان "القائمة"** في أعلى الـ Sidebar
- **تحسين شكل الروابط** - خلفية ناعمة مع حدود وردية
- **Overlay للإغلاق** - الضغط خارج القائمة يغلقها
- **إغلاق بـ Escape** - دعم لوحة المفاتيح

### 🎯 تحسينات صفحة المنتج (Mobile)

- **تصغير زر "ارفع صورتك"** - حجم أصغر وأكثر انسيابية
- **ترتيب أزرار Wishlist/Share** - القلب على اليسار، المشاركة على اليمين
- **أيقونة wishlist.png** - استبدال Emoji بأيقونة القلب الوردية
- **تأثيرات hover وactive** - تغيير اللون عند التفاعل

### 🔧 تقنية

- تحديث `css/main.css` - أنماط Sidebar المحسنة
- تحديث `js/app.js` - إضافة overlay وإغلاق بـ Escape
- تحديث `product.php` - ترتيب الأزرار وأيقونات جديدة

---

## [2.1.5] - 2026-01-16

### 🛠️ إصلاحات

- إصلاحات طفيفة في الأداء
- تحسينات في الاستجابة

---

## [2.1.0] - 2026-01-16

### ✨ ميزات جديدة

- 🚀 **PWA (Progressive Web App)** - تثبيت الموقع كتطبيق
- ❤️ **نظام المفضلة (Wishlist)** - localStorage بدون قاعدة بيانات
- 🔒 **CSP Security Headers** - حماية متطورة
- 🖼️ **Lazy Loading** - تحميل كسول للصور
- 📝 **Open Graph ديناميكي** - مشاركة منتجات صحيحة

### 📁 ملفات جديدة

- `wishlist.php` - صفحة المفضلة
- `includes/images.php` - دوال الصور المحسنة
- `manifest.json` - إعدادات PWA
- `sw.js` - Service Worker

---

## [2.0.5] - 2026-01-16

### ⚙️ إعدادات

- 📱 **السوشيال ميديا الديناميكية** - تنعكس في كل الموقع
- 🕐 **توحيد عرض الوقت** - Baghdad timezone + 12 ساعة عربي
- 🔗 **مودال تواصل معنا** - يستخدم الإعدادات المحفوظة

---

## [1.8.4] - 2026-01-15

### 📱 تحسينات الموبايل

- تحسين كامل لتجاوب صفحة المبيعات
- توحيد منطق حساب المبيعات

---

## [1.7.0] - 2026-01-14

### ✨ ميزات جديدة

- نظام تحليل المبيعات
- الأكثر مبيعاً تلقائي
- نظام التقييمات

---

## [1.6.0] - 2026-01-14

### 🔄 بنية تحتية

- نظام Cache Busting مركزي
- تبديل الصور التلقائي

---

## [1.5.0] - 2026-01-13

### ✨ ميزات جديدة

- نظام الأقسام الديناميكي
- حذف الصور الفردية
- نظام النسخ الاحتياطي

---

## [1.0.0] - 2026-01-01

### 🎉 الإطلاق الأولي

- إطلاق المتجر الإلكتروني
- لوحة تحكم كاملة
- نظام الطلبات والسلة

---

## 📌 صيغة الإصدارات

نستخدم [Semantic Versioning](https://semver.org/):

- **MAJOR** (X.0.0) - تغييرات كبيرة غير متوافقة
- **MINOR** (0.X.0) - ميزات جديدة متوافقة
- **PATCH** (0.0.X) - إصلاحات أخطاء

---

© 2026 Surprise! Store
