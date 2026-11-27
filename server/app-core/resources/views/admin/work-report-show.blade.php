@extends('admin.layout')

@section('title', \App\Support\PanelText::t('جزئیات راپور ماهانه'))

@section('content')
@php
    $chipFor = ['approved' => 'chip-green', 'submitted' => 'chip-amber', 'returned' => 'chip-red', 'draft' => 'chip-teal'];
    $reviewChip = ['approved' => 'chip-green', 'returned' => 'chip-red', 'pending' => 'chip-amber'];
    $reviewLabel = ['approved' => 'تأیید شده ✓', 'returned' => 'برگشت‌خورده ↩', 'pending' => 'در انتظار بررسی'];
    $reviewable = in_array($report->status, ['submitted', 'returned', 'approved'], true);
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

<p style="color:#5a6b70; margin: 4px 0 14px;">{{ \App\Support\PanelText::t('هر عکس جدا بررسی می‌شود: می‌توانید یک عکس را تأیید و دیگری را برای اصلاح برگشت بدهید. فقط عکس برگشت‌خورده را قابله باید دوباره بفرستد.') }}</p>

@foreach($photoKinds as $kind)
@php $review = $report->photoPath($kind) ? $report->photoReview($kind) : null; @endphp
<div class="card">
    <h2>{{ \App\Support\PanelText::t($photoLabels[$kind]) }}
        @if($review)
            <span class="chip {{ $reviewChip[$review] }}" style="margin-inline-start: 8px;">{{ \App\Support\PanelText::t($reviewLabel[$review]) }}</span>
        @endif
    </h2>
    @if($report->photoPath($kind))
        <a href="{{ route('admin.workReports.photo', [$report->id, $kind]) }}" target="_blank">
            <img src="{{ route('admin.workReports.photo', [$report->id, $kind]) }}" alt="{{ $kind }}" style="max-width: 520px; width: 100%; border-radius: 10px; border: 1px solid #ddd;">
        </a>
        @if($review === 'returned' && $report->photoNote($kind))
            <div class="alert alert-err" style="margin-top: 10px;">{{ \App\Support\PanelText::t('دلیل برگشت:') }} {{ $report->photoNote($kind) }}</div>
        @endif
        @if($reviewable)
        <div style="display: flex; gap: 18px; flex-wrap: wrap; margin-top: 14px; align-items: flex-start;">
            @if($review !== 'approved')
            <form method="POST" action="{{ route('admin.workReports.photoApprove', [$report->id, $kind]) }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">{{ \App\Support\PanelText::t('تأیید این عکس') }} ✓</button>
            </form>
            @endif
            @if($review !== 'returned')
            <form method="POST" action="{{ route('admin.workReports.photoReturn', [$report->id, $kind]) }}" style="flex: 1; min-width: 260px; display: flex; gap: 8px;">
                @csrf
                <input type="text" name="note" class="form-control" required maxlength="1000" placeholder="{{ \App\Support\PanelText::t('دلیل برگشت این عکس…') }}">
                <button type="submit" class="btn btn-ghost btn-sm" style="white-space: nowrap;">{{ \App\Support\PanelText::t('برگشت این عکس') }} ↩</button>
            </form>
            @endif
        </div>
        @endif
    @else
        <p style="color: #b3261e; font-weight: 700;">{{ \App\Support\PanelText::t('این عکس هنوز فرستاده نشده است.') }}</p>
    @endif
</div>
@endforeach

@if($report->status === 'approved')
<div class="alert alert-ok">{{ \App\Support\PanelText::t('هر سه عکس تأیید شده — این راپور کامل است.') }} @if($report->reviewed_at) ({{ \App\Support\DariDate::full($report->reviewed_at) }}) @endif</div>
@endif
@endsection
