import 'bootstrap';
import Swal from 'sweetalert2';
import $ from 'jquery';
import select2 from 'select2';

window.Swal = Swal;
window.$ = window.jQuery = $;
select2(window, $);

const menuIcons = {
    'Dashboard':'fa-chart-pie','POS Penjualan':'fa-cash-register','Shift Kasir':'fa-clock',
    'Barang & Konversi':'fa-boxes-stacked','Kategori':'fa-tags','Satuan':'fa-scale-balanced',
    'Supplier':'fa-truck-field','Pelanggan':'fa-users','Gudang':'fa-warehouse','Pembelian':'fa-cart-shopping',
    'Mutasi Stok':'fa-arrow-right-arrow-left','Batch & Kedaluwarsa':'fa-calendar-xmark','Penyesuaian Stok':'fa-sliders',
    'Stok Opname':'fa-clipboard-check','Retur Pembelian':'fa-rotate-left','Retur Penjualan':'fa-arrow-rotate-left',
    'Pengeluaran':'fa-wallet','Promo':'fa-percent','Ringkasan':'fa-chart-simple','Ringkasan Bisnis':'fa-chart-simple','Penjualan':'fa-chart-line','Stok':'fa-cubes',
    'Laba Rugi':'fa-coins','Utang & Piutang':'fa-file-invoice-dollar','Operasional':'fa-gears',
    'Pengguna':'fa-user-group','Role & Permission':'fa-user-lock','Pengaturan POS':'fa-gear',
    'Audit Log':'fa-clipboard-list','Activity Log':'fa-clipboard-list','Panduan Pengguna':'fa-book-open','Profil, Password & 2FA':'fa-shield-halved',
        'Profil & Password':'fa-shield-halved'
};

function bindPosNavigation() {
    const sidebar = document.querySelector('[data-pos-sidebar]');
    const backdrop = document.querySelector('[data-pos-backdrop]');
    const toggle = document.querySelector('[data-pos-toggle]');
    const close = () => {
        sidebar?.classList.remove('open');
        backdrop?.classList.remove('open');
    };

    if (toggle && !toggle.dataset.bound) {
        toggle.dataset.bound = '1';
        toggle.addEventListener('click', () => {
            if (window.innerWidth >= 992) {
                document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('ga-sidebar-collapsed', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
                return;
            }
            sidebar?.classList.toggle('open');
            backdrop?.classList.toggle('open');
        });
    }
    if (backdrop && !backdrop.dataset.bound) {
        backdrop.dataset.bound = '1';
        backdrop.addEventListener('click', close);
    }
    document.querySelectorAll('[data-pos-sidebar] a:not([data-bound])').forEach(link => {
        link.dataset.bound = '1';
        const label = link.textContent.trim();
        if (!link.querySelector('i')) link.insertAdjacentHTML('afterbegin', `<i class="fa-solid ${menuIcons[label] || 'fa-circle'}"></i>`);
        if (new URL(link.href).pathname === window.location.pathname) link.classList.add('active');
        link.addEventListener('click', close);
    });
    if (window.innerWidth >= 992 && localStorage.getItem('ga-sidebar-collapsed') === '1') document.body.classList.add('sidebar-collapsed');
}

function bindUserMenu() {
    const toggle = document.querySelector('[data-pos-user-toggle]');
    const menu = document.querySelector('[data-pos-user-menu]');
    if (!toggle || !menu || toggle.dataset.userBound === '1') return;
    toggle.dataset.userBound = '1';
    const close = () => {
        menu.classList.remove('show');
        toggle.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    };
    toggle.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        const open = !menu.classList.contains('show');
        close();
        if (open) {
            menu.classList.add('show');
            toggle.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
        }
    });
    menu.addEventListener('click', event => event.stopPropagation());
    document.addEventListener('click', close);
    document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
}

function bindLivewireConfirms() {
    document.querySelectorAll('[data-livewire-confirm]:not([data-confirm-bound])').forEach(button => {
        button.dataset.confirmBound = '1';
        button.addEventListener('click', async () => {
            const componentId = button.closest('[wire\\:id]')?.getAttribute('wire:id');
            const action = button.dataset.livewireAction;
            const id = Number(button.dataset.livewireId);
            if (!componentId || !action || !window.Livewire) return;
            const result = await Swal.fire({
                icon: 'warning', title: 'Konfirmasi hapus', text: button.dataset.livewireConfirm,
                showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal',
                confirmButtonColor: '#dc4149', reverseButtons: true, focusCancel: true,
            });
            if (!result.isConfirmed) return;
            button.disabled = true;
            try { await window.Livewire.find(componentId)?.call(action, id); }
            finally { if (document.body.contains(button)) button.disabled = false; }
        });
    });
}

function enhanceSelect2(root = document) {
    $(root).find('select.form-select:not([data-no-select2])').addBack('select.form-select:not([data-no-select2])').each(function () {
        const select = $(this);
        if (select.hasClass('select2-hidden-accessible')) return;
        select.select2({
            width: '100%',
            dropdownParent: $(document.body),
            placeholder: this.dataset.placeholder || undefined,
        });
        select.on('change.select2-ga', () => this.dispatchEvent(new Event('input', { bubbles: true })));
    });
}

function buttonIcon(label, button) {
    const text = label.toLowerCase();
    if (button.matches('[data-print-page]') || text.includes('cetak')) return 'fa-print';
    if (text.includes('excel')) return 'fa-file-excel'; if (text.includes('pdf')) return 'fa-file-pdf';
    if (text.includes('hapus')) return 'fa-trash'; if (text.includes('retur')) return 'fa-rotate-left';
    if (text.includes('simpan') || text.includes('bayar')) return 'fa-floppy-disk';
    if (text.includes('tambah') || text.includes('buat') || text.includes('buka')) return 'fa-plus';
    if (text.includes('edit') || text.includes('ubah')) return 'fa-pen-to-square';
    if (text.includes('kembali') || text.includes('batal')) return 'fa-arrow-left';
    if (text.includes('lihat') || text.includes('detail')) return 'fa-eye';
    if (text.includes('cari') || text.includes('filter')) return 'fa-magnifying-glass';
    if (text.includes('kelola')) return 'fa-sliders'; if (text.includes('kirim')) return 'fa-paper-plane';
    return button.classList.contains('btn-danger') ? 'fa-triangle-exclamation' : 'fa-arrow-right';
}

function enhanceButtons(root = document) {
    const buttons = [
        ...(root.matches?.('.btn:not(.btn-close):not([data-icon-ready])') ? [root] : []),
        ...(root.querySelectorAll?.('.btn:not(.btn-close):not([data-icon-ready])') || []),
    ];
    buttons.forEach(button => {
        button.dataset.iconReady = '1';
        if (!button.querySelector('i')) button.insertAdjacentHTML('afterbegin', `<i class="fa-solid ${buttonIcon(button.textContent.trim(), button)}"></i>`);
    });
    root.querySelectorAll?.('.btn-close:not([aria-label])').forEach(button => button.setAttribute('aria-label', 'Tutup'));
}

function enhancePageHeader(root = document) {
    const page = root.matches?.('.pos-page') ? root : (root.closest?.('.pos-page') || root.querySelector?.('.pos-page'));
    if (!page) return;
    const heading = page.querySelector('h2');
    if (
        !heading
        || heading.closest(
            '.card,.pos-modal-dialog,.ga-page-head,[data-manual-page-head]'
        )
    ) {
        return;
    }
    let container = heading.parentElement;
    if (container.parentElement?.classList.contains('d-flex')) container = container.parentElement;
    const containsPageContent = container === page || container.hasAttribute('wire:id') || container.querySelector('.card,table,form,.ga-notice');
    if (containsPageContent) {
        const wrapper = document.createElement('div');
        wrapper.className = 'ga-page-head';
        heading.before(wrapper);
        wrapper.append(heading);
        container = wrapper;
    }
    container.classList.add('ga-page-head');
    if (heading.parentElement === container) {
        const titleGroup = document.createElement('div');
        titleGroup.className = 'ga-page-title';
        heading.before(titleGroup);
        titleGroup.append(heading);
    } else {
        heading.parentElement.classList.add('ga-page-title');
    }
    const brandKicker = document.body.dataset.brandKicker || 'GENERAL AFFAIR · SIGAP & HANDAL';
    heading.insertAdjacentHTML('beforebegin', `<span class="ga-eyebrow"><i class="fa-solid fa-bolt"></i> ${escapeHtml(brandKicker)}</span>`);
}

function enhanceUi(root = document) {
    enhanceSelect2(root); enhanceButtons(root); enhancePageHeader(root);
    root.querySelectorAll?.('.pos-modal-dialog:not([data-modal-ready])').forEach(modal => {
        modal.dataset.modalReady = '1'; modal.classList.add('ga-animate-in');
        const title = modal.querySelector('h5');
        if (title && !title.querySelector('i')) title.insertAdjacentHTML('afterbegin', '<i class="fa-solid fa-layer-group me-2 text-warning"></i>');
    });
    bindLivewireConfirms();
    bindMicroInteractions();
}

function bindFlashMessages() {
    document.querySelectorAll('[data-flash-message]:not([data-bound])').forEach(box => {
        box.dataset.bound = '1';
        let errors = [];
        try { errors = JSON.parse(box.dataset.error || '[]'); } catch (_) { errors = []; }
        if (errors.length) {
            Swal.fire({ icon: 'error', title: 'Data belum dapat diproses', html: errors.map(item => `<div>${escapeHtml(item)}</div>`).join(''), confirmButtonText: 'Periksa kembali' });
        } else if (box.dataset.warning) {
            Swal.fire({ icon: 'warning', title: 'Perhatian', text: box.dataset.warning, confirmButtonText: 'Mengerti' });
        } else if (box.dataset.success) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: box.dataset.success, showConfirmButton: false, timer: 2800, timerProgressBar: true });
        }
    });
}

function bindAuthControls() {
    document.querySelectorAll('[data-password-toggle]:not([data-bound])').forEach(button => {
        button.dataset.bound = '1';
        button.addEventListener('click', () => {
            const input = button.closest('.input-group,.auth-input')?.querySelector('[data-password-input]');
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            const icon = button.querySelector('i');
            if (icon) icon.className = input.type === 'password' ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
            else button.textContent = input.type === 'password' ? 'Lihat' : 'Sembunyikan';
        });
    });

    document.querySelectorAll('[data-two-factor-switch]:not([data-bound])').forEach(button => {
        button.dataset.bound = '1';
        button.addEventListener('click', () => {
            const code = document.querySelector('[data-auth-code]');
            const recovery = document.querySelector('[data-recovery-code]');
            const usingRecovery = !recovery?.classList.contains('d-none');
            code?.classList.toggle('d-none', !usingRecovery);
            recovery?.classList.toggle('d-none', usingRecovery);
            button.textContent = usingRecovery ? 'Gunakan recovery code' : 'Gunakan kode authenticator';
            code?.querySelector('input')?.toggleAttribute('required', usingRecovery);
            recovery?.querySelector('input')?.toggleAttribute('required', !usingRecovery);
        });
    });
}

function bindBrandSettings() {
    const form = document.querySelector('[data-brand-settings]');
    if (!form || form.dataset.bound === '1') return;
    form.dataset.bound = '1';

    const themes = decodeBase64Json(form.dataset.themes, {});
    const defaultLogo = form.dataset.defaultLogo || '';
    const logoInput = form.querySelector('[data-brand-logo-input]');
    const removeLogo = form.querySelector('[data-brand-remove-logo]');
    const editorLogo = form.querySelector('[data-brand-logo-editor]');
    const previewLogo = form.querySelector('[data-brand-preview-logo]');
    const nameInput = form.querySelector('[data-brand-name-input]');
    const taglineInput = form.querySelector('[data-brand-tagline-input]');
    const kickerInput = form.querySelector('[data-brand-kicker-input]');
    const previewName = form.querySelector('[data-brand-preview-name]');
    const previewTagline = form.querySelector('[data-brand-preview-tagline]');
    const previewKicker = form.querySelector('[data-brand-preview-kicker]');
    const originalLogo = editorLogo?.src || previewLogo?.src || defaultLogo;

    function setLogo(src) {
        if (editorLogo) editorLogo.src = src;
        if (previewLogo) previewLogo.src = src;
    }

    function refreshText() {
        if (previewName) {
            previewName.textContent = nameInput?.value.trim() || 'Nama Toko';
        }
        if (previewTagline) {
            previewTagline.textContent = taglineInput?.value.trim() || 'Slogan';
        }
        if (previewKicker) {
            previewKicker.innerHTML = `<i class="fa-solid fa-bolt"></i> ${escapeHtml(kickerInput?.value.trim() || 'IDENTITAS APLIKASI')}`;
        }
    }

    function applyTheme(key) {
        const theme = themes[key];
        if (!theme) return;

        const variables = {
            '--ga-navy': theme.navy,
            '--ga-blue': theme.blue,
            '--ga-blue-2': theme.blue_2,
            '--ga-gold': theme.accent,
            '--ga-canvas': theme.canvas,
            '--ga-surface': theme.surface,
            '--ga-text': theme.text,
            '--ga-muted': theme.muted,
            '--ga-border': theme.border,
            '--ga-sidebar-end': theme.sidebar_end,
            '--ga-table-head': theme.table_head,
            '--ga-table-head-text': theme.table_head_text,
        };

        Object.entries(variables).forEach(([name, value]) => {
            document.body.style.setProperty(name, value);
        });

        form.querySelectorAll('.settings-theme-option').forEach(option => {
            option.classList.toggle(
                'is-selected',
                option.querySelector('[data-theme-option]')?.value === key
            );
        });

        const themeColor = document.querySelector(
            'meta[name="theme-color"]'
        );
        if (themeColor) themeColor.content = theme.navy;
    }

    function applyFontScale(key) {
        const root = document.documentElement;

        ['small', 'medium', 'large'].forEach(scale => {
            root.classList.toggle(
                `ga-font-${scale}`,
                scale === key
            );
        });

        form.querySelectorAll('.settings-font-option').forEach(option => {
            option.classList.toggle(
                'is-selected',
                option.querySelector('[data-font-scale-option]')?.value
                    === key
            );
        });
    }

    function refreshSystemAppearance() {
        const root = document.documentElement;
        const isSystem = root.classList.contains(
            'ga-appearance-system'
        );
        const isDark = window.matchMedia(
            '(prefers-color-scheme: dark)'
        ).matches;

        root.classList.toggle(
            'ga-system-dark',
            isSystem && isDark
        );
    }

    function applyAppearance(key) {
        const root = document.documentElement;

        ['light', 'dark', 'system'].forEach(mode => {
            root.classList.toggle(
                `ga-appearance-${mode}`,
                mode === key
            );
        });

        refreshSystemAppearance();

        form
            .querySelectorAll('.settings-appearance-option')
            .forEach(option => {
                option.classList.toggle(
                    'is-selected',
                    option.querySelector('[data-appearance-option]')?.value
                        === key
                );
            });
    }

    function applyFinish(key) {
        const root = document.documentElement;
        const finishes = ['normal', 'matte', 'glossy'];

        finishes.forEach(finish => {
            root.classList.toggle(
                `ga-finish-${finish}`,
                finish === key
            );
        });

        form.querySelectorAll('.settings-finish-option')
            .forEach(option => {
                option.classList.toggle(
                    'is-selected',
                    option.querySelector('[data-finish-option]')?.value
                        === key
                );
            });
    }

    [nameInput, taglineInput, kickerInput].forEach(input => {
        input?.addEventListener('input', refreshText);
    });

    logoInput?.addEventListener('change', () => {
        const file = logoInput.files?.[0];
        if (!file) {
            setLogo(removeLogo?.checked ? defaultLogo : originalLogo);
            return;
        }

        if (!file.type.startsWith('image/')) {
            logoInput.value = '';
            Swal.fire({
                icon: 'warning',
                title: 'File logo tidak valid',
                text: 'Pilih gambar PNG, JPG, JPEG, atau WEBP.',
            });
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            logoInput.value = '';
            Swal.fire({
                icon: 'warning',
                title: 'Logo terlalu besar',
                text: 'Ukuran logo maksimal 2 MB.',
            });
            return;
        }

        const reader = new FileReader();
        reader.addEventListener('load', () => {
            setLogo(String(reader.result || originalLogo));
            if (removeLogo) removeLogo.checked = false;
        });
        reader.readAsDataURL(file);
    });

    removeLogo?.addEventListener('change', () => {
        if (removeLogo.checked) {
            if (logoInput) logoInput.value = '';
            setLogo(defaultLogo);
        } else {
            setLogo(originalLogo);
        }
    });

    form.querySelectorAll('[data-theme-option]').forEach(input => {
        input.addEventListener('change', () => {
            if (input.checked) applyTheme(input.value);
        });
    });

    form.querySelectorAll('[data-font-scale-option]').forEach(input => {
        input.addEventListener('change', () => {
            if (input.checked) applyFontScale(input.value);
        });
    });

    form.querySelectorAll('[data-appearance-option]').forEach(input => {
        input.addEventListener('change', () => {
            if (input.checked) applyAppearance(input.value);
        });
    });

    form.querySelectorAll('[data-finish-option]').forEach(input => {
        input.addEventListener('change', () => {
            if (input.checked) applyFinish(input.value);
        });
    });

    const selectedTheme = form.querySelector('[data-theme-option]:checked');
    const selectedFont = form.querySelector(
        '[data-font-scale-option]:checked'
    );
    const selectedAppearance = form.querySelector(
        '[data-appearance-option]:checked'
    );
    const selectedFinish = form.querySelector(
        '[data-finish-option]:checked'
    );

    if (selectedTheme) applyTheme(selectedTheme.value);
    if (selectedFont) applyFontScale(selectedFont.value);
    if (selectedAppearance) {
        applyAppearance(selectedAppearance.value);
    }
    if (selectedFinish) {
        applyFinish(selectedFinish.value);
    }

    refreshText();
}


