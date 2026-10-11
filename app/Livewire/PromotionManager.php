<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Promotion;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class PromotionManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public string $scopeFilter = 'all';
    public int $perPage = 15;

    public bool $showModal = false;
    public ?int $editingId = null;
    public array $form = [];
    public array $productIds = [];

    public function mount(): void
    {
        Gate::authorize('promotions.manage');
        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedScopeFilter(): void
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

    public function updatedFormType(string $value): void
    {
        if ($value === 'fixed') {
            $this->form['maximum_discount'] = null;
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = 'all';
        $this->scopeFilter = 'all';
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('promotions.manage');

        $this->editingId = null;
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize('promotions.manage');

        $promotion = Promotion::query()
            ->with('products')
            ->withCount('sales')
            ->findOrFail($id);

        if ($promotion->sales_count > 0) {
            $this->dispatch(
                'alert',
                type: 'warning',
                title: 'Promo sudah digunakan',
                message: 'Promo yang sudah dipakai transaksi dikunci untuk menjaga histori. Gunakan tombol Duplikasi untuk membuat versi baru.'
            );
            return;
        }

        $this->editingId = $promotion->id;
        $this->form = [
            'code' => $promotion->code,
            'name' => $promotion->name,
            'type' => $promotion->type,
            'value' => (float) $promotion->value,
            'minimum_transaction' => (float) $promotion->minimum_transaction,
            'maximum_discount' => $promotion->maximum_discount !== null
                ? (float) $promotion->maximum_discount
                : null,
            'starts_at' => $promotion->starts_at?->format('Y-m-d\TH:i'),
            'ends_at' => $promotion->ends_at?->format('Y-m-d\TH:i'),
            'is_active' => (bool) $promotion->is_active,
        ];

        $this->productIds = $promotion->products
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        Gate::authorize('promotions.manage');

        $data = $this->validate(
            [
                'form.code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('promotions', 'code')
                        ->ignore($this->editingId),
                ],
                'form.name' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'form.type' => [
                    'required',
                    Rule::in(['percent', 'fixed']),
                ],
                'form.value' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],
                'form.minimum_transaction' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],
                'form.maximum_discount' => [
                    'nullable',
                    'numeric',
                    'gt:0',
                ],
                'form.starts_at' => [
                    'required',
                    'date',
                ],
                'form.ends_at' => [
                    'required',
                    'date',
                    'after:form.starts_at',
                ],
                'form.is_active' => ['boolean'],
                'productIds' => ['array'],
                'productIds.*' => [
                    'integer',
                    'distinct',
                    'exists:products,id',
                ],
            ],
            [
                'form.ends_at.after' => 'Waktu berakhir harus setelah waktu mulai.',
                'form.maximum_discount.gt' => 'Maksimum diskon harus lebih besar dari nol.',
            ],
            [
                'form.code' => 'kode promo',
                'form.name' => 'nama promo',
                'form.type' => 'jenis promo',
                'form.value' => 'nilai diskon',
                'form.minimum_transaction' => 'minimum transaksi',
                'form.maximum_discount' => 'maksimum diskon',
                'form.starts_at' => 'waktu mulai',
                'form.ends_at' => 'waktu berakhir',
                'productIds' => 'produk promo',
            ]
        );

        $data['form']['code'] = mb_strtoupper(
            trim($data['form']['code'])
        );
        $data['form']['name'] = trim($data['form']['name']);
        $data['form']['minimum_transaction'] =
            $data['form']['minimum_transaction'] ?: 0;

        if ($data['form']['type'] === 'fixed') {
            $data['form']['maximum_discount'] = null;
        }

        if (
            $data['form']['type'] === 'percent'
            && (float) $data['form']['value'] > 100
        ) {
            throw ValidationException::withMessages([
                'form.value' => 'Diskon persen tidak boleh lebih dari 100%.',
            ]);
        }

        $activeProductIds = Product::query()
            ->where('is_active', true)
            ->whereIn('id', $data['productIds'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($activeProductIds) !== count($data['productIds'])) {
            throw ValidationException::withMessages([
                'productIds' => 'Salah satu produk sudah tidak aktif. Pilih ulang produk promo.',
            ]);
        }

        $data['productIds'] = $activeProductIds;
        $wasEditing = $this->editingId !== null;

        DB::transaction(function () use ($data): void {
            $promotion = $this->editingId
                ? Promotion::query()
                    ->whereKey($this->editingId)
                    ->lockForUpdate()
                    ->firstOrFail()
                : new Promotion();

            if (
                $promotion->exists
                && $promotion->sales()->exists()
            ) {
                throw ValidationException::withMessages([
                    'form.code' => 'Promo sudah digunakan transaksi dan tidak dapat diubah. Duplikasi promo untuk membuat versi baru.',
                ]);
            }

            $promotion->fill($data['form']);
            $promotion->save();
            $promotion->products()->sync($data['productIds']);

            AuditService::log(
                'promotion.'.($this->editingId ? 'updated' : 'created'),
                $promotion,
                [
                    'product_ids' => $data['productIds'],
                ]
            );
        }, 3);

        $this->showModal = false;
        $this->editingId = null;
        $this->resetForm();

        $this->dispatch(
            'toast',
            message: $wasEditing
                ? 'Promo berhasil diperbarui.'
                : 'Promo berhasil dibuat.'
        );
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('promotions.manage');

        $promotion = Promotion::query()->findOrFail($id);
        $promotion->update([
            'is_active' => ! $promotion->is_active,
        ]);

        AuditService::log('promotion.status_updated', $promotion, [
            'is_active' => $promotion->is_active,
        ]);

        $this->dispatch(
            'toast',
            message: $promotion->is_active
                ? 'Promo berhasil diaktifkan.'
                : 'Promo berhasil dinonaktifkan.'
        );
    }

    public function duplicate(int $id): void
    {
        Gate::authorize('promotions.manage');

        DB::transaction(function () use ($id): void {
            $source = Promotion::query()
                ->with('products')
                ->findOrFail($id);

            $durationMinutes = max(
                60,
                $source->starts_at->diffInMinutes($source->ends_at)
            );

            $promotion = $source->replicate();
            $promotion->code = $this->duplicateCode($source->code);
            $promotion->name = $source->name.' (Salinan)';
            $promotion->starts_at = now();
            $promotion->ends_at = now()->addMinutes($durationMinutes);
            $promotion->is_active = false;
            $promotion->save();

            $promotion->products()->sync(
                $source->products->pluck('id')->all()
            );

            AuditService::log('promotion.duplicated', $promotion, [
                'source_promotion_id' => $source->id,
            ]);
        });

        $this->dispatch(
            'toast',
            message: 'Salinan promo berhasil dibuat dalam kondisi nonaktif.'
        );
    }

    public function delete(int $id): void
    {
        Gate::authorize('promotions.manage');

        $promotion = Promotion::query()
            ->withCount('sales')
            ->findOrFail($id);

        if ($promotion->sales_count > 0) {
            $this->dispatch(
                'alert',
                type: 'warning',
                title: 'Promo tidak dapat dihapus',
                message: 'Promo sudah dipakai transaksi. Nonaktifkan promo agar histori penjualan tetap utuh.'
            );
            return;
        }

        DB::transaction(function () use ($promotion): void {
            $promotion->products()->detach();
            $promotion->delete();

            AuditService::log('promotion.deleted', $promotion);
        });

        $this->dispatch(
            'toast',
            message: 'Promo yang belum pernah digunakan berhasil dihapus.'
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
        Gate::authorize('promotions.manage');

        $now = now();

        $query = Promotion::query()
            ->withCount(['products', 'sales']);

        if ($this->search !== '') {
            $terms = preg_split('/\s+/', trim($this->search)) ?: [];

            foreach (array_filter($terms) as $term) {
                $like = '%'.$term.'%';

                $query->where(function (Builder $builder) use ($like): void {
                    $builder
                        ->where('code', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhereHas('products', function (Builder $product) use ($like): void {
                            $product
                                ->where('name', 'like', $like)
                                ->orWhere('sku', 'like', $like);
                        });
                });
            }
        }

        $query
            ->when(
                $this->status === 'active',
                fn (Builder $builder) => $builder
                    ->where('is_active', true)
                    ->where('starts_at', '<=', $now)
                    ->where('ends_at', '>=', $now)
            )
            ->when(
                $this->status === 'scheduled',
                fn (Builder $builder) => $builder
                    ->where('is_active', true)
                    ->where('starts_at', '>', $now)
            )
            ->when(
                $this->status === 'expired',
                fn (Builder $builder) => $builder
                    ->where('ends_at', '<', $now)
            )
            ->when(
                $this->status === 'inactive',
                fn (Builder $builder) => $builder
                    ->where('is_active', false)
            )
            ->when(
                $this->scopeFilter === 'all_products',
                fn (Builder $builder) => $builder
                    ->whereDoesntHave('products')
            )
            ->when(
                $this->scopeFilter === 'selected_products',
                fn (Builder $builder) => $builder
                    ->whereHas('products')
            );

        $promotions = $query
            ->latest()
            ->paginate(
                in_array($this->perPage, [15, 30, 50], true)
                    ? $this->perPage
                    : 15
            )
            ->withQueryString();

        $base = Promotion::query();

        return view('livewire.promotion-manager', [
            'promotions' => $promotions,
            'products' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'sku', 'name']),
            'summary' => [
                'total' => (clone $base)->count(),
                'active' => (clone $base)
                    ->where('is_active', true)
                    ->where('starts_at', '<=', $now)
                    ->where('ends_at', '>=', $now)
                    ->count(),
                'scheduled' => (clone $base)
                    ->where('is_active', true)
                    ->where('starts_at', '>', $now)
                    ->count(),
                'locked' => (clone $base)
                    ->whereHas('sales')
                    ->count(),
            ],
        ]);
    }

    private function duplicateCode(string $sourceCode): string
    {
        $base = mb_substr(
            mb_strtoupper(trim($sourceCode)),
            0,
            34
        );

        $suffix = '-COPY';
        $candidate = $base.$suffix;
        $number = 2;

        while (
            Promotion::query()
                ->where('code', $candidate)
                ->exists()
        ) {
            $candidate = mb_substr(
                $base,
                0,
                50 - mb_strlen($suffix.(string) $number)
            ).$suffix.$number;

            $number++;
        }

        return $candidate;
    }

    private function resetForm(): void
    {
        $this->form = [
            'code' => '',
            'name' => '',
            'type' => 'percent',
            'value' => 0,
            'minimum_transaction' => 0,
            'maximum_discount' => null,
            'starts_at' => now()->format('Y-m-d\TH:i'),
            'ends_at' => now()->addMonth()->format('Y-m-d\TH:i'),
            'is_active' => true,
        ];

        $this->productIds = [];
        $this->resetValidation();
    }
}
