<?php

namespace App\Livewire;

use App\Models\CashierShift;
use App\Models\CashTransaction;
use App\Models\Expense;
use App\Models\Store;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $from = '';
    public string $to = '';
    public string $methodFilter = 'all';
    public string $categoryFilter = '';
    public int $perPage = 20;

    public bool $showModal = false;
    public ?int $editingId = null;
    public array $form = [];

    public function mount(): void
    {
        Gate::authorize('expenses.view');

        $this->from = now()->startOfMonth()->format('Y-m-d');
        $this->to = now()->format('Y-m-d');
        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->resetPage();
    }

    public function updatedMethodFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [15, 20, 50], true)) {
            $this->perPage = 20;
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->from = now()->startOfMonth()->format('Y-m-d');
        $this->to = now()->format('Y-m-d');
        $this->methodFilter = 'all';
        $this->categoryFilter = '';
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('expenses.create');

        $this->editingId = null;
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize('expenses.update');

        $expense = Expense::query()
            ->with('cashierShift')
            ->findOrFail($id);

        if (! $this->canEditExpense($expense)) {
            $this->dispatch(
                'alert',
                type: 'warning',
                title: 'Pengeluaran terkunci',
                message: 'Pengeluaran tunai dari shift yang sudah ditutup tidak dapat diedit. Buat pengeluaran koreksi pada shift aktif.'
            );
            return;
        }

        $this->editingId = $expense->id;
        $this->form = [
            'expense_date' => $expense->expense_date->format('Y-m-d'),
            'category' => $expense->category,
            'amount' => (float) $expense->amount,
            'payment_method' => $expense->payment_method,
            'notes' => $expense->notes,
        ];

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        Gate::authorize(
            $this->editingId
                ? 'expenses.update'
                : 'expenses.create'
        );

        $data = $this->validate(
            [
                'form.expense_date' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                ],
                'form.category' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'form.amount' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],
                'form.payment_method' => [
                    'required',
                    Rule::in(array_keys(config('pos.payment_methods'))),
                ],
                'form.notes' => [
                    'required',
                    'string',
                    'min:3',
                    'max:1000',
                ],
            ],
            [
                'form.expense_date.before_or_equal' => 'Tanggal pengeluaran tidak boleh melebihi hari ini.',
                'form.amount.gt' => 'Nominal pengeluaran harus lebih besar dari nol.',
                'form.notes.min' => 'Catatan pengeluaran minimal 3 karakter.',
            ],
            [
                'form.expense_date' => 'tanggal pengeluaran',
                'form.category' => 'kategori',
                'form.amount' => 'nominal',
                'form.payment_method' => 'metode pembayaran',
                'form.notes' => 'catatan',
            ]
        )['form'];

        $data['category'] = trim($data['category']);
        $data['notes'] = trim($data['notes']);
        $wasEditing = $this->editingId !== null;

        DB::transaction(function () use ($data): void {
            $expense = $this->editingId
                ? Expense::query()
                    ->with('cashierShift')
                    ->whereKey($this->editingId)
                    ->lockForUpdate()
                    ->firstOrFail()
                : new Expense();

            $oldShift = null;

            if (
                $expense->exists
                && $expense->payment_method === 'cash'
            ) {
                $oldShift = CashierShift::query()
                    ->whereKey($expense->cashier_shift_id)
                    ->lockForUpdate()
                    ->first();

                if (! $oldShift || $oldShift->status !== 'open') {
                    throw ValidationException::withMessages([
                        'form.amount' => 'Pengeluaran tunai dari shift yang sudah ditutup tidak dapat diedit.',
                    ]);
                }

                /*
                 * Kembalikan pengaruh data lama lebih dahulu.
                 * Jika validasi berikutnya gagal, transaction rollback
                 * mengembalikan semua perubahan.
                 */
                $oldShift->increment(
                    'expected_cash',
                    (float) $expense->amount
                );

                CashTransaction::query()
                    ->where('expense_id', $expense->id)
                    ->delete();
            }

            $targetShift = null;

            if ($data['payment_method'] === 'cash') {
                $targetShift = $oldShift;

                if (! $targetShift) {
                    $targetShift = CashierShift::query()
                        ->where('user_id', auth()->id())
                        ->where('status', 'open')
                        ->lockForUpdate()
                        ->latest('opened_at')
                        ->first();
                }

                if (! $targetShift) {
                    throw ValidationException::withMessages([
                        'form.payment_method' => 'Buka shift kasir sebelum mencatat pengeluaran tunai.',
                    ]);
                }

                if ((float) $targetShift->expected_cash < (float) $data['amount']) {
                    throw ValidationException::withMessages([
                        'form.amount' => 'Kas shift hanya tersedia Rp '
                            .number_format(
                                (float) $targetShift->expected_cash,
                                0,
                                ',',
                                '.'
                            ).'.',
                    ]);
                }
            }

            $expense->fill([
                'store_id' => Store::query()->value('id'),
                'cashier_shift_id' => $targetShift?->id,
                'created_by' => $expense->exists
                    ? $expense->created_by
                    : auth()->id(),
                'expense_date' => $data['expense_date'],
                'category' => $data['category'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'],
            ]);

            $expense->save();

            if ($targetShift) {
                $targetShift->decrement(
                    'expected_cash',
                    (float) $data['amount']
                );

                CashTransaction::create([
                    'cashier_shift_id' => $targetShift->id,
                    'expense_id' => $expense->id,
                    'created_by' => auth()->id(),
                    'type' => 'out',
                    'amount' => $data['amount'],
                    'category' => $data['category'],
                    'notes' => $data['notes'],
                ]);
            }

            AuditService::log(
                'expense.'.($this->editingId ? 'updated' : 'created'),
                $expense,
                [
                    'payment_method' => $data['payment_method'],
                    'amount' => $data['amount'],
                    'cashier_shift_id' => $targetShift?->id,
                ]
            );
        }, 3);

        $this->showModal = false;
        $this->editingId = null;
        $this->resetForm();

        $this->dispatch(
            'toast',
            message: $wasEditing
                ? 'Pengeluaran berhasil diperbarui.'
                : 'Pengeluaran berhasil dicatat.'
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
        Gate::authorize('expenses.view');

        $query = Expense::query()
            ->with(['creator', 'cashierShift']);

        $this->applyFilters($query);

        $expenses = $query
            ->latest('expense_date')
            ->latest('id')
            ->paginate(
                in_array($this->perPage, [15, 20, 50], true)
                    ? $this->perPage
                    : 20
            );

        $canUpdate = auth()->user()?->can('expenses.update') ?? false;

        $expenses->getCollection()->each(function (Expense $expense) use ($canUpdate): void {
            $expense->setAttribute(
                'can_edit',
                $canUpdate && $this->canEditExpense($expense)
            );
        });

        $summaryQuery = Expense::query();
        $this->applyFilters($summaryQuery);

        $summary = [
            'count' => (clone $summaryQuery)->count(),
            'total' => (float) (clone $summaryQuery)->sum('amount'),
            'cash' => (float) (clone $summaryQuery)
                ->where('payment_method', 'cash')
                ->sum('amount'),
            'non_cash' => (float) (clone $summaryQuery)
                ->where('payment_method', '!=', 'cash')
                ->sum('amount'),
        ];

        $categories = Expense::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $openShift = CashierShift::query()
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        return view('livewire.expense-manager', compact(
            'expenses',
            'summary',
            'categories',
            'openShift'
        ));
    }

    private function applyFilters(Builder $query): void
    {
        $query
            ->when(
                $this->from !== '',
                fn (Builder $builder) => $builder->whereDate(
                    'expense_date',
                    '>=',
                    $this->from
                )
            )
            ->when(
                $this->to !== '',
                fn (Builder $builder) => $builder->whereDate(
                    'expense_date',
                    '<=',
                    $this->to
                )
            )
            ->when(
                $this->methodFilter !== 'all',
                fn (Builder $builder) => $builder->where(
                    'payment_method',
                    $this->methodFilter
                )
            )
            ->when(
                $this->categoryFilter !== '',
                fn (Builder $builder) => $builder->where(
                    'category',
                    $this->categoryFilter
                )
            );

        if ($this->search !== '') {
            $terms = preg_split('/\s+/', trim($this->search)) ?: [];

            foreach (array_filter($terms) as $term) {
                $like = '%'.$term.'%';

                $query->where(function (Builder $builder) use ($like): void {
                    $builder
                        ->where('category', 'like', $like)
                        ->orWhere('notes', 'like', $like)
                        ->orWhere('payment_method', 'like', $like)
                        ->orWhereHas('creator', fn (Builder $user) => $user->where('name', 'like', $like));
                });
            }
        }
    }

    private function canEditExpense(Expense $expense): bool
    {
        if ($expense->payment_method !== 'cash') {
            return true;
        }

        return $expense->cashierShift?->status === 'open';
    }

    private function resetForm(): void
    {
        $this->form = [
            'expense_date' => now()->format('Y-m-d'),
            'category' => '',
            'amount' => 0,
            'payment_method' => 'cash',
            'notes' => '',
        ];

        $this->resetValidation();
    }
}
