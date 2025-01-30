<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Gaming Laptop',
                'description' => 'High-performance gaming laptop with RTX 3080',
                'price' => 1999.99,
                'stock' => 10,
                'sku' => 'LAPTOP-001',
                'image_url' => 'https://example.com/images/laptop.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Wireless Mouse',
                'description' => 'Ergonomic wireless mouse with long battery life',
                'price' => 49.99,
                'stock' => 50,
                'sku' => 'MOUSE-001',
                'image_url' => 'https://example.com/images/mouse.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Mechanical Keyboard',
                'description' => 'RGB mechanical keyboard with Cherry MX switches',
                'price' => 129.99,
                'stock' => 30,
                'sku' => 'KB-001',
                'image_url' => 'https://example.com/images/keyboard.jpg',
                'is_active' => true,
            ],
            [
                'name' => '4K Monitor',
                'description' => '32-inch 4K HDR monitor for professional use',
                'price' => 699.99,
                'stock' => 15,
                'sku' => 'MON-001',
                'image_url' => 'https://example.com/images/monitor.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Gaming Headset',
                'description' => '7.1 surround sound gaming headset',
                'price' => 89.99,
                'stock' => 40,
                'sku' => 'HEAD-001',
                'image_url' => 'https://example.com/images/headset.jpg',
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            \App\Models\Product::create($product);
        }
    }
}
