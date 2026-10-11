<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'receipt_alignment')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('receipt_alignment', 10)
                    ->default('left')
                    ->after('is_active');
            });
        }

        DB::table('users')
            ->whereNull('receipt_alignment')
            ->orWhereNotIn('receipt_alignment', ['left', 'right'])
            ->update(['receipt_alignment' => 'left']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'receipt_alignment')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('receipt_alignment');
            });
        }
    }
};
