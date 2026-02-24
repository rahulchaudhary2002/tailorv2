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
        Schema::create('garment_type_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garment_type_id')->constrained('garment_types')->cascadeOnDelete();
            $table->string('title');
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->unsignedInteger('order')->default(1);
            $table->timestamps();

            $table->index(['garment_type_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garment_type_measurements');
    }
};
