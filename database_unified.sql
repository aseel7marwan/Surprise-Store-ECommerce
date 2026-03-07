-- Surprise! Store Database Backup
-- Created: 2026-02-08 23:21:27
-- Tables: 23

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `admin_actions_log`;
CREATE TABLE `admin_actions_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_username` varchar(100) NOT NULL COMMENT 'المدير الذي قام بالإجراء',
  `action_type` varchar(50) NOT NULL COMMENT 'نوع الإجراء',
  `action_target` varchar(200) DEFAULT '' COMMENT 'الهدف من الإجراء',
  `action_details` text DEFAULT NULL COMMENT 'تفاصيل الإجراء (JSON)',
  `ip_address` varchar(45) NOT NULL COMMENT 'عنوان IP المدير',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_username`),
  KEY `idx_action` (`action_type`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT 'اسم المستخدم',
  `password` varchar(255) NOT NULL COMMENT 'كلمة المرور (مشفرة)',
  `email` varchar(255) DEFAULT NULL COMMENT 'البريد الإلكتروني',
  `full_name` varchar(255) DEFAULT NULL COMMENT 'الاسم الكامل',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'الحساب نشط',
  `last_login` timestamp NULL DEFAULT NULL COMMENT 'آخر تسجيل دخول',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`id`,`username`,`password`,`email`,`full_name`,`is_active`,`last_login`,`created_at`) VALUES ('1','admin','YOUR_PASSWORD_HASH_HERE',NULL,'مدير المتجر','1',NULL,'2026-01-20 01:09:53');

DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `banner_id` varchar(50) NOT NULL COMMENT 'معرف البانر',
  `image_path` varchar(255) NOT NULL COMMENT 'مسار الصورة',
  `title` varchar(255) DEFAULT NULL COMMENT 'العنوان',
  `subtitle` varchar(255) DEFAULT NULL COMMENT 'العنوان الفرعي',
  `link` varchar(255) DEFAULT NULL COMMENT 'الرابط',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'نشط',
  `sort_order` int(11) DEFAULT 0 COMMENT 'ترتيب العرض',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `banner_id` (`banner_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `banners` (`id`,`banner_id`,`image_path`,`title`,`subtitle`,`link`,`is_active`,`sort_order`,`created_at`) VALUES ('2','banner_02','banners/banner2.png','أطقم ساعات فاخرة','أناقة تتجاوز الزمن مع حفر الاسم',NULL,'1','2','2026-01-20 01:09:53');

