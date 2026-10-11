@extends('layouts.pos')
@section('title', 'Activity Log')
@section('body_class', 'pos-activity-log-page')

@section('content')
@php
    $filterActive = $filters['q'] !== ''
        || $filters['user_id'] !== ''
        || $filters['action_group'] !== ''
        || $filters['subject_type'] !== ''
        || $filters['date_from'] !== ''
        || $filters['date_to'] !== '';

    $groupLabels = [
        'user' => 'Pengguna',
        'role' => 'Role',
        'settings' => 'Pengaturan',
        'sale' => 'Penjualan',
        'sale_return' => 'Retur Penjualan',
        'purchase' => 'Pembelian',
        'purchase_return' => 'Retur Pembelian',
        'stock' => 'Stok',
        'opname' => 'Stok Opname',
        'expense' => 'Pengeluaran',
        'promotion' => 'Promosi',
        'shift' => 'Shift',
    ];

    $actionLabels = [
        'created' => 'Dibuat',
        'updated' => 'Diperbarui',
        'deleted' => 'Dihapus',
        'status_updated' => 'Status Diubah',
        'adjusted' => 'Disesuaikan',
        'submitted' => 'Diajukan',
        'approved' => 'Disetujui',
        'cancelled' => 'Dibatalkan',
        'duplicated' => 'Diduplikasi',
        'verification_resent' => 'Verifikasi Dikirim Ulang',
        'opened' => 'Dibuka',
        'closed' => 'Ditutup',
        'payment_added' => 'Pembayaran Ditambahkan',
    ];
@endphp

<div class="ga-page-head admin-page-head">
    <div class="ga-page-title">
        <span class="ga-eyebrow">
            <i class="fa-solid fa-clipboard-list"></i>
            JEJAK AKTIVITAS SISTEM
        </span>
        <h2>Activity Log</h2>
        <p class="mb-0">
            Telusuri siapa melakukan apa, kapan, dari IP mana,
            dan data apa yang berubah.
        </p>
    </div>

    <button
        type="button"
        class="btn btn-outline-light no-print"
        data-print-page
    >
        <i class="fa-solid fa-print"></i>
        Cetak Hasil
    </button>
</div>

<div class="admin-stat-grid activity-stat-grid">
    <article>
        <span><i class="fa-solid fa-database"></i></span>
        <div>
            <small>Total log</small>
            <strong>{{ number_format($stats['total']) }}</strong>
        </div>
    </article>
    <article class="is-active">
        <span><i class="fa-solid fa-calendar-day"></i></span>
        <div>
            <small>Aktivitas hari ini</small>
            <strong>{{ number_format($stats['today']) }}</strong>
        </div>
    </article>
    <article class="is-secure">
        <span><i class="fa-solid fa-users"></i></span>
        <div>
            <small>Pelaku aktif 30 hari</small>
            <strong>{{ number_format($stats['actors']) }}</strong>
        </div>
    </article>
    <article class="is-warning">
        <span><i class="fa-solid fa-robot"></i></span>
        <div>
            <small>Aktivitas sistem</small>
            <strong>{{ number_format($stats['system']) }}</strong>
        </div>
    </article>
</div>

<div class="activity-print-meta print-only">
    <div>
        <strong>Laporan Activity Log</strong>
        <span>
            {{ number_format($logs->total()) }} aktivitas sesuai filter
            · halaman {{ $logs->currentPage() }} dari {{ $logs->lastPage() }}
        </span>
    </div>
    <div>
        <span>Periode:</span>
        <strong>
            {{ $filters['date_from']
                ? \Illuminate\Support\Carbon::parse($filters['date_from'])->format('d/m/Y')
                : 'Awal data' }}
            —
            {{ $filters['date_to']
                ? \Illuminate\Support\Carbon::parse($filters['date_to'])->format('d/m/Y')
                : 'Hari ini' }}
        </strong>
        <small>Dicetak {{ now()->format('d/m/Y H:i') }}</small>
    </div>
</div>