function bindThermalReceipt() {
    const receipt = document.querySelector('[data-thermal-receipt]');

    if (!receipt || receipt.dataset.thermalBound === '1') {
        return;
    }

    receipt.dataset.thermalBound = '1';

    const paper = receipt.querySelector('[data-thermal-paper]');
    const sizeButtons = [
        ...document.querySelectorAll('[data-thermal-size]'),
    ];
    const printButtons = [
        ...document.querySelectorAll('[data-thermal-print]'),
    ];
    const sizeLabels = [
        ...document.querySelectorAll('[data-thermal-current-size]'),
    ];
    const alignmentButtons = [
        ...document.querySelectorAll('[data-thermal-alignment]'),
    ];
    const alignmentLabels = [
        ...document.querySelectorAll(
            '[data-thermal-current-alignment]'
        ),
    ];
    const backButton = document.querySelector('[data-thermal-back]');
    const primaryPrint = document.querySelector(
        '[data-thermal-autofocus]'
    ) || printButtons[0];

    let selectedSize = Number(receipt.dataset.defaultSize) === 80
        ? 80
        : 58;

    let selectedAlignment = ['left', 'center', 'right'].includes(
        receipt.dataset.defaultAlignment
    )
        ? receipt.dataset.defaultAlignment
        : 'center';

    let printPending = false;
    let printQueued = false;
    let printSequence = 0;
    let enterIsDown = false;
    let keyboardPrintArmed = false;
    let keyboardArmTimer = null;

    let transitionGuardUntil = 0;

    try {
        transitionGuardUntil = Number(
            sessionStorage.getItem(
                'pos:receipt-enter-guard-until'
            ) || 0
        );
        sessionStorage.removeItem(
            'pos:receipt-enter-guard-until'
        );
    } catch (error) {
        transitionGuardUntil = 0;
    }

    const keyboardReadyAt = Math.max(
        Date.now() + 650,
        transitionGuardUntil
    );

    const wait = milliseconds => new Promise(resolve => {
        window.setTimeout(resolve, milliseconds);
    });

    const nextFrame = () => new Promise(resolve => {
        requestAnimationFrame(resolve);
    });

    const setPrintButtonsDisabled = disabled => {
        printButtons.forEach(button => {
            button.disabled = disabled;
            button.setAttribute(
                'aria-busy',
                disabled ? 'true' : 'false'
            );
        });
    };

    const scheduleKeyboardArm = delay => {
        window.clearTimeout(keyboardArmTimer);
        keyboardPrintArmed = false;

        keyboardArmTimer = window.setTimeout(() => {
            if (!enterIsDown && Date.now() >= keyboardReadyAt) {
                keyboardPrintArmed = true;
            }
        }, Math.max(0, delay));
    };

    const focusPrimaryPrint = (scroll = false) => {
        if (!primaryPrint) return;

        if (scroll) {
            primaryPrint.scrollIntoView({
                block: 'center',
                inline: 'nearest',
            });
        }

        window.setTimeout(() => {
            if (!document.body.contains(primaryPrint)) return;
            primaryPrint.focus({ preventScroll: true });
        }, scroll ? 100 : 0);
    };

    const focusBackButton = () => {
        if (!backButton) return;

        backButton.scrollIntoView({
            block: 'center',
            inline: 'nearest',
        });

        window.setTimeout(() => {
            if (!document.body.contains(backButton)) return;
            backButton.focus({ preventScroll: true });
        }, 120);
    };

    const setSize = requestedSize => {
        selectedSize = Number(requestedSize) === 80 ? 80 : 58;

        receipt.dataset.selectedSize = String(selectedSize);

        paper?.classList.toggle('is-58mm', selectedSize === 58);
        paper?.classList.toggle('is-80mm', selectedSize === 80);

        sizeButtons.forEach(button => {
            const active = Number(button.dataset.thermalSize)
                === selectedSize;

            button.classList.toggle('is-active', active);
            button.classList.toggle('is-selected', active);
            button.setAttribute(
                'aria-pressed',
                active ? 'true' : 'false'
            );
        });

        sizeLabels.forEach(label => {
            label.textContent = `${selectedSize} mm`;
        });

        printButtons.forEach(button => {
            const textNode = [...button.childNodes].find(node =>
                node.nodeType === Node.TEXT_NODE
                && node.textContent.trim()
            );

            if (textNode) {
                textNode.textContent =
                    ` Cetak Struk ${selectedSize} mm`;
            } else {
                button.append(
                    document.createTextNode(
                        ` Cetak Struk ${selectedSize} mm`
                    )
                );
            }
        });
    };

    const setAlignment = requestedAlignment => {
        selectedAlignment = ['left', 'center', 'right'].includes(
            requestedAlignment
        )
            ? requestedAlignment
            : 'center';

        receipt.dataset.selectedAlignment = selectedAlignment;
        paper?.setAttribute(
            'data-receipt-alignment',
            selectedAlignment
        );

        ['left', 'center', 'right'].forEach(alignment => {
            paper?.classList.toggle(
                `is-align-${alignment}`,
                alignment === selectedAlignment
            );
            document.body.classList.toggle(
                `thermal-receipt-align-${alignment}`,
                alignment === selectedAlignment
            );
        });

        alignmentButtons.forEach(button => {
            const active = button.dataset.thermalAlignment
                === selectedAlignment;

            button.classList.toggle('is-selected', active);
            button.setAttribute(
                'aria-pressed',
                active ? 'true' : 'false'
            );
        });

        const activeButton = alignmentButtons.find(button =>
            button.dataset.thermalAlignment === selectedAlignment
        );
        const label = activeButton?.dataset.thermalAlignmentLabel
            || 'Posisi tengah';

        alignmentLabels.forEach(element => {
            element.textContent = label;
        });
    };

    const applyPrintClasses = () => {
        document.body.classList.remove(
            'thermal-58mm',
            'thermal-80mm',
            'thermal-receipt-align-left',
            'thermal-receipt-align-center',
            'thermal-receipt-align-right'
        );

        document.body.classList.add(
            'thermal-printing',
            `thermal-${selectedSize}mm`,
            `thermal-receipt-align-${selectedAlignment}`
        );

        setSize(selectedSize);
        setAlignment(selectedAlignment);
    };

    const cleanupPrintState = () => {
        document.body.classList.remove(
            'thermal-printing',
            'thermal-58mm',
            'thermal-80mm'
        );
    };

    const finishPrintFlow = () => {
        if (!printPending && !printQueued) return;

        printPending = false;
        printQueued = false;
        printSequence += 1;
        setPrintButtonsDisabled(false);
        cleanupPrintState();

        /*
         * Enter dari dialog printer harus dilepas dahulu sebelum
         * tombol Kembali ke Kasir dapat diaktifkan.
         */
        keyboardPrintArmed = false;
        scheduleKeyboardArm(320);
        focusBackButton();
    };

    const preparePrintLayout = async sequence => {
        applyPrintClasses();

        if (document.fonts?.ready) {
            try {
                await document.fonts.ready;
            } catch (error) {
                /* Font fallback tetap boleh dicetak. */
            }
        }

        await nextFrame();
        await nextFrame();
        await wait(180);

        /* Paksa browser menghitung ulang ukuran @page dan kertas. */
        void document.body.offsetHeight;
        void paper?.offsetWidth;

        return sequence === printSequence
            && document.body.contains(receipt);
    };

    const printReceipt = async () => {
        if (printPending || printQueued) return;

        const sequence = ++printSequence;
        printQueued = true;
        keyboardPrintArmed = false;
        setPrintButtonsDisabled(true);

        const layoutReady = await preparePrintLayout(sequence);

        if (!layoutReady) {
            printQueued = false;
            setPrintButtonsDisabled(false);
            return;
        }

        printQueued = false;
        printPending = true;
        window.print();

        window.setTimeout(() => {
            if (
                printPending
                && !window.matchMedia('print').matches
            ) {
                finishPrintFlow();
            }
        }, 550);
    };

    const handleReceiptKeyDown = event => {
        if (!document.body.contains(receipt)) {
            document.removeEventListener(
                'keydown',
                handleReceiptKeyDown,
                true
            );
            document.removeEventListener(
                'keyup',
                handleReceiptKeyUp,
                true
            );
            return;
        }

        if (event.key !== 'Enter') return;

        enterIsDown = true;

        const printFocused = printButtons.some(button =>
            button === document.activeElement
        );

        if (
            event.repeat
            || Date.now() < keyboardReadyAt
            || !keyboardPrintArmed
            || printPending
            || printQueued
        ) {
            if (printFocused || event.repeat) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }
    };

    const handleReceiptKeyUp = event => {
        if (event.key !== 'Enter') return;

        enterIsDown = false;

        const remaining = Math.max(
            0,
            keyboardReadyAt - Date.now()
        );

        scheduleKeyboardArm(remaining);
    };

    document.addEventListener(
        'keydown',
        handleReceiptKeyDown,
        true
    );
    document.addEventListener(
        'keyup',
        handleReceiptKeyUp,
        true
    );

    sizeButtons.forEach(button => {
        if (button.dataset.thermalSizeBound === '1') {
            return;
        }

        button.dataset.thermalSizeBound = '1';

        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();

            if (printPending || printQueued) return;

            setSize(button.dataset.thermalSize);
            focusPrimaryPrint();
        });
    });

    alignmentButtons.forEach(button => {
        if (button.dataset.thermalAlignmentBound === '1') {
            return;
        }

        button.dataset.thermalAlignmentBound = '1';

        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();

            if (printPending || printQueued) return;

            setAlignment(button.dataset.thermalAlignment);
            focusPrimaryPrint();
        });
    });

    printButtons.forEach(button => {
        if (button.dataset.thermalPrintBound === '1') {
            return;
        }

        button.dataset.thermalPrintBound = '1';

        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();

            const keyboardClick = event.detail === 0;

            if (
                keyboardClick
                && (
                    !keyboardPrintArmed
                    || Date.now() < keyboardReadyAt
                )
            ) {
                return;
            }

            printReceipt();
        });
    });

    window.addEventListener('beforeprint', applyPrintClasses);
    window.addEventListener('afterprint', finishPrintFlow);

    const printMedia = window.matchMedia('print');
    const handlePrintMedia = event => {
        if (!event.matches) {
            finishPrintFlow();
        }
    };

    if (typeof printMedia.addEventListener === 'function') {
        printMedia.addEventListener('change', handlePrintMedia);
    } else if (typeof printMedia.addListener === 'function') {
        printMedia.addListener(handlePrintMedia);
    }

    setSize(selectedSize);
    setAlignment(selectedAlignment);

    scheduleKeyboardArm(
        Math.max(0, keyboardReadyAt - Date.now())
    );

    /*
     * Fokus tetap otomatis ke tombol cetak, tetapi setelah layout
     * ukuran 58/80 mm dan alignment selesai diterapkan.
     */
    window.setTimeout(() => {
        focusPrimaryPrint(true);
    }, 320);
}


function bindPageHashAnchor() {
    const targetId = window.location.hash.replace(/^#/, '');

    if (!targetId) {
        return;
    }

    const revealAndScroll = () => {
        const target = document.getElementById(targetId);

        if (!target) {
            return false;
        }

        target.classList.add('ga-anchor-target');

        window.setTimeout(() => {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        }, 80);

        window.setTimeout(() => {
            target.classList.remove('ga-anchor-target');
        }, 1900);

        return true;
    };

    if (!revealAndScroll()) {
        window.setTimeout(revealAndScroll, 350);
    }
}



/* Restored lifecycle binders lost during cumulative receipt patches. */

function bindLoyaltySettings() {
    const card = document.querySelector('[data-loyalty-settings]');

    if (!card || card.dataset.loyaltyBound === '1') {
        return;
    }

    card.dataset.loyaltyBound = '1';

    const enabled = card.querySelector('[data-loyalty-enabled]');
    const earn = card.querySelector('[data-loyalty-earn]');
    const value = card.querySelector('[data-loyalty-value]');
    const percent = card.querySelector('[data-loyalty-percent]');
    const earnPreview = card.querySelector(
        '[data-loyalty-earn-preview]'
    );
    const valuePreview = card.querySelector(
        '[data-loyalty-value-preview]'
    );
    const limitPreview = card.querySelector(
        '[data-loyalty-limit-preview]'
    );

    const refresh = () => {
        card.classList.toggle(
            'is-disabled',
            !enabled?.checked
        );

        if (earnPreview) {
            earnPreview.textContent =
                `Belanja ${rupiah(earn?.value || 10000)} = 1 poin`;
        }

        if (valuePreview) {
            valuePreview.textContent =
                `1 poin = ${rupiah(value?.value || 100)}`;
        }

        if (limitPreview) {
            limitPreview.textContent =
                `Maksimal ${Number(percent?.value || 20)}% dari transaksi`;
        }
    };

    [enabled, earn, value, percent].forEach(input => {
        input?.addEventListener('input', refresh);
        input?.addEventListener('change', refresh);
    });

    refresh();
}

function bindManualGuide() {
    const guide = document.querySelector('[data-manual-guide]');
    const list = document.querySelector('[data-manual-list]');

    if (!guide || !list || guide.dataset.manualBound === '1') {
        return;
    }

    guide.dataset.manualBound = '1';

    const search = document.querySelector('[data-manual-search]');
    const cards = [...list.querySelectorAll('[data-manual-card]')];
    const categoryButtons = [
        ...document.querySelectorAll('[data-manual-category]'),
    ];
    const empty = document.querySelector('[data-manual-empty]');
    const count = document.querySelector('[data-manual-result-count]');
    let activeCategory = 'all';

    const normalize = value => String(value || '')
        .toLocaleLowerCase('id-ID')
        .trim();

    const applyFilter = () => {
        const query = normalize(search?.value);
        let visible = 0;

        cards.forEach(card => {
            const categoryMatch = activeCategory === 'all'
                || card.dataset.category === activeCategory;
            const searchMatch = !query
                || normalize(card.dataset.search).includes(query);
            const show = categoryMatch && searchMatch;

            card.classList.toggle('d-none', !show);

            if (show) {
                visible += 1;
            }
        });

        empty?.classList.toggle('d-none', visible !== 0);

        if (count) {
            count.textContent = `${visible} modul`;
        }
    };

    const openManualTarget = targetId => {
        if (!targetId) {
            return;
        }

        const target = document.getElementById(targetId);

        if (!target?.matches('[data-manual-card]')) {
            return;
        }

        activeCategory = 'all';

        if (search) {
            search.value = '';
        }

        categoryButtons.forEach(button => {
            button.classList.toggle(
                'is-active',
                button.dataset.manualCategory === 'all'
            );
        });

        applyFilter();
        target.open = true;

        window.setTimeout(() => {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        }, 40);
    };

    search?.addEventListener('input', applyFilter);

    categoryButtons.forEach(button => {
        button.addEventListener('click', () => {
            activeCategory = button.dataset.manualCategory || 'all';

            categoryButtons.forEach(item => {
                item.classList.toggle('is-active', item === button);
            });

            applyFilter();
        });
    });

    document.querySelector('[data-manual-expand]')
        ?.addEventListener('click', () => {
            cards
                .filter(card => !card.classList.contains('d-none'))
                .forEach(card => { card.open = true; });
        });

    document.querySelector('[data-manual-collapse]')
        ?.addEventListener('click', () => {
            cards.forEach(card => { card.open = false; });
        });

    document.querySelector('[data-manual-print]')
        ?.addEventListener('click', () => {
            cards
                .filter(card => !card.classList.contains('d-none'))
                .forEach(card => { card.open = true; });

            window.print();
        });

    const openManualStep = targetId => {
        if (!targetId) {
            return;
        }

        const target = document.getElementById(targetId);

        if (!target) {
            return;
        }

        const card = target.closest('[data-manual-card]');

        if (card) {
            card.classList.remove('d-none');
            card.open = true;
        }

        target.classList.add('is-anchor-highlight');

        window.setTimeout(() => {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }, 40);

        window.setTimeout(() => {
            target.classList.remove('is-anchor-highlight');
        }, 1900);
    };

    document.querySelectorAll('[data-manual-step-anchor]')
        .forEach(link => {
            link.addEventListener('click', event => {
                event.preventDefault();

                const targetId = link.dataset.manualStepAnchor;

                history.replaceState(
                    null,
                    '',
                    `#${targetId}`
                );

                openManualStep(targetId);
            });
        });

    document.querySelectorAll('[data-manual-anchor]')
        .forEach(link => {
            link.addEventListener('click', event => {
                const targetId = link.dataset.manualAnchor;

                if (!targetId) {
                    return;
                }

                event.preventDefault();
                history.replaceState(
                    null,
                    '',
                    `#${targetId}`
                );
                openManualTarget(targetId);
            });
        });

    window.addEventListener('hashchange', () => {
        openManualTarget(
            window.location.hash.replace(/^#/, '')
        );
    });

    document.addEventListener('keydown', event => {
        if (
            event.key === '/'
            && document.activeElement?.tagName !== 'INPUT'
            && document.activeElement?.tagName !== 'TEXTAREA'
        ) {
            event.preventDefault();
            search?.focus();
        }
    });

    applyFilter();

    if (window.location.hash) {
        const targetId = window.location.hash.replace(/^#/, '');
        const target = document.getElementById(targetId);

        if (target?.matches('[data-manual-item]')) {
            openManualStep(targetId);
        } else {
            openManualTarget(targetId);
        }
    }
}

function bindDashboard() {
    const dashboard = document.querySelector('[data-dashboard]');

    if (!dashboard || dashboard.dataset.dashboardBound === '1') {
        return;
    }

    dashboard.dataset.dashboardBound = '1';

    const filterButtons = [
        ...dashboard.querySelectorAll('[data-dashboard-filter]'),
    ];
    const sections = [
        ...dashboard.querySelectorAll('[data-dashboard-section]'),
    ];
    const rows = [
        ...dashboard.querySelectorAll('[data-dashboard-row]'),
    ];
    const densityButton = dashboard.querySelector(
        '[data-dashboard-density]'
    );
    const refreshButton = dashboard.querySelector(
        '[data-dashboard-refresh]'
    );
    const storagePrefix = 'ga-dashboard-panel-';

    const availableFilters = new Set(
        filterButtons.map(button => button.dataset.dashboardFilter)
    );

    let activeFilter = localStorage.getItem(
        'ga-dashboard-filter'
    ) || 'all';

    if (!availableFilters.has(activeFilter)) {
        activeFilter = 'all';
    }

    const refreshRows = () => {
        rows.forEach(row => {
            const groupedChildren = [
                ...row.querySelectorAll(
                    ':scope > [data-dashboard-section]'
                ),
            ];

            if (groupedChildren.length === 0) {
                return;
            }

            const visibleChildren = groupedChildren.filter(
                child => !child.classList.contains('d-none')
            );

            row.classList.toggle(
                'd-none',
                visibleChildren.length === 0
            );
            row.classList.toggle(
                'has-one-visible',
                visibleChildren.length === 1
            );
            row.classList.toggle(
                'has-two-visible',
                visibleChildren.length === 2
            );
            row.dataset.visiblePanels = String(
                visibleChildren.length
            );
        });
    };

    const applyFilter = filter => {
        activeFilter = availableFilters.has(filter)
            ? filter
            : 'all';

        sections.forEach(section => {
            const sectionGroup = section.dataset.dashboardSection;
            const visible = activeFilter === 'all'
                || sectionGroup === activeFilter;

            section.classList.toggle('d-none', !visible);
        });

        filterButtons.forEach(button => {
            const active =
                button.dataset.dashboardFilter === activeFilter;

            button.classList.toggle('is-active', active);
            button.setAttribute(
                'aria-pressed',
                active ? 'true' : 'false'
            );
        });

        localStorage.setItem(
            'ga-dashboard-filter',
            activeFilter
        );

        refreshRows();
    };

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            applyFilter(button.dataset.dashboardFilter);
        });
    });

    const setCompact = compact => {
        dashboard.classList.toggle(
            'is-compact',
            compact
        );

        if (densityButton) {
            densityButton.classList.toggle(
                'is-active',
                compact
            );
            densityButton.setAttribute(
                'aria-pressed',
                compact ? 'true' : 'false'
            );

            const icon = densityButton.querySelector('i');
            const label = densityButton.querySelector('span');

            if (icon) {
                icon.className = compact
                    ? 'fa-solid fa-expand'
                    : 'fa-solid fa-compress';
            }

            if (label) {
                label.textContent = compact
                    ? 'Lengkap'
                    : 'Ringkas';
            }
        }

        localStorage.setItem(
            'ga-dashboard-compact',
            compact ? '1' : '0'
        );
    };

    densityButton?.addEventListener('click', () => {
        setCompact(
            !dashboard.classList.contains('is-compact')
        );
    });

    refreshButton?.addEventListener('click', () => {
        refreshButton.disabled = true;
        refreshButton.classList.add('is-loading');

        const icon = refreshButton.querySelector('i');
        const label = refreshButton.querySelector('span');

        if (icon) {
            icon.className = 'fa-solid fa-spinner fa-spin';
        }

        if (label) {
            label.textContent = 'Memuat...';
        }

        window.setTimeout(() => {
            window.location.reload();
        }, 250);
    });

    dashboard
        .querySelectorAll('[data-dashboard-panel]')
        .forEach(panel => {
            const header = panel.querySelector(
                ':scope > .dashboard-panel-header'
            );

            if (!header) {
                return;
            }

            const panelKey = panel.dataset.dashboardPanel;
            const savedCollapsed = localStorage.getItem(
                `${storagePrefix}${panelKey}`
            ) === '1';

            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'dashboard-panel-toggle no-print';
            toggle.setAttribute(
                'aria-label',
                'Lipat atau buka panel'
            );
            toggle.innerHTML =
                '<i class="fa-solid fa-chevron-up"></i>';

            const setCollapsed = collapsed => {
                panel.classList.toggle(
                    'is-collapsed',
                    collapsed
                );

                toggle.setAttribute(
                    'aria-expanded',
                    collapsed ? 'false' : 'true'
                );

                const icon = toggle.querySelector('i');

                if (icon) {
                    icon.className = collapsed
                        ? 'fa-solid fa-chevron-down'
                        : 'fa-solid fa-chevron-up';
                }

                localStorage.setItem(
                    `${storagePrefix}${panelKey}`,
                    collapsed ? '1' : '0'
                );
            };

            toggle.addEventListener('click', () => {
                setCollapsed(
                    !panel.classList.contains('is-collapsed')
                );
            });

            header.append(toggle);
            setCollapsed(savedCollapsed);
        });

    setCompact(
        localStorage.getItem('ga-dashboard-compact') === '1'
    );
    applyFilter(activeFilter);
}

