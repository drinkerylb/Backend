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
        Schema::create('return_orders', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('order_id')->constrained()->onDelete('restrict');
            $table->foreignId('customer_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('warehouse_id')->constrained()->onDelete('restrict');
            $table->string('status'); // pending, approved, received, inspected, completed, rejected
            $table->string('return_reason');
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('return_method'); // pickup, ship
            $table->json('shipping_details')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->string('refund_method')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->json('refund_details')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->json('documents')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('return_reason');
        });

        Schema::create('return_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained()->onDelete('restrict');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('restrict');
            $table->integer('quantity');
            $table->string('condition'); // new, opened, damaged
            $table->string('reason');
            $table->decimal('refund_amount', 10, 2);
            $table->string('status'); // pending, received, inspected, accepted, rejected
            $table->json('inspection_results')->nullable();
            $table->string('resolution'); // refund, exchange, repair
            $table->text('notes')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_order_items');
        Schema::dropIfExists('return_orders');
    }
};
