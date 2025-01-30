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
        Schema::create('stock_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->date('forecast_date');
            $table->integer('forecasted_demand');
            $table->integer('forecasted_stock');
            $table->integer('recommended_po_quantity');
            $table->decimal('confidence_score', 5, 2);
            $table->json('historical_data')->nullable();
            $table->json('seasonal_factors')->nullable();
            $table->json('trend_factors')->nullable();
            $table->json('external_factors')->nullable();
            $table->string('forecast_method');
            $table->json('forecast_parameters')->nullable();
            $table->decimal('accuracy_metrics', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Using a shorter name for the unique index
            $table->unique(['product_id', 'variant_id', 'warehouse_id', 'forecast_date'], 'stock_forecast_unique');
            $table->index('forecast_date');
            $table->index('confidence_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_forecasts');
    }
}; 