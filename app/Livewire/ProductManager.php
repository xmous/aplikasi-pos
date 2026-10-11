<?php

namespace App\Livewire;

use App\Imports\ProductRowsImport;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProductManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';
    public string $statusFilter = 'all';
    public string $stockFilter = 'all';
    public int $perPage = 15;

    public bool $showModal = false;
    public bool $showImportModal = false;
    public bool $baseUnitLocked = false;
    public bool $updateExistingProducts = true;
    public ?int $editingId = null;

    public mixed $importFile = null;

    public array $form = [];
    public array $conversions = [];
    public array $importSummary = [];
    public array $importErrors = [];

    public function mount(): void
    {
        Gate::authorize('products.view');
        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [15, 30, 50], true)) {
            $this->perPage = 15;
        }

        $this->resetPage();
    }

    public function updatedFormTrackStock(mixed $value): void
    {
        if (! filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            $this->form['has_expiry'] = false;
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = '';
        $this->statusFilter = 'all';
        $this->stockFilter = 'all';
        $this->resetPage();
    }

    public function openImportModal(): void
    {
        Gate::authorize('products.create');

        $this->resetImportState();
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->resetImportState();
    }

    public function importProducts(): void
    {
        Gate::authorize('products.create');

        $this->validate(
            [
                'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
                'updateExistingProducts' => ['boolean'],
            ],
            [
                'importFile.required' => 'Pilih file Excel terlebih dahulu.',
                'importFile.mimes' => 'File harus berformat XLSX, XLS, atau CSV.',
                'importFile.max' => 'Ukuran file maksimal 2 MB.',
            ],
            [
                'importFile' => 'file impor barang',
            ]
        );

        $this->importSummary = [
            'rows' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];
        $this->importErrors = [];

        $reader = new ProductRowsImport();

        try {
            Excel::import($reader, $this->importFile);
        } catch (Throwable $exception) {
            report($exception);

            $this->addError(
                'importFile',
                'File tidak dapat dibaca. Pastikan memakai template yang tersedia dan file tidak rusak.'
            );
            return;
        }

        $rows = $reader->rows ?? collect();

        if ($rows->isEmpty()) {
            $this->addError('importFile', 'File tidak mempunyai baris data untuk diimpor.');
            return;
        }

        if ($rows->count() > 1000) {
            $this->addError(
                'importFile',
                'Maksimal 1.000 baris dalam sekali impor. Pecah file menjadi beberapa bagian.'
            );
            return;
        }

        $categoryMap = $this->categoryImportMap();
        $unitMap = $this->unitImportMap();
        $seenSkus = [];

        foreach ($rows as $offset => $row) {
            $excelRow = $offset + 2;
            $values = collect($row)->all();

            if ($this->importRowIsEmpty($values)) {
                continue;
            }

            $this->importSummary['rows']++;

            try {
                $sku = mb_strtoupper($this->requiredImportText(
                    $values['sku'] ?? null,
                    'SKU'
                ));

                $skuKey = $this->normaliseImportLookup($sku);

                if (isset($seenSkus[$skuKey])) {
                    throw ValidationException::withMessages([
                        'import' => 'SKU '.$sku.' muncul lebih dari sekali dalam file.',
                    ]);
                }

                $seenSkus[$skuKey] = true;

                $result = $this->importProductRow(
                    $values,
                    $categoryMap,
                    $unitMap,
                    $sku
                );

                $this->importSummary[$result]++;
            } catch (Throwable $exception) {
                if (! $exception instanceof ValidationException) {
                    report($exception);
                }

                $this->importSummary['skipped']++;

                if (count($this->importErrors) < 100) {
                    $this->importErrors[] = [
                        'row' => $excelRow,
                        'message' => $this->importExceptionMessage($exception),
                    ];
                }
            }
        }

        $this->importFile = null;
        $this->resetPage();

        $successCount = $this->importSummary['created'] + $this->importSummary['updated'];

        if ($successCount > 0 && $this->importSummary['skipped'] === 0) {
            $this->dispatch(
                'toast',
                message: $successCount.' barang berhasil diproses dari Excel.'
            );
            return;
        }

        if ($successCount > 0) {
            $this->dispatch(
                'alert',
                type: 'warning',
                title: 'Impor selesai sebagian',
                message: $successCount.' barang berhasil dan '
                    .$this->importSummary['skipped'].' baris perlu diperiksa.'
            );
            return;
        }

        $this->dispatch(
            'alert',
            type: 'error',
            title: 'Tidak ada barang yang diimpor',
            message: 'Periksa daftar kesalahan pada jendela impor.'
        );
    }

    public function create(): void
    {
        Gate::authorize('products.create');

        $this->editingId = null;
        $this->baseUnitLocked = false;
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize('products.update');

        $product = Product::query()
            ->with(['conversionUnits' => fn ($query) => $query->orderBy('id')])
            ->findOrFail($id);

        $this->editingId = $product->id;
        $this->baseUnitLocked = $this->hasProductHistory($product->id);

        $this->form = [
            'product_category_id' => $product->product_category_id,
            'unit_id' => $product->unit_id,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'purchase_price' => (float) $product->purchase_price,
            'selling_price' => (float) $product->selling_price,
            'wholesale_price' => $product->wholesale_price !== null
                ? (float) $product->wholesale_price
                : null,
            'wholesale_min_qty' => $product->wholesale_min_qty !== null
                ? (float) $product->wholesale_min_qty
                : null,
            'minimum_stock' => (float) $product->minimum_stock,
            'rack_location' => $product->rack_location,
            'track_stock' => (bool) $product->track_stock,
            'has_expiry' => (bool) $product->has_expiry,
            'is_active' => (bool) $product->is_active,
        ];

        $this->conversions = $product->conversionUnits
            ->map(fn (ProductUnit $row) => [
                '_key' => 'existing-'.$row->id,
                'id' => $row->id,
                'unit_id' => $row->unit_id,
                'conversion_factor' => (float) $row->conversion_factor,
                'barcode' => $row->barcode,
                'purchase_price' => $row->purchase_price !== null
                    ? (float) $row->purchase_price
                    : null,
                'selling_price' => $row->selling_price !== null
                    ? (float) $row->selling_price
                    : null,
                'is_active' => (bool) $row->is_active,
            ])
            ->values()
            ->all();

        $this->resetValidation();
        $this->showModal = true;
    }

    public function addConversion(): void
    {
        Gate::authorize($this->editingId ? 'products.update' : 'products.create');

        if (empty($this->form['unit_id'])) {
            $this->dispatch(
                'alert',
                type: 'warning',
                title: 'Satuan dasar belum dipilih',
                message: 'Pilih satuan dasar stok sebelum menambahkan konversi.'
            );
            return;
        }

        $usedUnitIds = collect($this->conversions)
            ->pluck('unit_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->push((int) $this->form['unit_id'])
            ->unique();

        $availableUnit = Unit::query()
            ->whereNotIn('id', $usedUnitIds)
            ->orderBy('name')
            ->first();

        if (! $availableUnit) {
            $this->dispatch(
                'alert',
                type: 'info',
                title: 'Semua satuan sudah digunakan',
                message: 'Tidak ada satuan lain yang dapat ditambahkan sebagai konversi.'
            );
            return;
        }

        $this->conversions[] = [
            '_key' => (string) Str::uuid(),
            'id' => null,
            'unit_id' => $availableUnit->id,
            'conversion_factor' => 1,
            'barcode' => null,
            'purchase_price' => null,
            'selling_price' => null,
            'is_active' => true,
        ];

        $this->resetValidation('conversions');
    }

    public function removeConversion(int $index): void
    {
        Gate::authorize($this->editingId ? 'products.update' : 'products.create');

        if (! array_key_exists($index, $this->conversions)) {
            return;
        }

        unset($this->conversions[$index]);
        $this->conversions = array_values($this->conversions);
        $this->resetValidation('conversions');
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('products.update');

        $product = Product::query()->findOrFail($id);
        $product->is_active = ! $product->is_active;
        $product->save();

        AuditService::log('product.status_updated', $product, [
            'is_active' => $product->is_active,
        ]);

        $this->dispatch(
            'toast',
            message: $product->is_active
                ? 'Barang berhasil diaktifkan.'
                : 'Barang berhasil dinonaktifkan.'
        );
    }

    public function save(): void
    {
        Gate::authorize($this->editingId ? 'products.update' : 'products.create');

        $this->normaliseInputs();
        $data = $this->validate($this->rules(), $this->messages(), $this->validationAttributes());

        $this->validateBusinessRules($data);
        $wasEditing = $this->editingId !== null;

        DB::transaction(function () use ($data): void {
            $product = $this->editingId
                ? Product::query()->lockForUpdate()->findOrFail($this->editingId)
                : new Product();

            $form = $data['form'];
            foreach (['barcode', 'wholesale_price', 'wholesale_min_qty', 'rack_location'] as $field) {
                $form[$field] = $this->nullableValue($form[$field] ?? null);
            }

            $product->fill($form);

            if (! $product->exists) {
                $product->average_cost = $form['purchase_price'];
            }

            $product->save();

            $existingConversions = $product->conversionUnits()
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $keepIds = [];

            foreach ($data['conversions'] as $row) {
                $conversion = null;

                if (! empty($row['id'])) {
                    $conversion = $existingConversions->get((int) $row['id']);

                    if (! $conversion) {
                        throw ValidationException::withMessages([
                            'conversions' => 'Salah satu konversi tidak lagi tersedia. Muat ulang halaman dan coba kembali.',
                        ]);
                    }
                }

                if (! $conversion) {
                    $conversion = $product->conversionUnits()
                        ->where('unit_id', $row['unit_id'])
                        ->first() ?? new ProductUnit(['product_id' => $product->id]);
                }

                $conversion->fill([
                    'unit_id' => $row['unit_id'],
                    'conversion_factor' => $row['conversion_factor'],
                    'barcode' => $this->nullableValue($row['barcode'] ?? null),
                    'purchase_price' => $this->nullableValue($row['purchase_price'] ?? null),
                    'selling_price' => $this->nullableValue($row['selling_price'] ?? null),
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ]);

                $conversion->product_id = $product->id;
                $conversion->save();
                $keepIds[] = $conversion->id;
            }

            $removedConversions = $existingConversions
                ->reject(fn (ProductUnit $conversion) => in_array($conversion->id, $keepIds, true));

            foreach ($removedConversions as $conversion) {
                $hasHistory = SaleItem::query()
                    ->where('product_unit_id', $conversion->id)
                    ->exists()
                    || PurchaseItem::query()
                        ->where('product_unit_id', $conversion->id)
                        ->exists();

                if ($hasHistory) {
                    $conversion->update([
                        'is_active' => false,
                        'barcode' => null,
                    ]);
                } else {
                    $conversion->delete();
                }
            }

            AuditService::log(
                'product.'.($this->editingId ? 'updated' : 'created'),
                $product,
                ['conversions' => count($keepIds)]
            );
        }, 3);

        $this->showModal = false;
        $this->editingId = null;
        $this->baseUnitLocked = false;
        $this->resetForm();

        $this->dispatch(
            'toast',
            message: $wasEditing
                ? 'Barang berhasil diperbarui.'
                : 'Barang berhasil ditambahkan.'
        );
    }

    public function delete(int $id): void
    {
        Gate::authorize('products.delete');

        $product = Product::query()->findOrFail($id);
        $stock = (float) $product->stocks()->sum('quantity');

        if ($stock != 0.0 || $this->hasProductHistory($product->id)) {
            $this->dispatch(
                'alert',
                type: 'error',
                title: 'Barang tidak dapat dihapus',
                message: 'Barang sudah mempunyai stok atau histori transaksi. Nonaktifkan barang agar histori tetap tersimpan.'
            );
            return;
        }

        $product->conversionUnits()->delete();
        $product->delete();

        AuditService::log('product.deleted', $product);
        $this->dispatch('toast', message: 'Barang berhasil dihapus.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->baseUnitLocked = false;
        $this->resetForm();
    }

    public function render()
    {
        Gate::authorize('products.view');

        $stockTotalSql = '(SELECT COALESCE(SUM(stocks.quantity), 0)
            FROM stocks
            WHERE stocks.product_id = products.id)';

        $query = Product::query()
            ->with([
                'category',
                'unit',
                'conversionUnits' => fn ($conversion) => $conversion
                    ->with('unit')
                    ->orderByDesc('is_active')
                    ->orderBy('id'),
            ])
            ->withSum('stocks as total_stock', 'quantity');

        $this->applySearch($query);

        $query
            ->when(
                $this->categoryFilter !== '',
                fn (Builder $builder) => $builder->where(
                    'product_category_id',
                    (int) $this->categoryFilter
                )
            )
            ->when(
                $this->statusFilter === 'active',
                fn (Builder $builder) => $builder->where('is_active', true)
            )
            ->when(
                $this->statusFilter === 'inactive',
                fn (Builder $builder) => $builder->where('is_active', false)
            )
            ->when(
                $this->stockFilter === 'safe',
                fn (Builder $builder) => $builder
                    ->where('track_stock', true)
                    ->whereRaw($stockTotalSql.' > products.minimum_stock')
            )
            ->when(
                $this->stockFilter === 'low',
                fn (Builder $builder) => $builder
                    ->where('track_stock', true)
                    ->whereRaw($stockTotalSql.' > 0')
                    ->whereRaw($stockTotalSql.' <= products.minimum_stock')
            )
            ->when(
                $this->stockFilter === 'out',
                fn (Builder $builder) => $builder
                    ->where('track_stock', true)
                    ->whereRaw($stockTotalSql.' <= 0')
            )
            ->when(
                $this->stockFilter === 'untracked',
                fn (Builder $builder) => $builder->where('track_stock', false)
            );

        $perPage = in_array($this->perPage, [15, 30, 50], true)
            ? $this->perPage
            : 15;

        $stats = [
            'total' => Product::query()->count(),
            'active' => Product::query()->where('is_active', true)->count(),
            'low' => Product::query()
                ->where('track_stock', true)
                ->whereRaw($stockTotalSql.' > 0')
                ->whereRaw($stockTotalSql.' <= products.minimum_stock')
                ->count(),
            'out' => Product::query()
                ->where('track_stock', true)
                ->whereRaw($stockTotalSql.' <= 0')
                ->count(),
        ];

        return view('livewire.product-manager', [
            'products' => $query
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->paginate($perPage),
            'categories' => ProductCategory::query()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
            'units' => Unit::query()->orderBy('name')->get(),
            'stats' => $stats,
        ]);
    }

    private function applySearch(Builder $query): void
    {
        $terms = preg_split('/\s+/', trim($this->search)) ?: [];

        foreach (array_filter($terms) as $term) {
            $like = '%'.$term.'%';

            $query->where(function (Builder $builder) use ($like): void {
                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('barcode', 'like', $like)
                    ->orWhere('rack_location', 'like', $like)
                    ->orWhereHas('category', function (Builder $category) use ($like): void {
                        $category
                            ->where('name', 'like', $like)
                            ->orWhere('code', 'like', $like);
                    })
                    ->orWhereHas('unit', function (Builder $unit) use ($like): void {
                        $unit
                            ->where('name', 'like', $like)
                            ->orWhere('symbol', 'like', $like);
                    })
                    ->orWhereHas('conversionUnits', function (Builder $conversion) use ($like): void {
                        $conversion
                            ->where('barcode', 'like', $like)
                            ->orWhereHas('unit', function (Builder $unit) use ($like): void {
                                $unit
                                    ->where('name', 'like', $like)
                                    ->orWhere('symbol', 'like', $like);
                            });
                    });
            });
        }
    }

    private function resetForm(): void
    {
        $this->form = [
            'product_category_id' => '',
            'unit_id' => '',
            'sku' => '',
            'barcode' => null,
            'name' => '',
            'purchase_price' => 0,
            'selling_price' => 0,
            'wholesale_price' => null,
            'wholesale_min_qty' => null,
            'minimum_stock' => 0,
            'rack_location' => null,
            'track_stock' => true,
            'has_expiry' => false,
            'is_active' => true,
        ];

        $this->conversions = [];
        $this->resetValidation();
    }

    private function normaliseInputs(): void
    {
        foreach (['sku', 'barcode', 'name', 'rack_location'] as $field) {
            $value = $this->form[$field] ?? null;
            $this->form[$field] = is_string($value) ? trim($value) : $value;
        }

        foreach ($this->conversions as $index => $conversion) {
            $this->conversions[$index]['barcode'] = trim(
                (string) ($conversion['barcode'] ?? '')
            );

            foreach (['purchase_price', 'selling_price'] as $field) {
                if (($conversion[$field] ?? '') === '') {
                    $this->conversions[$index][$field] = null;
                }
            }
        }

        foreach (['wholesale_price', 'wholesale_min_qty'] as $field) {
            if (($this->form[$field] ?? '') === '') {
                $this->form[$field] = null;
            }
        }
    }

    private function rules(): array
    {
        return [
            'form.product_category_id' => ['required', 'exists:product_categories,id'],
            'form.unit_id' => ['required', 'exists:units,id'],
            'form.sku' => [
                'required',
                'string',
                'max:60',
                Rule::unique('products', 'sku')->ignore($this->editingId),
            ],
            'form.barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')->ignore($this->editingId),
            ],
            'form.name' => ['required', 'string', 'max:255'],
            'form.purchase_price' => ['required', 'numeric', 'min:0'],
            'form.selling_price' => ['required', 'numeric', 'min:0'],
            'form.wholesale_price' => [
                'nullable',
                'numeric',
                'min:0',
                'required_with:form.wholesale_min_qty',
            ],
            'form.wholesale_min_qty' => [
                'nullable',
                'numeric',
                'gt:0',
                'required_with:form.wholesale_price',
            ],
            'form.minimum_stock' => ['required', 'numeric', 'min:0'],
            'form.rack_location' => ['nullable', 'string', 'max:100'],
            'form.track_stock' => ['boolean'],
            'form.has_expiry' => ['boolean'],
            'form.is_active' => ['boolean'],

            'conversions' => ['array'],
            'conversions.*.id' => ['nullable', 'integer'],
            'conversions.*.unit_id' => ['required', 'distinct', 'exists:units,id'],
            'conversions.*.conversion_factor' => ['required', 'numeric', 'gt:0'],
            'conversions.*.barcode' => ['nullable', 'string', 'max:100', 'distinct'],
            'conversions.*.purchase_price' => ['nullable', 'numeric', 'min:0'],
            'conversions.*.selling_price' => ['nullable', 'numeric', 'min:0'],
            'conversions.*.is_active' => ['boolean'],
        ];
    }

    private function validateBusinessRules(array $data): void
    {
        if (
            (bool) $data['form']['has_expiry']
            && ! (bool) $data['form']['track_stock']
        ) {
            throw ValidationException::withMessages([
                'form.has_expiry' => 'Pelacakan kedaluwarsa hanya dapat digunakan pada barang yang stoknya dikelola.',
            ]);
        }

        if ($this->editingId) {
            $product = Product::query()->findOrFail($this->editingId);

            if (
                (int) $product->unit_id !== (int) $data['form']['unit_id']
                && $this->hasProductHistory($product->id)
            ) {
                throw ValidationException::withMessages([
                    'form.unit_id' => 'Satuan dasar tidak dapat diubah karena barang sudah mempunyai histori stok atau transaksi.',
                ]);
            }
        }

        foreach ($data['conversions'] as $index => $row) {
            if ((int) $row['unit_id'] === (int) $data['form']['unit_id']) {
                throw ValidationException::withMessages([
                    'conversions.'.$index.'.unit_id' => 'Satuan konversi tidak boleh sama dengan satuan dasar.',
                ]);
            }
        }

        $mainBarcode = $this->nullableValue($data['form']['barcode'] ?? null);
        $conversionBarcodes = collect($data['conversions'])
            ->pluck('barcode')
            ->map(fn ($barcode) => $this->nullableValue($barcode))
            ->filter()
            ->values();

        if ($mainBarcode && $conversionBarcodes->contains($mainBarcode)) {
            throw ValidationException::withMessages([
                'form.barcode' => 'Barcode dasar tidak boleh sama dengan barcode konversi.',
            ]);
        }

        if (
            $mainBarcode
            && ProductUnit::query()->where('barcode', $mainBarcode)->exists()
        ) {
            throw ValidationException::withMessages([
                'form.barcode' => 'Barcode dasar sudah digunakan oleh satuan konversi.',
            ]);
        }

        if ($conversionBarcodes->isEmpty()) {
            return;
        }

        $productBarcodeConflict = Product::query()
            ->whereIn('barcode', $conversionBarcodes)
            ->when(
                $this->editingId,
                fn (Builder $query) => $query->whereKeyNot($this->editingId)
            )
            ->exists();

        if ($productBarcodeConflict) {
            throw ValidationException::withMessages([
                'conversions' => 'Salah satu barcode konversi sudah digunakan sebagai barcode dasar barang lain.',
            ]);
        }

        $conversionBarcodeConflict = ProductUnit::query()
            ->whereIn('barcode', $conversionBarcodes)
            ->when(
                $this->editingId,
                fn (Builder $query) => $query->where('product_id', '!=', $this->editingId)
            )
            ->exists();

        if ($conversionBarcodeConflict) {
            throw ValidationException::withMessages([
                'conversions' => 'Salah satu barcode konversi sudah digunakan oleh barang lain.',
            ]);
        }
    }

    private function importProductRow(
        array $row,
        array $categoryMap,
        array $unitMap,
        string $sku
    ): string {
        $name = $this->requiredImportText($row['nama_barang'] ?? null, 'Nama barang');
        $categoryKey = $this->normaliseImportLookup(
            $this->requiredImportText($row['kategori'] ?? null, 'Kategori')
        );
        $unitKey = $this->normaliseImportLookup(
            $this->requiredImportText($row['satuan_dasar'] ?? null, 'Satuan dasar')
        );

        $category = $categoryMap[$categoryKey] ?? null;
        $baseUnit = $unitMap[$unitKey] ?? null;

        if (! $category) {
            throw ValidationException::withMessages([
                'import' => 'Kategori "'.($row['kategori'] ?? '').'" tidak ditemukan pada master kategori.',
            ]);
        }

        if (! $baseUnit) {
            throw ValidationException::withMessages([
                'import' => 'Satuan dasar "'.($row['satuan_dasar'] ?? '').'" tidak ditemukan pada master satuan.',
            ]);
        }

        if (mb_strlen($sku) > 60) {
            throw ValidationException::withMessages([
                'import' => 'SKU maksimal 60 karakter.',
            ]);
        }

        if (mb_strlen($name) > 255) {
            throw ValidationException::withMessages([
                'import' => 'Nama barang maksimal 255 karakter.',
            ]);
        }

        $barcode = $this->nullableImportText($row['barcode'] ?? null);
        $rackLocation = $this->nullableImportText($row['lokasi_rak'] ?? null);

        if ($barcode && mb_strlen($barcode) > 100) {
            throw ValidationException::withMessages([
                'import' => 'Barcode dasar maksimal 100 karakter.',
            ]);
        }

        if ($rackLocation && mb_strlen($rackLocation) > 100) {
            throw ValidationException::withMessages([
                'import' => 'Lokasi rak maksimal 100 karakter.',
            ]);
        }

        $purchasePrice = $this->importNumber(
            $row['harga_beli'] ?? null,
            'Harga beli'
        );
        $sellingPrice = $this->importNumber(
            $row['harga_jual'] ?? null,
            'Harga jual'
        );
        $wholesalePrice = $this->importNumber(
            $row['harga_grosir'] ?? null,
            'Harga grosir',
            true
        );
        $wholesaleMinimum = $this->importNumber(
            $row['minimal_grosir'] ?? null,
            'Minimal grosir',
            true
        );
        $minimumStock = $this->importNumber(
            $row['stok_minimum'] ?? 0,
            'Stok minimum'
        );

        if (($wholesalePrice === null) xor ($wholesaleMinimum === null)) {
            throw ValidationException::withMessages([
                'import' => 'Harga grosir dan minimal grosir harus diisi bersamaan.',
            ]);
        }

        if ($wholesaleMinimum !== null && $wholesaleMinimum <= 0) {
            throw ValidationException::withMessages([
                'import' => 'Minimal grosir harus lebih besar dari nol.',
            ]);
        }

        $trackStock = $this->importBoolean(
            $row['kelola_stok'] ?? null,
            true,
            'Kelola stok'
        );
        $hasExpiry = $this->importBoolean(
            $row['punya_kedaluwarsa'] ?? null,
            false,
            'Punya kedaluwarsa'
        );
        $isActive = $this->importBoolean(
            $row['aktif'] ?? null,
            true,
            'Aktif'
        );

        if ($hasExpiry && ! $trackStock) {
            throw ValidationException::withMessages([
                'import' => 'Barang berkedaluwarsa harus mengaktifkan pengelolaan stok.',
            ]);
        }

        $existing = Product::withTrashed()
            ->whereRaw('LOWER(sku) = ?', [mb_strtolower($sku)])
            ->first();

        if ($existing?->trashed()) {
            throw ValidationException::withMessages([
                'import' => 'SKU '.$sku.' pernah dihapus. Pulihkan data lama atau gunakan SKU lain.',
            ]);
        }

        if ($existing && ! $this->updateExistingProducts) {
            throw ValidationException::withMessages([
                'import' => 'SKU '.$sku.' sudah tersedia. Aktifkan opsi pembaruan untuk memperbaruinya.',
            ]);
        }

        if (
            $existing
            && (int) $existing->unit_id !== (int) $baseUnit->id
            && $this->hasProductHistory($existing->id)
        ) {
            throw ValidationException::withMessages([
                'import' => 'Satuan dasar SKU '.$sku.' tidak dapat diubah karena sudah mempunyai histori.',
            ]);
        }

        $conversions = $this->importConversions(
            $row,
            $unitMap,
            $baseUnit->id,
            $barcode
        );

        return DB::transaction(function () use (
            $existing,
            $sku,
            $name,
            $barcode,
            $rackLocation,
            $category,
            $baseUnit,
            $purchasePrice,
            $sellingPrice,
            $wholesalePrice,
            $wholesaleMinimum,
            $minimumStock,
            $trackStock,
            $hasExpiry,
            $isActive,
            $conversions
        ): string {
            $product = $existing
                ? Product::query()->lockForUpdate()->findOrFail($existing->id)
                : new Product();

            $isNew = ! $product->exists;
            $baseUnitChanged = $product->exists
                && (int) $product->unit_id !== (int) $baseUnit->id;

            $this->assertImportedBarcodeAvailable(
                $barcode,
                $product->exists ? $product->id : null,
                null,
                'Barcode dasar'
            );

            if ($baseUnitChanged) {
                $product->conversionUnits()->delete();
            }

            $product->fill([
                'product_category_id' => $category->id,
                'unit_id' => $baseUnit->id,
                'sku' => $sku,
                'barcode' => $barcode,
                'name' => $name,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'wholesale_price' => $wholesalePrice,
                'wholesale_min_qty' => $wholesaleMinimum,
                'minimum_stock' => $minimumStock,
                'rack_location' => $rackLocation,
                'track_stock' => $trackStock,
                'has_expiry' => $hasExpiry,
                'is_active' => $isActive,
            ]);

            if ($isNew) {
                $product->average_cost = $purchasePrice;
            }

            $product->save();

            foreach ($conversions as $conversionRow) {
                $this->assertImportedBarcodeAvailable(
                    $conversionRow['barcode'],
                    $product->id,
                    $conversionRow['unit_id'],
                    'Barcode konversi'
                );

                $product->conversionUnits()->updateOrCreate(
                    ['unit_id' => $conversionRow['unit_id']],
                    [
                        'conversion_factor' => $conversionRow['conversion_factor'],
                        'barcode' => $conversionRow['barcode'],
                        'purchase_price' => $conversionRow['purchase_price'],
                        'selling_price' => $conversionRow['selling_price'],
                        'is_active' => true,
                    ]
                );
            }

            AuditService::log(
                'product.imported',
                $product,
                [
                    'source' => 'excel',
                    'action' => $isNew ? 'created' : 'updated',
                    'conversions' => count($conversions),
                ]
            );

            return $isNew ? 'created' : 'updated';
        }, 3);
    }

    private function importConversions(
        array $row,
        array $unitMap,
        int $baseUnitId,
        ?string $mainBarcode
    ): array {
        $conversions = [];
        $usedUnitIds = [];
        $usedBarcodes = array_filter([$mainBarcode]);

        for ($number = 1; $number <= 3; $number++) {
            $prefix = 'konversi_'.$number.'_';
            $unitValue = $this->nullableImportText($row[$prefix.'satuan'] ?? null);
            $factorValue = $row[$prefix.'faktor'] ?? null;
            $barcode = $this->nullableImportText($row[$prefix.'barcode'] ?? null);
            $purchasePriceValue = $row[$prefix.'harga_beli'] ?? null;
            $sellingPriceValue = $row[$prefix.'harga_jual'] ?? null;

            $hasAnyValue = $unitValue !== null
                || $this->nullableImportText($factorValue) !== null
                || $barcode !== null
                || $this->nullableImportText($purchasePriceValue) !== null
                || $this->nullableImportText($sellingPriceValue) !== null;

            if (! $hasAnyValue) {
                continue;
            }

            if (! $unitValue) {
                throw ValidationException::withMessages([
                    'import' => 'Satuan konversi '.$number.' wajib diisi.',
                ]);
            }

            $unit = $unitMap[$this->normaliseImportLookup($unitValue)] ?? null;

            if (! $unit) {
                throw ValidationException::withMessages([
                    'import' => 'Satuan konversi "'.$unitValue.'" tidak ditemukan.',
                ]);
            }

            if ((int) $unit->id === $baseUnitId) {
                throw ValidationException::withMessages([
                    'import' => 'Satuan konversi '.$number.' tidak boleh sama dengan satuan dasar.',
                ]);
            }

            if (isset($usedUnitIds[$unit->id])) {
                throw ValidationException::withMessages([
                    'import' => 'Satuan konversi '.$unitValue.' dipilih lebih dari sekali.',
                ]);
            }

            $factor = $this->importNumber(
                $factorValue,
                'Faktor konversi '.$number
            );

            if ($factor <= 0) {
                throw ValidationException::withMessages([
                    'import' => 'Faktor konversi '.$number.' harus lebih besar dari nol.',
                ]);
            }

            if ($barcode && in_array($barcode, $usedBarcodes, true)) {
                throw ValidationException::withMessages([
                    'import' => 'Barcode '.$barcode.' digunakan lebih dari sekali pada baris yang sama.',
                ]);
            }

            if ($barcode && mb_strlen($barcode) > 100) {
                throw ValidationException::withMessages([
                    'import' => 'Barcode konversi maksimal 100 karakter.',
                ]);
            }

            $usedUnitIds[$unit->id] = true;

            if ($barcode) {
                $usedBarcodes[] = $barcode;
            }

            $conversions[] = [
                'unit_id' => $unit->id,
                'conversion_factor' => $factor,
                'barcode' => $barcode,
                'purchase_price' => $this->importNumber(
                    $purchasePriceValue,
                    'Harga beli konversi '.$number,
                    true
                ),
                'selling_price' => $this->importNumber(
                    $sellingPriceValue,
                    'Harga jual konversi '.$number,
                    true
                ),
            ];
        }

        return $conversions;
    }

    private function assertImportedBarcodeAvailable(
        ?string $barcode,
        ?int $productId,
        ?int $unitId,
        string $label
    ): void {
        if (! $barcode) {
            return;
        }

        $productConflict = Product::query()
            ->where('barcode', $barcode)
            ->when(
                $productId,
                fn (Builder $query) => $query->whereKeyNot($productId)
            )
            ->exists();

        if ($productConflict) {
            throw ValidationException::withMessages([
                'import' => $label.' '.$barcode.' sudah digunakan sebagai barcode dasar barang lain.',
            ]);
        }

        $conversionConflict = ProductUnit::query()
            ->where('barcode', $barcode)
            ->when(
                $productId && $unitId,
                fn (Builder $query) => $query->where(function (Builder $conflict) use ($productId, $unitId): void {
                    $conflict
                        ->where('product_id', '!=', $productId)
                        ->orWhere('unit_id', '!=', $unitId);
                })
            )
            ->exists();

        if ($conversionConflict) {
            throw ValidationException::withMessages([
                'import' => $label.' '.$barcode.' sudah digunakan pada satuan konversi.',
            ]);
        }
    }

    private function categoryImportMap(): array
    {
        $map = [];

        ProductCategory::query()->get()->each(function (ProductCategory $category) use (&$map): void {
            foreach ([$category->code, $category->name] as $value) {
                $key = $this->normaliseImportLookup($value);

                if ($key !== '') {
                    $map[$key] = $category;
                }
            }
        });

        return $map;
    }

    private function unitImportMap(): array
    {
        $map = [];

        Unit::query()->get()->each(function (Unit $unit) use (&$map): void {
            foreach ([$unit->symbol, $unit->name] as $value) {
                $key = $this->normaliseImportLookup($value);

                if ($key !== '') {
                    $map[$key] = $unit;
                }
            }
        });

        return $map;
    }

    private function importRowIsEmpty(array $row): bool
    {
        return collect($row)->every(
            fn ($value) => $value === null || trim((string) $value) === ''
        );
    }

    private function requiredImportText(mixed $value, string $label): string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            throw ValidationException::withMessages([
                'import' => $label.' wajib diisi.',
            ]);
        }

        return $text;
    }

    private function nullableImportText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function importNumber(
        mixed $value,
        string $label,
        bool $nullable = false
    ): ?float {
        if ($value === null || trim((string) $value) === '') {
            if ($nullable) {
                return null;
            }

            throw ValidationException::withMessages([
                'import' => $label.' wajib diisi.',
            ]);
        }

        if (is_int($value) || is_float($value)) {
            $number = (float) $value;
        } else {
            $normalised = trim((string) $value);
            $normalised = preg_replace('/(?i)rp|\s+/', '', $normalised) ?? '';

            if (
                str_contains($normalised, '.')
                && str_contains($normalised, ',')
            ) {
                if (strrpos($normalised, ',') > strrpos($normalised, '.')) {
                    $normalised = str_replace('.', '', $normalised);
                    $normalised = str_replace(',', '.', $normalised);
                } else {
                    $normalised = str_replace(',', '', $normalised);
                }
            } elseif (str_contains($normalised, ',')) {
                $normalised = str_replace(',', '.', $normalised);
            } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $normalised)) {
                $normalised = str_replace('.', '', $normalised);
            }

            if (! is_numeric($normalised)) {
                throw ValidationException::withMessages([
                    'import' => $label.' harus berupa angka.',
                ]);
            }

            $number = (float) $normalised;
        }

        if ($number < 0) {
            throw ValidationException::withMessages([
                'import' => $label.' tidak boleh kurang dari nol.',
            ]);
        }

        return $number;
    }

    private function importBoolean(
        mixed $value,
        bool $default,
        string $label
    ): bool {
        if ($value === null || trim((string) $value) === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalised = $this->normaliseImportLookup($value);

        if (in_array($normalised, ['1', 'ya', 'yes', 'true', 'aktif'], true)) {
            return true;
        }

        if (in_array($normalised, ['0', 'tidak', 'no', 'false', 'nonaktif'], true)) {
            return false;
        }

        throw ValidationException::withMessages([
            'import' => $label.' hanya boleh berisi YA atau TIDAK.',
        ]);
    }

    private function normaliseImportLookup(mixed $value): string
    {
        return mb_strtolower(trim((string) ($value ?? '')));
    }

    private function importExceptionMessage(Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return collect($exception->errors())
                ->flatten()
                ->first() ?? 'Data pada baris tidak valid.';
        }

        return 'Terjadi kesalahan saat menyimpan baris. Periksa data lalu coba kembali.';
    }

    private function resetImportState(): void
    {
        $this->importFile = null;
        $this->updateExistingProducts = true;
        $this->importSummary = [];
        $this->importErrors = [];
        $this->resetValidation('importFile');
    }

    private function hasProductHistory(int $productId): bool
    {
        return StockMovement::query()->where('product_id', $productId)->exists()
            || SaleItem::query()->where('product_id', $productId)->exists()
            || PurchaseItem::query()->where('product_id', $productId)->exists();
    }

    private function nullableValue(mixed $value): mixed
    {
        return $value === '' || $value === null ? null : $value;
    }

    private function messages(): array
    {
        return [
            'form.wholesale_price.required_with' => 'Harga grosir wajib diisi ketika minimal pembelian grosir diisi.',
            'form.wholesale_min_qty.required_with' => 'Minimal pembelian grosir wajib diisi ketika harga grosir diisi.',
            'conversions.*.unit_id.distinct' => 'Satuan konversi tidak boleh dipilih lebih dari sekali.',
            'conversions.*.barcode.distinct' => 'Barcode konversi tidak boleh sama.',
        ];
    }

    private function validationAttributes(): array
    {
        return [
            'form.product_category_id' => 'kategori',
            'form.unit_id' => 'satuan dasar',
            'form.sku' => 'SKU',
            'form.barcode' => 'barcode dasar',
            'form.name' => 'nama barang',
            'form.purchase_price' => 'harga beli dasar',
            'form.selling_price' => 'harga jual dasar',
            'form.wholesale_price' => 'harga grosir',
            'form.wholesale_min_qty' => 'minimal grosir',
            'form.minimum_stock' => 'stok minimum',
            'form.rack_location' => 'lokasi rak',
            'conversions.*.unit_id' => 'satuan konversi',
            'conversions.*.conversion_factor' => 'faktor konversi',
            'conversions.*.barcode' => 'barcode konversi',
            'conversions.*.purchase_price' => 'harga beli konversi',
            'conversions.*.selling_price' => 'harga jual konversi',
        ];
    }
}
