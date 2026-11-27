@extends('admin.layout')

@section('title', \App\Support\PanelText::t('راپور ماهانهٔ کاری'))

@section('content')
@php
    $chipFor = ['approved' => 'chip-green', 'submitted' => 'chip-amber', 'returned' => 'chip-red', 'draft' => 'chip-teal', 'missing' => 'chip-grey'];
@endphp
<div class="page-title">
    <div>
        <h1>{{ \App\Support\PanelText::t('راپورهای ماهانهٔ کاری قابله‌ها') }}</h1>
        <div class="sub">{{ \App\Support\PanelText::t('واکسیناسیون، OPD و تنظیم خانواده') }} — {{ \App\Support\DariDate::monthYear($monthDate) }}</div>
    </div>
    <form method="GET" action="{{ route('admin.workReports') }}" class="actions">
        <input class="form-control num" type="month" name="month" value="{{ $month }}" style="max-width: 190px;">
        <button type="submit" class="btn btn-primary btn-sm">{{ \App\Support\PanelText::t('نمایش') }}</button>
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.workReports.export', ['month' => $month]) }}">{{ \App\Support\PanelText::t('خروجی Excel') }}</a>
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.workReports.print', ['month' => $month]) }}" target="_blank">{{ \App\Support\PanelText::t('چاپ راپور وضعیت') }}</a>
    </form>
</div>

<div class="stats-row">
    <div class="stat-card stat-green">
        <div class="icon-circle">✓</div>
        <div class="value num">{{ \App\Support\DariDate::en($counts['approved']) }}</div>
        <div class="label">{{ \App\Support\PanelText::t('تأییدشده') }}</div>
    </div>
    <div class="stat-card stat-amber">
        <div class="icon-circle">⏳</div>
        <div class="value num">{{ \App\Support\DariDate::en($counts['submitted']) }}</div>
        <div class="label">{{ \App\Support\PanelText::t('در انتظار بررسی') }}</div>
    </div>
    <div class="stat-card stat-red">
        <div class="icon-circle">↩</div>
        <div class="value num">{{ \App\Support\DariDate::en($counts['returned']) }}</div>
        <div class="label">{{ \App\Support\PanelText::t('برگشت‌خورده') }}</div>
    </div>
    <div class="stat-card stat-grey">
        <div class="icon-circle">✉</div>
        <div class="value num">{{ \App\Support\DariDate::en($counts['missing'] + $counts['draft']) }}</div>
        <div class="label">{{ \App\Support\PanelText::t('نرسیده / پیش‌نویس') }}</div>
    </div>
</div>

<div class="card">
    <h2>{{ \App\Support\PanelText::t('وضعیت هر قابله') }}</h2>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>{{ \App\Support\PanelText::t('کد') }}</th>
                    <th>{{ \App\Support\PanelText::t('قابله') }}</th>
                    <th>{{ \App\Support\PanelText::t('کلستر') }}</th>
                    <th>{{ \App\Support\PanelText::t('وضعیت') }}</th>
                    <th>{{ \App\Support\PanelText::t('عکس‌ها') }}</th>
                    <th>{{ \App\Support\PanelText::t('عملیات') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($midwives as $mw)
                @php
                    $r = $reports->get($mw->id); $st = $r?->status ?? 'missing';
                    $photoCount = $r ? (int) !!$r->photo_opv + (int) !!$r->photo_opd + (int) !!$r->photo_fp : 0;
                @endphp
                <tr>
                    <td class="num">{{ \App\Support\DariDate::en($mw->code ?? '') }}</td>
                    <td style="font-weight: 700;">{{ $mw->full_name }}</td>
                    <td>{{ $mw->cluster ? $mw->cluster->displayLabel() : '—' }}</td>
                    <td><span class="chip {{ $chipFor[$st] ?? 'chip-grey' }}">{{ \App\Support\PanelText::t(\App\Models\MonthlyWorkReport::STATUS_LABELS[$st] ?? $st) }}</span></td>
                    <td class="num">{{ $r ? \App\Support\DariDate::en($photoCount) . ' / ' . \App\Support\DariDate::en(3) : '—' }}</td>
                    <td>
                        @if($r)
                            <a class="btn btn-ghost btn-sm" href="{{ route('admin.workReports.show', $r->id) }}">{{ \App\Support\PanelText::t('مشاهده') }}</a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
