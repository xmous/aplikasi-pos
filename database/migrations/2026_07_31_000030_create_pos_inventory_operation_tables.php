<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number', 100);
            $table->date('expired_at')->nullable();
            $table->decimal('quantity', 18, 3)->default(0);
            $table->timestamps();
            $table->index(['warehouse_id', 'product_id', 'expired_at'], 'batch_fefo_lookup');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('direction', 10);
            $table->decimal('quantity', 18, 3);
            $table->decimal('stock_before', 18, 3);
            $table->decimal('stock_after', 18, 3);
            $table->nullableMorphs('reference');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'warehouse_id', 'created_at'], 'stock_movement_lookup');
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('number', 60)->unique();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('opname_date');
            $table->string('status', 20)->default('draft');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('system_quantity', 18, 3);
            $table->decimal('physical_quantity', 18, 3)->nullable();
            $table->decimal('difference', 18, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['stock_opname_id', 'product_id']);
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->string('number', 60)->unique();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('returned_at');
            $table->decimal('total', 18, 2)->default(0);
            $table->string('refund_method', 30)->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 3);
            $table->decimal('unit_price', 18, 2);
            $table->decimal('subtotal', 18, 2);
            $table->string('condition', 20)->default('restock');
            $table->timestamps();
        });

        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('number', 60)->unique();
            $table->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('returned_at');
            $table->decimal('total', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 3);
            $table->decimal('unit_cost', 18, 2);
            $table->decimal('subtotal', 18, 2);
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->nullableMorphs('subject');
            $table->json('properties')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_batches');
    }
};
