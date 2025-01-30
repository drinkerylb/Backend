<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Coupon;
use App\Models\OrderItem;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::with(['items.product', 'coupon'])
            ->when(request('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when(request('from_date'), function ($query, $date) {
                $query->whereDate('created_at', '>=', $date);
            })
            ->when(request('to_date'), function ($query, $date) {
                $query->whereDate('created_at', '<=', $date);
            })
            ->paginate(request('per_page', 15));

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipping_address' => 'required|string',
            'billing_address' => 'required|string',
            'payment_method' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'coupon_code' => 'nullable|string|exists:coupons,code'
        ]);

        \DB::beginTransaction();

        try {
            // Create order
            $order = new Order([
                'user_id' => auth()->id(),
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'shipping_address' => $validated['shipping_address'],
                'billing_address' => $validated['billing_address'],
                'payment_method' => $validated['payment_method'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'shipping' => 10.00, // Default shipping cost
            ]);

            $order->save();

            // Add items
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                $orderItem = new OrderItem([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price
                ]);

                $order->items()->save($orderItem);
                
                // Update product stock
                $product->decrement('stock', $item['quantity']);
            }

            // Apply coupon if provided
            if (!empty($validated['coupon_code'])) {
                $coupon = Coupon::where('code', $validated['coupon_code'])->first();
                if ($coupon && $coupon->isValid()) {
                    $discount = $coupon->calculateDiscount($order->subtotal);
                    $order->update([
                        'coupon_id' => $coupon->id,
                        'discount' => $discount
                    ]);
                    $coupon->increment('times_used');
                }
            }

            $order->recalculateTotal();

            \DB::commit();

            return response()->json($order->load('items.product'), 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        return response()->json($order->load('items.product', 'coupon'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,processing,completed,cancelled',
            'payment_status' => 'sometimes|in:pending,paid,failed',
            'shipping_address' => 'sometimes|string',
            'billing_address' => 'sometimes|string'
        ]);

        $order->update($validated);

        return response()->json($order->load('items.product'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Cannot delete non-pending order'], 400);
        }

        \DB::transaction(function () use ($order) {
            // Restore product stock
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }
            
            $order->delete();
        });

        return response()->json(null, 204);
    }

    public function items(Order $order)
    {
        return response()->json($order->items()->with('product')->get());
    }

    public function addItem(Request $request, Order $order)
    {
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Cannot modify non-pending order'], 400);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        \DB::transaction(function () use ($order, $validated) {
            $product = Product::findOrFail($validated['product_id']);
            
            if ($product->stock < $validated['quantity']) {
                throw new \Exception("Insufficient stock for product: {$product->name}");
            }

            $orderItem = new OrderItem([
                'product_id' => $product->id,
                'quantity' => $validated['quantity'],
                'unit_price' => $product->price
            ]);

            $order->items()->save($orderItem);
            $product->decrement('stock', $validated['quantity']);
            $order->recalculateTotal();
        });

        return response()->json($order->load('items.product'));
    }

    public function removeItem(Order $order, OrderItem $item)
    {
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Cannot modify non-pending order'], 400);
        }

        if ($item->order_id !== $order->id) {
            return response()->json(['message' => 'Item does not belong to this order'], 400);
        }

        \DB::transaction(function () use ($item) {
            // Restore product stock
            $item->product->increment('stock', $item->quantity);
            $item->delete();
            $item->order->recalculateTotal();
        });

        return response()->json($order->load('items.product'));
    }
}
