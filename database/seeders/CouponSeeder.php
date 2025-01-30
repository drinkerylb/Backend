<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'type' => 'percentage',
                'value' => 10,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(1),
                'max_uses' => 100,
                'times_used' => 0,
                'min_order_value' => 50,
                'is_active' => true,
            ],
            [
                'code' => 'SAVE20',
                'type' => 'percentage',
                'value' => 20,
                'valid_from' => now(),
                'valid_until' => now()->addDays(7),
                'max_uses' => 50,
                'times_used' => 0,
                'min_order_value' => 100,
                'is_active' => true,
            ],
            [
                'code' => 'FLAT50',
                'type' => 'fixed',
                'value' => 50,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(2),
                'max_uses' => 25,
                'times_used' => 0,
                'min_order_value' => 200,
                'is_active' => true,
            ],
            [
                'code' => 'SPECIAL25',
                'type' => 'percentage',
                'value' => 25,
                'valid_from' => now()->subDays(7),
                'valid_until' => now()->subDays(1),
                'max_uses' => 100,
                'times_used' => 100,
                'min_order_value' => 150,
                'is_active' => false,
            ],
        ];

        foreach ($coupons as $coupon) {
            \App\Models\Coupon::create($coupon);
        }
    }
}
