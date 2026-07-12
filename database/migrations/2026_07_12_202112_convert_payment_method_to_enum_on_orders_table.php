<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('orders')
            ->whereNull('payment_method')
            ->orWhereNotIn('payment_method', ['cash', 'qr', 'pos'])
            ->update(['payment_method' => 'cash']);

        DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('cash', 'qr', 'pos') NOT NULL DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE orders MODIFY payment_method VARCHAR(100) NULL DEFAULT NULL');
    }
};
