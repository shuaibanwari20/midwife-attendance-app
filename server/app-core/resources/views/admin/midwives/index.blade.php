@extends('admin.layout')

@section('title', \App\Support\PanelText::t('مدیریت قابله‌ها'))

@section('content')
<div class="page-title">
    <div>
        <h1>{{ \App\Support\PanelText::t('مدیریت قابله‌ها') }}</h1>
        <div class="sub">{{ \App\Support\DariDate::en($midwives->count()) }} قابله — تنظیم پین، قطع اتصال دستگاه، ویرایش</div>
    </div>
    <div class="actions">
        <a class="btn btn-gold btn-sm" href="{{ route('admin.midwives.create') }}">{{ \App\Support\PanelText::t('+ قابله جدید') }}</a>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
            <tr>
                <th>{{ \App\Support\PanelText::t('کد') }}</th>
                <th>{{ \App\Support\PanelText::t('نام') }}</th>
                <th>{{ \App\Support\PanelText::t('تلیفون') }}</th>
                <th>{{ \App\Support\PanelText::t('کلستر') }}</th>
                <th>{{ \App\Support\PanelText::t('دستگاه متصل') }}</th>
                <th>{{ \App\Support\PanelText::t('باقی‌مانده رخصتی') }}</th>
                <th>{{ \App\Support\PanelText::t('وضعیت') }}</th>
                <th>{{ \App\Support\PanelText::t('عملیات') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($midwives as $midwife)
                <tr>
                    <td class="num">{{ \App\Support\DariDate::en($midwife->code) }}</td>
                    <td style="font-weight: 700;">{{ $midwife->full_name }}</td>
                    <td class="num">{{ \App\Support\DariDate::en($midwife->phone ?? '—') }}</td>
                    <td>
                        @if($midwife->cluster)
                            <span class="chip chip-teal">{{ \App\Support\DariDate::en($midwife->cluster->code) }} — {{ $midwife->cluster->village }}</span>
                        @else
                            <span class="chip chip-grey">—</span>
                        @endif
                    </td>
                    <td>
                        @if($midwife->device_id)
                            <span class="chip chip-green">{{ \App\Support\PanelText::t('متصل') }}</span>
                            <form method="POST" action="{{ route('admin.midwives.unbind', $midwife->id) }}" class="inline-form">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-sm" onclick="return confirm('اتصال دستگاه قطع شود؟');">{{ \App\Support\PanelText::t('قطع دستگاه') }}</button>
                            </form>
                        @else
                            <span class="chip chip-grey">{{ \App\Support\PanelText::t('ندارد') }}</span>
                        @endif
                    </td>
                    <td class="num">{{ \App\Support\DariDate::en($midwife->leave_balance) }} روز</td>
                    <td>
                        @if($midwife->active)
                            <span class="chip chip-green">{{ \App\Support\PanelText::t('فعال') }}</span>
                        @else
                            <span class="chip chip-red">{{ \App\Support\PanelText::t('غیرفعال') }}</span>
                        @endif
                    </td>
                    <td style="white-space: nowrap;">
                        <a class="btn btn-ghost btn-sm" href="{{ route('admin.midwives.edit', $midwife->id) }}">{{ \App\Support\PanelText::t('ویرایش') }}</a>
                        <form method="POST" action="{{ route('admin.midwives.reset-pin', $midwife->id) }}" class="inline-form">
                            @csrf
                            <input class="form-control" style="width: 76px; display: inline-block; padding: 6px 8px;"
                                   name="pin" placeholder="پین جدید" maxlength="8" required>
                            <button type="submit" class="btn btn-gold btn-sm">{{ \App\Support\PanelText::t('تغییر پین') }}</button>
                        </form>
                        @if($midwife->active)
                            <form method="POST" action="{{ route('admin.midwives.destroy', $midwife->id) }}" class="inline-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm" onclick="return confirm('{{ \App\Support\PanelText::t('قابله غیرفعال شود؟ سوابقش حفظ می‌شود و از ویرایش می‌توانید دوباره فعالش کنید.') }}');">{{ \App\Support\PanelText::t('غیرفعال') }}</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.midwives.force-delete', $midwife->id) }}" class="inline-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ \App\Support\PanelText::t('حذف کامل؟') }} {{ $midwife->full_name }} — {{ \App\Support\PanelText::t('تمام سوابق حضور، رخصتی و راپورهایش برای همیشه پاک می‌شود و برگشت ندارد!') }}');">{{ \App\Support\PanelText::t('حذف کامل') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
