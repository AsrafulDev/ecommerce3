# 🏪 CurlWare Ecommerce — Client Quotation & Feature Matrix

> **Prepared For:** Client Proposal / RFP Response  
> **Platform:** Laravel 12 · PHP 8.2 · MySQL · Bootstrap  
> **Delivery Model:** Licensed Source Code + 1 Year Support  
> **Date:** July 11, 2026

---

## 📋 Executive Summary

CurlWare is a **production-ready, enterprise-grade eCommerce platform** built on Laravel 12 with **350+ built-in features** across 20 business modules. It supports B2C, B2B (wholesale), POS, multi-payment, multi-courier, mobile API, and comprehensive back-office operations — eliminating the need for 15+ separate SaaS subscriptions.

---

## 📊 Module Summary Table

| # | Module | Features | Type | Key Capability |
|---|--------|----------|------|----------------|
| 1 | **Product & Catalog** | 30+ | 🟢 Core | 3-level categories, variants, wholesale, digital, barcode |
| 2 | **Order Management** | 35+ | 🟢 Core | 15-stage workflow, returns, fraud detection, bulk ops |
| 3 | **Payment Gateways** | 4 gateways | 🟢 Core | bKash, Shurjopay, UddoktaPay, AamarPay + COD |
| 4 | **Stock & Inventory** | 15+ | 🟡 Advanced | FIFO/LIFO/Average costing, batch tracking, COGS, valuation |
| 5 | **Shipping & Logistics** | 12+ | 🟡 Advanced | RedX + Pathao courier, district rates, webhook tracking |
| 6 | **Purchase & Supplier** | 20+ | 🟡 Advanced | Procurement lifecycle, supplier payments, returns, exports |
| 7 | **Financial Management** | 12+ | 🟡 Advanced | Fund tracking, expenses, P&L, audit logs, exports |
| 8 | **CRM & HR** | 18+ | 🔵 Premium | Employee DB, attendance, leave, salary, bonus |
| 9 | **Customer Experience** | 15+ | 🟢 Core | OTP auth, portal, live search, reviews, multi-language |
| 10 | **Content & SEO** | 12+ | 🟢 Core | Blog, CMS pages, banners, popups, sitemap, SEO settings |
| 11 | **Theme & Layout Builder** | 20+ | 🔵 Premium | 15+ themes, drag-drop builder, header/footer editor |
| 12 | **Marketing & Analytics** | 16+ | 🔵 Premium | FB Pixel/CAPI, TikTok, GTM, GA4, ads dashboard, SMS |
| 13 | **Point of Sale (POS)** | 15+ | 🟡 Advanced | Barcode scanning, hold cart, unified inventory |
| 14 | **Mobile App API** | 25+ | 🔵 Premium | Flutter-ready REST API with Sanctum auth |
| 15 | **Security & Admin** | 18+ | 🟢 Core | RBAC, IP block, backup/restore, auto-update, cron |
| 16 | **Demo Presets** | 5 presets | 🟢 Core | One-click import: Electronics, Fashion, Grocery, Beauty, Multi |
| 17 | **Reports & BI** | 8+ | 🟡 Advanced | Orders, purchases, expenses, stock, P&L, all exportable |

| Legend | Meaning |
|--------|---------|
| 🟢 Core | Included in all tiers — essential eCommerce operations |
| 🟡 Advanced | Included in Business & Enterprise — operational efficiency |
| 🔵 Premium | Included in Enterprise — competitive advantage features |

---

## 🧩 Module-Wise Feature Table

### MODULE 1 — PRODUCT & CATALOG MANAGEMENT

| # | Feature | Description | Business Value |
|---|---------|-------------|----------------|
| 1.1 | 3-Level Category Tree | Category → Subcategory → Childcategory hierarchy | Organize unlimited products logically |
| 1.2 | Product CRUD | Full product lifecycle with images, pricing, SEO | Complete product control |
| 1.3 | Product Variants | Color + Size combinations with per-variant pricing & stock | Sell configurable products (e.g., T-shirts by size/color) |
| 1.4 | Wholesale / B2B | Separate wholesale catalog, bulk pricing tiers, min. qty | Serve business buyers alongside retail |
| 1.5 | Digital Products | Secure download links, download limits, expiry dates | Sell eBooks, software, courses |
| 1.6 | Product Approval Workflow | Pending → Approve/Reject with notes | Multi-vendor or quality-control ready |
| 1.7 | Flash Sales & Hot Deals | Timed promotions with countdown timers | Drive urgency & conversion |
| 1.8 | Campaign Management | Landing pages with custom banners & linked products | Seasonal & marketing campaigns |
| 1.9 | Coupon / Discount System | Fixed amount or percentage codes, min. purchase rules | Boost sales with promo codes |
| 1.10 | Brand Management | Brand pages with logo, product association | Brand-based shopping experience |
| 1.11 | Barcode System | Auto-generate & print product barcodes | In-store & warehouse scanning |

