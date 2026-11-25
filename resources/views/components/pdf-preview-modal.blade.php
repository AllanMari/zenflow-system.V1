@props([
    'reportKey',
    'reportTitle' => null,
])

@php
    $pdfSchema = app(\App\Support\Pdf\PdfLayoutResolver::class)->buildFrontendSchema($reportKey);
    if ($reportTitle) {
        $pdfSchema['reportLabel'] = $reportTitle;
    }
    $isAdminUser = auth()->user()?->isAdmin() ?? false;
    $pdfSchema['canManageDefaults'] = $isAdminUser;
    $pdfSchema['saveDefaultUrl'] = $isAdminUser ? route('admin.pdf-defaults.save', ['reportKey' => $reportKey]) : null;
    $pdfSchema['resetDefaultUrl'] = $isAdminUser ? route('admin.pdf-defaults.reset', ['reportKey' => $reportKey]) : null;
    $pdfSchema['csrfToken'] = csrf_token();
@endphp

@once
<script>
    (function () {
        if (window.zenflowPdfPreview) {
            return;
        }

        window.zenflowPdfPreview = {
            open(options) {
                window.dispatchEvent(new CustomEvent('zenflow:open-pdf-preview', {
                    detail: options || {}
                }));
            }
        };

        const registerAlpineComponent = () => {
            if (!window.Alpine) return;

            window.Alpine.data('zenflowPdfPreviewModal', (initialSchema) => ({
                schema: initialSchema,
                open: false,
                mobileTab: 'preview',
                loading: false,
                pendingDebounce: false,
                savingDefault: false,
                defaultNotice: null,
                showAdvanced: false,
                error: null,
                blobUrl: null,
                streamBaseUrl: '',
                downloadBaseUrl: '',
                downloadFilename: '',
                extraParams: {},
                abortController: null,
                debounceTimer: null,
                uniformMargins: false,
                lastUnit: 'mm',
                form: {
                    preset: '',
                    paper: 'a4',
                    unit: 'mm',
                    width: 210,
                    height: 297,
                    orientation: 'portrait',
                    marginTop: 9.26,
                    marginRight: 9.26,
                    marginBottom: 9.26,
                    marginLeft: 9.26,
                    scalePercent: 100,
                    baseFontSizePx: 10,
                    repeatTableHeaders: true,
                    showPageNumbers: true,
                    footerText: '',
                    contentFrameEnabled: false,
                    contentFrameWidth: 90,
                    contentFrameAlignment: 'top-center',
                    contentFrameOffsetX: 0,
                    contentFrameOffsetY: 0,
                },

                init() {
                    this.applyDefaults(this.schema.defaults);

                    window.addEventListener('zenflow:open-pdf-preview', (event) => {
                        const detail = event.detail || {};
                        if (detail.reportKey && detail.reportKey !== this.schema.reportKey) {
                            return;
                        }
                        this.openWith(detail);
                    });
                },

                openWith(detail) {
                    this.streamBaseUrl = detail.streamUrl || '';
                    this.downloadBaseUrl = detail.downloadUrl || detail.streamUrl || '';
                    this.downloadFilename = detail.downloadFilename || `${this.schema.reportKey.replace(/_/g, '-')}.pdf`;
                    this.extraParams = detail.extraParams || {};
                    this.error = null;
                    this.pendingDebounce = false;
                    this.mobileTab = 'preview';
                    this.open = true;
                    document.body.classList.add('overflow-hidden');
                    this.fetchPreview(true);
                },

                close() {
                    this.open = false;
                    document.body.classList.remove('overflow-hidden');
                    if (this.debounceTimer) {
                        clearTimeout(this.debounceTimer);
                        this.debounceTimer = null;
                    }
                    this.pendingDebounce = false;
                    if (this.abortController) {
                        this.abortController.abort();
                        this.abortController = null;
                    }
                    this.loading = false;
                    this.revokeBlob();
                },

                revokeBlob() {
                    if (this.blobUrl) {
                        URL.revokeObjectURL(this.blobUrl);
                        this.blobUrl = null;
                    }
                },

                applyDefaults(cfg) {
                    const margins = cfg.margins_mm || {};
                    const frame = cfg.content_frame || {};
                    const unit = cfg.unit || 'mm';
                    const factors = { mm: 1, cm: 10, in: 25.4 };
                    const factor = factors[unit] || 1;
                    const precision = unit === 'mm' ? 10 : 100;
                    const rawW = Number(cfg.width_mm || 210);
                    const rawH = Number(cfg.height_mm || 297);
                    this.lastUnit = unit;
                    this.form = {
                        preset: cfg.preset || Object.keys(this.schema.presets)[0] || 'standard_report',
                        paper: cfg.paper || 'a4',
                        unit: unit,
                        width: (cfg.paper === 'custom' && factor !== 1) ? Math.round((rawW / factor) * precision) / precision : rawW,
                        height: (cfg.paper === 'custom' && factor !== 1) ? Math.round((rawH / factor) * precision) / precision : rawH,
                        orientation: cfg.orientation || 'portrait',
                        marginTop: Number(margins.top ?? 9.26),
                        marginRight: Number(margins.right ?? 9.26),
                        marginBottom: Number(margins.bottom ?? 9.26),
                        marginLeft: Number(margins.left ?? 9.26),
                        scalePercent: Number(cfg.scale_percent ?? 100),
                        baseFontSizePx: Number(cfg.base_font_size_px ?? 10),
                        repeatTableHeaders: Boolean(cfg.repeat_table_headers ?? true),
                        showPageNumbers: Boolean(cfg.show_page_numbers ?? true),
                        footerText: cfg.footer_text || '',
                        contentFrameEnabled: Boolean(frame.enabled ?? false),
                        contentFrameWidth: Number(frame.width_mm ?? 90),
                        contentFrameAlignment: frame.alignment || 'top-center',
                        contentFrameOffsetX: Number(frame.offset_x_mm ?? 0),
                        contentFrameOffsetY: Number(frame.offset_y_mm ?? 0),
                    };
                },

                onPresetChange() {
                    const preset = this.schema.presets[this.form.preset];
                    if (!preset) {
                        this.schedulePreview(80);
                        return;
                    }

                    this.form.paper = preset.paper || 'a4';
                    this.form.orientation = preset.orientation || 'portrait';
                    if (preset.margins_mm) {
                        this.form.marginTop = Number(preset.margins_mm.top ?? 9.26);
                        this.form.marginRight = Number(preset.margins_mm.right ?? 9.26);
                        this.form.marginBottom = Number(preset.margins_mm.bottom ?? 9.26);
                        this.form.marginLeft = Number(preset.margins_mm.left ?? 9.26);
                    }
                    const frame = preset.content_frame || {};
                    this.form.contentFrameEnabled = Boolean(frame.enabled ?? false);
                    this.form.contentFrameWidth = Number(frame.width_mm ?? 90);
                    this.form.contentFrameAlignment = frame.alignment || 'top-center';
                    this.form.contentFrameOffsetX = Number(frame.offset_x_mm ?? 0);
                    this.form.contentFrameOffsetY = Number(frame.offset_y_mm ?? 0);

                    this.form.scalePercent = Number(preset.scale_percent ?? 100);
                    this.form.baseFontSizePx = Number(preset.base_font_size_px ?? 10);
                    this.form.repeatTableHeaders = Boolean(preset.repeat_table_headers ?? true);
                    this.form.showPageNumbers = Boolean(preset.show_page_numbers ?? true);

                    if (this.form.paper !== 'custom') {
                        this.syncDimensionsFromPaper();
                    }

                    this.schedulePreview(80);
                },

                onPaperChange() {
                    if (this.form.paper === 'custom') {
                        const dims = this.currentDimensionsMm();
                        this.form.unit = 'mm';
                        this.lastUnit = 'mm';
                        this.form.width = dims.width;
                        this.form.height = dims.height;
                    } else {
                        this.syncDimensionsFromPaper();
                    }
                    this.schedulePreview(80);
                },

                onUnitChange(newUnit) {
                    const factors = { mm: 1, cm: 10, in: 25.4 };
                    const oldFactor = factors[this.lastUnit] || 1;
                    const newFactor = factors[newUnit] || 1;
                    if (oldFactor !== newFactor) {
                        const wMm = Number(this.form.width || 210) * oldFactor;
                        const hMm = Number(this.form.height || 297) * oldFactor;
                        const precision = newUnit === 'mm' ? 10 : 100;
                        this.form.width = Math.round((wMm / newFactor) * precision) / precision;
                        this.form.height = Math.round((hMm / newFactor) * precision) / precision;
                    }
                    this.lastUnit = newUnit;
                    this.schedulePreview(120);
                },

                syncDimensionsFromPaper() {
                    const p = this.schema.papers[this.form.paper];
                    if (!p) return;
                    if (!p.supports_orientation) {
                        this.form.orientation = 'portrait';
                    }
                    this.form.unit = 'mm';
                    this.lastUnit = 'mm';
                    this.form.width = Number(p.width_mm);
                    this.form.height = Number(p.height_mm);
                },

                setOrientation(orient) {
                    if (!this.supportsOrientation) return;
                    if (this.form.orientation === orient) return;
                    this.form.orientation = orient;
                    if (this.form.paper === 'custom') {
                        const w = Number(this.form.width || 0);
                        const h = Number(this.form.height || 0);
                        if ((orient === 'landscape' && w < h) || (orient === 'portrait' && w > h)) {
                            this.form.width = h;
                            this.form.height = w;
                        }
                    }
                    this.schedulePreview(60);
                },

                applyMarginPreset(mm) {
                    const val = Number(mm);
                    this.form.marginTop = val;
                    this.form.marginRight = val;
                    this.form.marginBottom = val;
                    this.form.marginLeft = val;
                    this.schedulePreview(80);
                },

                onUniformMarginInput(val) {
                    if (this.uniformMargins) {
                        const n = Number(val);
                        this.form.marginTop = n;
                        this.form.marginRight = n;
                        this.form.marginBottom = n;
                        this.form.marginLeft = n;
                    }
                    this.schedulePreview(320);
                },

                resetTemporaryChanges() {
                    this.applyDefaults(this.schema.defaults);
                    this.schedulePreview(60);
                },

                get supportsOrientation() {
                    if (this.form.paper === 'custom') return true;
                    const p = this.schema.papers[this.form.paper];
                    return p ? Boolean(p.supports_orientation) : true;
                },

                currentDimensionsMm() {
                    const minW = Number(this.schema.bounds?.width_mm?.min ?? 40);
                    const maxW = Number(this.schema.bounds?.width_mm?.max ?? 600);
                    const minH = Number(this.schema.bounds?.height_mm?.min ?? 40);
                    const maxH = Number(this.schema.bounds?.height_mm?.max ?? 2000);

                    if (this.form.paper === 'custom') {
                        const factor = this.form.unit === 'cm' ? 10 : (this.form.unit === 'in' ? 25.4 : 1);
                        let w = Math.max(minW, Math.min(maxW, Number(this.form.width || 210) * factor));
                        let h = Math.max(minH, Math.min(maxH, Number(this.form.height || 297) * factor));
                        if (this.form.orientation === 'landscape' && w < h) {
                            [w, h] = [h, w];
                        } else if (this.form.orientation === 'portrait' && w > h) {
                            [w, h] = [h, w];
                        }
                        return { width: Math.round(w * 10) / 10, height: Math.round(h * 10) / 10 };
                    }
                    const p = this.schema.papers[this.form.paper] || { width_mm: 210, height_mm: 297 };
                    let w = Number(p.width_mm);
                    let h = Number(p.height_mm);
                    if (this.form.orientation === 'landscape' && w < h) {
                        [w, h] = [h, w];
                    } else if (this.form.orientation === 'portrait' && w > h) {
                        [w, h] = [h, w];
                    }
                    return { width: w, height: h };
                },

                get canExport() {
                    return Boolean(this.blobUrl) && !this.loading && !this.pendingDebounce;
                },

                get isStalePreview() {
                    return Boolean(this.blobUrl) && Boolean(this.error) && !this.loading;
                },

                get scaleMin() {
                    return Number(this.schema.bounds?.scale_percent?.min ?? 50);
                },

                get scaleMax() {
                    return Number(this.schema.bounds?.scale_percent?.max ?? 150);
                },

                get activeSummaryLabel() {
                    const dims = this.currentDimensionsMm();
                    const orient = this.form.orientation === 'landscape' ? 'Landscape' : 'Portrait';
                    return `${dims.width} × ${dims.height} mm • ${orient} • ${this.form.scalePercent}%`;
                },

                buildQueryParams(actionValue) {
                    const params = new URLSearchParams();
                    params.set('action', actionValue);
                    params.set('pdf_preset', this.form.preset);
                    params.set('pdf_paper', this.form.paper);
                    params.set('pdf_orientation', this.form.orientation);
                    if (this.form.paper === 'custom') {
                        params.set('pdf_unit', this.form.unit);
                        params.set('pdf_width', String(this.form.width));
                        params.set('pdf_height', String(this.form.height));
                    }
                    params.set('pdf_margin_top', String(this.form.marginTop));
                    params.set('pdf_margin_right', String(this.form.marginRight));
                    params.set('pdf_margin_bottom', String(this.form.marginBottom));
                    params.set('pdf_margin_left', String(this.form.marginLeft));
                    params.set('pdf_scale', String(this.form.scalePercent));
                    params.set('pdf_base_font_size', String(this.form.baseFontSizePx));
                    params.set('pdf_repeat_headers', this.form.repeatTableHeaders ? '1' : '0');
                    params.set('pdf_show_page_numbers', this.form.showPageNumbers ? '1' : '0');
                    if (this.form.footerText && this.form.footerText.trim() !== '') {
                        params.set('pdf_footer_text', this.form.footerText.trim());
                    }
                    params.set('pdf_frame_enabled', this.form.contentFrameEnabled ? '1' : '0');
                    if (this.form.contentFrameEnabled) {
                        params.set('pdf_frame_width', String(this.form.contentFrameWidth));
                        params.set('pdf_frame_alignment', this.form.contentFrameAlignment);
                        params.set('pdf_frame_offset_x', String(this.form.contentFrameOffsetX));
                        params.set('pdf_frame_offset_y', String(this.form.contentFrameOffsetY));
                    }

                    Object.entries(this.extraParams || {}).forEach(([k, v]) => {
                        if (v !== null && v !== undefined && v !== '') {
                            params.set(k, String(v));
                        }
                    });

                    return params;
                },

                buildTargetUrl(baseUrl, actionValue) {
                    const url = new URL(baseUrl, window.location.origin);
                    const layoutParams = this.buildQueryParams(actionValue);
                    layoutParams.forEach((value, key) => {
                        url.searchParams.set(key, value);
                    });
                    return url.toString();
                },

                get resolvedDownloadUrl() {
                    if (!this.downloadBaseUrl) return '#';
                    return this.buildTargetUrl(this.downloadBaseUrl, 'download');
                },

                schedulePreview(delayMs = 300) {
                    if (!this.open) return;
                    if (this.debounceTimer) {
                        clearTimeout(this.debounceTimer);
                    }
                    this.pendingDebounce = true;
                    this.debounceTimer = setTimeout(() => {
                        this.debounceTimer = null;
                        this.pendingDebounce = false;
                        this.fetchPreview(false);
                    }, delayMs);
                },

                async fetchPreview(immediate = false) {
                    if (!this.streamBaseUrl) return;
                    if (this.debounceTimer) {
                        clearTimeout(this.debounceTimer);
                        this.debounceTimer = null;
                    }
                    this.pendingDebounce = false;
                    if (this.abortController) {
                        this.abortController.abort();
                    }

                    const controller = new AbortController();
                    this.abortController = controller;
                    this.loading = true;
                    this.error = null;

                    const url = this.buildTargetUrl(this.streamBaseUrl, 'stream');

                    try {
                        const response = await fetch(url, {
                            method: 'GET',
                            credentials: 'same-origin',
                            signal: controller.signal,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/pdf',
                            },
                        });

                        if (!response.ok) {
                            throw new Error(`Failed to generate PDF (HTTP ${response.status}).`);
                        }

                        const contentType = response.headers.get('content-type') || '';
                        if (!contentType.toLowerCase().includes('application/pdf')) {
                            throw new Error('Server did not return a valid PDF document.');
                        }

                        const blob = await response.blob();
                        if (controller.signal.aborted) return;

                        const nextUrl = URL.createObjectURL(blob);
                        const previousUrl = this.blobUrl;
                        this.blobUrl = nextUrl;

                        if (previousUrl) {
                            setTimeout(() => URL.revokeObjectURL(previousUrl), 1000);
                        }
                    } catch (err) {
                        if (err && err.name === 'AbortError') {
                            return;
                        }
                        this.error = (err && err.message) ? err.message : 'Unable to load PDF preview.';
                    } finally {
                        if (this.abortController === controller) {
                            this.loading = false;
                            this.abortController = null;
                        }
                    }
                },

                async saveAsSystemDefault() {
                    if (!this.schema.canManageDefaults || !this.schema.saveDefaultUrl || this.savingDefault) return;
                    this.savingDefault = true;
                    this.defaultNotice = null;
                    this.error = null;

                    try {
                        const payload = {
                            preset: this.form.preset,
                            paper: this.form.paper,
                            unit: this.form.unit,
                            width: this.form.width,
                            height: this.form.height,
                            orientation: this.form.orientation,
                            margins_mm: {
                                top: this.form.marginTop,
                                right: this.form.marginRight,
                                bottom: this.form.marginBottom,
                                left: this.form.marginLeft,
                            },
                            content_frame: {
                                enabled: this.form.contentFrameEnabled,
                                width_mm: this.form.contentFrameWidth,
                                alignment: this.form.contentFrameAlignment,
                                offset_x_mm: this.form.contentFrameOffsetX,
                                offset_y_mm: this.form.contentFrameOffsetY,
                            },
                            scale_percent: this.form.scalePercent,
                            base_font_size_px: this.form.baseFontSizePx,
                            repeat_table_headers: this.form.repeatTableHeaders,
                            show_page_numbers: this.form.showPageNumbers,
                            footer_text: this.form.footerText || null,
                        };

                        const res = await fetch(this.schema.saveDefaultUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.schema.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(payload),
                        });

                        if (!res.ok) {
                            throw new Error(`Failed to save default (HTTP ${res.status}).`);
                        }

                        const data = await res.json();
                        if (data.schema) {
                            this.schema = Object.assign({}, this.schema, data.schema);
                            this.applyDefaults(this.schema.defaults);
                        }
                        this.defaultNotice = data.message || 'Saved as system default.';
                        setTimeout(() => { this.defaultNotice = null; }, 3500);
                    } catch (err) {
                        this.error = (err && err.message) ? err.message : 'Unable to save default settings.';
                    } finally {
                        this.savingDefault = false;
                    }
                },

                async resetSystemDefault() {
                    if (!this.schema.canManageDefaults || !this.schema.resetDefaultUrl || this.savingDefault) return;
                    this.savingDefault = true;
                    this.defaultNotice = null;
                    this.error = null;

                    try {
                        const res = await fetch(this.schema.resetDefaultUrl, {
                            method: 'DELETE',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.schema.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!res.ok) {
                            throw new Error(`Failed to reset default (HTTP ${res.status}).`);
                        }

                        const data = await res.json();
                        if (data.schema) {
                            this.schema = Object.assign({}, this.schema, data.schema);
                            this.applyDefaults(this.schema.defaults);
                        }
                        this.defaultNotice = data.message || 'Reset to system default.';
                        setTimeout(() => { this.defaultNotice = null; }, 3500);
                        this.fetchPreview(true);
                    } catch (err) {
                        this.error = (err && err.message) ? err.message : 'Unable to reset default settings.';
                    } finally {
                        this.savingDefault = false;
                    }
                },

                downloadCurrentPdf() {
                    if (!this.canExport) return;
                    const a = document.createElement('a');
                    a.href = this.blobUrl;
                    a.download = this.downloadFilename || `${this.schema.reportKey.replace(/_/g, '-')}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                },

                printCurrentPdf() {
                    if (!this.canExport) return;
                    const frame = this.$refs.pdfFrame;
                    if (frame && frame.contentWindow) {
                        try {
                            frame.contentWindow.focus();
                            frame.contentWindow.print();
                            return;
                        } catch (e) {}
                    }
                    if (this.blobUrl) {
                        window.open(this.blobUrl, '_blank', 'noopener');
                    }
                },
            }));
        };

        if (window.Alpine) {
            registerAlpineComponent();
        } else {
            document.addEventListener('alpine:init', registerAlpineComponent);
        }
    })();
</script>
@endonce

<div
    x-data="zenflowPdfPreviewModal(@js($pdfSchema))"
    x-show="open"
    x-cloak
    @click="close()"
    @keydown.escape.window="open && close()"
    class="fixed inset-0 z-[90] flex items-center justify-center p-2 sm:p-4 bg-slate-950/70 backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
    :aria-label="schema.reportLabel + ' PDF Preview'"
>
    <div
        @click.stop
        class="w-[96vw] sm:w-[94vw] h-[93vh] max-w-[1600px] rounded-2xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 shadow-2xl flex flex-col overflow-hidden"
    >
        {{-- Modal Top Bar --}}
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 sm:px-5 py-3 border-b border-gray-200 dark:border-slate-800 bg-gray-50/80 dark:bg-slate-900">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-teal-600/10 dark:bg-teal-500/15 text-teal-700 dark:text-teal-300 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white truncate" x-text="schema.reportLabel + ' — PDF Preview'"></h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="activeSummaryLabel"></p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                {{-- Mobile / Tablet Tab Switcher --}}
                <div class="inline-flex lg:hidden rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-0.5 text-xs font-semibold">
                    <button
                        type="button"
                        @click="mobileTab = 'preview'"
                        :class="mobileTab === 'preview' ? 'bg-slate-900 text-white dark:bg-teal-600' : 'text-gray-600 dark:text-gray-300'"
                        class="px-2.5 py-1 rounded-md transition"
                    >
                        Preview
                    </button>
                    <button
                        type="button"
                        @click="mobileTab = 'settings'"
                        :class="mobileTab === 'settings' ? 'bg-slate-900 text-white dark:bg-teal-600' : 'text-gray-600 dark:text-gray-300'"
                        class="px-2.5 py-1 rounded-md transition"
                    >
                        Settings
                    </button>
                </div>

                <button
                    type="button"
                    @click="close()"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:text-gray-800 hover:bg-gray-200/70 dark:text-gray-400 dark:hover:text-white dark:hover:bg-slate-800 transition"
                    title="Close preview"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Split Workspace: Left Settings Panel + Right Live PDF Preview --}}
        <div class="flex-1 min-h-0 flex flex-col lg:flex-row">
            {{-- LEFT: Configuration Controls (Reference Layout) --}}
            <aside
                :class="mobileTab === 'settings' ? 'flex' : 'hidden lg:flex'"
                class="w-full flex-1 min-h-0 lg:flex-none lg:w-[320px] lg:shrink-0 flex-col border-b lg:border-b-0 lg:border-r border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-y-auto"
            >
                <div class="p-4 space-y-5 flex-1">
                    {{-- Preset --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Document Preset
                        </label>
                        <select
                            x-model="form.preset"
                            @change="onPresetChange()"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500"
                        >
                            <template x-for="(preset, key) in schema.presets" :key="key">
                                <option :value="key" x-text="preset.label"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Paper Size --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Paper Size
                        </label>
                        <select
                            x-model="form.paper"
                            @change="onPaperChange()"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500"
                        >
                            <template x-for="(paper, key) in schema.papers" :key="key">
                                <option :value="key" x-text="`${paper.label} — ${paper.width_mm} × ${paper.height_mm} mm`"></option>
                            </template>
                            <option value="custom">Custom Dimensions...</option>
                        </select>
                    </div>

                    {{-- Contextual Custom Dimensions --}}
                    <div
                        x-show="form.paper === 'custom'"
                        x-transition
                        class="p-3 rounded-xl bg-gray-50 dark:bg-slate-800/70 border border-gray-200 dark:border-slate-700 space-y-2.5"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">Custom Size</span>
                            <select
                                :value="form.unit"
                                @change="form.unit = $event.target.value; onUnitChange($event.target.value)"
                                class="rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-xs text-gray-800 dark:text-gray-100 px-2 py-1"
                            >
                                <option value="mm">mm</option>
                                <option value="cm">cm</option>
                                <option value="in">in</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] text-gray-500 dark:text-gray-400 mb-1">Width</label>
                                <input
                                    type="number"
                                    step="0.1"
                                    min="1"
                                    x-model.number="form.width"
                                    @input="schedulePreview(350)"
                                    class="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-xs text-gray-900 dark:text-gray-100 px-2.5 py-1.5"
                                >
                            </div>
                            <div>
                                <label class="block text-[11px] text-gray-500 dark:text-gray-400 mb-1">Height</label>
                                <input
                                    type="number"
                                    step="0.1"
                                    min="1"
                                    x-model.number="form.height"
                                    @input="schedulePreview(350)"
                                    class="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-xs text-gray-900 dark:text-gray-100 px-2.5 py-1.5"
                                >
                            </div>
                        </div>
                    </div>

                    {{-- Orientation Segmented Toggle (Reference Style) --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Orientation
                        </label>
                        <div class="grid grid-cols-2 rounded-lg border border-gray-300 dark:border-slate-700 overflow-hidden bg-gray-50 dark:bg-slate-800">
                            <button
                                type="button"
                                :disabled="!supportsOrientation"
                                @click="setOrientation('portrait')"
                                :class="form.orientation === 'portrait'
                                    ? 'bg-slate-700 text-white dark:bg-teal-600'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700'"
                                class="flex items-center justify-center gap-1.5 py-2 text-xs font-semibold transition disabled:opacity-50"
                            >
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="6" y="3" width="12" height="18" rx="1.5"/>
                                </svg>
                                Portrait
                            </button>
                            <button
                                type="button"
                                :disabled="!supportsOrientation"
                                @click="setOrientation('landscape')"
                                :class="form.orientation === 'landscape'
                                    ? 'bg-slate-700 text-white dark:bg-teal-600'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700'"
                                class="flex items-center justify-center gap-1.5 py-2 text-xs font-semibold border-l border-gray-300 dark:border-slate-700 transition disabled:opacity-50"
                            >
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="6" width="18" height="12" rx="1.5"/>
                                </svg>
                                Landscape
                            </button>
                        </div>
                    </div>

                    {{-- Margins (mm) --}}
                    <div class="pt-1 border-t border-gray-100 dark:border-slate-800">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Margins (mm)
                            </label>
                            <div class="flex items-center gap-1 text-[11px]">
                                <button type="button" @click="applyMarginPreset(5)" class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700">Narrow</button>
                                <button type="button" @click="applyMarginPreset(9.26)" class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700">Normal</button>
                                <button type="button" @click="applyMarginPreset(15)" class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700">Wide</button>
                            </div>
                        </div>

                        <label class="inline-flex items-center gap-2 text-[11px] text-gray-600 dark:text-gray-400 mb-2 cursor-pointer">
                            <input type="checkbox" x-model="uniformMargins" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            Sync all sides
                        </label>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] text-gray-500 dark:text-gray-400 mb-0.5">Top</label>
                                <input type="number" step="0.5" min="0" max="60" x-model.number="form.marginTop" @input="onUniformMarginInput($event.target.value)" class="w-full rounded-md border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-900 dark:text-gray-100 px-2.5 py-1.5">
                            </div>
                            <div>
                                <label class="block text-[11px] text-gray-500 dark:text-gray-400 mb-0.5">Bottom</label>
                                <input type="number" step="0.5" min="0" max="60" x-model.number="form.marginBottom" @input="onUniformMarginInput($event.target.value)" class="w-full rounded-md border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-900 dark:text-gray-100 px-2.5 py-1.5">
                            </div>
                            <div>
                                <label class="block text-[11px] text-gray-500 dark:text-gray-400 mb-0.5">Left</label>
                                <input type="number" step="0.5" min="0" max="60" x-model.number="form.marginLeft" @input="onUniformMarginInput($event.target.value)" class="w-full rounded-md border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-900 dark:text-gray-100 px-2.5 py-1.5">
                            </div>
                            <div>
                                <label class="block text-[11px] text-gray-500 dark:text-gray-400 mb-0.5">Right</label>
                                <input type="number" step="0.5" min="0" max="60" x-model.number="form.marginRight" @input="onUniformMarginInput($event.target.value)" class="w-full rounded-md border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-900 dark:text-gray-100 px-2.5 py-1.5">
                            </div>
                        </div>
                    </div>

                    {{-- Content Scale --}}
                    <div class="pt-1 border-t border-gray-100 dark:border-slate-800">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Content Scale</label>
                            <span class="text-xs font-bold text-teal-700 dark:text-teal-400" x-text="form.scalePercent + '%'"></span>
                        </div>
                        <input
                            type="range"
                            :min="scaleMin"
                            :max="scaleMax"
                            step="5"
                            x-model.number="form.scalePercent"
                            @input="schedulePreview(280)"
                            class="w-full accent-teal-600 cursor-pointer"
                        >
                        <div class="flex justify-between text-[10px] text-gray-400 mt-0.5">
                            <span x-text="scaleMin + '%'"></span>
                            <button type="button" @click="form.scalePercent = 100; schedulePreview(80)" class="text-teal-600 hover:underline">100% (Default)</button>
                            <span x-text="scaleMax + '%'"></span>
                        </div>
                    </div>

                    {{-- Contextual Content Frame Controls (e.g. Receipt on A4 or Custom) --}}
                    <div
                        x-show="form.contentFrameEnabled || schema.presets[form.preset]?.capabilities?.content_frame"
                        x-transition
                        class="p-3 rounded-xl bg-teal-50/60 dark:bg-teal-950/30 border border-teal-200 dark:border-teal-800/70 space-y-2.5"
                    >
                        <label class="flex items-center justify-between text-xs font-semibold text-teal-900 dark:text-teal-200 cursor-pointer">
                            <span>Content Frame</span>
                            <input
                                type="checkbox"
                                x-model="form.contentFrameEnabled"
                                @change="schedulePreview(80)"
                                class="rounded border-teal-400 text-teal-600 focus:ring-teal-500"
                            >
                        </label>

                        <div x-show="form.contentFrameEnabled" class="space-y-2">
                            <div>
                                <div class="flex justify-between text-[11px] text-gray-600 dark:text-gray-300 mb-0.5">
                                    <span>Content Width</span>
                                    <span class="font-semibold" x-text="form.contentFrameWidth + ' mm'"></span>
                                </div>
                                <input
                                    type="range"
                                    min="55"
                                    max="180"
                                    step="5"
                                    x-model.number="form.contentFrameWidth"
                                    @input="schedulePreview(280)"
                                    class="w-full accent-teal-600 cursor-pointer"
                                >
                            </div>

                            <div>
                                <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">Placement</label>
                                <select
                                    x-model="form.contentFrameAlignment"
                                    @change="schedulePreview(80)"
                                    class="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-xs text-gray-900 dark:text-gray-100 px-2.5 py-1.5"
                                >
                                    <option value="top-left">Top Left</option>
                                    <option value="top-center">Top Center</option>
                                    <option value="top-right">Top Right</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[11px] text-gray-500 dark:text-gray-400 mb-0.5">Offset X (mm)</label>
                                    <input type="number" step="1" min="-50" max="50" x-model.number="form.contentFrameOffsetX" @input="schedulePreview(300)" class="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-xs text-gray-900 dark:text-gray-100 px-2 py-1">
                                </div>
                                <div>
                                    <label class="block text-[11px] text-gray-500 dark:text-gray-400 mb-0.5">Offset Y (mm)</label>
                                    <input type="number" step="1" min="-50" max="50" x-model.number="form.contentFrameOffsetY" @input="schedulePreview(300)" class="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-xs text-gray-900 dark:text-gray-100 px-2 py-1">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Table & Advanced Layout Options --}}
                    <div class="pt-1 border-t border-gray-100 dark:border-slate-800 space-y-2.5">
                        <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input
                                type="checkbox"
                                x-model="form.repeatTableHeaders"
                                @change="schedulePreview(80)"
                                class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                            >
                            Repeat table headers on new pages
                        </label>

                        <button
                            type="button"
                            @click="showAdvanced = !showAdvanced"
                            class="w-full flex items-center justify-between text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-teal-700 dark:hover:text-teal-400 py-1"
                        >
                            <span>Advanced Footer & Font</span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="showAdvanced ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>

                        <div x-show="showAdvanced" x-transition class="space-y-2.5 pt-1">
                            <div>
                                <div class="flex justify-between text-[11px] text-gray-500 dark:text-gray-400 mb-0.5">
                                    <span>Base Font Size</span>
                                    <span class="font-semibold" x-text="form.baseFontSizePx + ' px'"></span>
                                </div>
                                <input type="range" min="6" max="16" step="1" x-model.number="form.baseFontSizePx" @input="schedulePreview(280)" class="w-full accent-teal-600 cursor-pointer">
                            </div>

                            <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="form.showPageNumbers"
                                    @change="schedulePreview(80)"
                                    class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                                >
                                Show page numbers (Page X of Y)
                            </label>

                            <div>
                                <label class="block text-[11px] text-gray-500 dark:text-gray-400 mb-0.5">Custom Footer Note</label>
                                <input
                                    type="text"
                                    maxlength="120"
                                    placeholder="Optional footer text..."
                                    x-model="form.footerText"
                                    @input="schedulePreview(350)"
                                    class="w-full rounded-md border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-gray-900 dark:text-gray-100 px-2.5 py-1.5"
                                >
                            </div>
                        </div>
                    </div>

                    {{-- Admin-Controlled Saved Default Notice --}}
                    <div
                        x-show="defaultNotice"
                        x-transition
                        class="px-3 py-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300"
                        x-text="defaultNotice"
                    ></div>
                </div>

                {{-- Panel Footer --}}
                <div class="p-3 border-t border-gray-200 dark:border-slate-800 bg-gray-50/70 dark:bg-slate-900 space-y-2">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="resetTemporaryChanges()"
                            class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 text-xs font-semibold text-gray-700 dark:text-gray-200 transition"
                        >
                            Reset
                        </button>
                        <button
                            type="button"
                            @click="fetchPreview(true)"
                            :disabled="loading"
                            class="flex-1 px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-xs font-semibold text-white transition disabled:opacity-60"
                        >
                            Refresh Preview
                        </button>
                    </div>

                    <template x-if="schema.canManageDefaults">
                        <div class="flex items-center gap-2 pt-1 border-t border-gray-200/80 dark:border-slate-800">
                            <button
                                type="button"
                                @click="saveAsSystemDefault()"
                                :disabled="savingDefault"
                                class="flex-1 px-2.5 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-[11px] font-semibold text-white transition disabled:opacity-50"
                            >
                                Save as Default
                            </button>
                            <button
                                type="button"
                                x-show="schema.hasSavedDefault"
                                @click="resetSystemDefault()"
                                :disabled="savingDefault"
                                class="px-2.5 py-1.5 rounded-lg border border-amber-300 dark:border-amber-700 text-[11px] font-semibold text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition disabled:opacity-50"
                            >
                                Reset Default
                            </button>
                        </div>
                    </template>
                </div>
            </aside>

            {{-- RIGHT: Live PDF Preview Viewport --}}
            <section
                :class="mobileTab === 'preview' ? 'flex' : 'hidden lg:flex'"
                class="flex-1 min-w-0 flex-col bg-gray-100 dark:bg-slate-950"
            >
                {{-- Preview Header & Actions --}}
                <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 border-b border-gray-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/90">
                    <div class="flex items-center gap-2 text-xs font-semibold text-gray-600 dark:text-gray-300">
                        <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <span>Live Preview</span>

                        <span
                            x-show="loading"
                            x-transition
                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 text-[11px] font-semibold"
                        >
                            <svg class="w-3 h-3 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                            </svg>
                            Generating PDF...
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="printCurrentPdf()"
                            :disabled="!canExport"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 text-xs font-semibold text-gray-700 dark:text-gray-200 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 6 2 18 2 18 9"/>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                                <rect x="6" y="14" width="12" height="8"/>
                            </svg>
                            Print PDF
                        </button>

                        <button
                            type="button"
                            @click="downloadCurrentPdf()"
                            :disabled="!canExport"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-xs font-semibold text-white shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 3v12"/>
                                <path d="M7 10l5 5 5-5"/>
                                <path d="M5 21h14"/>
                            </svg>
                            Download PDF
                        </button>
                    </div>
                </div>

                {{-- Error Banner (non-destructive: keeps previous PDF visible below) --}}
                <div
                    x-show="error"
                    x-transition
                    class="mx-4 mt-3 px-3.5 py-2.5 rounded-xl bg-red-50 dark:bg-red-950/60 border border-red-200 dark:border-red-800 flex items-center justify-between gap-3 text-xs text-red-700 dark:text-red-300"
                >
                    <div>
                        <span class="font-semibold" x-text="error"></span>
                        <span x-show="isStalePreview" class="ml-1 opacity-90">— Showing the last successful preview below.</span>
                    </div>
                    <button
                        type="button"
                        @click="fetchPreview(true)"
                        class="px-2.5 py-1 rounded-md bg-red-600 hover:bg-red-700 text-white font-semibold shrink-0"
                    >
                        Retry
                    </button>
                </div>

                {{-- PDF Iframe Container --}}
                <div class="relative flex-1 min-h-0 p-3 sm:p-4">
                    {{-- Initial Loading State (only when no prior PDF exists yet) --}}
                    <div
                        x-show="!blobUrl && loading"
                        class="w-full h-full rounded-xl border border-dashed border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 flex flex-col items-center justify-center gap-3 text-gray-500 dark:text-gray-400"
                    >
                        <svg class="w-8 h-8 animate-spin text-teal-600" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                        </svg>
                        <p class="text-sm font-medium">Generating actual PDF preview...</p>
                    </div>

                    {{-- Initial Failure State (when no successful preview exists yet) --}}
                    <div
                        x-show="!blobUrl && !loading && error"
                        class="w-full h-full rounded-xl border border-dashed border-red-300 dark:border-red-800 bg-white dark:bg-slate-900 flex flex-col items-center justify-center gap-3 text-center px-6"
                    >
                        <p class="text-sm font-semibold text-red-600 dark:text-red-400" x-text="error"></p>
                        <button
                            type="button"
                            @click="fetchPreview(true)"
                            class="px-3.5 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition"
                        >
                            Try Again
                        </button>
                    </div>

                    {{-- Actual Generated PDF via Blob URL --}}
                    <iframe
                        x-ref="pdfFrame"
                        x-show="blobUrl"
                        :src="blobUrl"
                        title="Generated PDF Preview"
                        class="w-full h-full rounded-xl border border-gray-200 dark:border-slate-800 bg-white shadow-sm"
                    ></iframe>

                    {{-- Non-destructive Loading Pill over existing preview --}}
                    <div
                        x-show="blobUrl && loading"
                        x-transition
                        class="absolute top-6 right-7 px-3 py-1.5 rounded-full bg-slate-900/85 text-white text-xs font-semibold shadow-lg flex items-center gap-2 pointer-events-none"
                    >
                        <svg class="w-3.5 h-3.5 animate-spin text-teal-400" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                        </svg>
                        Updating preview...
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