function bindSummaryAnalytics() {
    const root = document.querySelector('[data-summary-analytics]');

    if (!root || root.dataset.summaryBound === '1') {
        return;
    }

    root.dataset.summaryBound = '1';

    const rangeSelect = root.querySelector(
        '[data-summary-range-select]'
    );
    const rangeFields = [
        ...root.querySelectorAll('[data-summary-range-field]'),
    ];
    const cashierSelect = root.querySelector(
        '[data-summary-cashier-select]'
    );
    const tooltip = root.querySelector(
        '[data-summary-chart-tooltip]'
    );
    const modal = root.querySelector(
        '[data-summary-detail-modal]'
    );
    const detailEndpoint = root.dataset.detailEndpoint;
    const cashierId = root.dataset.cashierId || '';
    const analyticsMode = root.dataset.mode || 'sales';

    const formatMoney = value => new Intl.NumberFormat(
        'id-ID',
        {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }
    ).format(Number(value || 0));

    const refreshRangeFields = () => {
        const selected = rangeSelect?.value || 'day';

        rangeFields.forEach(field => {
            const visible =
                field.dataset.summaryRangeField === selected;

            field.hidden = !visible;

            field
                .querySelectorAll('input,select')
                .forEach(input => {
                    input.disabled = !visible;
                });
        });
    };

    rangeSelect?.addEventListener(
        'change',
        refreshRangeFields
    );
    refreshRangeFields();

    cashierSelect?.addEventListener('change', () => {
        cashierSelect.form?.requestSubmit();
    });

    const hideTooltip = () => {
        if (tooltip) {
            tooltip.hidden = true;
        }
    };

    const showTooltip = (point, event = null) => {
        if (!tooltip) return;

        tooltip.innerHTML = `
            <strong>${escapeHtml(point.dataset.label || '-')}</strong>
            <span>${escapeHtml(formatMoney(point.dataset.value))}</span>
            <small>${escapeHtml(point.dataset.transactions || '0')} transaksi · klik untuk rincian</small>
        `;
        tooltip.hidden = false;

        const rootRect = root.getBoundingClientRect();
        const pointRect = point.getBoundingClientRect();

        let left = pointRect.left
            - rootRect.left
            + (pointRect.width / 2);
        let top = pointRect.top
            - rootRect.top
            - tooltip.offsetHeight
            - 12;

        if (event && 'clientX' in event) {
            left = event.clientX - rootRect.left;
            top = event.clientY
                - rootRect.top
                - tooltip.offsetHeight
                - 14;
        }

        const maxLeft = Math.max(
            12,
            root.clientWidth - tooltip.offsetWidth - 12
        );

        tooltip.style.left = `${Math.max(
            12,
            Math.min(left, maxLeft)
        )}px`;
        tooltip.style.top = `${Math.max(12, top)}px`;
    };

    const closeModal = () => {
        if (!modal) return;

        modal.hidden = true;
        document.body.classList.remove(
            'summary-detail-open'
        );
    };

    const renderEmpty = message => `
        <div class="summary-detail-empty">
            <i class="fa-solid fa-circle-info"></i>
            <span>${escapeHtml(message)}</span>
        </div>
    `;

    const renderTransactions = rows => {
        if (!rows?.length) {
            return renderEmpty(
                'Tidak ada transaksi pada periode ini.'
            );
        }

        return rows.map(row => `
            <article>
                <div>
                    <strong>${escapeHtml(row.number)}</strong>
                    <small>${escapeHtml(row.time)} · ${escapeHtml(row.cashier)}</small>
                </div>
                <span>${escapeHtml(row.customer)}</span>
                <em>
                    ${escapeHtml(formatMoney(row.value))}
                    <small>${escapeHtml(row.method)}</small>
                </em>
            </article>
        `).join('');
    };

    const renderProducts = rows => {
        if (!rows?.length) {
            return renderEmpty(
                'Tidak ada produk yang terjual pada periode ini.'
            );
        }

        return rows.map((row, index) => `
            <article>
                <b>${index + 1}</b>
                <div>
                    <strong>${escapeHtml(row.name)}</strong>
                    <small>${escapeHtml(row.sku || '-')}</small>
                </div>
                <span>
                    ${escapeHtml(String(row.quantity).replace('.', ','))}
                    ${escapeHtml(row.unit || '')}
                </span>
                <em>${escapeHtml(formatMoney(row.value))}</em>
            </article>
        `).join('');
    };

    const openPointDetail = async point => {
        if (!modal || !detailEndpoint) return;

        modal.hidden = false;
        document.body.classList.add(
            'summary-detail-open'
        );

        const title = modal.querySelector(
            '#summary-detail-title'
        );
        const period = modal.querySelector(
            '[data-summary-detail-period]'
        );
        const transactionList = modal.querySelector(
            '[data-summary-transactions]'
        );
        const productList = modal.querySelector(
            '[data-summary-products]'
        );

        if (title) {
            title.textContent = `Rincian ${point.dataset.label || 'Grafik'}`;
        }
        if (period) {
            period.textContent = 'Memuat data...';
        }
        if (transactionList) {
            transactionList.innerHTML =
                '<div class="summary-detail-loading"><i class="fa-solid fa-spinner fa-spin"></i> Memuat transaksi...</div>';
        }
        if (productList) {
            productList.innerHTML =
                '<div class="summary-detail-loading"><i class="fa-solid fa-spinner fa-spin"></i> Memuat produk...</div>';
        }

        const params = new URLSearchParams({
            mode: analyticsMode,
            metric: point.dataset.metric || '',
            key: point.dataset.key || '',
        });

        if (cashierId) {
            params.set('cashier_id', cashierId);
        }

        try {
            const response = await fetch(
                `${detailEndpoint}?${params.toString()}`,
                {
                    headers: {
                        Accept: 'application/json',
                    },
                }
            );

            const data = await response.json();

            if (!response.ok) {
                throw new Error(
                    data.message || 'Rincian gagal dimuat.'
                );
            }

            if (title) {
                title.textContent = data.title
                    || 'Rincian Grafik';
            }
            if (period) {
                period.textContent = `${data.period} · ${data.scope?.label || ''}`;
            }

            modal.querySelector(
                '[data-summary-detail-value]'
            ).textContent = formatMoney(
                data.summary?.value || 0
            );
            modal.querySelector(
                '[data-summary-detail-transactions]'
            ).textContent = new Intl.NumberFormat(
                'id-ID'
            ).format(data.summary?.transactions || 0);
            modal.querySelector(
                '[data-summary-detail-products]'
            ).textContent = new Intl.NumberFormat(
                'id-ID'
            ).format(data.summary?.products || 0);

            const transactionTitle = modal.querySelector(
                '[data-summary-transaction-title]'
            );
            const productTitle = modal.querySelector(
                '[data-summary-product-title]'
            );

            if (transactionTitle && data.labels?.transactions) {
                transactionTitle.textContent =
                    data.labels.transactions;
            }
            if (productTitle && data.labels?.products) {
                productTitle.textContent =
                    data.labels.products;
            }

            modal.querySelector(
                '[data-summary-transaction-count]'
            ).textContent = `${data.transactions?.length || 0} data`;
            modal.querySelector(
                '[data-summary-product-count]'
            ).textContent = `${data.products?.length || 0} barang`;

            if (transactionList) {
                transactionList.innerHTML =
                    renderTransactions(data.transactions);
            }
            if (productList) {
                productList.innerHTML =
                    renderProducts(data.products);
            }
        } catch (error) {
            const message = error instanceof Error
                ? error.message
                : 'Rincian gagal dimuat.';

            if (period) {
                period.textContent = message;
            }
            if (transactionList) {
                transactionList.innerHTML =
                    renderEmpty(message);
            }
            if (productList) {
                productList.innerHTML =
                    renderEmpty(message);
            }
        }
    };

    root
        .querySelectorAll('[data-analytics-point]')
        .forEach(point => {
            point.addEventListener(
                'mouseenter',
                event => showTooltip(point, event)
            );
            point.addEventListener(
                'mousemove',
                event => showTooltip(point, event)
            );
            point.addEventListener(
                'mouseleave',
                hideTooltip
            );
            point.addEventListener(
                'focus',
                () => showTooltip(point)
            );
            point.addEventListener(
                'blur',
                hideTooltip
            );
            point.addEventListener(
                'click',
                () => openPointDetail(point)
            );
            point.addEventListener(
                'keydown',
                event => {
                    if (
                        event.key === 'Enter'
                        || event.key === ' '
                    ) {
                        event.preventDefault();
                        openPointDetail(point);
                    }
                }
            );
        });

    modal
        ?.querySelectorAll('[data-summary-detail-close]')
        .forEach(button => {
            button.addEventListener('click', closeModal);
        });

    document.addEventListener('keydown', event => {
        if (
            event.key === 'Escape'
            && modal
            && !modal.hidden
        ) {
            closeModal();
        }
    });
}

function bindAll() {
    const binders = [
        bindPageHashAnchor,
        bindDashboard,
        bindSummaryAnalytics,
        bindBrandSettings,
        bindManualGuide,
        bindLoyaltySettings,
        bindThermalReceipt,
        bindPosNavigation,
        bindUserMenu,
        bindLivewireConfirms,
        bindAuthControls,
        bindFlashMessages,
        enhanceUi,
        bindMicroInteractions,
    ];

    binders.forEach(binder => {
        try {
            binder();
        } catch (error) {
            console.error(
                `Gagal menjalankan ${binder.name || 'binder UI'}.`,
                error
            );
        }
    });
}

function createRipple(target, event) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const rect = target.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const ripple = document.createElement('span');
    ripple.className = 'ga-ripple';
    ripple.style.width = ripple.style.height = `${size}px`;
    ripple.style.left = `${event.clientX - rect.left - size / 2}px`;
    ripple.style.top = `${event.clientY - rect.top - size / 2}px`;
    target.append(ripple);
    ripple.addEventListener('animationend', () => ripple.remove(), { once: true });
}

function bindMicroInteractions() {
    document.querySelectorAll('.btn:not([data-motion-ready]),.pos-menu-toggle:not([data-motion-ready]),.pos-nav:not([data-motion-ready])').forEach(target => {
        target.dataset.motionReady = '1';
        target.addEventListener('pointerdown', event => createRipple(target, event));
        target.addEventListener('click', () => {
            target.classList.remove('ga-click-bounce');
            void target.offsetWidth;
            target.classList.add('ga-click-bounce');
        });
    });
}

document.addEventListener('click', event => {
    const closeButton = event.target.closest('.pos-modal [wire\\:click="closeModal"]');
    if (!closeButton || closeButton.dataset.closing === '1') return;
    const modal = closeButton.closest('.pos-modal-dialog');
    const componentRoot = closeButton.closest('[wire\\:id]');
    const componentId = componentRoot?.getAttribute('wire:id');
    if (!modal || !componentId || !window.Livewire) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    closeButton.dataset.closing = '1';
    modal.classList.remove('ga-animate-in');
    modal.classList.add('ga-closing');
    window.setTimeout(() => window.Livewire.find(componentId)?.call('closeModal'), 210);
}, true);

document.addEventListener('livewire:navigating', () => document.querySelector('.pos-page')?.classList.add('ga-page-leaving'));
document.addEventListener('livewire:navigated', () => document.querySelector('.pos-page')?.classList.remove('ga-page-leaving'));
document.addEventListener('submit', event => {
    window.setTimeout(() => {
        if (event.defaultPrevented) return;
        const button = event.submitter;
        if (!button?.classList.contains('btn')) return;
        button.classList.add('ga-submitting');
        const icon = button.querySelector('i');
        if (icon) icon.className = 'fa-solid fa-spinner';
        else button.insertAdjacentHTML('afterbegin', '<i class="fa-solid fa-spinner"></i>');
    }, 0);
});

const appearanceMedia = window.matchMedia(
    '(prefers-color-scheme: dark)'
);

function syncSystemAppearance() {
    const root = document.documentElement;
    root.classList.toggle(
        'ga-system-dark',
        root.classList.contains('ga-appearance-system')
            && appearanceMedia.matches
    );
}

appearanceMedia.addEventListener?.('change', syncSystemAppearance);

document.addEventListener('DOMContentLoaded', () => {
    syncSystemAppearance();
    bindAll();
});
document.addEventListener('livewire:navigated', () => {
    syncSystemAppearance();
    bindAll();
});
document.addEventListener('livewire:init', () => {
    Livewire.on('toast', ({ message }) => Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: message, showConfirmButton: false, timer: 2800 }));
    Livewire.on('alert', ({ type = 'error', title = 'Perhatian', message }) => Swal.fire({ icon: type, title, text: message, confirmButtonText: 'Mengerti' }));
    Livewire.hook('morph.removing', ({ el }) => {
        $(el).find('select.select2-hidden-accessible').addBack('select.select2-hidden-accessible').each(function () {
            try { $(this).select2('destroy'); } catch (_) { /* elemen sudah dibersihkan */ }
        });
    });
    Livewire.hook('morph.updated', ({ el }) => requestAnimationFrame(() => enhanceUi(el)));
});

const uiObserver = new MutationObserver(mutations => mutations.forEach(mutation => mutation.addedNodes.forEach(node => {
    if (node.nodeType === 1) requestAnimationFrame(() => enhanceUi(node));
})));
document.addEventListener('DOMContentLoaded', () => uiObserver.observe(document.body, { childList: true, subtree: true }));

