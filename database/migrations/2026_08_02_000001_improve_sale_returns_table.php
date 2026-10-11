<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sale_returns', 'refund_amount')) {
            Schema::table('sale_returns', function (Blueprint $table): void {
                $table->decimal('refund_amount', 15, 2)
                    ->default(0)
                    ->after('total');
            });

            DB::table('sale_returns')->update([
                'refund_amount' => DB::raw('total'),
            ]);
        }

        if (! Schema::hasColumn('sale_return_items', 'batch_number')) {
            Schema::table('sale_return_items', function (Blueprint $table): void {
                $table->string('batch_number', 100)
                    ->nullable()
                    ->after('condition');
            });
        }

        if (! Schema::hasColumn('sale_return_items', 'expired_at')) {
            Schema::table('sale_return_items', function (Blueprint $table): void {
                $table->date('expired_at')
                    ->nullable()
                    ->after('batch_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sale_return_items', 'expired_at')) {
            Schema::table('sale_return_items', function (Blueprint $table): void {
                $table->dropColumn('expired_at');
            });
        }

        if (Schema::hasColumn('sale_return_items', 'batch_number')) {
            Schema::table('sale_return_items', function (Blueprint $table): void {
                $table->dropColumn('batch_number');
            });
        }

        if (Schema::hasColumn('sale_returns', 'refund_amount')) {
            Schema::table('sale_returns', function (Blueprint $table): void {
                $table->dropColumn('refund_amount');
            });
        }
    }
};
