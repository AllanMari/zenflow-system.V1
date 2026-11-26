@extends('layouts.admin')

@section('title', 'Database Backup')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Database Backup &amp; Restore</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Create, verify, download, and safely restore private backups of the ZenFlow database. Arbitrary SQL uploads are not permitted.
            </p>
        </div>

        <form id="backup-create-form" method="POST" action="{{ route('admin.database-backup.create') }}">
            @csrf
            <button id="backup-create-button" type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white shadow transition hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <i data-lucide="database-backup" class="h-4 w-4"></i>
                <span id="backup-create-label">Create Backup</span>
            </button>
        </form>
    </div>

    @if(!empty($restoreState))
        @php
            $rState = $restoreState['state'] ?? 'UNKNOWN';
            $isRecovery = $rState === 'RECOVERY_REQUIRED';
            $isFailed = $rState === 'FAILED';
        @endphp
        <div class="rounded-2xl border p-4 text-xs leading-5 {{ $isRecovery ? 'border-red-300 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-950/40 dark:text-red-200' : ($isFailed ? 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200' : 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200') }}">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <span class="font-bold uppercase tracking-wide">Last Restore Status: {{ $rState }}</span>
                    @if(!empty($restoreState['backup_id']))
                        <span class="ml-2 font-mono">Target Backup: {{ $restoreState['backup_id'] }}</span>
                    @endif
                    @if(!empty($restoreState['safety_backup_id']))
                        <span class="ml-2 font-mono">Safety Backup: {{ $restoreState['safety_backup_id'] }}</span>
                    @endif
                </div>
                @if(!empty($maintenanceActive))
                    <span class="rounded-full bg-red-600 px-2.5 py-0.5 text-[10px] font-bold uppercase text-white">Maintenance Guard Active</span>
                @endif
            </div>
            @if(!empty($restoreState['error_message']))
                <p class="mt-1.5 font-medium">{{ $restoreState['error_message'] }}</p>
            @endif
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Backup History</h2>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                Only completely verified backups are listed. Incomplete exports are discarded.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3">Created</th>
                        <th class="px-5 py-3">Backup ID</th>
                        <th class="px-5 py-3">Source DB</th>
                        <th class="px-5 py-3">Size</th>
                        <th class="px-5 py-3">Tables</th>
                        <th class="px-5 py-3">Rows</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($backups as $backup)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-200">
                                {{ \Carbon\Carbon::parse($backup['created_at'])->format('M j, Y g:i A') }}
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-600 dark:text-slate-300">
                                {{ $backup['backup_id'] }}
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-600 dark:text-slate-300">
                                {{ $backup['source_database'] ?? 'unknown' }}
                            </td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                                {{ number_format(((int) ($backup['file_size'] ?? 0)) / 1048576, 2) }} MB
                            </td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                                {{ $backup['table_count'] ?? count($backup['included_tables'] ?? []) }}
                            </td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                                {{ number_format((int) ($backup['exported_row_count'] ?? 0)) }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    {{ ucfirst($backup['verification_status'] ?? 'verified') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.database-backup.download', $backup['backup_id']) }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                        <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                        Download
                                    </a>

                                    @if(($backup['verification_status'] ?? '') === 'verified')
                                        <button type="button"
                                                class="open-restore-modal inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-900/50"
                                                data-action="{{ route('admin.database-backup.restore', $backup['backup_id']) }}"
                                                data-backup-id="{{ $backup['backup_id'] }}"
                                                data-backup-date="{{ \Carbon\Carbon::parse($backup['created_at'])->format('M j, Y g:i A') }}">
                                            <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                                            Restore Database
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                    <i data-lucide="database" class="h-6 w-6 text-slate-400"></i>
                                </div>
                                <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-200">No backups yet</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Create a verified backup before relying on this feature.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/30 dark:text-amber-200">
        <div class="flex items-start gap-2">
            <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <p>
                Backups are stored outside the public web root, verified with SHA-256 and HMAC, and audit events are appended to a private log.
                Deployment migrations must not run while a backup is being created.
            </p>
        </div>
    </div>
</div>

{{-- Compact Restore Confirmation Modal (matches ZenFlow max-w-sm / 360px-384px dialog sizing) --}}
<div id="restoreConfirmModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="restoreConfirmTitle">
    <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
        <h2 id="restoreConfirmTitle" class="text-base font-bold text-slate-900 dark:text-white">Confirm Database Restore</h2>
        <div class="mt-2.5 space-y-1.5 text-xs leading-5 text-slate-600 dark:text-slate-300">
            <p>
                Backup: <span id="restoreModalBackupId" class="font-mono font-semibold text-slate-800 dark:text-slate-100"></span>
                <span class="text-slate-400">·</span>
                <span id="restoreModalBackupDate"></span>
            </p>
            <p class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 font-medium text-red-800 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200">
                Current database contents will be replaced. A safety backup is created first, and the app will enter maintenance mode.
            </p>
        </div>

        <form id="restoreConfirmForm" method="POST" action="" class="mt-4 space-y-3">
            @csrf
            <div>
                <label for="restoreAdminPassword" class="mb-1 block text-xs font-bold text-slate-700 dark:text-slate-200">Admin Password</label>
                <input id="restoreAdminPassword"
                       type="password"
                       name="admin_password"
                       autocomplete="current-password"
                       required
                       placeholder="Enter your Admin password"
                       class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            </div>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button"
                        id="restoreCancelButton"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    Cancel
                </button>
                <button type="submit"
                        id="restoreSubmitButton"
                        class="rounded-lg bg-red-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <span id="restoreSubmitLabel">Restore Database</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('backup-create-form');
    const button = document.getElementById('backup-create-button');
    const label = document.getElementById('backup-create-label');
    if (!form || !button) return;

    form.addEventListener('submit', async function (event) {
        if (button.dataset.confirmed === '1') return;
        event.preventDefault();

        const result = await Swal.fire({
            title: 'Create database backup?',
            text: 'This exports the current ZenFlow database into private storage. Do not run deployments while the export is in progress.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Create Backup',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#0f766e',
            background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#fff' : '#374151',
            customClass: { popup: 'rounded-2xl' }
        });

        if (!result.isConfirmed) return;

        button.dataset.confirmed = '1';
        button.disabled = true;
        label.textContent = 'Creating backup...';
        Swal.fire({
            title: 'Creating backup',
            text: 'Please keep this page open until the export finishes.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        form.submit();
    });

    const modal = document.getElementById('restoreConfirmModal');
    const restoreForm = document.getElementById('restoreConfirmForm');
    const backupIdEl = document.getElementById('restoreModalBackupId');
    const backupDateEl = document.getElementById('restoreModalBackupDate');
    const passwordInput = document.getElementById('restoreAdminPassword');
    const cancelBtn = document.getElementById('restoreCancelButton');
    const submitBtn = document.getElementById('restoreSubmitButton');
    const submitLabel = document.getElementById('restoreSubmitLabel');

    function closeRestoreModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (passwordInput) passwordInput.value = '';
        if (submitBtn) submitBtn.disabled = false;
        if (submitLabel) submitLabel.textContent = 'Restore Database';
    }

    document.querySelectorAll('.open-restore-modal').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!modal || !restoreForm) return;
            restoreForm.action = btn.dataset.action || '';
            if (backupIdEl) backupIdEl.textContent = btn.dataset.backupId || '';
            if (backupDateEl) backupDateEl.textContent = btn.dataset.backupDate || '';
            if (passwordInput) passwordInput.value = '';
            if (submitBtn) submitBtn.disabled = false;
            if (submitLabel) submitLabel.textContent = 'Restore Database';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (passwordInput) passwordInput.focus();
        });
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeRestoreModal);
    }

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeRestoreModal();
        });
    }

    if (restoreForm) {
        restoreForm.addEventListener('submit', function () {
            if (submitBtn) submitBtn.disabled = true;
            if (submitLabel) submitLabel.textContent = 'Restoring...';
        });
    }
})();
</script>
@endpush