function decodeBase64Json(encoded, fallback) {
    if (!encoded) return fallback;

    try {
        const binary = window.atob(encoded);
        const bytes = Uint8Array.from(binary, char => char.charCodeAt(0));
        return JSON.parse(new TextDecoder().decode(bytes));
    } catch (error) {
        console.error('Data halaman POS tidak dapat dibaca.', error);
        return fallback;
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function escapeAttribute(value) {
    return escapeHtml(value).replaceAll('`', '&#096;');
}

function rupiah(value) {
    return `Rp ${Number(value || 0).toLocaleString('id-ID')}`;
}

function normalizeSearch(value) {
    return String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
}

function digitsOnly(value) {
    return String(value ?? '').replace(/\D+/g, '');
}

function bindSalePos() {
    const form = document.querySelector('[data-pos-sale]');
    if (!form || form.dataset.bound === '1') return;
    form.dataset.bound = '1';

    const products = decodeBase64Json(form.dataset.products, []);
    const paymentMethods = decodeBase64Json(form.dataset.paymentMethods, {});
    const loyalty = decodeBase64Json(form.dataset.loyalty, {
        enabled: false,
        earn_amount: 10000,
        redeem_value: 100,
        max_redeem_percent: 20,
    });
    const mayDiscountItems = form.dataset.canDiscount === '1';
    const shiftOpen = form.dataset.shiftOpen === '1';
    const shiftUrl = form.dataset.shiftUrl || '';
    const productMap = Object.fromEntries(products.map(product => [String(product.id), product]));
    const barcodeMap = {};
    const cart = {};
    let paymentIndex = 0;
    let submissionConfirmed = false;
    let activeCustomerId = '';

    products.forEach(product => product.units.forEach(unit => {
        if (unit.barcode) {
            barcodeMap[String(unit.barcode).toLowerCase()] = { product, unitId: unit.id };
        }
    }));

    const cartContainer = form.querySelector('#cart');
    const paymentContainer = form.querySelector('#payments');
    const searchInput = form.querySelector('#search');
    const warehouseSelect = form.querySelector('#warehouse');
    const searchClear = form.querySelector('#clear-search');
    const resultCount = form.querySelector('#product-result-count');
    const emptyResult = form.querySelector('#product-empty');
    let keyboardAutofocusDone = false;

    function isKeyboardVisible(element) {
        if (!element || element.disabled) return false;
        if (element.closest('[hidden],.d-none')) return false;

        const style = window.getComputedStyle(element);
        return style.display !== 'none'
            && style.visibility !== 'hidden'
            && element.getClientRects().length > 0;
    }

    function selectFocusTarget(select) {
        if (!select) return null;

        const select2Container = select.nextElementSibling;
        const selection = select2Container?.classList.contains(
            'select2-container'
        )
            ? select2Container.querySelector('.select2-selection')
            : null;

        return selection || select;
    }

    function keyboardFlowElements() {
        const elements = [];
        const add = element => {
            if (
                element
                && isKeyboardVisible(element)
                && !elements.includes(element)
            ) {
                elements.push(element);
            }
        };

        add(searchInput);

        if (warehouseSelect?.options?.length > 1) {
            add(selectFocusTarget(warehouseSelect));
        }

        cartContainer
            .querySelectorAll('.pos-cart-item')
            .forEach(item => {
                /* Qty paling sering diubah, jadi diletakkan lebih dulu. */
                add(item.querySelector('.qty'));
                add(selectFocusTarget(item.querySelector('.cart-unit')));
                add(item.querySelector('.item-discount:not([readonly])'));
            });

        add(selectFocusTarget(
            form.querySelector('[name="customer_id"]')
        ));
        add(form.querySelector('#redeem-points'));
        add(form.querySelector('#use-max-points:not(:disabled)'));
        add(selectFocusTarget(
            form.querySelector('[name="promotion_id"]')
        ));
        add(form.querySelector('#manual-discount:not([readonly])'));
        add(form.querySelector('[name="due_date"]'));
        add(form.querySelector('[name="notes"]'));

        paymentContainer
            .querySelectorAll('.payment-row')
            .forEach(row => {
                add(selectFocusTarget(
                    row.querySelector('.payment-method')
                ));
                add(row.querySelector('.payment-amount'));
                add(row.querySelector(
                    '.payment-reference:not(:disabled)'
                ));
            });

        add(form.querySelector('#add-payment'));
        add(form.querySelector('.pos-pay-button:not(:disabled)'));

        return elements;
    }

    function focusKeyboardElement(element, selectValue = true) {
        if (!element || !isKeyboardVisible(element)) return false;

        element.focus({ preventScroll: false });

        if (
            selectValue
            && element instanceof HTMLInputElement
            && element.matches(
                '.qty,.item-discount,.payment-amount,'
                + '#manual-discount,#redeem-points'
            )
        ) {
            element.select();
        }

        element.classList.add('ga-click-bounce');
        window.setTimeout(
            () => element.classList.remove('ga-click-bounce'),
            180
        );

        return true;
    }

    function flowElementFor(target, flow) {
        return flow.find(element => (
            element === target
            || element.contains?.(target)
            || target?.contains?.(element)
        ));
    }

    function moveKeyboardFocus(target, direction = 1) {
        const flow = keyboardFlowElements();
        if (!flow.length) return false;

        const current = flowElementFor(target, flow);
        const currentIndex = current ? flow.indexOf(current) : -1;
        const nextIndex = currentIndex < 0
            ? 0
            : Math.min(
                flow.length - 1,
                Math.max(0, currentIndex + direction)
            );

        return focusKeyboardElement(flow[nextIndex]);
    }

    function focusSearch(selectValue = true) {
        if (!searchInput || !shiftOpen) return false;

        searchInput.focus();
        if (selectValue) searchInput.select();
        return true;
    }

    function focusLatestCartQuantity() {
        const quantities = [
            ...cartContainer.querySelectorAll('.qty'),
        ];
        const quantity = quantities.at(-1);

        if (quantity) {
            return focusKeyboardElement(quantity);
        }

        const customer = selectFocusTarget(
            form.querySelector('[name="customer_id"]')
        );

        return focusKeyboardElement(customer, false);
    }

    function restoreCartFieldFocus(className, productId) {
        requestAnimationFrame(() => {
            const field = cartContainer.querySelector(
                `${className}[data-id="${CSS.escape(String(productId))}"]`
            );
            focusKeyboardElement(
                selectFocusTarget(field),
                field?.matches?.('input')
            );
        });
    }

    function prepareKeyboardFlow() {
        form.querySelectorAll(
            '.product-card .add-product,.pos-cart-remove,'
            + '.remove-payment,.pos-clear-customer'
        ).forEach(button => {
            button.tabIndex = -1;
        });

        searchInput?.setAttribute('aria-keyshortcuts', 'F2 Enter Tab');
        form.querySelector('.pos-pay-button')?.setAttribute(
            'aria-keyshortcuts',
            'Enter'
        );

        const metaHint = form.querySelector(
            '.pos-product-meta span:last-child'
        );
        if (metaHint) {
            metaHint.textContent =
                'F2 cari · Enter tambah/lanjut · Tab pindah kolom';
        }
    }

    function fillRemainingPayment(input) {
        const summary = calculateSaleSummary();
        const otherPaid = [
            ...paymentContainer.querySelectorAll('.payment-amount'),
        ].filter(field => field !== input)
            .reduce(
                (sum, field) => sum + Number(field.value || 0),
                0
            );
        const remaining = Math.max(
            0,
            Math.round(summary.total - otherPaid)
        );

        if (Number(input.value || 0) <= 0 && remaining > 0) {
            input.value = String(remaining);
            input.dispatchEvent(new Event('input', {
                bubbles: true,
            }));
        }
    }

    function selectedUnit(item) {
        return item.product.units.find(unit => String(unit.id ?? '') === String(item.unitId ?? ''))
            || item.product.units[0];
    }

    function itemPrice(item) {
        const unit = selectedUnit(item);
        const baseQuantity = item.qty * unit.factor;
        return unit.id === null && unit.wholesale && unit.min && baseQuantity >= unit.min
            ? unit.wholesale
            : unit.price;
    }

    function currentStock(product) {
        if (!product.trackStock) return null;
        return Number(product.stocks?.[String(warehouseSelect?.value)] || 0);
    }

    function stockText(product) {
        const stock = currentStock(product);
        return stock === null
            ? 'Tanpa pelacakan stok'
            : `Stok ${stock.toLocaleString('id-ID')} ${product.units[0].symbol}`;
    }

    function refreshGridStocks() {
        products.forEach(product => {
            const label = form.querySelector(`[data-stock-label="${Number(product.id)}"]`);
            if (label) label.textContent = stockText(product);
        });
    }

    function paymentOptions(selected = 'cash') {
        return Object.entries(paymentMethods).map(([key, label]) => (
            `<option value="${escapeAttribute(key)}" ${selected === key ? 'selected' : ''}>${escapeHtml(label)}</option>`
        )).join('');
    }

    function refreshPaymentRows() {
        [...paymentContainer.querySelectorAll('.payment-row')].forEach((row, index) => {
            const number = row.querySelector('.payment-row-number');
            if (number) number.textContent = `Pembayaran ${index + 1}`;

            const method = row.querySelector('.payment-method')?.value;
            const reference = row.querySelector('.payment-reference');
            if (reference) {
                const isCash = method === 'cash';
                reference.disabled = isCash;
                reference.placeholder = isCash ? 'Tidak diperlukan untuk tunai' : 'Nomor referensi (opsional)';
                if (isCash) reference.value = '';
            }
        });
    }

    function addPayment(method = 'cash', amount = 0) {
        const index = paymentIndex++;
        paymentContainer.insertAdjacentHTML('beforeend', `
            <div class="payment-row">
                <div class="payment-row-head">
                    <span class="payment-row-number">Pembayaran</span>
                    <button type="button" class="remove-payment" aria-label="Hapus metode pembayaran" title="Hapus metode">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-sm-4">
                        <label class="form-label">Metode</label>
                        <select name="payments[${index}][method]" class="form-select form-select-sm payment-method">
                            ${paymentOptions(method)}
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Nominal</label>
                        <div class="input-group input-group-sm payment-amount-group">
                            <span class="input-group-text">Rp</span>
                            <input name="payments[${index}][amount]"
                                   type="text"
                                   inputmode="numeric"
                                   pattern="[0-9]*"
                                   autocomplete="off"
                                   value="${digitsOnly(amount)}"
                                   class="form-control form-control-sm payment-amount"
                                   placeholder="0"
                                   aria-label="Nominal pembayaran">
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Referensi</label>
                        <input name="payments[${index}][reference_number]"
                               class="form-control form-control-sm payment-reference"
                               maxlength="255"
                               placeholder="Nomor referensi (opsional)">
                    </div>
                </div>
            </div>
        `);
        refreshPaymentRows();
        render();
        requestAnimationFrame(prepareKeyboardFlow);
    }

    function unitOptions(item) {
        return item.product.units.map(unit => (
            `<option value="${unit.id ?? ''}" ${String(unit.id ?? '') === String(item.unitId ?? '') ? 'selected' : ''}>${escapeHtml(unit.symbol)} (×${Number(unit.factor)})</option>`
        )).join('');
    }

    function selectedPromotion() {
        const select = form.querySelector('[name="promotion_id"]');
        const option = select?.selectedOptions?.[0];
        if (!option?.value) return null;

        return {
            id: Number(option.value),
            name: option.dataset.name || option.textContent.trim(),
            type: option.dataset.type,
            value: Number(option.dataset.value || 0),
            minimum: Number(option.dataset.minimum || 0),
            maximum: option.dataset.maximum === '' ? null : Number(option.dataset.maximum || 0),
            productIds: new Set(
                String(option.dataset.products || '')
                    .split(',')
                    .map(value => value.trim())
                    .filter(Boolean)
            ),
        };
    }

    function calculateSaleSummary() {
        const items = Object.values(cart).map(item => {
            const unit = selectedUnit(item);
            const price = itemPrice(item);
            const gross = Number(item.qty) * Number(price);
            const discount = Math.min(Math.max(0, Number(item.discount) || 0), gross);
            const line = Math.max(0, gross - discount);

            return {
                item,
                unit,
                price,
                discount,
                line,
                baseQuantity: Number(item.qty) * Number(unit.factor || 1),
            };
        });

        const subtotal = items.reduce((sum, row) => sum + row.line, 0);
        const promotion = selectedPromotion();
        let promotionDiscount = 0;
        let promotionEligible = true;

        if (promotion) {
            promotionEligible = subtotal >= promotion.minimum;
            if (promotionEligible) {
                const eligibleSubtotal = promotion.productIds.size
                    ? items
                        .filter(row => promotion.productIds.has(String(row.item.product.id)))
                        .reduce((sum, row) => sum + row.line, 0)
                    : subtotal;

                promotionDiscount = promotion.type === 'percent'
                    ? eligibleSubtotal * (promotion.value / 100)
                    : Math.min(eligibleSubtotal, promotion.value);

                if (promotion.maximum !== null) {
                    promotionDiscount = Math.min(promotionDiscount, promotion.maximum);
                }

                promotionDiscount = Math.round(Math.min(promotionDiscount, subtotal));
            }
        }

        const manualDiscount = Math.min(
            Math.max(
                0,
                Number(
                    form.querySelector('#manual-discount')?.value
                ) || 0
            ),
            Math.max(0, subtotal - promotionDiscount)
        );

        const customerSelect = form.querySelector(
            '[name="customer_id"]'
        );
        const customerOption = customerSelect?.selectedOptions?.[0];
        const customer = customerOption?.value
            ? {
                id: Number(customerOption.value),
                name: customerOption.dataset.name
                    || customerOption.textContent.trim(),
                phone: customerOption.dataset.phone || '',
                points: Math.max(
                    0,
                    Number(customerOption.dataset.points || 0)
                ),
            }
            : null;

        const beforePointDiscount = Math.max(
            0,
            subtotal - promotionDiscount - manualDiscount
        );

        const redeemValue = Math.max(
            1,
            Number(loyalty.redeem_value || 100)
        );
        const earnAmount = Math.max(
            1,
            Number(loyalty.earn_amount || 10000)
        );
        const maximumPercent = Math.min(
            100,
            Math.max(
                1,
                Number(loyalty.max_redeem_percent || 20)
            )
        );

        const maximumPointDiscount = Math.floor(
            beforePointDiscount * (maximumPercent / 100)
        );
        const maximumByTransaction = Math.floor(
            maximumPointDiscount / redeemValue
        );
        const maximumRedeemPoints = loyalty.enabled && customer
            ? Math.max(
                0,
                Math.min(
                    customer.points,
                    maximumByTransaction
                )
            )
            : 0;

        const redeemInput = form.querySelector('#redeem-points');
        const requestedPoints = Math.max(
            0,
            Math.floor(Number(redeemInput?.value || 0))
        );
        const redeemedPoints = Math.min(
            requestedPoints,
            maximumRedeemPoints
        );

        if (
            redeemInput
            && Number(redeemInput.value || 0) !== redeemedPoints
        ) {
            redeemInput.value = String(redeemedPoints);
        }

        const pointDiscount = redeemedPoints * redeemValue;
        const total = Math.max(
            0,
            beforePointDiscount - pointDiscount
        );

        const payments = [...paymentContainer.querySelectorAll('.payment-row')]
            .map(row => {
                const method = row.querySelector('.payment-method')?.value || '';
                const amount = Number(row.querySelector('.payment-amount')?.value || 0);
                const reference = row.querySelector('.payment-reference')?.value?.trim() || '';

                return {
                    method,
                    methodLabel: paymentMethods[method] || method,
                    amount,
                    reference,
                };
            })
            .filter(payment => payment.amount > 0);

        const paid = payments.reduce((sum, payment) => sum + payment.amount, 0);
        const cashPaid = payments
            .filter(payment => payment.method === 'cash')
            .reduce((sum, payment) => sum + payment.amount, 0);
        const nonCashPaid = paid - cashPaid;
        const change = Math.max(0, paid - total);
        const balance = Math.max(0, total - paid);
        const earnedPoints = loyalty.enabled && customer
            ? Math.floor(total / earnAmount)
            : 0;

        return {
            items,
            subtotal,
            promotion,
            promotionEligible,
            promotionDiscount,
            manualDiscount,
            beforePointDiscount,
            redeemedPoints,
            pointDiscount,
            maximumRedeemPoints,
            earnedPoints,
            total,
            payments,
            paid,
            cashPaid,
            nonCashPaid,
            change,
            balance,
            customer,
            dueDate: form.querySelector('[name="due_date"]')?.value || '',
        };
    }

    function reviewItemsHtml(summary) {
        return summary.items.map((row, index) => `
            <div class="pos-review-item">
                <span class="pos-review-item-number">
                    ${index + 1}
                </span>
                <div>
                    <strong>${escapeHtml(row.item.product.name)}</strong>
                    <small>
                        ${Number(row.item.qty).toLocaleString('id-ID')}
                        ${escapeHtml(row.unit.symbol)}
                        × ${rupiah(row.price)}
                    </small>
                </div>
                <b>${rupiah(row.line)}</b>
            </div>
        `).join('');
    }

    function reviewPaymentsHtml(summary) {
        const paymentIcons = {
            cash: 'fa-money-bill-wave',
            qris: 'fa-qrcode',
            transfer: 'fa-building-columns',
            bank_transfer: 'fa-building-columns',
            card: 'fa-credit-card',
            debit: 'fa-credit-card',
            credit_card: 'fa-credit-card',
            ewallet: 'fa-wallet',
            e_wallet: 'fa-wallet',
        };

        return summary.payments.map(payment => {
            const methodClass = String(
                payment.method || 'other'
            ).toLowerCase().replace(/[^a-z0-9_-]/g, '-');

            const icon = paymentIcons[payment.method]
                || 'fa-circle-dollar-to-slot';

            return `
                <div class="pos-review-payment is-${methodClass}">
                    <span class="pos-review-payment-icon">
                        <i class="fa-solid ${icon}"></i>
                    </span>

                    <span class="pos-review-payment-name">
                        ${escapeHtml(payment.methodLabel)}
                        ${payment.reference
                            ? `<small>${escapeHtml(payment.reference)}</small>`
                            : ''}
                    </span>

                    <b>${rupiah(payment.amount)}</b>
                </div>
            `;
        }).join('');
    }

    function fitReviewMainAmounts(popup) {
        if (!popup) return;

        popup.querySelectorAll('.pos-review-main-amount').forEach(amount => {
            amount.style.fontSize = '';

            const computedSize = Number.parseFloat(
                window.getComputedStyle(amount).fontSize
            );

            let currentSize = Number.isFinite(computedSize)
                ? computedSize
                : 30;

            const minimumSize = 19;

            while (
                amount.scrollWidth > amount.clientWidth
                && currentSize > minimumSize
            ) {
                currentSize -= 1;
                amount.style.fontSize = `${currentSize}px`;
            }

            amount.classList.toggle(
                'is-fitted',
                currentSize < computedSize
            );
        });
    }

    function reviewHtml(summary) {
        const customerName = summary.customer?.name || 'Pelanggan umum';
        const settlementLabel = summary.balance > 0
            ? 'Sisa Piutang'
            : 'Kembalian';

        const settlementValue = summary.balance > 0
            ? summary.balance
            : summary.change;

        const settlementClass = summary.balance > 0
            ? 'is-debt'
            : summary.change > 0
                ? 'is-change'
                : 'is-zero';

        const settlementCaption = summary.balance > 0
            ? `${escapeHtml(customerName)} · Jatuh tempo ${escapeHtml(summary.dueDate)}`
            : summary.change > 0
                ? 'Uang yang dikembalikan kepada pelanggan'
                : 'Pembayaran pas, tidak ada kembalian';

        const debtBlock = summary.balance > 0 ? `
            <div class="pos-review-debt">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <strong>Transaksi menyisakan piutang</strong>
                    <span>
                        ${escapeHtml(customerName)} ·
                        Jatuh tempo ${escapeHtml(summary.dueDate)}
                    </span>
                </div>
            </div>
        ` : '';

        return `
            <div class="pos-review">
                <div class="pos-review-layout">
                    <section class="pos-review-section pos-review-items-section is-items">
                        <div class="pos-review-section-title">
                            <span>
                                <i class="fa-solid fa-basket-shopping"></i>
                                Daftar Barang
                            </span>
                            <b>${summary.items.length} item</b>
                        </div>

                        <div class="pos-review-list pos-review-items-scroll">
                            ${reviewItemsHtml(summary)}
                        </div>

                        <div class="pos-review-items-footer">
                            <span>Total jenis barang</span>
                            <b>${summary.items.length} item</b>
                        </div>
                    </section>

                    <div class="pos-review-summary-column">
                        <section class="pos-review-section is-summary">
                            <div class="pos-review-section-title">
                                <span>
                                    <i class="fa-solid fa-calculator"></i>
                                    Ringkasan Transaksi
                                </span>
                            </div>

                            <div class="pos-review-totals pos-review-compact-totals">
                                <span>
                                    Subtotal
                                    <b>${rupiah(summary.subtotal)}</b>
                                </span>
                                ${summary.promotionDiscount > 0 ? `
                                    <span>
                                        Diskon promo
                                        <b>- ${rupiah(summary.promotionDiscount)}</b>
                                    </span>
                                ` : ''}
                                ${summary.manualDiscount > 0 ? `
                                    <span>
                                        Diskon transaksi
                                        <b>- ${rupiah(summary.manualDiscount)}</b>
                                    </span>
                                ` : ''}
                                ${summary.pointDiscount > 0 ? `
                                    <span class="is-points">
                                        Potongan ${summary.redeemedPoints.toLocaleString('id-ID')} poin
                                        <b>- ${rupiah(summary.pointDiscount)}</b>
                                    </span>
                                ` : ''}
                            </div>
                        </section>

                        <div class="pos-review-highlight-grid">
                            <article class="pos-review-highlight is-total">
                                <div class="pos-review-highlight-head">
                                    <span>
                                        <i class="fa-solid fa-receipt"></i>
                                    </span>
                                    <small>Total Transaksi</small>
                                </div>

                                <strong
                                    class="pos-review-main-amount"
                                    title="${rupiah(summary.total)}"
                                >
                                    ${rupiah(summary.total)}
                                </strong>

                                <em>Nilai akhir yang harus dibayar</em>
                            </article>

                            <article class="pos-review-highlight ${settlementClass}">
                                <div class="pos-review-highlight-head">
                                    <span>
                                        <i class="fa-solid ${
                                            summary.balance > 0
                                                ? 'fa-file-invoice-dollar'
                                                : 'fa-money-bill-transfer'
                                        }"></i>
                                    </span>
                                    <small>${settlementLabel}</small>
                                </div>

                                <strong
                                    class="pos-review-main-amount"
                                    title="${rupiah(settlementValue)}"
                                >
                                    ${rupiah(settlementValue)}
                                </strong>

                                <em>${settlementCaption}</em>
                            </article>
                        </div>

                        <section class="pos-review-section is-payment-section">
                            <div class="pos-review-section-title">
                                <span>
                                    <i class="fa-solid fa-wallet"></i>
                                    Pembayaran
                                </span>
                                <b>${summary.payments.length} metode</b>
                            </div>

                            <div class="pos-review-list">
                                ${reviewPaymentsHtml(summary)}
                            </div>

                            <div class="pos-review-totals is-payment">
                                <span>
                                    Total dibayar
                                    <b>${rupiah(summary.paid)}</b>
                                </span>
                            </div>
                        </section>

                        <div class="pos-review-customer">
                            <i class="fa-solid fa-user"></i>
                            <span>Pelanggan</span>
                            <b>${escapeHtml(customerName)}</b>
                        </div>

                        ${loyalty.enabled && summary.customer ? `
                            <div class="pos-review-loyalty">
                                <span>
                                    <i class="fa-solid fa-coins"></i>
                                </span>
                                <div>
                                    ${summary.redeemedPoints > 0 ? `
                                        <strong>
                                            ${summary.redeemedPoints.toLocaleString('id-ID')} poin digunakan
                                        </strong>
                                        <small>
                                            Potongan ${rupiah(summary.pointDiscount)}
                                        </small>
                                    ` : `
                                        <strong>Tidak memakai poin</strong>
                                        <small>
                                            Saldo ${summary.customer.points.toLocaleString('id-ID')} poin
                                        </small>
                                    `}
                                </div>
                                <b class="${summary.balance > 0 ? 'is-pending' : 'is-earned'}">
                                    ${summary.earnedPoints.toLocaleString('id-ID')} poin
                                    ${summary.balance > 0 ? 'menunggu lunas' : 'akan diperoleh'}
                                </b>
                            </div>
                        ` : ''}

                        ${debtBlock}
                    </div>
                </div>
            </div>
        `;
    }

    function render() {
        const items = Object.values(cart);

        const cartCount = form.querySelector('#cart-count');
        if (cartCount) cartCount.textContent = `${items.length} item`;

        cartContainer.innerHTML = items.length ? items.map((item, index) => {
            const unit = selectedUnit(item);
            const price = itemPrice(item);
            const line = Math.max(0, item.qty * price - item.discount);
            const baseQuantity = item.qty * unit.factor;
            const readonly = mayDiscountItems ? '' : 'readonly';

            return `
                <div class="pos-cart-item">
                    <div class="pos-cart-item-head">
                        <div class="pos-cart-item-title">
                            <strong>${escapeHtml(item.product.name)}</strong>
                            <small>${escapeHtml(item.product.sku)} · ${escapeHtml(stockText(item.product))}</small>
                        </div>
                        <button type="button"
                                class="pos-cart-remove remove"
                                data-id="${Number(item.product.id)}"
                                aria-label="Hapus ${escapeHtml(item.product.name)} dari keranjang"
                                title="Hapus dari keranjang">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>

                    <div class="row g-2 pos-cart-item-grid">
                        <div class="col-md-4 col-12">
                            <label class="small text-muted">Satuan</label>
                            <select class="form-select form-select-sm cart-unit" data-id="${Number(item.product.id)}">${unitOptions(item)}</select>
                        </div>
                        <div class="col-md-2 col-4">
                            <label class="small text-muted">Qty</label>
                            <input class="form-control form-control-sm qty" data-id="${Number(item.product.id)}" type="number" min=".001" step=".001" value="${Number(item.qty)}">
                        </div>
                        <div class="col-md-3 col-4">
                            <label class="small text-muted">Diskon</label>
                            <input class="form-control form-control-sm item-discount" data-id="${Number(item.product.id)}" type="number" min="0" value="${Number(item.discount)}" ${readonly}>
                        </div>
                        <div class="col-md-3 col-4">
                            <label class="small text-muted">Subtotal</label>
                            <div class="pos-cart-line-total">${rupiah(line)}</div>
                        </div>
                    </div>

                    <div class="pos-cart-item-foot">
                        <span class="pos-cart-conversion"><i class="fa-solid fa-scale-balanced"></i>= ${Number(baseQuantity).toLocaleString('id-ID')} ${escapeHtml(item.product.units[0].symbol)}</span>
                        <span class="pos-cart-unit-price">${rupiah(price)}/${escapeHtml(unit.symbol)}</span>
                    </div>

                    <input type="hidden" name="items[${index}][product_id]" value="${Number(item.product.id)}">
                    <input type="hidden" name="items[${index}][product_unit_id]" value="${unit.id ?? ''}">
                    <input type="hidden" name="items[${index}][quantity]" value="${Number(item.qty)}">
                    <input type="hidden" name="items[${index}][discount]" value="${Number(item.discount)}">
                </div>
            `;
        }).join('') : '<div class="text-center text-muted py-4">Keranjang kosong.</div>';

        const summary = calculateSaleSummary();
        const discountTotal = summary.promotionDiscount
            + summary.manualDiscount
            + summary.pointDiscount;

        form.querySelector('#total').textContent = rupiah(summary.total);
        form.querySelector('#paid-total').textContent = rupiah(summary.paid);
        form.querySelector('#change').textContent = rupiah(summary.change);

        const breakdown = form.querySelector('#total-breakdown');
        if (breakdown) {
            const pointPart = summary.pointDiscount > 0
                ? ` · Poin ${rupiah(summary.pointDiscount)}`
                : '';

            breakdown.textContent =
                `Subtotal ${rupiah(summary.subtotal)} · `
                + `Diskon ${rupiah(discountTotal)}`
                + pointPart;
        }

        updateLoyaltyPanel(summary);
        prepareKeyboardFlow();
    }

    function updateLoyaltyPanel(
        summary = calculateSaleSummary()
    ) {
        const panel = form.querySelector('#loyalty-panel');

        if (!panel) {
            return;
        }

        const pointInput = panel.querySelector('#redeem-points');
        const maxButton = panel.querySelector('#use-max-points');
        const currentPoints = panel.querySelector(
            '#loyalty-current-points'
        );
        const balanceText = panel.querySelector(
            '#loyalty-balance-text'
        );
        const discountText = panel.querySelector(
            '#loyalty-discount'
        );
        const earnedText = panel.querySelector(
            '#loyalty-earned'
        );
        const notice = panel.querySelector('#loyalty-notice');

        if (!summary.customer) {
            panel.classList.add('d-none');

            if (pointInput) {
                pointInput.value = '0';
                pointInput.max = '0';
            }

            return;
        }

        panel.classList.remove('d-none');

        if (pointInput) {
            pointInput.max = String(
                summary.maximumRedeemPoints
            );
        }

        if (maxButton) {
            maxButton.disabled =
                summary.maximumRedeemPoints <= 0;
        }

        if (currentPoints) {
            currentPoints.textContent =
                `${summary.customer.points.toLocaleString('id-ID')} poin`;
        }

        if (balanceText) {
            balanceText.textContent =
                `Setara ${rupiah(
                    summary.customer.points
                    * Number(loyalty.redeem_value || 100)
                )}`;
        }

        if (discountText) {
            discountText.textContent = rupiah(
                summary.pointDiscount
            );
        }

        if (earnedText) {
            earnedText.textContent =
                `${summary.earnedPoints.toLocaleString('id-ID')} poin`;
        }

        if (notice) {
            if (summary.maximumRedeemPoints <= 0) {
                notice.className =
                    'pos-loyalty-notice is-muted';
                notice.textContent =
                    'Belum ada poin yang dapat digunakan pada transaksi ini.';
            } else if (summary.balance > 0) {
                notice.className =
                    'pos-loyalty-notice is-pending';
                notice.textContent =
                    `${summary.earnedPoints.toLocaleString('id-ID')} poin baru masuk setelah piutang dilunasi.`;
            } else {
                notice.className =
                    'pos-loyalty-notice is-ready';
                notice.textContent =
                    `${summary.earnedPoints.toLocaleString('id-ID')} poin akan ditambahkan setelah transaksi disimpan.`;
            }
        }
    }

    function updateCustomerSummary(notify = false) {
        const customerSelect = form.querySelector(
            '[name="customer_id"]'
        );
        const summary = form.querySelector('#customer-summary');
        const clearButtons = [
            ...form.querySelectorAll('[data-clear-customer]'),
        ];

        if (!customerSelect || !summary) {
            return;
        }

        const option = customerSelect.options[
            customerSelect.selectedIndex
        ];
        const nameEl = summary.querySelector(
            '#customer-summary-name'
        );
        const metaEl = summary.querySelector(
            '#customer-summary-meta'
        );
        const hasCustomer = Boolean(option?.value);

        clearButtons.forEach(button => {
            button.classList.toggle(
                'd-none',
                !hasCustomer
            );
        });

        if (!hasCustomer) {
            summary.classList.add('d-none');
            return;
        }

        const name = option.dataset.name || option.textContent.trim();
        const phone = option.dataset.phone ? option.dataset.phone.trim() : '';
        const points = Number(option.dataset.points || 0);

        nameEl.textContent = name;
        const parts = [];
        if (phone) parts.push(phone);
        parts.push(`${points.toLocaleString('id-ID')} poin`);

        if (loyalty.enabled) {
            parts.push(
                `setara ${rupiah(
                    points
                    * Number(loyalty.redeem_value || 100)
                )}`
            );
        }

        metaEl.textContent = parts.join(' · ');
        summary.classList.remove('d-none');

        if (notify && loyalty.enabled) {
            const saleSummary = calculateSaleSummary();

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: `${name} memiliki ${points.toLocaleString('id-ID')} poin`,
                text: saleSummary.maximumRedeemPoints > 0
                    ? `Maksimal ${saleSummary.maximumRedeemPoints.toLocaleString('id-ID')} poin dapat digunakan.`
                    : 'Belum ada poin yang dapat digunakan pada transaksi ini.',
                showConfirmButton: false,
                timer: 2800,
            });
        }
    }

    function clearSelectedCustomer() {
        const customerSelect = form.querySelector(
            '[name="customer_id"]'
        );

        if (!customerSelect || !customerSelect.value) {
            return;
        }

        customerSelect.value = '';

        if (
            $(customerSelect).hasClass(
                'select2-hidden-accessible'
            )
        ) {
            $(customerSelect)
                .val('')
                .trigger('change.select2');
        }

        handleCustomerChange(false);

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Pelanggan dihapus',
            text: 'Transaksi kembali menggunakan Pelanggan Umum.',
            showConfirmButton: false,
            timer: 2200,
        });
    }

    function handleCustomerChange(notify = false) {
        const customerSelect = form.querySelector(
            '[name="customer_id"]'
        );
        const customerId = customerSelect?.value || '';

        if (customerId !== activeCustomerId) {
            activeCustomerId = customerId;

            const pointInput = form.querySelector(
                '#redeem-points'
            );

            if (pointInput) {
                pointInput.value = '0';
            }
        }

        updateCustomerSummary(notify);
        render();
    }

    async function showShiftRequired() {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Shift kasir belum dibuka',
            text: 'Buka shift terlebih dahulu agar POS dan keranjang dapat digunakan.',
            showCancelButton: true,
            confirmButtonText: 'Buka Shift',
            cancelButtonText: 'Nanti',
            confirmButtonColor: '#f1aa00',
            reverseButtons: true,
        });
        if (result.isConfirmed && shiftUrl) window.location.href = shiftUrl;
    }

    function addProduct(product, unitId = undefined) {
        if (!shiftOpen) {
            showShiftRequired();
            return;
        }

        if (!product) return;

        const id = String(product.id);
        const existingItem = cart[id] || null;
        const candidate = existingItem
            ? { ...existingItem }
            : {
                product,
                unitId: unitId === undefined ? null : unitId,
                qty: 0,
                discount: 0,
            };

        if (unitId !== undefined) candidate.unitId = unitId;

        const unit = selectedUnit(candidate);
        const nextQuantity = Number(candidate.qty || 0) + 1;
        const proposedBaseQuantity = nextQuantity * Number(unit.factor || 1);
        const available = currentStock(product);

        if (available !== null && available <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Stok barang habis',
                html: `<b>${escapeHtml(product.name)}</b> tidak mempunyai stok layak jual di gudang yang dipilih.`,
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#e2a500',
            });
            return;
        }

        if (available !== null && proposedBaseQuantity > available) {
            Swal.fire({
                icon: 'warning',
                title: 'Stok tidak mencukupi',
                html: `Stok layak jual <b>${escapeHtml(product.name)}</b> hanya
                       <b>${available.toLocaleString('id-ID')} ${escapeHtml(product.units[0].symbol)}</b>.`,
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#e2a500',
            });
            return;
        }

        candidate.qty = nextQuantity;
        cart[id] = candidate;
        render();
    }

    function updateCartUnit(select) {
        const id = String(select.dataset.id || '');
        const item = cart[id];
        if (!item) return;

        const nextUnitId = select.value === '' ? null : Number(select.value);
        const candidate = { ...item, unitId: nextUnitId };
        const unit = selectedUnit(candidate);
        const proposedBaseQuantity = Number(candidate.qty || 0) * Number(unit.factor || 1);
        const available = currentStock(candidate.product);

        if (available !== null && proposedBaseQuantity > available) {
            Swal.fire({
                icon: 'warning',
                title: 'Stok tidak cukup untuk satuan ini',
                html: `Jumlah tersebut setara dengan
                       <b>${proposedBaseQuantity.toLocaleString('id-ID')} ${escapeHtml(candidate.product.units[0].symbol)}</b>,
                       sedangkan stok tersedia hanya
                       <b>${available.toLocaleString('id-ID')} ${escapeHtml(candidate.product.units[0].symbol)}</b>.`,
                confirmButtonText: 'Pilih Satuan Lain',
                confirmButtonColor: '#e2a500',
            });

            render();
            return;
        }

        item.unitId = nextUnitId;
        render();
    }

    form.addEventListener('click', event => {
        const productButton = event.target.closest('.add-product');
        if (productButton) addProduct(productMap[productButton.dataset.id]);

        if (event.target.matches('.remove')) {
            delete cart[event.target.dataset.id];
            render();
        }

        const clearCustomer = event.target.closest(
            '[data-clear-customer]'
        );

        if (clearCustomer) {
            clearSelectedCustomer();
            return;
        }

        const useMaximumPoints = event.target.closest(
            '#use-max-points'
        );

        if (useMaximumPoints) {
            const saleSummary = calculateSaleSummary();
            const pointInput = form.querySelector(
                '#redeem-points'
            );

            if (pointInput) {
                pointInput.value = String(
                    saleSummary.maximumRedeemPoints
                );
                render();
            }
        }

        const removePayment = event.target.closest('.remove-payment');
        if (removePayment) {
            removePayment.closest('.payment-row')?.remove();
            refreshPaymentRows();
            render();
        }
    });

    form.addEventListener('input', event => {
        if (event.target.matches('.cart-unit')) {
            const productId = event.target.dataset.id;
            updateCartUnit(event.target);
            restoreCartFieldFocus('.cart-unit', productId);
            return;
        }

        if (event.target.matches('.qty')) {
            const productId = event.target.dataset.id;
            cart[productId].qty = Math.max(
                0.001,
                Number(event.target.value) || 1
            );
            render();
            restoreCartFieldFocus('.qty', productId);
        }
        if (event.target.matches('.item-discount')) {
            const productId = event.target.dataset.id;
            cart[productId].discount = Math.max(
                0,
                Number(event.target.value) || 0
            );
            render();
            restoreCartFieldFocus(
                '.item-discount',
                productId
            );
        }
        if (event.target.matches('.payment-amount')) {
            const clean = digitsOnly(event.target.value);
            if (event.target.value !== clean) event.target.value = clean;
            render();
        }
        if (event.target.matches('#manual-discount')) {
            render();
        }

        if (event.target.matches('#redeem-points')) {
            const clean = digitsOnly(event.target.value);
            event.target.value = clean === '' ? '0' : clean;
            render();
        }

        /*
         * Select2 meneruskan perubahan select sebagai event input.
         * Promo harus dihitung ulang melalui event ini agar diskon,
         * total, pembayaran, kembalian, dan piutang langsung berubah.
         */
        if (event.target.matches('[name="promotion_id"]')) {
            render();
        }

        if (event.target.matches('[name="customer_id"]')) {
            handleCustomerChange(false);
        }

        if (event.target.matches('#search')) {
            filterProducts(event.target.value);
        }
    });

    form.addEventListener('change', event => {
        if (event.target.matches('.payment-method')) {
            refreshPaymentRows();
        }
        if (event.target.matches('[name="customer_id"]')) {
            handleCustomerChange(true);
        }
        if (event.target.matches('[name="promotion_id"]')) {
            render();
        }
    });

    searchInput?.addEventListener('keydown', event => {
        if (event.key !== 'Enter') return;

        if (event.repeat) {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        const value = event.target.value.trim().toLowerCase();
        const hit = barcodeMap[value];
        if (hit) addProduct(hit.product, hit.unitId);
        else {
            const firstVisible = [...form.querySelectorAll('.product-card:not(.d-none)')][0];
            const product = firstVisible ? productMap[firstVisible.querySelector('.add-product')?.dataset.id] : null;
            if (!product) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Barang tidak ditemukan', showConfirmButton: false, timer: 1800 });
                return;
            }
            addProduct(product);
        }
        event.target.value = '';
        filterProducts('');
        event.target.focus();
    });
    function filterProducts(value) {
        const terms = normalizeSearch(value).split(/\s+/).filter(Boolean);
        let visible = 0;
        form.querySelectorAll('.product-card').forEach(card => {
            const haystack = normalizeSearch(card.dataset.search);
            const matches = terms.every(term => haystack.includes(term));
            card.classList.toggle('d-none', !matches);
            if (matches) visible += 1;
        });
        if (resultCount) resultCount.textContent = String(visible);
        emptyResult?.classList.toggle('d-none', visible !== 0);
        searchClear?.classList.toggle('is-visible', Boolean(value));
    }
    searchClear?.addEventListener('click', () => {
        searchInput.value = '';
        filterProducts('');
        searchInput.focus();
    });
    document.addEventListener('keydown', event => {
        if ((event.key === 'F2' || ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k')) && document.body.contains(form)) {
            event.preventDefault();
            searchInput?.focus();
            searchInput?.select();
        }
    });
    form.addEventListener('keydown', event => {
        const target = event.target;
        const select2IsOpen = Boolean(
            document.querySelector('.select2-container--open')
        );

        if (event.key === 'Tab' && !select2IsOpen) {
            const flow = keyboardFlowElements();
            const current = flowElementFor(target, flow);

            if (!current) return;

            event.preventDefault();

            if (target === searchInput && !event.shiftKey) {
                focusLatestCartQuantity();
                return;
            }

            moveKeyboardFocus(
                current,
                event.shiftKey ? -1 : 1
            );
            return;
        }

        if (event.key === 'Escape' && !select2IsOpen) {
            event.preventDefault();
            focusSearch();
            return;
        }

        if (event.key === 'Enter') {
            if (event.repeat) {
                event.preventDefault();
                return;
            }

            /* Search mempunyai handler Enter tersendiri. */
            if (target === searchInput) return;

            if (target.matches('.qty,.item-discount,.cart-unit')) {
                event.preventDefault();
                focusSearch();
                return;
            }

            if (target.matches('.payment-amount')) {
                event.preventDefault();
                fillRemainingPayment(target);

                requestAnimationFrame(() => {
                    const payButton = form.querySelector(
                        '.pos-pay-button:not(:disabled)'
                    );
                    if (payButton) {
                        focusKeyboardElement(
                            payButton,
                            false
                        );
                    }
                });
                return;
            }

            if (target.matches(
                '#manual-discount,#redeem-points,'
                + '[name="due_date"],[name="notes"],'
                + '.payment-reference'
            )) {
                event.preventDefault();
                moveKeyboardFocus(target, 1);
                return;
            }
        }

        if (!target.matches('.payment-amount')) return;

        const allowedKeys = [
            'Backspace',
            'Delete',
            'Tab',
            'ArrowLeft',
            'ArrowRight',
            'Home',
            'End',
        ];
        const isShortcut = event.ctrlKey || event.metaKey;

        if (
            !isShortcut
            && !allowedKeys.includes(event.key)
            && !/^\d$/.test(event.key)
        ) {
            event.preventDefault();
        }
    });

    form.addEventListener('paste', event => {
        if (!event.target.matches('.payment-amount')) return;
        event.preventDefault();

        const input = event.target;
        const pasted = digitsOnly(event.clipboardData?.getData('text') || '');
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? input.value.length;
        input.setRangeText(pasted, start, end, 'end');
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });

    form.querySelector('#add-payment')?.addEventListener('click', () => addPayment('qris', 0));
    warehouseSelect?.addEventListener('change', () => {
        refreshGridStocks();
        render();
    });
    form.addEventListener('submit', async event => {
        if (submissionConfirmed) return;

        event.preventDefault();

        if (!shiftOpen) {
            showShiftRequired();
            return;
        }

        if (!Object.keys(cart).length) {
            await Swal.fire({
                icon: 'info',
                title: 'Keranjang kosong',
                text: 'Tambahkan minimal satu barang sebelum menyimpan transaksi.',
                confirmButtonText: 'Kembali ke POS',
            });
            return;
        }

        const summary = calculateSaleSummary();

        if (summary.promotion && !summary.promotionEligible) {
            await Swal.fire({
                icon: 'warning',
                title: 'Minimum promo belum terpenuhi',
                html: `Promo <b>${escapeHtml(summary.promotion.name)}</b> membutuhkan minimum transaksi
                       <b>${rupiah(summary.promotion.minimum)}</b>.`,
                confirmButtonText: 'Periksa Keranjang',
            });
            return;
        }

        if (summary.total > 0 && summary.payments.length === 0) {
            await Swal.fire({
                icon: 'warning',
                title: 'Belum ada pembayaran',
                text: 'Masukkan nominal pada minimal satu metode pembayaran sebelum melanjutkan.',
                confirmButtonText: 'Isi Pembayaran',
                confirmButtonColor: '#e2a500',
            });
            form.querySelector('.payment-amount')?.focus();
            return;
        }

        if (summary.nonCashPaid > summary.total) {
            await Swal.fire({
                icon: 'error',
                title: 'Pembayaran non-tunai terlalu besar',
                html: `Total transaksi <b>${rupiah(summary.total)}</b>, sedangkan pembayaran non-tunai
                       <b>${rupiah(summary.nonCashPaid)}</b>.`,
                confirmButtonText: 'Perbaiki Pembayaran',
            });
            return;
        }

        if (summary.change > summary.cashPaid) {
            await Swal.fire({
                icon: 'error',
                title: 'Kembalian tidak valid',
                text: 'Kelebihan pembayaran hanya boleh berasal dari pembayaran tunai.',
                confirmButtonText: 'Perbaiki Pembayaran',
            });
            return;
        }

        if (summary.balance > 0) {
            if (!summary.customer || !summary.dueDate) {
                await Swal.fire({
                    icon: 'warning',
                    title: 'Pembayaran masih kurang',
                    html: `Pembayaran kurang <b>${rupiah(summary.balance)}</b>.<br>
                           Pilih <b>member/pelanggan</b> dan isi <b>jatuh tempo</b>
                           jika sisa pembayaran ingin dicatat sebagai piutang.`,
                    confirmButtonText: 'Lengkapi Data',
                    confirmButtonColor: '#e2a500',
                });

                if (!summary.customer) {
                    const customerSelect = form.querySelector('[name="customer_id"]');
                    if ($(customerSelect).hasClass('select2-hidden-accessible')) {
                        $(customerSelect).select2('open');
                    } else {
                        customerSelect?.focus();
                    }
                } else {
                    form.querySelector('[name="due_date"]')?.focus();
                }
                return;
            }

            const debtConfirm = await Swal.fire({
                icon: 'warning',
                title: 'Pembayaran kurang',
                html: `Sisa <b>${rupiah(summary.balance)}</b> akan dicatat sebagai piutang
                       atas nama <b>${escapeHtml(summary.customer.name)}</b>
                       dengan jatuh tempo <b>${escapeHtml(summary.dueDate)}</b>.`,
                showCancelButton: true,
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Perbaiki Pembayaran',
                confirmButtonColor: '#e2a500',
                reverseButtons: true,
            });

            if (!debtConfirm.isConfirmed) return;
        }

        const confirmation = await Swal.fire({
            icon: 'question',
            title: 'Periksa transaksi',
            html: reviewHtml(summary),
            width: 1060,
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-floppy-disk"></i> Simpan & Lihat Struk',
            cancelButtonText: 'Kembali Periksa',
            confirmButtonColor: '#174c8e',
            reverseButtons: true,
            focusConfirm: true,
            customClass: {
                popup: 'pos-review-popup',
                htmlContainer: 'pos-review-container',
                confirmButton: 'pos-review-confirm',
            },
            didOpen: popup => {
                fitReviewMainAmounts(popup);
            },
            didRender: popup => {
                fitReviewMainAmounts(popup);
            },
        });

        if (!confirmation.isConfirmed) return;

        submissionConfirmed = true;

        /*
         * Enter yang masih ditahan ketika transaksi disimpan dapat
         * terbawa ke halaman struk dan menekan tombol cetak terlalu
         * cepat. Simpan waktu transisi supaya halaman struk memberi
         * jeda sampai tombol benar-benar dilepas.
         */
        try {
            sessionStorage.setItem(
                'pos:receipt-enter-guard-until',
                String(Date.now() + 1600)
            );
        } catch (error) {
            /* sessionStorage dapat diblokir oleh mode privasi. */
        }

        const submitButton = form.querySelector('.pos-pay-button');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
        }

        form.requestSubmit();
    });

    if (shiftOpen) addPayment('cash', 0);
    refreshGridStocks();
    filterProducts(searchInput?.value || '');
    activeCustomerId = form.querySelector(
        '[name="customer_id"]'
    )?.value || '';
    render();
    updateCustomerSummary(false);
    prepareKeyboardFlow();

    if (shiftOpen && !keyboardAutofocusDone) {
        keyboardAutofocusDone = true;
        window.setTimeout(() => {
            const active = document.activeElement;
            if (
                document.body.contains(form)
                && (
                    !active
                    || active === document.body
                    || active === document.documentElement
                )
            ) {
                focusSearch(false);
            }
        }, 120);
    }
}


