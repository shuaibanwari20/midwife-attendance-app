@extends('admin.layout')

@section('title', \App\Support\PanelText::t('مدیریت کلسترها'))

@section('content')
<div class="page-title">
    <div>
        <h1>{{ \App\Support\PanelText::t('مدیریت کلسترها') }}</h1>
        <div class="sub">{{ \App\Support\DariDate::en($clusters->count()) }} کلستر ثبت‌شده — ویرایش، ایجاد و حذف</div>
    </div>
    <div class="actions">
        <a class="btn btn-gold btn-sm" href="{{ route('admin.clusters.create') }}">{{ \App\Support\PanelText::t('+ کلستر جدید') }}</a>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
            <tr>
                <th>{{ \App\Support\PanelText::t('کد') }}</th>
                <th>{{ \App\Support\PanelText::t('نام') }}</th>
                <th>{{ \App\Support\PanelText::t('قریه') }}</th>
                <th>{{ \App\Support\PanelText::t('ولسوالی / ولایت') }}</th>
                <th>{{ \App\Support\PanelText::t('موقعیت (Lat, Lng)') }}</th>
                <th>شعاع</th>
                <th>{{ \App\Support\PanelText::t('قابله‌ها') }}</th>
                <th>{{ \App\Support\PanelText::t('وضعیت') }}</th>
                <th>{{ \App\Support\PanelText::t('عملیات') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($clusters as $cluster)
                <tr>
                    <td class="num">{{ \App\Support\DariDate::en($cluster->code) }}</td>
                    <td style="font-weight: 700;">{{ $cluster->name }}</td>
                    <td>{{ $cluster->village }}</td>
                    <td class="muted">{{ $cluster->district }} / {{ $cluster->province }}</td>
                    <td class="num small">{{ \App\Support\DariDate::en(number_format($cluster->lat, 4, '.', '')) }}, {{ \App\Support\DariDate::en(number_format($cluster->lng, 4, '.', '')) }}</td>
                    <td class="num">{{ \App\Support\DariDate::en($cluster->radius_m) }} متر</td>
                    <td class="num">{{ \App\Support\DariDate::en($cluster->midwives_count) }}</td>
                    <td>
                        @if($cluster->active)
                            <span class="chip chip-green">{{ \App\Support\PanelText::t('فعال') }}</span>
                        @else
                            <span class="chip chip-grey">{{ \App\Support\PanelText::t('غیرفعال') }}</span>
                        @endif
                    </td>
                    <td>
                        <a class="btn btn-ghost btn-sm" href="{{ route('admin.clusters.edit', $cluster->id) }}">{{ \App\Support\PanelText::t('ویرایش') }}</a>
                        <form method="POST" action="{{ route('admin.clusters.destroy', $cluster->id) }}" class="inline-form"
                              onsubmit="return confirm('آیا مطمئن هستید که این کلستر حذف شود؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">{{ \App\Support\PanelText::t('حذف') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
