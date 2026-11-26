<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\SurveyAdminController;
use App\Http\Controllers\Admin\ClusterAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeaveAdminController;
use App\Http\Controllers\Admin\MapController;
use App\Http\Controllers\Admin\MidwifeAdminController;
use App\Http\Controllers\Admin\NotificationAdminController;
use App\Http\Controllers\Admin\ProblemAdminController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

/* Feature 44 — panel language switch (Dari / Pashto), stored in the session. */
Route::get('/lang/{loc}', function (string $loc) {
    if (in_array($loc, ['fa', 'ps'], true)) {
        session(['admin_locale' => $loc]);
    }

    return back();
})->name('panel.lang');

/*
|--------------------------------------------------------------------------
| Admin panel (Laravel Blade, Dari RTL)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('admin.login.submit')->middleware('throttle:10,1');

    Route::middleware(['web', 'auth', 'admin'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/map', [MapController::class, 'index'])->name('admin.map');
        Route::get('/map/data', [MapController::class, 'data'])->name('admin.map.data');

        Route::get('/surveys', [SurveyAdminController::class, 'index'])->name('admin.surveys');
        Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('admin.reports.export');
        Route::get('/reports/monthly-print', [ReportController::class, 'monthlyPrint'])->name('admin.reports.monthlyPrint');

        Route::get('/clusters', [ClusterAdminController::class, 'index'])->name('admin.clusters.index');
        Route::get('/clusters/create', [ClusterAdminController::class, 'create'])->name('admin.clusters.create');
        Route::post('/clusters', [ClusterAdminController::class, 'store'])->name('admin.clusters.store');
        Route::get('/clusters/{id}/edit', [ClusterAdminController::class, 'edit'])->name('admin.clusters.edit');
        Route::put('/clusters/{id}', [ClusterAdminController::class, 'update'])->name('admin.clusters.update');
        Route::delete('/clusters/{id}', [ClusterAdminController::class, 'destroy'])->name('admin.clusters.destroy');

        Route::get('/midwives', [MidwifeAdminController::class, 'index'])->name('admin.midwives.index');
        Route::get('/midwives/create', [MidwifeAdminController::class, 'create'])->name('admin.midwives.create');
        Route::post('/midwives', [MidwifeAdminController::class, 'store'])->name('admin.midwives.store');
        Route::get('/midwives/{id}/edit', [MidwifeAdminController::class, 'edit'])->name('admin.midwives.edit');
        Route::put('/midwives/{id}', [MidwifeAdminController::class, 'update'])->name('admin.midwives.update');
        Route::delete('/midwives/{id}', [MidwifeAdminController::class, 'destroy'])->name('admin.midwives.destroy');
        Route::post('/midwives/{id}/reset-pin', [MidwifeAdminController::class, 'resetPin'])->name('admin.midwives.reset-pin');
        Route::post('/midwives/{id}/unbind-device', [MidwifeAdminController::class, 'unbindDevice'])->name('admin.midwives.unbind');

        Route::get('/leaves', [LeaveAdminController::class, 'index'])->name('admin.leaves.index');
        Route::post('/leaves/manual', [LeaveAdminController::class, 'storeManual'])->name('admin.leaves.manual');
        Route::post('/leaves/{id}/approve', [LeaveAdminController::class, 'approve'])->name('admin.leaves.approve');
        Route::post('/leaves/{id}/reject', [LeaveAdminController::class, 'reject'])->name('admin.leaves.reject');
        Route::post('/leaves/{id}/delete', [LeaveAdminController::class, 'destroy'])->name('admin.leaves.delete');

        Route::get('/notifications', [NotificationAdminController::class, 'index'])->name('admin.notifications.index');
        Route::post('/notifications', [NotificationAdminController::class, 'store'])->name('admin.notifications.store');
        Route::delete('/notifications/{id}', [NotificationAdminController::class, 'destroy'])->name('admin.notifications.destroy');

        Route::get('/problems', [ProblemAdminController::class, 'index'])->name('admin.problems.index');
        Route::post('/problems/{id}/resolve', [ProblemAdminController::class, 'resolve'])->name('admin.problems.resolve');

        Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings');
        Route::post('/settings', [SettingsController::class, 'update'])->name('admin.settings.update');
        Route::post('/settings/rules', [SettingsController::class, 'updateRules'])->name('admin.settings.rules');
        Route::post('/settings/app-version', [SettingsController::class, 'updateAppVersion'])->name('admin.settings.appVersion');
    });
});
