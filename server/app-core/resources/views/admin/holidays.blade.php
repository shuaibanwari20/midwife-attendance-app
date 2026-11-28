@extends('admin.layout')

@section('title', \App\Support\PanelText::t('رخصتی‌های عمومی'))

@section('content')
<div class="page-title">
    <div>
        <h1>{{ \App\Support\PanelText::t('رخصتی‌های عمومی') }}</h1>
        <div class="sub">{{ \App\Support\PanelText::t('این روزها برای همهٔ قابله‌ها رخصتی است و در تقویم اپ نمایش داده می‌شود') }}</div>
    </div>
</div>

<div class="card">
    <h2>{{ \App\Support\PanelText::t('افزودن رخصتی عمومی') }}</h2>
    <form method="POST" action="{{ route('admin.holidays.store') }}" class="form-grid">
        @csrf
        <div class="field">
            <label>{{ \App\Support\PanelText::t('تاریخ') }} *</label>
            <input class="form-control num" type="date" name="date" value="{{ old('date') }}" required>
            @error('date')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label>{{ \App\Support\PanelText::t('نام رخصتی') }} *</label>
            <input class="form-control" type="text" name="name" value="{{ old('name') }}" placeholder="{{ \App\Support\PanelText::t('مثلاً: عید سعید فطر') }}" required>
            @error('name')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field" style="align-self: end;">
            <button type="submit" class="btn btn-primary">{{ \App\Support\PanelText::t('افزودن') }}</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
            <tr>
                <th>{{ \App\Support\PanelText::t('تاریخ') }}</th>
                <th>{{ \App\Support\PanelText::t('روز') }}</th>
                <th>{{ \App\Support\PanelText::t('نام رخصتی') }}</th>
                <th>{{ \App\Support\PanelText::t('عملیات') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($holidays as $holiday)
                <tr>
                    <td class="num">{{ \App\Support\DariDate::dayMonthYear($holiday->date) }}</td>
                    <td>{{ \App\Support\DariDate::weekday($holiday->date) }}</td>
                    <td style="font-weight: 700;">{{ $holiday->name }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.holidays.delete', $holiday->id) }}" onsubmit="return confirm('{{ \App\Support\PanelText::t('این رخصتی حذف شود؟') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">{{ \App\Support\PanelText::t('حذف') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">{{ \App\Support\PanelText::t('هنوز رخصتی عمومی ثبت نشده است') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