---

### MODULE 2 — ORDER MANAGEMENT (System-Driven Workflow)

| # | Feature | Description | Business Value |
|---|---------|-------------|----------------|
| 2.1 | 15-Stage Order Lifecycle | Pending → Confirmed → Picking → Packing → Shipped → Delivered → Completed | Full warehouse workflow visibility |
| 2.2 | Return/Refund Workflow | Return Request → Approve → Receive → Close | Professional returns handling |
| 2.3 | Order Notes Timeline | Timestamped notes from admin, system, courier, customer | Complete order audit trail |
| 2.4 | Independent Payment Tracking | Payment status separate from order status | Accurate financial reconciliation |
| 2.5 | Multi-Type Orders | Online, POS, COD, Prepaid — all in one system | Unified order management |
| 2.6 | Bulk Operations | Bulk delete, bulk courier assign, bulk status update | Save time on high-volume days |
| 2.7 | Fraud Detection | Manual & automated fraud flagging | Reduce chargebacks & losses |
| 2.8 | Duplicate Order Detection | Auto-detect & flag duplicate orders | Prevent fulfillment errors |
| 2.9 | Abandoned Cart Recovery | Capture incomplete checkouts for follow-up | Recover lost revenue |
| 2.10 | PDF Invoice Generation | Professional invoices with barcode, branding | Customer-ready documentation |
| 2.11 | Order Tracking (Public) | Customers track by invoice ID without login | Reduce support tickets |

---

### MODULE 3 — PAYMENT GATEWAY INTEGRATION

| # | Gateway | Method | Use Case |
|---|---------|--------|----------|
| 3.1 | **bKash** | Mobile Wallet | #1 mobile payment in Bangladesh |
| 3.2 | **Shurjopay** | Unified Gateway | Card, bank, mobile banking |
| 3.3 | **UddoktaPay** | Multi-bank Gateway | Bank transfer & mobile banking |
| 3.4 | **AamarPay** | Payment Aggregator | Cards, wallets, net banking |
| 3.5 | **Cash on Delivery** | COD | Trust-based offline payment |
| 3.6 | Payment Status Workflow | Pending → Paid → Refunded → Partial Refund | Complete financial tracking |

---

### MODULE 4 — STOCK & INVENTORY MANAGEMENT

| # | Feature | Description | Business Value |
|---|---------|-------------|----------------|
| 4.1 | Batch Tracking | Each purchase tracked as separate batch | Traceability & expiry management |
| 4.2 | FIFO / LIFO / Average Costing | Selectable costing method per product | Accurate profit calculation |
| 4.3 | COGS Reporting | Cost of Goods Sold by period | True profitability analysis |
| 4.4 | Stock Valuation | Current inventory value at cost | Balance sheet accuracy |
| 4.5 | Stock Adjustments | Manual add/reduce/correct with reason & audit log | Handle shrinkage, damage, recounts |
| 4.6 | Low Stock Dashboard | Visual overview of stock levels | Prevent stockouts |
| 4.7 | Expiry Date Tracking | MFG date + Expiry date per batch | FEFO/FIFO picking for perishables |
| 4.8 | Variant-Level Stock | Stock tracked per color/size variant | Accurate variant availability |

---

### MODULE 5 — SHIPPING & LOGISTICS

| # | Feature | Description | Business Value |
|---|---------|-------------|----------------|
| 5.1 | District-Based Shipping | Configurable rates for all Bangladesh districts | Accurate delivery pricing |
| 5.2 | **RedX Courier Integration** | Auto parcel creation, area lookup, webhook tracking | Automated last-mile delivery |
| 5.3 | **Pathao Courier Integration** | City/zone lookup, token-based API | Multi-courier flexibility |
| 5.4 | Bulk Courier Assignment | Assign courier to multiple orders at once | Save dispatch time |
| 5.5 | Courier Webhook | Real-time status updates from courier | Live tracking without manual entry |
| 5.6 | Per-Product Free Shipping | Toggle free delivery per product | Promotional flexibility |
| 5.7 | Multi-Courier Support | Choose courier per order | Optimize delivery cost & speed |

