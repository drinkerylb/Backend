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
        Schema::create('batch_lots', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('restrict');
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('restrict');
            $table->foreignId('purchase_order_id')->nullable()->constrained()->onDelete('restrict');
            $table->string('status'); // in_production, quarantine, available, depleted, expired, recalled
            $table->date('manufacturing_date');
            $table->date('expiry_date')->nullable();
            $table->integer('initial_quantity');
            $table->integer('current_quantity');
            $table->json('storage_conditions')->nullable();
            $table->string('quality_grade')->nullable();
            $table->json('quality_certificates')->nullable();
            $table->json('test_results')->nullable();
            $table->boolean('requires_quality_check')->default(false);
            $table->json('tracking_data')->nullable();
            $table->json('custom_fields')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('manufacturing_date');
            $table->index('expiry_date');
        });

        Schema::create('batch_lot_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_lot_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('restrict');
            $table->string('location_code'); // Specific location within warehouse
            $table->integer('quantity');
            $table->json('storage_conditions')->nullable();
            $table->timestamps();

            // Using a shorter name for the unique index
            $table->unique(['batch_lot_id', 'warehouse_id', 'location_code'], 'batch_lot_location_unique');
        });

        Schema::create('batch_lot_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_lot_id')->constrained()->onDelete('cascade');
            $table->string('reference_type'); // Order, Transfer, Adjustment
            $table->unsignedBigInteger('reference_id');
            $table->string('movement_type'); // in, out, transfer
            $table->integer('quantity');
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->onDelete('restrict');
            $table->string('from_location_code')->nullable();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->onDelete('restrict');
            $table->string('to_location_code')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('performed_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index('movement_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_lot_movements');
        Schema::dropIfExists('batch_lot_locations');
        Schema::dropIfExists('batch_lots');
    }
}; 