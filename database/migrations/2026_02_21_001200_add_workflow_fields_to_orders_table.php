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
        Schema::table('orders', function (Blueprint $table) {
            $table->dateTime('delivery_due_at')
                ->nullable()
                ->after('ordered_at');
            $table->foreignId('worker_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('worker_assigned_at')
                ->nullable()
                ->after('worker_id');
            $table->dateTime('worker_deadline_at')
                ->nullable()
                ->after('worker_assigned_at');
            $table->dateTime('fabric_issued_at')
                ->nullable()
                ->after('status');
            $table->dateTime('completed_at')
                ->nullable()
                ->after('fabric_issued_at');
            $table->dateTime('delivered_at')
                ->nullable()
                ->after('completed_at');
            $table->dateTime('closed_at')
                ->nullable()
                ->after('delivered_at');

            $table->index(['status', 'worker_id']);
            $table->index('delivery_due_at');
            $table->index('worker_deadline_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'worker_id']);
            $table->dropIndex(['delivery_due_at']);
            $table->dropIndex(['worker_deadline_at']);
            $table->dropConstrainedForeignId('worker_id');
            $table->dropColumn([
                'delivery_due_at',
                'worker_assigned_at',
                'worker_deadline_at',
                'fabric_issued_at',
                'completed_at',
                'delivered_at',
                'closed_at',
            ]);
        });
    }
};