---

### MODULE 6 — PURCHASE & SUPPLIER MANAGEMENT

| # | Feature | Description | Business Value |
|---|---------|-------------|----------------|
| 6.1 | Purchase Order CRUD | Full procurement lifecycle with line items | Organized purchasing |
| 6.2 | Supplier Management | Supplier profiles with balance tracking | Vendor relationship management |
| 6.3 | Supplier Payments | Record payments against purchases, track dues | Accurate accounts payable |
| 6.4 | Supplier Returns | Return defective items to suppliers | Inventory quality control |
| 6.5 | Purchase Invoice PDF | Downloadable purchase invoices | Documentation & audit |
| 6.6 | Purchase Export | Export purchase data to Excel/CSV | Accounting integration |

---

### MODULE 7 — FINANCIAL MANAGEMENT

| # | Feature | Description | Business Value |
|---|---------|-------------|----------------|
| 7.1 | Fund Management | Track all cash inflows & outflows | Complete cash flow visibility |
| 7.2 | Expense Management | Record & categorize all business expenses | Tax-ready expense tracking |
| 7.3 | Profit & Loss Report | Revenue minus costs, real-time | Know if you're making money |
| 7.4 | Financial Exports | Export fund, expense, purchase data | Easy handoff to accountant |
| 7.5 | Transaction Audit Logs | Every financial change timestamped & attributed | Fraud prevention & audit ready |

---

### MODULE 8 — CRM & HUMAN RESOURCES

| # | Feature | Description | Business Value |
|---|---------|-------------|----------------|
| 8.1 | Employee Database | Full employee profiles | Centralized HR records |
| 8.2 | Attendance Tracking | Daily attendance with bulk marking | Payroll accuracy |
| 8.3 | Leave Management | Apply → Approve/Reject workflow | Organized leave tracking |
| 8.4 | Salary Calculation | Individual & bulk salary computation | Streamlined payroll |
| 8.5 | Bonus Management | Bonus requests with approval & payment tracking | Employee incentive management |
| 8.6 | Salary Payment Records | Track all payments against calculated salary | Payroll audit trail |

---

### MODULE 9 — CUSTOMER EXPERIENCE

| # | Feature | Description | Business Value |
|---|---------|-------------|----------------|
| 9.1 | OTP-Verified Registration | Phone/email OTP for signup & password reset | Secure customer onboarding |
| 9.2 | Customer Portal | Order history, invoices, profile management | Self-service reduces support load |
| 9.3 | Live Search | AJAX real-time product search | Faster product discovery |
| 9.4 | Quick View | Modal product preview without page navigation | Better browsing experience |
| 9.5 | Product Reviews & Ratings | Customer-submitted reviews with admin approval | Social proof for conversions |
| 9.6 | Support Ticket System | Customers submit complaints, track resolution | Professional customer support |
| 9.7 | Newsletter Subscription | Email capture for marketing | Build marketing lists |
| 9.8 | Multi-Language | English + Bengali (add more anytime) | Serve diverse customer base |
| 9.9 | Responsive Design | Mobile-optimized storefront | Capture mobile shoppers |
| 9.10 | Dynamic Theme CSS | Color/theme changes apply instantly | Brand customization |

---

### MODULE 10 — CONTENT & SEO

| # | Feature | Description | Business Value |
|---|---------|-------------|----------------|
| 10.1 | Blog System | Full blog with categories, SEO-friendly slugs | Content marketing & SEO |
| 10.2 | Custom Pages | Unlimited CMS pages (About, Privacy, Terms, etc.) | Complete content control |
| 10.3 | Banner Management | Category-grouped banners with images | Promotional flexibility |
| 10.4 | Popup Manager | Timed/scheduled popups for offers | Lead capture & promotions |
| 10.5 | SEO Settings | Per-page meta titles, descriptions, OG tags | Search engine visibility |
| 10.6 | Auto Sitemap Generation | XML sitemap for search engines | Better SEO indexing |
| 10.7 | Contact Form + WhatsApp | Public contact form with WhatsApp integration | Multi-channel customer contact |

---

### MODULE 11 — THEME & LAYOUT BUILDER

