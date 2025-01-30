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
        Schema::create('quality_check_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->json('checklist_items');
            $table->json('required_equipment')->nullable();
            $table->json('acceptance_criteria');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quality_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->nullable()->constrained('quality_check_templates')->nullOnDelete();
            $table->string('reference_type'); // PurchaseOrder, BatchLot, ReturnOrder
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('warehouse_id')->constrained()->onDelete('restrict');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('restrict');
            $table->string('batch_number')->nullable();
            $table->string('status'); // pending, in_progress, passed, failed, requires_review
            $table->json('checklist_results');
            $table->json('measurements')->nullable();
            $table->json('equipment_used')->nullable();
            $table->text('notes')->nullable();
            $table->json('images')->nullable();
            $table->json('documents')->nullable();
            $table->boolean('requires_action')->default(false);
            $table->text('action_notes')->nullable();
            $table->foreignId('performed_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reference_type', 'reference_id']);
            $table->index('status');
            $table->index('batch_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_checks');
        Schema::dropIfExists('quality_check_templates');
    }
};
