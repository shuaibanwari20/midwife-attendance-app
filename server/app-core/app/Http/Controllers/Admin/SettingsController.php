<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings', [
            'workStart' => AttendanceService::workStart()->format('H:i'),
            'workEnd' => AttendanceService::workEnd()->format('H:i'),
            'checkoutStart' => AttendanceService::checkoutStart()->format('H:i'),
            'checkoutEnd' => AttendanceService::checkoutEnd()->format('H:i'),
            'lateUntil' => AttendanceService::workEnd()->format('H:i'),
            'maxAccuracy' => config('midwife.max_gps_accuracy'),
            'appLatestVersion' => (int) (Setting::get('app_latest_version_code') ?: 1),
            'appUpdateUrl' => (string) (Setting::get('app_update_url') ?: ''),
            'appUpdateNote' => (string) (Setting::get('app_update_note') ?: ''),
        ]);
    }

    /**
     * Feature 30 — publish the newest app version. Every midwife app older
     * than this code shows an update banner on its home screen.
     */
    public function updateAppVersion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'latest_version_code' => ['required', 'integer', 'min:1', 'max:9999'],
            'update_url' => ['nullable', 'url', 'max:500'],
            'update_note' => ['nullable', 'string', 'max:300'],
        ], [
            'latest_version_code.required' => 'کد نسخهٔ جدید را وارد کنید.',
            'latest_version_code.integer' => 'کد نسخه باید عدد باشد.',
            'update_url.url' => 'لینک دانلود نامعتبر است.',
        ]);

        Setting::set('app_latest_version_code', (string) $data['latest_version_code']);
        Setting::set('app_update_url', (string) ($data['update_url'] ?? ''));
        Setting::set('app_update_note', (string) ($data['update_note'] ?? ''));

        return back()->with('ok', 'نسخهٔ جدید اپ ثبت شد — اپ‌های قدیمی‌تر از همین حالا خبر می‌شوند.');
    }

    /** Save the working hours (شروع کار / ختم کار) + the check-out window — applies immediately. */
    public function updateRules(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'work_start' => ['required', 'date_format:H:i'],
            'work_end' => ['required', 'date_format:H:i'],
            'checkout_start' => ['required', 'date_format:H:i'],
            'checkout_end' => ['required', 'date_format:H:i'],
        ], [
            'work_start.required' => 'ساعت شروع کار را وارد کنید.',
            'work_start.date_format' => 'ساعت شروع کار نامعتبر است.',
            'work_end.required' => 'ساعت ختم کار را وارد کنید.',
            'work_end.date_format' => 'ساعت ختم کار نامعتبر است.',
            'checkout_start.required' => 'ساعت شروع بازۀ خروج را وارد کنید.',
            'checkout_start.date_format' => 'ساعت شروع بازۀ خروج نامعتبر است.',
            'checkout_end.required' => 'ساعت پایان بازۀ خروج را وارد کنید.',
            'checkout_end.date_format' => 'ساعت پایان بازۀ خروج نامعتبر است.',
        ]);

        if ($data['work_end'] <= $data['work_start']) {
            return back()
                ->withErrors(['work_end' => 'ساعت ختم کار باید بعد از ساعت شروع کار باشد.'])
                ->withInput();
        }

        if ($data['checkout_end'] <= $data['checkout_start']) {
            return back()
                ->withErrors(['checkout_end' => 'پایان بازۀ خروج باید بعد از شروع آن باشد.'])
                ->withInput();
        }

        Setting::set('work_start', $data['work_start']);
        Setting::set('work_end', $data['work_end']);
        Setting::set('checkout_start', $data['checkout_start']);
        Setting::set('checkout_end', $data['checkout_end']);

        return back()->with('ok', 'ساعات کاری و بازۀ خروج ذخیره شد و از همین حالا اجرا می‌شود.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'password' => ['nullable', 'string', 'min:6'],
            'password_confirmation' => ['nullable', 'same:password'],
        ], [
            'name.required' => 'نام را وارد کنید.',
            'email.required' => 'ایمیل را وارد کنید.',
            'email.email' => 'ایمیل نامعتبر است.',
            'password.min' => 'رمز جدید حداقل 6 حرف باشد.',
            'password_confirmation.same' => 'تکرار رمز مطابقت ندارد.',
        ]);

        $user = $request->user();
        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return back()->with('ok', 'تنظیمات حساب ذخیره شد.');
    }
}