function bindShiftPage() {
    const page = document.querySelector('[data-shift-page]');
    if (!page || page.dataset.bound === '1') return;
    page.dataset.bound = '1';

    const expectedCash = Number(page.dataset.expectedCash || 0);
    const cashInputs = [...page.querySelectorAll('[data-cash-input]')];

    function sanitizeCashInput(input) {
        const clean = digitsOnly(input.value);
        if (input.value !== clean) input.value = clean;
        return Number(clean || 0);
    }

    function updateOpeningPreview() {
        const input = page.querySelector('[data-opening-cash]');
        const preview = page.querySelector('[data-opening-preview]');
        if (!input || !preview) return;
        preview.textContent = rupiah(sanitizeCashInput(input));
    }

    function updateDifferencePreview() {
        const input = page.querySelector('[data-actual-cash]');
        const box = page.querySelector('[data-shift-difference]');
        const value = page.querySelector('[data-difference-value]');
        const caption = page.querySelector('[data-difference-caption]');
        if (!input || !box || !value || !caption) return;

        box.classList.remove('is-empty', 'is-even', 'is-over', 'is-short');
        if (input.value === '') {
            box.classList.add('is-empty');
            value.textContent = 'Masukkan kas fisik';
            caption.textContent = 'Sistem akan membandingkannya dengan kas seharusnya.';
            return;
        }

        const actualCash = sanitizeCashInput(input);
        const difference = actualCash - expectedCash;

        if (difference === 0) {
            box.classList.add('is-even');
            value.textContent = 'Rp 0';
            caption.textContent = 'Kas fisik sesuai dengan kas sistem.';
        } else if (difference > 0) {
            box.classList.add('is-over');
            value.textContent = `+${rupiah(difference)}`;
            caption.textContent = 'Kas fisik lebih besar daripada kas sistem.';
        } else {
            box.classList.add('is-short');
            value.textContent = `-${rupiah(Math.abs(difference))}`;
            caption.textContent = 'Kas fisik lebih kecil daripada kas sistem. Tambahkan penjelasan pada catatan.';
        }
    }

    cashInputs.forEach(input => {
        input.addEventListener('input', () => {
            sanitizeCashInput(input);
            updateOpeningPreview();
            updateDifferencePreview();
        });

        input.addEventListener('keydown', event => {
            const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
            const isShortcut = event.ctrlKey || event.metaKey;
            if (!isShortcut && !allowedKeys.includes(event.key) && !/^\d$/.test(event.key)) {
                event.preventDefault();
            }
        });

        input.addEventListener('paste', event => {
            event.preventDefault();
            const pasted = digitsOnly(event.clipboardData?.getData('text') || '');
            const start = input.selectionStart ?? input.value.length;
            const end = input.selectionEnd ?? input.value.length;
            input.setRangeText(pasted, start, end, 'end');
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });

    page.querySelectorAll('form[data-shift-action]').forEach(form => {
        if (form.dataset.shiftBound === '1') return;
        form.dataset.shiftBound = '1';

        form.addEventListener('submit', async event => {
            if (form.dataset.shiftConfirmed === '1') return;

            event.preventDefault();

            if (!form.reportValidity()) return;

            const action = form.dataset.shiftAction;

            if (action === 'open') {
                const openingCash = Number(
                    digitsOnly(form.querySelector('[name="opening_cash"]')?.value || '0')
                );

                const confirmation = await Swal.fire({
                    icon: 'question',
                    title: 'Buka shift kasir?',
                    html: `
                        <div style="text-align:left">
                            <p style="margin-bottom:8px">Pastikan saldo awal sudah sesuai dengan uang fisik di laci kasir.</p>
                            <div style="padding:10px 12px;border:1px solid #dce5ef;border-radius:10px;background:#f7faff">
                                <small style="display:block;color:#71829a">Saldo awal</small>
                                <strong style="font-size:1.15rem;color:#173f75">${rupiah(openingCash)}</strong>
                            </div>
                            <p style="margin:10px 0 0;color:#6f8097;font-size:.82rem">
                                Setelah dibuka, POS dan keranjang akan langsung dapat digunakan.
                            </p>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-door-open"></i> Ya, Buka Shift',
                    cancelButtonText: 'Periksa Lagi',
                    confirmButtonColor: '#198754',
                    reverseButtons: true,
                    focusCancel: true,
                });

                if (!confirmation.isConfirmed) return;
            }

            if (action === 'close') {
                const actualCash = Number(
                    digitsOnly(form.querySelector('[name="actual_cash"]')?.value || '0')
                );
                const difference = actualCash - expectedCash;

                let differenceText = 'Kas sesuai';
                let differenceColor = '#198754';
                let icon = 'question';

                if (difference > 0) {
                    differenceText = `Kas lebih ${rupiah(difference)}`;
                    differenceColor = '#198754';
                    icon = 'warning';
                } else if (difference < 0) {
                    differenceText = `Kas kurang ${rupiah(Math.abs(difference))}`;
                    differenceColor = '#dc3545';
                    icon = 'warning';
                }

                const confirmation = await Swal.fire({
                    icon,
                    title: 'Tutup shift kasir?',
                    html: `
                        <div style="text-align:left">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                                <div style="padding:9px 10px;border:1px solid #dce5ef;border-radius:9px;background:#f7faff">
                                    <small style="display:block;color:#71829a">Kas sistem</small>
                                    <strong style="color:#173f75">${rupiah(expectedCash)}</strong>
                                </div>
                                <div style="padding:9px 10px;border:1px solid #dce5ef;border-radius:9px;background:#f7faff">
                                    <small style="display:block;color:#71829a">Kas fisik</small>
                                    <strong style="color:#173f75">${rupiah(actualCash)}</strong>
                                </div>
                            </div>
                            <div style="margin-top:8px;padding:10px 12px;border:1px solid ${differenceColor}55;border-radius:9px;background:${difference < 0 ? '#fff4f5' : '#f0fbf5'}">
                                <small style="display:block;color:#71829a">Selisih</small>
                                <strong style="color:${differenceColor}">${differenceText}</strong>
                            </div>
                            <p style="margin:10px 0 0;color:#8a6610;font-size:.82rem">
                                Setelah shift ditutup, transaksi baru membutuhkan pembukaan shift kembali.
                            </p>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-lock"></i> Ya, Tutup Shift',
                    cancelButtonText: 'Periksa Lagi',
                    confirmButtonColor: '#dc3545',
                    reverseButtons: true,
                    focusCancel: true,
                });

                if (!confirmation.isConfirmed) return;
            }

            form.dataset.shiftConfirmed = '1';

            const submitButton = form.querySelector('[type="submit"], button:not([type])');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
            }

            form.requestSubmit();
        });
    });

    updateOpeningPreview();
    updateDifferencePreview();
}

function bindReceiptKeyboard() {
    /* Thermal receipt mempunyai pengelolaan Enter tersendiri. */
    if (document.querySelector('[data-thermal-receipt]')) {
        return;
    }

    const newSale = document.querySelector('[data-new-sale]');
    if (!newSale || newSale.dataset.keyboardBound === '1') return;
    newSale.dataset.keyboardBound = '1';
    const handler = event => {
        if (!document.body.contains(newSale)) {
            document.removeEventListener('keydown', handler);
            return;
        }
        if (event.key !== 'Enter' || event.target.matches('input,select,textarea,button,a')) return;
        event.preventDefault();
        document.removeEventListener('keydown', handler);
        newSale.click();
    };
    document.addEventListener('keydown', handler);
}

function bindPurchaseForm() {
    const form = document.querySelector('[data-pos-purchase]');
    if (!form || form.dataset.bound === '1') return;
    form.dataset.bound = '1';

    const products = decodeBase64Json(form.dataset.products, []);
    const initialItems = decodeBase64Json(form.dataset.initialItems, []);
    const paymentMethods = decodeBase64Json(form.dataset.paymentMethods, {});
    const productMap = Object.fromEntries(products.map(product => [String(product.id), product]));
    const itemsContainer = form.querySelector('#purchase-items');
    const itemCount = form.querySelector('[data-purchase-item-count]');
    const supplierSelect = form.querySelector('#supplier_id');
    const warehouseSelect = form.querySelector('#warehouse_id');
    const purchaseDate = form.querySelector('#purchase_date');
    const dueDate = form.querySelector('#due_date');
    const paidInput = form.querySelector('#paid_amount');
    const paymentMethod = form.querySelector('#payment_method');
    const BASE_UNIT_VALUE = '__base__';

    let rowIndex = 0;
    let submissionConfirmed = false;
    let latestSummary = null;

    function productOptions(selectedId = '') {
        return '<option value="">Pilih barang</option>' + products.map(product => (
            `<option value="${Number(product.id)}" ${String(product.id) === String(selectedId) ? 'selected' : ''}>
                ${escapeHtml(product.sku)} - ${escapeHtml(product.name)}
            </option>`
        )).join('');
    }

    function unitOptions(product, selectedUnitId = null) {
        const baseSelected = selectedUnitId === null || selectedUnitId === '' || selectedUnitId === undefined;
        const base = `<option value="${BASE_UNIT_VALUE}" ${baseSelected ? 'selected' : ''}>
            ${escapeHtml(product.units[0].symbol)} (satuan dasar)
        </option>`;

        return base + product.units.slice(1).map(unit => (
            `<option value="${Number(unit.id)}" ${String(unit.id) === String(selectedUnitId) ? 'selected' : ''}>
                ${escapeHtml(unit.symbol)} (×${Number(unit.factor)})
            </option>`
        )).join('');
    }

    function selectedUnit(row) {
        const product = productMap[row.querySelector('.product-select')?.value];
        if (!product) return null;

        const selectedValue = row.querySelector('.unit-select')?.value;

        if (selectedValue === BASE_UNIT_VALUE || selectedValue === '') {
            return product.units[0];
        }

        return product.units.find(unit => String(unit.id) === String(selectedValue))
            || product.units[0];
    }

    function syncSubmittedUnit(row) {
        const visualUnit = row.querySelector('.unit-select');
        const submittedUnit = row.querySelector('.product-unit-value');
        if (!visualUnit || !submittedUnit) return;

        submittedUnit.value = visualUnit.value === BASE_UNIT_VALUE
            ? ''
            : visualUnit.value;
    }

    function refreshRows() {
        const rows = [...itemsContainer.querySelectorAll('.purchase-item-row')];

        rows.forEach((row, index) => {
            const number = row.querySelector('.purchase-item-number');
            if (number) number.textContent = index + 1;
        });

        if (itemCount) {
            itemCount.textContent = `${rows.length} ${rows.length === 1 ? 'barang' : 'barang'}`;
        }
    }

    function paymentState(summary) {
        if (summary.total <= 0 || summary.paid <= 0) return 'unpaid';
        if (summary.paid >= summary.total) return 'paid';
        return 'partial';
    }

    function calculateSummary() {
        const rows = [...itemsContainer.querySelectorAll('.purchase-item-row')];
        const items = rows.map(row => {
            const product = productMap[row.querySelector('.product-select')?.value] || null;
            const unit = selectedUnit(row);
            const quantity = Number(row.querySelector('.qty')?.value || 0);
            const cost = Number(row.querySelector('.cost')?.value || 0);
            const discount = Number(row.querySelector('.line-discount')?.value || 0);
            const gross = quantity * cost;
            const subtotal = Math.max(0, gross - discount);

            return {
                row,
                product,
                unit,
                quantity,
                cost,
                discount,
                gross,
                subtotal,
                batch: row.querySelector('.batch')?.value?.trim() || '',
                expiry: row.querySelector('.expiry')?.value || '',
            };
        });

        const subtotal = items.reduce((sum, item) => sum + item.subtotal, 0);
        const transactionDiscount = Number(form.querySelector('#discount')?.value || 0);
        const additionalCost = Number(form.querySelector('#additional')?.value || 0);
        const total = Math.max(0, subtotal - transactionDiscount + additionalCost);
        const paid = Number(paidInput?.value || 0);
        const balance = Math.max(0, total - paid);

        return {
            items,
            subtotal,
            transactionDiscount,
            additionalCost,
            total,
            paid,
            balance,
            status: paymentState({ total, paid }),
        };
    }

    function updatePaymentPreview(summary) {
        const preview = form.querySelector('#purchase-payment-preview');
        const status = form.querySelector('#purchase-payment-status');
        const caption = form.querySelector('#purchase-payment-caption');

        if (!preview || !status || !caption) return;

        preview.classList.remove('is-paid', 'is-partial', 'is-unpaid');
        preview.classList.add(`is-${summary.status}`);

        if (summary.status === 'paid') {
            status.textContent = 'Lunas';
            caption.textContent = 'Tidak ada utang supplier dari transaksi ini.';
        } else if (summary.status === 'partial') {
            status.textContent = 'Dibayar Sebagian';
            caption.textContent = `Sisa ${rupiah(summary.balance)} akan dicatat sebagai utang supplier.`;
        } else {
            status.textContent = 'Belum Dibayar';
            caption.textContent = summary.total > 0
                ? `Seluruh ${rupiah(summary.total)} akan dicatat sebagai utang supplier.`
                : 'Tambahkan barang untuk menghitung pembayaran.';
        }

        if (supplierSelect) supplierSelect.required = summary.balance > 0;
        if (dueDate) dueDate.required = summary.balance > 0;
        if (paymentMethod) paymentMethod.disabled = summary.paid <= 0;
    }

    function recalculate() {
        const summary = calculateSummary();

        summary.items.forEach(item => {
            const lineTotal = item.row.querySelector('.line-total');
            if (lineTotal) lineTotal.textContent = rupiah(item.subtotal);

            const baseInfo = item.row.querySelector('.base-info');
            if (baseInfo) {
                baseInfo.textContent = item.product && item.unit
                    ? `Setara ${(item.quantity * Number(item.unit.factor || 1)).toLocaleString('id-ID')} ${item.product.units[0].symbol}`
                    : 'Pilih barang terlebih dahulu';
            }
        });

        const values = {
            '#purchase-subtotal': summary.subtotal,
            '#purchase-discount-preview': summary.transactionDiscount,
            '#purchase-additional-preview': summary.additionalCost,
            '#grand-total': summary.total,
            '#purchase-paid-preview': summary.paid,
            '#purchase-balance': summary.balance,
        };

        Object.entries(values).forEach(([selector, value]) => {
            const element = form.querySelector(selector);
            if (element) element.textContent = rupiah(value);
        });

        updatePaymentPreview(summary);
        latestSummary = summary;

        return summary;
    }

    function addRow(initial = {}) {
        const index = rowIndex++;

        itemsContainer.insertAdjacentHTML('beforeend', `
            <article class="purchase-item-row">
                <div class="purchase-item-head">
                    <div class="purchase-item-identity">
                        <span class="purchase-item-number">${itemsContainer.children.length + 1}</span>
                        <span>
                            <strong>Item Pembelian</strong>
                            <small>Pilih barang, satuan, jumlah, dan harga beli.</small>
                        </span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove" aria-label="Hapus baris" title="Hapus baris">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>

                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label" for="purchase-product-${index}">Barang <b>*</b></label>
                        <select
                            name="items[${index}][product_id]"
                            id="purchase-product-${index}"
                            class="form-select product-select"
                            data-placeholder="Pilih barang"
                            required
                        >
                            ${productOptions(initial.product_id)}
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-3">
                        <label class="form-label" for="purchase-unit-${index}">Satuan</label>
                        <select
                            id="purchase-unit-${index}"
                            class="form-select unit-select"
                            data-placeholder="Pilih satuan"
                        >
                            <option value="">Pilih barang</option>
                        </select>
                        <input
                            type="hidden"
                            name="items[${index}][product_unit_id]"
                            class="product-unit-value"
                            value=""
                        >
                    </div>

                    <div class="col-xl-1 col-md-3">
                        <label class="form-label" for="purchase-qty-${index}">Qty <b>*</b></label>
                        <input
                            name="items[${index}][quantity]"
                            id="purchase-qty-${index}"
                            type="number"
                            min=".001"
                            step=".001"
                            value="${escapeHtml(initial.quantity ?? 1)}"
                            class="form-control qty"
                            required
                        >
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label class="form-label" for="purchase-cost-${index}">Harga/Satuan <b>*</b></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input
                                name="items[${index}][unit_cost]"
                                id="purchase-cost-${index}"
                                type="number"
                                min="0"
                                step=".01"
                                value="${escapeHtml(initial.unit_cost ?? 0)}"
                                class="form-control cost"
                                required
                            >
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label class="form-label" for="purchase-discount-${index}">Diskon Item</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input
                                name="items[${index}][discount]"
                                id="purchase-discount-${index}"
                                type="number"
                                min="0"
                                step=".01"
                                value="${escapeHtml(initial.discount ?? 0)}"
                                class="form-control line-discount"
                            >
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label class="form-label">Subtotal</label>
                        <div class="purchase-line-total line-total">Rp 0</div>
                    </div>
                </div>

                <div class="purchase-conversion-row">
                    <span class="purchase-conversion-label">
                        <i class="fa-solid fa-right-left"></i>
                        Konversi satuan
                    </span>
                    <span class="base-info">Pilih barang terlebih dahulu</span>
                </div>

                <div class="purchase-expiry-fields d-none">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label" for="purchase-batch-${index}">Nomor Batch <b>*</b></label>
                            <input
                                name="items[${index}][batch_number]"
                                id="purchase-batch-${index}"
                                value="${escapeHtml(initial.batch_number ?? '')}"
                                class="form-control batch"
                                maxlength="100"
                                placeholder="Contoh: BATCH-20260801"
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="purchase-expiry-${index}">Tanggal Kedaluwarsa <b>*</b></label>
                            <input
                                name="items[${index}][expired_at]"
                                id="purchase-expiry-${index}"
                                type="date"
                                value="${escapeHtml(initial.expired_at ?? '')}"
                                class="form-control expiry"
                            >
                        </div>

                        <div class="col-md-4">
                            <div class="purchase-expiry-note">
                                <i class="fa-solid fa-circle-info"></i>
                                Batch dan tanggal kedaluwarsa wajib diisi untuk barang ini.
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        `);

        const row = itemsContainer.lastElementChild;
        if (!row) return;

        enhanceUi(row);

        const productSelect = row.querySelector('.product-select');
        const product = productMap[String(initial.product_id || '')];

        if (product) {
            updateProductRow(productSelect, initial.product_unit_id, false);

            row.querySelector('.qty').value = initial.quantity ?? 1;
            row.querySelector('.cost').value = initial.unit_cost ?? product.units[0].cost ?? 0;
            row.querySelector('.line-discount').value = initial.discount ?? 0;
            row.querySelector('.batch').value = initial.batch_number ?? '';
            row.querySelector('.expiry').value = initial.expired_at ?? '';
        }

        refreshRows();
        recalculate();
    }

    function updateProductRow(productSelect, selectedUnitId = null, resetValues = true) {
        const product = productMap[productSelect.value];
        const row = productSelect.closest('.purchase-item-row');
        if (!row) return;

        const unitSelect = row.querySelector('.unit-select');
        unitSelect.innerHTML = product
            ? unitOptions(product, selectedUnitId)
            : '<option value="">Pilih barang</option>';

        if (product) {
            unitSelect.value = selectedUnitId
                ? String(selectedUnitId)
                : BASE_UNIT_VALUE;
        } else {
            unitSelect.value = '';
        }

        syncSubmittedUnit(row);

        if ($(unitSelect).hasClass('select2-hidden-accessible')) {
            $(unitSelect).trigger('change.select2');
        }

        if (resetValues) {
            row.querySelector('.cost').value = product
                ? Number(selectedUnit(row)?.cost || 0)
                : 0;
        }

        const expiryFields = row.querySelector('.purchase-expiry-fields');
        const batchInput = row.querySelector('.batch');
        const expiryInput = row.querySelector('.expiry');
        const requiresExpiry = Boolean(product?.expiry);

        expiryFields?.classList.toggle('d-none', !requiresExpiry);
        if (batchInput) batchInput.required = requiresExpiry;
        if (expiryInput) expiryInput.required = requiresExpiry;

        if (!requiresExpiry && resetValues) {
            if (batchInput) batchInput.value = '';
            if (expiryInput) expiryInput.value = '';
        }

        recalculate();
    }

    function updateUnitRow(unitSelect) {
        const row = unitSelect.closest('.purchase-item-row');
        if (!row) return;

        syncSubmittedUnit(row);
        const unit = selectedUnit(row);
        row.querySelector('.cost').value = Number(unit?.cost || 0);
        recalculate();
    }

    function validateSummary(summary) {
        if (!summary.items.length) {
            return 'Tambahkan minimal satu barang pembelian.';
        }

        for (const [index, item] of summary.items.entries()) {
            const number = index + 1;

            if (!item.product) {
                return `Barang pada baris ${number} belum dipilih.`;
            }

            if (item.quantity <= 0) {
                return `Jumlah ${item.product.name} harus lebih besar dari nol.`;
            }

            if (item.cost < 0) {
                return `Harga ${item.product.name} tidak boleh kurang dari nol.`;
            }

            if (item.discount > item.gross) {
                return `Diskon ${item.product.name} melebihi subtotal barang.`;
            }

            if (item.product.expiry && (!item.batch || !item.expiry)) {
                return `Batch dan kedaluwarsa ${item.product.name} wajib diisi.`;
            }
        }

        if (summary.transactionDiscount > summary.subtotal) {
            return 'Diskon transaksi tidak boleh melebihi subtotal pembelian.';
        }

        if (summary.paid > summary.total) {
            return 'Bayar awal tidak boleh melebihi total pembelian.';
        }

        if (summary.balance > 0 && !supplierSelect?.value) {
            return 'Supplier wajib dipilih karena pembelian belum dibayar lunas.';
        }

        if (summary.balance > 0 && !dueDate?.value) {
            return 'Tanggal jatuh tempo wajib diisi karena masih ada utang supplier.';
        }

        return null;
    }

    function reviewHtml(summary) {
        const supplier = supplierSelect?.selectedOptions?.[0]?.textContent?.trim()
            || 'Tanpa supplier';
        const warehouse = warehouseSelect?.selectedOptions?.[0]?.textContent?.trim()
            || '-';
        const method = summary.paid > 0
            ? paymentMethods[paymentMethod?.value] || paymentMethod?.value || '-'
            : 'Belum ada pembayaran';

        const itemRows = summary.items.map(item => `
            <div class="purchase-review-item">
                <div>
                    <strong>${escapeHtml(item.product.name)}</strong>
                    <small>
                        ${item.quantity.toLocaleString('id-ID')} ${escapeHtml(item.unit?.symbol || '')}
                        × ${rupiah(item.cost)}
                        ${item.discount > 0 ? ` · Diskon ${rupiah(item.discount)}` : ''}
                    </small>
                </div>
                <b>${rupiah(item.subtotal)}</b>
            </div>
        `).join('');

        return `
            <div class="purchase-review">
                <div class="purchase-review-info">
                    <span><small>Supplier</small><b>${escapeHtml(supplier)}</b></span>
                    <span><small>Gudang</small><b>${escapeHtml(warehouse)}</b></span>
                    <span><small>Tanggal</small><b>${escapeHtml(purchaseDate?.value || '-')}</b></span>
                    <span><small>Metode</small><b>${escapeHtml(method)}</b></span>
                </div>

                <section>
                    <header>Daftar Barang <b>${summary.items.length} item</b></header>
                    <div class="purchase-review-list">${itemRows}</div>
                </section>

                <section>
                    <header>Ringkasan</header>
                    <div class="purchase-review-totals">
                        <span>Subtotal <b>${rupiah(summary.subtotal)}</b></span>
                        <span>Diskon <b>- ${rupiah(summary.transactionDiscount)}</b></span>
                        <span>Biaya tambahan <b>${rupiah(summary.additionalCost)}</b></span>
                        <span class="is-total">Total <b>${rupiah(summary.total)}</b></span>
                        <span>Dibayar awal <b>${rupiah(summary.paid)}</b></span>
                        <span class="is-balance">Sisa utang <b>${rupiah(summary.balance)}</b></span>
                    </div>
                </section>

                <div class="purchase-review-warning">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Setelah disimpan, stok dan harga rata-rata langsung berubah. Transaksi tidak dapat diedit atau dihapus.</span>
                </div>
            </div>
        `;
    }

    form.querySelector('#add-row')?.addEventListener('click', () => addRow());

    form.addEventListener('input', event => {
        if (event.target.matches(
            '.qty,.cost,.line-discount,#discount,#additional,#paid_amount'
        )) {
            recalculate();
        }
    });

    $(form).on('change.posPurchase', '.product-select', function () {
        updateProductRow(this);
    });

    $(form).on('change.posPurchase', '.unit-select', function () {
        updateUnitRow(this);
    });

    form.addEventListener('change', event => {
        if (event.target.matches('#supplier_id,#warehouse_id,#payment_method,#due_date')) {
            recalculate();
        }

        if (event.target.matches('#purchase_date') && dueDate) {
            dueDate.min = event.target.value;

            if (dueDate.value && dueDate.value < event.target.value) {
                dueDate.value = event.target.value;
            }
        }
    });

    form.addEventListener('click', event => {
        const removeButton = event.target.closest('.remove');
        if (!removeButton) return;

        const row = removeButton.closest('.purchase-item-row');
        if (!row) return;

        $(row).find('select.select2-hidden-accessible').each(function () {
            try {
                $(this).select2('destroy');
            } catch (_) {
                // Elemen sudah dibersihkan.
            }
        });

        row.remove();
        refreshRows();
        recalculate();

        if (!itemsContainer.children.length) addRow();
    });

    form.addEventListener('submit', async event => {
        if (submissionConfirmed) return;

        event.preventDefault();

        recalculate();

        if (!form.reportValidity()) return;

        const summary = latestSummary || calculateSummary();
        const error = validateSummary(summary);

        if (error) {
            await Swal.fire({
                icon: 'warning',
                title: 'Pembelian belum lengkap',
                text: error,
                confirmButtonText: 'Periksa Data',
                confirmButtonColor: '#e2a500',
            });
            return;
        }

        const confirmation = await Swal.fire({
            icon: 'question',
            title: 'Periksa pembelian',
            html: reviewHtml(summary),
            width: 760,
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-floppy-disk"></i> Simpan Pembelian',
            cancelButtonText: 'Kembali Periksa',
            confirmButtonColor: '#174c8e',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'purchase-review-popup',
                htmlContainer: 'purchase-review-container',
            },
        });

        if (!confirmation.isConfirmed) return;

        submissionConfirmed = true;

        const submitButton = form.querySelector('[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
        }

        form.requestSubmit();
    });

    if (purchaseDate && dueDate) {
        dueDate.min = purchaseDate.value;
    }

    if (initialItems.length) {
        initialItems.forEach(item => addRow(item));
    } else {
        addRow();
    }

    recalculate();
}