<section class="admin-list-card activity-list-card">
    <form
        method="get"
        action="{{ route('activity.index') }}"
        class="activity-filter-panel no-print"
    >
        <div class="activity-filter-row activity-filter-row-primary">
            <div class="activity-filter-field activity-filter-field-search">
                <label for="activity-filter-q">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Pencarian
                </label>
                <div class="admin-search-field">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input
                        id="activity-filter-q"
                        name="q"
                        value="{{ $filters['q'] }}"
                        class="form-control"
                        placeholder="Aksi, pengguna, email, IP, subjek, atau ID..."
                        autocomplete="off"
                    >
                </div>
            </div>

            <div class="activity-filter-field">
                <label for="activity-filter-user">
                    <i class="fa-solid fa-user"></i>
                    Pengguna
                </label>
                <select
                    id="activity-filter-user"
                    name="user_id"
                    class="form-select"
                >
                    <option value="">Semua pengguna</option>
                    @foreach($users as $user)
                        <option
                            value="{{ $user->id }}"
                            @selected((string) $user->id === $filters['user_id'])
                        >
                            {{ $user->name }} · {{ $user->email }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="activity-filter-field">
                <label for="activity-filter-action">
                    <i class="fa-solid fa-bolt"></i>
                    Kelompok Aksi
                </label>
                <select
                    id="activity-filter-action"
                    name="action_group"
                    class="form-select"
                >
                    <option value="">Semua kelompok aksi</option>
                    @foreach($actionGroups as $group)
                        <option
                            value="{{ $group }}"
                            @selected($filters['action_group'] === $group)
                        >
                            {{ $groupLabels[$group] ?? str($group)->replace('_', ' ')->title() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="activity-filter-row activity-filter-row-secondary">
            <div class="activity-filter-field">
                <label for="activity-filter-subject">
                    <i class="fa-solid fa-cube"></i>
                    Jenis Subjek
                </label>
                <select
                    id="activity-filter-subject"
                    name="subject_type"
                    class="form-select"
                >
                    <option value="">Semua jenis subjek</option>
                    @foreach($subjectTypes as $type)
                        <option
                            value="{{ $type }}"
                            @selected($filters['subject_type'] === $type)
                        >
                            {{ class_basename($type) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="activity-filter-field">
                <label for="activity-filter-date-from">
                    <i class="fa-solid fa-calendar-day"></i>
                    Tanggal Awal
                </label>
                <input
                    id="activity-filter-date-from"
                    name="date_from"
                    class="form-control"
                    value="{{ $filters['date_from'] }}"
                    type="date"
                >
            </div>

            <div class="activity-filter-field">
                <label for="activity-filter-date-to">
                    <i class="fa-solid fa-calendar-check"></i>
                    Tanggal Akhir
                </label>
                <input
                    id="activity-filter-date-to"
                    name="date_to"
                    class="form-control"
                    value="{{ $filters['date_to'] }}"
                    type="date"
                >
            </div>

            <div class="activity-filter-actions">
                <button class="btn btn-primary">
                    <i class="fa-solid fa-filter"></i>
                    Terapkan Filter
                </button>

                <a
                    href="{{ route('activity.index') }}"
                    class="btn btn-light {{ $filterActive ? '' : 'disabled' }}"
                    @if(! $filterActive)
                        aria-disabled="true"
                        tabindex="-1"
                    @endif
                >
                    <i class="fa-solid fa-xmark"></i>
                    Bersihkan
                </a>
            </div>
        </div>
    </form>

    <div class="admin-list-meta">
        <span>
            <i class="fa-solid fa-list"></i>
            <b>{{ number_format($logs->total()) }}</b>
            aktivitas ditemukan
        </span>

        <form method="get" class="no-print">
            @foreach(request()->except('per_page', 'page') as $key => $value)
                @if(is_scalar($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <label>
                Tampilkan
                <select
                    name="per_page"
                    class="form-select form-select-sm"
                    onchange="this.form.submit()"
                >
                    @foreach([20, 30, 50, 100] as $size)
                        <option
                            value="{{ $size }}"
                            @selected($filters['per_page'] === $size)
                        >
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
            </label>
        </form>
    </div>

    <div class="table-responsive admin-table-wrap">
        <table class="table admin-data-table activity-data-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pelaku</th>
                    <th>Aksi</th>
                    <th>Subjek</th>
                    <th>IP & Perangkat</th>
                    <th>Perubahan / Properti</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $group = str($log->action)->before('.')->toString();
                        $action = str($log->action)->after('.')->toString();
                        $properties = $log->properties ?: [];
                        $propertyCount = is_array($properties) ? count($properties) : 0;
                    @endphp

                    <tr>
                        <td>
                            <div class="admin-date-cell activity-date-cell">
                                <strong>{{ $log->created_at->format('d/m/Y') }}</strong>
                                <small>{{ $log->created_at->format('H:i:s') }}</small>
                                <em>{{ $log->created_at->diffForHumans() }}</em>
                            </div>
                        </td>

                        <td>
                            <div class="activity-actor-cell">
                                <span>
                                    {{ $log->user
                                        ? mb_strtoupper(mb_substr($log->user->name, 0, 1))
                                        : 'S' }}
                                </span>
                                <div>
                                    <strong>{{ $log->user?->name ?? 'Sistem' }}</strong>
                                    <small>{{ $log->user?->email ?? 'Proses otomatis' }}</small>
                                </div>
                            </div>
                        </td>

                        <td>
                            <span class="activity-action-badge is-{{ $group }}">
                                <i class="fa-solid fa-bolt"></i>
                                {{ $groupLabels[$group] ?? str($group)->replace('_', ' ')->title() }}
                                · {{ $actionLabels[$action] ?? str($action)->replace('_', ' ')->title() }}
                            </span>
                            <small class="activity-action-code">{{ $log->action }}</small>
                        </td>

                        <td>
                            @if($log->subject_type)
                                <div class="activity-subject-cell">
                                    <strong>{{ class_basename($log->subject_type) }}</strong>
                                    <small>ID #{{ $log->subject_id ?? '-' }}</small>
                                </div>
                            @else
                                <span class="text-muted">Tanpa subjek model</span>
                            @endif
                        </td>

                        <td>
                            <div class="activity-device-cell">
                                <strong>
                                    <i class="fa-solid fa-network-wired"></i>
                                    {{ $log->ip_address ?: '-' }}
                                </strong>
                                <small title="{{ $log->user_agent }}">
                                    {{ str($log->user_agent ?: 'Tidak diketahui')->limit(54) }}
                                </small>
                            </div>
                        </td>

                        <td>
                            @if($propertyCount > 0)
                                <details class="activity-properties">
                                    <summary>
                                        <i class="fa-solid fa-code"></i>
                                        {{ number_format($propertyCount) }} properti
                                    </summary>
                                    <pre>{{ json_encode(
                                        $properties,
                                        JSON_PRETTY_PRINT
                                            | JSON_UNESCAPED_UNICODE
                                            | JSON_UNESCAPED_SLASHES
                                    ) }}</pre>
                                </details>
                            @else
                                <span class="text-muted">Tidak ada properti tambahan</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="admin-empty-state">
                                <i class="fa-solid fa-clipboard-list"></i>
                                <strong>Activity log tidak ditemukan</strong>
                                <span>
                                    Belum ada aktivitas atau filter terlalu spesifik.
                                </span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="admin-pagination no-print">
            {{ $logs->links() }}
        </div>
    @endif
</section>
@endsection
