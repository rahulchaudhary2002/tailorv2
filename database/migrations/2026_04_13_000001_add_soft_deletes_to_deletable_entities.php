<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (
            [
                'outlets',
                'roles',
                'users',
                'units',
                'customers',
                'products',
                'vendors',
                'garment_types',
                'garment_type_measurements',
                'garment_type_tailoring_packages',
            ] as $tableName
        ) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach (
            [
                'garment_type_tailoring_packages',
                'garment_type_measurements',
                'garment_types',
                'vendors',
                'products',
                'customers',
                'units',
                'users',
                'roles',
                'outlets',
            ] as $tableName
        ) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
