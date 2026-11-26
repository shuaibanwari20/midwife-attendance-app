<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'پنل مدیریت') — حضور قابله</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @yield('head')
</head>
<body>
<header class="site-header">
    <div class="inner">
        <div class="brand">
            <div class="logo">ح</div>
            <div>
                حضور قابله
                <small>پنل مدیریت موسسه — 42 کلستر</small>
            </div>
        </div>
        <nav class="main-nav">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">امروز (داشبورد)</a>
            <a href="{{ route('admin.map') }}" class="{{ request()->routeIs('admin.map') ? 'active' : '' }}">نقشه کلسترها</a>
            <a href="{{ route('admin.reports') }}" class="{{ request()->routeIs('admin.reports') ? 'active' : '' }}">راپورها</a>
            <a href="{{ route('admin.clusters.index') }}" class="{{ request()->routeIs('admin.clusters.*') ? 'active' : '' }}">مدیریت کلسترها</a>
            <a href="{{ route('admin.midwives.index') }}" class="{{ request()->routeIs('admin.midwives.*') ? 'active' : '' }}">مدیریت قابله‌ها</a>
            <a href="{{ route('admin.leaves.index') }}" class="{{ request()->routeIs('admin.leaves.*') ? 'active' : '' }}">رخصتی‌ها</a>
            <a href="{{ route('admin.notifications.index') }}" class="{{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">اعلان‌ها</a>
            <a href="{{ route('admin.problems.index') }}" class="{{ request()->routeIs('admin.problems.*') ? 'active' : '' }}">گزارش مشکلات</a>
            <a href="{{ route('admin.settings') }}" class="{{ request()->routeIs('admin.settings') ? 'active' : '' }}">تنظیمات</a>
            <form method="POST" action="{{ route('admin.logout') }}" class="inline-form">
                @csrf
                <button type="submit" class="btn btn-gold btn-sm">خروج</button>
            </form>
        </nav>
    </div>
</header>

<main class="container">
    @if(session('ok'))
        <div class="alert alert-ok">{{ session('ok') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-err">
            لطفاً خطاهای زیر را بررسی کنید:
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')

    <div class="footer-note">
        سیستم ثبت حضور قابله‌ها — اوقات کاری: شروع {{ \App\Support\DariDate::en(\App\Support\AttendanceService::workStart()->format('H:i')) }} · ختم {{ \App\Support\DariDate::en(\App\Support\AttendanceService::workEnd()->format('H:i')) }} (وقت کابل) ·
        تاریخ‌ها میلادی (عیسوی) — {{ \App\Support\DariDate::full(\App\Support\AttendanceService::now()) }}
    </div>
</main>

@yield('scripts')
</body>
</html>