| # | Feature | Description | Business Value |
|---|---------|-------------|----------------|
| 11.1 | 15+ Pre-built Themes | Ready-to-apply color/UI themes | Instant rebranding |
| 11.2 | Theme Customizer | Customize primary, secondary, footer colors | Match your brand identity |
| 11.3 | Drag-and-Drop Layout Builder | Visually reorder homepage sections | No developer needed for layout changes |
| 11.4 | Section Toggle & Settings | Enable/disable/configure each section | A/B test homepage layouts |
| 11.5 | Header & Footer Builder | Customize navigation and footer components | Complete site structure control |
| 11.6 | Theme/Layout Export & Import | Transfer designs between installations | Agency/multi-site deployment |
| 11.7 | Logo & Favicon Management | White & dark logo variants, custom favicon | Professional branding |

---

### MODULE 12 — MARKETING & ANALYTICS

| # | Feature | Description | Business Value |
|---|---------|-------------|----------------|
| 12.1 | Facebook Pixel | Server-side + browser tracking | Retargeting & lookalike audiences |
| 12.2 | Facebook CAPI | Conversion API for accurate event tracking | iOS 14+ compliant tracking |
| 12.3 | Facebook Page Auto-Post | Auto-share new products to FB page | Automated social marketing |
| 12.4 | TikTok Pixel | TikTok ad tracking | TikTok ad campaign optimization |
| 12.5 | Google Tag Manager | GTM container integration | Flexible tag deployment |
| 12.6 | **Google Analytics 4** | gtag.js native integration with admin control | In-depth visitor analytics & audience insights |
| 12.7 | Ads Analytics Dashboard | Multi-platform metrics in one view | Campaign ROI at a glance |
| 12.8 | SMS Marketing | Bulk/custom SMS via gateway | Direct customer reach |
| 12.9 | Campaign Landing Pages | Custom pages for marketing campaigns | Conversion-optimized promotions |

---

### MODULE 13 — POINT OF SALE (POS)

| # | Feature | Description | Business Value |
|---|---------|-------------|----------------|
| 13.1 | POS Order Creation | Create walk-in orders from admin panel | Physical store + online in one system |
| 13.2 | Barcode Scanning | Scan products directly into POS cart | Fast in-store checkout |
| 13.3 | Hold & Resume Cart | Save cart, serve next customer, resume later | Queue management |
| 13.4 | POS Discounts | Per-item or cart-level discounts | Flexible in-store pricing |
| 13.5 | POS Coupons | Apply coupon codes at POS | Unified promo across channels |
| 13.6 | Unified Inventory | POS sales deduct from same stock pool | Single source of truth |

---

### MODULE 14 — MOBILE APP API (Flutter-Ready)

| # | Feature | Description | Business Value |
|---|---------|-------------|----------------|
| 14.1 | REST API (Sanctum Auth) | Token-based secure API | Ready for Flutter/iOS/Android app |
| 14.2 | Product APIs | List, search, featured, hot deals, by category | Full mobile catalog |
| 14.3 | Cart APIs | Add, update, remove, clear, count | Mobile shopping cart |
| 14.4 | Order APIs | Create order, history, tracking | Complete mobile checkout |
| 14.5 | User Profile APIs | Register, login, profile, password change | Mobile account management |
| 14.6 | App Config API | Settings, sliders, menus, social links | Dynamic app configuration |

---

### MODULE 15 — SECURITY & ADMINISTRATION

| # | Feature | Description | Business Value |
|---|---------|-------------|----------------|
| 15.1 | Role-Based Access Control | Spatie Permission — granular roles & permissions | Control who does what |
| 15.2 | Admin Lock Screen | Session lock with password re-entry | Security for shared computers |
| 15.3 | IP Blocking | Block malicious IPs, quick-block from orders | Anti-fraud protection |
| 15.4 | Demo Mode | Restrict destructive actions during demos | Safe client demonstrations |
| 15.5 | Database Backup & Restore | One-click backup, download, restore | Disaster recovery |
| 15.6 | Auto-Update System | Check, download, install updates from admin | Stay current without DevOps |
| 15.7 | Error Log Viewer | View logs in admin, no server access needed | Faster debugging |
| 15.8 | License Management | Built-in license verification | Software protection |
| 15.9 | Cron Job Manager | Enable/disable/run scheduled tasks from UI | Control background jobs |
| 15.10 | Cache Management | One-click clear all caches | Performance troubleshooting |

---

### MODULE 16 — DEMO PRESET SYSTEM

