@extends('admin.layout')

@section('title', \App\Support\PanelText::t('نظرسنجی ماهانه'))

@section('content')
<div class="page-title">
    <div>
        <h1>{{ \App\Support\PanelText::t('نظرسنجی ماهانهٔ قابله‌ها') }}</h1>
        <div class="sub">رضایت از اپ و مشکلات این ماه — {{ \App\Support\DariDate::monthYear($monthDate) }}</div>
    </div>
    <form method="GET" action="{{ route('admin.surveys') }}" class="actions">
        <input class="form-control num" type="month" name="month" value="{{ $month }}" style="max-width: 190px;">
        <button type="submit" class="btn btn-primary btn-sm">{{ \App\Support\PanelText::t('نمایش') }}</button>
    </form>
</div>

<div class="stats-row">
    <div class="stat-card stat-teal">
        <div class="icon-circle">✎</div>
        <div class="value num">{{ \App\Support\DariDate::en($responses->count()) }} / {{ \App\Support\DariDate::en($totalMidwives) }}</div>
        <div class="label">{{ \App\Support\PanelText::t('پاسخ‌دهنده از کل قابله‌ها') }}</div>
    </div>
    <div class="stat-card stat-amber">
        <div class="icon-circle">★</div>
        <div class="value num">{{ $avgSatisfaction !== null ? \App\Support\DariDate::en($avgSatisfaction) : '—' }}</div>
        <div class="label">{{ \App\Support\PanelText::t('میانگین رضایت (از 5)') }}</div>
    </div>
    <div class="stat-card stat-grey">
        <div class="icon-circle">✉</div>
        <div class="value num">{{ \App\Support\DariDate::en($responses->filter(fn ($r) => trim((string) $r->comment) !== '')->count()) }}</div>
        <div class="label">{{ \App\Support\PanelText::t('نظر / پیشنهاد نوشته‌شده') }}</div>
    </div>
</div>

<div class="card">
    <h2>{{ \App\Support\PanelText::t('مشکلات گزارش‌شده') }}</h2>
    <div class="table-wrap">
        <table class="data">
            <tbody>
            @foreach($problemCounts as $p)
                <tr>
                    <td style="font-weight: 700;">{{ $p['label'] }}</td>
                    <td class="num">{{ \App\Support\DariDate::en($p['count']) }} قابله</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2>{{ \App\Support\PanelText::t('پاسخ‌ها') }}</h2>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr><th>{{ \App\Support\PanelText::t('قابله') }}</th><th>{{ \App\Support\PanelText::t('کلستر') }}</th><th>{{ \App\Support\PanelText::t('رضایت') }}</th><th>{{ \App\Support\PanelText::t('مشکلات') }}</th><th>{{ \App\Support\PanelText::t('نظر') }}</th></tr>
            </thead>
            <tbody>
            @forelse($responses as $r)
                <tr>
                    <td>
                        <strong>{{ $r->midwife->full_name ?? '—' }}</strong>
                        <div class="num" style="color: var(--muted);">{{ \App\Support\DariDate::en($r->midwife->code ?? '') }}</div>
                    </td>
                    <td>{{ $r->midwife && $r->midwife->cluster ? \App\Support\DariDate::en($r->midwife->cluster->code) : '—' }}</td>
                    <td class="num">{{ \App\Support\DariDate::en($r->satisfaction) }} ★</td>
                    <td>
                        @foreach($r->problems ?? [] as $p)
                            <span class="chip chip-grey">{{ \App\Models\SurveyResponse::PROBLEM_LABELS[$p] ?? $p }}</span>
                        @endforeach
                    </td>
                    <td>{{ $r->comment ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">برای این ماه هنوز پاسخی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
