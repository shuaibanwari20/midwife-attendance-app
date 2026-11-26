@extends('admin.layout')

@section('title', \App\Support\PanelText::t('اعلان‌ها و پیام‌ها'))

@section('content')
<div class="page-title">
    <div>
        <h1>{{ \App\Support\PanelText::t('اعلان‌ها و پیام‌های مدیریت') }}</h1>
        <div class="sub">{{ \App\Support\PanelText::t('ارسال اعلان به همه، یک کلستر یا یک قابله مشخص') }}</div>
    </div>
</div>

<div class="card gold-top">
    <h2>{{ \App\Support\PanelText::t('ارسال اعلان جدید') }}</h2>
    <form method="POST" action="{{ route('admin.notifications.store') }}">
        @csrf
        <div class="form-grid">
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('عنوان *') }}</label>
                <input class="form-control" name="title" value="{{ old('title') }}" required maxlength="160">
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('گیرنده *') }}</label>
                <select class="form-control" name="target" id="target-select" required>
                    <option value="all" @selected(old('target') === 'all')>{{ \App\Support\PanelText::t('همه قابله‌ها') }}</option>
                    <option value="cluster" @selected(old('target') === 'cluster')>{{ \App\Support\PanelText::t('یک کلستر') }}</option>
                    <option value="midwife" @selected(old('target') === 'midwife')>{{ \App\Support\PanelText::t('یک قابله') }}</option>
                </select>
            </div>
            <div id="cluster-field" style="display: {{ old('target') === 'cluster' ? 'block' : 'none' }};">
                <label class="field-label">{{ \App\Support\PanelText::t('کلستر') }}</label>
                <select class="form-control" name="cluster_id">
                    <option value="">{{ \App\Support\PanelText::t('انتخاب کنید...') }}</option>
                    @foreach($clusters as $cluster)
                        <option value="{{ $cluster->id }}">{{ \App\Support\DariDate::en($cluster->code) }} — {{ $cluster->village }}</option>
                    @endforeach
                </select>
            </div>
            <div id="midwife-field" style="display: {{ old('target') === 'midwife' ? 'block' : 'none' }};">
                <label class="field-label">{{ \App\Support\PanelText::t('قابله') }}</label>
                <select class="form-control" name="midwife_id">
                    <option value="">{{ \App\Support\PanelText::t('انتخاب کنید...') }}</option>
                    @foreach($midwives as $midwife)
                        <option value="{{ $midwife->id }}">{{ \App\Support\DariDate::en($midwife->code) }} — {{ $midwife->full_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="margin-top: 12px;">
            <label class="field-label">{{ \App\Support\PanelText::t('متن اعلان *') }}</label>
            <textarea class="form-control" name="body" required maxlength="2000">{{ old('body') }}</textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ \App\Support\PanelText::t('ارسال اعلان') }}</button>
        </div>
    </form>
</div>

<div class="card">
    <h2>{{ \App\Support\PanelText::t('اعلان‌های ارسال‌شده') }}</h2>
    <div class="table-wrap">
        <table class="data">
            <thead>
            <tr>
                <th>{{ \App\Support\PanelText::t('عنوان') }}</th>
                <th>{{ \App\Support\PanelText::t('متن') }}</th>
                <th>{{ \App\Support\PanelText::t('گیرنده') }}</th>
                <th>{{ \App\Support\PanelText::t('تاریخ ارسال') }}</th>
                <th>{{ \App\Support\PanelText::t('توسط') }}</th>
                <th>{{ \App\Support\PanelText::t('عملیات') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($notifications as $n)
                <tr>
                    <td style="font-weight: 700;">{{ $n->title }}</td>
                    <td class="small" style="max-width: 380px;">{{ $n->body }}</td>
                    <td>
                        @if($n->target === 'all')
                            <span class="chip chip-teal">{{ \App\Support\PanelText::t('همه قابله‌ها') }}</span>
                        @elseif($n->target === 'cluster')
                            <span class="chip chip-gold">کلستر {{ \App\Support\DariDate::en($n->cluster->code ?? '') }} — {{ $n->cluster->village ?? '' }}</span>
                        @else
                            <span class="chip chip-green">{{ $n->midwife->full_name ?? '—' }}</span>
                        @endif
                    </td>
                    <td class="num">{{ \App\Support\DariDate::dayMonthYear($n->created_at) }} — {{ \App\Support\DariDate::time24($n->created_at) }}</td>
                    <td>{{ $n->creator->name ?? 'سیستم' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.notifications.destroy', $n->id) }}" class="inline-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('این اعلان حذف شود؟');">{{ \App\Support\PanelText::t('حذف') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted text-center">{{ \App\Support\PanelText::t('اعلانی ثبت نشده است.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const targetSelect = document.getElementById('target-select');
    function toggleTargetFields() {
        document.getElementById('cluster-field').style.display = targetSelect.value === 'cluster' ? 'block' : 'none';
        document.getElementById('midwife-field').style.display = targetSelect.value === 'midwife' ? 'block' : 'none';
    }
    targetSelect.addEventListener('change', toggleTargetFields);
    toggleTargetFields();
</script>
@endsection
