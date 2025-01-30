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
        Schema::create('analytics_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->text('description')->nullable();
            $table->string('entity_type'); // users, orders, products, etc.
            $table->json('conditions'); // Segment definition rules
            $table->json('filters')->nullable();
            $table->boolean('is_dynamic')->default(true); // Dynamic or static segment
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('refresh_interval')->nullable(); // For dynamic segments
            $table->timestamp('last_calculated_at')->nullable();
            $table->integer('member_count')->default(0);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index('entity_type');
            $table->index('is_active');
        });

        Schema::create('analytics_segment_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->constrained('analytics_segments')->onDelete('cascade');
            $table->morphs('member');
            $table->json('properties')->nullable(); // Additional properties at time of segmentation
            $table->timestamp('added_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Using a shorter name for the unique index
            $table->unique(['segment_id', 'member_type', 'member_id'], 'segment_member_unique');
            $table->index('added_at');
            $table->index('expires_at');
        });

        Schema::create('analytics_segment_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->constrained('analytics_segments')->onDelete('cascade');
            $table->timestamp('snapshot_date');
            $table->integer('member_count');
            $table->json('metrics')->nullable(); // Segment-specific metrics
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Using a shorter name for the unique index
            $table->unique(['segment_id', 'snapshot_date'], 'segment_snapshot_unique');
            $table->index('snapshot_date');
        });

        Schema::create('analytics_segment_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->constrained('analytics_segments')->onDelete('cascade');
            $table->string('rule_type'); // attribute, behavior, compound
            $table->string('operator');
            $table->json('parameters');
            $table->integer('sequence')->default(0);
            $table->string('conjunction')->default('and'); // and, or
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('rule_type');
            $table->index('sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_segment_rules');
        Schema::dropIfExists('analytics_segment_snapshots');
        Schema::dropIfExists('analytics_segment_members');
        Schema::dropIfExists('analytics_segments');
    }
}; 