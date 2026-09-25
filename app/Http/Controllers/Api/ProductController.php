<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Setting;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with([
            'category',
            'brand',
            'productType',
            'laptopModels',
            'partNumbers',
            'tags',
            'imagesRelation',
            'variations'
        ])->where('status', 'active');

        // ==========================================
        // 1. GLOBAL SEARCH ACROSS 18 ATTRIBUTES
        // ==========================================
        if ($request->filled('search')) {
            $raw = trim($request->search);
            $s = $raw;

            // Extract numeric screen size if present in query (e.g., '15.6', '15.6"', '15.6 inch')
            $detectedSize = null;
            if (preg_match('/(10\.1|11\.6|12\.0|12\.5|13\.3|13\.4|14\.0|14\.5|15\.0|15\.6|16\.0|16\.1|17\.0|17\.3|18\.0|14|15|16|17)/i', $raw, $matches)) {
                $detectedSize = $matches[1];
                if ($detectedSize === '14') $detectedSize = '14';
                if ($detectedSize === '15') $detectedSize = '15.6';
                if ($detectedSize === '16') $detectedSize = '16';
                if ($detectedSize === '17') $detectedSize = '17.3';
            }

            $query->where(function ($q) use ($s, $detectedSize) {
                // 1. Product Name & SKU
                $q->where('products.name', 'like', "%{$s}%")
                  ->orWhere('products.sku', 'like', "%{$s}%")
                  ->orWhere('products.panel_number', 'like', "%{$s}%")
                  ->orWhere('products.laptop_model', 'like', "%{$s}%");

                // 2. Screen Size Match
                if ($detectedSize) {
                    $q->orWhere('products.display_size', 'like', "%{$detectedSize}%");
                }
                $q->orWhere('products.display_size', 'like', "%{$s}%");

                // 3. Technical Specifications
                $q->orWhere('products.pin_type', 'like', "%{$s}%")
                  ->orWhere('products.display_type', 'like', "%{$s}%")
                  ->orWhere('products.resolution', 'like', "%{$s}%")
                  ->orWhere('products.connector', 'like', "%{$s}%")
                  ->orWhere('products.description', 'like', "%{$s}%")
                  ->orWhere('products.short_description', 'like', "%{$s}%");

                // Touch handling: Don't match Non-Touch when customer searches "Touch"
                if (stripos($s, 'non-touch') !== false || stripos($s, 'nontouch') !== false) {
                    $q->orWhere('products.touch_type', 'like', '%Non-Touch%');
                } elseif (stripos($s, 'touch') !== false) {
                    $q->orWhere(function ($tq) {
                        $tq->where('products.touch_type', 'Touch')
                           ->orWhere(function ($sub) {
                               $sub->where('products.touch_type', 'like', '%Touch%')
                                   ->where('products.touch_type', 'not like', '%Non%');
                           });
                    });
                } else {
                    $q->orWhere('products.touch_type', 'like', "%{$s}%");
                }

                // 4. Relational: Brand Name (e.g. "HP", "Apple", "Dell", "Lenovo")
                $q->orWhereHas('brand', function ($b) use ($s) {
                    $b->where('name', 'like', "%{$s}%")
                      ->orWhere('slug', 'like', "%{$s}%");
                });

                // 5. Special Apple / MacBook matching
                if (stripos($s, 'macbook') !== false || stripos($s, 'apple') !== false) {
                    $q->orWhereHas('brand', function ($b) {
                        $b->where('slug', 'apple')->orWhere('name', 'Apple');
                    })->orWhereHas('productType', function ($pt) {
                        $pt->where('slug', 'like', '%macbook%')->orWhere('name', 'like', '%macbook%');
                    });
                }

                // 6. Relational: Category & Product Type
                $q->orWhereHas('category', function ($c) use ($s) {
                    $c->where('name', 'like', "%{$s}%")->orWhere('slug', 'like', "%{$s}%");
                });

                $q->orWhereHas('productType', function ($pt) use ($s) {
                    $pt->where('name', 'like', "%{$s}%")->orWhere('slug', 'like', "%{$s}%");
                });

                // 7. Relational: Compatible Laptop Models (e.g. "HP Pavilion 15", "A2337", "EliteBook")
                $q->orWhereHas('laptopModels', function ($m) use ($s) {
                    $m->where('model_name', 'like', "%{$s}%");
                });

                // 8. Relational: Multiple Part Numbers (e.g. "B156HAN08.0", "NV156FHM")
                $q->orWhereHas('partNumbers', function ($p) use ($s) {
                    $p->where('part_number', 'like', "%{$s}%");
                });

                // 9. Relational: Product Tags
                $q->orWhereHas('tags', function ($t) use ($s) {
                    $t->where('tag', 'like', "%{$s}%");
                });
            });
        }

        // ==========================================
        // 2. COMBINED CUSTOMER FILTERS (AND LOGIC)
        // ==========================================

        // Filter by Category
        if ($request->filled('category')) {
            $catSlug = $request->category;
            $query->whereHas('category', function ($q) use ($catSlug) {
                if (is_numeric($catSlug)) {
                    $q->where('id', $catSlug);
                } else {
                    $q->where('slug', $catSlug);
                }
            });
        }

        // Filter by Product Type
        if ($request->filled('product_type') || $request->filled('type')) {
            $typeParam = $request->get('product_type', $request->get('type'));
            $query->whereHas('productType', function ($q) use ($typeParam) {
                if (is_numeric($typeParam)) {
                    $q->where('id', $typeParam);
                } else {
                    $q->where('slug', $typeParam)->orWhere('name', 'like', "%{$typeParam}%");
                }
            });
        }

        // Filter by Brand (Single or Multi-select comma-separated)
        if ($request->filled('brand')) {
            $brandParam = $request->brand;
            $brandList = is_array($brandParam) ? $brandParam : explode(',', $brandParam);
            $brandList = array_map('trim', array_filter($brandList));

            if (!empty($brandList)) {
                $query->whereHas('brand', function ($q) use ($brandList) {
                    $q->whereIn('slug', $brandList)
                      ->orWhereIn('name', $brandList);
                });
            }
        }

        // Filter by Screen Size (Single or Multi-select e.g. "15.6,14.0")
        if ($request->filled('size') || $request->filled('screen_size')) {
            $sizeParam = $request->get('screen_size', $request->get('size'));
            $sizeList = is_array($sizeParam) ? $sizeParam : explode(',', $sizeParam);
            $sizeList = array_map('trim', array_filter($sizeList));

            if (!empty($sizeList)) {
                $query->where(function ($q) use ($sizeList) {
                    foreach ($sizeList as $sVal) {
                        // Strip quotes and "inch" for clean matching
                        $cleanVal = trim(str_replace(['"', 'inch', 'Inch'], '', $sVal));
                        $q->orWhere('products.display_size', 'like', "%{$cleanVal}%");
                    }
                });
            }
        }

        // Filter by Pin Type (30-pin, 40-pin)
        if ($request->filled('pin') || $request->filled('pin_type')) {
            $pin = $request->get('pin', $request->get('pin_type'));
            $query->where('products.pin_type', 'like', "%{$pin}%");
        }

        // Filter by Touch Type
        if ($request->filled('touch') || $request->filled('touch_type')) {
            $touchVal = $request->get('touch', $request->get('touch_type'));
            if ($touchVal === 'Touch' || $touchVal === '1' || $touchVal === 'true') {
                $query->where('products.touch_type', 'Touch');
            } elseif ($touchVal === 'Non-Touch' || $touchVal === '0' || $touchVal === 'false') {
                $query->where('products.touch_type', 'Non-Touch');
            }
        }

        // Filter by Display Type (IPS, TN, OLED)
        if ($request->filled('display_type') || $request->filled('ips')) {
            $ipsVal = $request->get('ips');
            $dispVal = $request->get('display_type');
            if ($ipsVal === '1' || $ipsVal === 'true' || $dispVal === 'IPS') {
                $query->where('products.display_type', 'IPS');
            } elseif (!empty($dispVal)) {
                $query->where('products.display_type', $dispVal);
            }
        }

        // Price range
        if ($request->filled('min_price')) {
            $query->where('products.selling_price', '>=', (float)$request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('products.selling_price', '<=', (float)$request->max_price);
        }

        // ==========================================
        // 3. SORTING & RELEVANCE
        // ==========================================
        $sortBy = $request->get('sort', 'popular');

        if ($request->filled('search')) {
            // When search is active, prioritize match strength
            $term = trim($request->search);
            $query->orderByRaw("
                CASE
                    WHEN products.sku = ? THEN 100
                    WHEN products.panel_number = ? THEN 95
                    WHEN products.name LIKE ? THEN 85
                    WHEN products.display_size LIKE ? THEN 70
                    WHEN products.laptop_model LIKE ? THEN 60
                    ELSE 10
                END DESC
            ", [$term, $term, "{$term}%", "%{$term}%", "%{$term}%"]);
        }

        if ($sortBy === 'price_asc') {
            $query->orderBy('products.selling_price', 'asc');
        } elseif ($sortBy === 'price_desc') {
            $query->orderBy('products.selling_price', 'desc');
        } elseif ($sortBy === 'newest') {
            $query->orderBy('products.created_at', 'desc');
        } else {
            $query->orderBy('products.is_featured', 'desc')->orderBy('products.id', 'desc');
        }

        $perPage = (int) $request->get('per_page', 24);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
            ]
        ]);
    }

    public function show($slug)
    {
        $product = Product::with([
            'category',
            'brand',
            'productType',
            'laptopModels',
            'partNumbers',
            'tags',
            'imagesRelation',
            'variations'
        ])
        ->where('slug', $slug)
        ->orWhere('id', is_numeric($slug) ? (int)$slug : 0)
        ->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    public function homepage()
    {
        // 1. Featured Products: If not enough products marked featured, include latest active products
        $featuredProducts = Product::with([
            'category',
            'brand',
            'productType',
            'laptopModels',
            'partNumbers',
            'imagesRelation',
            'variations'
        ])
        ->where('status', 'active')
        ->where('is_featured', true)
        ->take(10)
        ->get();

        if ($featuredProducts->count() < 4) {
            $additional = Product::with([
                'category',
                'brand',
                'productType',
                'laptopModels',
                'partNumbers',
                'imagesRelation',
                'variations'
            ])
            ->where('status', 'active')
            ->whereNotIn('id', $featuredProducts->pluck('id'))
            ->orderBy('id', 'desc')
            ->take(8 - $featuredProducts->count())
            ->get();

            $featuredProducts = $featuredProducts->concat($additional);
        }

        // 2. All Active Banners
        $banners = Banner::where('status', true)->orderBy('sort_order')->get();

        // 3. Taxonomies
        $categories = Category::where('status', true)->orderBy('sort_order')->get();
        $brands = Brand::where('status', true)->get();
        $productTypes = ProductType::where('status', true)->orderBy('sort_order')->get();
        $sizes = Size::where('status', true)->orderBy('sort_order')->get();
        $whatsappNumber = Setting::get('whatsapp', '94766025870');

        return response()->json([
            'success' => true,
            'data' => [
                'banners' => $banners,
                'featured_products' => $featuredProducts,
                'categories' => $categories,
                'brands' => $brands,
                'product_types' => $productTypes,
                'sizes' => $sizes,
                'whatsapp_number' => $whatsappNumber,
            ]
        ]);
    }

    public function homeProducts()
    {
        $products = Product::with([
            'category',
            'brand',
            'productType',
            'laptopModels',
            'partNumbers',
            'imagesRelation',
            'variations'
        ])
        ->where('status', 'active')
        ->orderBy('is_featured', 'desc')
        ->orderBy('id', 'desc')
        ->take(12)
        ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    public function sizes()
    {
        $sizes = Size::where('status', true)->orderBy('sort_order')->get();
        return response()->json([
            'success' => true,
            'data' => $sizes
        ]);
    }

    public function productTypes()
    {
        $types = ProductType::where('status', true)->orderBy('sort_order')->get();
        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    public function displayFinder(Request $request)
    {
        $query = Product::with([
            'category',
            'brand',
            'productType',
            'laptopModels',
            'partNumbers',
            'variations'
        ])->where('status', 'active');

        if ($request->filled('brand')) {
            $query->whereHas('brand', function ($q) use ($request) {
                $q->where('slug', $request->brand)->orWhere('name', 'like', "%{$request->brand}%");
            });
        }
        if ($request->filled('model')) {
            $m = $request->model;
            $query->where(function ($q) use ($m) {
                $q->where('laptop_model', 'like', "%{$m}%")
                  ->orWhereHas('laptopModels', fn($lm) => $lm->where('model_name', 'like', "%{$m}%"));
            });
        }
        if ($request->filled('size')) {
            $s = trim(str_replace(['"', 'inch'], '', $request->size));
            $query->where('display_size', 'like', "%{$s}%");
        }
        if ($request->filled('pin')) {
            $query->where('pin_type', 'like', "%{$request->pin}%");
        }
        if ($request->filled('touch')) {
            $query->where('touch_type', $request->touch);
        }
        if ($request->filled('display_type')) {
            $query->where('display_type', $request->display_type);
        }

        $results = $query->take(24)->get();

        return response()->json([
            'success' => true,
            'count' => $results->count(),
            'data' => $results
        ]);
    }
}
