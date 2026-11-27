<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProblemReportController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ServerTimeController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\WorkReportController;
use App\Http\Controllers\Api\WeekController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API (Sanctum token auth)
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [ProfileController::class, 'me']);
    Route::get('/server-time', [ServerTimeController::class, 'index']);
    Route::post('/check-in', [AttendanceController::class, 'checkIn']);
    Route::get('/history', [HistoryController::class, 'index']);
    Route::get('/week', [WeekController::class, 'index']);
    Route::get('/leaves', [LeaveController::class, 'index']);
    Route::post('/leaves', [LeaveController::class, 'store']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/problem-reports', [ProblemReportController::class, 'store']);
    Route::get('/survey', [SurveyController::class, 'current']);
    Route::post('/survey', [SurveyController::class, 'respond']);
    Route::get('/work-reports/months', [WorkReportController::class, 'months']);
    Route::get('/work-reports/current', [WorkReportController::class, 'current']);
    Route::post('/work-reports/submit', [WorkReportController::class, 'submit']);
    Route::post('/work-reports/photo', [WorkReportController::class, 'photo']);
    Route::post('/work-reports/photo/delete', [WorkReportController::class, 'deletePhoto']);
    Route::post('/sync', [SyncController::class, 'store']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
