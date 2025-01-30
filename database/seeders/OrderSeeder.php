<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users and products
        $users = \App\Models\User::all();
        $products = \App\Models\Product::all();
        $coupons = \App\Models\Coupon::where('is_active', true)->get();

        // Create 10 orders
        foreach (range(1, 10) as $i) {
            $user = $users->random();
            $orderProducts = $products->random(rand(1, 3)); // Random 1-3 products per order
            $coupon = rand(0, 1) ? $coupons->random() : null; // 50% chance of using coupon

            $order = \App\Models\Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'subtotal' => 0, // Will be calculated
                'tax' => 0, // Will be calculated
                'shipping' => 10.00,
                'total' => 0, // Will be calculated
                'coupon_id' => $coupon ? $coupon->id : null,
                'discount' => 0, // Will be calculated if coupon exists
                'status' => collect(['pending', 'processing', 'completed'])->random(),
                'shipping_address' => json_encode([
                    'street' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'state' => fake()->state(),
                    'zip' => fake()->postcode(),
                    'country' => fake()->country(),
                ]),
                'billing_address' => json_encode([
                    'street' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'state' => fake()->state(),
                    'zip' => fake()->postcode(),
                    'country' => fake()->country(),
                ]),
                'payment_method' => collect(['credit_card', 'paypal', 'bank_transfer'])->random(),
                'payment_status' => collect(['pending', 'paid', 'failed'])->random(),
            ]);

            // Create order items
            foreach ($orderProducts as $product) {
                $quantity = rand(1, 3);
                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $product->price * $quantity,
                ]);

                // Update product stock
                $product->decrement('stock', $quantity);
            }

            // Recalculate order totals
            $order->recalculateTotal();

            // If there's a coupon, apply it
            if ($coupon) {
                $discount = $coupon->calculateDiscount($order->subtotal);
                $order->update(['discount' => $discount]);
                $order->recalculateTotal();
                $coupon->increment('times_used');
            }
        }
    }
}
