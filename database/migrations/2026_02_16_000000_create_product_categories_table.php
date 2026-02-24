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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('product_categories')->insert([
            [
                'name' => 'Ready Made',
                'slug' => 'ready-made',
                'description' => 'Pre-stitched garments available for direct purchase.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Accessories',
                'slug' => 'accessories',
                'description' => 'Fashion and garment-related accessories.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Fabrics',
                'slug' => 'fabrics',
                'description' => 'Cloth and material stock for sale or garment.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
