<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Stock;
use App\Services\AuditService;
use App\Services\NumberService;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseReturnController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'per_page' => (int) $request->query('per_page', 20),
        ];

        if (! in_array($filters['per_page'], [15, 20, 50], true)) {
            $filters['per_page'] = 20;
        }

        $query = PurchaseReturn::query()
            ->with(['purchase.supplier', 'purchase.warehouse', 'creator'])
            ->withCount('items');

        if ($filters['q'] !== '') {
            $like = '%'.$filters['q'].'%';

            $query->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where('number', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhereHas('purchase', function (Builder $purchase) use ($like): void {
                        $purchase
                            ->where('number', 'like', $like)
                            ->orWhere('supplier_invoice', 'like', $like)
                            ->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('name', 'like', $like));
                    });
            });
        }

        $query
            ->when(
                $filters['date_from'] !== '',
                fn (Builder $builder) => $builder->whereDate('returned_at', '>=', $filters['date_from'])
            )
            ->when(
                $filters['date_to'] !== '',
                fn (Builder $builder) => $builder->whereDate('returned_at', '<=', $filters['date_to'])
            );

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $stats = [
            'total' => PurchaseReturn::query()->count(),
            'month_count' => PurchaseReturn::query()
                ->whereBetween('returned_at', [$monthStart, $monthEnd])
                ->count(),
            'month_total' => (float) PurchaseReturn::query()
                ->whereBetween('returned_at', [$monthStart, $monthEnd])
                ->sum('total'),
            'items' => (float) PurchaseReturnItem::query()->sum('quantity'),
        ];

        return view('returns.purchases.index', [
            'returns' => $query
                ->latest('returned_at')
                ->paginate($filters['per_page'])
                ->withQueryString(),
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function create(Purchase $purchase): View
    {
        $purchase->load([
            'items.product.unit',
            'items.unit',
            'supplier',
            'warehouse',
            'returns',
        ]);

        $returnedQuantities = PurchaseReturnItem::query()
            ->whereIn('purchase_item_id', $purchase->items->pluck('id'))
            ->selectRaw('purchase_item_id, SUM(quantity) as returned_quantity')
            ->groupBy('purchase_item_id')
            ->pluck('returned_quantity', 'purchase_item_id');

        $availableStocks = Stock::query()
            ->where('warehouse_id', $purchase->warehouse_id)
            ->whereIn('product_id', $purchase->items->pluck('product_id'))
            ->pluck('quantity', 'product_id');

        $hasRemainingItems = $purchase->items->contains(function ($item) use ($returnedQuantities): bool {
            return (float) $item->quantity
                > (float) ($returnedQuantities[$item->id] ?? 0);
        });

        return view('returns.purchases.create', compact(
            'purchase',
            'returnedQuantities',
            'availableStocks',
            'hasRemainingItems'
        ));
    }

    public function store(
        Request $request,
        Purchase $purchase,
        StockService $service
    ): RedirectResponse {
        $data = $request->validate(
            [
                'notes' => ['required', 'string', 'min:5', 'max:2000'],
                'items' => ['required', 'array'],
                'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            ],
            [
                'notes.required' => 'Alasan retur wajib diisi.',
                'notes.min' => 'Alasan retur minimal 5 karakter.',
            ]
        );

        $return = DB::transaction(function () use ($data, $purchase, $service): PurchaseReturn {
            $purchase = Purchase::query()
                ->whereKey($purchase->id)
                ->lockForUpdate()
                ->firstOrFail();

            $items = $purchase->items()
                ->with('product')
                ->lockForUpdate()
                ->get();

            $selected = collect();

            foreach ($items as $item) {
                $quantity = (float) ($data['items'][$item->id]['quantity'] ?? 0);

                if ($quantity <= 0) {
                    continue;
                }

                $alreadyReturned = (float) PurchaseReturnItem::query()
                    ->where('purchase_item_id', $item->id)
                    ->sum('quantity');

                $remaining = round((float) $item->quantity - $alreadyReturned, 3);

                if ($quantity > $remaining) {
                    throw ValidationException::withMessages([
                        'items' => "Retur {$item->product->name} melebihi sisa {$remaining}.",
                    ]);
                }

                $netUnitCost = (float) $item->quantity > 0
                    ? round((float) $item->subtotal / (float) $item->quantity, 2)
                    : 0;

                $selected->push([
                    'item' => $item,
                    'quantity' => $quantity,
                    'net_unit_cost' => $netUnitCost,
                    'base_quantity' => round($quantity * (float) $item->conversion_factor, 3),
                ]);
            }

            if ($selected->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Pilih minimal satu barang yang akan diretur.',
                ]);
            }

            $return = PurchaseReturn::create([
                'number' => NumberService::make('PR'),
                'purchase_id' => $purchase->id,
                'created_by' => auth()->id(),
                'returned_at' => now(),
                'total' => round((float) $selected->sum(
                    fn ($row) => $row['quantity'] * $row['net_unit_cost']
                ), 2),
                'notes' => $data['notes'],
            ]);

            foreach ($selected as $row) {
                $item = $row['item'];

                $return->items()->create([
                    'purchase_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $row['quantity'],
                    'unit_cost' => $row['net_unit_cost'],
                    'subtotal' => round($row['quantity'] * $row['net_unit_cost'], 2),
                ]);

                if ($item->product->track_stock) {
                    $service->decreaseFefo(
                        $purchase->warehouse_id,
                        $item->product,
                        $row['base_quantity'],
                        'purchase_return',
                        $return,
                        'Retur pembelian '.$return->number,
                        auth()->id(),
                        true
                    );
                }
            }

            $returnedTotal = (float) $purchase->returns()->sum('total');
            $netTotal = max(0, (float) $purchase->total - $returnedTotal);

            $paymentStatus = (float) $purchase->paid_amount >= $netTotal
                ? 'paid'
                : ((float) $purchase->paid_amount > 0 ? 'partial' : 'unpaid');

            $purchase->update([
                'payment_status' => $paymentStatus,
            ]);

            AuditService::log('purchase_return.created', $return, [
                'purchase_id' => $purchase->id,
                'total' => $return->total,
                'items' => $selected->count(),
            ]);

            return $return;
        }, 3);

        return redirect()
            ->route('purchase-returns.show', $return)
            ->with('success', 'Retur pembelian berhasil disimpan dan stok sudah dikurangi.');
    }

    public function show(PurchaseReturn $purchaseReturn): View
    {
        $purchaseReturn->load([
            'purchase.supplier',
            'purchase.warehouse',
            'creator',
            'items.product.unit',
            'items.purchaseItem.unit',
        ]);

        return view('returns.purchases.show', [
            'return' => $purchaseReturn,
        ]);
    }
}
