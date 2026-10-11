@extends('layouts.pos')
@section('title', 'Shift Kasir')
@section('body_class', 'pos-shift-page')

@section('content')
    <div class="ga-page-head shift-page-head {{ $openShift ? 'is-open' : 'is-closed' }}">
        <div class="ga-page-title">
            <span class="ga-eyebrow"><i class="fa-solid fa-clock"></i> KONTROL KASIR &amp; REKONSILIASI KAS</span>
            <div class="shift-title-line">
                <h2 class="mb-0">Shift Kasir</h2>
                <span class="shift-header-badge {{ $openShift ? 'is-open' : 'is-closed' }}">
                    <i class="fa-solid {{ $openShift ? 'fa-circle-check' : 'fa-lock' }}"></i>
                    {{ $openShift ? 'Sedang Aktif' : 'Belum Aktif' }}
                </span>
            </div>
            <p class="mb-0">
                {{ $openShift
                    ? 'POS dapat digunakan. Tutup shift setelah kas fisik selesai dihitung.'
                    : 'Buka shift sebelum memulai transaksi penjualan.' }}
            </p>
        </div>

        @can('sales.create')
            <a wire:navigate href="{{ route('sales.pos') }}" class="btn {{ $openShift ? 'btn-success' : 'btn-outline-light' }}">
                <i class="fa-solid fa-cash-register"></i>
                {{ $openShift ? 'Kembali ke POS' : 'Lihat POS' }}
            </a>
        @endcan
    </div>

    <div data-shift-page data-shift-open="{{ $openShift ? 1 : 0 }}"
        data-expected-cash="{{ $openShift ? (float) $openShift->expected_cash : 0 }}">

        @if (!$store)
            <div class="alert alert-danger d-flex align-items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                <div>
                    <strong>Data toko belum tersedia.</strong>
                    <div>Tambahkan data toko terlebih dahulu sebelum membuka shift kasir.</div>
                </div>
            </div>
        @endif

        @if (!$openShift)
            <section class="shift-status-shell is-closed">
                <div class="shift-status-intro">
                    <span class="shift-status-icon"><i class="fa-solid fa-lock"></i></span>
                    <div>
                        <span class="shift-status-kicker">POS TERKUNCI</span>
                        <h3>Belum ada shift kasir aktif</h3>
                        <p>Transaksi, keranjang, dan pembayaran akan otomatis aktif setelah shift dibuka.</p>
                    </div>
                </div>

                <div class="shift-flow">
                    <div class="shift-flow-step is-current">
                        <span>1</span>
                        <div><strong>Masukkan saldo awal</strong><small>Hitung uang tunai di laci kasir.</small></div>
                    </div>
                    <i class="fa-solid fa-chevron-right"></i>
                    <div class="shift-flow-step">
                        <span>2</span>
                        <div><strong>Buka shift</strong><small>Sistem mulai mencatat kas dan penjualan.</small></div>
                    </div>
                    <i class="fa-solid fa-chevron-right"></i>
                    <div class="shift-flow-step">
                        <span>3</span>
                        <div><strong>Mulai transaksi</strong><small>POS dan keranjang akan terbuka.</small></div>
                    </div>
                </div>

                <form method="post" action="{{ route('shifts.open') }}" class="shift-action-form"
                    data-shift-action="open">
                    @csrf
                    <input type="hidden" name="store_id" value="{{ $store?->id }}">

                    <div class="shift-form-grid">
                        <div>
                            <label class="form-label" for="opening-cash">Saldo Awal Kas</label>
                            <div class="input-group shift-cash-input">
                                <span class="input-group-text">Rp</span>
                                <input id="opening-cash" name="opening_cash" type="text" inputmode="numeric"
                                    pattern="[0-9]*" autocomplete="off" value="{{ old('opening_cash', 0) }}"
                                    class="form-control" data-cash-input data-opening-cash required
                                    @disabled(!$store)>
                            </div>
                            <small>Masukkan jumlah uang fisik yang tersedia sebelum transaksi pertama.</small>
                        </div>

                        <div>
                            <label class="form-label" for="opening-notes">Catatan Pembukaan</label>
                            <textarea id="opening-notes" name="notes" class="form-control" rows="3" maxlength="1000"
                                placeholder="Contoh: Pecahan uang sudah dihitung lengkap">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="shift-opening-preview">
                        <div>
                            <small>Saldo awal yang akan dicatat</small>
                            <strong data-opening-preview>Rp 0</strong>
                        </div>
                        <button class="btn btn-success shift-main-action" @disabled(!$store)>
                            <i class="fa-solid fa-door-open"></i>Buka Shift Sekarang
                        </button>
                    </div>
                </form>
            </section>

            <div class="shift-warning-card">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <strong>Mengapa shift wajib dibuka?</strong>
                    <p>Shift menghubungkan penjualan tunai, pengeluaran, refund, dan pembayaran piutang dengan kasir yang
                        bertugas sehingga selisih kas dapat diaudit.</p>
                </div>
            </div>
        @else
            <section class="shift-status-shell is-open">
                <div class="shift-status-intro">
                    <span class="shift-status-icon"><i class="fa-solid fa-circle-check"></i></span>
                    <div>
                        <span class="shift-status-kicker">SHIFT AKTIF</span>
                        <h3>Kasir dapat menerima transaksi</h3>
                        <p>Dibuka {{ $openShift->opened_at->format('d/m/Y H:i') }} ·
                            {{ $openShift->opened_at->diffForHumans() }}.</p>
                    </div>
                    <span class="shift-live-indicator"><i></i> LIVE</span>
                </div>

                <div class="shift-stat-grid">
                    <article>
                        <span><i class="fa-solid fa-wallet"></i></span>
                        <div><small>Saldo Awal</small><strong>Rp
                                {{ number_format($openShift->opening_cash, 0, ',', '.') }}</strong></div>
                    </article>
                    <article>
                        <span><i class="fa-solid fa-cash-register"></i></span>
                        <div><small>Kas Sistem</small><strong>Rp
                                {{ number_format($openShift->expected_cash, 0, ',', '.') }}</strong></div>
                    </article>
                    <article>
                        <span><i class="fa-solid fa-receipt"></i></span>
                        <div><small>Transaksi</small><strong>{{ number_format($openShift->sales_count ?? 0) }}</strong>
                        </div>
                    </article>
                    <article>
                        <span><i class="fa-solid fa-chart-line"></i></span>
                        <div><small>Nilai Penjualan</small><strong>Rp
                                {{ number_format($openShift->sales_sum_total ?? 0, 0, ',', '.') }}</strong></div>
                    </article>
                </div>

                <form method="post" action="{{ route('shifts.close', $openShift) }}"
                    class="shift-action-form shift-close-form" data-shift-action="close">
                    @csrf

                    <div class="shift-close-heading">
                        <div>
                            <span class="shift-status-kicker">REKONSILIASI KAS</span>
                            <h4>Tutup Shift</h4>
                            <p>Masukkan kas fisik. Sistem akan menghitung selisih secara langsung.</p>
                        </div>
                        <div class="shift-expected-chip">
                            <small>Kas seharusnya</small>
                            <strong>Rp {{ number_format($openShift->expected_cash, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    <div class="shift-form-grid">
                        <div>
                            <label class="form-label" for="actual-cash">Kas Fisik Hasil Hitung</label>
                            <div class="input-group shift-cash-input">
                                <span class="input-group-text">Rp</span>
                                <input id="actual-cash" name="actual_cash" type="text" inputmode="numeric"
                                    pattern="[0-9]*" autocomplete="off" value="{{ old('actual_cash') }}"
                                    class="form-control" data-cash-input data-actual-cash placeholder="0" required>
                            </div>
                            <small>Hitung uang di laci kasir sebelum menutup shift.</small>
                        </div>

                        <div>
                            <label class="form-label" for="closing-notes">Catatan Penutupan</label>
                            <textarea id="closing-notes" name="notes" class="form-control" rows="3" maxlength="1000"
                                placeholder="Jelaskan jika terdapat selisih kas">{{ old('notes', $openShift->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="shift-difference-preview is-empty" data-shift-difference>
                        <span class="shift-difference-icon"><i class="fa-solid fa-calculator"></i></span>
                        <div>
                            <small>Selisih kas</small>
                            <strong data-difference-value>Masukkan kas fisik</strong>
                            <span data-difference-caption>Sistem akan membandingkannya dengan kas seharusnya.</span>
                        </div>
                    </div>

                    <div class="shift-close-actions">
                        <div class="shift-close-warning">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Setelah ditutup, transaksi berikutnya memerlukan shift baru.
                        </div>
                        <button class="btn btn-danger shift-main-action">
                            <i class="fa-solid fa-lock"></i>Tutup Shift
                        </button>
                    </div>
                </form>
            </section>
        @endif

        <section class="shift-history-card">
            <div class="shift-history-head">
                <div>
                    <span class="shift-status-kicker">RIWAYAT KASIR</span>
                    <h3>Riwayat Shift Saya</h3>
                    <p>Periksa waktu kerja, transaksi, serta selisih kas pada setiap shift.</p>
                </div>
                <span class="shift-history-count">{{ number_format($shifts->total()) }} shift</span>
            </div>

            <div class="table-responsive">
                <table class="table shift-history-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Transaksi</th>
                            <th>Saldo Awal</th>
                            <th>Kas Sistem</th>
                            <th>Kas Fisik</th>
                            <th>Selisih</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shifts as $shift)
                            @php
                                $difference = $shift->cash_difference !== null ? (float) $shift->cash_difference : null;
                                $differenceClass =
                                    $difference === null
                                        ? 'is-pending'
                                        : ($difference < 0
                                            ? 'is-short'
                                            : ($difference > 0
                                                ? 'is-over'
                                                : 'is-even'));
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $shift->opened_at->format('d/m/Y H:i') }}</strong>
                                    <small>{{ $shift->closed_at?->format('d/m/Y H:i') ?? 'Masih berjalan' }}</small>
                                </td>
                                <td>
                                    <strong>{{ number_format($shift->sales_count ?? 0) }} transaksi</strong>
                                    <small>Rp {{ number_format($shift->sales_sum_total ?? 0, 0, ',', '.') }}</small>
                                </td>
                                <td>Rp {{ number_format($shift->opening_cash, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($shift->expected_cash, 0, ',', '.') }}</td>
                                <td>{{ $shift->actual_cash !== null ? 'Rp ' . number_format($shift->actual_cash, 0, ',', '.') : '-' }}
                                </td>
                                <td>
                                    <span class="shift-difference-badge {{ $differenceClass }}">
                                        @if ($difference === null)
                                            Belum dihitung
                                        @elseif($difference > 0)
                                            +Rp {{ number_format($difference, 0, ',', '.') }}
                                        @else
                                            {{ $difference < 0 ? '-Rp ' . number_format(abs($difference), 0, ',', '.') : 'Rp 0' }}
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="shift-table-status {{ $shift->status === 'open' ? 'is-open' : 'is-closed' }}">
                                        <i
                                            class="fa-solid {{ $shift->status === 'open' ? 'fa-circle' : 'fa-circle-check' }}"></i>
                                        {{ $shift->status === 'open' ? 'Aktif' : 'Selesai' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="shift-empty-state">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                        <strong>Belum ada riwayat shift</strong>
                                        <span>Riwayat akan muncul setelah shift pertama dibuka.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($shifts->hasPages())
                <div class="shift-history-pagination">{{ $shifts->links() }}</div>
            @endif
        </section>
    </div>
@endsection
