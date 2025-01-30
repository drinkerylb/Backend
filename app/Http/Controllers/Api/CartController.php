<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    protected function getOrCreateCart(Request $request)
    {
        if (auth()->check()) {
            $cart = Cart::firstOrCreate(
                ['user_id' => auth()->id(), 'status' => 'active'],
                ['expires_at' => now()->addDays(7)]
            );
        } else {
            $sessionId = $request->cookie('cart_session_id', Str::uuid());
            $cart = Cart::firstOrCreate(
                ['session_id' => $sessionId, 'status' => 'active'],
                ['expires_at' => now()->addDays(7)]
            );
        }

        return $cart;
    }

    public function show(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        
        return response()->json([
            'cart' => $cart->load(['items.product', 'items.variant']),
            'subtotal' => $cart->subtotal,
            'total' => $cart->total,
            'item_count' => $cart->item_count
        ]);
    }

    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = $this->getOrCreateCart($request);
        $product = Product::findOrFail($validated['product_id']);
        $variant = isset($validated['variant_id']) ? ProductVariant::find($validated['variant_id']) : null;

        // Check stock availability
        $availableStock = $variant ? $variant->stock : $product->stock;
        if ($availableStock < $validated['quantity']) {
            return response()->json([
                'message' => 'Not enough stock available'
            ], 422);
        }

        $item = $cart->addItem($product, $validated['quantity'], $variant);

        return response()->json([
            'message' => 'Item added to cart',
            'cart' => $cart->fresh(['items.product', 'items.variant']),
            'subtotal' => $cart->subtotal,
            'total' => $cart->total,
            'item_count' => $cart->item_count
        ]);
    }

    public function updateItem(Request $request, $itemId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);

        $cart = $this->getOrCreateCart($request);
        $item = $cart->items()->findOrFail($itemId);

        // Check stock availability
        $product = $item->product;
        $variant = $item->variant;
        $availableStock = $variant ? $variant->stock : $product->stock;
        if ($availableStock < $validated['quantity']) {
            return response()->json([
                'message' => 'Not enough stock available'
            ], 422);
        }

        $cart->updateItemQuantity($itemId, $validated['quantity']);

        return response()->json([
            'message' => 'Cart updated',
            'cart' => $cart->fresh(['items.product', 'items.variant']),
            'subtotal' => $cart->subtotal,
            'total' => $cart->total,
            'item_count' => $cart->item_count
        ]);
    }

    public function removeItem(Request $request, $itemId)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->removeItem($itemId);

        return response()->json([
            'message' => 'Item removed from cart',
            'cart' => $cart->fresh(['items.product', 'items.variant']),
            'subtotal' => $cart->subtotal,
            'total' => $cart->total,
            'item_count' => $cart->item_count
        ]);
    }

    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->clear();

        return response()->json([
            'message' => 'Cart cleared',
            'cart' => $cart->fresh(),
            'subtotal' => 0,
            'total' => 0,
            'item_count' => 0
        ]);
    }

    public function applyCoupon(Request $request)
    {
        // TODO: Implement coupon logic
        return response()->json([
            'message' => 'Coupon functionality coming soon'
        ]);
    }

    public function removeCoupon(Request $request)
    {
        // TODO: Implement coupon logic
        return response()->json([
            'message' => 'Coupon functionality coming soon'
        ]);
    }
} 