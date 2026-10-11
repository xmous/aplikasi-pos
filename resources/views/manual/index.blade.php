@extends('layouts.pos')

@section('title', 'Panduan Pengguna')
@section('body_class', 'manual-book-page')

@section('content')
<section
    class="manual-hero"
    data-manual-guide
    data-manual-page-head
>
    <div class="manual-hero-copy">
        <span class="ga-eyebrow">
            <i class="fa-solid fa-book-open"></i>
            PANDUAN BERDASARKAN HAK AKSES
        </span>

        <h2>{{ $manualTitle }}</h2>

        <p>
            Modul panduan otomatis disaring berdasarkan permission akun
            <strong>{{ auth()->user()->name }}</strong>. Anda hanya melihat
            petunjuk untuk menu yang memang dapat diakses.
        </p>

        <div class="manual-hero-meta">
            <span>
                <i class="fa-solid fa-user-lock"></i>
                {{ auth()->user()->getRoleNames()->join(', ') ?: 'Pengguna' }}
            </span>
            <span>
                <i class="fa-solid fa-key"></i>
                {{ $permissionCount }} permission
            </span>
            <span>
                <i class="fa-solid fa-layer-group"></i>
                {{ $sections->count() }} modul panduan
            </span>
        </div>
    </div>

    <div class="manual-hero-actions no-print">
        <button
            type="button"
            class="btn btn-light manual-print-primary"
            data-manual-print
        >
            <i class="fa-solid fa-print"></i>
            Cetak Panduan Saya
        </button>

        @canany(['settings.manage', 'roles.manage'])
            <div class="manual-download-actions">
                <a
                    class="btn btn-outline-light"
                    href="{{ route('manual.download', 'pdf') }}"
                >
                    <i class="fa-solid fa-file-pdf"></i>
                    PDF Lengkap
                </a>

                <a
                    class="btn btn-outline-light"
                    href="{{ route('manual.download', 'docx') }}"
                >
                    <i class="fa-solid fa-file-word"></i>
                    DOCX Editable
                </a>
            </div>
        @endcanany
    </div>
</section>

<section class="manual-toolbar no-print">
    <div class="manual-toolbar-top">
        <div class="manual-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
                type="search"
                placeholder="Cari modul, langkah, istilah, atau masalah..."
                autocomplete="off"
                data-manual-search
            >
            <kbd>/</kbd>
        </div>

        <div class="manual-toolbar-actions">
            <button type="button" data-manual-expand>
                <i class="fa-solid fa-angles-down"></i>
                Buka Semua
            </button>
            <button type="button" data-manual-collapse>
                <i class="fa-solid fa-angles-up"></i>
                Tutup Semua
            </button>
        </div>
    </div>

    <div class="manual-category-row">
        <span class="manual-category-label">
            <i class="fa-solid fa-filter"></i>
            Filter kategori
        </span>

        <div class="manual-category-filter" data-manual-categories>
            <button
                type="button"
                class="is-active"
                data-manual-category="all"
            >
                Semua
            </button>

            @foreach($categories as $category)
                <button
                    type="button"
                    data-manual-category="{{ $category }}"
                >
                    {{ $category }}
                </button>
            @endforeach
        </div>
    </div>
</section>

<div class="manual-status">
    <span>
        <i class="fa-solid fa-circle-info"></i>
        Panduan yang tampil tidak memberikan akses baru. Akses tetap
        diperiksa oleh permission pada route dan tindakan aplikasi.
    </span>
    <b data-manual-result-count>{{ $sections->count() }} modul</b>
</div>

<div class="manual-grid" data-manual-list>
    @foreach($sections as $section)
        <details
            id="manual-{{ $section['slug'] }}"
            class="manual-card manual-tone-{{ $section['color'] ?? 'blue' }}"
            data-manual-card
            data-category="{{ $section['category'] }}"
            data-search="{{ $section['search_text'] }}"
            @if($loop->first) open @endif
        >
            <summary>
                <span class="manual-card-icon">
                    <i class="fa-solid {{ $section['icon'] }}"></i>
                </span>
                <span class="manual-card-title">
                    <small>
                        {{ $section['category'] }}

                        @if($section['badge'] ?? null)
                            <b class="manual-card-badge">
                                {{ $section['badge'] }}
                            </b>
                        @endif
                    </small>
                    <strong>{{ $section['title'] }}</strong>
                    <em>{{ $section['summary'] }}</em>
                </span>
                <span class="manual-card-access">
                    @if(($section['roles'] ?? []) !== [])
                        Role:
                        {{ collect($section['roles'])->join(' / ') }}
                    @elseif(($section['permissions'] ?? []) === [])
                        Semua pengguna
                    @else
                        {{ collect($section['permissions'])->join(' / ') }}
                    @endif
                </span>
                <i class="fa-solid fa-chevron-down manual-chevron"></i>
            </summary>

            <div class="manual-card-body">

                @if(($section['journey'] ?? []) !== [])
                    <nav
                        class="manual-journey-tabs no-print"
                        aria-label="Tahapan panduan {{ $section['title'] }}"
                    >
                        <span class="manual-journey-title">
                            <i class="fa-solid fa-route"></i>
                            Alur cepat
                        </span>

                        <div class="manual-journey-track">
                            @foreach($section['journey'] as $journey)
                                <a
                                    href="#{{ $journey['target'] }}"
                                    class="manual-journey-tab is-{{ $journey['tone'] ?? 'blue' }}"
                                    data-manual-step-anchor="{{ $journey['target'] }}"
                                >
                                    <i class="fa-solid {{ $journey['icon'] }}"></i>
                                    <span>
                                        <strong>{{ $journey['label'] }}</strong>
                                        <small>{{ $journey['range'] }}</small>
                                    </span>
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            @endforeach
                        </div>
                    </nav>
                @endif

                @if(($section['prerequisites'] ?? []) !== [])
                    <section>
                        <h3>
                            <i class="fa-solid fa-list-check"></i>
                            Sebelum Memulai
                        </h3>
                        @include(
                            'manual.partials.action-list',
                            [
                                'items' => $section['prerequisites'],
                                'ordered' => false,
                                'group' => 'prerequisites',
                            ]
                        )
                    </section>
                @endif

                <section>
                    <h3>
                        <i class="fa-solid fa-shoe-prints"></i>
                        Langkah Penggunaan
                    </h3>
                    @include(
                        'manual.partials.action-list',
                        [
                            'items' => $section['steps'],
                            'ordered' => true,
                            'group' => 'steps',
                        ]
                    )
                </section>

                @if(($section['tips'] ?? []) !== [])
                    <section class="manual-note is-tip">
                        <h3>
                            <i class="fa-solid fa-lightbulb"></i>
                            Praktik yang Disarankan
                        </h3>
                        @include(
                            'manual.partials.action-list',
                            [
                                'items' => $section['tips'],
                                'ordered' => false,
                                'group' => 'tips',
                            ]
                        )
                    </section>
                @endif

                @if(($section['warnings'] ?? []) !== [])
                    <section class="manual-note is-warning">
                        <h3>
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Perhatian
                        </h3>
                        @include(
                            'manual.partials.action-list',
                            [
                                'items' => $section['warnings'],
                                'ordered' => false,
                                'group' => 'warnings',
                            ]
                        )
                    </section>
                @endif

            </div>
        </details>
    @endforeach
</div>

<div class="manual-empty d-none" data-manual-empty>
    <span><i class="fa-solid fa-book-open-reader"></i></span>
    <strong>Panduan tidak ditemukan</strong>
    <p>Coba gunakan kata lain atau pilih kategori Semua.</p>
</div>
@endsection
