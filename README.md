<div align="center">

# 🎁 Surprise! Store

### Full-Stack E-Commerce Platform

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)

_A modern, secure, and DSGVO-compliant e-commerce platform built for the Iraqi market._

</div>

---

## 📖 About The Project

**Surprise! Store** is a production-grade, full-stack e-commerce platform designed to deliver a seamless online shopping experience for customers in the Iraqi market. Built from the ground up with **PHP** and **MySQL**, the platform addresses the unique challenges of regional e-commerce — including RTL (Right-to-Left) interface design, localized payment methods, and Arabic-first user experience.

The project empowers small-to-medium retailers with a professional digital storefront, eliminating the need for expensive third-party SaaS solutions while maintaining enterprise-level security standards and DSGVO (GDPR) compliance for international data protection.

---

## ✨ Key Features

- 🛍️ **User-Friendly Shopping Interface** — Responsive, RTL-optimized storefront with intuitive navigation and product discovery
- 📂 **Dynamic Product Categories** — Organized product catalog with filtering, search suggestions, and category management
- 🛒 **Shopping Cart System** — Persistent cart with real-time price calculation and coupon code support
- 💳 **Secure Checkout Flow** — Multi-step checkout supporting Cash on Delivery, Mastercard, ZainCash, and Asia Credit
- 📦 **Order Tracking** — Real-time order status tracking with Telegram notifications for instant order alerts
- ⭐ **Product Reviews & Ratings** — Customer review system with moderation capabilities
- ❤️ **Wishlist** — Save-for-later functionality for enhanced user engagement
- 📊 **Administrative Dashboard** — Full-featured admin panel for inventory management, sales reports, order processing, and staff management
- 👥 **Staff & Permissions** — Role-based access control with multi-staff support
- 🔐 **Two-Factor Authentication (2FA)** — TOTP-based 2FA with device trust management for admin security
- 🔒 **Security Hardened** — CSRF protection, brute-force prevention, rate limiting, secure session handling, and Content Security Policy headers
- 📱 **Progressive Web App (PWA)** — Offline-capable with service worker and app manifest
- 🔍 **SEO Optimized** — Structured data (JSON-LD), dynamic sitemaps, and semantic HTML for search engine visibility

---

## 🏗️ Architecture

The codebase follows a **clean, modular structure** with clear separation of concerns:

```
surprise/
├── includes/          # Core engine: config, security, functions, SEO, TOTP
├── admin/             # Administrative dashboard & staff management
├── api/               # RESTful API endpoints (orders, search, stock, reviews)
├── js/                # Client-side logic (cart, wishlist, admin UI)
├── css/               # Responsive stylesheets with RTL support
├── data/              # Configuration data & logs (git-ignored)
└── images/            # Static assets & user uploads
```

**Security-First Design:**

- All database interactions use **PDO prepared statements** — zero SQL injection surface
- CSRF tokens validated on every state-changing request
- Admin sessions protected by **2FA**, device fingerprinting, and IP-based trust verification
- Rate-limited login with automatic lockout and security event logging
- **DSGVO (GDPR) compliant** — privacy policy, data handling transparency, and secure credential storage

---

## 📸 Screenshots

> _UI showcases and visual features of the platform._
> _(Add your image links here using HTML <img width="800" src="..." /> tags)_

## ⚖️ License & Intellectual Property

> **⚠️ Proprietary / Showcase Only**
> This repository serves primarily as a technical portfolio piece. The architecture, concepts, and custom source code are proprietary. Unauthorized commercial use, modification, or distribution is strictly prohibited.

## 👤 Author & Contact

**Aseel Marwan Kheder**
_Full-Stack Softwareentwickler | Experte für DSGVO-konforme Plattformen & Native Apps_

[![LinkedIn](https://img.shields.io/badge/LinkedIn-0A66C2?style=for-the-badge&logo=linkedin&logoColor=white)](https://www.linkedin.com/in/aseel-marwan-kheder-36b17033b/)
[![GitHub](https://img.shields.io/badge/GitHub-181717?style=for-the-badge&logo=github&logoColor=white)](https://github.com/aseel7marwan)

📧 **Email:** [aseel.marwan.kheder@gmail.com](mailto:aseel.marwan.kheder@gmail.com)