function bindStockAdjustment() {
    const form = document.querySelector('[data-stock-adjustment]');
    if (!form || form.dataset.bound === '1') return;
    form.dataset.bound = '1';

    const products = decodeBase64Json(form.dataset.products, []);
    const productMap = Object.fromEntries(
        products.map(product => [String(product.id), product])
    );

    const warehouseSelect = form.querySelector('#adj-warehouse');
    const productSelect = form.querySelector('#adj-product');
    const quantityInput = form.querySelector('#adj-qty');
    const unitLabel = form.querySelector('#adj-unit');
    const currentLabel = form.querySelector('#adj-current');
    const resultLabel = form.querySelector('#adj-result');
    const batchInput = form.querySelector('#adj-batch');
    const expiryInput = form.querySelector('#adj-expiry');
    const reasonInput = form.querySelector('#adj-reason');

    let submitConfirmed = false;

    function product() {
        return productMap[String(productSelect?.value || '')] || null;
    }

    function direction() {
        return form.querySelector('[name="direction"]:checked')?.value || 'in';
    }

    function currentStock() {
        const selectedProduct = product();
        const warehouseId = String(warehouseSelect?.value || '');

        if (!selectedProduct || !warehouseId) return null;

        return Number(selectedProduct.stocks?.[warehouseId] || 0);
    }

    function refresh() {
        const selectedProduct = product();
        const stock = currentStock();
        const quantity = Math.max(0, Number(quantityInput?.value || 0));
        const isIncoming = direction() === 'in';

        form.querySelectorAll('.direction-grid label').forEach(label => {
            label.classList.toggle(
                'is-selected',
                Boolean(label.querySelector('input')?.checked)
            );
        });

        if (unitLabel) unitLabel.textContent = selectedProduct?.unit || 'dasar';

        if (stock === null) {
            currentLabel.textContent = 'Pilih gudang dan barang';
            resultLabel.textContent = '-';
            resultLabel.classList.remove('text-danger');
        } else {
            const after = isIncoming ? stock + quantity : stock - quantity;

            currentLabel.textContent =
                `${stock.toLocaleString('id-ID')} ${selectedProduct.unit}`;

            resultLabel.textContent =
                `${after.toLocaleString('id-ID')} ${selectedProduct.unit}`;

            resultLabel.classList.toggle('text-danger', after < 0);
        }

        const requiresBatch = Boolean(selectedProduct?.expiry && isIncoming);

        batchInput.disabled = !isIncoming;
        expiryInput.disabled = !isIncoming;
        batchInput.required = requiresBatch;
        expiryInput.required = requiresBatch;

        if (!isIncoming) {
            batchInput.value = '';
            expiryInput.value = '';
        }
    }

    form.addEventListener('input', event => {
        if (event.target.matches('#adj-qty,[name="direction"]')) refresh();
    });

    form.addEventListener('change', event => {
        if (
            event.target.matches(
                '#adj-warehouse,#adj-product,[name="direction"]'
            )
        ) {
            refresh();
        }
    });

    form.querySelectorAll('[data-adj-reason]').forEach(button => {
        button.addEventListener('click', () => {
            reasonInput.value = button.dataset.adjReason || '';
            reasonInput.focus();

            form.querySelectorAll('[data-adj-reason]').forEach(chip => {
                chip.classList.toggle('is-selected', chip === button);
            });
        });
    });

    form.addEventListener('submit', async event => {
        if (submitConfirmed) return;

        event.preventDefault();

        if (!form.reportValidity()) return;

        const selectedProduct = product();
        const stock = currentStock();
        const quantity = Number(quantityInput.value || 0);
        const isIncoming = direction() === 'in';

        if (!selectedProduct || stock === null) {
            await Swal.fire({
                icon: 'warning',
                title: 'Data belum lengkap',
                text: 'Pilih gudang dan barang terlebih dahulu.',
                confirmButtonText: 'Periksa Data',
            });
            return;
        }

        if (!isIncoming && quantity > stock) {
            await Swal.fire({
                icon: 'error',
                title: 'Stok tidak mencukupi',
                html: `Stok tersedia <b>${stock.toLocaleString('id-ID')} ${escapeHtml(selectedProduct.unit)}</b>,
                       sedangkan pengurangan <b>${quantity.toLocaleString('id-ID')} ${escapeHtml(selectedProduct.unit)}</b>.`,
                confirmButtonText: 'Perbaiki Jumlah',
            });
            quantityInput.focus();
            return;
        }

        const after = isIncoming ? stock + quantity : stock - quantity;
        const warehouseName =
            warehouseSelect.selectedOptions[0]?.textContent?.trim() || '-';

        const confirmation = await Swal.fire({
            icon: 'warning',
            title: 'Posting penyesuaian stok?',
            html: `
                <div class="stock-confirm-grid">
                    <span><small>Barang</small><b>${escapeHtml(selectedProduct.name)}</b></span>
                    <span><small>Gudang</small><b>${escapeHtml(warehouseName)}</b></span>
                    <span><small>Jenis</small><b>${isIncoming ? 'Tambah stok' : 'Kurangi stok'}</b></span>
                    <span><small>Perubahan</small><b>${isIncoming ? '+' : '-'}${quantity.toLocaleString('id-ID')} ${escapeHtml(selectedProduct.unit)}</b></span>
                    <span><small>Stok sebelum</small><b>${stock.toLocaleString('id-ID')} ${escapeHtml(selectedProduct.unit)}</b></span>
                    <span><small>Stok setelah</small><b>${after.toLocaleString('id-ID')} ${escapeHtml(selectedProduct.unit)}</b></span>
                </div>
                <p class="stock-confirm-note">
                    Mutasi ini langsung mengubah saldo stok dan tidak dapat diedit.
                </p>
            `,
            showCancelButton: true,
            confirmButtonText: 'Ya, Posting',
            cancelButtonText: 'Periksa Lagi',
            confirmButtonColor: '#174c8e',
            reverseButtons: true,
            focusCancel: true,
        });

        if (!confirmation.isConfirmed) return;

        submitConfirmed = true;

        const submitButton = form.querySelector('[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
        }

        form.requestSubmit();
    });

    refresh();
}

