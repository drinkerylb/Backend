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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_collection_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('model');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type');
            $table->string('disk');
            $table->string('path');
            $table->unsignedBigInteger('size');
            $table->json('custom_properties')->nullable();
            $table->json('responsive_images')->nullable();
            $table->json('generated_conversions')->nullable();
            $table->string('original_url')->nullable();
            $table->integer('order_column')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_column');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
