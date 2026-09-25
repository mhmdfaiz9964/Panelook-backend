# Panelook.lk Backend API & E-Commerce Management System

> **Robust RESTful API & Admin Backend for Panelook.lk — Sri Lanka's Leading Laptop Display & Replacement Parts Platform.**  
> Powered by **Laravel 12/13**, **PHP 8.3+**, **Laravel Sanctum**, and **DomPDF**.

---

## Table of Contents
1. [System Architecture & Features](#system-architecture--features)
2. [Tech Stack & Prerequisites](#tech-stack--prerequisites)
3. [Local Development Setup](#local-development-setup)
4. [Environment Variables Reference](#environment-variables-reference)
5. [Database Migrations & Seeders](#database-migrations--seeders)
6. [Complete REST API Documentation](#complete-rest-api-documentation)
   - [Authentication Endpoints](#1-authentication-endpoints)
   - [Public Storefront Endpoints](#2-public-storefront-endpoints)
   - [Sri Lanka Logistics & Shipping Endpoints](#3-sri-lanka-logistics--shipping-endpoints)
   - [Customer Account Portal](#4-customer-account-portal)
   - [Admin Management Portal](#5-admin-management-portal)
7. [PDF Document Engine](#pdf-document-engine)
8. [cPanel / Shared Hosting Deployment Guide](#cpanel--shared-hosting-deployment-guide)
9. [Linux VPS Deployment Guide (Ubuntu / Debian + Nginx)](#linux-vps-deployment-guide-ubuntu--debian--nginx)
10. [System Maintenance & CLI Commands](#system-maintenance--cli-commands)
11. [Troubleshooting & FAQ](#troubleshooting--faq)

---

## System Architecture & Features

Panelook.lk Backend is designed for high-throughput e-commerce operations, custom hardware cataloging, and automated logistics handling:

- **E-Commerce Storefront Engine**: Public product catalog, dynamic filtering by laptop brand, screen size, resolution, connector pin count (30-pin, 40-pin, etc.), and refresh rate.
- **Display Finder**: Interactive laptop screen compatibility search matching manufacturer model codes to exact replacement panel specifications.
- **Sri Lanka District & City Geolocation**: Pre-loaded Sri Lankan provinces, districts, and postal delivery zones with dynamic flat-rate/distance-based shipping fee calculations.
- **Unified Checkout System**: Cash on Delivery (COD) and Bank Transfer workflows with coupon discounts and automated stock decrement.
- **Sanctum Multi-Auth & RBAC**: Role-Based Access Control distinguishing `super_admin`, `admin`, `staff`, and `customer` roles.
- **Automated Logistics & Courier Assignment**: Shipment tracking, waybills, courier assignment (Domex, Prompt, Koombiyo, Pronto, etc.), and order lifecycle states (`pending`, `confirmed`, `processing`, `dispatched`, `delivered`, `cancelled`).
- **Inventory & Multi-Warehouse Control**: Real-time stock alerts, batch updates, inventory change audit logs, and inter-warehouse stock transfer approvals.
- **B2B Inquiries & WhatsApp Orders**: Conversion of informal customer inquiries and WhatsApp orders into formal invoices and sales records with a single click.
- **PDF Generation**: Dynamic generation of tax invoices, customer shipping notes, and printable warehouse dispatch labels using DomPDF.

---

## Tech Stack & Prerequisites

### Server Requirements
| Component | Minimum Version | Recommended Version |
| :--- | :--- | :--- |
| **PHP** | `^8.3` | `8.3.x` or `8.4.x` |
| **Framework** | Laravel `12.x` / `13.x` | Latest Stable |
| **Database** | MySQL `8.0+` / MariaDB `10.5+` (or SQLite `3.35+`) | MySQL `8.0` |
| **Web Server** | Nginx `1.20+` or Apache `2.4+` | Nginx `1.24+` with PHP-FPM |
| **Package Manager** | Composer `2.2+` | Composer `2.7+` |

### Required PHP Extensions
Ensure the following PHP extensions are enabled:
```
bcmath, ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, pcre, pdo, pdo_mysql (or pdo_sqlite), session, tokenizer, xml, gd (or imagick), zip
```

---

## Local Development Setup

### 1. Clone & Enter Directory
```bash
git clone git@github.com:mhmdfaiz9964/Panelook-backend.git backend
cd backend
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Setup Environment File
Copy the example environment file and configure your database settings:
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database in `.env`
For MySQL:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=panelook
DB_USERNAME=root
DB_PASSWORD=your_password
```
*(Alternatively, for quick local testing with SQLite: set `DB_CONNECTION=sqlite` and create `database/database.sqlite`)*

### 5. Run Migrations & Seeders
Populate default admin accounts, Sri Lankan provinces/districts/cities, store settings, categories, and sample display hardware:
```bash
php artisan migrate:fresh --seed
```

### 6. Create Storage Symlink
Link `storage/app/public` to `public/storage` so uploaded product images and logos are publicly accessible:
```bash
php artisan storage:link
```

### 7. Start Local Development Server
```bash
php artisan serve
```
The API server will run at: `http://127.0.0.1:8000`

---

## Environment Variables Reference

Key `.env` options for production and development:

```env
APP_NAME=Panelook.lk
APP_ENV=production          # Use 'local' in development
APP_DEBUG=false             # NEVER set to true in production
APP_URL=https://admin.panelook.lk

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=panelook_db
DB_USERNAME=panelook_usr
DB_PASSWORD=StrongPasswordHere

# Sanctum & Session
SESSION_DRIVER=database
SANCTUM_STATEFUL_DOMAINS=panelook.lk,admin.panelook.lk,localhost:3000

# Filesystem
FILESYSTEM_DISK=public

# Mail Driver (for customer notifications)
MAIL_MAILER=smtp
MAIL_HOST=mail.panelook.lk
MAIL_PORT=465
MAIL_USERNAME=info@panelook.lk
MAIL_PASSWORD=YourMailPassword
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="info@panelook.lk"
MAIL_FROM_NAME="Panelook.lk"
```

---

## Database Migrations & Seeders

The database schema includes tables for:
- `users` (Roles: `super_admin`, `admin`, `staff`, `customer`)
- `categories`, `brands`, `sizes`, `panel_pins`, `product_models`, `attributes`
- `products`, `product_variations`, `inventory_logs`
- `provinces`, `districts`, `cities`, `shipping_rates`, `coupons`
- `orders`, `order_items`, `shipments`, `shipment_tracking`
- `warehouses`, `stock_transfers`, `suppliers`, `purchase_orders`, `purchase_receipts`
- `quotes`, `whatsapp_orders`, `banners`, `settings`, `activity_logs`

### Default Admin Credentials
When seeded using `DatabaseSeeder`:
- **Admin Email**: `admin@example.com`
- **Admin Password**: `ChangeMe123!`
- **Role**: `admin`
*(Be sure to change this password immediately in production via the Admin UI or `php artisan tinker`)*

### Seeder Commands
```bash
# Run all seeders (store settings, categories, brands, locations, admin user)
php artisan db:seed

# Run only location and shipping data
php artisan db:seed --class=LocationAndShippingSeeder

# Seed specific test product inventory
php artisan db:seed --class=RequiredTestProductsSeeder
```

---

## Complete REST API Documentation

Base API URL: `http://localhost:8000/api` (Local) or `https://admin.panelook.lk/api` (Production)

### 1. Authentication Endpoints

#### Register Customer
- **Endpoint**: `POST /api/auth/register`
- **Body**:
  ```json
  {
    "name": "Kamal Perera",
    "email": "kamal@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "phone": "0771234567"
  }
  ```
- **Response**: `{ "token": "1|sanctum_token...", "user": { ... } }`

#### Login (Customer)
- **Endpoint**: `POST /api/auth/login`
- **Body**: `{ "email": "kamal@example.com", "password": "Password123!" }`

#### Login (Admin & Staff)
- **Endpoint**: `POST /api/admin/login`
- **Body**: `{ "email": "admin@example.com", "password": "ChangeMe123!" }`
- **Response**: `{ "token": "...", "user": { "role": "admin", ... } }`

#### Authenticated User Profile
- **Endpoint**: `GET /api/auth/user`
- **Headers**: `Authorization: Bearer <TOKEN>`

#### Logout
- **Endpoint**: `POST /api/auth/logout`
- **Headers**: `Authorization: Bearer <TOKEN>`

---

### 2. Public Storefront Endpoints

#### Get All Products
- **Endpoint**: `GET /api/products`
- **Query Params**:
  - `brand` (string): e.g. `hp`, `dell`, `asus`
  - `size` (string): e.g. `14.0`, `15.6`
  - `pin` (string): e.g. `30`, `40`
  - `category` (string): Category slug
  - `search` (string): Search keyword
  - `sort` (string): `price_asc`, `price_desc`, `latest`
  - `page` (int): Page number

#### Get Product By Slug
- **Endpoint**: `GET /api/products/{slug}`
- **Response**: Full product details, image galleries, pin connector specs, resolution, compatible models, stock levels.

#### Homepage Aggregation
- **Endpoint**: `GET /api/homepage` or `GET /api/home`
- **Response**: Banners, featured displays, top categories, latest arrivals in one payload.

#### Display Finder (Hardware Matcher)
- **Endpoint**: `GET /api/display-finder`
- **Query Params**: `brand_id`, `model_name`, `pin_id`, `size_id`
- **Response**: Exact matching screens with compatibility flags and stock availability.

#### Checkout Order Creation
- **Endpoint**: `POST /api/checkout`
- **Body**:
  ```json
  {
    "customer_name": "Nimal Silva",
    "customer_email": "nimal@example.com",
    "customer_phone": "0712345678",
    "shipping_address": "No. 45, Galle Road",
    "shipping_city": "Colombo 03",
    "shipping_district_id": 1,
    "payment_method": "cod",
    "coupon_code": "DISCOUNT5",
    "items": [
      {
        "product_id": 12,
        "quantity": 1,
        "price": 18500
      }
    ]
  }
  ```
- **Response**: `{ "success": true, "order_id": 1042, "order_number": "ORD-1042", "total": 19150 }`

#### Track Order
- **Endpoint**: `GET /api/orders/{id}`

---

### 3. Sri Lanka Logistics & Shipping Endpoints

- `GET /api/locations/provinces` — List all 9 Sri Lankan Provinces.
- `GET /api/locations/districts` — List all 25 Districts (filterable by `province_id`).
- `GET /api/locations/cities` — List major cities & delivery zones (filterable by `district_id`).
- `GET /api/shipping/methods` — Available delivery partners & rates (Standard, Express, COD).
- `POST /api/shipping/calculate` — Calculate exact delivery fee based on destination district and cart weight.
- `POST /api/coupons/validate` — Validate promo code and get discount deduction.

---

### 4. Customer Account Portal
*(Requires `Authorization: Bearer <TOKEN>`)*

- `GET /api/customer/profile` — View personal account details.
- `PUT /api/customer/profile` — Update phone, name, and address.
- `PUT /api/customer/password` — Change password.
- `GET /api/customer/orders` — Paginated order history.
- `GET /api/customer/orders/{id}` — Order items, payment status, tracking timeline.
- `GET /api/customer/addresses` — Saved shipping addresses.
- `POST /api/customer/addresses` — Add new saved address.
- `DELETE /api/customer/addresses/{id}` — Delete saved address.

---

### 5. Admin Management Portal
*(Requires `Authorization: Bearer <TOKEN>` with `admin` or `super_admin` role)*

| Route | Method | Description |
| :--- | :--- | :--- |
| `/api/admin/dashboard` | `GET` | Revenue, order stats, low stock warnings, recent activity |
| `/api/admin/orders` | `GET` | List orders with status filters (`pending`, `confirmed`, `dispatched`, etc.) |
| `/api/admin/orders/{id}` | `GET` | Comprehensive order details, items, notes |
| `/api/admin/orders/{id}/status`| `POST` | Update order state and notify customer |
| `/api/admin/shipments` | `GET` | Shipments queue, tracking IDs, couriers |
| `/api/admin/shipments/{id}/assign-courier` | `POST` | Assign delivery service (Domex, Pronto, Koombiyo) |
| `/api/admin/shipments/{id}/status` | `POST` | Update courier dispatch state |
| `/api/admin/products` | `GET`, `POST` | List and create hardware products |
| `/api/admin/products/{id}` | `PUT`, `DELETE` | Update and archive products |
| `/api/admin/upload-image` | `POST` | Upload product images & specs sheets (`multipart/form-data`) |
| `/api/admin/inventory` | `GET` | Stock audit logs & adjustments history |
| `/api/admin/inventory/update` | `POST` | Manual stock level increments / decrements |
| `/api/admin/warehouses` | `GET`, `POST`, `PUT`, `DELETE` | Multi-warehouse branch management |
| `/api/admin/stock-transfers` | `GET`, `POST` | Inter-branch inventory transfer requests |
| `/api/admin/stock-transfers/{id}/status` | `POST` | Approve, dispatch, or receive stock transfer |
| `/api/admin/categories` | CRUD | Display category tree |
| `/api/admin/brands` | CRUD | Laptop brands (HP, Dell, Lenovo, Acer, Apple, etc.) |
| `/api/admin/sizes` | CRUD | Screen dimensions (11.6", 13.3", 14.0", 15.6", etc.) |
| `/api/admin/panel-pins` | CRUD | Connector specifications (30 Pin, 40 Pin, eDP, etc.) |
| `/api/admin/models` | CRUD | Laptop compatibility matrix |
| `/api/admin/suppliers` | CRUD | Wholesale vendors & contact info |
| `/api/admin/purchase-orders` | `GET`, `POST` | Supplier POs & procurement orders |
| `/api/admin/purchase-orders/{id}/receive` | `POST` | Inward goods receipt & automatic inventory increment |
| `/api/admin/quotes` | `GET`, `POST` | Quotations management |
| `/api/admin/quotes/{id}/convert` | `POST` | Convert accepted quotation to real sales order |
| `/api/admin/whatsapp-orders` | `GET`, `POST` | WhatsApp chats log & manual order builder |
| `/api/admin/whatsapp-orders/{id}/convert` | `POST` | One-click convert WhatsApp chat to formal order |
| `/api/admin/reports/sales` | `GET` | Daily, weekly, monthly sales & revenue breakdown |
| `/api/admin/reports/profit` | `GET` | Cost of goods sold (COGS) vs selling price profit margins |
| `/api/admin/reports/inventory` | `GET` | Fast vs slow-moving stock analysis |
| `/api/admin/settings` | `GET`, `POST` | Store phone, WhatsApp, logos, currency, VAT |
| `/api/admin/users` | CRUD | Manage staff accounts and roles |
| `/api/admin/system/status` | `GET` | PHP, database connection, storage health |
| `/api/admin/system/storage-link` | `POST` | Re-create storage symlink via web UI |
| `/api/admin/system/optimize-clear` | `POST` | Clear route, view, and config caches |

---

## PDF Document Engine

The backend generates downloadable PDFs using `barryvdh/laravel-dompdf`:

1. **Tax Invoice**:
   - `GET /api/orders/{id}/invoice`
   - Formatted with company header, order number, line items, VAT, shipping fee, and bank payment instructions.
2. **Customer Shipping Note**:
   - `GET /api/orders/{id}/shipping-note`
   - Included inside the parcel box with return policy and warranty card terms.
3. **Courier Dispatch Waybill / Label**:
   - `GET /api/admin/shipments/{id}/label`
   - Barcode-ready thermal shipping label showing sender info, recipient contact, address, COD collection amount, and package weight.

---

## cPanel / Shared Hosting Deployment Guide

Follow this guide to host the Laravel API on cPanel (e.g. at `admin.panelook.lk` or `api.panelook.lk`):

### 1. File Placement (Best Practice Security)
Never place the entire Laravel root inside `public_html`. Separate core code from the public webroot:

```text
/home/yourusername/
  ├── panelook_backend_core/     <-- Put app, bootstrap, config, database, routes, vendor here
  │     ├── app/
  │     ├── artisan
  │     ├── bootstrap/
  │     ├── config/
  │     ├── database/
  │     ├── routes/
  │     ├── storage/
  │     ├── vendor/
  │     └── .env
  └── public_html/
        └── admin.panelook.lk/   <-- Put contents of backend/public here!
              ├── index.php
              ├── robots.txt
              ├── .htaccess
              └── storage/       <-- Symlink to panelook_backend_core/storage/app/public
```

### 2. Configure Subdomain Document Root
In cPanel:
1. Go to **Domains** or **Subdomains**.
2. Create `admin.panelook.lk` (or `api.panelook.lk`).
3. Set the Document Root to: `/home/yourusername/public_html/admin.panelook.lk` (or point directly to `/home/yourusername/panelook_backend_core/public`).

### 3. Update `index.php` Path
If your core files are placed in `/home/yourusername/panelook_backend_core/`, edit `/home/yourusername/public_html/admin.panelook.lk/index.php`:
```php
// Register The Auto Loader
require __DIR__.'/../../panelook_backend_core/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/../../panelook_backend_core/bootstrap/app.php';
```

### 4. Create MySQL Database in cPanel
1. Open **MySQL Database Wizard** in cPanel.
2. Create database: `panelook_db`.
3. Create user: `panelook_usr` with a strong password.
4. Add user to database with **ALL PRIVILEGES**.
5. Update `/home/yourusername/panelook_backend_core/.env` with these database credentials.

### 5. MultiPHP Version & Extensions
1. In cPanel, navigate to **MultiPHP Manager**.
2. Select `admin.panelook.lk` and set PHP version to **PHP 8.3** or higher.
3. Open **Select PHP Version / MultiPHP INI Editor**:
   - `upload_max_filesize` = `64M`
   - `post_max_size` = `64M`
   - `memory_limit` = `256M`
   - `max_execution_time` = `300`

### 6. Run Migrations & Storage Link on cPanel
Open cPanel **Terminal** (or SSH) and run:
```bash
cd ~/panelook_backend_core
php artisan key:generate --force
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

*(If SSH access is disabled on your cPanel account, use the built-in maintenance endpoint `/api/admin/system/storage-link` and `/api/admin/system/optimize-clear` after logging in as admin).*

### 7. Setup Laravel Cron Job
1. In cPanel, click **Cron Jobs**.
2. Set frequency to **Every Minute** (`* * * * *`).
3. Command:
   ```bash
   /usr/local/bin/php /home/yourusername/panelook_backend_core/artisan schedule:run >> /dev/null 2>&1
   ```

---

## Linux VPS Deployment Guide (Ubuntu / Debian + Nginx)

For high-performance production hosting on DigitalOcean, AWS EC2, Hetzner, or Linode:

### 1. Install PHP 8.3, Nginx, MySQL & Composer
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common curl git unzip nginx
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml \
    php8.3-bcmath php8.3-curl php8.3-gd php8.3-zip php8.3-intl php8.3-cli

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Clone Repository to `/var/www/panelook-backend`
```bash
sudo mkdir -p /var/www/panelook-backend
sudo chown -R $USER:$USER /var/www/panelook-backend
git clone git@github.com:mhmdfaiz9964/Panelook-backend.git /var/www/panelook-backend
cd /var/www/panelook-backend
composer install --no-dev --optimize-autoloader
```

### 3. Setup Permissions
```bash
sudo chown -R www-data:www-data /var/www/panelook-backend/storage /var/www/panelook-backend/bootstrap/cache
sudo chmod -R 775 /var/www/panelook-backend/storage /var/www/panelook-backend/bootstrap/cache
```

### 4. Create Nginx Server Block
Create `/etc/nginx/sites-available/panelook-backend.conf`:
```nginx
server {
    listen 80;
    server_name admin.panelook.lk;
    root /var/www/panelook-backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;
    charset utf-8;

    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the configuration and reload Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/panelook-backend.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 5. Secure with Free Let's Encrypt SSL
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d admin.panelook.lk
```

### 6. Production Optimizations
```bash
cd /var/www/panelook-backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

### 7. Supervisor for Background Queues
Create `/etc/supervisor/conf.d/panelook-worker.conf`:
```ini
[program:panelook-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/panelook-backend/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/panelook-backend/storage/logs/worker.log
stopwaitsecs=3600
```
Update supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start panelook-worker:*
```

---

## System Maintenance & CLI Commands

```bash
# Clear all cached configuration and routes
php artisan optimize:clear

# Optimize for production
php artisan optimize

# Re-run specific seeder
php artisan db:seed --class=RequiredTestProductsSeeder

# Create storage symlink
php artisan storage:link

# Inspect failed background queue jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## Troubleshooting & FAQ

#### 1. 403 Forbidden or Upload Error when uploading product images
- Ensure `storage/app/public` exists and `php artisan storage:link` was executed.
- Ensure web server user (`www-data` on Ubuntu or cPanel account user) has write permissions: `chmod -R 775 storage bootstrap/cache`.

#### 2. CORS Errors from Frontend (localhost or panelook.lk)
- Edit `config/cors.php` or `.env` and verify `allowed_origins` contains `https://panelook.lk` and `https://admin.panelook.lk`.

#### 3. Database connection refused on cPanel
- Ensure `DB_HOST=127.0.0.1` or `localhost`.
- Check if your database name and user are prefixed with your cPanel username (e.g. `cpaneluser_panelook_db`).

---

## License

This software is proprietary and confidential.  
Copyright © 2026 **Panelook.lk**. All rights reserved.
