<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\CashTransaction;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\AuditService;
use App\Services\NumberService;
use App\Services\LoyaltyPointService;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleReturnController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'refund_method' => (string) $request->query('refund_method', 'all'),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'per_page' => (int) $request->query('per_page', 20),
        ];

        $allowedMethods = array_merge(
            array_keys(config('pos.payment_methods')),
            ['credit_adjustment']
        );

        if (
            $filters['refund_method'] !== 'all'
            && ! in_array($filters['refund_method'], $allowedMethods, true)
        ) {
            $filters['refund_method'] = 'all';
        }

        if (! in_array($filters['per_page'], [15, 20, 50], true)) {
            $filters['per_page'] = 20;
        }

        $query = SaleReturn::query()
            ->with([
                'sale.customer',
                'sale.warehouse',
                'creator',
            ])
            ->withCount('items')
            ->withSum([
                'items as restocked_quantity' => fn (Builder $items) => $items
                    ->where('condition', 'restock'),
            ], 'quantity');

        if ($filters['q'] !== '') {
            $terms = preg_split('/\s+/', $filters['q']) ?: [];

            foreach (array_filter($terms) as $term) {
                $like = '%'.$term.'%';

                $query->where(function (Builder $builder) use ($like): void {
                    $builder
                        ->where('number', 'like', $like)
                        ->orWhere('notes', 'like', $like)
                        ->orWhereHas('sale', function (Builder $sale) use ($like): void {
                            $sale
                                ->where('number', 'like', $like)
                                ->orWhereHas('customer', function (Builder $customer) use ($like): void {
                                    $customer
                                        ->where('name', 'like', $like)
                                        ->orWhere('code', 'like', $like)
                                        ->orWhere('phone', 'like', $like);
                                });
                        })
                        ->orWhereHas('creator', fn (Builder $user) => $user->where('name', 'like', $like))
                        ->orWhereHas('items.product', function (Builder $product) use ($like): void {
                            $product
                                ->where('name', 'like', $like)
                                ->orWhere('sku', 'like', $like);
                        });
                });
            }
        }

        $query
            ->when(
                $filters['refund_method'] !== 'all',
                fn (Builder $builder) => $builder->where(
                    'refund_method',
                    $filters['refund_method']
                )
            )
            ->when(
                $filters['date_from'] !== '',
                fn (Builder $builder) => $builder->whereDate(
                    'returned_at',
                    '>=',
                    $filters['date_from']
                )
            )
            ->when(
                $filters['date_to'] !== '',
                fn (Builder $builder) => $builder->whereDate(
                    'returned_at',
                    '<=',
                    $filters['date_to']
                )
            );

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $stats = [
            'total' => SaleReturn::query()->count(),
            'month_count' => SaleReturn::query()
                ->whereBetween('returned_at', [$monthStart, $monthEnd])
                ->count(),
            'month_value' => (float) SaleReturn::query()
                ->whereBetween('returned_at', [$monthStart, $monthEnd])
                ->sum('total'),
            'month_refund' => (float) SaleReturn::query()
                ->whereBetween('returned_at', [$monthStart, $monthEnd])
                ->sum('refund_amount'),
        ];

        return view('returns.sales.index', [
            'returns' => $query
                ->latest('returned_at')
                ->paginate($filters['per_page'])
                ->withQueryString(),
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function create(Sale $sale): View
    {
        $sale->load([
            'items.returnItems',
            'items.product.unit',
            'items.unit',
            'customer',
            'warehouse',
            'cashier',
            'returns',
        ]);

        $previousReturnTotal = (float) $sale->returns->sum('total');

        $hasRemainingItems = $sale->items->contains(function ($item): bool {
            return (float) $item->quantity
                > (float) $item->returnItems->sum('quantity');
        });

        return view('returns.sales.create', compact(
            'sale',
            'previousReturnTotal',
            'hasRemainingItems'
        ));
    }

    public function store(
        Request $request,
        Sale $sale,
        StockService $stockService,
        LoyaltyPointService $loyaltyPointService
    ): RedirectResponse {
        $paymentMethods = array_keys(config('pos.payment_methods'));

        $data = $request->validate(
            [
                'refund_method' => [
                    'nullable',
                    Rule::in($paymentMethods),
                ],
                'notes' => ['required', 'string', 'min:5', 'max:2000'],
                'items' => ['required', 'array'],
                'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
                'items.*.condition' => ['nullable', Rule::in(['restock', 'discard'])],
                'items.*.batch_number' => ['nullable', 'string', 'max:100'],
                'items.*.expired_at' => ['nullable', 'date'],
            ],
            [
                'notes.required' => 'Alasan retur wajib diisi.',
                'notes.min' => 'Alasan retur minimal 5 karakter.',
            ]
        );

        $transactionResult = DB::transaction(function () use (
            $data,
            $sale,
            $stockService,
            $paymentMethods,
            $loyaltyPointService
        ): array {
            $sale = Sale::query()
                ->whereKey($sale->id)
                ->lockForUpdate()
                ->firstOrFail();

            $items = $sale->items()
                ->with(['returnItems', 'product'])
                ->lockForUpdate()
                ->get();

            $selected = collect();

            foreach ($items as $item) {
                $input = $data['items'][$item->id] ?? [];
                $quantity = (float) ($input['quantity'] ?? 0);

                if ($quantity <= 0) {
                    continue;
                }

                $alreadyReturned = (float) $item->returnItems->sum('quantity');
                $remaining = round(
                    (float) $item->quantity - $alreadyReturned,
                    3
                );

                if ($quantity > $remaining) {
                    throw ValidationException::withMessages([
                        'items' => "Jumlah retur {$item->product_name} melebihi sisa {$remaining}.",
                    ]);
                }

                $condition = $input['condition'] ?? 'restock';
                $batchNumber = trim((string) ($input['batch_number'] ?? ''));
                $expiredAt = $input['expired_at'] ?? null;

                if (
                    $condition === 'restock'
                    && $item->product->has_expiry
                ) {
                    if ($batchNumber === '') {
                        throw ValidationException::withMessages([
                            'items' => "Nomor batch {$item->product_name} wajib diisi jika dikembalikan ke stok.",
                        ]);
                    }

                    if (! $expiredAt) {
                        throw ValidationException::withMessages([
                            'items' => "Tanggal kedaluwarsa {$item->product_name} wajib diisi jika dikembalikan ke stok.",
                        ]);
                    }

                    if (now()->startOfDay()->gt(
                        \Illuminate\Support\Carbon::parse($expiredAt)->startOfDay()
                    )) {
                        throw ValidationException::withMessages([
                            'items' => "{$item->product_name} sudah kedaluwarsa dan tidak boleh dikembalikan ke stok layak jual.",
                        ]);
                    }
                }

                $lineNetUnit = (float) $item->quantity > 0
                    ? (float) $item->subtotal / (float) $item->quantity
                    : 0;

                $transactionFactor = (float) $sale->subtotal > 0
                    ? min(
                        1,
                        (float) $sale->total / (float) $sale->subtotal
                    )
                    : 0;

                $returnUnitValue = round(
                    $lineNetUnit * $transactionFactor,
                    2
                );

                $selected->push([
                    'item' => $item,
                    'quantity' => $quantity,
                    'condition' => $condition,
                    'batch_number' => $batchNumber !== '' ? $batchNumber : null,
                    'expired_at' => $expiredAt,
                    'return_unit_value' => $returnUnitValue,
                ]);
            }

            if ($selected->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Pilih minimal satu barang retur.',
                ]);
            }

            $returnValue = round(
                (float) $selected->sum(
                    fn (array $row) => $row['quantity']
                        * $row['return_unit_value']
                ),
                2
            );

            $previousReturnValue = (float) $sale->returns()->sum('total');
            $oldNetTotal = max(
                0,
                (float) $sale->total - $previousReturnValue
            );
            $newNetTotal = max(
                0,
                $oldNetTotal - $returnValue
            );

            /*
             * Retur mengurangi piutang terlebih dahulu.
             * Uang hanya dikembalikan bila pembayaran pelanggan
             * melebihi total transaksi setelah retur.
             */
            $currentPaid = (float) $sale->paid_amount;
            $refundAmount = round(
                max(0, $currentPaid - $newNetTotal),
                2
            );

            $refundMethod = 'credit_adjustment';
            $shift = null;

            if ($refundAmount > 0) {
                $refundMethod = $data['refund_method'] ?? null;

                if (! in_array($refundMethod, $paymentMethods, true)) {
                    throw ValidationException::withMessages([
                        'refund_method' => 'Pilih metode pengembalian dana.',
                    ]);
                }

                if ($refundMethod === 'cash') {
                    $shift = CashierShift::query()
                        ->where('user_id', auth()->id())
                        ->where('status', 'open')
                        ->lockForUpdate()
                        ->latest('opened_at')
                        ->first();

                    if (! $shift) {
                        throw ValidationException::withMessages([
                            'refund_method' => 'Buka shift kasir sebelum melakukan refund tunai.',
                        ]);
                    }

                    if ((float) $shift->expected_cash < $refundAmount) {
                        throw ValidationException::withMessages([
                            'refund_method' => 'Kas shift tidak mencukupi untuk refund tunai sebesar Rp '
                                .number_format($refundAmount, 0, ',', '.').'.',
                        ]);
                    }
                }
            }

            $return = SaleReturn::create([
                'number' => NumberService::make('SR'),
                'sale_id' => $sale->id,
                'created_by' => auth()->id(),
                'returned_at' => now(),
                'total' => $returnValue,
                'refund_amount' => $refundAmount,
                'refund_method' => $refundMethod,
                'notes' => $data['notes'],
            ]);

            foreach ($selected as $row) {
                $item = $row['item'];
                $subtotal = round(
                    $row['quantity'] * $row['return_unit_value'],
                    2
                );

                $return->items()->create([
                    'sale_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['return_unit_value'],
                    'subtotal' => $subtotal,
                    'condition' => $row['condition'],
                    'batch_number' => $row['batch_number'],
                    'expired_at' => $row['expired_at'],
                ]);

                if (
                    $row['condition'] === 'restock'
                    && $item->product->track_stock
                ) {
                    $batchNumber = $item->product->has_expiry
                        ? $row['batch_number']
                        : 'RETURN-'.$return->number;

                    $stockService->increase(
                        $sale->warehouse_id,
                        $item->product,
                        $row['quantity'] * (float) $item->conversion_factor,
                        'sale_return',
                        $return,
                        'Retur penjualan '.$return->number,
                        auth()->id(),
                        $batchNumber,
                        $row['expired_at']
                    );
                }
            }

            $allReturned = $items->every(function ($item): bool {
                return (float) $item->returnItems()->sum('quantity')
                    >= (float) $item->quantity;
            });

            $retainedPaid = round(
                max(0, $currentPaid - $refundAmount),
                2
            );

            $paymentStatus = $retainedPaid >= $newNetTotal
                ? 'paid'
                : ($retainedPaid > 0 ? 'partial' : 'unpaid');

            $sale->update([
                'paid_amount' => $retainedPaid,
                'status' => $allReturned
                    ? 'returned'
                    : 'partially_returned',
                'payment_status' => $paymentStatus,
            ]);

            $pointAdjustment = $loyaltyPointService
                ->adjustAfterReturn(
                    $sale,
                    $return,
                    $newNetTotal,
                    auth()->id()
                );

            if ($shift && $refundAmount > 0) {
                $shift->decrement('expected_cash', $refundAmount);

                CashTransaction::create([
                    'cashier_shift_id' => $shift->id,
                    'created_by' => auth()->id(),
                    'type' => 'out',
                    'amount' => $refundAmount,
                    'category' => 'sale_return',
                    'notes' => 'Refund '.$return->number,
                ]);
            }

            AuditService::log('sale_return.created', $return, [
                'sale_id' => $sale->id,
                'return_value' => $returnValue,
                'refund_amount' => $refundAmount,
                'new_net_total' => $newNetTotal,
                'items' => $selected->count(),
                'points_reversed' => $pointAdjustment['reversed'],
                'points_restored' => $pointAdjustment['restored'],
                'points_awarded' => $pointAdjustment['awarded'],
            ]);

            return [
                'return' => $return,
                'point_adjustment' => $pointAdjustment,
            ];
        }, 3);

        /** @var SaleReturn $return */
        $return = $transactionResult['return'];
        $pointAdjustment = $transactionResult[
            'point_adjustment'
        ];

        $message = 'Retur penjualan berhasil disimpan dan stok sudah diperbarui.';

        if ($pointAdjustment['reversed'] > 0) {
            $message .= ' '
                .number_format(
                    $pointAdjustment['reversed'],
                    0,
                    ',',
                    '.'
                )
                .' poin pelanggan dikurangi.';
        }

        if ($pointAdjustment['restored'] > 0) {
            $message .= ' '
                .number_format(
                    $pointAdjustment['restored'],
                    0,
                    ',',
                    '.'
                )
                .' poin yang digunakan dikembalikan.';
        }

        if ($pointAdjustment['awarded'] > 0) {
            $message .= ' '
                .number_format(
                    $pointAdjustment['awarded'],
                    0,
                    ',',
                    '.'
                )
                .' poin diberikan karena transaksi menjadi lunas.';
        }

        return redirect()
            ->route('sale-returns.show', $return)
            ->with('success', $message);
    }

    public function show(SaleReturn $saleReturn): View
    {
        $saleReturn->load([
            'sale.customer',
            'sale.warehouse',
            'sale.cashier',
            'creator',
            'items.product.unit',
            'items.saleItem.unit',
        ]);

        return view('returns.sales.show', [
            'return' => $saleReturn,
        ]);
    }
}
