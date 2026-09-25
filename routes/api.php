<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\AttributeController;
use App\Http\Controllers\Api\Admin\BannerController;
use App\Http\Controllers\Api\Admin\BrandController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\CouponController;
use App\Http\Controllers\Api\Admin\PanelPinController;
use App\Http\Controllers\Api\Admin\ProductModelController;
use App\Http\Controllers\Api\Admin\PromotionController;
use App\Http\Controllers\Api\Admin\PurchaseOrderController;
use App\Http\Controllers\Api\Admin\QuoteController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\ShipmentController;
use App\Http\Controllers\Api\Admin\SizeController;
use App\Http\Controllers\Api\Admin\StockTransferController;
use App\Http\Controllers\Api\Admin\SupplierController;
use App\Http\Controllers\Api\Admin\SystemMaintenanceController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\WarehouseController;
use App\Http\Controllers\Api\Admin\WhatsappOrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// ==========================================
// 1. PUBLIC STOREFRONT ENDPOINTS
// ==========================================
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/homepage', [ProductController::class, 'homepage']);
Route::get('/home', [ProductController::class, 'homepage']);
Route::get('/home/products', [ProductController::class, 'homeProducts']);
Route::get('/home/banners', [BannerController::class, 'publicBanners']);
Route::get('/display-finder', [ProductController::class, 'displayFinder']);
Route::post('/checkout', [CheckoutController::class, 'checkout']);
Route::get('/banners', [BannerController::class, 'publicBanners']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/sizes', [ProductController::class, 'sizes']);
Route::get('/product-types', [ProductController::class, 'productTypes']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::get('/orders/{id}/invoice', [OrderController::class, 'invoicePdf']);
Route::get('/orders/{id}/shipping-note', [OrderController::class, 'shippingNotePdf']);

// Sri Lanka Locations & Shipping Public Endpoints
Route::get('/locations/provinces', [LocationController::class, 'provinces']);
Route::get('/locations/districts', [LocationController::class, 'districts']);
Route::get('/locations/cities', [LocationController::class, 'cities']);
Route::get('/shipping/methods', [LocationController::class, 'shippingMethods']);
Route::post('/shipping/calculate', [LocationController::class, 'calculateShipping']);
Route::post('/coupons/validate', [LocationController::class, 'validateCoupon']);

// ==========================================
// 2. AUTHENTICATION ENDPOINTS
// ==========================================
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);

// ==========================================
// 3. AUTHENTICATED CUSTOMER PORTAL
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Customer Account & History
    Route::get('/customer/profile', [CustomerController::class, 'profile']);
    Route::put('/customer/profile', [CustomerController::class, 'updateProfile']);
    Route::put('/customer/password', [CustomerController::class, 'updatePassword']);
    Route::get('/customer/orders', [CustomerController::class, 'orders']);
    Route::get('/customer/orders/{id}', [CustomerController::class, 'orderDetail']);

    // Saved Addresses
    Route::get('/customer/addresses', [CustomerController::class, 'addresses']);
    Route::post('/customer/addresses', [CustomerController::class, 'storeAddress']);
    Route::delete('/customer/addresses/{id}', [CustomerController::class, 'destroyAddress']);
});