| # | Preset | Description | Ready For |
|---|--------|-------------|-----------|
| 16.1 | Gadget + Fashion + Grocery | Multi-category general store | General eCommerce |
| 16.2 | Electronics | Pure tech & gadget store | Electronics retailers |
| 16.3 | Natural Food & Grocery | Organic food, health products | Grocery & food delivery |
| 16.4 | Clothing Fashion | Apparel, accessories, designer wear | Fashion brands |
| 16.5 | Beauty & Cosmetics | Skincare, makeup, beauty products | Beauty & cosmetics shops |

Each preset includes: categories, products, theme, layout, banners — importable in **one click**.

---

### MODULE 17 — REPORTS & BUSINESS INTELLIGENCE

| # | Report | What It Shows | Decision Support |
|---|--------|---------------|------------------|
| 17.1 | Order Reports | Sales by date, status, product | Revenue trends, peak periods |
| 17.2 | Purchase Reports | Procurement spend by supplier, date | Supplier negotiation, budgeting |
| 17.3 | Expense Reports | All business expenses categorized | Cost control, tax filing |
| 17.4 | Stock Reports | Current inventory levels, movements | Reorder planning |
| 17.5 | Profit & Loss | Revenue - COGS - Expenses | Business health dashboard |
| 17.6 | Fund Reports | Cash in/out, running balance | Cash flow management |
| 17.7 | Export to Excel/CSV | All reports exportable | Accounting & analysis |

---

## 💰 Deployment & Delivery Options

| Tier | What's Included | Best For |
|------|----------------|----------|
| **Starter** | Source code + 1 domain license + 3 months support | Single-store startups |
| **Business** | Source code + 3 domain license + 1 year support + installation | Growing businesses |
| **Enterprise** | Source code + unlimited domains + 1 year priority support + customization hours + server setup | Multi-store, agencies |

---

## 🛠️ Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend Framework | Laravel 12 (PHP 8.2+) |
| Database | MySQL / MariaDB |
| Frontend | Bootstrap 5 + jQuery + AJAX |
| Build Tool | Vite |
| PDF Engine | barryvdh/laravel-dompdf |
| Barcode Engine | picqer/php-barcode-generator |
| Image Processing | intervention/image |
| Auth (API) | Laravel Sanctum |
| Auth (Roles) | Spatie Laravel Permission |
| Cart Engine | hardevine/shoppingcart |
| Testing | PHPUnit 11 |
| Server | Apache / Nginx / Laravel Sail (Docker) |

---

## ✅ What You Get

| Deliverable | Details |
|-------------|---------|
| 📦 Full Source Code | Unencrypted, well-commented, PSR-4 structured |
| 📖 Documentation | Installation guide, user manual, API docs |
| 🎨 15+ Themes | Pre-built, customizable |
| 📱 Mobile API | 25+ endpoints, Flutter-ready |
| 🔄 Free Updates | Bug fixes & minor features during support period |
| 🧪 Pre-Tested | PHPUnit tests, production-deployed |
| 🌐 2 Languages | English + Bengali (extensible) |
| 🔌 4 Payment Gateways | bKash, Shurjopay, UddoktaPay, AamarPay |
| 🚚 2 Courier APIs | RedX + Pathao |
| 🏪 POS Module | Complete in-store selling system |

---

## 📞 Post-Purchase Support

| Period | Coverage |
|--------|----------|
| **First 30 Days** | Priority bug fixes, installation help, configuration guidance |
| **Support Period** | Email/ticket support, bug fixes, minor feature guidance |
| **Extended Support** | Available as add-on — includes customization hours |

---

## 📋 Feature Count Summary

| Category | Features |
|----------|----------|
| Product & Catalog | 30+ |
| Order Management | 35+ |
| Shopping Experience | 25+ |
| Payment Gateways | 4 gateways, 6 features |
| Stock & Inventory | 15+ |
| Shipping & Logistics | 12+ |
| Purchase & Supplier | 20+ |
| Financial Management | 12+ |
| CRM & HR | 18+ |
| Customer Experience | 15+ |
| Content & SEO | 12+ |
| Theme & Layout Builder | 20+ |
| Marketing & Analytics | 16+ |
| POS System | 15+ |
| Mobile API | 25+ |
| Security & Admin | 18+ |
| Reports & BI | 8+ |
| Demo Presets | 5 presets |
| **TOTAL** | **~350+ Features** |

---

> **Ready for demo.** Contact us to schedule a live walkthrough of the admin panel and storefront.

---

*© 2026 CurlWare. Laravel is a trademark of Taylor Otwell. All third-party packages are used under their respective licenses.*
