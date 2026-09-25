<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductLaptopModel;
use App\Models\ProductPartNumber;
use App\Models\ProductTag;
use App\Models\ProductType;
use App\Models\ProductVariation;
use App\Models\Setting;
use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalSales = Order::where('order_status', '!=', 'Cancelled')->sum('grand_total');
        $todaySales = Order::where('order_status', '!=', 'Cancelled')->whereDate('created_at', date('Y-m-d'))->sum('grand_total');
        $totalOrders = Order::count();
        $pendingOrders = Order::where('order_status', 'Pending')->count();
        $totalProducts = Product::count();
        $lowStockProducts = Product::whereColumn('stock_quantity', '<=', 'min_stock')->get();
        $outOfStockCount = Product::where('stock_quantity', 0)->count();
        $totalCustomers = User::where('role', 'customer')->count();
        $recentOrders = Order::orderBy('id', 'desc')->take(10)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_sales' => $totalSales,
                'today_sales' => $todaySales,
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'total_products' => $totalProducts,
                'low_stock_count' => $lowStockProducts->count(),
                'out_of_stock_count' => $outOfStockCount,
                'total_customers' => $totalCustomers,
                'low_stock_products' => $lowStockProducts,
                'recent_orders' => $recentOrders,
            ]
        ]);
    }

    public function orders(Request $request)
    {
        $query = Order::with('items')->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%")
                  ->orWhere('customer_phone', 'like', "%{$s}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(20)
        ]);
    }

    public function orderShow($id)
    {
        $order = Order::with(['items', 'statusHistories.creator'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $order]);
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'order_status' => 'required|string',
            'payment_status' => 'nullable|string',
            'shipping_status' => 'nullable|string',
            'comment' => 'nullable|string',
        ]);

        $order = Order::findOrFail($id);
        $previousStatus = $order->order_status;
        $order->update([
            'order_status' => $request->order_status,
            'payment_status' => $request->get('payment_status', $order->payment_status),
            'shipping_status' => $request->get('shipping_status', $order->shipping_status),
        ]);

        $order->statusHistories()->create([
            'status' => $request->order_status,
            'comment' => $request->comment ?? 'Status updated by admin',
            'created_by' => $request->user() ? $request->user()->id : null,
        ]);

        ActivityLog::record(
            'Updated Order Status',
            'Orders',
            "#{$order->order_number}",
            ['order_status' => $previousStatus],
            ['order_status' => $request->order_status],
            $request->user()?->id,
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    public function productsIndex(Request $request)
    {
        $query = Product::with([
            'category',
            'brand',
            'productType',
            'partNumbers',
            'laptopModels',
            'imagesRelation'
        ])->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('sku', 'like', "%{$s}%")
                  ->orWhere('panel_number', 'like', "%{$s}%")
                  ->orWhere('laptop_model', 'like', "%{$s}%")
                  ->orWhere('display_size', 'like', "%{$s}%")
                  ->orWhereHas('brand', fn($b) => $b->where('name', 'like', "%{$s}%"))
                  ->orWhereHas('partNumbers', fn($p) => $p->where('part_number', 'like', "%{$s}%"))
                  ->orWhereHas('laptopModels', fn($m) => $m->where('model_name', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('product_type_id')) {
            $query->where('product_type_id', $request->product_type_id);
        }
        if ($request->filled('display_size')) {
            $query->where('display_size', $request->display_size);
        }
        if ($request->filled('stock')) {
            if ($request->stock === 'low') {
                $query->whereColumn('stock_quantity', '<=', 'min_stock')->where('stock_quantity', '>', 0);
            } elseif ($request->stock === 'out') {
                $query->where('stock_quantity', 0);
            }
        }

        return response()->json(['success' => true, 'data' => $query->paginate(20)]);
    }

    public function productShow($id)
    {
        $product = Product::with([
            'category',
            'brand',
            'productType',
            'partNumbers',
            'laptopModels',
            'tags',
            'imagesRelation',
            'variations'
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $product]);
    }

    public function customersIndex(Request $request)
    {
        $query = User::where('role', 'customer')
            ->withCount('orders')
            ->withSum('orders as total_spent', 'grand_total')
            ->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        return response()->json(['success' => true, 'data' => $query->paginate(20)]);
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'category_id' => 'nullable|integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'product_type_id' => 'nullable|integer|exists:product_types,id',
            'cost_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:active,draft,archived',
            'main_image' => 'nullable|string',
            'display_size' => 'nullable|string|max:50',
            'pin_type' => 'nullable|string|max:50',
            'display_type' => 'nullable|string|max:50',
            'touch_type' => 'nullable|string|max:50',
            'resolution' => 'nullable|string|max:100',
            'refresh_rate' => 'nullable|string|max:50',
            'connector' => 'nullable|string|max:100',
            'warranty' => 'nullable|string|max:100',
            'condition' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_new' => 'nullable|boolean',
            'is_sale' => 'nullable|boolean',
            'laptop_models' => 'nullable',
            'part_numbers' => 'nullable',
            'tags' => 'nullable',
            'images' => 'nullable',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $slugBase = Str::slug($request->name);
            $slug = $slugBase;
            $counter = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = "{$slugBase}-{$counter}";
                $counter++;
            }

            // Normalise laptop models array
            $modelsList = $this->parseArrayInput($request->input('laptop_models'));
            $partsList = $this->parseArrayInput($request->input('part_numbers'));
            $tagsList = $this->parseArrayInput($request->input('tags'));
            $imagesList = $this->parseArrayInput($request->input('images'));

            $productData = array_merge($validated, [
                'slug' => $slug,
                'status' => $request->input('status', 'active'),
                'is_featured' => filter_var($request->input('is_featured', false), FILTER_VALIDATE_BOOLEAN),
                'is_new' => filter_var($request->input('is_new', false), FILTER_VALIDATE_BOOLEAN),
                'is_sale' => filter_var($request->input('is_sale', false), FILTER_VALIDATE_BOOLEAN),
                'laptop_model' => !empty($modelsList) ? implode(' / ', $modelsList) : $request->input('laptop_model'),
                'panel_number' => !empty($partsList) ? $partsList[0] : $request->input('panel_number'),
                'images' => !empty($imagesList) ? $imagesList : null,
            ]);

            unset($productData['laptop_models'], $productData['part_numbers'], $productData['tags']);

            $product = Product::create($productData);

            // Sync laptop models
            foreach ($modelsList as $m) {
                if (trim($m) !== '') {
                    ProductLaptopModel::create([
                        'product_id' => $product->id,
                        'model_name' => trim($m),
                    ]);
                }
            }

            // Sync part numbers
            foreach ($partsList as $p) {
                if (trim($p) !== '') {
                    ProductPartNumber::create([
                        'product_id' => $product->id,
                        'part_number' => trim($p),
                    ]);
                }
            }

            // Sync tags
            foreach ($tagsList as $t) {
                if (trim($t) !== '') {
                    ProductTag::create([
                        'product_id' => $product->id,
                        'tag' => trim($t),
                    ]);
                }
            }

            // Sync images
            if (!empty($product->main_image)) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $product->main_image,
                    'image_type' => 'main',
                    'sort_order' => 0,
                ]);
            }

            foreach ($imagesList as $idx => $img) {
                $imgUrl = is_array($img) ? ($img['url'] ?? $img['image_url'] ?? '') : $img;
                if (!empty($imgUrl) && $imgUrl !== $product->main_image) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $imgUrl,
                        'image_type' => 'gallery',
                        'sort_order' => $idx + 1,
                    ]);
                }
            }

            // Clear cache
            Cache::flush();

            $product->load([
                'category',
                'brand',
                'productType',
                'laptopModels',
                'partNumbers',
                'tags',
                'imagesRelation'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);
        });
    }

    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'sometimes|required|string|max:100|unique:products,sku,' . $id,
            'selling_price' => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'category_id' => 'nullable|integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'product_type_id' => 'nullable|integer|exists:product_types,id',
            'cost_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:active,draft,archived',
            'main_image' => 'nullable|string',
            'display_size' => 'nullable|string|max:50',
            'pin_type' => 'nullable|string|max:50',
            'display_type' => 'nullable|string|max:50',
            'touch_type' => 'nullable|string|max:50',
            'resolution' => 'nullable|string|max:100',
            'refresh_rate' => 'nullable|string|max:50',
            'connector' => 'nullable|string|max:100',
            'warranty' => 'nullable|string|max:100',
            'condition' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_new' => 'nullable|boolean',
            'is_sale' => 'nullable|boolean',
            'laptop_models' => 'nullable',
            'part_numbers' => 'nullable',
            'tags' => 'nullable',
            'images' => 'nullable',
        ]);

        return DB::transaction(function () use ($request, $product, $validated) {
            $modelsList = $request->has('laptop_models') ? $this->parseArrayInput($request->input('laptop_models')) : null;
            $partsList = $request->has('part_numbers') ? $this->parseArrayInput($request->input('part_numbers')) : null;
            $tagsList = $request->has('tags') ? $this->parseArrayInput($request->input('tags')) : null;
            $imagesList = $request->has('images') ? $this->parseArrayInput($request->input('images')) : null;

            $updateData = $validated;
            if ($modelsList !== null) {
                $updateData['laptop_model'] = implode(' / ', $modelsList);
            }
            if ($partsList !== null && count($partsList) > 0) {
                $updateData['panel_number'] = $partsList[0];
            }
            if ($imagesList !== null) {
                $updateData['images'] = $imagesList;
            }

            unset($updateData['laptop_models'], $updateData['part_numbers'], $updateData['tags']);

            $product->update($updateData);

            // Sync models if provided
            if ($modelsList !== null) {
                $product->laptopModels()->delete();
                foreach ($modelsList as $m) {
                    if (trim($m) !== '') {
                        ProductLaptopModel::create([
                            'product_id' => $product->id,
                            'model_name' => trim($m),
                        ]);
                    }
                }
            }

            // Sync parts if provided
            if ($partsList !== null) {
                $product->partNumbers()->delete();
                foreach ($partsList as $p) {
                    if (trim($p) !== '') {
                        ProductPartNumber::create([
                            'product_id' => $product->id,
                            'part_number' => trim($p),
                        ]);
                    }
                }
            }

            // Sync tags if provided
            if ($tagsList !== null) {
                $product->tags()->delete();
                foreach ($tagsList as $t) {
                    if (trim($t) !== '') {
                        ProductTag::create([
                            'product_id' => $product->id,
                            'tag' => trim($t),
                        ]);
                    }
                }
            }

            // Sync images if provided or if main_image changed
            if ($imagesList !== null || $request->has('main_image')) {
                $product->imagesRelation()->delete();

                if (!empty($product->main_image)) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $product->main_image,
                        'image_type' => 'main',
                        'sort_order' => 0,
                    ]);
                }

                $list = $imagesList ?? ($product->images ?: []);
                foreach ($list as $idx => $img) {
                    $imgUrl = is_array($img) ? ($img['url'] ?? $img['image_url'] ?? '') : $img;
                    if (!empty($imgUrl) && $imgUrl !== $product->main_image) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_url' => $imgUrl,
                            'image_type' => 'gallery',
                            'sort_order' => $idx + 1,
                        ]);
                    }
                }
            }

            Cache::flush();

            $product->load([
                'category',
                'brand',
                'productType',
                'laptopModels',
                'partNumbers',
                'tags',
                'imagesRelation'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product
            ]);
        });
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    public function uploadImage(Request $request, ImageUploadService $uploader)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'folder' => 'nullable|string|in:products,banners',
        ]);

        $folder = $request->input('folder', 'products');
        $maxWidth = $folder === 'banners' ? 1920 : 1200;
        $maxHeight = $folder === 'banners' ? 800 : 1200;

        try {
            $result = $uploader->processAndStore(
                $request->file('image'),
                $folder,
                $maxWidth,
                $maxHeight,
                85
            );

            return response()->json([
                'success' => true,
                'message' => 'Image processed, compressed, and converted to WebP successfully.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Image upload failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to process image: ' . $e->getMessage()
            ], 500);
        }
    }

    private function parseArrayInput($input): array
    {
        if (is_array($input)) {
            return array_values(array_filter($input, fn($v) => !is_null($v) && $v !== ''));
        }
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, fn($v) => !is_null($v) && $v !== ''));
            }
            return array_values(array_filter(array_map('trim', explode(',', $input)), fn($v) => $v !== ''));
        }
    }

    public function inventoryLogs()
    {
        $logs = InventoryTransaction::with(['product', 'variation', 'user'])->orderBy('id', 'desc')->paginate(30);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function updateStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'change_quantity' => 'required|integer',
            'reason' => 'required|string',
        ]);

        $product = Product::findOrFail($request->product_id);
        $prevStock = $product->stock_quantity;
        $newStock = max(0, $prevStock + $request->change_quantity);
        $product->update(['stock_quantity' => $newStock]);

        InventoryTransaction::create([
            'product_id' => $product->id,
            'previous_quantity' => $prevStock,
            'change_quantity' => $request->change_quantity,
            'new_quantity' => $newStock,
            'reason' => $request->reason,
            'user_id' => $request->user() ? $request->user()->id : null,
        ]);

        ActivityLog::record(
            'Adjusted Stock',
            'Inventory',
            $product->name,
            ['stock_quantity' => $prevStock],
            ['stock_quantity' => $newStock, 'reason' => $request->reason],
            $request->user()?->id,
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock updated successfully',
            'new_stock' => $newStock
        ]);
    }

    public function getSettings()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function updateSettings(Request $request)
    {
        foreach ($request->all() as $k => $v) {
            Setting::set($k, $v);
        }
        return response()->json(['success' => true, 'message' => 'Settings updated successfully']);
    }
}
