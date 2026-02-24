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
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreignId('target_product_id')
                ->nullable()
                ->after('reference_id')
                ->constrained('products')
                ->nullOnDelete();

            $table->foreignId('target_variant_id')
                ->nullable()
                ->after('target_product_id')
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->index(['reference_type', 'target_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex(['reference_type', 'target_product_id']);
            $table->dropConstrainedForeignId('target_variant_id');
            $table->dropConstrainedForeignId('target_product_id');
        });
    }
};
