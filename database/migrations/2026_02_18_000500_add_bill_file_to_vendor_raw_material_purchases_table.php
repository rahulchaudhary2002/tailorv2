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
            $table->string('bill_file_path')->nullable()->after('vendor_bill_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_raw_material_purchases', function (Blueprint $table) {
            $table->dropColumn('bill_file_path');
        });
    }
};
