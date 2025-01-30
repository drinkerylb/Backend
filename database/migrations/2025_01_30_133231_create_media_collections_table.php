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
        Schema::create('media_collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->text('description')->nullable();
            $table->json('allowed_mime_types')->nullable();
            $table->json('allowed_file_extensions')->nullable();
            $table->unsignedInteger('max_file_size')->nullable(); // in bytes
            $table->unsignedInteger('max_files')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('conversion_settings')->nullable();
            $table->json('custom_properties')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('key');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_collections');
    }
};
