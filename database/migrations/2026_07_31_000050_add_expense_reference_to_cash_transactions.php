<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->foreignId('expense_id')->nullable()->after('cashier_shift_id')->constrained()->nullOnDelete();
            $table->index(['expense_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropIndex(['expense_id', 'type']);
            $table->dropConstrainedForeignId('expense_id');
        });
    }
};
