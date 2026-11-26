<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Support\PanelText::t('ورود مدیریت — حضور قابله') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0A4A51">
    <link rel="apple-touch-icon" href="/pwa/apple-touch-icon.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="حضور قابله">
    <script>if ("serviceWorker" in navigator) { navigator.serviceWorker.register("/sw.js").catch(function () {}); }</script>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="logo-big">ح</div>
        <h1>{{ \App\Support\PanelText::t('حضور قابله') }}</h1>
        <div class="sub">{{ \App\Support\PanelText::t('پنل مدیریت موسسه — ورود مدیر') }}</div>

        @if($errors->any())
            <div class="alert alert-err">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div style="margin-bottom: 14px;">
                <label class="field-label" for="email">{{ \App\Support\PanelText::t('ایمیل') }}</label>
                <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div style="margin-bottom: 20px;">
                <label class="field-label" for="password">{{ \App\Support\PanelText::t('رمز عبور') }}</label>
                <input class="form-control" type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">{{ \App\Support\PanelText::t('ورود به پنل') }}</button>
        </form>

        <p class="muted small text-center" style="margin-top: 18px;">{{ \App\Support\PanelText::t('حساب پیش‌فرض: admin@anwari.tech / admin1234') }}<br>{{ \App\Support\PanelText::t('رمز عبور را پس از اولین ورود از بخش «تنظیمات» تغییر دهید.') }}</p>
    </div>
</div>
</body>
</html>
