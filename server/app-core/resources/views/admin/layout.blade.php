<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'پنل مدیریت') — حضور قابله</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=20261126-ps1">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0A4A51">
    <link rel="apple-touch-icon" href="/pwa/apple-touch-icon.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="حضور قابله">
    <script>if ("serviceWorker" in navigator) { navigator.serviceWorker.register("/sw.js").catch(function () {}); }</script>
    @yield('head')
</head>
<body>
<div class="layout">

    {{-- ===== Sidebar (right side in RTL) ===== --}}
    <aside class="sidebar" id="sidebar" aria-label="منوی اصلی">
        <div class="side-brand">
            <div class="logo">ح</div>
            <div class="brand-text">{{ \App\Support\PanelText::t('حضور قابله') }}<small>{{ \App\Support\PanelText::t('پنل مدیریت موسسه — 42 کلستر') }}</small>
            </div>
            <button type="button" class="side-close" id="sideClose" aria-label="بستن منو">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <nav class="side-nav">
            <div class="side-group">{{ \App\Support\PanelText::t('اصلی') }}</div>
            <a href="{{ route('admin.dashboard') }}" class="side-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="3.5" width="7" height="7" rx="2"/><rect x="13.5" y="3.5" width="7" height="7" rx="2"/><rect x="3.5" y="13.5" width="7" height="7" rx="2"/><rect x="13.5" y="13.5" width="7" height="7" rx="2"/></svg>
                <span>{{ \App\Support\PanelText::t('امروز (داشبورد)') }}</span>
            </a>
            <a href="{{ route('admin.map') }}" class="side-link {{ request()->routeIs('admin.map') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-5.5-7-11a7 7 0 1 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.6"/></svg>
                <span>{{ \App\Support\PanelText::t('نقشه کلسترها') }}</span>
            </a>
            <a href="{{ route('admin.reports') }}" class="side-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M21 20H3"/></svg>
                <span>{{ \App\Support\PanelText::t('راپورها') }}</span>
            </a>

            <div class="side-group">{{ \App\Support\PanelText::t('مدیریت') }}</div>
            <a href="{{ route('admin.clusters.index') }}" class="side-link {{ request()->routeIs('admin.clusters.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/></svg>
                <span>{{ \App\Support\PanelText::t('مدیریت کلسترها') }}</span>
            </a>
            <a href="{{ route('admin.midwives.index') }}" class="side-link {{ request()->routeIs('admin.midwives.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 19.5c.6-3 2.8-4.5 5.5-4.5s4.9 1.5 5.5 4.5"/><path d="M15.5 5.4a3.2 3.2 0 1 1 0 5.9M17.8 15.3c1.5.6 2.4 1.9 2.7 4.2"/></svg>
                <span>{{ \App\Support\PanelText::t('مدیریت قابله‌ها') }}</span>
            </a>
            <a href="{{ route('admin.leaves.index') }}" class="side-link {{ request()->routeIs('admin.leaves.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="16" rx="3"/><path d="M3.5 10h17M8 3v4M16 3v4M9 16.2l2.2 2.2 4-4.2"/></svg>
                <span>{{ \App\Support\PanelText::t('رخصتی‌ها') }}</span>
            </a>
            <a href="{{ route('admin.holidays') }}" class="side-link {{ request()->routeIs('admin.holidays*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="16" rx="3"/><path d="M3.5 10h17M8 3v4M16 3v4"/><path d="M12 12.2l.9 1.8 2 .3-1.45 1.4.35 2-1.8-.95-1.8.95.35-2L9.1 14.3l2-.3z"/></svg>
                <span>{{ \App\Support\PanelText::t('رخصتی‌های عمومی') }}</span>
            </a>
            <a href="{{ route('admin.surveys') }}" class="side-link {{ request()->routeIs('admin.surveys') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="17" rx="2.5"/><path d="M9 4.5V3h6v1.5M9 12h6M9 16h4"/></svg>
                <span>{{ \App\Support\PanelText::t('نظرسنجی ماهانه') }}</span>
            </a>
            <a href="{{ route('admin.workReports') }}" class="side-link {{ request()->routeIs('admin.workReports*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3.5h9L19 7.5v13H6z"/><path d="M14.5 3.5v4.5H19M9 13.5l2 2 3.5-4"/></svg>
                <span>{{ \App\Support\PanelText::t('راپور ماهانهٔ کاری') }}</span>
            </a>

            <div class="side-group">{{ \App\Support\PanelText::t('سیستم') }}</div>
            <a href="{{ route('admin.notifications.index') }}" class="side-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 16v-5a6 6 0 1 1 12 0v5l1.5 3h-15L6 16z"/><path d="M10 21a2.2 2.2 0 0 0 4 0"/></svg>
                <span>{{ \App\Support\PanelText::t('اعلان‌ها') }}</span>
            </a>
            <a href="{{ route('admin.problems.index') }}" class="side-link {{ request()->routeIs('admin.problems.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4L22 20H2L12 4z"/><path d="M12 10v4.4M12 17.2v.3"/></svg>
                <span>{{ \App\Support\PanelText::t('گزارش مشکلات') }}</span>
            </a>
            <a href="{{ route('admin.settings') }}" class="side-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2.2"/><circle cx="9" cy="17" r="2.2"/></svg>
                <span>{{ \App\Support\PanelText::t('تنظیمات') }}</span>
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
                    <span>{{ \App\Support\PanelText::t('خروج') }}</span>
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
            <div class="lang-switch">
                <a href="{{ route('panel.lang', 'fa') }}" class="{{ app()->getLocale() === 'fa' ? 'active' : '' }}">دری</a>
                <a href="{{ route('panel.lang', 'ps') }}" class="{{ app()->getLocale() === 'ps' ? 'active' : '' }}">پښتو</a>
            </div>
        </header>

        <main class="container">
            @if(session('ok'))
                <div class="alert alert-ok">{{ session('ok') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-err">{{ \App\Support\PanelText::t('لطفاً خطاهای زیر را بررسی کنید:') }}<ul>
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
