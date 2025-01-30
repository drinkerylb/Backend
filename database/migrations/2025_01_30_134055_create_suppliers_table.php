<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('company_name');
            $table->string('contact_person');
            $table->string('email');
            $table->string('phone');
            $table->string('website')->nullable();
            $table->string('tax_number')->nullable();
            $table->text('billing_address');
            $table->text('shipping_address')->nullable();
            $table->string('payment_terms')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->decimal('minimum_order_amount', 10, 2)->nullable();
            $table->string('currency')->default('USD');
            $table->boolean('is_active')->default(true);
            $table->integer('rating')->nullable();
            $table->json('categories')->nullable();
            $table->json('custom_fields')->nullable();
            $table->text('notes')->nullable();
            $table->json('documents')->nullable(); // Store document references
            $table->json('bank_details')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('rating');
        });

        // Create supplier_product pivot table
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('supplier_sku')->nullable();
            $table->decimal('unit_cost', 10, 2);
            $table->integer('minimum_order_quantity')->default(1);
            $table->integer('lead_time_days')->nullable();
            $table->boolean('is_preferred_supplier')->default(false);
            $table->json('pricing_tiers')->nullable();
            $table->timestamps();

            $table->unique(['supplier_id', 'product_id']);
            $table->index('supplier_sku');
            $table->index('is_preferred_supplier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
        Schema::dropIfExists('suppliers');
    }
};
