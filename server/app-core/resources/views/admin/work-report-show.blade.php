@extends('admin.layout')

@section('title', \App\Support\PanelText::t('جزئیات راپور ماهانه'))

@section('content')
@php
    $chipFor = ['approved' => 'chip-green', 'submitted' => 'chip-amber', 'returned' => 'chip-red', 'draft' => 'chip-teal'];
    $p = $report->payload ?? [];
    $t = $report->totals ?? [];
    $val = fn ($k) => \App\Support\DariDate::en((string) (int) ($p[$k] ?? 0));
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

@if(!empty($warnings))
<div class="alert alert-err" style="margin-bottom: 14px;">
    {{ \App\Support\PanelText::t('هشدارهای سازگاری ارقام:') }}
    <ul>
        @foreach($warnings as $w)
            <li>{{ $w }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="stats-row">
    <div class="stat-card stat-teal">
        <div class="icon-circle">💉</div>
        <div class="value num">{{ \App\Support\DariDate::en($t['vacc_doses'] ?? 0) }}</div>
        <div class="label">{{ \App\Support\PanelText::t('مجموع دوز واکسین') }}</div>
    </div>
    <div class="stat-card stat-teal">
        <div class="icon-circle">🩺</div>
        <div class="value num">{{ \App\Support\DariDate::en($t['opd_total'] ?? 0) }}</div>
        <div class="label">{{ \App\Support\PanelText::t('مجموع مراجعین OPD') }}</div>
    </div>
    <div class="stat-card stat-amber">
        <div class="icon-circle">%</div>
        <div class="value num">{{ isset($t['opv_dropout_pct']) && $t['opv_dropout_pct'] !== null ? \App\Support\DariDate::en((string) $t['opv_dropout_pct']) : '—' }}</div>
        <div class="label">{{ \App\Support\PanelText::t('ریزش OPV1 تا OPV3') }}</div>
    </div>
    <div class="stat-card stat-teal">
        <div class="icon-circle">👨‍👩‍👧</div>
        <div class="value num">{{ \App\Support\DariDate::en(($t['fp_new'] ?? 0) + ($t['fp_revisit'] ?? 0)) }}</div>
        <div class="label">{{ \App\Support\PanelText::t('مراجعین تنظیم خانواده') }}</div>
    </div>
</div>

@foreach($sections as $section)
<div class="card">
    <h2>{{ \App\Support\PanelText::t($section['label']) }}</h2>
    <div class="table-wrap">
        <table class="data">
            <tbody>
            @foreach($section['fields'] as $key => $label)
                <tr>
                    <td style="font-weight: 600; width: 55%;">{{ \App\Support\PanelText::t($label) }}</td>
                    <td class="num">{{ $val($key) }}</td>
                </tr>
            @endforeach
            @if($section['label'] === 'موجودی و مصرف')
                @foreach(\App\Models\MonthlyWorkReport::STOCK_ITEMS as $item)
                    <tr>
                        <td style="font-weight: 700;">{{ \App\Support\PanelText::t('باقی‌ماندهٔ پایان ماه') }} — {{ $item }}</td>
                        <td class="num">{{ \App\Support\DariDate::en((string) (int) (($t['stock_closing'][$item] ?? 0))) }}</td>
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>
    </div>
</div>
@endforeach

@if(!empty($p['problems']))
<div class="card">
    <h2>{{ \App\Support\PanelText::t('مشکلات و نیازمندی‌ها') }}</h2>
    <p style="white-space: pre-wrap;">{{ $p['problems'] }}</p>
</div>
@endif

@if($report->register_photo)
<div class="card">
    <h2>{{ \App\Support\PanelText::t('عکس رجیستر کاغذی') }}</h2>
    <a href="{{ route('admin.workReports.photo', $report->id) }}" target="_blank">
        <img src="{{ route('admin.workReports.photo', $report->id) }}" alt="register" style="max-width: 420px; width: 100%; border-radius: 10px; border: 1px solid #ddd;">
    </a>
</div>
@endif

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
