<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stores', 'receipt_policy_text')) {
            Schema::table('stores', function (Blueprint $table): void {
                $table->string('receipt_policy_text')
                    ->nullable()
                    ->after('receipt_footer');
            });
        }

        DB::table('stores')
            ->whereNull('receipt_policy_text')
            ->update([
                'receipt_policy_text' =>
                    'Barang yang sudah dibeli mengikuti ketentuan retur toko.',
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('stores', 'receipt_policy_text')) {
            Schema::table('stores', function (Blueprint $table): void {
                $table->dropColumn('receipt_policy_text');
            });
        }
    }
};
