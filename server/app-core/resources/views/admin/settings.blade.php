@extends('admin.layout')

@section('title', 'تنظیمات')

@section('content')
<div class="page-title">
    <div>
        <h1>تنظیمات حساب و سیستم</h1>
        <div class="sub">تغییر مشخصات مدیر، رمز عبور و مشاهده قوانین ثبت حضور</div>
    </div>
</div>

<div class="card gold-top">
    <h2>حساب مدیر</h2>
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <div class="form-grid">
            <div>
                <label class="field-label">نام *</label>
                <input class="form-control" name="name" value="{{ old('name', auth()->user()->name) }}" required>
            </div>
            <div>
                <label class="field-label">ایمیل *</label>
                <input class="form-control" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
            </div>
            <div>
                <label class="field-label">رمز عبور جدید (اختیاری)</label>
                <input class="form-control" type="password" name="password" minlength="6">
                <div class="form-hint">حداقل 6 حرف — اگر خالی باشد رمز فعلی حفظ می‌شود</div>
            </div>
            <div>
                <label class="field-label">تکرار رمز جدید</label>
                <input class="form-control" type="password" name="password_confirmation">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">ذخیره تنظیمات</button>
        </div>
    </form>
</div>

<div class="card">
    <h2>ساعات کاری (وقت کابل — Asia/Kabul)</h2>
    <form method="POST" action="{{ route('admin.settings.rules') }}">
        @csrf
        <div class="form-grid">
            <div>
                <label class="field-label">شروع ساعت کاری *</label>
                <input class="form-control num" type="time" name="work_start" value="{{ old('work_start', $workStart) }}" required>
                <div class="form-hint">تا این ساعت: <span class="chip chip-green">حاضر به‌وقت</span></div>
            </div>
            <div>
                <label class="field-label">ختم ساعت کاری *</label>
                <input class="form-control num" type="time" name="work_end" value="{{ old('work_end', $workEnd) }}" required>
                <div class="form-hint">بین شروع و ختم: <span class="chip chip-amber">دیرکرد</span> — بعد از ختم، ثبت حضور <strong>قفل</strong> می‌شود و روز تمام است</div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">ذخیره ساعات کاری</button>
        </div>
    </form>
    <div class="table-wrap">
        <table class="data">
            <tbody>
            <tr>
                <td style="font-weight: 700;">حداکثر دقت GPS</td>
                <td class="num">{{ \App\Support\DariDate::en($maxAccuracy) }} متر (بدون دقت کافی ثبت حضور ممکن نیست)</td>
            </tr>
            <tr>
                <td style="font-weight: 700;">GPS جعلی</td>
                <td>رد می‌شود — ثبت حضور با موقعیت جعلی (Mock Location) پذیرفته نمی‌شود</td>
            </tr>
            <tr>
                <td style="font-weight: 700;">تعداد ثبت</td>
                <td>روزانه فقط یک ثبت حضور برای هر قابله</td>
            </tr>
            <tr>
                <td style="font-weight: 700;">روز جمعه</td>
                <td>روز رخصتی هفتگی — غیبت ثبت نمی‌شود</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
