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
        Schema::table('vendor_raw_material_purchases', function (Blueprint $table) {
            $table->timestamp('vendor_bill_recorded_at')->nullable()->after('notes');
            $table->string('vendor_bill_number', 120)->nullable()->after('vendor_bill_recorded_at');
            $table->decimal('vendor_bill_amount', 12, 2)->default(0)->after('vendor_bill_number');
            $table->timestamp('inventory_updated_at')->nullable()->after('vendor_bill_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_raw_material_purchases', function (Blueprint $table) {
            $table->dropColumn([
                'vendor_bill_recorded_at',
                'vendor_bill_number',
                'vendor_bill_amount',
                'inventory_updated_at',
            ]);
        });
    }
};
