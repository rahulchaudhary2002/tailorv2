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
        Schema::create('customer_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_garment_type_id')->constrained('customer_garment_types')->cascadeOnDelete();
            $table->string('type');
            $table->string('measurement', 50);
            $table->string('unit', 20);
            $table->unsignedInteger('order')->default(1);
            $table->timestamps();

            $table->index(['customer_garment_type_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_measurements');
    }
};
