<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->decimal('conversion_factor', 18, 6);
            $table->string('barcode', 100)->nullable()->unique();
            $table->decimal('purchase_price', 18, 2)->nullable();
            $table->decimal('selling_price', 18, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['product_id', 'unit_id']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('product_unit_id')->nullable()->after('product_id')->constrained('product_units')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->after('product_unit_id')->constrained()->nullOnDelete();
            $table->decimal('conversion_factor', 18, 6)->default(1)->after('quantity');
            $table->decimal('base_quantity', 18, 3)->default(0)->after('conversion_factor');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('product_unit_id')->nullable()->after('product_id')->constrained('product_units')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->after('product_unit_id')->constrained()->nullOnDelete();
            $table->decimal('conversion_factor', 18, 6)->default(1)->after('quantity');
            $table->decimal('base_quantity', 18, 3)->default(0)->after('conversion_factor');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_unit_id');
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn(['conversion_factor', 'base_quantity']);
        });
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_unit_id');
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn(['conversion_factor', 'base_quantity']);
        });
        Schema::dropIfExists('product_units');
    }
};
