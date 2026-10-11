<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'points_earned' => ! Schema::hasColumn(
                'sales',
                'points_earned'
            ),
            'points_redeemed' => ! Schema::hasColumn(
                'sales',
                'points_redeemed'
            ),
            'points_discount' => ! Schema::hasColumn(
                'sales',
                'points_discount'
            ),
            'points_earn_amount' => ! Schema::hasColumn(
                'sales',
                'points_earn_amount'
            ),
            'points_redeem_value' => ! Schema::hasColumn(
                'sales',
                'points_redeem_value'
            ),
            'points_awarded_at' => ! Schema::hasColumn(
                'sales',
                'points_awarded_at'
            ),
            'points_reversed' => ! Schema::hasColumn(
                'sales',
                'points_reversed'
            ),
            'points_redeemed_restored' => ! Schema::hasColumn(
                'sales',
                'points_redeemed_restored'
            ),
        ];

        if (in_array(true, $columns, true)) {
            Schema::table('sales', function (Blueprint $table) use (
                $columns
            ): void {
                if ($columns['points_earned']) {
                    $table->unsignedInteger('points_earned')
                        ->default(0);
                }

                if ($columns['points_redeemed']) {
                    $table->unsignedInteger('points_redeemed')
                        ->default(0);
                }

                if ($columns['points_discount']) {
                    $table->decimal(
                        'points_discount',
                        15,
                        2
                    )->default(0);
                }

                if ($columns['points_earn_amount']) {
                    $table->decimal(
                        'points_earn_amount',
                        15,
                        2
                    )->default(0);
                }

                if ($columns['points_redeem_value']) {
                    $table->decimal(
                        'points_redeem_value',
                        15,
                        2
                    )->default(0);
                }

                if ($columns['points_awarded_at']) {
                    $table->timestamp(
                        'points_awarded_at'
                    )->nullable();
                }

                if ($columns['points_reversed']) {
                    $table->unsignedInteger(
                        'points_reversed'
                    )->default(0);
                }

                if ($columns['points_redeemed_restored']) {
                    $table->unsignedInteger(
                        'points_redeemed_restored'
                    )->default(0);
                }
            });
        }

        if (! Schema::hasTable('customer_point_transactions')) {
            Schema::create(
                'customer_point_transactions',
                function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('customer_id')
                        ->constrained()
                        ->cascadeOnUpdate()
                        ->restrictOnDelete();
                    $table->foreignId('sale_id')
                        ->nullable()
                        ->constrained()
                        ->cascadeOnUpdate()
                        ->nullOnDelete();
                    $table->foreignId('sale_return_id')
                        ->nullable()
                        ->constrained('sale_returns')
                        ->cascadeOnUpdate()
                        ->nullOnDelete();
                    $table->string('type', 40);
                    $table->integer('points');
                    $table->unsignedInteger('balance_after');
                    $table->string('description', 255)
                        ->nullable();
                    $table->foreignId('created_by')
                        ->nullable()
                        ->constrained('users')
                        ->cascadeOnUpdate()
                        ->nullOnDelete();
                    $table->timestamps();

                    $table->index([
                        'customer_id',
                        'created_at',
                    ], 'customer_point_history_index');
                    $table->index([
                        'sale_id',
                        'type',
                    ], 'sale_point_type_index');
                }
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_point_transactions');

        $columns = array_values(array_filter([
            Schema::hasColumn('sales', 'points_earned')
                ? 'points_earned'
                : null,
            Schema::hasColumn('sales', 'points_redeemed')
                ? 'points_redeemed'
                : null,
            Schema::hasColumn('sales', 'points_discount')
                ? 'points_discount'
                : null,
            Schema::hasColumn('sales', 'points_earn_amount')
                ? 'points_earn_amount'
                : null,
            Schema::hasColumn('sales', 'points_redeem_value')
                ? 'points_redeem_value'
                : null,
            Schema::hasColumn('sales', 'points_awarded_at')
                ? 'points_awarded_at'
                : null,
            Schema::hasColumn('sales', 'points_reversed')
                ? 'points_reversed'
                : null,
            Schema::hasColumn(
                'sales',
                'points_redeemed_restored'
            )
                ? 'points_redeemed_restored'
                : null,
        ]));

        if ($columns !== []) {
            Schema::table(
                'sales',
                fn (Blueprint $table) => $table->dropColumn(
                    $columns
                )
            );
        }
    }
};
