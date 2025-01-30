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
        Schema::create('inventory_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // low_stock, overstock, expiring, damaged, etc.
            $table->string('severity'); // info, warning, critical
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');
            $table->integer('current_quantity');
            $table->integer('threshold_quantity');
            $table->text('message');
            $table->json('meta_data')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->boolean('requires_action')->default(true);
            $table->json('suggested_actions')->nullable();
            $table->timestamp('last_notification_sent_at')->nullable();
            $table->integer('notification_count')->default(0);
            $table->timestamps();

            $table->index('type');
            $table->index('severity');
            $table->index('is_resolved');
            $table->index('requires_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_alerts');
    }
};
