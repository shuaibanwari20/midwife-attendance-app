@extends('admin.layout')

@section('title', \App\Support\PanelText::t('تنظیمات'))

@section('content')
<div class="page-title">
    <div>
        <h1>{{ \App\Support\PanelText::t('تنظیمات حساب و سیستم') }}</h1>
        <div class="sub">{{ \App\Support\PanelText::t('تغییر مشخصات مدیر، رمز عبور و مشاهده قوانین ثبت حضور') }}</div>
    </div>
</div>

<div class="card gold-top">
    <h2>{{ \App\Support\PanelText::t('حساب مدیر') }}</h2>
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <div class="form-grid">
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('نام *') }}</label>
                <input class="form-control" name="name" value="{{ old('name', auth()->user()->name) }}" required>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('ایمیل *') }}</label>
                <input class="form-control" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('رمز عبور جدید (اختیاری)') }}</label>
                <input class="form-control" type="password" name="password" minlength="6">
                <div class="form-hint">{{ \App\Support\PanelText::t('حداقل 6 حرف — اگر خالی باشد رمز فعلی حفظ می‌شود') }}</div>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('تکرار رمز جدید') }}</label>
                <input class="form-control" type="password" name="password_confirmation">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ \App\Support\PanelText::t('ذخیره تنظیمات') }}</button>
        </div>
    </form>
</div>

<div class="card">
    <h2>{{ \App\Support\PanelText::t('ساعات کاری (وقت کابل — Asia/Kabul)') }}</h2>
    <form method="POST" action="{{ route('admin.settings.rules') }}">
        @csrf
        <div class="form-grid">
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('شروع ساعت کاری *') }}</label>
                <input class="form-control num" type="time" name="work_start" value="{{ old('work_start', $workStart) }}" required>
                <div class="form-hint">{{ \App\Support\PanelText::t('تا این ساعت:') }}<span class="chip chip-green">{{ \App\Support\PanelText::t('حاضر به‌وقت') }}</span></div>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('ختم ساعت کاری *') }}</label>
                <input class="form-control num" type="time" name="work_end" value="{{ old('work_end', $workEnd) }}" required>
                <div class="form-hint">{{ \App\Support\PanelText::t('بین شروع و ختم:') }}<span class="chip chip-amber">{{ \App\Support\PanelText::t('دیرکرد') }}</span> — بعد از ختم، ثبت حضور <strong>قفل</strong> می‌شود و روز تمام است</div>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('شروع بازۀ خروج *') }}</label>
                <input class="form-control num" type="time" name="checkout_start" value="{{ old('checkout_start', $checkoutStart) }}" required>
                <div class="form-hint">{{ \App\Support\PanelText::t('ثبت خروج (حاضری دوم) از این ساعت ممکن می‌شود') }}</div>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('پایان بازۀ خروج *') }}</label>
                <input class="form-control num" type="time" name="checkout_end" value="{{ old('checkout_end', $checkoutEnd) }}" required>
                <div class="form-hint">{{ \App\Support\PanelText::t('بعد از این ساعت ثبت خروج قفل می‌شود و خروج آن روز «ثبت نشده» می‌ماند') }}</div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ \App\Support\PanelText::t('ذخیره ساعات کاری') }}</button>
        </div>
    </form>
    <div class="table-wrap">
        <table class="data">
            <tbody>
            <tr>
                <td style="font-weight: 700;">{{ \App\Support\PanelText::t('حداکثر دقت GPS') }}</td>
                <td class="num">{{ \App\Support\DariDate::en($maxAccuracy) }} متر (بدون دقت کافی ثبت حضور ممکن نیست)</td>
            </tr>
            <tr>
                <td style="font-weight: 700;">{{ \App\Support\PanelText::t('GPS جعلی') }}</td>
                <td>رد می‌شود — ثبت حضور با موقعیت جعلی (Mock Location) پذیرفته نمی‌شود</td>
            </tr>
            <tr>
                <td style="font-weight: 700;">{{ \App\Support\PanelText::t('تعداد ثبت') }}</td>
                <td>{{ \App\Support\PanelText::t('روزانه فقط یک ثبت حضور برای هر قابله') }}</td>
            </tr>
            <tr>
                <td style="font-weight: 700;">{{ \App\Support\PanelText::t('روز جمعه') }}</td>
                <td>{{ \App\Support\PanelText::t('روز رخصتی هفتگی — غیبت ثبت نمی‌شود') }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card gold-top">
    <h2>{{ \App\Support\PanelText::t('نسخهٔ اپ قابله‌ها — خبررسانی به‌روزرسانی') }}</h2>
    <form method="POST" action="{{ route('admin.settings.appVersion') }}">
        @csrf
        <div class="form-grid">
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('کد آخرین نسخهٔ منتشرشده *') }}</label>
                <input class="form-control num" type="number" name="latest_version_code" min="1" max="9999" value="{{ old('latest_version_code', $appLatestVersion) }}" required>
                <div class="form-hint">هر اپی که کد نسخه‌اش از این عدد کمتر باشد، در صفحهٔ اصلی‌اش بنر «نسخهٔ جدید آماده است» می‌بیند.</div>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('لینک دانلود نسخهٔ جدید') }}</label>
                <input class="form-control" type="url" name="update_url" dir="ltr" placeholder="https://..." value="{{ old('update_url', $appUpdateUrl) }}">
                <div class="form-hint">{{ \App\Support\PanelText::t('اختیاری — مثلاً لینک تلگرام یا لینک مستقیم فایل APK.') }}</div>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('یادداشت نسخه (اختیاری)') }}</label>
                <input class="form-control" type="text" name="update_note" maxlength="300" placeholder="{{ \App\Support\PanelText::t('مثلاً: حالت تاریک و اصلاحات جدید') }}" value="{{ old('update_note', $appUpdateNote) }}">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-gold">{{ \App\Support\PanelText::t('ثبت نسخهٔ جدید') }}</button>
        </div>
    </form>
</div>
@endsection
