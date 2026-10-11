<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\NumberService;
use App\Services\StockService;
use App\Services\UnitConversionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $supplierId = (string) $request->query('supplier_id', '');
        $paymentStatus = (string) $request->query('payment_status', 'all');
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        $perPage = (int) $request->query('per_page', 25);

        if (! in_array($paymentStatus, ['all', 'paid', 'partial', 'unpaid'], true)) {
            $paymentStatus = 'all';
        }

        if (! in_array($perPage, [15, 25, 50], true)) {
            $perPage = 25;
        }

        $query = Purchase::query()
            ->with(['supplier', 'warehouse', 'creator'])
            ->withCount('items')
            ->withSum('returns', 'total');

        if ($search !== '') {
            $terms = preg_split('/\s+/', $search) ?: [];

            foreach (array_filter($terms) as $term) {
                $like = '%'.$term.'%';

                $query->where(function (Builder $builder) use ($like): void {
                    $builder
                        ->where('number', 'like', $like)
                        ->orWhere('supplier_invoice', 'like', $like)
                        ->orWhereHas('supplier', function (Builder $supplier) use ($like): void {
                            $supplier
                                ->where('name', 'like', $like)
                                ->orWhere('code', 'like', $like)
                                ->orWhere('phone', 'like', $like);
                        })
                        ->orWhereHas('warehouse', function (Builder $warehouse) use ($like): void {
                            $warehouse
                                ->where('name', 'like', $like)
                                ->orWhere('code', 'like', $like);
                        });
                });
            }
        }

        $query
            ->when(
                $supplierId !== '',
                fn (Builder $builder) => $builder->where('supplier_id', (int) $supplierId)
            )
            ->when(
                $paymentStatus !== 'all',
                fn (Builder $builder) => $builder->where('payment_status', $paymentStatus)
            )
            ->when(
                $dateFrom !== '',
                fn (Builder $builder) => $builder->whereDate('purchase_date', '>=', $dateFrom)
            )
            ->when(
                $dateTo !== '',
                fn (Builder $builder) => $builder->whereDate('purchase_date', '<=', $dateTo)
            );

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $stats = [
            'total' => Purchase::query()->count(),
            'month_count' => Purchase::query()
                ->whereBetween('purchase_date', [$monthStart, $monthEnd])
                ->count(),
            'month_total' => (float) Purchase::query()
                ->whereBetween('purchase_date', [$monthStart, $monthEnd])
                ->sum('total'),
            'open_debt' => Purchase::query()
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->count(),
        ];

        return view('purchases.index', [
            'purchases' => $query
                ->orderByDesc('purchase_date')
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'supplier_id' => $supplierId,
                'payment_status' => $paymentStatus,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): View
    {
        return view('purchases.create', [
            'products' => Product::query()
                ->with(['unit', 'conversionUnits.unit'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'suppliers' => Supplier::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(
        PurchaseRequest $request,
        StockService $stockService,
        UnitConversionService $conversionService
    ): RedirectResponse {
        $purchase = DB::transaction(function () use (
            $request,
            $stockService,
            $conversionService
        ): Purchase {
            $items = collect($request->validated('items'));

            $products = Product::query()
                ->with(['unit', 'conversionUnits.unit'])
                ->whereIn('id', $items->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $prepared = collect();

            foreach ($items as $index => $item) {
                $product = $products->get((int) $item['product_id']);

                if (! $product?->is_active) {
                    throw ValidationException::withMessages([
                        "items.$index.product_id" => 'Barang tidak aktif atau tidak ditemukan.',
                    ]);
                }

                if ($product->has_expiry) {
                    if (empty($item['batch_number'])) {
                        throw ValidationException::withMessages([
                            "items.$index.batch_number" => "Nomor batch {$product->name} wajib diisi.",
                        ]);
                    }

                    if (empty($item['expired_at'])) {
                        throw ValidationException::withMessages([
                            "items.$index.expired_at" => "Tanggal kedaluwarsa {$product->name} wajib diisi.",
                        ]);
                    }
                }

                $unit = $conversionService->resolve(
                    $product,
                    ! empty($item['product_unit_id'])
                        ? (int) $item['product_unit_id']
                        : null
                );

                $quantity = (float) $item['quantity'];
                $factor = (float) $unit['factor'];
                $baseQuantity = $conversionService->toBase($quantity, $factor);
                $unitCost = (float) $item['unit_cost'];
                $gross = round($quantity * $unitCost, 2);
                $itemDiscount = (float) ($item['discount'] ?? 0);

                if ($itemDiscount > $gross) {
                    throw ValidationException::withMessages([
                        "items.$index.discount" => "Diskon {$product->name} tidak boleh melebihi subtotal barang.",
                    ]);
                }

                $baseCost = $factor > 0
                    ? round($unitCost / $factor, 2)
                    : $unitCost;

                $prepared->push([
                    'product' => $product,
                    'product_unit' => $unit['product_unit'],
                    'unit_id' => $unit['unit_id'],
                    'quantity' => $quantity,
                    'factor' => $factor,
                    'base_quantity' => $baseQuantity,
                    'cost' => $unitCost,
                    'base_cost' => $baseCost,
                    'discount' => $itemDiscount,
                    'subtotal' => max(0, $gross - $itemDiscount),
                    'batch_number' => $item['batch_number'] ?? null,
                    'expired_at' => $item['expired_at'] ?? null,
                ]);
            }

            $subtotal = round((float) $prepared->sum('subtotal'), 2);
            $discount = (float) $request->input('discount', 0);
            $additionalCost = (float) $request->input('additional_cost', 0);

            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount' => 'Diskon transaksi tidak boleh melebihi subtotal pembelian.',
                ]);
            }

            $total = max(0, round($subtotal - $discount + $additionalCost, 2));
            $paidAmount = (float) $request->input('paid_amount', 0);

            if ($paidAmount > $total) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Bayar awal tidak boleh melebihi total pembelian.',
                ]);
            }

            $supplierId = $request->input('supplier_id');
            $dueDate = $request->date('due_date');

            if ($paidAmount < $total && ! $supplierId) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Supplier wajib dipilih jika pembelian belum dibayar lunas.',
                ]);
            }

            if ($paidAmount < $total && ! $dueDate) {
                throw ValidationException::withMessages([
                    'due_date' => 'Tanggal jatuh tempo wajib diisi jika masih ada utang supplier.',
                ]);
            }

            if ($paidAmount > 0 && ! $request->filled('payment_method')) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Metode pembayaran wajib dipilih jika ada pembayaran awal.',
                ]);
            }

            $warehouse = Warehouse::query()
                ->whereKey($request->integer('warehouse_id'))
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            if ($supplierId) {
                Supplier::query()
                    ->whereKey($supplierId)
                    ->where('is_active', true)
                    ->firstOrFail();
            }

            $supplierInvoice = trim((string) $request->input('supplier_invoice', ''));

            if ($supplierId && $supplierInvoice !== '') {
                $duplicateInvoice = Purchase::query()
                    ->where('supplier_id', $supplierId)
                    ->where('supplier_invoice', $supplierInvoice)
                    ->exists();

                if ($duplicateInvoice) {
                    throw ValidationException::withMessages([
                        'supplier_invoice' => 'Nomor invoice tersebut sudah pernah dicatat untuk supplier yang sama.',
                    ]);
                }
            }

            $purchase = Purchase::create([
                'number' => NumberService::make('PO'),
                'store_id' => $warehouse->store_id,
                'warehouse_id' => $warehouse->id,
                'supplier_id' => $supplierId,
                'created_by' => auth()->id(),
                'purchase_date' => $request->date('purchase_date'),
                'due_date' => $dueDate,
                'supplier_invoice' => $supplierInvoice !== '' ? $supplierInvoice : null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'additional_cost' => $additionalCost,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'payment_status' => $this->paymentStatus($paidAmount, $total),
                'status' => 'received',
                'notes' => $request->input('notes'),
            ]);

            foreach ($prepared as $item) {
                $product = $item['product'];

                $purchaseItem = $purchase->items()->create([
                    'product_id' => $product->id,
                    'product_unit_id' => $item['product_unit']?->id,
                    'unit_id' => $item['unit_id'],
                    'quantity' => $item['quantity'],
                    'conversion_factor' => $item['factor'],
                    'base_quantity' => $item['base_quantity'],
                    'unit_cost' => $item['cost'],
                    'discount' => $item['discount'],
                    'subtotal' => $item['subtotal'],
                    'batch_number' => $item['batch_number'],
                    'expired_at' => $item['expired_at'],
                ]);

                $currentStock = (float) $product->stocks()
                    ->where('warehouse_id', $warehouse->id)
                    ->value('quantity');

                $newAverageCost = ($currentStock + $item['base_quantity']) > 0
                    ? (
                        ((float) $product->average_cost * $currentStock)
                        + ($item['base_cost'] * $item['base_quantity'])
                    ) / ($currentStock + $item['base_quantity'])
                    : $item['base_cost'];

                $product->update([
                    'purchase_price' => round($item['base_cost'], 2),
                    'average_cost' => round($newAverageCost, 2),
                ]);

                if ($item['product_unit']) {
                    $item['product_unit']->update([
                        'purchase_price' => $item['cost'],
                    ]);
                }

                if ($product->track_stock) {
                    $stockService->increase(
                        $warehouse->id,
                        $product,
                        $item['base_quantity'],
                        'purchase',
                        $purchase,
                        'Barang masuk '.$purchase->number,
                        auth()->id(),
                        $purchaseItem->batch_number,
                        $purchaseItem->expired_at,
                        $purchaseItem->id
                    );
                }
            }

            if ($paidAmount > 0) {
                $purchase->payments()->create([
                    'created_by' => auth()->id(),
                    'method' => $request->input('payment_method'),
                    'amount' => $paidAmount,
                    'paid_at' => now(),
                    'notes' => 'Pembayaran awal',
                ]);
            }

            AuditService::log('purchase.created', $purchase, [
                'total' => $total,
                'items' => $prepared->count(),
                'warehouse_id' => $warehouse->id,
            ]);

            return $purchase;
        }, 3);

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Pembelian berhasil disimpan dan stok dasar sudah bertambah.');
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load([
            'items.product',
            'items.unit',
            'items.productUnit.unit',
            'supplier',
            'warehouse.store',
            'creator',
            'payments',
            'returns.items',
        ]);

        return view('purchases.show', compact('purchase'));
    }

    public function addPayment(Purchase $purchase, Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'amount' => ['required', 'numeric', 'gt:0'],
                'method' => ['required', 'in:cash,qris,transfer,debit,ewallet'],
                'reference_number' => ['nullable', 'max:255'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ],
            [
                'amount.required' => 'Nominal pembayaran wajib diisi.',
                'amount.gt' => 'Nominal pembayaran harus lebih besar dari nol.',
            ]
        );

        DB::transaction(function () use ($purchase, $data): void {
            $purchase = Purchase::query()
                ->whereKey($purchase->id)
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (float) $data['amount'];

            if ($amount > $purchase->balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pembayaran tidak boleh melebihi sisa utang.',
                ]);
            }

            if ($amount <= 0 || $purchase->balance <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Utang pembelian sudah lunas.',
                ]);
            }

            $purchase->payments()->create([
                'created_by' => auth()->id(),
                'method' => $data['method'],
                'amount' => $amount,
                'paid_at' => now(),
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $paid = (float) $purchase->paid_amount + $amount;

            $purchase->update([
                'paid_amount' => $paid,
                'payment_status' => $this->paymentStatus($paid, (float) $purchase->net_total),
            ]);

            AuditService::log('purchase.payment_added', $purchase, [
                'amount' => $amount,
                'method' => $data['method'],
            ]);
        });

        return back()->with('success', 'Pembayaran utang supplier berhasil dicatat.');
    }

    private function paymentStatus(float $paid, float $total): string
    {
        return $paid >= $total
            ? 'paid'
            : ($paid > 0 ? 'partial' : 'unpaid');
    }
}
