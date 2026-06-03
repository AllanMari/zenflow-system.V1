@extends('layouts.admin')

@section('title', 'Shift Templates')

@push('styles')
<style>
    :root { --bg-primary: #ffffff; --bg-secondary: #f8fafc; --bg-card: #ffffff; --bg-hover: #f1f5f9; --border-color: #e2e8f0; --text-primary: #0f172a; --text-secondary: #64748b; --text-muted: #94a3b8; --accent-teal: #0d9488; --accent-teal-light: #ccfbf1; --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05); --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
    .dark { --bg-primary: #0f172a; --bg-secondary: #1e293b; --bg-card: #1e293b; --bg-hover: #334155; --border-color: #334155; --text-primary: #f1f5f9; --text-secondary: #cbd5e1; --text-muted: #64748b; --accent-teal: #2dd4bf; --accent-teal-light: #134e4a; --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.3); --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.4); }
    .form-label { display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 6px; }
    .form-input, .form-select { width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-size: 14px; transition: all 0.2s; }
    .form-input:focus, .form-select:focus { outline: none; border-color: var(--accent-teal); box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
    .btn { padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
    .btn-primary { background: var(--accent-teal); color: white; }
    .btn-primary:hover { background: #0f766e; }
    .btn-secondary { background: var(--bg-secondary); color: var(--text-secondary); border: 1px solid var(--border-color); }
    .btn-secondary:hover { background: var(--bg-hover); }
    .btn-danger { background: #ef4444; color: white; }
    .btn-danger:hover { background: #dc2626; }
    .template-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 24px; transition: all 0.2s; }
    .template-card:hover { box-shadow: var(--shadow-md); }
    .template-preview { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-top: 12px; }
    .preview-cell { padding: 8px; border-radius: 8px; text-align: center; font-size: 11px; font-weight: 600; }
    .preview-cell.work { background: #d1fae5; color: #065f46; }
    .dark .preview-cell.work { background: rgba(16, 185, 129, 0.15); color: #34d399; }
    .preview-cell.off { background: #fee2e2; color: #991b1b; }
    .dark .preview-cell.off { background: rgba(239, 68, 68, 0.1); color: #f87171; }
    .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 50; padding: 20px; }
    .modal-backdrop.active { display: flex; }
    .modal-box { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-md); }
    .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; }
    .modal-body { padding: 24px; }
    .modal-footer { padding: 16px 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 10px; }
    .schedule-table { width: 100%; border-collapse: collapse; }
    .schedule-table th, .schedule-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); }
    .schedule-table th { font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; }
    .schedule-table td { color: var(--text-primary); }
    .day-off-row td { opacity: 0.4; text-decoration: line-through; }
    .day-toggle { width: 20px; height: 20px; accent-color: var(--accent-teal); cursor: pointer; }
    .template-data { display: none; }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-12">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight" style="color: var(--text-primary);">Shift Templates</h1>
            <p class="mt-2 text-base" style="color: var(--text-secondary);">Create reusable weekly schedule patterns</p>
        </div>
        <button type="button" onclick="openCreateModal()" class="btn btn-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Template
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($templates as $t)
        <div class="template-card">
            <textarea class="template-data" id="template-data-{{ $t->id }}">{{ json_encode($t->pattern) }}</textarea>

            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-bold text-lg" style="color: var(--text-primary);">{{ $t->name }}</h3>
                    <p class="text-xs mt-1" style="color: var(--text-muted);">Created {{ $t->created_at->diffForHumans() }} by {{ $t->creator?->first_name ?? 'System' }}</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="loadEditModal({{ $t->id }})" class="btn btn-secondary !p-2" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <form action="{{ route('admin.shift-templates.destroy', $t) }}" method="POST" class="inline" onsubmit="return confirm('Delete this template?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger !p-2" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>
            <div class="template-preview">
                @php $dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; @endphp
                @foreach($dayLabels as $dow => $label)
                    @php $day = $t->pattern[$dow] ?? null; @endphp
                    <div class="preview-cell {{ ($day['is_day_off'] ?? true) ? 'off' : 'work' }}">
                        {{ $label }}
                        @if(!($day['is_day_off'] ?? true))
                            <div class="text-[10px] font-normal mt-1">{{ $day['start_time'] ?? '' }}&ndash;{{ $day['end_time'] ?? '' }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    @if($templates->isEmpty())
    <div class="text-center py-16" style="color: var(--text-muted);">
        <svg class="w-16 h-16 mx-auto mb-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <p class="text-lg font-medium">No templates yet</p>
        <p class="text-sm mt-1">Create your first reusable schedule pattern above</p>
    </div>
    @endif
</div>

{{-- CREATE / EDIT MODAL --}}
<div class="modal-backdrop" id="templateModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="font-bold text-lg" style="color: var(--text-primary);" id="modalTitle">New Template</h3>
            <button type="button" onclick="closeModal('templateModal')" class="text-2xl" style="color: var(--text-muted);">&times;</button>
        </div>
        <form id="templateForm" action="{{ route('admin.shift-templates.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="template_id" id="templateId" value="">
            <div class="modal-body space-y-5">
                <div>
                    <label class="form-label">Template Name</label>
                    <input type="text" name="name" id="templateName" required placeholder="e.g., Standard Spa Week" class="form-input">
                </div>
                <div>
                    <label class="form-label">Weekly Schedule</label>
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Working</th>
                                <th>Start</th>
                                <th>End</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; @endphp
                            @foreach($dayLabels as $dow => $label)
                            <tr id="row-{{ $dow }}">
                                <td style="font-weight: 600;">{{ $label }}</td>
                                <td>
                                    <input type="checkbox" class="day-toggle" id="cb-{{ $dow }}" checked onchange="toggleRow({{ $dow }})">
                                </td>
                                <td>
                                    <input type="time" name="pattern[{{ $dow }}][start_time]" id="start-{{ $dow }}" value="09:00" step="1800" class="form-input">
                                </td>
                                <td>
                                    <input type="time" name="pattern[{{ $dow }}][end_time]" id="end-{{ $dow }}" value="18:00" step="1800" class="form-input">
                                </td>
                            </tr>
                            <input type="hidden" name="pattern[{{ $dow }}][is_day_off]" value="0" id="off-{{ $dow }}">
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('templateModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Template</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }
    document.querySelectorAll('.modal-backdrop').forEach(function(m) {
        m.addEventListener('click', function(e) { if (e.target === m) closeModal(m.id); });
    });

    function toggleRow(dow) {
        var row = document.getElementById('row-' + dow);
        var cb = document.getElementById('cb-' + dow);
        var start = document.getElementById('start-' + dow);
        var end = document.getElementById('end-' + dow);
        var off = document.getElementById('off-' + dow);

        if (cb.checked) {
            row.classList.remove('day-off-row');
            start.disabled = false;
            end.disabled = false;
            off.value = '0';
        } else {
            row.classList.add('day-off-row');
            start.disabled = true;
            end.disabled = true;
            off.value = '1';
        }
    }

    function resetModal() {
        document.getElementById('templateName').value = '';
        document.getElementById('templateId').value = '';
        for (var i = 0; i < 7; i++) {
            document.getElementById('cb-' + i).checked = true;
            toggleRow(i);
            document.getElementById('start-' + i).value = '09:00';
            document.getElementById('end-' + i).value = '18:00';
        }
    }

    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'New Template';
        document.getElementById('templateForm').action = '{{ route("admin.shift-templates.store") }}';
        document.getElementById('formMethod').value = 'POST';
        resetModal();
        openModal('templateModal');
    }

    function loadEditModal(templateId) {
        var dataEl = document.getElementById('template-data-' + templateId);
        if (!dataEl) return;

        var pattern;
        try {
            pattern = JSON.parse(dataEl.value) || {};
        } catch (e) {
            pattern = {};
        }

        var card = dataEl.closest('.template-card');
        var nameEl = card.querySelector('h3');
        document.getElementById('templateName').value = nameEl ? nameEl.textContent.trim() : '';

        document.getElementById('modalTitle').textContent = 'Edit Template';
        document.getElementById('templateForm').action = '{{ url("/admin/shift-templates") }}/' + templateId;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('templateId').value = templateId;

        for (var i = 0; i < 7; i++) {
            var day = pattern[i] || pattern[String(i)] || { is_day_off: true, start_time: '09:00', end_time: '18:00' };

            var isOff = true;
            if (day.is_day_off !== undefined && day.is_day_off !== null) {
                isOff = (day.is_day_off === true || day.is_day_off === 1 || day.is_day_off === '1' || day.is_day_off === 'true');
            }

            document.getElementById('cb-' + i).checked = !isOff;
            toggleRow(i);

            if (!isOff) {
                document.getElementById('start-' + i).value = day.start_time || '09:00';
                document.getElementById('end-' + i).value = day.end_time || '18:00';
            }
        }

        openModal('templateModal');
    }
</script>
@endpush