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
            'lateUntil' => AttendanceService::workEnd()->format('H:i'),
            'maxAccuracy' => config('midwife.max_gps_accuracy'),
        ]);
    }

    /** Save the working hours (شروع کار / ختم کار) — applies immediately. */
    public function updateRules(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'work_start' => ['required', 'date_format:H:i'],
            'work_end' => ['required', 'date_format:H:i'],
        ], [
            'work_start.required' => 'ساعت شروع کار را وارد کنید.',
            'work_start.date_format' => 'ساعت شروع کار نامعتبر است.',
            'work_end.required' => 'ساعت ختم کار را وارد کنید.',
            'work_end.date_format' => 'ساعت ختم کار نامعتبر است.',
        ]);

        if ($data['work_end'] <= $data['work_start']) {
            return back()
                ->withErrors(['work_end' => 'ساعت ختم کار باید بعد از ساعت شروع کار باشد.'])
                ->withInput();
        }

        Setting::set('work_start', $data['work_start']);
        Setting::set('work_end', $data['work_end']);

        return back()->with('ok', 'ساعات کاری ذخیره شد و از همین حالا اجرا می‌شود.');
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
