<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class MasterDataManager extends Component
{
    use WithPagination;

    public string $type;
    public string $search = '';
    public string $statusFilter = 'all';
    public int $perPage = 15;

    public bool $showModal = false;
    public ?int $editingId = null;
    public array $form = [];

    private const MAP = [
        'categories' => [
            'model' => ProductCategory::class,
            'title' => 'Kategori',
            'singular' => 'Kategori',
            'permission' => 'categories.manage',
            'icon' => 'fa-tags',
            'description' => 'Kelompokkan barang agar pencarian, laporan, dan pengelolaan stok lebih teratur.',
            'supports_status' => true,
        ],
        'units' => [
            'model' => Unit::class,
            'title' => 'Satuan',
            'singular' => 'Satuan',
            'permission' => 'units.manage',
            'icon' => 'fa-scale-balanced',
            'description' => 'Kelola satuan dasar dan satuan yang dapat digunakan pada konversi barang.',
            'supports_status' => false,
        ],
        'suppliers' => [
            'model' => Supplier::class,
            'title' => 'Supplier',
            'singular' => 'Supplier',
            'permission' => 'suppliers.manage',
            'icon' => 'fa-truck-field',
            'description' => 'Simpan data pemasok untuk pembelian, utang, dan riwayat penerimaan barang.',
            'supports_status' => true,
        ],
        'customers' => [
            'model' => Customer::class,
            'title' => 'Pelanggan & Member',
            'singular' => 'Pelanggan',
            'permission' => 'customers.manage',
            'icon' => 'fa-users',
            'description' => 'Kelola pelanggan, member, poin, limit kredit, dan riwayat transaksi.',
            'supports_status' => true,
        ],
        'warehouses' => [
            'model' => Warehouse::class,
            'title' => 'Gudang',
            'singular' => 'Gudang',
            'permission' => 'warehouses.manage',
            'icon' => 'fa-warehouse',
            'description' => 'Atur lokasi penyimpanan yang digunakan pada pembelian, penjualan, dan stok opname.',
            'supports_status' => true,
        ],
    ];

    public function mount(string $type): void
    {
        abort_unless(isset(self::MAP[$type]), 404);

        $this->type = $type;
        Gate::authorize($this->permission());
        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
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

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize($this->permission());

        $this->editingId = null;
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize($this->permission());

        $model = $this->modelClass()::query()->findOrFail($id);

        $this->editingId = $id;
        $this->form = collect($this->defaults())
            ->mapWithKeys(fn ($default, $field) => [$field => $model->{$field} ?? $default])
            ->all();

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        Gate::authorize($this->permission());

        $this->normaliseForm();
        $data = $this->validate(
            $this->rules(),
            $this->messages(),
            $this->validationAttributes()
        )['form'];

        if ($this->type === 'warehouses') {
            $storeId = Store::query()->value('id');

            if (! $storeId) {
                throw ValidationException::withMessages([
                    'form.name' => 'Data toko belum tersedia. Isi pengaturan toko sebelum menambahkan gudang.',
                ]);
            }

            $data['store_id'] = $storeId;
        }

        $wasEditing = $this->editingId !== null;

        $model = $this->editingId
            ? $this->modelClass()::query()->findOrFail($this->editingId)
            : new ($this->modelClass());

        $model->fill($data);
        $model->save();

        AuditService::log(
            $this->type.'.'.($wasEditing ? 'updated' : 'created'),
            $model
        );

        $this->showModal = false;
        $this->editingId = null;
        $this->resetForm();

        $this->dispatch(
            'toast',
            message: $this->singular().' berhasil '.($wasEditing ? 'diperbarui.' : 'ditambahkan.')
        );
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize($this->permission());

        if (! $this->supportsStatus()) {
            return;
        }

        $model = $this->modelClass()::query()->findOrFail($id);
        $model->is_active = ! (bool) $model->is_active;
        $model->save();

        AuditService::log($this->type.'.status_updated', $model, [
            'is_active' => (bool) $model->is_active,
        ]);

        $this->dispatch(
            'toast',
            message: $this->singular().' berhasil '.
                ($model->is_active ? 'diaktifkan.' : 'dinonaktifkan.')
        );
    }

    public function delete(int $id): void
    {
        Gate::authorize($this->permission());

        $model = $this->modelClass()::query()->findOrFail($id);
        $usageMessage = $this->usageMessage($model);

        if ($usageMessage !== null) {
            $this->dispatch(
                'alert',
                type: 'error',
                title: $this->singular().' tidak dapat dihapus',
                message: $usageMessage
            );
            return;
        }

        $model->delete();

        AuditService::log($this->type.'.deleted', $model);
        $this->dispatch(
            'toast',
            message: $this->singular().' berhasil dihapus.'
        );
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->resetForm();
    }

    public function render()
    {
        Gate::authorize($this->permission());

        $query = $this->modelClass()::query();
        $this->applyUsageCounts($query);
        $this->applySearch($query);

        if ($this->supportsStatus()) {
            $query
                ->when(
                    $this->statusFilter === 'active',
                    fn (Builder $builder) => $builder->where('is_active', true)
                )
                ->when(
                    $this->statusFilter === 'inactive',
                    fn (Builder $builder) => $builder->where('is_active', false)
                );
        }

        $perPage = in_array($this->perPage, [15, 30, 50], true)
            ? $this->perPage
            : 15;

        $rows = $query
            ->orderByDesc($this->supportsStatus() ? 'is_active' : 'id')
            ->orderBy('name')
            ->paginate($perPage);

        if ($this->type === 'units') {
            $rows->getCollection()->transform(function (Unit $unit): Unit {
                $unit->usage_count =
                    (int) ($unit->base_usage_count ?? 0)
                    + (int) ($unit->conversion_usage_count ?? 0);

                return $unit;
            });
        }

        return view('livewire.master-data-manager', [
            'rows' => $rows,
            'meta' => self::MAP[$this->type],
            'stats' => $this->stats(),
        ]);
    }

    private function applySearch(Builder $query): void
    {
        $terms = preg_split('/\s+/', trim($this->search)) ?: [];

        foreach (array_filter($terms) as $term) {
            $like = '%'.$term.'%';

            $query->where(function (Builder $builder) use ($like): void {
                $builder->where('name', 'like', $like);

                if ($this->type !== 'units') {
                    $builder->orWhere('code', 'like', $like);
                }

                if ($this->type === 'units') {
                    $builder->orWhere('symbol', 'like', $like);
                }

                if (in_array($this->type, ['suppliers', 'customers'], true)) {
                    $builder
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('address', 'like', $like);
                }

                if ($this->type === 'suppliers') {
                    $builder->orWhere('notes', 'like', $like);
                }

                if ($this->type === 'warehouses') {
                    $builder->orWhere('address', 'like', $like);
                }
            });
        }
    }

    private function applyUsageCounts(Builder $query): void
    {
        match ($this->type) {
            'categories' => $query->withCount('products as usage_count'),
            'units' => $query->withCount([
                'products as base_usage_count',
                'productConversions as conversion_usage_count',
            ]),
            'suppliers' => $query->withCount('purchases as usage_count'),
            'customers' => $query->withCount('sales as usage_count'),
            'warehouses' => $query
                ->select('warehouses.*')
                ->selectRaw(
                    '(
                        (SELECT COUNT(*) FROM stocks WHERE stocks.warehouse_id = warehouses.id)
                        + (SELECT COUNT(*) FROM purchases WHERE purchases.warehouse_id = warehouses.id)
                        + (SELECT COUNT(*) FROM sales WHERE sales.warehouse_id = warehouses.id)
                        + (SELECT COUNT(*) FROM stock_opnames WHERE stock_opnames.warehouse_id = warehouses.id)
                        + (SELECT COUNT(*) FROM stock_movements WHERE stock_movements.warehouse_id = warehouses.id)
                    ) AS usage_count'
                ),
        };
    }

    private function stats(): array
    {
        $model = $this->modelClass();
        $total = $model::query()->count();

        if ($this->type === 'units') {
            $baseUsed = Unit::query()->whereHas('products')->count();
            $conversionUsed = Unit::query()->whereHas('productConversions')->count();

            return [
                [
                    'label' => 'Total satuan',
                    'value' => $total,
                    'icon' => 'fa-scale-balanced',
                    'class' => 'is-total',
                ],
                [
                    'label' => 'Satuan dasar',
                    'value' => $baseUsed,
                    'icon' => 'fa-box',
                    'class' => 'is-active',
                ],
                [
                    'label' => 'Konversi',
                    'value' => $conversionUsed,
                    'icon' => 'fa-right-left',
                    'class' => 'is-info',
                ],
                [
                    'label' => 'Belum digunakan',
                    'value' => Unit::query()
                        ->whereDoesntHave('products')
                        ->whereDoesntHave('productConversions')
                        ->count(),
                    'icon' => 'fa-circle-minus',
                    'class' => 'is-muted',
                ],
            ];
        }

        $active = $model::query()->where('is_active', true)->count();
        $inactive = $model::query()->where('is_active', false)->count();

        $used = match ($this->type) {
            'categories' => ProductCategory::query()->whereHas('products')->count(),
            'suppliers' => Supplier::query()->whereHas('purchases')->count(),
            'customers' => Customer::query()->whereHas('sales')->count(),
            'warehouses' => Warehouse::query()
                ->where(function (Builder $query): void {
                    $query
                        ->whereExists(function ($sub): void {
                            $sub->selectRaw('1')
                                ->from('stocks')
                                ->whereColumn('stocks.warehouse_id', 'warehouses.id');
                        })
                        ->orWhereExists(function ($sub): void {
                            $sub->selectRaw('1')
                                ->from('purchases')
                                ->whereColumn('purchases.warehouse_id', 'warehouses.id');
                        })
                        ->orWhereExists(function ($sub): void {
                            $sub->selectRaw('1')
                                ->from('sales')
                                ->whereColumn('sales.warehouse_id', 'warehouses.id');
                        });
                })
                ->count(),
        };

        return [
            [
                'label' => 'Total '.$this->singular(),
                'value' => $total,
                'icon' => self::MAP[$this->type]['icon'],
                'class' => 'is-total',
            ],
            [
                'label' => 'Aktif',
                'value' => $active,
                'icon' => 'fa-circle-check',
                'class' => 'is-active',
            ],
            [
                'label' => 'Nonaktif',
                'value' => $inactive,
                'icon' => 'fa-circle-minus',
                'class' => 'is-muted',
            ],
            [
                'label' => 'Sudah digunakan',
                'value' => $used,
                'icon' => 'fa-link',
                'class' => 'is-info',
            ],
        ];
    }

    private function usageMessage(Model $model): ?string
    {
        $used = match ($this->type) {
            'categories' => $model->products()->exists(),
            'units' => $model->products()->exists()
                || $model->productConversions()->exists(),
            'suppliers' => $model->purchases()->exists(),
            'customers' => $model->sales()->exists(),
            'warehouses' => Stock::query()->where('warehouse_id', $model->id)->exists()
                || Purchase::query()->where('warehouse_id', $model->id)->exists()
                || Sale::query()->where('warehouse_id', $model->id)->exists()
                || StockOpname::query()->where('warehouse_id', $model->id)->exists()
                || StockMovement::query()->where('warehouse_id', $model->id)->exists(),
        };

        if (! $used) {
            return null;
        }

        if ($this->supportsStatus()) {
            return $this->singular().
                ' sudah digunakan pada barang atau transaksi. Nonaktifkan data agar histori tetap utuh.';
        }

        return 'Satuan sudah digunakan sebagai satuan dasar atau konversi barang sehingga tidak dapat dihapus.';
    }

    private function normaliseForm(): void
    {
        foreach ($this->form as $field => $value) {
            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);

            if ($field === 'code') {
                $value = Str::upper($value);
            }

            if ($field === 'email') {
                $value = Str::lower($value);
            }

            $this->form[$field] = $value === '' ? null : $value;
        }
    }

    private function rules(): array
    {
        $table = (new ($this->modelClass()))->getTable();

        $rules = [
            'form.name' => ['required', 'string', 'max:255'],
        ];

        if ($this->type !== 'units') {
            $codeRequired = in_array(
                $this->type,
                ['categories', 'warehouses'],
                true
            );

            $codeRule = Rule::unique($table, 'code')->ignore($this->editingId);

            if (in_array($this->type, ['suppliers', 'customers'], true)) {
                $codeRule->whereNull('deleted_at');
            }

            $rules['form.code'] = [
                $codeRequired ? 'required' : 'nullable',
                'string',
                'max:30',
                $codeRule,
            ];
            $rules['form.is_active'] = ['boolean'];
        }

        if ($this->type === 'categories') {
            $rules['form.name'][] = Rule::unique($table, 'name')
                ->ignore($this->editingId);
        }

        if ($this->type === 'units') {
            $rules['form.name'][] = Rule::unique($table, 'name')
                ->ignore($this->editingId);
            $rules['form.symbol'] = [
                'required',
                'string',
                'max:20',
                Rule::unique($table, 'symbol')->ignore($this->editingId),
            ];
        }

        if (in_array($this->type, ['suppliers', 'customers'], true)) {
            $rules['form.phone'] = ['nullable', 'string', 'max:40'];
            $rules['form.email'] = ['nullable', 'email:rfc', 'max:255'];
            $rules['form.address'] = ['nullable', 'string', 'max:2000'];
        }

        if ($this->type === 'suppliers') {
            $rules['form.notes'] = ['nullable', 'string', 'max:2000'];
        }

        if ($this->type === 'customers') {
            $rules['form.credit_limit'] = ['required', 'numeric', 'min:0'];
        }

        if ($this->type === 'warehouses') {
            $rules['form.address'] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }

    private function defaults(): array
    {
        return match ($this->type) {
            'categories' => [
                'code' => '',
                'name' => '',
                'is_active' => true,
            ],
            'units' => [
                'name' => '',
                'symbol' => '',
            ],
            'suppliers' => [
                'code' => '',
                'name' => '',
                'phone' => '',
                'email' => '',
                'address' => '',
                'notes' => '',
                'is_active' => true,
            ],
            'customers' => [
                'code' => '',
                'name' => '',
                'phone' => '',
                'email' => '',
                'address' => '',
                'credit_limit' => 0,
                'is_active' => true,
            ],
            'warehouses' => [
                'code' => '',
                'name' => '',
                'address' => '',
                'is_active' => true,
            ],
        };
    }

    private function resetForm(): void
    {
        $this->form = $this->defaults();
        $this->resetValidation();
    }

    private function messages(): array
    {
        return [
            'form.code.unique' => 'Kode sudah digunakan.',
            'form.name.unique' => 'Nama sudah digunakan.',
            'form.symbol.unique' => 'Simbol satuan sudah digunakan.',
            'form.email.email' => 'Format email tidak valid.',
            'form.credit_limit.min' => 'Limit kredit tidak boleh kurang dari nol.',
        ];
    }

    private function validationAttributes(): array
    {
        return [
            'form.code' => 'kode',
            'form.name' => 'nama',
            'form.symbol' => 'simbol satuan',
            'form.phone' => 'nomor telepon',
            'form.email' => 'email',
            'form.address' => 'alamat',
            'form.notes' => 'catatan',
            'form.credit_limit' => 'limit kredit',
            'form.is_active' => 'status',
        ];
    }

    private function modelClass(): string
    {
        return self::MAP[$this->type]['model'];
    }

    private function permission(): string
    {
        return self::MAP[$this->type]['permission'];
    }

    private function singular(): string
    {
        return self::MAP[$this->type]['singular'];
    }

    private function supportsStatus(): bool
    {
        return (bool) self::MAP[$this->type]['supports_status'];
    }
}
