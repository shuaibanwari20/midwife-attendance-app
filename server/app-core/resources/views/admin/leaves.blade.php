@extends('admin.layout')

@section('title', \App\Support\PanelText::t('درخواست‌های رخصتی'))

@section('content')
<div class="page-title">
    <div>
        <h1>{{ \App\Support\PanelText::t('رخصتی‌ها') }}</h1>
        <div class="sub">رخصتی رسمی دفتر را مستقیم بدهید و درخواست‌های قابله‌ها را تایید یا رد کنید — تاریخ‌ها میلادی</div>
    </div>
</div>

<div class="card gold-top">
    <h2>{{ \App\Support\PanelText::t('رخصتی رسمی دفتر — دادن رخصتی مستقیم') }}</h2>
    <form method="POST" action="{{ route('admin.leaves.manual') }}">
        @csrf
        <div class="form-grid">
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('نوع رخصتی *') }}</label>
                <select class="form-control" name="scope" id="leave-scope">
                    <option value="single" {{ old('scope') === 'single' ? 'selected' : '' }}>{{ \App\Support\PanelText::t('برای یک قابله') }}</option>
                    <option value="all" {{ old('scope') === 'all' ? 'selected' : '' }}>{{ \App\Support\PanelText::t('برای همه قابله‌ها (رخصتی عمومی)') }}</option>
                </select>
            </div>
            <div id="leave-midwife-wrap">
                <label class="field-label">{{ \App\Support\PanelText::t('قابله *') }}</label>
                <select class="form-control" name="midwife_id">
                    <option value="">{{ \App\Support\PanelText::t('— انتخاب قابله —') }}</option>
                    @foreach($midwives as $m)
                        <option value="{{ $m->id }}" {{ (string) old('midwife_id') === (string) $m->id ? 'selected' : '' }}>
                            {{ \App\Support\DariDate::en($m->code) }} — {{ $m->full_name }}{{ $m->cluster ? ' ('.\App\Support\DariDate::en($m->cluster->code).')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('از تاریخ *') }}</label>
                <input class="form-control num" type="date" name="from_date" value="{{ old('from_date') }}" required>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('تا تاریخ *') }}</label>
                <input class="form-control num" type="date" name="to_date" value="{{ old('to_date') }}" required>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('دلیل (اختیاری)') }}</label>
                <input class="form-control" name="reason" value="{{ old('reason') }}" placeholder="{{ \App\Support\PanelText::t('مثلاً: رخصتی رسمی عید') }}">
            </div>
        </div>
        <div class="form-hint">رخصتی رسمی فوراً تایید می‌شود و از موجودی رخصتی شخصی قابله کم نمی‌شود.</div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ \App\Support\PanelText::t('ثبت رخصتی رسمی') }}</button>
        </div>
    </form>
    <script>
        (function () {
            var scope = document.getElementById('leave-scope');
            var wrap = document.getElementById('leave-midwife-wrap');
            function sync() { wrap.style.display = scope.value === 'all' ? 'none' : ''; }
            scope.addEventListener('change', sync);
            sync();
        })();
    </script>
</div>

<div class="pill-tabs">
    <a href="{{ route('admin.leaves.index') }}" class="{{ $status === null ? 'active' : '' }}">{{ \App\Support\PanelText::t('همه') }}</a>
    <a href="{{ route('admin.leaves.index', ['status' => 'pending']) }}" class="{{ $status === 'pending' ? 'active' : '' }}">{{ \App\Support\PanelText::t('در انتظار') }}</a>
    <a href="{{ route('admin.leaves.index', ['status' => 'approved']) }}" class="{{ $status === 'approved' ? 'active' : '' }}">{{ \App\Support\PanelText::t('تایید شده') }}</a>
    <a href="{{ route('admin.leaves.index', ['status' => 'rejected']) }}" class="{{ $status === 'rejected' ? 'active' : '' }}">{{ \App\Support\PanelText::t('رد شده') }}</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
            <tr>
                <th>{{ \App\Support\PanelText::t('قابله') }}</th>
                <th>{{ \App\Support\PanelText::t('کلستر') }}</th>
                <th>{{ \App\Support\PanelText::t('از تاریخ') }}</th>
                <th>{{ \App\Support\PanelText::t('تا تاریخ') }}</th>
                <th>{{ \App\Support\PanelText::t('روزها') }}</th>
                <th>{{ \App\Support\PanelText::t('دلیل') }}</th>
                <th>{{ \App\Support\PanelText::t('نوع') }}</th>
                <th>{{ \App\Support\PanelText::t('وضعیت') }}</th>
                <th>{{ \App\Support\PanelText::t('عملیات') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($leaves as $leave)
                <tr>
                    <td style="font-weight: 700;">{{ $leave->midwife->full_name ?? '—' }}
                        <span class="muted small">{{ \App\Support\DariDate::en($leave->midwife->code ?? '') }}</span>
                    </td>
                    <td>{{ $leave->midwife->cluster ? \App\Support\DariDate::en($leave->midwife->cluster->code) : '—' }}</td>
                    <td class="num">{{ \App\Support\DariDate::dayMonthYear($leave->from_date) }}</td>
                    <td class="num">{{ \App\Support\DariDate::dayMonthYear($leave->to_date) }}</td>
                    <td class="num">{{ \App\Support\DariDate::en($leave->durationDays()) }}</td>
                    <td class="small">{{ $leave->reason }}</td>
                    <td>
                        @if($leave->source === 'office')
                            <span class="chip chip-green">{{ \App\Support\PanelText::t('رسمی دفتر') }}</span>
                        @else
                            <span class="chip chip-grey">{{ \App\Support\PanelText::t('درخواستی قابله') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($leave->status === 'pending')
                            <span class="chip chip-amber">{{ \App\Support\PanelText::t('در انتظار') }}</span>
                        @elseif($leave->status === 'approved')
                            <span class="chip chip-green">{{ \App\Support\PanelText::t('تایید شده') }}</span>
                        @else
                            <span class="chip chip-red">{{ \App\Support\PanelText::t('رد شده') }}</span>
                        @endif
                    </td>
                    <td style="white-space: nowrap;">
                        @if($leave->status === 'pending')
                            <form method="POST" action="{{ route('admin.leaves.approve', $leave->id) }}" class="inline-form">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm"
                                        onclick="return confirm('این رخصتی تایید شود؟ از باقی‌مانده رخصتی قابله کم می‌شود.');">{{ \App\Support\PanelText::t('تایید') }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.leaves.reject', $leave->id) }}" class="inline-form">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('این درخواست رد شود؟');">{{ \App\Support\PanelText::t('رد') }}</button>
                            </form>
                        @else
                            <span class="muted small">{{ $leave->review_note ?? '—' }}</span>
                        @endif
                        <form method="POST" action="{{ route('admin.leaves.delete', $leave->id) }}" class="inline-form">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm('این رخصتی حذف شود؟');">{{ \App\Support\PanelText::t('حذف') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="muted text-center">{{ \App\Support\PanelText::t('درخواستی یافت نشد.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
