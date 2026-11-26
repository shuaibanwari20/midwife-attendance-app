<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>گزارش ماهانهٔ حضور — {{ \App\Support\DariDate::monthYear($monthStart) }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #E9EFED; color: #17323A; font-family: 'Vazirmatn', Tahoma, sans-serif; font-size: 13px; }
        .sheet { background: #fff; max-width: 820px; margin: 18px auto; padding: 34px 38px 28px; box-shadow: 0 10px 34px rgba(10,74,81,.13); }
        .toolbar { max-width: 820px; margin: 18px auto 0; display: flex; gap: 10px; }
        .toolbar button, .toolbar a {
            border: none; border-radius: 12px; padding: 11px 20px; font-family: inherit;
            font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none;
        }
        .toolbar .print-btn { background: #C59A36; color: #fff; }
        .toolbar .back-btn { background: #fff; color: #0D5C63; }
        .head { display: flex; align-items: center; gap: 14px; border-bottom: 3px solid #C59A36; padding-bottom: 16px; }
        .head .logo { width: 54px; height: 54px; border-radius: 16px; background: #C59A36; color: #fff; font-size: 25px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .head h1 { margin: 0; font-size: 21px; font-weight: 800; color: #0A4A51; }
        .head .sub { color: #7A8B8F; font-size: 12.5px; margin-top: 3px; }
        .head .month { margin-inline-start: auto; text-align: center; background: #0D5C63; color: #fff; border-radius: 14px; padding: 9px 16px; }
        .head .month .m { font-size: 16px; font-weight: 800; }
        .head .month .r { font-size: 11px; opacity: .85; margin-top: 2px; }
        .summary { display: flex; gap: 10px; margin: 18px 0 6px; flex-wrap: wrap; }
        .summary .box { flex: 1; min-width: 110px; background: #F2F6F4; border-radius: 14px; padding: 11px 8px; text-align: center; }
        .summary .box .v { font-size: 20px; font-weight: 800; }
        .summary .box .l { color: #7A8B8F; font-size: 11.5px; margin-top: 2px; }
        .v-green { color: #27875C; } .v-amber { color: #B7791F; } .v-red { color: #C0392B; } .v-teal { color: #0D5C63; } .v-grey { color: #7A8B8F; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #0A4A51; color: #fff; font-weight: 700; padding: 8px 7px; font-size: 12px; text-align: right; }
        td { padding: 7px; border-bottom: 1px solid #E7EDEB; font-size: 12.5px; }
        tr:nth-child(even) td { background: #F8FAF9; }
        tfoot td { font-weight: 800; background: #F4EAD2 !important; border-top: 2px solid #C59A36; }
        .num { font-variant-numeric: tabular-nums; text-align: center; }
        .signs { display: flex; gap: 40px; margin-top: 44px; }
        .signs .sign { flex: 1; }
        .signs .role { font-weight: 700; font-size: 12.5px; margin-bottom: 34px; }
        .signs .line { border-top: 1.5px dotted #9DB3B6; padding-top: 6px; color: #7A8B8F; font-size: 11.5px; text-align: center; }
        .foot { margin-top: 26px; text-align: center; color: #7A8B8F; font-size: 11px; border-top: 1px solid #E7EDEB; padding-top: 10px; }
        @page { size: A4; margin: 11mm; }
        @media print {
            body { background: #fff; }
            .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; }
            .toolbar { display: none; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button type="button" class="print-btn" onclick="window.print()">چاپ / ذخیره PDF</button>
    <a class="back-btn" href="{{ route('admin.reports') }}">بازگشت به راپورها</a>
</div>

<div class="sheet">
    <div class="head">
        <div class="logo">ح</div>
        <div>
            <h1>حضور قابله — گزارش ماهانهٔ حضور و غیاب</h1>
            <div class="sub">پنل مدیریت موسسه — 42 کلستر · تاریخ‌ها میلادی (عیسوی)</div>
        </div>
        <div class="month">
            <div class="m">{{ \App\Support\DariDate::monthYear($monthStart) }}</div>
            <div class="r">از {{ \App\Support\DariDate::dayMonthYear($monthStart) }} تا {{ \App\Support\DariDate::dayMonthYear($monthEnd) }}</div>
        </div>
    </div>

    <div class="summary">
        <div class="box"><div class="v v-teal">{{ \App\Support\DariDate::en(count($rows)) }}</div><div class="l">کل قابله‌ها</div></div>
        <div class="box"><div class="v v-green">{{ \App\Support\DariDate::en($totals['on_time']) }}</div><div class="l">حاضر به‌وقت</div></div>
        <div class="box"><div class="v v-amber">{{ \App\Support\DariDate::en($totals['late']) }}</div><div class="l">دیرکرد</div></div>
        <div class="box"><div class="v v-red">{{ \App\Support\DariDate::en($totals['absent']) }}</div><div class="l">غایب</div></div>
        <div class="box"><div class="v v-grey">{{ \App\Support\DariDate::en($totals['leave']) }}</div><div class="l">رخصتی</div></div>
        <div class="box"><div class="v v-teal">{{ \App\Support\DariDate::en($totals['percent']) }}٪</div><div class="l">فیصدی حضور کلی</div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>کد</th><th>نام قابله</th><th>کلستر</th>
                <th class="num">حاضر به‌وقت</th><th class="num">دیرکرد</th>
                <th class="num">غایب</th><th class="num">رخصتی</th><th class="num">فیصدی حضور</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ \App\Support\DariDate::en($row['midwife']->code) }}</td>
                    <td>{{ $row['midwife']->full_name }}</td>
                    <td>{{ $row['midwife']->cluster ? \App\Support\DariDate::en($row['midwife']->cluster->code) : '—' }}</td>
                    <td class="num">{{ \App\Support\DariDate::en($row['on_time']) }}</td>
                    <td class="num">{{ \App\Support\DariDate::en($row['late']) }}</td>
                    <td class="num">{{ \App\Support\DariDate::en($row['absent']) }}</td>
                    <td class="num">{{ \App\Support\DariDate::en($row['leave']) }}</td>
                    <td class="num">{{ \App\Support\DariDate::en($row['percent']) }}٪</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">مجموع کل</td>
                <td class="num">{{ \App\Support\DariDate::en($totals['on_time']) }}</td>
                <td class="num">{{ \App\Support\DariDate::en($totals['late']) }}</td>
                <td class="num">{{ \App\Support\DariDate::en($totals['absent']) }}</td>
                <td class="num">{{ \App\Support\DariDate::en($totals['leave']) }}</td>
                <td class="num">{{ \App\Support\DariDate::en($totals['percent']) }}٪</td>
            </tr>
        </tfoot>
    </table>

    <div class="signs">
        <div class="sign">
            <div class="role">تهیه‌کننده — مدیریت سیستم</div>
            <div class="line">امضا و تاریخ</div>
        </div>
        <div class="sign">
            <div class="role">تایید — رئیس موسسه</div>
            <div class="line">امضا و تاریخ</div>
        </div>
    </div>

    <div class="foot">
        این گزارش به صورت خودکار از سیستم ثبت حضور قابله‌ها ساخته شد — {{ \App\Support\DariDate::full($generatedAt) }} (وقت کابل)
    </div>
</div>
</body>
</html>
