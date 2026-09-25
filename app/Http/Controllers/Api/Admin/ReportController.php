<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $orders = Order::where('order_status', '!=', 'Cancelled')->get();

        $byDay = $orders->groupBy(fn ($o) => $o->created_at->format('Y-m-d'))
            ->map(fn ($group, $day) => ['date' => $day, 'sales' => $group->sum('grand_total'), 'orders' => $group->count()])
            ->values()->sortBy('date')->values();

        return response()->json(['success' => true, 'data' => [
            'gross_sales' => $orders->sum('subtotal'),
            'discounts' => $orders->sum('discount'),
            'shipping' => $orders->sum('shipping_cost'),
            'net_sales' => $orders->sum('grand_total'),
            'order_count' => $orders->count(),
            'average_order_value' => $orders->count() > 0 ? round($orders->sum('grand_total') / $orders->count(), 2) : 0,
            'by_day' => $byDay,
        ]]);
    }

    public function orders()
    {
        $counts = Order::select('order_status', DB::raw('count(*) as total'))
            ->groupBy('order_status')->pluck('total', 'order_status');

        return response()->json(['success' => true, 'data' => [
            'total' => Order::count(),
            'by_status' => $counts,
        ]]);
    }

    public function products()
    {
        $topProducts = OrderItem::select('product_name', DB::raw('sum(quantity) as units_sold'), DB::raw('sum(subtotal) as revenue'))
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return response()->json(['success' => true, 'data' => $topProducts]);
    }

    public function inventory()
    {
        $products = Product::all();
        $stockValue = $products->sum(fn ($p) => $p->stock_quantity * $p->cost_price);
        $retailValue = $products->sum(fn ($p) => $p->stock_quantity * $p->selling_price);

        return response()->json(['success' => true, 'data' => [
            'total_units' => $products->sum('stock_quantity'),
            'stock_value_cost' => $stockValue,
            'stock_value_retail' => $retailValue,
            'low_stock_count' => $products->filter(fn ($p) => $p->stock_quantity > 0 && $p->stock_quantity <= $p->min_stock)->count(),
            'out_of_stock_count' => $products->filter(fn ($p) => $p->stock_quantity === 0)->count(),
        ]]);
    }

    public function customers()
    {
        $topCustomers = Order::select('customer_name', 'customer_phone', DB::raw('count(*) as order_count'), DB::raw('sum(grand_total) as total_spent'))
            ->groupBy('customer_name', 'customer_phone')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        return response()->json(['success' => true, 'data' => [
            'total_customers' => User::where('role', 'customer')->count(),
            'top_customers' => $topCustomers,
        ]]);
    }

    public function purchases()
    {
        $bySupplier = PurchaseOrder::select('supplier_id', DB::raw('count(*) as order_count'), DB::raw('sum(total) as total_spent'))
            ->groupBy('supplier_id')
            ->with('supplier:id,name')
            ->orderByDesc('total_spent')
            ->get();

        return response()->json(['success' => true, 'data' => [
            'total_orders' => PurchaseOrder::count(),
            'total_spent' => PurchaseOrder::sum('total'),
            'by_supplier' => $bySupplier,
        ]]);
    }

    public function profit()
    {
        $items = OrderItem::with('product:id,cost_price')->get();
        $revenue = $items->sum('subtotal');
        $cost = $items->sum(fn ($i) => ($i->product->cost_price ?? 0) * $i->quantity);

        return response()->json(['success' => true, 'data' => [
            'revenue' => $revenue,
            'cost' => $cost,
            'gross_profit' => $revenue - $cost,
            'margin_pct' => $revenue > 0 ? round((($revenue - $cost) / $revenue) * 100, 1) : 0,
        ]]);
    }
}
