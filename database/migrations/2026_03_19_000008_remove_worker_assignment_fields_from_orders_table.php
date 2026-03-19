<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'worker_id']);
            $table->dropIndex(['worker_deadline_at']);
            $table->dropConstrainedForeignId('worker_id');
            $table->dropColumn([
                'worker_assigned_at',
                'worker_deadline_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('worker_id')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('worker_assigned_at')
                ->nullable()
                ->after('worker_id');
            $table->dateTime('worker_deadline_at')
                ->nullable()
                ->after('worker_assigned_at');
            $table->index(['status', 'worker_id']);
            $table->index('worker_deadline_at');
        });
    }
};