// ==========================================
// 4. ADMIN & STAFF PORTAL (Sanctum Protected)
// ==========================================
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);

    // Orders Management
    Route::get('/orders', [AdminController::class, 'orders']);
    Route::get('/orders/{id}', [AdminController::class, 'orderShow']);
    Route::post('/orders/{id}/status', [AdminController::class, 'updateOrderStatus']);

    // Shipments & Courier Logistics
    Route::get('/shipments', [ShipmentController::class, 'index']);
    Route::get('/shipments/{id}', [ShipmentController::class, 'show']);
    Route::post('/shipments/{id}/assign-courier', [ShipmentController::class, 'assignCourier']);
    Route::post('/shipments/{id}/status', [ShipmentController::class, 'updateStatus']);
    Route::get('/shipments/{id}/label', [ShipmentController::class, 'shippingLabelPdf']);
    Route::get('/couriers', [ShipmentController::class, 'couriers']);

    // Products Management
    Route::get('/products', [AdminController::class, 'productsIndex']);
    Route::get('/products/{id}', [AdminController::class, 'productShow']);
    Route::post('/products', [AdminController::class, 'storeProduct']);
    Route::put('/products/{id}', [AdminController::class, 'updateProduct']);
    Route::delete('/products/{id}', [AdminController::class, 'deleteProduct']);
    Route::post('/upload-image', [AdminController::class, 'uploadImage']);
    Route::get('/product-types', [ProductController::class, 'productTypes']);

    // Inventory Control
    Route::get('/inventory', [AdminController::class, 'inventoryLogs']);
    Route::post('/inventory/update', [AdminController::class, 'updateStock']);
    Route::apiResource('warehouses', WarehouseController::class)->except(['show']);
    Route::get('/stock-transfers', [StockTransferController::class, 'index']);
    Route::post('/stock-transfers', [StockTransferController::class, 'store']);
    Route::post('/stock-transfers/{id}/status', [StockTransferController::class, 'updateStatus']);
    Route::delete('/stock-transfers/{id}', [StockTransferController::class, 'destroy']);

    // Catalog Taxonomy
    Route::apiResource('categories', CategoryController::class)->except(['show']);
    Route::apiResource('brands', BrandController::class)->except(['show']);
    Route::apiResource('sizes', SizeController::class)->except(['show']);
    Route::apiResource('panel-pins', PanelPinController::class)->except(['show']);
    Route::apiResource('models', ProductModelController::class)->except(['show']);
    Route::apiResource('attributes', AttributeController::class)->except(['show']);

    // Purchases & Suppliers
    Route::apiResource('suppliers', SupplierController::class)->except(['show']);
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index']);
    Route::get('/purchase-orders/{id}', [PurchaseOrderController::class, 'show']);
    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store']);
    Route::post('/purchase-orders/{id}/status', [PurchaseOrderController::class, 'updateStatus']);
    Route::post('/purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receive']);
    Route::delete('/purchase-orders/{id}', [PurchaseOrderController::class, 'destroy']);
    Route::get('/purchase-receipts', [PurchaseOrderController::class, 'receiptsIndex']);

    // Marketing
    Route::apiResource('banners', BannerController::class)->except(['show']);
    Route::apiResource('coupons', CouponController::class)->except(['show']);
    Route::apiResource('promotions', PromotionController::class)->except(['show']);

    // Quotes & WhatsApp Conversion
    Route::get('/quotes', [QuoteController::class, 'index']);
    Route::post('/quotes', [QuoteController::class, 'store']);
    Route::post('/quotes/{id}/status', [QuoteController::class, 'updateStatus']);
    Route::post('/quotes/{id}/convert', [QuoteController::class, 'convertToOrder']);
    Route::delete('/quotes/{id}', [QuoteController::class, 'destroy']);

    Route::get('/whatsapp-orders', [WhatsappOrderController::class, 'index']);
    Route::post('/whatsapp-orders', [WhatsappOrderController::class, 'store']);
    Route::post('/whatsapp-orders/{id}/status', [WhatsappOrderController::class, 'updateStatus']);
    Route::post('/whatsapp-orders/{id}/convert', [WhatsappOrderController::class, 'convertToOrder']);
    Route::delete('/whatsapp-orders/{id}', [WhatsappOrderController::class, 'destroy']);

    // Customers
    Route::get('/customers', [AdminController::class, 'customersIndex']);

    // Reports (Sales, Inventory, Profit)
    Route::get('/reports/sales', [ReportController::class, 'sales']);
    Route::get('/reports/orders', [ReportController::class, 'orders']);
    Route::get('/reports/products', [ReportController::class, 'products']);
    Route::get('/reports/inventory', [ReportController::class, 'inventory']);
    Route::get('/reports/customers', [ReportController::class, 'customers']);
    Route::get('/reports/purchases', [ReportController::class, 'purchases']);
    Route::get('/reports/profit', [ReportController::class, 'profit']);

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);

    // RBAC Protected Routes: Settings & User Management
    Route::middleware(['role:admin,super_admin'])->group(function () {
        Route::get('/settings', [AdminController::class, 'getSettings']);
        Route::post('/settings', [AdminController::class, 'updateSettings']);
        Route::apiResource('users', UserController::class)->except(['show']);

        // System Maintenance & Artisan Operations
        Route::get('/system/status', [SystemMaintenanceController::class, 'status']);
        Route::match(['get', 'post'], '/system/storage-link', [SystemMaintenanceController::class, 'storageLink']);
        Route::match(['get', 'post'], '/system/optimize-clear', [SystemMaintenanceController::class, 'optimizeClear']);
    });
});
