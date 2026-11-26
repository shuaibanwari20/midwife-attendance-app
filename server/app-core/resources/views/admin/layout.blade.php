<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'پنل مدیریت') — حضور قابله</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=20261126-sb1">
    @yield('head')
</head>
<body>
<div class="layout">

    {{-- ===== Sidebar (right side in RTL) ===== --}}
    <aside class="sidebar" id="sidebar" aria-label="منوی اصلی">
        <div class="side-brand">
            <div class="logo">ح</div>
            <div class="brand-text">
                حضور قابله
                <small>پنل مدیریت موسسه — 42 کلستر</small>
            </div>
            <button type="button" class="side-close" id="sideClose" aria-label="بستن منو">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <nav class="side-nav">
            <div class="side-group">اصلی</div>
            <a href="{{ route('admin.dashboard') }}" class="side-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="3.5" width="7" height="7" rx="2"/><rect x="13.5" y="3.5" width="7" height="7" rx="2"/><rect x="3.5" y="13.5" width="7" height="7" rx="2"/><rect x="13.5" y="13.5" width="7" height="7" rx="2"/></svg>
                <span>امروز (داشبورد)</span>
            </a>
            <a href="{{ route('admin.map') }}" class="side-link {{ request()->routeIs('admin.map') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-5.5-7-11a7 7 0 1 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.6"/></svg>
                <span>نقشه کلسترها</span>
            </a>
            <a href="{{ route('admin.reports') }}" class="side-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M21 20H3"/></svg>
                <span>راپورها</span>
            </a>

            <div class="side-group">مدیریت</div>
            <a href="{{ route('admin.clusters.index') }}" class="side-link {{ request()->routeIs('admin.clusters.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/></svg>
                <span>مدیریت کلسترها</span>
            </a>
            <a href="{{ route('admin.midwives.index') }}" class="side-link {{ request()->routeIs('admin.midwives.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 19.5c.6-3 2.8-4.5 5.5-4.5s4.9 1.5 5.5 4.5"/><path d="M15.5 5.4a3.2 3.2 0 1 1 0 5.9M17.8 15.3c1.5.6 2.4 1.9 2.7 4.2"/></svg>
                <span>مدیریت قابله‌ها</span>
            </a>
            <a href="{{ route('admin.leaves.index') }}" class="side-link {{ request()->routeIs('admin.leaves.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="16" rx="3"/><path d="M3.5 10h17M8 3v4M16 3v4M9 16.2l2.2 2.2 4-4.2"/></svg>
                <span>رخصتی‌ها</span>
            </a>

            <div class="side-group">سیستم</div>
            <a href="{{ route('admin.notifications.index') }}" class="side-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 16v-5a6 6 0 1 1 12 0v5l1.5 3h-15L6 16z"/><path d="M10 21a2.2 2.2 0 0 0 4 0"/></svg>
                <span>اعلان‌ها</span>
            </a>
            <a href="{{ route('admin.problems.index') }}" class="side-link {{ request()->routeIs('admin.problems.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4L22 20H2L12 4z"/><path d="M12 10v4.4M12 17.2v.3"/></svg>
                <span>گزارش مشکلات</span>
            </a>
            <a href="{{ route('admin.settings') }}" class="side-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2.2"/><circle cx="9" cy="17" r="2.2"/></svg>
                <span>تنظیمات</span>
            </a>
        </nav>

        <div class="side-foot">
            <div class="side-hours">
                اوقات کاری امروز: شروع {{ \App\Support\DariDate::en(\App\Support\AttendanceService::workStart()->format('H:i')) }} · ختم {{ \App\Support\DariDate::en(\App\Support\AttendanceService::workEnd()->format('H:i')) }}
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="side-logout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h-6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6"/><path d="M10 12h11M18 8.5L21.5 12 18 15.5"/></svg>
                    <span>خروج</span>
                </button>
            </form>
        </div>
    </aside>
    <div class="scrim" id="scrim"></div>

    {{-- ===== Main column ===== --}}
    <div class="main-col">
        <header class="topbar">
            <button type="button" class="hamburger" id="menuBtn" aria-label="باز کردن منو">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <div class="topbar-title">@yield('title', 'پنل مدیریت')</div>
            <div class="topbar-date">{{ \App\Support\DariDate::full(\App\Support\AttendanceService::now()) }}</div>
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
    </div>
</div>

<script>
    (function () {
        var sidebar = document.getElementById('sidebar');
        var scrim = document.getElementById('scrim');
        function openMenu() { sidebar.classList.add('open'); scrim.classList.add('show'); document.body.classList.add('no-scroll'); }
        function closeMenu() { sidebar.classList.remove('open'); scrim.classList.remove('show'); document.body.classList.remove('no-scroll'); }
        document.getElementById('menuBtn').addEventListener('click', openMenu);
        document.getElementById('sideClose').addEventListener('click', closeMenu);
        scrim.addEventListener('click', closeMenu);
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenu(); });
    })();
</script>
@yield('scripts')
</body>
</html>
