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
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->renameColumn('avg_cost', 'unit_cost');
        });

        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->dropColumn(['base_price', 'special_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->decimal('base_price', 12, 2)->default(0)->after('unit_cost');
            $table->decimal('special_price', 12, 2)->nullable()->after('base_price');
        });

        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->renameColumn('unit_cost', 'avg_cost');
        });
    }
};
