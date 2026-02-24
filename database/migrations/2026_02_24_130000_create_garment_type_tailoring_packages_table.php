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
        Schema::create('garment_type_tailoring_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garment_type_id')->constrained('garment_types')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('description', 255)->nullable();
            $table->unsignedInteger('order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['garment_type_id', 'is_active']);
            $table->index(['garment_type_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garment_type_tailoring_packages');
    }
};

