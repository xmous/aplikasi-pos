<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPointTransaction;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Setting;
use Illuminate\Validation\ValidationException;

class LoyaltyPointService
{
    private ?array $resolvedSettings = null;

    public function settings(): array
    {
        if ($this->resolvedSettings !== null) {
            return $this->resolvedSettings;
        }

        $stored = Setting::query()
            ->whereIn('key', [
                'loyalty_enabled',
                'loyalty_earn_amount',
                'loyalty_redeem_value',
                'loyalty_max_redeem_percent',
            ])
            ->pluck('value', 'key');

        return $this->resolvedSettings = [
            'enabled' => ($stored['loyalty_enabled'] ?? '1') === '1',
            'earn_amount' => max(
                1,
                (int) ($stored['loyalty_earn_amount'] ?? 10000)
            ),
            'redeem_value' => max(
                1,
                (int) ($stored['loyalty_redeem_value'] ?? 100)
            ),
            'max_redeem_percent' => min(
                100,
                max(
                    1,
                    (int) ($stored['loyalty_max_redeem_percent'] ?? 20)
                )
            ),
        ];
    }

    public function calculateEarned(float $netTotal): int
    {
        if (! $this->settings()['enabled'] || $netTotal <= 0) {
            return 0;
        }

        return (int) floor(
            $netTotal / $this->settings()['earn_amount']
        );
    }

    public function resolveRedemption(
        ?Customer $customer,
        float $eligibleAmount,
        int $requestedPoints
    ): array {
        $settings = $this->settings();

        if (
            ! $settings['enabled']
            || ! $customer
            || $eligibleAmount <= 0
        ) {
            return [
                'points' => 0,
                'discount' => 0.0,
                'max_points' => 0,
            ];
        }

        $maximumDiscount = floor(
            $eligibleAmount
            * ($settings['max_redeem_percent'] / 100)
        );

        $maximumByValue = (int) floor(
            $maximumDiscount / $settings['redeem_value']
        );

        $maximumPoints = max(
            0,
            min((int) $customer->points, $maximumByValue)
        );

        if ($requestedPoints > $maximumPoints) {
            throw ValidationException::withMessages([
                'redeem_points' => 'Poin yang dapat digunakan maksimal '
                    .number_format($maximumPoints, 0, ',', '.')
                    .' poin untuk transaksi ini.',
            ]);
        }

        $points = max(0, min($requestedPoints, $maximumPoints));

        return [
            'points' => $points,
            'discount' => (float) (
                $points * $settings['redeem_value']
            ),
            'max_points' => $maximumPoints,
        ];
    }

