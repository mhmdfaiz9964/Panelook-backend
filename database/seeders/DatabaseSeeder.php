<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PanelPin;
use App\Models\Product;
use App\Models\ProductModel;
use App\Models\ProductVariation;
use App\Models\Setting;
use App\Models\Size;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LocationAndShippingSeeder::class);

        // 1. Create Default Admin
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Panelook Admin',
                'password' => Hash::make('ChangeMe123!'),
                'role' => 'admin',
                'phone' => '0761234567',
                'address' => 'Colombo, Sri Lanka',
            ]
        );

        // 2. Settings
        $settings = [
            'store_name' => 'Panelook.lk',
            'store_tagline' => "Sri Lanka's #1 Laptop Display Store",
            'phone' => '076 123 4567',
            'whatsapp' => '94761234567',
            'email' => 'info@panelook.lk',
            'address' => 'Colombo, Sri Lanka',
            'currency' => 'LKR',
            'shipping_flat_rate' => '650',
            'whatsapp_message' => "Hello Panelook.lk, I would like to order standard display panel.",
        ];
        foreach ($settings as $k => $v) {
            Setting::set($k, $v);
        }

        // 3. Brands
        $brandsData = [
            ['name' => 'HP', 'slug' => 'hp', 'logo' => '/brands/hp.svg'],
            ['name' => 'Dell', 'slug' => 'dell', 'logo' => '/brands/dell.svg'],
            ['name' => 'Lenovo', 'slug' => 'lenovo', 'logo' => '/brands/lenovo.svg'],
            ['name' => 'ASUS', 'slug' => 'asus', 'logo' => '/brands/asus.svg'],
            ['name' => 'Acer', 'slug' => 'acer', 'logo' => '/brands/acer.svg'],
            ['name' => 'MSI', 'slug' => 'msi', 'logo' => '/brands/msi.svg'],
        ];

        $brands = [];
        foreach ($brandsData as $b) {
            $brands[$b['slug']] = Brand::updateOrCreate(['slug' => $b['slug']], $b);
        }

        // 3b. Sizes
        $sizesData = ['10.1"', '11.6"', '12.5"', '13.3"', '14"', '15.6"', '16"', '17.3"', '18.0"'];
        foreach ($sizesData as $i => $label) {
            Size::updateOrCreate(['label' => $label], ['sort_order' => $i]);
        }

        // 3c. Panel Pins
        $pinsData = [
            ['name' => '30-pin', 'pin_count' => 30, 'description' => 'Standard 30-pin LVDS connector, common on HD panels.'],
            ['name' => '40-pin', 'pin_count' => 40, 'description' => 'Higher bandwidth 40-pin connector for FHD/QHD/144Hz panels.'],
        ];
        foreach ($pinsData as $i => $p) {
            PanelPin::updateOrCreate(['name' => $p['name']], array_merge($p, ['sort_order' => $i]));
        }

        // 3d. Attributes
        $attributesData = [
            ['name' => 'Panel Type', 'type' => 'select', 'values' => ['IPS', 'TN', 'OLED', 'VA'], 'filterable' => true],
            ['name' => 'Touch', 'type' => 'boolean', 'values' => null, 'filterable' => true],
            ['name' => 'Refresh Rate', 'type' => 'select', 'values' => ['60Hz', '90Hz', '120Hz', '144Hz', '165Hz'], 'filterable' => true],
            ['name' => 'Connector Position', 'type' => 'select', 'values' => ['Left', 'Right', 'Center', 'Bottom'], 'filterable' => false],
            ['name' => 'Condition', 'type' => 'select', 'values' => ['Brand New Genuine', 'Grade A Refurbished', 'Open Box'], 'filterable' => true],
        ];
        foreach ($attributesData as $i => $a) {
            Attribute::updateOrCreate(['name' => $a['name']], array_merge($a, ['sort_order' => $i]));
        }

        // 3e. Product Models
        $modelsData = [
            ['brand' => 'hp', 'model_name' => 'Pavilion 15', 'model_number' => '15-eg0001', 'series' => 'Pavilion', 'compatible_sizes' => ['15.6"']],
            ['brand' => 'hp', 'model_name' => 'EliteBook 840 G5', 'model_number' => '840-G5', 'series' => 'EliteBook', 'compatible_sizes' => ['14"']],
            ['brand' => 'dell', 'model_name' => 'Inspiron 15 3000', 'model_number' => '3000', 'series' => 'Inspiron', 'compatible_sizes' => ['15.6"']],
            ['brand' => 'lenovo', 'model_name' => 'ThinkPad E580', 'model_number' => 'E580', 'series' => 'ThinkPad', 'compatible_sizes' => ['15.6"']],
            ['brand' => 'asus', 'model_name' => 'ZenBook 15', 'model_number' => 'UX533', 'series' => 'ZenBook', 'compatible_sizes' => ['15.6"']],
            ['brand' => 'msi', 'model_name' => 'GF63 Thin', 'model_number' => 'GF63', 'series' => 'Gaming', 'compatible_sizes' => ['15.6"']],
        ];
        foreach ($modelsData as $m) {
            ProductModel::updateOrCreate(
                ['model_name' => $m['model_name']],
                [
                    'brand_id' => $brands[$m['brand']]->id,
                    'model_number' => $m['model_number'],
                    'series' => $m['series'],
                    'compatible_sizes' => $m['compatible_sizes'],
                ]
            );
        }

        // 3f. Warehouses
        $warehousesData = [
            ['name' => 'Colombo Main Warehouse', 'code' => 'WH-CMB', 'address' => 'Colombo 05, Sri Lanka', 'contact_person' => 'Kasun Jayawardena', 'phone' => '0771112233'],
            ['name' => 'Kandy Branch Store', 'code' => 'WH-KDY', 'address' => 'Kandy, Sri Lanka', 'contact_person' => 'Nadeesha Perera', 'phone' => '0772223344'],
        ];
        foreach ($warehousesData as $w) {
            Warehouse::updateOrCreate(['code' => $w['code']], $w);
        }

        // 3g. Suppliers
        $suppliersData = [
            ['name' => 'ShenZhen Display Components Ltd', 'contact_person' => 'Li Wei', 'phone' => '+86 138 0000 1234', 'email' => 'sales@szdisplay.cn', 'city' => 'Shenzhen', 'country' => 'China'],
            ['name' => 'Colombo Electronics Distributors', 'contact_person' => 'Saman Kumara', 'phone' => '0112223344', 'email' => 'info@colombodist.lk', 'city' => 'Colombo', 'country' => 'Sri Lanka'],
        ];
        foreach ($suppliersData as $s) {
            Supplier::updateOrCreate(['name' => $s['name']], $s);
        }

        // 4. Categories
        $categoriesData = [
            ['name' => 'Laptop Displays', 'slug' => 'laptop-displays', 'image' => '/images/promo-card-1.jpeg', 'sort_order' => 1],
            ['name' => 'Touch Displays', 'slug' => 'touch-displays', 'image' => '/images/promo-center.jpeg', 'sort_order' => 2],
            ['name' => 'IPS High-Color Screens', 'slug' => 'ips-displays', 'image' => '/images/promo-card-2.jpeg', 'sort_order' => 3],
            ['name' => '30-Pin & 40-Pin Panels', 'slug' => 'pin-types', 'image' => '/images/promo-card-3.jpeg', 'sort_order' => 4],
            ['name' => '144Hz Gaming Screens', 'slug' => 'gaming-displays', 'image' => '/images/banner-sizes-range.jpeg', 'sort_order' => 5],
            ['name' => 'Islandwide Ready Stock', 'slug' => 'express-delivery', 'image' => '/images/banner-islandwide-delivery.jpeg', 'sort_order' => 6],
            ['name' => 'Mobile Accessories', 'slug' => 'mobile-accessories', 'image' => '/images/promo-card-1.jpeg', 'sort_order' => 7],
            ['name' => 'PC Accessories', 'slug' => 'pc-accessories', 'image' => '/images/promo-card-2.jpeg', 'sort_order' => 8],
            ['name' => 'Chargers', 'slug' => 'chargers', 'image' => '/images/promo-card-3.jpeg', 'sort_order' => 9],
            ['name' => 'Keyboards', 'slug' => 'keyboards', 'image' => '/images/promo-center.jpeg', 'sort_order' => 10],
            ['name' => 'Batteries', 'slug' => 'batteries', 'image' => '/images/promo-card-1.jpeg', 'sort_order' => 11],
            ['name' => 'Cables', 'slug' => 'cables', 'image' => '/images/promo-card-2.jpeg', 'sort_order' => 12],
            ['name' => 'Other Accessories', 'slug' => 'other-accessories', 'image' => '/images/promo-card-3.jpeg', 'sort_order' => 13],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['slug']] = Category::updateOrCreate(['slug' => $c['slug']], $c);
        }

        // 4b. Banners (3 full widescreen homepage slider banners)
        $bannersData = [
            [
                'title' => 'Fast Islandwide Delivery Across Sri Lanka',
                'subtitle' => '06 Month Warranty - Safe Packing Before Shipping',
                'desktop_image' => '/images/Banner slider 01.jpeg',
                'mobile_image' => '/images/Banner slider 01.jpeg',
                'button_text' => 'Order Now',
                'button_url' => '/shop',
                'sort_order' => 1,
                'status' => true,
            ],
            [
                'title' => 'Laptop Displays 10.1" - 18.0" Wide Size Range',
                'subtitle' => 'Find the right display for your laptop - HP, Dell, Lenovo, Asus, Acer, MSI',
                'desktop_image' => '/images/Banner slider 02.jpeg',
                'mobile_image' => '/images/Banner slider 02.jpeg',
                'button_text' => 'Shop Displays',
                'button_url' => '/shop',
                'sort_order' => 2,
                'status' => true,
            ],
            [
                'title' => 'Special Assembly Touch Displays',
                'subtitle' => 'Complete touch digitizer assemblies & premium replacement panels',
                'desktop_image' => '/images/home banner 03.jpeg',
                'mobile_image' => '/images/home banner 03.jpeg',
                'button_text' => 'Explore Touch Displays',
                'button_url' => '/shop?touch=Touch',
                'sort_order' => 3,
                'status' => true,
            ],
        ];
        foreach ($bannersData as $b) {
            Banner::updateOrCreate(['title' => $b['title']], $b);
        }

        // 5. Products (Matching UI Mockups exactly)
        $productsData = [
            [
                'name' => 'B156XW04 V.8',
                'slug' => 'b156xw04-v8',
                'sku' => 'DSP-B156XW04-V8',
                'category_id' => $categories['laptop-displays']->id,
                'brand_id' => $brands['hp']->id,
                'short_description' => '15.6" HD (1366x768) 30-pin LED Laptop Display Panel',
                'description' => '100% Genuine high quality 15.6" HD Slim LED Display replacement panel compatible with HP, Dell, Lenovo, ASUS laptops.',
                'main_image' => '/images/products/panel-b156xw04.png',
                'cost_price' => 10500,
                'selling_price' => 15500,
                'stock_quantity' => 12,
                'min_stock' => 2,
                'panel_number' => 'B156XW04 V.8',
                'laptop_model' => 'HP Pavilion 15 / Dell Inspiron 15',
                'display_size' => '15.6"',
                'pin_type' => '30-pin',
                'display_type' => 'TN',
                'touch_type' => 'Non-Touch',
                'resolution' => '1366x768',
                'warranty' => '6 Months Warranty',
                'is_featured' => true,
            ],
            [
                'name' => 'N156HCE-GN1',
                'slug' => 'n156hce-gn1',
                'sku' => 'DSP-N156HCE-GN1',
                'category_id' => $categories['laptop-displays']->id,
                'brand_id' => $brands['lenovo']->id,
                'short_description' => '15.6" Full HD (1920x1080) 30-pin IPS Slim Screen Panel',
                'description' => 'Vibrant IPS wide-angle viewing 15.6 inch FHD LED panel with crystal clear color reproduction and high contrast.',
                'main_image' => '/images/products/panel-n156hce.png',
                'cost_price' => 19500,
                'selling_price' => 28500,
                'stock_quantity' => 8,
                'min_stock' => 2,
                'panel_number' => 'N156HCE-GN1',
                'laptop_model' => 'Lenovo Ideapad 330 / ThinkPad E580',
                'display_size' => '15.6"',
                'pin_type' => '30-pin',
                'display_type' => 'IPS',
                'touch_type' => 'Non-Touch',
                'resolution' => '1920x1080',
                'warranty' => '6 Months Warranty',
                'is_featured' => true,
            ],
            [
                'name' => 'LP156WF9-SPK1',
                'slug' => 'lp156wf9-spk1',
                'sku' => 'DSP-LP156WF9-SPK1',
                'category_id' => $categories['laptop-displays']->id,
                'brand_id' => $brands['asus']->id,
                'short_description' => '15.6" Full HD (1920x1080) 30-pin IPS Touch Screen Digitizer Assembly',
                'description' => 'Original IPS Touch screen assembly display for ASUS ZenBook, HP Envy x360 and Dell Inspiron Touch laptops.',
                'main_image' => '/images/products/panel-lp156wf9.png',
                'cost_price' => 24000,
                'selling_price' => 35000,
                'stock_quantity' => 5,
                'min_stock' => 2,
                'panel_number' => 'LP156WF9-SPK1',
                'laptop_model' => 'ASUS ZenBook 15 / HP Envy x360',
                'display_size' => '15.6"',
                'pin_type' => '30-pin',
                'display_type' => 'IPS',
                'touch_type' => 'Touch',
                'resolution' => '1920x1080',
                'warranty' => '12 Months Warranty',
                'is_featured' => true,
            ],
            [
                'name' => 'LM156LFGL01',
                'slug' => 'lm156lfgl01',
                'sku' => 'DSP-LM156LFGL01',
                'category_id' => $categories['laptop-displays']->id,
                'brand_id' => $brands['msi']->id,
                'short_description' => '15.6" Full HD 144Hz 40-pin IPS Gaming Laptop Screen',
                'description' => 'High performance 144Hz high refresh rate 40-pin gaming laptop screen for MSI GF63, ASUS ROG Strix, and Acer Nitro 5.',
                'main_image' => '/images/products/panel-lm156lfgl01.png',
                'cost_price' => 22000,
                'selling_price' => 32000,
                'stock_quantity' => 2, // Low stock indicator
                'min_stock' => 3,
                'panel_number' => 'LM156LFGL01',
                'laptop_model' => 'MSI GF63 Thin / Acer Nitro 5',
                'display_size' => '15.6"',
                'pin_type' => '40-pin',
                'display_type' => 'IPS',
                'touch_type' => 'Non-Touch',
                'resolution' => '1920x1080',
                'warranty' => '6 Months Warranty',
                'is_featured' => true,
            ],
            [
                'name' => 'B173HAN04.0',
                'slug' => 'b173han04-0',
                'sku' => 'DSP-B173HAN04-0',
                'category_id' => $categories['laptop-displays']->id,
                'brand_id' => $brands['dell']->id,
                'short_description' => '17.3" Full HD (1920x1080) 40-pin IPS Large Screen Display Panel',
                'description' => 'Ultra wide 17.3 inch FHD IPS replacement screen panel designed for Dell Alienware, HP Omen and Lenovo Legion 17.3 inch models.',
                'main_image' => '/images/products/panel-b173han04.png',
                'cost_price' => 26500,
                'selling_price' => 38500,
                'stock_quantity' => 10,
                'min_stock' => 2,
                'panel_number' => 'B173HAN04.0',
                'laptop_model' => 'Dell G7 17 / HP Omen 17',
                'display_size' => '17.3"',
                'pin_type' => '40-pin',
                'display_type' => 'IPS',
                'touch_type' => 'Non-Touch',
                'resolution' => '1920x1080',
                'warranty' => '6 Months Warranty',
                'is_featured' => true,
            ],
            [
                'name' => 'NV140FHM-N48',
                'slug' => 'nv140fhm-n48',
                'sku' => 'DSP-NV140FHM-N48',
                'category_id' => $categories['laptop-displays']->id,
                'brand_id' => $brands['hp']->id,
                'short_description' => '14.0" Full HD (1920x1080) 30-pin Ultra-Slim IPS Display',
                'description' => 'Crisp 14 inch Full HD IPS display panel for ultrabooks including HP EliteBook 840, ThinkPad T480 and Dell Latitude 7490.',
                'main_image' => '/images/products/panel-nv140fhm.png',
                'cost_price' => 17000,
                'selling_price' => 24900,
                'stock_quantity' => 14,
                'min_stock' => 2,
                'panel_number' => 'NV140FHM-N48',
                'laptop_model' => 'HP EliteBook 840 G5 / Dell Latitude 7490',
                'display_size' => '14"',
                'pin_type' => '30-pin',
                'display_type' => 'IPS',
                'touch_type' => 'Non-Touch',
                'resolution' => '1920x1080',
                'warranty' => '6 Months Warranty',
                'is_featured' => true,
            ]
        ];

        $products = [];
        foreach ($productsData as $pData) {
            $product = Product::updateOrCreate(['sku' => $pData['sku']], $pData);
            $products[] = $product;

            // Add variations
            ProductVariation::updateOrCreate(
                ['sku' => $product->sku . '-VAR1'],
                [
                    'product_id' => $product->id,
                    'panel_number' => $product->panel_number,
                    'laptop_model' => $product->laptop_model,
                    'brand' => $product->brand ? $product->brand->name : 'Universal',
                    'display_size' => $product->display_size,
                    'pin_type' => $product->pin_type,
                    'touch_type' => $product->touch_type,
                    'display_type' => $product->display_type,
                    'resolution' => $product->resolution,
                    'price' => $product->selling_price,
                    'stock_quantity' => $product->stock_quantity,
                    'image' => $product->main_image,
                ]
            );
        }

        // 6. Sample Orders (so the admin dashboard has real, non-zero data)
        $sampleOrders = [
            ['name' => 'Nimal Perera', 'phone' => '0771234567', 'city' => 'Colombo', 'status' => 'Pending', 'payment' => 'Pending', 'days_ago' => 0, 'items' => [[0, 1]]],
            ['name' => 'Tech Solutions Lanka', 'phone' => '0777654321', 'city' => 'Kandy', 'status' => 'Processing', 'payment' => 'Paid', 'days_ago' => 0, 'items' => [[1, 2], [3, 1]]],
            ['name' => 'Gayan Weerasinghe', 'phone' => '0712223334', 'city' => 'Galle', 'status' => 'Shipped', 'payment' => 'Paid', 'days_ago' => 1, 'items' => [[2, 1]]],
            ['name' => 'Lakmal Holdings', 'phone' => '0765554443', 'city' => 'Negombo', 'status' => 'Delivered', 'payment' => 'Paid', 'days_ago' => 1, 'items' => [[4, 1]]],
            ['name' => 'Chamara Fernando', 'phone' => '0723334445', 'city' => 'Colombo', 'status' => 'Pending', 'payment' => 'Pending', 'days_ago' => 2, 'items' => [[5, 1]]],
            ['name' => 'Ruwan Silva', 'phone' => '0701112223', 'city' => 'Matara', 'status' => 'Delivered', 'payment' => 'Paid', 'days_ago' => 3, 'items' => [[0, 1], [5, 1]]],
        ];

        foreach ($sampleOrders as $idx => $o) {
            $subtotal = 0;
            $orderItems = [];
            foreach ($o['items'] as [$productIdx, $qty]) {
                $product = $products[$productIdx];
                $lineTotal = $product->selling_price * $qty;
                $subtotal += $lineTotal;
                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $product->selling_price,
                    'subtotal' => $lineTotal,
                ];
            }
            $shipping = 650;
            $orderNumber = 'ORD-00' . (1240 + $idx);

            $order = Order::updateOrCreate(
                ['order_number' => $orderNumber],
                [
                    'invoice_number' => 'INV-00' . (1240 + $idx),
                    'customer_name' => $o['name'],
                    'customer_email' => Str::slug($o['name']) . '@example.com',
                    'customer_phone' => $o['phone'],
                    'shipping_address' => '123 Main Street',
                    'city' => $o['city'],
                    'district' => $o['city'],
                    'payment_method' => 'COD',
                    'payment_status' => $o['payment'],
                    'shipping_status' => $o['status'],
                    'order_status' => $o['status'],
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shipping,
                    'discount' => 0,
                    'grand_total' => $subtotal + $shipping,
                    'created_at' => now()->subDays($o['days_ago']),
                    'updated_at' => now()->subDays($o['days_ago']),
                ]
            );

            foreach ($orderItems as $item) {
                OrderItem::updateOrCreate(
                    ['order_id' => $order->id, 'product_id' => $item['product_id']],
                    $item
                );
            }
        }
    }
}
