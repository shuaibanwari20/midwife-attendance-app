@extends('admin.layout')

@section('title', \App\Support\PanelText::t('داشبورد امروز'))

@section('head')
    <meta http-equiv="refresh" content="30">
@endsection

@section('content')
<div class="page-title">
    <div>
        <h1>{{ \App\Support\PanelText::t('داشبورد امروز') }}</h1>
        <div class="sub">{{ $todayLabel }} — {{ $timeLabel }} (وقت کابل) · به‌روزرسانی خودکار هر 30 ثانیه</div>
    </div>
    <div class="actions">
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.map') }}">{{ \App\Support\PanelText::t('نقشه کلسترها') }}</a>
        <a class="btn btn-gold btn-sm" href="{{ route('admin.reports') }}">{{ \App\Support\PanelText::t('راپورها') }}</a>
    </div>
</div>

<div class="stats-row">
    <div class="stat-card stat-teal">
        <div class="icon-circle">👥</div>
        <div class="value">{{ \App\Support\DariDate::en($total) }}</div>
        <div class="label">{{ \App\Support\PanelText::t('کل قابله‌ها') }}</div>
    </div>
    <div class="stat-card stat-green">
        <div class="icon-circle">✓</div>
        <div class="value">{{ \App\Support\DariDate::en($counts['on_time']) }}</div>
        <div class="label">{{ \App\Support\PanelText::t('حاضر به‌وقت') }}</div>
    </div>
    <div class="stat-card stat-amber">
        <div class="icon-circle">⏱</div>
        <div class="value">{{ \App\Support\DariDate::en($counts['late']) }}</div>
        <div class="label">{{ \App\Support\PanelText::t('دیرکرد') }}</div>
    </div>
    <div class="stat-card stat-red">
        <div class="icon-circle">✕</div>
        <div class="value">{{ \App\Support\DariDate::en($counts['absent']) }}</div>
        <div class="label">{{ \App\Support\PanelText::t('غایب / هنوز نرسیده') }}</div>
    </div>
    <div class="stat-card stat-grey">
        <div class="icon-circle">🌴</div>
        <div class="value">{{ \App\Support\DariDate::en($counts['leave']) }}</div>
        <div class="label">{{ \App\Support\PanelText::t('رخصتی') }}</div>
    </div>
</div>

<div class="card">
    <h2>{{ \App\Support\PanelText::t('وضعیت زنده قابله‌ها') }}</h2>
    <div class="table-wrap">
        <table class="data">
            <thead>
            <tr>
                <th>{{ \App\Support\PanelText::t('کد') }}</th>
                <th>{{ \App\Support\PanelText::t('نام قابله') }}</th>
                <th>{{ \App\Support\PanelText::t('کلستر') }}</th>
                <th>{{ \App\Support\PanelText::t('ساعت ثبت حضور') }}</th>
                <th>{{ \App\Support\PanelText::t('فاصله از مرکز') }}</th>
                <th>{{ \App\Support\PanelText::t('وضعیت') }}</th>
                <th>{{ \App\Support\PanelText::t('کد پیگیری') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($rows as $row)
                @php($m = $row['midwife'])
                <tr>
                    <td class="num">{{ \App\Support\DariDate::en($m->code) }}</td>
                    <td style="font-weight: 700;">{{ $m->full_name }}</td>
                    <td>
                        @if($m->cluster)
                            <span class="chip chip-teal">{{ \App\Support\DariDate::en($m->cluster->code) }} — {{ $m->cluster->village }}</span>
                        @else
                            <span class="chip chip-grey">{{ \App\Support\PanelText::t('بدون کلستر') }}</span>
                        @endif
                    </td>
                    <td class="num">{{ $row['time'] }}</td>
                    <td class="num">{{ $row['distance'] }}</td>
                    <td>
                        @if($row['state'] === 'on_time')
                            <span class="chip chip-green"><span class="dot" style="background:#2FA36B"></span>{{ \App\Support\PanelText::t('حاضر به‌وقت') }}</span>
                        @elseif($row['state'] === 'late')
                            <span class="chip chip-amber"><span class="dot" style="background:#D69E2E"></span>دیرکرد {{ \App\Support\DariDate::en($row['attendance']->late_minutes) }} دقیقه</span>
                        @elseif($row['state'] === 'leave')
                            <span class="chip chip-grey"><span class="dot" style="background:#9AA8AB"></span>{{ \App\Support\PanelText::t('رخصتی') }}</span>
                        @elseif($row['state'] === 'friday')
                            <span class="chip chip-grey">{{ \App\Support\PanelText::t('روز رخصتی (جمعه)') }}</span>
                        @elseif($row['state'] === 'not_arrived')
                            <span class="chip chip-red"><span class="dot" style="background:#D9534F"></span>{{ \App\Support\PanelText::t('هنوز نرسیده') }}</span>
                        @elseif($row['state'] === 'before_join')
                            <span class="chip chip-grey"><span class="dot" style="background:#9AA8AB"></span>{{ \App\Support\PanelText::t('تازه ثبت شده') }}</span>
                        @else
                            <span class="chip chip-red"><span class="dot" style="background:#D9534F"></span>{{ \App\Support\PanelText::t('غایب') }}</span>
                        @endif
                    </td>
                    <td class="num">{{ $row['attendance'] ? \App\Support\DariDate::en($row['attendance']->tracking_code) : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