function bindOpnameCreate() {
    const form = document.querySelector('[data-opname-create]');
    if (!form || form.dataset.bound === '1') return;
    form.dataset.bound = '1';

    let submitConfirmed = false;

    form.addEventListener('submit', async event => {
        if (submitConfirmed) return;

        event.preventDefault();

        if (!form.reportValidity()) return;

        const warehouse =
            form.querySelector('[name="warehouse_id"]')
                ?.selectedOptions?.[0]?.textContent?.trim() || '-';

        const date = form.querySelector('[name="opname_date"]')?.value || '-';

        const confirmation = await Swal.fire({
            icon: 'question',
            title: 'Buat daftar hitung?',
            html: `Snapshot stok gudang <b>${escapeHtml(warehouse)}</b>
                   akan dibuat untuk tanggal <b>${escapeHtml(date)}</b>.<br><br>
                   Hentikan transaksi di gudang tersebut sampai opname selesai.`,
            showCancelButton: true,
            confirmButtonText: 'Buat Daftar',
            cancelButtonText: 'Periksa Lagi',
            confirmButtonColor: '#174c8e',
            reverseButtons: true,
            focusCancel: true,
        });

        if (!confirmation.isConfirmed) return;

        submitConfirmed = true;
        form.requestSubmit();
    });
}

function bindOpnamePage() {
    const page = document.querySelector('[data-opname-page]');
    if (!page || page.dataset.bound === '1') return;
    page.dataset.bound = '1';

    const rows = [...page.querySelectorAll('.opname-row')];
    const searchInput = page.querySelector('#opname-search');
    const submitForm = page.querySelector('[data-opname-submit]');

    let submitConfirmed = false;

    function refreshRow(row) {
        const input = row.querySelector('.opname-physical');
        const badge = row.querySelector('.diff-pill');

        if (!input || !badge) return null;

        badge.classList.remove('minus', 'plus', 'even');

        if (input.value === '') {
            badge.textContent = '-';
            badge.classList.add('even');
            return null;
        }

        const difference =
            Number(input.value || 0) - Number(row.dataset.system || 0);

        badge.textContent =
            `${difference > 0 ? '+' : ''}${difference.toLocaleString('id-ID')}`;

        badge.classList.add(
            difference < 0 ? 'minus' : (difference > 0 ? 'plus' : 'even')
        );

        return difference;
    }

    function refreshSummary() {
        const differences = rows.map(refreshRow);

        const counted = differences.filter(value => value !== null).length;
        const changed = differences.filter(
            value => value !== null && value !== 0
        ).length;
        const total = differences.reduce(
            (sum, value) => sum + (value ?? 0),
            0
        );

        const countedElement = document.querySelector('#opname-counted');
        const changedElement = document.querySelector('#opname-diff-count');
        const totalElement = document.querySelector('#opname-diff-total');

        if (countedElement) {
            countedElement.textContent = counted.toLocaleString('id-ID');
        }

        if (changedElement) {
            changedElement.textContent = changed.toLocaleString('id-ID');
        }

        if (totalElement) {
            totalElement.textContent =
                `${total > 0 ? '+' : ''}${total.toLocaleString('id-ID')}`;
        }

        return { counted, changed, total };
    }

    page.addEventListener('input', event => {
        if (event.target.matches('.opname-physical')) refreshSummary();
    });

    searchInput?.addEventListener('input', () => {
        const keyword = normalizeSearch(searchInput.value);
        let visible = 0;

        rows.forEach(row => {
            const show = normalizeSearch(row.dataset.search || '')
                .includes(keyword);

            row.classList.toggle('d-none', !show);
            if (show) visible++;
        });

        const visibleElement = page.querySelector('#opname-visible');
        if (visibleElement) {
            visibleElement.textContent = `${visible} barang tampil`;
        }
    });

    submitForm?.addEventListener('submit', async event => {
        if (submitConfirmed) return;

        event.preventDefault();

        if (!submitForm.reportValidity()) return;

        const summary = refreshSummary();

        if (summary.counted !== rows.length) {
            await Swal.fire({
                icon: 'warning',
                title: 'Penghitungan belum lengkap',
                text: `${rows.length - summary.counted} barang belum mempunyai stok fisik.`,
                confirmButtonText: 'Lengkapi Data',
            });
            return;
        }

        const confirmation = await Swal.fire({
            icon: 'question',
            title: 'Ajukan stok opname?',
            html: `Terdapat <b>${summary.changed} barang berbeda</b>
                   dengan total selisih
                   <b>${summary.total > 0 ? '+' : ''}${summary.total.toLocaleString('id-ID')}</b>.<br><br>
                   Setelah diajukan, data tidak dapat diubah kecuali opname dibatalkan.`,
            showCancelButton: true,
            confirmButtonText: 'Ya, Ajukan',
            cancelButtonText: 'Periksa Lagi',
            confirmButtonColor: '#174c8e',
            reverseButtons: true,
            focusCancel: true,
        });

        if (!confirmation.isConfirmed) return;

        submitConfirmed = true;
        submitForm.requestSubmit();
    });

    refreshSummary();
}

