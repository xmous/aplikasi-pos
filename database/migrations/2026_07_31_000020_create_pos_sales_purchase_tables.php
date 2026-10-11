<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('type', 20);
            $table->decimal('value', 18, 2);
            $table->decimal('minimum_transaction', 18, 2)->default(0);
            $table->decimal('maximum_discount', 18, 2)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('promotion_product', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['promotion_id', 'product_id']);
        });

        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->decimal('opening_cash', 18, 2)->default(0);
            $table->decimal('expected_cash', 18, 2)->default(0);
            $table->decimal('actual_cash', 18, 2)->nullable();
            $table->decimal('cash_difference', 18, 2)->nullable();
            $table->string('status', 20)->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('number', 60)->unique();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->date('purchase_date');
            $table->date('due_date')->nullable();
            $table->string('supplier_invoice')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('additional_cost', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('status', 20)->default('received');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['purchase_date', 'payment_status']);
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 3);
            $table->decimal('unit_cost', 18, 2);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2);
            $table->string('batch_number', 100)->nullable();
            $table->date('expired_at')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('method', 30);
            $table->decimal('amount', 18, 2);
            $table->dateTime('paid_at');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('number', 60)->unique();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('cashier_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('sold_at');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('promotion_discount', 18, 2)->default(0);
            $table->decimal('manual_discount', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->decimal('change_amount', 18, 2)->default(0);
            $table->string('payment_status', 20)->default('paid');
            $table->string('status', 30)->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['sold_at', 'payment_status']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->string('sku', 60);
            $table->decimal('quantity', 18, 3);
            $table->decimal('unit_price', 18, 2);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2);
            $table->timestamps();
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('method', 30);
            $table->decimal('amount', 18, 2);
            $table->dateTime('paid_at');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 18, 2);
            $table->string('category', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->date('expense_date');
            $table->string('category', 100);
            $table->decimal('amount', 18, 2);
            $table->string('payment_method', 30)->default('cash');
            $table->text('notes');
            $table->timestamps();
            $table->index('expense_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('cashier_shifts');
        Schema::dropIfExists('promotion_product');
        Schema::dropIfExists('promotions');
    }
};
