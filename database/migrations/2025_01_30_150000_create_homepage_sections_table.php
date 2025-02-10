<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('type'); // e.g., 'products', 'categories', 'custom'
            $table->boolean('is_active')->default(true);
            $table->integer('position')->default(0);
            $table->json('settings')->nullable(); // For section-specific settings
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('homepage_section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homepage_section_id')->constrained()->onDelete('cascade');
            $table->morphs('itemable'); // For polymorphic relationships
            $table->integer('position')->default(0);
            $table->json('custom_fields')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('homepage_section_items');
        Schema::dropIfExists('homepage_sections');
    }
}; 