<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode', 32)->nullable()->after('code');
        });

        DB::table('products')
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    $base = '20' . str_pad((string) $product->id, 10, '0', STR_PAD_LEFT);

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'barcode' => $base . $this->checksumDigit($base),
                        ]);
                }
            });

        Schema::table('products', function (Blueprint $table) {
            $table->unique('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
            $table->dropColumn('barcode');
        });
    }

    private function checksumDigit(string $payload): int
    {
        $digits = str_split($payload);
        $sum = 0;
        $length = count($digits);

        foreach ($digits as $index => $digit) {
            $positionFromRight = $length - $index;
            $sum += ((int) $digit) * ($positionFromRight % 2 === 0 ? 3 : 1);
        }

        return (10 - ($sum % 10)) % 10;
    }
};
