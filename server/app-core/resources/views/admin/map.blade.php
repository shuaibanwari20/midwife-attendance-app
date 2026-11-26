@extends('admin.layout')

@section('title', \App\Support\PanelText::t('نقشه کلسترها'))

@section('head')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <style>
        .leaflet-container { font-family: 'Vazirmatn', Tahoma, sans-serif; }
        .gold-pin {
            background: #C59A36; width: 26px; height: 26px; border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg); border: 3px solid #fff; box-shadow: 0 3px 10px rgba(0,0,0,0.3);
            display: flex; align-items: center; justify-content: center;
        }
        .gold-pin::after { content: ''; width: 8px; height: 8px; background: #fff; border-radius: 50%; transform: rotate(45deg); }
        .popup-name { font-weight: 800; font-size: 14px; margin-bottom: 4px; direction: rtl; text-align: right; }
        .popup-row { font-size: 12.5px; direction: rtl; text-align: right; margin: 2px 0; }
        .map-legend {
            position: absolute; left: 12px; bottom: 12px; z-index: 999; background: rgba(255,255,255,0.94);
            border-radius: 12px; padding: 10px 14px; font-size: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.15);
            direction: rtl; text-align: right;
        }
    </style>
@endsection

@section('content')
<div class="page-title">
    <div>
        <h1>{{ \App\Support\PanelText::t('نقشه کلسترها') }}</h1>
        <div class="sub">42 کلستر روی نقشه OpenStreetMap — روی نشانه کلیک کنید تا وضعیت امروز قابله‌ها را ببینید</div>
    </div>
    <div class="actions">
        <span class="chip chip-gold" id="date-chip">{{ \App\Support\PanelText::t('در حال بارگذاری...') }}</span>
        <button class="btn btn-ghost btn-sm" onclick="loadData()">{{ \App\Support\PanelText::t('به‌روزرسانی') }}</button>
    </div>
</div>

<div class="card" style="padding: 10px; position: relative;">
    <div id="map-canvas"></div>
    <div class="map-legend">
        <strong>{{ \App\Support\PanelText::t('راهنما:') }}</strong><br>
        <span style="color:#C59A36">●</span>{{ \App\Support\PanelText::t('مرکز کلستر (شعاع 150 متر)') }}<br>
        <span style="color:#2FA36B">●</span>{{ \App\Support\PanelText::t('حاضر به‌وقت') }}&nbsp; <span style="color:#D69E2E">●</span>{{ \App\Support\PanelText::t('دیرکرد') }}<br>
        <span style="color:#D9534F">●</span>{{ \App\Support\PanelText::t('بدون ثبت') }}&nbsp; <span style="color:#9AA8AB">●</span>{{ \App\Support\PanelText::t('رخصتی') }}</div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    const map = L.map('map-canvas').setView([33.8, 66.2], 6);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let clusterLayer = L.layerGroup().addTo(map);

    function pinIcon() {
        return L.divIcon({ className: '', html: '<div class="gold-pin"></div>', iconSize: [26, 26], iconAnchor: [13, 26], popupAnchor: [0, -26] });
    }

    function stateColor(state) {
        return state === 'on_time' ? '#2FA36B' : state === 'late' ? '#D69E2E' : state === 'not_arrived' ? '#D69E2E' : state === 'leave' ? '#9AA8AB' : state === 'before_join' ? '#9AA8AB' : state === 'friday' ? '#9AA8AB' : '#D9534F';
    }

    function loadData() {
        fetch('{{ route('admin.map.data') }}')
            .then(r => r.json())
            .then(json => {
                if (!json.success) return;
                document.getElementById('date-chip').textContent = json.data.date;
                clusterLayer.clearLayers();

                json.data.clusters.forEach(c => {
                    const marker = L.marker([c.lat, c.lng], { icon: pinIcon() });
                    const circle = L.circle([c.lat, c.lng], {
                        radius: c.radius_m,
                        color: '#0D5C63',
                        weight: 2,
                        dashArray: '6 6',
                        fillColor: '#11707A',
                        fillOpacity: 0.08
                    });

                    let rows = c.midwives.map(m =>
                        '<div class="popup-row"><span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:' +
                        stateColor(m.state) + ';margin-left:6px;"></span><strong>' + m.name + '</strong> (' + m.code + ') — ' + m.label + '</div>'
                    ).join('');

                    if (!rows) rows = '<div class="popup-row muted">قابله‌ای ثبت نشده است</div>';

                    marker.bindPopup(
                        '<div class="popup-name">' + c.name + ' — ' + c.village + '</div>' +
                        '<div class="popup-row muted">کد: ' + c.code + ' · شعاع: ' + c.radius_m + ' متر</div>' + rows
                    );

                    clusterLayer.addLayer(circle);
                    clusterLayer.addLayer(marker);
                });
            })
            .catch(() => alert('خطا در بارگذاری نقشه. دوباره تلاش کنید.'));
    }

    loadData();
    setInterval(loadData, 60000);
</script>
@endsection
