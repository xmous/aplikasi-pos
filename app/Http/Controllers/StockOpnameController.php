<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockOpname;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\NumberService;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'warehouse_id' => (string) $request->query('warehouse_id', ''),
            'status' => (string) $request->query('status', 'all'),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'per_page' => (int) $request->query('per_page', 20),
        ];

        if (! in_array($filters['status'], ['all', 'draft', 'submitted', 'approved'], true)) {
            $filters['status'] = 'all';
        }

        if (! in_array($filters['per_page'], [15, 20, 50], true)) {
            $filters['per_page'] = 20;
        }

        $query = StockOpname::query()
            ->with(['warehouse', 'creator', 'approver'])
            ->withCount('items')
            ->withCount([
                'items as difference_items_count' => fn (Builder $items) => $items->where('difference', '!=', 0),
            ])
            ->withSum('items as difference_sum', 'difference');

        if ($filters['q'] !== '') {
            $like = '%'.$filters['q'].'%';

            $query->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where('number', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhereHas('warehouse', function (Builder $warehouse) use ($like): void {
                        $warehouse
                            ->where('name', 'like', $like)
                            ->orWhere('code', 'like', $like);
                    })
                    ->orWhereHas('creator', fn (Builder $user) => $user->where('name', 'like', $like));
            });
        }

        $query
            ->when(
                $filters['warehouse_id'] !== '',
                fn (Builder $builder) => $builder->where('warehouse_id', (int) $filters['warehouse_id'])
            )
            ->when(
                $filters['status'] !== 'all',
                fn (Builder $builder) => $builder->where('status', $filters['status'])
            )
            ->when(
                $filters['date_from'] !== '',
                fn (Builder $builder) => $builder->whereDate('opname_date', '>=', $filters['date_from'])
            )
            ->when(
                $filters['date_to'] !== '',
                fn (Builder $builder) => $builder->whereDate('opname_date', '<=', $filters['date_to'])
            );

        $stats = [
            'total' => StockOpname::query()->count(),
            'draft' => StockOpname::query()->where('status', 'draft')->count(),
            'submitted' => StockOpname::query()->where('status', 'submitted')->count(),
            'approved' => StockOpname::query()->where('status', 'approved')->count(),
        ];

        return view('opnames.index', [
            'opnames' => $query
                ->latest('opname_date')
                ->latest('id')
                ->paginate($filters['per_page'])
                ->withQueryString(),
            'warehouses' => Warehouse::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        return view('opnames.create', [
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'activeOpnames' => StockOpname::query()
                ->with('warehouse')
                ->whereIn('status', ['draft', 'submitted'])
                ->latest('opname_date')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'warehouse_id' => ['required', 'exists:warehouses,id'],
                'opname_date' => ['required', 'date', 'before_or_equal:today'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ],
            [
                'opname_date.before_or_equal' => 'Tanggal opname tidak boleh melebihi hari ini.',
            ]
        );

        $warehouse = Warehouse::query()
            ->whereKey($data['warehouse_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $active = StockOpname::query()
            ->where('warehouse_id', $warehouse->id)
            ->whereIn('status', ['draft', 'submitted'])
            ->first();

        if ($active) {
            throw ValidationException::withMessages([
                'warehouse_id' => "Gudang {$warehouse->name} masih mempunyai opname aktif {$active->number}. Selesaikan atau batalkan terlebih dahulu.",
            ]);
        }

        $opname = DB::transaction(function () use ($data, $warehouse): StockOpname {
            $opname = StockOpname::create([
                'number' => NumberService::make('SO'),
                'warehouse_id' => $warehouse->id,
                'created_by' => auth()->id(),
                'opname_date' => $data['opname_date'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            $products = Product::query()
                ->where('is_active', true)
                ->where('track_stock', true)
                ->orderBy('name')
                ->get();

            $stocks = Stock::query()
                ->where('warehouse_id', $warehouse->id)
                ->whereIn('product_id', $products->pluck('id'))
                ->lockForUpdate()
                ->pluck('quantity', 'product_id');

            foreach ($products as $product) {
                $opname->items()->create([
                    'product_id' => $product->id,
                    'system_quantity' => (float) ($stocks[$product->id] ?? 0),
                ]);
            }

            AuditService::log('opname.created', $opname, [
                'warehouse_id' => $warehouse->id,
                'items' => $products->count(),
            ]);

            return $opname;
        });

        return redirect()
            ->route('opnames.show', $opname)
            ->with('success', 'Daftar hitung opname berhasil dibuat.');
    }

    public function show(StockOpname $opname): View
    {
        $opname->load([
            'items.product.unit',
            'warehouse',
            'creator',
            'approver',
        ]);

        return view('opnames.show', compact('opname'));
    }

    public function submit(Request $request, StockOpname $opname): RedirectResponse
    {
        $data = $request->validate(
            [
                'items' => ['required', 'array'],
                'items.*.physical_quantity' => ['required', 'numeric', 'min:0'],
                'items.*.notes' => ['nullable', 'string', 'max:500'],
            ],
            [
                'items.*.physical_quantity.required' => 'Semua stok fisik wajib diisi.',
                'items.*.physical_quantity.min' => 'Stok fisik tidak boleh kurang dari nol.',
            ]
        );

        DB::transaction(function () use ($opname, $data): void {
            $opname = StockOpname::query()
                ->whereKey($opname->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($opname->status !== 'draft') {
                throw ValidationException::withMessages([
                    'items' => 'Hanya opname berstatus draft yang dapat diajukan.',
                ]);
            }

            $items = $opname->items()
                ->with('product')
                ->lockForUpdate()
                ->get();

            $this->ensureSnapshotIsCurrent($opname, $items);

            foreach ($items as $item) {
                $input = $data['items'][$item->id] ?? null;

                if (! $input) {
                    throw ValidationException::withMessages([
                        'items' => 'Semua barang harus dihitung.',
                    ]);
                }

                $physical = (float) $input['physical_quantity'];

                $item->update([
                    'physical_quantity' => $physical,
                    'difference' => round($physical - (float) $item->system_quantity, 3),
                    'notes' => $input['notes'] ?? null,
                ]);
            }

            $opname->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            AuditService::log('opname.submitted', $opname);
        });

        return back()->with('success', 'Stok opname berhasil diajukan untuk persetujuan.');
    }

    public function approve(StockOpname $opname, StockService $service): RedirectResponse
    {
        abort_unless(auth()->user()->can('opname.approve'), 403);

        DB::transaction(function () use ($opname, $service): void {
            $opname = StockOpname::query()
                ->whereKey($opname->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($opname->status !== 'submitted') {
                throw ValidationException::withMessages([
                    'opname' => 'Hanya opname yang sudah diajukan yang dapat disetujui.',
                ]);
            }

            $items = $opname->items()
                ->with('product')
                ->lockForUpdate()
                ->get();

            $this->ensureSnapshotIsCurrent($opname, $items);

            foreach ($items as $item) {
                $difference = (float) $item->difference;

                if ($difference > 0) {
                    $service->increase(
                        $opname->warehouse_id,
                        $item->product,
                        $difference,
                        'stock_opname',
                        $opname,
                        "Selisih opname {$opname->number}",
                        auth()->id(),
                        'OPNAME-'.$opname->number
                    );
                } elseif ($difference < 0) {
                    $service->decreaseFefo(
                        $opname->warehouse_id,
                        $item->product,
                        abs($difference),
                        'stock_opname',
                        $opname,
                        "Selisih opname {$opname->number}",
                        auth()->id(),
                        true
                    );
                }
            }

            $opname->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            AuditService::log('opname.approved', $opname, [
                'difference_items' => $items->where('difference', '!=', 0)->count(),
            ]);
        }, 3);

        return back()->with('success', 'Opname disetujui dan saldo stok berhasil diperbarui.');
    }

    public function destroy(StockOpname $opname): RedirectResponse
    {
        abort_unless(
            (int) $opname->created_by === (int) auth()->id()
            || auth()->user()->can('opname.approve'),
            403
        );

        if (! in_array($opname->status, ['draft', 'submitted'], true)) {
            throw ValidationException::withMessages([
                'opname' => 'Opname yang sudah disetujui tidak dapat dibatalkan.',
            ]);
        }

        DB::transaction(function () use ($opname): void {
            $locked = StockOpname::query()
                ->whereKey($opname->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($locked->status, ['draft', 'submitted'], true)) {
                throw ValidationException::withMessages([
                    'opname' => 'Status opname sudah berubah dan tidak dapat dibatalkan.',
                ]);
            }

            AuditService::log('opname.cancelled', $locked, [
                'status' => $locked->status,
            ]);

            $locked->items()->delete();
            $locked->delete();
        });

        return redirect()
            ->route('opnames.index')
            ->with('success', 'Opname dibatalkan karena belum pernah diterapkan ke stok.');
    }

    private function ensureSnapshotIsCurrent(StockOpname $opname, $items): void
    {
        $currentStocks = Stock::query()
            ->where('warehouse_id', $opname->warehouse_id)
            ->whereIn('product_id', $items->pluck('product_id'))
            ->lockForUpdate()
            ->pluck('quantity', 'product_id');

        $changed = $items->filter(function ($item) use ($currentStocks): bool {
            $current = round((float) ($currentStocks[$item->product_id] ?? 0), 3);
            $snapshot = round((float) $item->system_quantity, 3);

            return $current !== $snapshot;
        });

        if ($changed->isNotEmpty()) {
            $examples = $changed
                ->take(3)
                ->map(fn ($item) => $item->product->name)
                ->implode(', ');

            throw ValidationException::withMessages([
                'opname' => 'Stok sistem berubah setelah daftar opname dibuat'
                    .($examples ? " ({$examples})" : '')
                    .'. Batalkan opname ini, hentikan transaksi pada gudang tersebut, lalu buat daftar hitung baru.',
            ]);
        }
    }
}
