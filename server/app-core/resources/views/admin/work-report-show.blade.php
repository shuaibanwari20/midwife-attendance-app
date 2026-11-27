@extends('admin.layout')

@section('title', \App\Support\PanelText::t('جزئیات راپور ماهانه'))

@section('content')
@php
    $chipFor = ['approved' => 'chip-green', 'submitted' => 'chip-amber', 'returned' => 'chip-red', 'draft' => 'chip-teal'];
@endphp
<div class="page-title">
    <div>
        <h1>{{ $report->midwife->full_name }} — {{ \App\Support\PanelText::t('راپور') }} {{ \App\Support\DariDate::monthYear($monthDate) }}</h1>
        <div class="sub">
            {{ $report->midwife->cluster ? $report->midwife->cluster->displayLabel() : '' }}
            · <span class="chip {{ $chipFor[$report->status] ?? 'chip-grey' }}">{{ \App\Support\PanelText::t(\App\Models\MonthlyWorkReport::STATUS_LABELS[$report->status] ?? $report->status) }}</span>
            @if($report->submitted_at)
                · {{ \App\Support\PanelText::t('ارسال‌شده') }}: {{ \App\Support\DariDate::full($report->submitted_at) }}
            @endif
        </div>
    </div>
    <div class="actions">
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.workReports', ['month' => $report->month]) }}">{{ \App\Support\PanelText::t('بازگشت به فهرست') }}</a>
    </div>
</div>

@foreach($photoKinds as $kind)
<div class="card">
    <h2>{{ \App\Support\PanelText::t($photoLabels[$kind]) }}</h2>
    @if($report->photoPath($kind))
        <a href="{{ route('admin.workReports.photo', [$report->id, $kind]) }}" target="_blank">
            <img src="{{ route('admin.workReports.photo', [$report->id, $kind]) }}" alt="{{ $kind }}" style="max-width: 520px; width: 100%; border-radius: 10px; border: 1px solid #ddd;">
        </a>
        <div style="margin-top: 8px;">
            <a class="btn btn-ghost btn-sm" href="{{ route('admin.workReports.photo', [$report->id, $kind]) }}" target="_blank">{{ \App\Support\PanelText::t('بازکردن عکس کامل') }}</a>
        </div>
    @else
        <p style="color: #b3261e; font-weight: 700;">{{ \App\Support\PanelText::t('این عکس هنوز فرستاده نشده است.') }}</p>
    @endif
</div>
@endforeach

@if($report->status === 'submitted')
<div class="card">
    <h2>{{ \App\Support\PanelText::t('بررسی دفتر') }}</h2>
    <form method="POST" action="{{ route('admin.workReports.approve', $report->id) }}" style="margin-bottom: 16px;">
        @csrf
        <button type="submit" class="btn btn-primary">{{ \App\Support\PanelText::t('تأیید راپور') }} ✓</button>
    </form>
    <form method="POST" action="{{ route('admin.workReports.return', $report->id) }}">
        @csrf
        <label style="display:block; font-weight:700; margin-bottom:6px;">{{ \App\Support\PanelText::t('برگشت برای اصلاح — نظر شما به قابله می‌رسد:') }}</label>
        <textarea name="note" class="form-control" rows="3" required maxlength="1000" placeholder="{{ \App\Support\PanelText::t('علت برگشت و اصلاح لازم را بنویسید…') }}"></textarea>
        <button type="submit" class="btn btn-ghost" style="margin-top: 10px;">{{ \App\Support\PanelText::t('برگشت‌دادن راپور') }} ↩</button>
    </form>
</div>
@endif

@if($report->status === 'returned' && $report->supervisor_note)
<div class="alert alert-err">{{ \App\Support\PanelText::t('نظر دفتر:') }} {{ $report->supervisor_note }}</div>
@endif
@if($report->status === 'approved')
<div class="alert alert-ok">{{ \App\Support\PanelText::t('این راپور تأیید شده است.') }} @if($report->reviewed_at) ({{ \App\Support\DariDate::full($report->reviewed_at) }}) @endif</div>
@endif
@endsection
