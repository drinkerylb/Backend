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
        Schema::create('media_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('conversion_name');
            $table->string('mime_type');
            $table->string('disk');
            $table->string('path');
            $table->unsignedBigInteger('size');
            $table->json('manipulation_data')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['media_id', 'conversion_name']);
            $table->index('conversion_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_conversions');
    }
}; 