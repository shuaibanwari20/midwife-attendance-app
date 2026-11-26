@extends('admin.layout')

@section('title', $cluster ? 'ویرایش کلستر' : 'کلستر جدید')

@section('head')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endsection

@section('content')
<div class="page-title">
    <div>
        <h1>{{ $cluster ? 'ویرایش کلستر '.\App\Support\DariDate::en($cluster->code) : 'ایجاد کلستر جدید' }}</h1>
        <div class="sub">موقعیت را روی نقشه کلیک کنید تا طول و عرض جغرافیایی پر شود</div>
    </div>
    <div class="actions">
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.clusters.index') }}">{{ \App\Support\PanelText::t('بازگشت به لیست') }}</a>
    </div>
</div>

<div class="card gold-top">
    <form method="POST" action="{{ $cluster ? route('admin.clusters.update', $cluster->id) : route('admin.clusters.store') }}">
        @csrf
        @if($cluster) @method('PUT') @endif

        <div class="form-grid">
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('نام کلستر *') }}</label>
                <input class="form-control" name="name" value="{{ old('name', $cluster->name ?? 'کلستر '.(43)) }}" required>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('نام قریه *') }}</label>
                <input class="form-control" name="village" value="{{ old('village', $cluster->village ?? '') }}" required>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('ولسوالی') }}</label>
                <input class="form-control" name="district" value="{{ old('district', $cluster->district ?? '') }}">
            </div>
            <div>
                <label class="field-label">ولایت</label>
                <input class="form-control" name="province" value="{{ old('province', $cluster->province ?? '') }}">
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('عرض جغرافیایی (Lat) *') }}</label>
                <input class="form-control" name="lat" id="lat" value="{{ old('lat', $cluster->lat ?? '34.5553') }}" required>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('طول جغرافیایی (Lng) *') }}</label>
                <input class="form-control" name="lng" id="lng" value="{{ old('lng', $cluster->lng ?? '69.2075') }}" required>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('شعاع ساحه (متر) *') }}</label>
                <input class="form-control" type="number" name="radius_m" min="20" max="5000"
                       value="{{ old('radius_m', $cluster->radius_m ?? 150) }}" required>
                <div class="form-hint">{{ \App\Support\PanelText::t('حد مجاز برای ثبت حضور (پیش‌فرض 150 متر)') }}</div>
            </div>
            <div>
                <label class="field-label">{{ \App\Support\PanelText::t('وضعیت') }}</label>
                <label style="font-weight: 600;">
                    <input type="checkbox" name="active" value="1" @checked(old('active', $cluster->active ?? true))>{{ \App\Support\PanelText::t('فعال') }}</label>
            </div>
        </div>

        <div style="margin: 18px 0;">
            <label class="field-label">{{ \App\Support\PanelText::t('انتخاب موقعیت روی نقشه') }}</label>
            <div id="picker" class="map-picker"></div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ $cluster ? 'ذخیره تغییرات' : 'ایجاد کلستر' }}</button>
            <a class="btn btn-ghost" href="{{ route('admin.clusters.index') }}">{{ \App\Support\PanelText::t('انصراف') }}</a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    const startLat = parseFloat(document.getElementById('lat').value) || 34.5553;
    const startLng = parseFloat(document.getElementById('lng').value) || 69.2075;

    const picker = L.map('picker').setView([startLat, startLng], 13);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(picker);

    let marker = L.marker([startLat, startLng]).addTo(picker);
    let circle = L.circle([startLat, startLng], {
        radius: parseInt(document.querySelector('[name=radius_m]').value) || 150,
        color: '#0D5C63', weight: 2, dashArray: '6 6', fillOpacity: 0.08
    }).addTo(picker);

    picker.on('click', function (e) {
        const { lat, lng } = e.latlng;
        document.getElementById('lat').value = lat.toFixed(6);
        document.getElementById('lng').value = lng.toFixed(6);
        marker.setLatLng([lat, lng]);
        circle.setLatLng([lat, lng]);
    });

    document.querySelector('[name=radius_m]').addEventListener('input', function () {
        circle.setRadius(parseInt(this.value) || 150);
    });
</script>
@endsection
