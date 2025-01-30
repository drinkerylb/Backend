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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained()->onDelete('cascade');
            $table->foreignId('translation_group_id')->constrained()->onDelete('cascade');
            $table->string('key', 191);
            $table->text('value');
            $table->text('default_value')->nullable(); // Original text in default language
            $table->string('model_type', 191)->nullable(); // For model translations (e.g., Product, Category)
            $table->unsignedBigInteger('model_id')->nullable(); // For model translations
            $table->string('field', 191)->nullable(); // For model translations (e.g., name, description)
            $table->boolean('is_auto_translated')->default(false);
            $table->timestamp('last_translated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Split the unique constraint into smaller indexes to avoid key length limitation
            $table->unique(['language_id', 'translation_group_id', 'key'], 'unique_translation_key');
            $table->index(['model_type', 'model_id', 'field'], 'translation_model_index');
        });

        // Create table for translatable fields configuration
        Schema::create('translatable_fields', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->string('field');
            $table->string('field_type')->default('text'); // text, textarea, html, etc.
            $table->boolean('is_required')->default(false);
            $table->boolean('is_html')->default(false);
            $table->json('validation_rules')->nullable();
            $table->timestamps();

            $table->unique(['model_type', 'field']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translatable_fields');
        Schema::dropIfExists('translations');
    }
};