    public function redeem(
        Customer $customer,
        Sale $sale,
        int $points,
        float $discount,
        ?int $createdBy
    ): void {
        if ($points <= 0) {
            return;
        }

        $customer = Customer::query()
            ->whereKey($customer->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ((int) $customer->points < $points) {
            throw ValidationException::withMessages([
                'redeem_points' => 'Saldo poin pelanggan tidak mencukupi.',
            ]);
        }

        $customer->decrement('points', $points);
        $customer->refresh();

        $this->record(
            customer: $customer,
            sale: $sale,
            saleReturn: null,
            type: 'redeemed',
            points: -$points,
            description: 'Penukaran poin pada '.$sale->number
                .' senilai Rp '.number_format($discount, 0, ',', '.'),
            createdBy: $createdBy,
        );
    }

    public function awardForPaidSale(
        Sale $sale,
        ?int $createdBy
    ): int {
        $sale = Sale::query()
            ->whereKey($sale->id)
            ->lockForUpdate()
            ->firstOrFail();

        if (
            ! $sale->customer_id
            || $sale->payment_status !== 'paid'
            || $sale->points_awarded_at
        ) {
            return 0;
        }

        $points = max(
            0,
            (int) $sale->points_earned
                - (int) $sale->points_reversed
        );

        $sale->update([
            'points_awarded_at' => now(),
        ]);

        if ($points <= 0) {
            return 0;
        }

        $customer = Customer::query()
            ->whereKey($sale->customer_id)
            ->lockForUpdate()
            ->first();

        if (! $customer) {
            return 0;
        }

        $customer->increment('points', $points);
        $customer->refresh();

        $this->record(
            customer: $customer,
            sale: $sale,
            saleReturn: null,
            type: 'earned',
            points: $points,
            description: 'Poin transaksi lunas '.$sale->number,
            createdBy: $createdBy,
        );

        return $points;
    }

    public function adjustAfterReturn(
        Sale $sale,
        SaleReturn $saleReturn,
        float $newNetTotal,
        ?int $createdBy
    ): array {
        $sale = Sale::query()
            ->whereKey($sale->id)
            ->lockForUpdate()
            ->firstOrFail();

        $result = [
            'reversed' => 0,
            'restored' => 0,
            'awarded' => 0,
        ];

        if (! $sale->customer_id) {
            return $result;
        }

        $customer = Customer::query()
            ->whereKey($sale->customer_id)
            ->lockForUpdate()
            ->first();

        if (! $customer) {
            return $result;
        }

        /*
         * Bila poin belum pernah diberikan dan retur membuat transaksi
         * menjadi lunas, hitung ulang potensi poin dari total bersih baru.
         */
        if (
            ! $sale->points_awarded_at
            && $sale->payment_status === 'paid'
        ) {
            $earnAmount = (float) $sale->points_earn_amount > 0
                ? (float) $sale->points_earn_amount
                : (float) $this->settings()['earn_amount'];

            $sale->update([
                'points_earned' => $earnAmount > 0
                    ? (int) floor($newNetTotal / $earnAmount)
                    : 0,
            ]);

            $result['awarded'] = $this->awardForPaidSale(
                $sale,
                $createdBy
            );

            $sale->refresh();
            $customer->refresh();
        }

        /*
         * Kurangi poin yang dulu diperoleh bila nilai bersih transaksi
         * turun akibat retur.
         */
        if ($sale->points_awarded_at) {
            $earnAmount = (float) $sale->points_earn_amount > 0
                ? (float) $sale->points_earn_amount
                : (float) $this->settings()['earn_amount'];

            $targetEarned = $earnAmount > 0
                ? (int) floor($newNetTotal / $earnAmount)
                : 0;

            $effectiveEarned = max(
                0,
                (int) $sale->points_earned
                    - (int) $sale->points_reversed
            );

            $expectedReversal = max(
                0,
                $effectiveEarned - $targetEarned
            );

            if ($expectedReversal > 0) {
                $actualReversal = min(
                    (int) $customer->points,
                    $expectedReversal
                );

                if ($actualReversal > 0) {
                    $customer->decrement(
                        'points',
                        $actualReversal
                    );
                    $customer->refresh();

                    $this->record(
                        customer: $customer,
                        sale: $sale,
                        saleReturn: $saleReturn,
                        type: 'return_reversal',
                        points: -$actualReversal,
                        description: 'Pengurangan poin karena retur '
                            .$saleReturn->number,
                        createdBy: $createdBy,
                    );
                }

                $sale->increment(
                    'points_reversed',
                    $expectedReversal
                );

                $result['reversed'] = $actualReversal;
            }
        }

        /*
         * Poin yang digunakan sebagai potongan dikembalikan secara
         * proporsional terhadap nilai retur kumulatif.
         */
        if ((int) $sale->points_redeemed > 0) {
            $cumulativeReturn = (float) $sale
                ->returns()
                ->sum('total');

            $ratio = (float) $sale->total > 0
                ? min(
                    1,
                    $cumulativeReturn / (float) $sale->total
                )
                : 1;

            $targetRestored = (int) floor(
                (int) $sale->points_redeemed * $ratio
            );

            $restorePoints = max(
                0,
                $targetRestored
                    - (int) $sale->points_redeemed_restored
            );

            if ($restorePoints > 0) {
                $customer->increment('points', $restorePoints);
                $customer->refresh();

                $sale->increment(
                    'points_redeemed_restored',
                    $restorePoints
                );

                $this->record(
                    customer: $customer,
                    sale: $sale,
                    saleReturn: $saleReturn,
                    type: 'return_restore',
                    points: $restorePoints,
                    description: 'Pengembalian poin karena retur '
                        .$saleReturn->number,
                    createdBy: $createdBy,
                );

                $result['restored'] = $restorePoints;
            }
        }

        return $result;
    }

    private function record(
        Customer $customer,
        ?Sale $sale,
        ?SaleReturn $saleReturn,
        string $type,
        int $points,
        string $description,
        ?int $createdBy
    ): void {
        CustomerPointTransaction::create([
            'customer_id' => $customer->id,
            'sale_id' => $sale?->id,
            'sale_return_id' => $saleReturn?->id,
            'type' => $type,
            'points' => $points,
            'balance_after' => max(0, (int) $customer->points),
            'description' => $description,
            'created_by' => $createdBy,
        ]);
    }
}
