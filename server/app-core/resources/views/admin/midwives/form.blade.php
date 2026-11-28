@extends('admin.layout')

@section('title', $midwife ? 'ویرایش قابله' : 'قابله جدید')

@section('content')
<div class="page-title">
    <div>
        <h1>{{ $midwife ? 'ویرایش قابله '.\App\Support\DariDate::en($midwife->code) : 'ثبت قابله جدید' }}</h1>
        <div class="sub">پین 4 رقمی برای ورود قابله به اپلیکیشن موبایل استفاده می‌شود</div>
    </div>
    <div class="actions">
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.midwives.index') }}">{{ \App\Support\PanelText::t('بازگشت به لیست') }}</a>
    </div>
</div>

<div class="card gold-top">
    <form method="POST" action="{{ $midwife ? route('admin.midwives.update', $midwife->id) : route('admin.midwives.store') }}">
        @csrf
        @if($midwife) @method('PUT') @endif

        <div class="form-grid">
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('نام کامل *') }}</label>
                <input class="form-control" name="full_name" value="{{ old('full_name', $midwife->full_name ?? '') }}" required>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('شماره تلیفون') }}</label>
                <input class="form-control" name="phone" value="{{ old('phone', $midwife->phone ?? '') }}" placeholder="0700 123 456">
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('کلستر *') }}</label>
                <select class="form-control" name="cluster_id" required>
                    <option value="">{{ \App\Support\PanelText::t('انتخاب کنید...') }}</option>
                    @foreach($clusters as $cluster)
                        <option value="{{ $cluster->id }}" @selected(old('cluster_id', $midwife->cluster_id ?? '') == $cluster->id)>
                            {{ \App\Support\DariDate::en($cluster->code) }} — {{ $cluster->village }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">{{ $midwife ? 'پین جدید (اختیاری)' : 'پین (4 رقم) *' }}</label>
                <input class="form-control" name="pin" maxlength="8" placeholder="مثال: 1234"
                       value="{{ old('pin') }}" {{ $midwife ? '' : 'required' }}>
                <div class="form-hint">{{ $midwife ? 'اگر خالی باشد پین فعلی حفظ می‌شود.' : 'پین ورود قابله به اپلیکیشن' }}</div>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('باقی‌مانده رخصتی (روز)') }}</label>
                <input class="form-control" type="number" name="leave_balance" min="0" max="365"
                       value="{{ old('leave_balance', $midwife->leave_balance ?? 12) }}">
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('وضعیت') }}</label>
                <label style="font-weight: 600;">
                    <input type="checkbox" name="active" value="1" @checked(old('active', $midwife->active ?? true))>{{ \App\Support\PanelText::t('فعال') }}</label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ $midwife ? 'ذخیره تغییرات' : 'ثبت قابله' }}</button>
            <a class="btn btn-ghost" href="{{ route('admin.midwives.index') }}">{{ \App\Support\PanelText::t('انصراف') }}</a>
        </div>
    </form>
</div>

@if($midwife && isset($bioState))
<div class="card">
    <h2>{{ \App\Support\PanelText::t('اثر انگشت (حاضری فقط با انگشت خودش)') }}</h2>
    <div style="margin-bottom: 10px;">
        @if($bioState === 'active')
            <span class="chip chip-green"><span class="dot" style="background:#2FA36B"></span>{{ \App\Support\PanelText::t('فعال') }}</span>
            <span class="num" style="color: var(--muted);">{{ \App\Support\PanelText::t('ثبت شده در') }} {{ $bioKey ? \App\Support\DariDate::full($bioKey->enrolled_at) : '' }}</span>
        @elseif($bioState === 'invalidated')
            <span class="chip chip-red"><span class="dot" style="background:#D9534F"></span>{{ \App\Support\PanelText::t('باطل شده') }}</span>
            <span style="color: var(--muted);">{{ \App\Support\PanelText::t('انگشت‌های گوشی تغییر کرده — حاضری تا ثبت‌نام دوباره بسته است') }}</span>
        @else
            <span class="chip chip-grey"><span class="dot" style="background:#9AA8AB"></span>{{ \App\Support\PanelText::t('ثبت نشده') }}</span>
            <span style="color: var(--muted);">{{ \App\Support\PanelText::t('تا ثبت‌نام، حاضری بدون اثر انگشت کار می‌کند') }}</span>
        @endif
    </div>

    @if(session('bio_code'))
        <div class="banner" style="background:#FFF7E0;border:1px solid #E8C86A;border-radius:10px;padding:12px;margin-bottom:10px;">
            {{ \App\Support\PanelText::t('کد ثبت‌نام (فقط همین یک بار نمایش داده می‌شود — حضوری به خود قابله بدهید):') }}
            <div class="num" style="font-size: 26px; font-weight: 900; letter-spacing: 4px;">{{ \App\Support\DariDate::en(session('bio_code')) }}</div>
            <div style="color: var(--muted);">{{ \App\Support\PanelText::t('اعتبار: 30 دقیقه — یک بار مصرف') }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.midwives.biometric-code', $midwife->id) }}">
        @csrf
        <button type="submit" class="btn btn-primary">{{ \App\Support\PanelText::t('صدور کد ثبت‌نام اثر انگشت') }}</button>
    </form>
    <div class="form-hint" style="margin-top: 8px;">{{ \App\Support\PanelText::t('قابله کد را در اپ (پروفایل ← ثبت اثر انگشت) وارد می‌کند و انگشت خودش را اسکن می‌کند. اگر بعداً در گوشی انگشت تازه‌ای ثبت شود، کلید او خودکار باطل و حاضری بسته می‌شود.') }}</div>
</div>
@endif
@endsection
