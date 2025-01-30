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
        Schema::create('translation_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique(); // Unique identifier for the group
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // System groups cannot be deleted
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_groups');
    }
};
