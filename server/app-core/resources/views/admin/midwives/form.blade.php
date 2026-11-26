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
@endsection
