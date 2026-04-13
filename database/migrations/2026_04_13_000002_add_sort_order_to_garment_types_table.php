<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('garment_types', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0)->after('title');
        });

        $preferredOrder = [
            'Coat',
            'Pant',
            'Shirt',
            'WaistCoat',
            'Daura Surwal',
            'Kurta Surwal',
            'Kamij Surwal',
            'Jwaricoat',
            'Long Coat',
        ];

        $rows = DB::table('garment_types')
            ->select(['id', 'title'])
            ->orderBy('id')
            ->get();

        $preferredLookup = collect($preferredOrder)
            ->mapWithKeys(fn (string $title, int $index) => [mb_strtolower($title) => $index + 1]);

        $fallbackStart = count($preferredOrder) + 1;
        $fallbackOffset = 0;

        foreach ($rows as $row) {
            $normalizedTitle = mb_strtolower(trim((string) $row->title));
            $sortOrder = $preferredLookup[$normalizedTitle] ?? ($fallbackStart + $fallbackOffset++);

            DB::table('garment_types')
                ->where('id', $row->id)
                ->update(['sort_order' => $sortOrder]);
        }
    }

    public function down(): void
    {
        Schema::table('garment_types', function (Blueprint $table): void {
            $table->dropColumn('sort_order');
        });
    }
};
