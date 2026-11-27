<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>راپور تجمیعی ماهانهٔ کاری — {{ \App\Support\DariDate::monthYear($monthDate) }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #E9EFED; color: #17323A; font-family: 'Vazirmatn', Tahoma, sans-serif; font-size: 13px; }
        .sheet { background: #fff; max-width: 820px; margin: 18px auto; padding: 34px 38px 28px; box-shadow: 0 10px 34px rgba(10,74,81,.13); }
        .toolbar { max-width: 820px; margin: 18px auto 0; display: flex; gap: 10px; }
        .toolbar button, .toolbar a { border: none; border-radius: 12px; padding: 11px 20px; font-family: inherit; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; }
        .toolbar .print-btn { background: #C59A36; color: #fff; }
        .toolbar .back-btn { background: #fff; color: #0D5C63; }
        .head { display: flex; align-items: center; gap: 14px; border-bottom: 3px solid #C59A36; padding-bottom: 16px; }
        .head .logo { width: 54px; height: 54px; border-radius: 16px; background: #0D5C63; color: #fff; font-size: 25px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .head h1 { margin: 0; font-size: 20px; font-weight: 800; color: #0A4A51; }
        .head .sub { color: #7A8B8F; font-size: 12.5px; margin-top: 3px; }
        .head .month { margin-inline-start: auto; text-align: center; background: #0D5C63; color: #fff; border-radius: 14px; padding: 9px 16px; }
        .head .month .m { font-size: 16px; font-weight: 800; }
        .summary { display: flex; gap: 10px; margin: 18px 0 6px; flex-wrap: wrap; }
        .summary .box { flex: 1; min-width: 110px; background: #F2F6F4; border-radius: 14px; padding: 11px 8px; text-align: center; }
        .summary .box .v { font-size: 20px; font-weight: 800; color: #0D5C63; }
        .summary .box .l { color: #7A8B8F; font-size: 11.5px; margin-top: 2px; }
        h2.sec { font-size: 15px; color: #0A4A51; margin: 22px 0 4px; border-inline-start: 4px solid #C59A36; padding-inline-start: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #0A4A51; color: #fff; font-weight: 700; padding: 8px 7px; font-size: 12px; text-align: right; }
        td { padding: 6.5px 7px; border-bottom: 1px solid #E7EDEB; font-size: 12.5px; }
        tr:nth-child(even) td { background: #F8FAF9; }
        .num { font-variant-numeric: tabular-nums; text-align: center; }
        .signs { display: flex; gap: 40px; margin-top: 44px; }
        .signs .sign { flex: 1; }
        .signs .role { font-weight: 700; font-size: 12.5px; margin-bottom: 34px; }
        .signs .line { border-top: 1.5px dotted #9DB3B6; padding-top: 6px; color: #7A8B8F; font-size: 11.5px; text-align: center; }
        .foot { margin-top: 26px; color: #7A8B8F; font-size: 11px; text-align: center; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { box-shadow: none; margin: 0; max-width: none; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button class="print-btn" onclick="window.print()">🖨 چاپ / ذخیره PDF</button>
    <a class="back-btn" href="{{ route('admin.workReports', ['month' => $month]) }}">بازگشت به پنل</a>
</div>

<div class="sheet">
    <div class="head">
        <div class="logo">✚</div>
        <div>
            <h1>راپور تجمیعی ماهانهٔ کاری قابله‌ها</h1>
            <div class="sub">واکسیناسیون و OPV · مراجعین سراپا (OPD) · تنظیم خانواده · صحت مادر و طفل — فقط راپورهای تأییدشده</div>
        </div>
        <div class="month">
            <div class="m">{{ \App\Support\DariDate::monthYear($monthDate) }}</div>
        </div>
    </div>

    <div class="summary">
        <div class="box"><div class="v">{{ \App\Support\DariDate::en($approved->count()) }} / {{ \App\Support\DariDate::en($totalMidwives) }}</div><div class="l">راپور تأییدشده از کل قابله‌ها</div></div>
        <div class="box"><div class="v">{{ \App\Support\DariDate::en($totals['vacc_doses'] ?? 0) }}</div><div class="l">مجموع دوز واکسین</div></div>
        <div class="box"><div class="v">{{ \App\Support\DariDate::en($totals['opd_total'] ?? 0) }}</div><div class="l">مجموع مراجعین OPD</div></div>
        <div class="box"><div class="v">{{ \App\Support\DariDate::en(($totals['fp_new'] ?? 0) + ($totals['fp_revisit'] ?? 0)) }}</div><div class="l">مراجعین تنظیم خانواده</div></div>
    </div>

    @foreach($sections as $section)
        <h2 class="sec">{{ $section['label'] }}</h2>
        <table>
            <tbody>
            @foreach($section['fields'] as $key => $label)
                <tr>
                    <td style="width: 60%;">{{ $label }}</td>
                    <td class="num">{{ \App\Support\DariDate::en((string) (int) ($sums[$key] ?? 0)) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endforeach

    <h2 class="sec">خلاصه بر اساس قابله</h2>
    <table>
        <thead>
            <tr>
                <th>قابله</th>
                <th>کلستر</th>
                <th class="num">مراجعین OPD</th>
                <th class="num">دوز واکسین</th>
                <th class="num">تنظیم خانواده</th>
                <th class="num">ولادت‌ها</th>
            </tr>
        </thead>
        <tbody>
        @foreach($approved as $r)
            <tr>
                <td>{{ $r->midwife->full_name }}</td>
                <td>{{ $r->midwife->cluster ? $r->midwife->cluster->displayLabel() : '—' }}</td>
                <td class="num">{{ \App\Support\DariDate::en($r->totals['opd_total'] ?? 0) }}</td>
                <td class="num">{{ \App\Support\DariDate::en($r->totals['vacc_doses'] ?? 0) }}</td>
                <td class="num">{{ \App\Support\DariDate::en(($r->totals['fp_new'] ?? 0) + ($r->totals['fp_revisit'] ?? 0)) }}</td>
                <td class="num">{{ \App\Support\DariDate::en((string) (int) ($r->payload['deliveries'] ?? 0)) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="signs">
        <div class="sign">
            <div class="role">مسئول صحی / سوپروایزر</div>
            <div class="line">امضا و تاریخ</div>
        </div>
        <div class="sign">
            <div class="role">مدیر دفتر</div>
            <div class="line">امضا و تاریخ</div>
        </div>
    </div>

    <div class="foot">ساخته‌شده توسط سیستم ثبت حضور و راپوردهی قابله‌ها — {{ \App\Support\DariDate::full($generatedAt) }}</div>
</div>
</body>
</html>