function bindOpnameApproval() {
    document
        .querySelectorAll('form[data-opname-approve]:not([data-bound])')
        .forEach(form => {
            form.dataset.bound = '1';
            let submitConfirmed = false;

            form.addEventListener('submit', async event => {
                if (submitConfirmed) return;

                event.preventDefault();

                const items = Number(form.dataset.items || 0);
                const total = Number(form.dataset.total || 0);

                const confirmation = await Swal.fire({
                    icon: 'warning',
                    title: 'Setujui dan terapkan stok?',
                    html: `Opname <b>${escapeHtml(form.dataset.number || '')}</b>
                           akan mengubah saldo pada <b>${items} barang</b>
                           dengan total selisih
                           <b>${total > 0 ? '+' : ''}${total.toLocaleString('id-ID')}</b>.<br><br>
                           Tindakan ini tidak dapat dibatalkan.`,
                    showCancelButton: true,
                    confirmButtonText: 'Setujui & Terapkan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#198754',
                    reverseButtons: true,
                    focusCancel: true,
                });

                if (!confirmation.isConfirmed) return;

                submitConfirmed = true;
                form.requestSubmit();
            });
        });
}

function bindPurchaseReturn() {
    const form = document.querySelector('[data-purchase-return]');
    if (!form || form.dataset.bound === '1') return;
    form.dataset.bound = '1';

    const rows = [...form.querySelectorAll('.return-row')];
    const notesInput = form.querySelector('#return-notes');

    let submitConfirmed = false;

    function refresh() {
        let total = 0;

        rows.forEach(row => {
            const input = row.querySelector('.return-input');
            const quantity = Number(input?.value || 0);
            const factor = Number(row.dataset.factor || 1);
            const cost = Number(row.dataset.cost || 0);
            const subtotal = quantity * cost;

            const basePreview = row.querySelector('.base-preview');
            const lineTotal = row.querySelector('.line-return');

            if (basePreview) {
                basePreview.textContent =
                    (quantity * factor).toLocaleString('id-ID');
            }

            if (lineTotal) lineTotal.textContent = rupiah(subtotal);

            row.classList.toggle('selected', quantity > 0);
            total += subtotal;
        });

        const totalElement = form.querySelector('#return-total');
        if (totalElement) totalElement.textContent = rupiah(total);

        return total;
    }

    form.addEventListener('input', event => {
        if (event.target.matches('.return-input')) refresh();
    });

    form.querySelectorAll('[data-return-reason]').forEach(button => {
        button.addEventListener('click', () => {
            notesInput.value = button.dataset.returnReason || '';
            notesInput.focus();

            form.querySelectorAll('[data-return-reason]').forEach(chip => {
                chip.classList.toggle('is-selected', chip === button);
            });
        });
    });

    form.addEventListener('submit', async event => {
        if (submitConfirmed) return;

        event.preventDefault();

        if (!form.reportValidity()) return;

        const selectedRows = rows.filter(row => {
            return Number(row.querySelector('.return-input')?.value || 0) > 0;
        });

        if (!selectedRows.length) {
            await Swal.fire({
                icon: 'warning',
                title: 'Belum ada barang retur',
                text: 'Isi qty retur pada minimal satu barang.',
                confirmButtonText: 'Pilih Barang',
            });
            return;
        }

        const productRequirements = {};

        for (const row of selectedRows) {
            const input = row.querySelector('.return-input');
            const quantity = Number(input.value || 0);
            const maximum = Number(input.max || 0);
            const baseQuantity =
                quantity * Number(row.dataset.factor || 1);
            const productId = String(row.dataset.product || '');
            const availableStock = Number(row.dataset.stock || 0);

            if (quantity > maximum) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Qty retur terlalu besar',
                    text: `Retur ${row.dataset.name} melebihi sisa yang tersedia.`,
                    confirmButtonText: 'Periksa Qty',
                });
                input.focus();
                return;
            }

            if (!productRequirements[productId]) {
                productRequirements[productId] = {
                    required: 0,
                    available: availableStock,
                    name: row.dataset.name,
                };
            }

            productRequirements[productId].required += baseQuantity;
        }

        const stockConflict = Object.values(productRequirements).find(
            item => item.required > item.available
        );

        if (stockConflict) {
            await Swal.fire({
                icon: 'error',
                title: 'Stok gudang tidak mencukupi',
                html: `Retur <b>${escapeHtml(stockConflict.name)}</b>
                       membutuhkan <b>${stockConflict.required.toLocaleString('id-ID')}</b>
                       satuan dasar, sedangkan stok gudang hanya
                       <b>${stockConflict.available.toLocaleString('id-ID')}</b>.`,
                confirmButtonText: 'Periksa Stok',
            });
            return;
        }

        const total = refresh();

        const itemsHtml = selectedRows.map(row => {
            const quantity =
                Number(row.querySelector('.return-input').value || 0);
            const subtotal =
                quantity * Number(row.dataset.cost || 0);

            return `
                <div class="return-confirm-item">
                    <span>
                        <b>${escapeHtml(row.dataset.name || '')}</b>
                        <small>${quantity.toLocaleString('id-ID')} unit retur</small>
                    </span>
                    <strong>${rupiah(subtotal)}</strong>
                </div>
            `;
        }).join('');

        const confirmation = await Swal.fire({
            icon: 'warning',
            title: 'Simpan retur pembelian?',
            html: `
                <div class="return-confirm-review">
                    ${itemsHtml}
                    <div class="return-confirm-total">
                        <span>Total Retur</span>
                        <b>${rupiah(total)}</b>
                    </div>
                    <p>
                        Stok akan langsung berkurang dan retur tidak dapat diedit
                        atau dihapus.
                    </p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Ya, Simpan Retur',
            cancelButtonText: 'Periksa Lagi',
            confirmButtonColor: '#dc3545',
            reverseButtons: true,
            focusCancel: true,
        });

        if (!confirmation.isConfirmed) return;

        submitConfirmed = true;

        const submitButton = form.querySelector('[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
        }

        form.requestSubmit();
    });

    refresh();
}


function bindSaleReturn() {
    const form = document.querySelector('[data-sale-return]');
    if (!form || form.dataset.bound === '1') return;
    form.dataset.bound = '1';

    const rows = [...form.querySelectorAll('.sale-return-row')];
    const returnTotalElement = form.querySelector('#sale-return-total');
    const returnValueElement = form.querySelector('#sale-return-value-preview');
    const newNetElement = form.querySelector('#sale-return-new-net');
    const creditElement = form.querySelector('#sale-return-credit-preview');
    const refundElement = form.querySelector('#sale-return-refund-preview');
    const retainedPaidElement = form.querySelector('#sale-return-paid-preview');
    const balanceElement = form.querySelector('#sale-return-balance-preview');
    const methodPanel = form.querySelector('#sale-return-refund-method-panel');
    const methodSelect = form.querySelector('#sale-return-refund-method');
    const methodCaption = form.querySelector('#sale-return-refund-caption');
    const statusElement = form.querySelector('#sale-return-settlement-status');
    const notesInput = form.querySelector('#sale-return-notes');

    const saleTotal = Number(form.dataset.saleTotal || 0);
    const paidAmount = Number(form.dataset.paidAmount || 0);
    const previousReturns = Number(form.dataset.previousReturns || 0);
    const paymentMethods = decodeBase64Json(
        form.dataset.paymentMethods,
        {}
    );

    let submitConfirmed = false;

    function refreshRow(row) {
        const quantityInput = row.querySelector('.sale-return-quantity');
        const conditionSelect = row.querySelector('.sale-return-condition');
        const batchInput = row.querySelector('.sale-return-batch');
        const expiryInput = row.querySelector('.sale-return-expiry');
        const lineValueElement = row.querySelector('.sale-return-line-value');
        const basePreview = row.querySelector('.sale-return-base-preview');

        const quantity = Math.max(
            0,
            Number(quantityInput?.value || 0)
        );
        const price = Number(row.dataset.price || 0);
        const factor = Number(row.dataset.factor || 1);
        const condition = conditionSelect?.value || 'restock';
        const requiresExpiry = row.dataset.expiry === '1';
        const selected = quantity > 0;
        const restock = condition === 'restock';

        const lineValue = quantity * price;
        const baseQuantity = quantity * factor;

        if (lineValueElement) {
            lineValueElement.textContent = rupiah(lineValue);
        }

        if (basePreview) {
            basePreview.textContent =
                baseQuantity.toLocaleString('id-ID');
        }

        row.classList.toggle('is-selected', selected);
        row.classList.toggle('is-discard', selected && !restock);

        if (batchInput) {
            batchInput.required =
                selected && restock && requiresExpiry;
            batchInput.disabled =
                Boolean(quantityInput?.disabled) || !selected || !restock;
        }

        if (expiryInput) {
            expiryInput.required =
                selected && restock && requiresExpiry;
            expiryInput.disabled =
                Boolean(quantityInput?.disabled) || !selected || !restock;
        }

        return {
            row,
            quantityInput,
            quantity,
            price,
            factor,
            condition,
            requiresExpiry,
            lineValue,
            baseQuantity,
            batch: batchInput?.value?.trim() || '',
            expiry: expiryInput?.value || '',
            name: row.dataset.name || 'Barang',
            unit: row.dataset.unit || '',
            baseUnit: row.dataset.baseUnit || '',
        };
    }

    function calculate() {
        const items = rows.map(refreshRow);
        const returnValue = items.reduce(
            (sum, item) => sum + item.lineValue,
            0
        );

        const oldNetTotal = Math.max(
            0,
            saleTotal - previousReturns
        );

        const newNetTotal = Math.max(
            0,
            oldNetTotal - returnValue
        );

        const refundAmount = Math.max(
            0,
            paidAmount - newNetTotal
        );

        const retainedPaid = Math.max(
            0,
            paidAmount - refundAmount
        );

        const creditReduction = Math.max(
            0,
            returnValue - refundAmount
        );

        const balance = Math.max(
            0,
            newNetTotal - retainedPaid
        );

        if (returnTotalElement) {
            returnTotalElement.textContent = rupiah(returnValue);
        }

        if (returnValueElement) {
            returnValueElement.textContent = rupiah(returnValue);
        }

        if (newNetElement) {
            newNetElement.textContent = rupiah(newNetTotal);
        }

        if (creditElement) {
            creditElement.textContent = rupiah(creditReduction);
        }

        if (refundElement) {
            refundElement.textContent = rupiah(refundAmount);
        }

        if (retainedPaidElement) {
            retainedPaidElement.textContent = rupiah(retainedPaid);
        }

        if (balanceElement) {
            balanceElement.textContent = rupiah(balance);
        }

        const hasRefund = refundAmount > 0;

        if (methodSelect) {
            methodSelect.disabled = !hasRefund;
            methodSelect.required = hasRefund;
        }

        methodPanel?.classList.toggle('is-disabled', !hasRefund);

        if (methodCaption) {
            methodCaption.textContent = hasRefund
                ? `Pelanggan menerima ${rupiah(refundAmount)} melalui metode yang dipilih.`
                : 'Tidak ada uang keluar. Nilai retur digunakan untuk mengurangi piutang atau total transaksi.';
        }

        if (statusElement) {
            statusElement.classList.remove(
                'is-empty',
                'is-credit',
                'is-refund'
            );

            const icon = statusElement.querySelector('i');
            const title = statusElement.querySelector('strong');
            const description = statusElement.querySelector('small');

            if (returnValue <= 0) {
                statusElement.classList.add('is-empty');
                if (icon) icon.className =
                    'fa-solid fa-file-invoice-dollar';
                if (title) title.textContent =
                    'Belum ada barang dipilih';
                if (description) description.textContent =
                    'Isi qty retur untuk menghitung penyelesaian.';
            } else if (hasRefund) {
                statusElement.classList.add('is-refund');
                if (icon) icon.className =
                    'fa-solid fa-money-bill-transfer';
                if (title) title.textContent =
                    `Refund ${rupiah(refundAmount)}`;
                if (description) description.textContent =
                    creditReduction > 0
                        ? `${rupiah(creditReduction)} mengurangi piutang dan sisanya dikembalikan.`
                        : 'Seluruh nilai retur dikembalikan kepada pelanggan.';
            } else {
                statusElement.classList.add('is-credit');
                if (icon) icon.className =
                    'fa-solid fa-file-circle-minus';
                if (title) title.textContent =
                    `Pengurang piutang ${rupiah(creditReduction)}`;
                if (description) description.textContent =
                    'Tidak ada refund tunai atau non-tunai.';
            }
        }

        return {
            items,
            returnValue,
            oldNetTotal,
            newNetTotal,
            refundAmount,
            retainedPaid,
            creditReduction,
            balance,
        };
    }

    function validateCalculation(summary) {
        const selectedItems = summary.items.filter(
            item => item.quantity > 0
        );

        if (!selectedItems.length) {
            return 'Pilih minimal satu barang yang akan diretur.';
        }

        for (const item of selectedItems) {
            const maximum = Number(item.quantityInput?.max || 0);

            if (item.quantity > maximum) {
                return `Qty retur ${item.name} melebihi sisa yang tersedia.`;
            }

            if (
                item.condition === 'restock'
                && item.requiresExpiry
                && (!item.batch || !item.expiry)
            ) {
                return `Batch dan tanggal kedaluwarsa ${item.name} wajib diisi jika barang dikembalikan ke stok.`;
            }
        }

        if (summary.refundAmount > 0 && !methodSelect?.value) {
            return 'Pilih metode pengembalian dana.';
        }

        return null;
    }

    function confirmationHtml(summary) {
        const selectedItems = summary.items.filter(
            item => item.quantity > 0
        );

        const itemsHtml = selectedItems.map(item => `
            <div class="sale-return-confirm-item">
                <span>
                    <b>${escapeHtml(item.name)}</b>
                    <small>
                        ${item.quantity.toLocaleString('id-ID')}
                        ${escapeHtml(item.unit)}
                        · ${item.condition === 'restock'
                            ? 'Kembali ke stok'
                            : 'Tidak direstok'}
                    </small>
                </span>
                <strong>${rupiah(item.lineValue)}</strong>
            </div>
        `).join('');

        const methodLabel = summary.refundAmount > 0
            ? paymentMethods[methodSelect?.value]
                || methodSelect?.value
                || '-'
            : 'Pengurang piutang';

        return `
            <div class="sale-return-confirm-review">
                <section>
                    <header>
                        Barang Retur
                        <b>${selectedItems.length} item</b>
                    </header>
                    <div>${itemsHtml}</div>
                </section>

                <section class="sale-return-confirm-summary">
                    <span>
                        Nilai retur barang
                        <b>${rupiah(summary.returnValue)}</b>
                    </span>
                    <span>
                        Total transaksi baru
                        <b>${rupiah(summary.newNetTotal)}</b>
                    </span>
                    <span>
                        Pengurang piutang
                        <b>${rupiah(summary.creditReduction)}</b>
                    </span>
                    <span class="is-refund">
                        Refund aktual
                        <b>${rupiah(summary.refundAmount)}</b>
                    </span>
                    <span>
                        Metode penyelesaian
                        <b>${escapeHtml(methodLabel)}</b>
                    </span>
                    <span>
                        Piutang setelah retur
                        <b>${rupiah(summary.balance)}</b>
                    </span>
                </section>

                <p>
                    Retur langsung memengaruhi stok, pembayaran, piutang,
                    dan poin pelanggan. Transaksi tidak dapat diedit atau
                    dihapus.
                </p>
            </div>
        `;
    }

    form.addEventListener('input', event => {
        if (
            event.target.matches(
                '.sale-return-quantity,.sale-return-batch,.sale-return-expiry'
            )
        ) {
            calculate();
        }
    });

    form.addEventListener('change', event => {
        if (
            event.target.matches(
                '.sale-return-condition,#sale-return-refund-method'
            )
        ) {
            calculate();
        }
    });

    form.querySelectorAll('[data-sale-return-reason]').forEach(
        button => {
            button.addEventListener('click', () => {
                notesInput.value =
                    button.dataset.saleReturnReason || '';
                notesInput.focus();

                form
                    .querySelectorAll('[data-sale-return-reason]')
                    .forEach(chip => {
                        chip.classList.toggle(
                            'is-selected',
                            chip === button
                        );
                    });
            });
        }
    );

    form.addEventListener('submit', async event => {
        if (submitConfirmed) return;

        event.preventDefault();

        const summary = calculate();
        const error = validateCalculation(summary);

        if (!form.reportValidity()) return;

        if (error) {
            await Swal.fire({
                icon: 'warning',
                title: 'Retur belum lengkap',
                text: error,
                confirmButtonText: 'Periksa Data',
            });
            return;
        }

        const confirmation = await Swal.fire({
            icon: 'warning',
            title: 'Simpan retur penjualan?',
            html: confirmationHtml(summary),
            width: 720,
            showCancelButton: true,
            confirmButtonText:
                '<i class="fa-solid fa-arrow-rotate-left"></i> Simpan Retur',
            cancelButtonText: 'Periksa Lagi',
            confirmButtonColor: '#dc3545',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'sale-return-confirm-popup',
                htmlContainer: 'sale-return-confirm-container',
            },
        });

        if (!confirmation.isConfirmed) return;

        submitConfirmed = true;

        const submitButton = form.querySelector('[type="submit"]');

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
        }

        form.requestSubmit();
    });

    calculate();
}

function bindPageActions() {
    document.querySelectorAll('[data-print-page]:not([data-bound])').forEach(button => {
        button.dataset.bound = '1';
        button.addEventListener('click', () => window.print());
    });

    document.querySelectorAll('form[data-confirm]:not([data-bound])').forEach(form => {
        form.dataset.bound = '1';
        form.addEventListener('submit', async event => {
            if (form.dataset.confirmed === '1') return;
            event.preventDefault();
            const result = await Swal.fire({ icon: 'question', title: 'Konfirmasi tindakan', text: form.dataset.confirm, showCancelButton: true, confirmButtonText: 'Ya, lanjutkan', cancelButtonText: 'Batal', reverseButtons: true });
            if (result.isConfirmed) { form.dataset.confirmed = '1'; form.requestSubmit(); }
        });
    });
}

// Extend the existing lifecycle binding without global document handlers per page.
document.addEventListener('DOMContentLoaded', () => {
    bindSalePos();
    bindShiftPage();
    bindPurchaseForm();
    bindStockAdjustment();
    bindOpnameCreate();
    bindOpnamePage();
    bindOpnameApproval();
    bindPurchaseReturn();
    bindSaleReturn();
    bindPageActions();
    bindReceiptKeyboard();
    bindThermalReceipt();
});
document.addEventListener('livewire:navigated', () => {
    bindSalePos();
    bindShiftPage();
    bindPurchaseForm();
    bindStockAdjustment();
    bindOpnameCreate();
    bindOpnamePage();
    bindOpnameApproval();
    bindPurchaseReturn();
    bindSaleReturn();
    bindPageActions();
    bindReceiptKeyboard();
    bindThermalReceipt();
});