DROP TABLE IF EXISTS `blocked_devices`;
CREATE TABLE `blocked_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'عنوان IP المحظور',
  `device_fingerprint` varchar(64) DEFAULT NULL COMMENT 'بصمة الجهاز المحظورة',
  `user_agent_hash` varchar(64) DEFAULT NULL COMMENT 'هاش User Agent',
  `blocked_by` varchar(100) NOT NULL COMMENT 'من قام بالحظر',
  `block_reason` varchar(255) DEFAULT '' COMMENT 'سبب الحظر',
  `original_session_id` int(11) DEFAULT NULL COMMENT 'الجلسة الأصلية التي أدت للحظر',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'الحظر مفعل',
  `unblocked_at` datetime DEFAULT NULL COMMENT 'تاريخ إلغاء الحظر',
  `unblocked_by` varchar(100) DEFAULT NULL COMMENT 'من ألغى الحظر',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_fingerprint` (`device_fingerprint`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_key` varchar(50) NOT NULL COMMENT 'مفتاح القسم (مثل: boxes, decorations)',
  `name` varchar(100) NOT NULL COMMENT 'اسم القسم بالعربي',
  `icon` varchar(10) DEFAULT '?' COMMENT 'أيقونة القسم (emoji)',
  `sort_order` int(11) DEFAULT 0 COMMENT 'ترتيب العرض',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'القسم نشط',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_key` (`category_key`),
  KEY `idx_key` (`category_key`),
  KEY `idx_active` (`is_active`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`,`category_key`,`name`,`icon`,`sort_order`,`is_active`,`created_at`) VALUES ('1','boxes','البوكسات والعلب','🎁','1','1','2026-01-20 01:09:53');
INSERT INTO `categories` (`id`,`category_key`,`name`,`icon`,`sort_order`,`is_active`,`created_at`) VALUES ('2','decorations','تحف وديكورات','✨','2','1','2026-01-20 01:09:53');
INSERT INTO `categories` (`id`,`category_key`,`name`,`icon`,`sort_order`,`is_active`,`created_at`) VALUES ('3','printing','طباعة وتخصيص','🎨','3','1','2026-01-20 01:09:53');
INSERT INTO `categories` (`id`,`category_key`,`name`,`icon`,`sort_order`,`is_active`,`created_at`) VALUES ('4','flowers','ورد وباقات','💐','4','1','2026-01-20 01:09:53');
INSERT INTO `categories` (`id`,`category_key`,`name`,`icon`,`sort_order`,`is_active`,`created_at`) VALUES ('5','watches','ساعات وإكسسوارات','⌚','5','1','2026-01-20 01:09:53');
INSERT INTO `categories` (`id`,`category_key`,`name`,`icon`,`sort_order`,`is_active`,`created_at`) VALUES ('6','perfumes','عطور ومستحضرات','🧴','6','1','2026-01-20 01:09:53');
INSERT INTO `categories` (`id`,`category_key`,`name`,`icon`,`sort_order`,`is_active`,`created_at`) VALUES ('7','electronics','إلكترونيات','📱','7','1','2026-01-20 01:09:53');
INSERT INTO `categories` (`id`,`category_key`,`name`,`icon`,`sort_order`,`is_active`,`created_at`) VALUES ('8','occasions','مناسبات خاصة','🎉','8','1','2026-01-20 01:09:53');

DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT 'كود الخصم',
  `discount_type` enum('percentage','fixed') DEFAULT 'percentage' COMMENT 'نوع الخصم',
  `discount_value` decimal(10,2) NOT NULL COMMENT 'قيمة الخصم',
  `min_order` int(11) DEFAULT 0 COMMENT 'الحد الأدنى للطلب',
  `max_discount` int(11) DEFAULT 0 COMMENT 'الحد الأقصى للخصم',
  `max_uses` int(11) DEFAULT 0 COMMENT 'الحد الأقصى للاستخدام (0 = غير محدود)',
  `used_count` int(11) DEFAULT 0 COMMENT 'عدد مرات الاستخدام',
  `expires_at` date DEFAULT NULL COMMENT 'تاريخ انتهاء الصلاحية',
  `apply_to` enum('all','specific') DEFAULT 'all' COMMENT 'تطبيق على',
  `product_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'معرفات المنتجات المحددة' CHECK (json_valid(`product_ids`)),
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'الكوبون نشط',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_code` (`code`),
  KEY `idx_active` (`is_active`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `coupons` (`id`,`code`,`discount_type`,`discount_value`,`min_order`,`max_discount`,`max_uses`,`used_count`,`expires_at`,`apply_to`,`product_ids`,`is_active`,`created_at`,`updated_at`) VALUES ('1','WELCOME10','percentage','10.00','25000','10000','100','0','2026-12-31','all',NULL,'1','2026-01-20 01:09:53','2026-01-20 01:09:53');
INSERT INTO `coupons` (`id`,`code`,`discount_type`,`discount_value`,`min_order`,`max_discount`,`max_uses`,`used_count`,`expires_at`,`apply_to`,`product_ids`,`is_active`,`created_at`,`updated_at`) VALUES ('2','SURPRISE20','percentage','20.00','50000','20000','50','0','2026-06-30','all',NULL,'1','2026-01-20 01:09:53','2026-01-20 01:09:53');
INSERT INTO `coupons` (`id`,`code`,`discount_type`,`discount_value`,`min_order`,`max_discount`,`max_uses`,`used_count`,`expires_at`,`apply_to`,`product_ids`,`is_active`,`created_at`,`updated_at`) VALUES ('3','GIFT5000','fixed','5000.00','30000','0','0','0',NULL,'all',NULL,'1','2026-01-20 01:09:53','2026-01-20 01:09:53');

DROP TABLE IF EXISTS `customer_uploads`;
CREATE TABLE `customer_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL COMMENT 'معرف الطلب',
  `original_name` varchar(255) DEFAULT NULL COMMENT 'الاسم الأصلي',
  `file_path` varchar(255) NOT NULL COMMENT 'مسار الملف',
  `file_size` int(11) DEFAULT NULL COMMENT 'حجم الملف',
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  CONSTRAINT `customer_uploads_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `forced_logouts`;
CREATE TABLE `forced_logouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `php_session_id` varchar(128) NOT NULL,
  `forced_at` datetime DEFAULT current_timestamp(),
  `forced_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`php_session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `known_devices`;
CREATE TABLE `known_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `device_id` varchar(64) NOT NULL,
  `fingerprint` varchar(64) NOT NULL,
  `device_type` varchar(20) DEFAULT 'unknown',
  `os_name` varchar(50) DEFAULT '',
  `browser_name` varchar(50) DEFAULT '',
  `first_ip` varchar(45) DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `first_country` varchar(100) DEFAULT '',
  `first_city` varchar(100) DEFAULT '',
  `last_country` varchar(100) DEFAULT '',
  `last_city` varchar(100) DEFAULT '',
  `is_forgotten` tinyint(1) DEFAULT 0,
  `first_seen` datetime DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_device` (`username`,`device_id`),
  KEY `idx_device` (`device_id`),
  KEY `idx_forgotten` (`is_forgotten`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `login_sessions`;
CREATE TABLE `login_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` enum('admin','staff') NOT NULL COMMENT 'نوع المستخدم',
  `user_id` int(11) DEFAULT NULL COMMENT 'معرف الموظف (NULL للمدير الرئيسي)',
  `username` varchar(100) NOT NULL COMMENT 'اسم المستخدم',
  `user_display_name` varchar(200) DEFAULT '' COMMENT 'الاسم الكامل للعرض',
  `session_id` varchar(128) NOT NULL COMMENT 'معرف الجلسة',
  `device_id` varchar(64) DEFAULT '',
  `remember_token_selector` varchar(24) DEFAULT NULL COMMENT 'معرف توكن Remember Me',
  `ip_address` varchar(45) NOT NULL COMMENT 'عنوان IP',
  `user_agent` text DEFAULT NULL COMMENT 'معلومات المتصفح الكاملة',
  `device_type` enum('phone','tablet','desktop','unknown') DEFAULT 'unknown' COMMENT 'نوع الجهاز',
  `device_name` varchar(100) DEFAULT '' COMMENT 'اسم الجهاز',
  `os_name` varchar(50) DEFAULT '' COMMENT 'اسم النظام',
  `os_version` varchar(20) DEFAULT '' COMMENT 'إصدار النظام',
  `browser_name` varchar(50) DEFAULT '' COMMENT 'اسم المتصفح',
  `browser_version` varchar(20) DEFAULT '' COMMENT 'إصدار المتصفح',
  `device_fingerprint` varchar(64) DEFAULT '' COMMENT 'بصمة الجهاز',
  `country` varchar(100) DEFAULT '' COMMENT 'الدولة',
  `country_code` varchar(5) DEFAULT '' COMMENT 'رمز الدولة',
  `city` varchar(100) DEFAULT '' COMMENT 'المدينة',
  `status` enum('active','expired','logged_out','blocked','admin_logout') DEFAULT 'active' COMMENT 'حالة الجلسة',
  `deleted_at` datetime DEFAULT NULL,
  `is_new_device` tinyint(1) DEFAULT 0 COMMENT 'جهاز جديد لهذا المستخدم',
  `is_new_location` tinyint(1) DEFAULT 0 COMMENT 'موقع جديد لهذا المستخدم',
  `blocked_at` datetime DEFAULT NULL COMMENT 'تاريخ الحظر',
  `blocked_by` varchar(100) DEFAULT NULL COMMENT 'من قام بالحظر',
  `block_reason` varchar(255) DEFAULT NULL COMMENT 'سبب الحظر',
  `logged_out_at` datetime DEFAULT NULL COMMENT 'تاريخ تسجيل الخروج',
  `logged_out_by` varchar(100) DEFAULT NULL COMMENT 'من قام بتسجيل الخروج',
  `login_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'وقت الدخول',
  `last_activity` datetime DEFAULT NULL COMMENT 'آخر نشاط',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_new_ip` tinyint(1) DEFAULT 0,
  `action_by` varchar(100) DEFAULT NULL,
  `action_at` datetime DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_type`,`user_id`),
  KEY `idx_username` (`username`),
  KEY `idx_session` (`session_id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_status` (`status`),
  KEY `idx_login_at` (`login_at`),
  KEY `idx_device_fingerprint` (`device_fingerprint`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL COMMENT 'معرف الطلب',
  `product_id` varchar(50) DEFAULT NULL COMMENT 'معرف المنتج',
  `product_name` varchar(255) NOT NULL COMMENT 'اسم المنتج',
  `price` int(11) NOT NULL COMMENT 'السعر',
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'الكمية',
  `has_custom_image` tinyint(1) DEFAULT 0 COMMENT 'يحتوي صورة مخصصة',
  `custom_image_path` varchar(255) DEFAULT NULL COMMENT 'مسار الصورة المخصصة',
  `selected_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'الخيارات المختارة (لون، حجم، عمر، نص مخصص)' CHECK (json_valid(`selected_options`)),
  `custom_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'صور التخصيص المتعددة المرفوعة من الزبون' CHECK (json_valid(`custom_images`)),
  `packaging_selected` tinyint(1) DEFAULT 0 COMMENT 'الزبون اختار التغليف',
  `packaging_price` int(11) DEFAULT 0 COMMENT 'سعر التغليف المحفوظ',
  `packaging_description` varchar(255) DEFAULT '' COMMENT 'وصف التغليف',
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `order_tracking`;
CREATE TABLE `order_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL COMMENT 'معرف الطلب',
  `status` varchar(50) NOT NULL COMMENT 'الحالة',
  `note` text DEFAULT NULL COMMENT 'ملاحظة',
  `location` varchar(255) DEFAULT NULL COMMENT 'الموقع',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `order_tracking_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) NOT NULL COMMENT 'رقم الطلب',
  `customer_name` varchar(255) DEFAULT NULL COMMENT 'اسم الزبون',
  `customer_phone` varchar(20) DEFAULT NULL COMMENT 'رقم الهاتف',
  `customer_city` varchar(100) DEFAULT NULL COMMENT 'المحافظة',
  `customer_address` text DEFAULT NULL COMMENT 'العنوان التفصيلي',
  `contact_method` enum('instagram','whatsapp','telegram') DEFAULT NULL COMMENT 'وسيلة التواصل المفضلة',
  `contact_value` varchar(100) DEFAULT NULL COMMENT 'معرف أو رقم التواصل',
  `subtotal` int(11) DEFAULT 0 COMMENT 'المجموع الفرعي (المنتجات)',
  `packaging_total` int(11) DEFAULT 0 COMMENT 'إجمالي كلفة التغليف لمجمل الطلب',
  `discount` int(11) DEFAULT 0 COMMENT 'قيمة الخصم',
  `coupon_code` varchar(50) DEFAULT NULL COMMENT 'كود الكوبون المستخدم',
  `delivery_fee` int(11) DEFAULT 0 COMMENT 'رسوم التوصيل',
  `total` int(11) DEFAULT 0 COMMENT 'المجموع الكلي',
  `notes` text DEFAULT NULL COMMENT 'ملاحظات الزبون',
  `terms_consent` tinyint(1) DEFAULT 0 COMMENT 'موافقة على الشروط والخصوصية',
  `consent_timestamp` datetime DEFAULT NULL COMMENT 'وقت الموافقة على الشروط',
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending' COMMENT 'حالة الطلب',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_status` (`status`),
  KEY `idx_order_number` (`order_number`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_year_month` (`created_at`,`status`),
  KEY `idx_coupon` (`coupon_code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` varchar(50) NOT NULL COMMENT 'معرف فريد للمنتج',
  `name` varchar(255) NOT NULL COMMENT 'اسم المنتج',
  `description` text DEFAULT NULL COMMENT 'وصف المنتج',
  `price` int(11) NOT NULL DEFAULT 0 COMMENT 'السعر بالدينار العراقي',
  `old_price` int(11) DEFAULT 0 COMMENT 'السعر القديم (قبل التخفيض)',
  `category` varchar(50) NOT NULL COMMENT 'التصنيف',
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'مصفوفة صور المنتج' CHECK (json_valid(`images`)),
  `customizable` tinyint(1) DEFAULT 0 COMMENT 'هل قابل للتخصيص',
  `featured` tinyint(1) DEFAULT 0 COMMENT 'منتج مميز',
  `status` enum('available','sold','hidden') DEFAULT 'available' COMMENT 'حالة المنتج',
  `stock` int(11) DEFAULT 10 COMMENT 'عدد القطع المتوفرة',
  `total_sold` int(11) DEFAULT 0 COMMENT 'إجمالي المبيعات',
  `monthly_sold` int(11) DEFAULT 0 COMMENT 'مبيعات الشهر الحالي',
  `is_best_seller` tinyint(1) DEFAULT 0 COMMENT 'الأكثر مبيعاً',
  `is_trending` tinyint(1) DEFAULT 0 COMMENT 'رائج هذا الشهر',
  `last_sale_at` datetime DEFAULT NULL COMMENT 'تاريخ آخر بيع',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'خيارات المنتج (ألوان، أحجام، أعمار، نص مخصص)' CHECK (json_valid(`options`)),
  `packaging_enabled` tinyint(1) DEFAULT 0 COMMENT 'هل التغليف متاح لهذا المنتج',
  `packaging_price` int(11) DEFAULT 0 COMMENT 'سعر التغليف بالدينار العراقي',
  `packaging_description` varchar(255) DEFAULT '' COMMENT 'وصف التغليف (اختياري)',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`featured`),
  KEY `idx_total_sold` (`total_sold`),
  KEY `idx_monthly_sold` (`monthly_sold`),
  KEY `idx_best_seller` (`is_best_seller`),
  KEY `idx_trending` (`is_trending`),
  KEY `idx_stock` (`stock`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('1','prod_696eabb577719','طقم ساعة رولكس فاخر مع حفر الاسم','طقم ساعة رولكس فاخر في علبة خشبية أنيقة باللون الأحمر الداكن مع بطانة جلدية. يشمل ساعة رولكس ذهبية وفضية مع حفر الاسم بالعربي على واجهة الساعة، إضافة إلى سبحة أنيقة. الهدية المثالية لرجل الأعمال والمناسبات الخاصة. يمكن تخصيص الاسم والتصميم حسب رغبتك.','55000','0','watches','[\"products\\/photo_2025-08-23_13-21-42.jpg\",\"products\\/photo_2025-08-23_13-21-43.jpg\"]','1','1','available','10','0','0','0','0',NULL,'{\"extra_fields\":{\"enabled\":true,\"required\":true,\"groups\":[]},\"custom_images\":{\"enabled\":true,\"required\":false,\"groups\":[],\"allowed_types\":[\"jpg\",\"jpeg\",\"png\",\"webp\"],\"label\":\"ارفع صورك للطباعة\",\"min_images\":1,\"max_images\":5,\"max_size_mb\":5}}','0','0','','2026-01-20 01:09:57','2026-02-01 20:16:15');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('2','prod_696eabb578973','طقم رولكس VIP الكامل','طقم رولكس الفاخر الكامل في علبة خضراء رسمية. يشمل ساعة رولكس ذهبية مع حفر الاسم، سبحة فاخرة، أزرار قميص كريستال، وقلم فاخر. الطقم المتكامل للرجل الأنيق. جميع القطع مصممة بجودة عالية ويمكن تخصيص الاسم على جميع القطع.','45000','0','watches','[\"products\\/photo_2025-08-23_13-24-42.jpg\"]','1','1','available','10','0','0','0','0',NULL,'{\"extra_fields\":{\"enabled\":true,\"required\":true,\"groups\":[]}}','0','0','','2026-01-20 01:09:57','2026-02-01 20:17:07');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('3','prod_696eabb578ef1','قبة زجاجية مضيئة مخصصة بالاسم','قبة زجاجية أنيقة مع إضاءة LED ساحرة وقاعدة خشبية. يمكن كتابة اسمك أو رسالتك المفضلة عليها بخط عربي جميل. مثالية للهدايا الرومانسية، أعياد الميلاد، أو كتذكار مميز. الإضاءة الدافئة تضيف لمسة سحرية للغرفة.','16000','0','decorations','[\"products\\/photo_2025-11-08_23-26-05.jpg\",\"products\\/photo_2025-11-08_23-26-06.jpg\"]','1','1','available','10','0','0','0','0',NULL,'[]','0','0','','2026-01-20 01:09:57','2026-02-01 20:17:33');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('4','prod_696eabb57a0ee','كوب سيراميك مطبوع - تصميم أنمي','كوب سيراميك عالي الجودة مع طباعة احترافية لتصميم أنمي رائع. الطباعة مقاومة للخدش والغسل. مثالي لمحبي الأنمي والفن الياباني. يمكنك طلب أي تصميم أنمي مفضل لديك أو صورتك الشخصية بستايل أنمي.','7000','0','printing','[\"products\\/photo_2025-11-21_09-51-37.jpg\",\"products\\/photo_2025-11-21_09-51-38.jpg\"]','1','0','available','10','0','0','0','0',NULL,'[]','0','0','','2026-01-20 01:09:57','2026-02-04 16:39:39');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('5','prod_696eabb57bf89','بوكس الأركيلة الفخم','بوكس هدية فاخر يحتوي على أركيلة مزخرفة بتصميم أنيق باللون الأسود والفضي، مع ورود حمراء وتغليف احترافي. الهدية المثالية لمحبي الأركيلة. يشمل بطاقة اهداء مخصصة مع رسالتك الشخصية.','18000','0','boxes','[\"products\\/photo_2025-12-08_12-07-34.jpg\",\"products\\/photo_2025-12-08_12-07-36.jpg\"]','1','1','available','10','0','0','0','0',NULL,'[]','0','0','','2026-01-20 01:09:57','2026-02-04 15:06:19');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('6','prod_696eabb57c640','أسطوانة زهور طبيعية مضيئة','أسطوانة زجاجية أنيقة تحتوي على باقة زهور طبيعية مجففة بألوان زهرية رقيقة مع إضاءة LED ساحرة. تحفة ديكورية مميزة تدوم طويلاً. مثالية كهدية لعيد الأم، أو للتعبير عن الحب والتقدير.','8000','0','decorations','[\"products\\/photo_2025-12-17_00-11-20.jpg\",\"products\\/photo_2025-12-17_00-11-25.jpg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-20 01:09:57','2026-02-04 16:38:55');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('7','prod_696eabb57df6d','قبة طلب الزواج المضيئة','قبة زجاجية ساحرة تحتوي على مجسم عروس وعريس يطلب يدها، مع قلوب وردية لامعة وإضاءة LED ملونة. الهدية المثالية لطلب الزواج أو للاحتفال بالذكرى السنوية. تصميم رومانسي لا يُنسى يعبر عن أجمل اللحظات.','8000','0','decorations','[\"products\\/photo_2025-12-17_00-11-40.jpg\",\"products\\/photo_2025-12-17_00-13-50.jpg\"]','0','1','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-20 01:09:57','2026-02-01 20:18:35');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('8','prod_696eabb57e5d4','تحفة الهلال المضيئة - I Love You','تحفة فنية رائعة على شكل هلال من الريزن الشفاف مع زهور طبيعية مجففة بداخلها، ومجسم زوجين رومانسي. قاعدة خشبية منقوش عليها \"I Love You\" مع إضاءة LED دافئة. الهدية المثالية للتعبير عن الحب.','35000','45000','decorations','[\"products\\/photo_2025-12-17_00-15-19.jpg\",\"products\\/photo_2025-12-17_00-15-20.jpg\"]','0','1','sold','10','0','0','0','0',NULL,'[]','0','0','','2026-01-20 01:09:57','2026-02-01 20:18:52');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('9','prod_696eabb57f178','بوكيه شوكولاتة كندر مع الورد','بوكيه فاخر من شوكولاتة كندر بوينو وكندر ديليشيز مع ورود حمراء صناعية جميلة. مغلف بقماش أحمر مخملي فاخر مع بطاقة اهداء. الهدية المثالية لمحبي الشوكولاتة والحلويات. تجمع بين الحلاوة والجمال.','38000','45000','boxes','[\"products\\/photo_2025-12-19_22-43-00.jpg\",\"products\\/photo_2025-12-19_22-43-09.jpg\"]','1','0','available','10','0','0','0','0',NULL,'[]','0','0','','2026-01-20 01:09:57','2026-01-20 01:09:57');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('12','prod_696eabb580b57','فانوس الأم المضيء - أمي الغالية','فانوس خشبي على شكل بيت صغير مع رسمة أم تحتضن أطفالها. سقف لامع باللون الوردي مع إضاءة LED دافئة. منقوش عليه &quot;أمي الغالية&quot;. الهدية المثالية لعيد الأم للتعبير عن الحب والتقدير للأم الحنونة.','6000','0','decorations','[\"products\\/photo_2025-12-28_18-18-21.jpg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-20 01:09:57','2026-02-04 16:38:09');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('13','prod_696eabb582899','بوكس هدايا رومانسي متكامل','بوكس هدايا متكامل للمحبين يشمل: ساعة يد أنيقة، ترمس شخصي بالاسم، باقة ورد، عطر فاخر، سلسلة ذهبية، شوكولاتة، وبطاقة مع صورة الزوجين. كل ما تحتاجه للتعبير عن حبك في علبة واحدة مميزة.','16000','25000','boxes','[\"products\\/photo_2025-12-28_18-18-39.jpg\",\"products\\/photo_2025-12-28_18-19-13.jpg\"]','1','1','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-20 01:09:57','2026-02-01 20:19:19');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('14','prod_696eabb582d3b','كرة ثلج Love Story الموسيقية','كرة ثلج موسيقية ساحرة بتصميم رومانسي يظهر شاب يقدم المظلة لحبيبته تحت الثلج. قاعدة مزينة برسومات المدينة وكتابة &quot;Love Story&quot;. تعمل بالموسيقى وتتسلل الثلج بداخلها. متوفرة بألوان متعددة: أزرق ووردي.','8000','0','decorations','[\"products\\/photo_2025-12-29_13-14-32.jpg\",\"products\\/photo_2025-12-29_13-14-33.jpg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-20 01:09:57','2026-02-04 15:36:14');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('15','prod_696eabb5836b6','فانوس كيوبيد المضيء - I Love You','فانوس عصري على شكل قوس مع ملاك كيوبيد الصغير يحمل بالونات الحب. إضاءة LED بألوان دافئة مع وردة حمراء في الأسفل. مكتوب عليه &quot;HappyHost&quot;. تحفة رومانسية مميزة للتعبير عن الحب.','8000','0','decorations','[\"products\\/photo_2025-12-29_13-14-33 (2).jpg\",\"products\\/photo_2025-12-29_13-14-34.jpg\"]','0','0','available','10','0','0','0','0',NULL,'[]','0','0','','2026-01-20 01:09:57','2026-02-04 17:14:30');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('16','prod_696eabb5846c6','بوكس يونيكورن الفاخر','بوكس هدايا ساحر يحتوي على دمية يونيكورن ضخمة بألوان قوس قزح، ساعة يد أنيقة، محفظة جلد مع حفر الاسم، سلسلة ذهبية، وورود حمراء. التغليف الفاخر مع شريط ساتان يجعله أروع هدية للبنات ومحبي اليونيكورن.','85000','100000','boxes','[\"products\\/photo_2026-01-13_15-28-55.jpg\",\"products\\/photo_2026-01-13_15-34-06.jpg\",\"products\\/photo_2026-01-13_15-34-14.jpg\",\"products\\/photo_2026-01-13_15-34-16.jpg\"]','1','1','sold','10','0','0','0','0',NULL,'[]','0','0','','2026-01-20 01:09:57','2026-02-01 20:20:13');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('17','prod_696eabb5865af','ألبوم صور الذكريات الفاخر','ألبوم صور عالي الجودة بتصميم رومانسي مع إطار قلب ذهبي وخلفيات ورود ملونة. يتسع لـ 100 صورة بحجم 4×6. متوفر بثلاثة تصاميم: أزرق سماوي، وردي بالورد، وأصفر بالشاي. الهدية المثالية لحفظ الذكريات الجميلة.','15000','0','printing','[\"products\\/photo_2026-01-13_15-34-39.jpg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','40000','','2026-01-20 01:09:57','2026-01-20 15:21:40');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('19','prod_697b8f4830d81','فنجان طباعة','فنجان طباعة حسب الطلب مع ماعون زجاج','12000','0','printing','[\"products\\/697b8f482fde6_1769705288.jpeg\",\"products\\/697b8f48309d9_1769705288.jpeg\",\"products\\/697b8f4830c45_1769705288.jpeg\"]','0','1','available','10','0','0','0','0',NULL,'{\"extra_fields\":{\"enabled\":true,\"required\":true,\"groups\":[{\"label\":\"\",\"items\":[{\"type\":\"text\",\"label\":\"الاسم\",\"required\":true,\"max_length\":50,\"placeholder\":\"اختار الاسم  للطباعة على الفنجان\"}]}]}}','1','1000','','2026-01-29 19:48:08','2026-01-29 20:01:34');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('21','prod_697b983217c95','إطار صور دوار','إطار صور دوار  تشغيله على البطاريه يحتوي على 4 صور','15000','0','decorations','[\"products\\/697b9832179f7_1769707570.jpeg\",\"products\\/697b983217bc3_1769707570.jpeg\"]','1','0','sold','10','0','0','0','0',NULL,'{\"custom_images\":{\"enabled\":true,\"required\":true,\"groups\":[{\"label\":\"\",\"min\":1,\"max\":4,\"max_size\":5,\"required\":true}],\"allowed_types\":[\"jpg\",\"jpeg\",\"png\",\"webp\"],\"label\":\"ارفع صورك للطباعة\",\"min_images\":1,\"max_images\":4,\"max_size_mb\":5}}','1','1000','','2026-01-29 20:26:10','2026-01-29 20:26:10');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('22','prod_697b992989721','قلادة قلب مع نقش اسم','قلاده قلب الحب مع نقش ليزر حسب الطلب','16000','0','اكسسوارات','[\"products\\/697b992989221_1769707817.jpeg\",\"products\\/697b99298959e_1769707817.jpeg\",\"products\\/697b9adcc486b_1769708252.jpeg\",\"products\\/697b9ae75ba67_1769708263.jpeg\"]','0','1','available','10','0','0','0','0',NULL,'{\"extra_fields\":{\"enabled\":true,\"required\":true,\"groups\":[{\"label\":\"\",\"items\":[{\"type\":\"text\",\"label\":\"الكلمة المطلوبه للطباعة\",\"required\":true,\"max_length\":50,\"placeholder\":\"\"}]}]}}','1','1000','','2026-01-29 20:30:17','2026-01-30 05:25:22');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('23','prod_697b9d5f739e6','طباعه كفر','طباعه كفر حسب الطلب صوره او اسم او اقباس','10000','0','printing','[\"products\\/697b9d5f731cb_1769708895.jpeg\",\"products\\/697b9d5f7347e_1769708895.jpeg\",\"products\\/697b9d5f73747_1769708895.jpeg\",\"products\\/697b9d5f73942_1769708895.jpeg\"]','1','0','available','10','0','0','0','0',NULL,'{\"sizes\":{\"enabled\":true,\"required\":false,\"groups\":[],\"values\":[]},\"extra_fields\":{\"enabled\":true,\"required\":false,\"groups\":[{\"label\":\"\",\"items\":[{\"type\":\"text\",\"label\":\"اسم الهاتف\",\"required\":true,\"max_length\":50,\"placeholder\":\"\"},{\"type\":\"text\",\"label\":\"الاسم او الاقتباس المطلوبه للطباعة\",\"required\":true,\"max_length\":50,\"placeholder\":\"\"}]}]},\"custom_images\":{\"enabled\":true,\"required\":false,\"groups\":[{\"label\":\"\",\"min\":1,\"max\":1,\"max_size\":5,\"required\":true}],\"allowed_types\":[\"jpg\",\"jpeg\",\"png\",\"webp\"],\"label\":\"ارفع صورك للطباعة\",\"min_images\":1,\"max_images\":1,\"max_size_mb\":5}}','0','0','','2026-01-29 20:48:15','2026-01-29 20:49:29');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('24','prod_697b9f8adec64','بوكس قلادة لؤلؤ','بوكس يحتوي على قلادة اللؤلؤ مع المحار وورد وكيس هديه','8000','0','boxes','[\"products\\/697b9f8ade32a_1769709450.jpeg\",\"products\\/697b9f8ade641_1769709450.jpeg\",\"products\\/697b9f8ade8b8_1769709450.jpeg\",\"products\\/697b9f8adeb1b_1769709450.jpeg\"]','0','1','available','10','0','0','0','0',NULL,'[]','0','0','','2026-01-29 20:57:30','2026-01-30 05:24:59');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('25','prod_697ba479c87e4','نظارات','نظارات شمسيه','5000','0','مناظر','[\"products\\/697ba479c82f0_1769710713.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 21:18:33','2026-01-29 21:21:31');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('26','prod_697ba5127ae05','نظارات','نظارات شمسيه','5000','0','مناظر','[\"products\\/697ba5127ab32_1769710866.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 21:21:06','2026-01-29 21:22:39');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('27','prod_697ba55335a43','نظارات','نظارات شمسيه','5000','0','مناظر','[\"products\\/697ba55335800_1769710931.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 21:22:11','2026-01-29 21:22:11');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('28','prod_697bb9f3c2231','نظارات','نظارات شمسية','5000','0','مناظر','[\"products\\/697bb9f3c17a3_1769716211.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 22:50:11','2026-01-29 22:50:11');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('29','prod_697bba194acf5','نظارات','نظارات شمسية','5000','0','مناظر','[\"products\\/697bba194ab5d_1769716249.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 22:50:49','2026-01-29 22:50:49');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('30','prod_697bba3c3087e','نظارات','نظارات شمسية','5000','0','مناظر','[\"products\\/697bba3c3057b_1769716284.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 22:51:24','2026-01-29 22:51:24');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('32','prod_697bba93d18fd','نظارات','نظارات شمسي','5000','0','مناظر','[\"products\\/697bba93d16b0_1769716371.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 22:52:51','2026-01-29 22:52:51');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('33','prod_697bbab54b9b2','نظارات','نظارات شمسية','5000','0','مناظر','[\"products\\/697bbab54b809_1769716405.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 22:53:25','2026-01-29 22:53:25');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('34','prod_697bbadd775aa','نظارات','نظارات شمسية','5000','0','مناظر','[\"products\\/697bbadd773de_1769716445.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 22:54:05','2026-01-29 22:54:05');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('35','prod_697bbcb855825','ديكور/ مصباح انيق','ديكور/مصباح أنيق على شكل هلال وقلب طباعة جهتين تشغليه بطاريه او توصيل بالكهرباء','15000','0','decorations','[\"products\\/697bbcb854811_1769716920.jpeg\",\"products\\/697bbcb854f1c_1769716920.jpeg\",\"products\\/697bbcb85554c_1769716920.jpeg\"]','1','0','available','10','0','0','0','0',NULL,'{\"custom_images\":{\"enabled\":true,\"required\":true,\"groups\":[{\"label\":\"\",\"min\":1,\"max\":2,\"max_size\":5,\"required\":false}],\"allowed_types\":[\"jpg\",\"jpeg\",\"png\",\"webp\"],\"label\":\"ارفع صورك للطباعة\",\"min_images\":1,\"max_images\":2,\"max_size_mb\":5}}','1','1000','','2026-01-29 23:02:00','2026-01-29 23:02:00');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('36','prod_697bbe22a8768','بوكي صور','بوكي صور يحتوي على 10 صور','10000','0','printing','[\"products\\/697bbe22a8219_1769717282.jpeg\",\"products\\/697bbe22a858e_1769717282.jpeg\"]','1','0','available','10','0','0','0','0',NULL,'{\"custom_images\":{\"enabled\":true,\"required\":true,\"groups\":[{\"label\":\"\",\"min\":1,\"max\":10,\"max_size\":5,\"required\":false}],\"allowed_types\":[\"jpg\",\"jpeg\",\"png\",\"webp\"],\"label\":\"ارفع صورك للطباعة\",\"min_images\":1,\"max_images\":10,\"max_size_mb\":5}}','1','1000','','2026-01-29 23:08:02','2026-01-29 23:08:02');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('37','prod_697bc19dc17c0','ساعة فاخره','ساعة من LEIX مع بوكس','25000','35000','watches','[\"products\\/697bc19dc1659_1769718173.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 23:22:53','2026-01-29 23:22:53');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('38','prod_697bc1f42d116','ساعة فاخره','ساعة مع بوكس من LEIX','25000','35000','watches','[\"products\\/697bc1f42c92e_1769718260.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 23:24:20','2026-01-29 23:24:20');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('39','prod_697bc231c556b','ساعة فاخره','ساعة مع بوكس من LEIX','25000','35000','watches','[\"products\\/697bc231c53f3_1769718321.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 23:25:21','2026-01-29 23:25:21');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('40','prod_697bc2d11e2a9','ساعة فاخره','ساعة وبوكس من LEIX','25000','35000','watches','[\"products\\/697bc2d11e10d_1769718481.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 23:28:01','2026-01-29 23:28:01');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('41','prod_697bc36755cf7','ساعة فاخره','ساعة وبوكس من LEIX','25000','35000','watches','[\"products\\/697bc36755b11_1769718631.jpeg\",\"products\\/697bc399baeb9_1769718681.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 23:30:31','2026-01-29 23:31:21');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('42','prod_697bc4b72fbd7','بوكس نسائي','بوكس نسائي يحتوي على عطر حجم صغير وسبلاش ولوشن وجل شاور','10000','0','boxes','[\"products\\/697bc4b72f276_1769718967.jpeg\",\"products\\/697bc4b72f88d_1769718967.jpeg\"]','0','0','available','1','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 23:36:07','2026-01-30 19:39:59');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('43','prod_697bc55441d06','بوكس رجالي','بوكس رجالي يحتوي على لوشن وجل استحمام وسبلاش وعطر حجم صغير','10000','0','boxes','[\"products\\/697bc5544136d_1769719124.jpeg\",\"products\\/697bc554419c8_1769719124.jpeg\"]','0','0','available','2','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 23:38:44','2026-01-30 19:40:20');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('44','prod_697bc5b9f121e','بوكس نسائي','بوكس نسائي يحتوي على عطر حجم صغير وسبلاش وجل استحمام ولوشن جسم','10000','0','boxes','[\"products\\/697bc5b9f0b97_1769719225.jpeg\",\"products\\/697bc5b9f0fb0_1769719225.jpeg\"]','0','0','available','1','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 23:40:25','2026-01-30 19:39:42');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('45','prod_697bc9720d1c4','بوكس نسائي','بوكس نسائي يحتوي على سبلاش وعطر حجم صغير وجل استحمام ولوشن جسم','10000','0','boxes','[\"products\\/697bc9720bb2f_1769720178.jpeg\",\"products\\/697bc9720c6bf_1769720178.jpeg\"]','0','0','available','1','0','0','0','0',NULL,'[]','1','1000','','2026-01-29 23:56:18','2026-01-30 19:41:04');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('46','prod_697bcd8d2d40c','بوكس نسائي','بوكس نسائي يحتوي على سبلاش وعطر حجم صغير ولوشن وجل استحمام','10000','0','boxes','[\"products\\/697bcd8d2abc3_1769721229.jpeg\",\"products\\/697bcd8d2beb9_1769721229.jpeg\"]','0','0','available','1','0','0','0','0',NULL,'[]','1','1000','','2026-01-30 00:13:49','2026-01-30 19:38:32');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('47','prod_697bcdf8626e3','بوكس نسائي','بوكس نسائي يحتوي على سبلاش وعطر حجم صغير ولوشن جسم وجل استحمام','10000','0','boxes','[\"products\\/697bcdf8605aa_1769721336.jpeg\",\"products\\/697bcdf861609_1769721336.jpeg\"]','0','0','available','1','0','0','0','0',NULL,'[]','1','1000','','2026-01-30 00:15:36','2026-01-30 19:38:57');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('48','prod_697bceb60a544','بوكس رجالي','بوكس رجالي يحتوي على جل استحمام ولوشن جسم وعطر حجم صغير وسبلاش','10000','0','boxes','[\"products\\/697bceb608594_1769721526.jpeg\",\"products\\/697bceb6096ec_1769721526.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-30 00:18:46','2026-01-30 00:18:46');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('49','prod_697bd21c70c60','دومنه طباعة','بوكس دومنه مع طباعة صوره + اسم','25000','0','printing','[\"products\\/697bd21c7083f_1769722396.jpeg\",\"products\\/697bd21c70b7f_1769722396.jpeg\"]','1','0','available','10','0','0','0','0',NULL,'{\"extra_fields\":{\"enabled\":true,\"required\":false,\"groups\":[{\"label\":\"\",\"items\":[{\"type\":\"text\",\"label\":\"الاسم المطلوب للطباعة\",\"required\":false,\"max_length\":50,\"placeholder\":\"\"}]}]},\"custom_images\":{\"enabled\":true,\"required\":false,\"groups\":[{\"label\":\"\",\"min\":1,\"max\":1,\"max_size\":5,\"required\":false}],\"allowed_types\":[\"jpg\",\"jpeg\",\"png\",\"webp\"],\"label\":\"ارفع صورك للطباعة\",\"min_images\":1,\"max_images\":1,\"max_size_mb\":5}}','1','1000','','2026-01-30 00:33:16','2026-01-30 00:33:16');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('50','prod_697bd2ab7c49d','بوكس أريال مدريد','بوكس أريال مدريد يحتوي على كفر + كوب + مداليه طباعة','25000','0','boxes','[\"products\\/697bd2ab7bcda_1769722539.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-30 00:35:39','2026-01-30 00:38:36');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('51','prod_697bd316c927f','قلاده مع رساله','قلاده مع  كتابه رساله او اسم','16000','0','اكسسوارات','[\"products\\/697bd316c8c9c_1769722646.jpeg\",\"products\\/697bd316c90b7_1769722646.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-30 00:37:26','2026-01-30 05:23:51');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('52','prod_697bd4371768f','بوكس اريال مدريد او برشلونة','بوكس أريال مديري او برشلونة يحتوي على كوب وكفر','17000','0','boxes','[\"products\\/697bd43717234_1769722935.jpeg\",\"products\\/697bd43717510_1769722935.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'{\"extra_fields\":{\"enabled\":true,\"required\":false,\"groups\":[{\"label\":\"\",\"items\":[{\"type\":\"text\",\"label\":\"اسم الهاتف\",\"required\":true,\"max_length\":50,\"placeholder\":\"\"},{\"type\":\"text\",\"label\":\"برشلونة او أريال\",\"required\":false,\"max_length\":50,\"placeholder\":\"\"}]}]}}','1','2000','بوكس+تغليف','2026-01-30 00:42:15','2026-01-30 00:42:15');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('53','prod_697bd55b7c188','إطار فراشة حقيقيه','إطار يحتوي على. 2 فراشة محنطه حقيقيه','10000','15000','boxes','[\"products\\/697bd55b7beb8_1769723227.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-30 00:47:07','2026-01-30 00:47:07');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('54','prod_697bd60ccf34e','بوكس دفتر وقلم','بوكس يحتوي على قلم ودفتر  جلد حسب الطباعة  الاسم','3000','0','boxes','[\"products\\/697bd60cce9df_1769723404.jpeg\",\"products\\/697bd60ccf014_1769723404.jpeg\"]','1','0','available','10','0','0','0','0',NULL,'{\"extra_fields\":{\"enabled\":true,\"required\":false,\"groups\":[{\"label\":\"\",\"items\":[{\"type\":\"text\",\"label\":\"الطباعة المطلوبة على القلم\",\"required\":true,\"max_length\":50,\"placeholder\":\"\"},{\"type\":\"text\",\"label\":\"الطباعة المطلوبة على الدفتر\",\"required\":true,\"max_length\":50,\"placeholder\":\"\"}]}]}}','1','1000','','2026-01-30 00:50:04','2026-01-30 00:50:04');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('55','prod_697c1506b0e03','أسوار','أسوار فضي','5000','8000','اكسسوارات','[\"products\\/697c1506b0c36_1769739526.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-30 05:18:46','2026-01-30 05:18:46');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('56','prod_697c15407cd18','أسوار','اسوار نسائي ذهبي','5000','8000','اكسسوارات','[\"products\\/697c15407cb1d_1769739584.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-30 05:19:44','2026-01-30 05:23:21');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('57','prod_697c158397616','اسوار','اسوار نسائي فضي ناعم','5000','8000','اكسسوارات','[\"products\\/697c15839748e_1769739651.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-30 05:20:51','2026-01-30 05:20:51');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('58','prod_697c15b38ac46','اسوار','اسوار ناعم بلون الأسود والفضي','5000','8000','اكسسوارات','[\"products\\/697c15b38a993_1769739699.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-30 05:21:39','2026-01-30 05:21:39');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('59','prod_697c15edd525a','اسوار','اسوار ناعم بلون الذهبي والفضي','5000','8000','اكسسوارات','[\"products\\/697c15edd50c3_1769739757.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-01-30 05:22:37','2026-01-30 05:22:37');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('65','prod_697c17c6468c5','بوكس الوردة الطبيعية','بوكس داخل كيس هديه  يحتوي على وردة طبيعية ذات تفاصيل جميلة','8000','0','boxes','[\"products\\/697c17c646317_1769740230.jpeg\",\"products\\/697c17c646531_1769740230.jpeg\",\"products\\/697c17c6466c4_1769740230.jpeg\",\"products\\/697c17c646811_1769740230.jpeg\"]','0','0','available','7','0','0','0','0',NULL,'[]','0','0','','2026-01-30 05:30:30','2026-01-30 19:48:03');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('66','prod_697c1ade00f8a','بوكس كندر','بوكس يحتوي على ورد صناعي و5  كندر ودب صغير','12000','0','boxes','[\"products\\/697c1ade00de7_1769741022.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'{\"extra_fields\":{\"enabled\":true,\"required\":false,\"groups\":[{\"label\":\"\",\"items\":[{\"type\":\"text\",\"label\":\"الاقتباسه او الكلام المطلوب للطباعه (اختياري بتفعيل اضافه تغليف )\",\"required\":false,\"max_length\":50,\"placeholder\":\"\"}]}]}}','1','1000','اضافة أقباس طباعه او اي كتابة حسب الطلب','2026-01-30 05:43:42','2026-01-30 05:46:29');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('67','prod_697c1c36e33be','محفظة رجالية','محفظة رجالية أنيقة 💸تحتوي على تغليف داخلي وبوكس خاص بالمحفظة','15000','25000','محافظ','[\"products\\/697c1c36e2d2e_1769741366.jpeg\",\"products\\/697c1c36e316c_1769741366.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','إضافه تغليف خارجي للبوكس','2026-01-30 05:49:26','2026-01-30 06:02:06');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('68','prod_697c1c9cf36dc','محفظة رجالية','محفظة رجالية أنيقة 💸تحتوي على تغليف داخلي وبوكس خاص بالمحفظة','15000','25000','محافظ','[\"products\\/697c1c9cf3470_1769741468.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','إضافه تغليف خارجي البوكس','2026-01-30 05:51:08','2026-01-30 06:01:33');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('69','prod_697c1dcd3cd55','محفظة رجالية','محفظة رجالية أنيقة💸 تحتوي على تغليف داخلي وبوكس خاص بالمحفظة','15000','25000','محافظ','[\"products\\/697c1dcd3cb42_1769741773.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','إضافه تغليف خارجي للبوكس','2026-01-30 05:56:13','2026-01-30 06:01:01');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('70','prod_697c1e38e43ed','محفظة رجالية','محفظة رجالية أنيقة💸 تحتوي على تغليف داخلي وبوكس خاص بالمحفظة','15000','25000','محافظ','[\"products\\/697c1e38e413c_1769741880.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','إضافه تغليف خارجي للبوكس','2026-01-30 05:58:00','2026-01-30 05:58:00');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('71','prod_697c1e6ff3b6b','محفظة رجالية','محفظة رجالية أنيقة 💸تحتوي على تغليف داخلي وبوكس خاص بالمحفظة','15000','25000','محافظ','[\"products\\/697c1e6ff3923_1769741935.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','إضافه تغليف خارجي للبوكس','2026-01-30 05:58:55','2026-01-30 05:58:55');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('72','prod_697c1eae1baf5','محفظة رجالية','محفظة رجالية أنيقة 💸تحتوي على تغليف داخلي وبوكس خاص بالمحفظة','15000','25000','محافظ','[\"products\\/697c1eae1b87d_1769741998.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','إضافه تغليف خارجي للبوكس','2026-01-30 05:59:58','2026-01-30 05:59:58');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('73','prod_697c1feeb9883','محفظة رجالية','ساعة رجالية انيق مع بوكس 💸','7000','10000','محافظ','[\"products\\/697c1feeb9176_1769742318.jpeg\",\"products\\/697c1feeb9632_1769742318.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','إضافه تغليف خارجي للبوكس','2026-01-30 06:05:18','2026-01-30 06:05:18');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('74','prod_697cdffb8ac5b','بوكس نسائي','بوكس نسائي يحتوي على سبلاش وعطر وجل استحمام وعطر حجم صغير','10000','0','boxes','[\"products\\/697cdffb8965f_1769791483.jpeg\"]','0','0','available','1','0','0','0','0',NULL,'[]','0','0','','2026-01-30 19:44:43','2026-01-30 19:44:43');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('75','prod_6983378338fe4','دورايمون','دب دورايمون','7500','0','قسم_الدبب','[\"products\\/6983378338a19_1770207107.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 15:11:47','2026-02-04 15:11:47');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('76','prod_698337d02c5eb','بيكاتشو','دب بيكاتشو','7500','0','قسم_الدبب','[\"products\\/698337d02c313_1770207184.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 15:13:04','2026-02-04 15:22:42');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('77','prod_69833818a6e0e','كرومي','دب كرومي','7500','0','قسم_الدبب','[\"products\\/69833818a6be5_1770207256.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 15:14:16','2026-02-04 15:14:16');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('78','prod_6983384462ccc','كرومي','دب كرومي','7500','0','قسم_الدبب','[\"products\\/6983384462a6d_1770207300.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 15:15:00','2026-02-04 15:15:00');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('79','prod_69833937d6d97','بطوط (دونالد داك )','دب بطوط','7500','0','قسم_الدبب','[\"products\\/69833937d6ab5_1770207543.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 15:19:03','2026-02-04 15:19:03');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('80','prod_698339b3e7352','أرنب','أرنب وردي','7500','0','قسم_الدبب','[\"products\\/698339b3e7116_1770207667.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 15:21:07','2026-02-04 15:21:07');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('81','prod_698339f5cc993','ستيتش','دب ستيتش','7500','0','قسم_الدبب','[\"products\\/698339f5cc657_1770207733.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 15:22:13','2026-02-04 15:22:13');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('82','prod_69833a8fa5d63','تحفية دورايمون','زجاج قوي \r\nمع لد ضوئي.','8000','0','decorations','[\"products\\/69833a8fa5b84_1770207887.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 15:24:47','2026-02-04 15:24:47');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('83','prod_69833b7762dcd','بوكس دورايمون','بوكس دورايمون\r\nيحتوي على  دب و تحفية  وكوب وعطر وبوكس يحتوي على ورد صناعي','21000','26500','boxes','[\"products\\/69833b776263a_1770208119.jpeg\",\"products\\/69833b77629bb_1770208119.jpeg\",\"products\\/69833b7762c60_1770208119.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','إضافه تغليف داخلي للكوب و التحفيه','2026-02-04 15:28:39','2026-02-04 15:28:39');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('84','prod_69833ceebb90f','بوكس دواريمون 2','بوكس دورايمون يحتوي على  دب و بلورة مع موسيقى  و تحفيه و عطر وبوكس يحتوي على ورد صناعي','29000','31500','boxes','[\"products\\/69833ceebb5a9_1770208494.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','إضافه تغليف داخلي بلوره و تحفيه','2026-02-04 15:34:54','2026-02-04 15:34:54');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('85','prod_69833d6a2de03','بوكس دواريمون 2','بوكس دورايمون يحتوي على  دب و بلورة مع موسيقى  و تحفيه و عطر وبوكس يحتوي على ورد صناعي','29000','31500','boxes','[\"products\\/69833d6a2db9e_1770208618.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','إضافه تغليف داخلي بلوره و تحفيه','2026-02-04 15:36:58','2026-02-04 15:36:58');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('86','prod_69833f43c1332','بوكس احمر','بوكس بالون الأحمر  يحتوي على \r\nسكارف (رجالي/نسائي)   : يحمل علامة تجارية باسم Groppa مصنوع بجودة عالية ويُعد قطعة شتوية الناعم ليوفر الدفء والنعومة.\r\n\r\nدب بالون الأحمر \r\nباورة زجاجيه قبة من الزجاج الشفاف مثبتة على قاعدة بيضاء بداخلها مجسم صغير لشخصين (عاشقين) يقفان جنباً إلى جنب، وهي لمسة رمزية تعبر عن المودة تحتوي على أسلاك نحاسية دقيقة  تمنح توهجاً دافئاً وجمالياً عند تشغيلها\r\nبوكس يحتوي على تزيين وورد صناعي بلون الأحمر','20000','21000','boxes','[\"products\\/69833f43c0dee_1770209091.jpeg\",\"products\\/69833f43c11a1_1770209091.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','اضافة تغليف داخلي للبلوره','2026-02-04 15:44:51','2026-02-04 15:52:04');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('87','prod_69834099288ad','بوكس احمر 2','بوكس باللون الأحمر يحتوي على \r\n\r\nسكارف احمر ( رجالي / نسائي)\r\nيحمل علامة تجارية باسم Groppa\r\nوشاح ناعم بلون الأحمر ينتهي بأطراف مزينه ليعطي لمسه من الرقي والدفء \r\n\r\nبلوره تمثال لعروسين (شاب وفتاة) في وضعية رمانسية داخل قبة زجاجية مليئة بسائل شفاف وقطع صغيرة تحاكي الثلج \r\n\r\nدب بلون الأحمر \r\nبوكس يحتوي على زينه وورد احمر صناعي','22000','23000','boxes','[\"products\\/698340992841e_1770209433.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','إضافه تغليف داخلي للبلوره','2026-02-04 15:50:33','2026-02-04 15:50:33');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('88','prod_6983421c8e4b1','بلورة  زجاجيه مع موسيقى','بلوره تجسد مشهداً شاعرياً لطفل وطفلة في قارب صغير، مما يرمز للرحلة المشتركة والمودة\r\nعند التشغيل، تملأ جزيئات الثلج الأبيض اللامع البلورة، مع إضاءة LED خافتة لتعطي جواً هادئاً\r\nمزودة بآلية صوتية تعزف ألحاناً موسيقية هادئة تساعد على الاسترخاء','8000','0','decorations','[\"products\\/6983421c8e08e_1770209820.jpeg\",\"products\\/6983421c8e371_1770209820.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 15:57:00','2026-02-04 16:00:27');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('89','prod_698342c4460df','بلوره زجاجه مع موسيقى','بلوره بلون الأحمر مصممة كتحفة فنية للهدايا\r\nتحتوي على مجسم لزوجين بملابس رسمية (فستان أحمر وبدلة سوداء) محاطين بـ &quot;ثلج&quot; صناعي يتطاير عند تحريكها وتتضمن إضاءة ملونةوتحتوي على موسيقى هادئة تأتي القاعدة بلون أحمر زاهٍ مزينة بقلب مكتوب عليه عبارة &quot;Happy everyday&quot; ورسومات لورود وعجلات صغيرة تشبه العربة.','8000','0','decorations','[\"products\\/698342c445ef8_1770209988.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 15:59:48','2026-02-04 15:59:48');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('90','prod_69834328c5d7b','بلوره زجاجيه مع موسيقى','بلوره بلون الوردي الفاتح  تجسد مشهداً شاعرياً لطفل وطفلة في قارب صغير، مما يرمز للرحلة المشتركة والمودة\r\nعند التشغيل، تملأ جزيئات الثلج الأبيض اللامع البلورة، مع إضاءة LED خافتة لتعطي جواً هادئاً\r\nمزودة بآلية صوتية تعزف ألحاناً موسيقية هادئة تساعد على الاسترخاء','8000','0','decorations','[\"products\\/69834328c59aa_1770210088.jpeg\",\"products\\/69834328c5c47_1770210088.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 16:01:28','2026-02-04 16:01:28');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('91','prod_6983444ed73dd','ساعة رجاليه فاخره','ساعه بلون الفضي مع بوكس وكيس  هدايا خاص بالساعه هذه الساعة من العلامة التجارية Bestwin تتميز بتصميم &quot;نيو-كلاسيك&quot; يجمع بين الفخامة العصرية والطابع الرياضي الأنيق \r\nهي ساعة &quot;جوكر&quot; تناسب الملابس الرسمية (البدلات) وكذلك الملابس الكاجوال الأنيقة، وتعتبر خياراً ممتازاً كهدية بفضل صندوقها الفاخر الظاهر في الصورة','35000','0','watches','[\"products\\/6983444ed70f1_1770210382.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 16:06:22','2026-02-04 16:06:22');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('93','prod_698346b28b954','ساعه رجاليه فاخره','ساعه بلون فضي و ذهبي مع بوكس وكيس هدايا خاصه بالساعه هذه الساعة من العلامة التجارية Bestwin تتميز بتصميميجمع بين الفخامة العصرية والأناقة','35000','0','watches','[\"products\\/698346b28b663_1770210994.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','0','0','','2026-02-04 16:16:34','2026-02-04 16:16:34');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('94','prod_698346ec07d0e','ساعه رجاليه فاخره','ساعه رجاليه  بلون الفضي والذهبي  مع بوكس وكيس هدايا خاص بالساعه هذه الساعة من العلامة التجارية Bestwin تتميز بتصميم &quot;نيو-كلاسيك&quot; يجمع بين الفخامة العصرية والطابع الرياضي الأنيق\r\nهي ساعة &quot;جوكر&quot; تناسب الملابس الرسمية (البدلات) وكذلك الملابس الكاجوال الأنيقة، وتعتبر خياراً ممتازاً كهدية بفضل صندوقها الفاخر الظاهر في الصورة','35000','0','watches','[\"products\\/698346ec0798c_1770211052.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','0','0','','2026-02-04 16:17:32','2026-02-04 16:17:32');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('95','prod_698346ef7cffe','ساعه رجاليه فاخره','ساعه رجاليه  بلون الفضي والذهبي  مع بوكس وكيس هدايا خاص بالساعه هذه الساعة من العلامة التجارية Bestwin تتميز بتصميم &quot;نيو-كلاسيك&quot; يجمع بين الفخامة العصرية والطابع الرياضي الأنيق\r\nهي ساعة &quot;جوكر&quot; تناسب الملابس الرسمية (البدلات) وكذلك الملابس الكاجوال الأنيقة، وتعتبر خياراً ممتازاً كهدية بفضل صندوقها الفاخر الظاهر في الصورة','35000','0','watches','[\"products\\/698346ef7cdac_1770211055.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','0','0','','2026-02-04 16:17:35','2026-02-04 16:17:35');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('96','prod_698349b45eaf2','بلوره ملائكه الحب','تمثال صغير لزوجين أو ملاك  وتكون الزوجه حامل داخل قبة زجاجية مزودة بإضاءة LED','6000','0','decorations','[\"products\\/698349b45e8f0_1770211764.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 16:29:24','2026-02-04 16:29:24');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('97','prod_698349e397913','بلوره ملائكه الحب','تمثال صغير لزوجين أو ملاك يحملان الورد  داخل قبة زجاجية مزودة بإضاءة LED.','6000','0','decorations','[\"products\\/698349e397633_1770211811.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 16:30:11','2026-02-04 16:30:11');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('98','prod_69834a0c1fa2c','بلوره ملائكه الحب','تمثال صغير لزوجين أو ملاك داخل قبة زجاجية مزودة بإضاءة LED.','6000','0','decorations','[\"products\\/69834a0c1f7e7_1770211852.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 16:30:52','2026-02-04 16:30:52');
INSERT INTO `products` (`id`,`product_id`,`name`,`description`,`price`,`old_price`,`category`,`images`,`customizable`,`featured`,`status`,`stock`,`total_sold`,`monthly_sold`,`is_best_seller`,`is_trending`,`last_sale_at`,`options`,`packaging_enabled`,`packaging_price`,`packaging_description`,`created_at`,`updated_at`) VALUES ('99','prod_69834a3683f69','بلوره ملائكه الحب','تمثال صغير لزوجين أو ملاك يحملان طفل صغير  داخل قبة زجاجية مزودة بإضاءة LED.','6000','0','decorations','[\"products\\/69834a3683d4a_1770211894.jpeg\"]','0','0','available','10','0','0','0','0',NULL,'[]','1','1000','','2026-02-04 16:31:34','2026-02-04 16:31:34');

DROP TABLE IF EXISTS `remember_tokens`;
CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` enum('admin','staff') NOT NULL COMMENT 'نوع المستخدم',
  `staff_id` int(11) DEFAULT NULL COMMENT 'معرف الموظف (إذا كان موظف)',
  `selector` varchar(24) NOT NULL COMMENT 'معرف التوكن للبحث',
  `hashed_validator` varchar(64) NOT NULL COMMENT 'التوكن المشفر للتحقق',
  `expires_at` datetime NOT NULL COMMENT 'تاريخ انتهاء الصلاحية',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `idx_selector` (`selector`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_staff` (`staff_id`),
  CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL COMMENT 'معرف المنتج المرتبط',
  `customer_name` varchar(100) DEFAULT 'عميل' COMMENT 'اسم العميل',
  `rating` int(11) NOT NULL COMMENT 'التقييم من 1 إلى 5',
  `comment` text DEFAULT NULL COMMENT 'تعليق العميل',
  `is_visible` tinyint(1) DEFAULT 1 COMMENT '1=ظاهر، 0=مخفي',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'تاريخ الإضافة',
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_is_visible` (`is_visible`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_rating` (`rating`),
  CONSTRAINT `chk_rating` CHECK (`rating` >= 1 and `rating` <= 5)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reviews` (`id`,`product_id`,`customer_name`,`rating`,`comment`,`is_visible`,`created_at`) VALUES ('1','5','عميل','5','','1','2026-02-06 15:32:39');
INSERT INTO `reviews` (`id`,`product_id`,`customer_name`,`rating`,`comment`,`is_visible`,`created_at`) VALUES ('2','5','عميل','5','','1','2026-02-06 15:35:02');

DROP TABLE IF EXISTS `sales_log`;
CREATE TABLE `sales_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` varchar(50) NOT NULL COMMENT 'معرف المنتج',
  `order_id` int(11) NOT NULL COMMENT 'معرف الطلب',
  `quantity` int(11) DEFAULT 1 COMMENT 'الكمية',
  `sale_amount` decimal(10,2) NOT NULL COMMENT 'مبلغ البيع',
  `sale_date` date NOT NULL COMMENT 'تاريخ البيع',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_date` (`sale_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `security_log`;
CREATE TABLE `security_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL COMMENT 'نوع الحدث',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'عنوان IP',
  `user_agent` text DEFAULT NULL COMMENT 'معلومات المتصفح',
  `username` varchar(100) DEFAULT NULL COMMENT 'اسم المستخدم',
  `message` text DEFAULT NULL COMMENT 'الرسالة',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'بيانات إضافية' CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL COMMENT 'مفتاح الإعداد',
  `setting_value` text DEFAULT NULL COMMENT 'قيمة الإعداد',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`updated_at`) VALUES ('1','instagram','sur._prises','2026-01-20 01:09:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`updated_at`) VALUES ('2','instagram_url','https://instagram.com/sur._prises','2026-01-20 01:09:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`updated_at`) VALUES ('3','instagram_dm','https://ig.me/m/sur._prises','2026-01-20 01:09:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`updated_at`) VALUES ('4','delivery_price','5000','2026-01-20 01:09:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`updated_at`) VALUES ('5','currency','د.ع','2026-01-20 01:09:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`updated_at`) VALUES ('6','site_name','Surprise!','2026-01-20 01:09:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`updated_at`) VALUES ('7','site_description','متجر الهدايا الفاخرة في العراق','2026-01-20 01:09:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`updated_at`) VALUES ('8','privacy_policy','نحن في متجر Surprise! نلتزم بحماية خصوصيتك وبياناتك الشخصية.','2026-01-20 01:09:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`updated_at`) VALUES ('9','terms','الشروط والأحكام لمتجر Surprise!','2026-01-20 01:09:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`updated_at`) VALUES ('10','telegram_bot_token','','2026-01-20 01:09:53');
INSERT INTO `settings` (`id`,`setting_key`,`setting_value`,`updated_at`) VALUES ('11','telegram_chat_id','','2026-01-20 01:09:53');

DROP TABLE IF EXISTS `staff`;
CREATE TABLE `staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT 'اسم المستخدم للدخول',
  `password` varchar(255) NOT NULL COMMENT 'كلمة المرور مشفرة',
  `first_name` varchar(100) NOT NULL COMMENT 'الاسم الأول',
  `last_name` varchar(100) NOT NULL COMMENT 'الاسم الأخير',
  `job_title` varchar(100) DEFAULT NULL COMMENT 'المسمى الوظيفي',
  `governorate` varchar(100) DEFAULT NULL COMMENT 'المحافظة',
  `district` varchar(100) DEFAULT NULL COMMENT 'المنطقة',
  `neighborhood` varchar(100) DEFAULT NULL COMMENT 'الحي',
  `address` text DEFAULT NULL COMMENT 'العنوان التفصيلي',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'صلاحيات الموظف' CHECK (json_valid(`permissions`)),
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1=نشط، 0=معطل',
  `last_login` datetime DEFAULT NULL COMMENT 'آخر تسجيل دخول',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `v_monthly_sales`;
;


DROP TABLE IF EXISTS `v_product_performance`;
;

INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('1','prod_696eabb577719','طقم ساعة رولكس فاخر مع حفر الاسم','watches','55000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('2','prod_696eabb578973','طقم رولكس VIP الكامل','watches','45000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('3','prod_696eabb578ef1','قبة زجاجية مضيئة مخصصة بالاسم','decorations','16000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('4','prod_696eabb57a0ee','كوب سيراميك مطبوع - تصميم أنمي','printing','7000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('5','prod_696eabb57bf89','بوكس الأركيلة الفخم','boxes','18000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('6','prod_696eabb57c640','أسطوانة زهور طبيعية مضيئة','decorations','8000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('7','prod_696eabb57df6d','قبة طلب الزواج المضيئة','decorations','8000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('8','prod_696eabb57e5d4','تحفة الهلال المضيئة - I Love You','decorations','35000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('9','prod_696eabb57f178','بوكيه شوكولاتة كندر مع الورد','boxes','38000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('12','prod_696eabb580b57','فانوس الأم المضيء - أمي الغالية','decorations','6000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('13','prod_696eabb582899','بوكس هدايا رومانسي متكامل','boxes','16000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('14','prod_696eabb582d3b','كرة ثلج Love Story الموسيقية','decorations','8000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('15','prod_696eabb5836b6','فانوس كيوبيد المضيء - I Love You','decorations','8000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('16','prod_696eabb5846c6','بوكس يونيكورن الفاخر','boxes','85000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('17','prod_696eabb5865af','ألبوم صور الذكريات الفاخر','printing','15000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('19','prod_697b8f4830d81','فنجان طباعة','printing','12000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('21','prod_697b983217c95','إطار صور دوار','decorations','15000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('22','prod_697b992989721','قلادة قلب مع نقش اسم','اكسسوارات','16000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('23','prod_697b9d5f739e6','طباعه كفر','printing','10000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('24','prod_697b9f8adec64','بوكس قلادة لؤلؤ','boxes','8000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('25','prod_697ba479c87e4','نظارات','مناظر','5000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('26','prod_697ba5127ae05','نظارات','مناظر','5000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('27','prod_697ba55335a43','نظارات','مناظر','5000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('28','prod_697bb9f3c2231','نظارات','مناظر','5000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('29','prod_697bba194acf5','نظارات','مناظر','5000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('30','prod_697bba3c3087e','نظارات','مناظر','5000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('32','prod_697bba93d18fd','نظارات','مناظر','5000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('33','prod_697bbab54b9b2','نظارات','مناظر','5000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('34','prod_697bbadd775aa','نظارات','مناظر','5000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('35','prod_697bbcb855825','ديكور/ مصباح انيق','decorations','15000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('36','prod_697bbe22a8768','بوكي صور','printing','10000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('37','prod_697bc19dc17c0','ساعة فاخره','watches','25000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('38','prod_697bc1f42d116','ساعة فاخره','watches','25000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('39','prod_697bc231c556b','ساعة فاخره','watches','25000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('40','prod_697bc2d11e2a9','ساعة فاخره','watches','25000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('41','prod_697bc36755cf7','ساعة فاخره','watches','25000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('42','prod_697bc4b72fbd7','بوكس نسائي','boxes','10000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('43','prod_697bc55441d06','بوكس رجالي','boxes','10000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('44','prod_697bc5b9f121e','بوكس نسائي','boxes','10000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('45','prod_697bc9720d1c4','بوكس نسائي','boxes','10000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('46','prod_697bcd8d2d40c','بوكس نسائي','boxes','10000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('47','prod_697bcdf8626e3','بوكس نسائي','boxes','10000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('48','prod_697bceb60a544','بوكس رجالي','boxes','10000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('49','prod_697bd21c70c60','دومنه طباعة','printing','25000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('50','prod_697bd2ab7c49d','بوكس أريال مدريد','boxes','25000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('51','prod_697bd316c927f','قلاده مع رساله','اكسسوارات','16000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('52','prod_697bd4371768f','بوكس اريال مدريد او برشلونة','boxes','17000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('53','prod_697bd55b7c188','إطار فراشة حقيقيه','boxes','10000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('54','prod_697bd60ccf34e','بوكس دفتر وقلم','boxes','3000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('55','prod_697c1506b0e03','أسوار','اكسسوارات','5000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('56','prod_697c15407cd18','أسوار','اكسسوارات','5000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('57','prod_697c158397616','اسوار','اكسسوارات','5000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('58','prod_697c15b38ac46','اسوار','اكسسوارات','5000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('59','prod_697c15edd525a','اسوار','اكسسوارات','5000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('65','prod_697c17c6468c5','بوكس الوردة الطبيعية','boxes','8000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('66','prod_697c1ade00f8a','بوكس كندر','boxes','12000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('67','prod_697c1c36e33be','محفظة رجالية','محافظ','15000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('68','prod_697c1c9cf36dc','محفظة رجالية','محافظ','15000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('69','prod_697c1dcd3cd55','محفظة رجالية','محافظ','15000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('70','prod_697c1e38e43ed','محفظة رجالية','محافظ','15000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('71','prod_697c1e6ff3b6b','محفظة رجالية','محافظ','15000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('72','prod_697c1eae1baf5','محفظة رجالية','محافظ','15000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('73','prod_697c1feeb9883','محفظة رجالية','محافظ','7000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('74','prod_697cdffb8ac5b','بوكس نسائي','boxes','10000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('75','prod_6983378338fe4','دورايمون','قسم_الدبب','7500','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('76','prod_698337d02c5eb','بيكاتشو','قسم_الدبب','7500','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('77','prod_69833818a6e0e','كرومي','قسم_الدبب','7500','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('78','prod_6983384462ccc','كرومي','قسم_الدبب','7500','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('79','prod_69833937d6d97','بطوط (دونالد داك )','قسم_الدبب','7500','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('80','prod_698339b3e7352','أرنب','قسم_الدبب','7500','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('81','prod_698339f5cc993','ستيتش','قسم_الدبب','7500','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('82','prod_69833a8fa5d63','تحفية دورايمون','decorations','8000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('83','prod_69833b7762dcd','بوكس دورايمون','boxes','21000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('84','prod_69833ceebb90f','بوكس دواريمون 2','boxes','29000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('85','prod_69833d6a2de03','بوكس دواريمون 2','boxes','29000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('86','prod_69833f43c1332','بوكس احمر','boxes','20000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('87','prod_69834099288ad','بوكس احمر 2','boxes','22000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('88','prod_6983421c8e4b1','بلورة  زجاجيه مع موسيقى','decorations','8000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('89','prod_698342c4460df','بلوره زجاجه مع موسيقى','decorations','8000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('90','prod_69834328c5d7b','بلوره زجاجيه مع موسيقى','decorations','8000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('91','prod_6983444ed73dd','ساعة رجاليه فاخره','watches','35000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('93','prod_698346b28b954','ساعه رجاليه فاخره','watches','35000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('94','prod_698346ec07d0e','ساعه رجاليه فاخره','watches','35000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('95','prod_698346ef7cffe','ساعه رجاليه فاخره','watches','35000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('96','prod_698349b45eaf2','بلوره ملائكه الحب','decorations','6000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('97','prod_698349e397913','بلوره ملائكه الحب','decorations','6000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('98','prod_69834a0c1fa2c','بلوره ملائكه الحب','decorations','6000','0','0','0','0','0');
INSERT INTO `v_product_performance` (`id`,`product_id`,`name`,`category`,`price`,`total_sold`,`monthly_sold`,`total_revenue`,`is_best_seller`,`is_trending`) VALUES ('99','prod_69834a3683f69','بلوره ملائكه الحب','decorations','6000','0','0','0','0','0');

DROP TABLE IF EXISTS `v_product_reviews`;
;

INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('5','بوكس الأركيلة الفخم','2','5.0','2');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('41','ساعة فاخره','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('4','كوب سيراميك مطبوع - تصميم أنمي','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('78','كرومي','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('32','نظارات','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('69','محفظة رجالية','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('55','أسوار','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('22','قلادة قلب مع نقش اسم','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('93','ساعه رجاليه فاخره','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('46','بوكس نسائي','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('9','بوكيه شوكولاتة كندر مع الورد','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('83','بوكس دورايمون','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('37','ساعة فاخره','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('74','بوكس نسائي','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('98','بلوره ملائكه الحب','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('65','بوكس الوردة الطبيعية','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('27','نظارات','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('51','قلاده مع رساله','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('16','بوكس يونيكورن الفاخر','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('88','بلورة  زجاجيه مع موسيقى','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('42','بوكس نسائي','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('79','بطوط (دونالد داك )','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('33','نظارات','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('70','محفظة رجالية','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('56','أسوار','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('23','طباعه كفر','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('94','ساعه رجاليه فاخره','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('47','بوكس نسائي','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('12','فانوس الأم المضيء - أمي الغالية','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('84','بوكس دواريمون 2','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('38','ساعة فاخره','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('1','طقم ساعة رولكس فاخر مع حفر الاسم','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('75','دورايمون','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('99','بلوره ملائكه الحب','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('66','بوكس كندر','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('28','نظارات','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('52','بوكس اريال مدريد او برشلونة','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('17','ألبوم صور الذكريات الفاخر','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('89','بلوره زجاجه مع موسيقى','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('43','بوكس رجالي','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('6','أسطوانة زهور طبيعية مضيئة','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('80','أرنب','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('34','نظارات','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('71','محفظة رجالية','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('57','اسوار','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('24','بوكس قلادة لؤلؤ','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('95','ساعه رجاليه فاخره','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('48','بوكس رجالي','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('13','بوكس هدايا رومانسي متكامل','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('85','بوكس دواريمون 2','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('39','ساعة فاخره','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('2','طقم رولكس VIP الكامل','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('76','بيكاتشو','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('67','محفظة رجالية','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('29','نظارات','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('53','إطار فراشة حقيقيه','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('19','فنجان طباعة','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('90','بلوره زجاجيه مع موسيقى','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('44','بوكس نسائي','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('7','قبة طلب الزواج المضيئة','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('81','ستيتش','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('35','ديكور/ مصباح انيق','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('72','محفظة رجالية','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('58','اسوار','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('25','نظارات','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('96','بلوره ملائكه الحب','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('49','دومنه طباعة','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('14','كرة ثلج Love Story الموسيقية','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('86','بوكس احمر','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('40','ساعة فاخره','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('3','قبة زجاجية مضيئة مخصصة بالاسم','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('77','كرومي','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('68','محفظة رجالية','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('30','نظارات','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('54','بوكس دفتر وقلم','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('21','إطار صور دوار','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('91','ساعة رجاليه فاخره','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('45','بوكس نسائي','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('8','تحفة الهلال المضيئة - I Love You','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('82','تحفية دورايمون','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('36','بوكي صور','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('73','محفظة رجالية','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('59','اسوار','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('26','نظارات','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('97','بلوره ملائكه الحب','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('50','بوكس أريال مدريد','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('15','فانوس كيوبيد المضيء - I Love You','0',NULL,'0');
INSERT INTO `v_product_reviews` (`product_id`,`product_name`,`review_count`,`avg_rating`,`visible_reviews`) VALUES ('87','بوكس احمر 2','0',NULL,'0');

SET FOREIGN_KEY_CHECKS = 1;
