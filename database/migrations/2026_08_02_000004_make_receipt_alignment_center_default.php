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
                    ->default('center')
                    ->after('is_active');
            });

            return;
        }

        DB::table('users')
            ->whereNull('receipt_alignment')
            ->orWhere('receipt_alignment', 'left')
            ->orWhereNotIn('receipt_alignment', ['left', 'center', 'right'])
            ->update(['receipt_alignment' => 'center']);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('receipt_alignment', 10)
                ->default('center')
                ->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'receipt_alignment')) {
            return;
        }

        DB::table('users')
            ->where('receipt_alignment', 'center')
            ->update(['receipt_alignment' => 'left']);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('receipt_alignment', 10)
                ->default('left')
                ->change();
        });
    }
};
