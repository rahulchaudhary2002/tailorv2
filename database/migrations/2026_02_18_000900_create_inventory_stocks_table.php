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
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('inventory_locations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('on_hand_qty', 12, 2)->default(0);
            $table->decimal('reserved_qty', 12, 2)->default(0);
            $table->decimal('avg_cost', 12, 2)->default(0);
            $table->decimal('base_price', 12, 2)->default(0);
            $table->decimal('special_price', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'product_variant_id', 'location_id', 'vendor_id'], 'inventory_stocks_unique_key');
            $table->index(['product_id', 'location_id']);
            $table->index('vendor_id');
            $table->index('unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
