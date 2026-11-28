<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cluster;
use App\Models\Midwife;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class MidwifeAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.midwives.index', [
            'midwives' => Midwife::with('cluster')->orderBy('code')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.midwives.form', [
            'midwife' => null,
            'clusters' => Cluster::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['pin_hash'] = Hash::make($data['pin']);
        unset($data['pin']);
        $data['code'] = $this->nextCode();
        $data['active'] = $request->boolean('active', true);

        Midwife::create($data);

        return redirect()->route('admin.midwives.index')->with('ok', 'قابله جدید ثبت شد.');
    }

    public function edit(int $id): View
    {
        $midwife = Midwife::findOrFail($id);

        return view('admin.midwives.form', [
            'midwife' => $midwife,
            'clusters' => Cluster::orderBy('code')->get(),
            'bioState' => \App\Support\BiometricGuard::state($midwife),
            'bioKey' => \App\Models\BiometricKey::where('midwife_id', $midwife->id)->latest('id')->first(),
        ]);
    }

    /**
     * Issue a one-time fingerprint enrollment code (30 minutes). The plain
     * code is flashed to the session once so the admin can read it to the
     * midwife in person — it is never stored in plain text.
     */
    public function issueBiometricCode(int $id): RedirectResponse
    {
        $midwife = Midwife::findOrFail($id);
        $plain = (string) random_int(100000, 999999);

        \App\Models\BiometricCode::create([
            'midwife_id' => $midwife->id,
            'code_hash' => \Illuminate\Support\Facades\Hash::make($plain),
            'expires_at' => now()->addMinutes(30),
        ]);

        return back()
            ->with('ok', 'کد ثبت‌نام اثر انگشت ساخته شد.')
            ->with('bio_code', $plain);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $midwife = Midwife::findOrFail($id);
        $data = $this->validated($request, (int) $midwife->id);

        if (! empty($data['pin'])) {
            $data['pin_hash'] = Hash::make($data['pin']);
        }
        unset($data['pin']);
        $data['active'] = $request->boolean('active');

        $midwife->update($data);

        return redirect()->route('admin.midwives.index')->with('ok', 'معلومات قابله ویرایش شد.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $midwife = Midwife::findOrFail($id);
        $midwife->update(['active' => false]);

        return redirect()->route('admin.midwives.index')->with('ok', 'قابله غیرفعال شد (سوابق حفظ شد).');
    }

    public function resetPin(Request $request, int $id): RedirectResponse
    {
        $midwife = Midwife::findOrFail($id);
        $request->validate([
            'pin' => ['required', 'string', 'min:4', 'max:8'],
        ], [
            'pin.required' => 'پین جدید را وارد کنید.',
            'pin.min' => 'پین حداقل 4 رقم باشد.',
        ]);

        $midwife->update(['pin_hash' => Hash::make($request->input('pin'))]);

        return back()->with('ok', 'پین '.$midwife->full_name.' تغییر کرد.');
    }

    public function unbindDevice(int $id): RedirectResponse
    {
        $midwife = Midwife::findOrFail($id);
        $midwife->update(['device_id' => null]);

        return back()->with('ok', 'اتصال دستگاه '.$midwife->full_name.' قطع شد. او می‌تواند با دستگاه جدید وارد شود.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'cluster_id' => ['required', 'exists:clusters,id'],
            'pin' => ['nullable', 'string', 'min:4', 'max:8'],
            'leave_balance' => ['nullable', 'integer', 'min:0', 'max:365'],
        ], [
            'full_name.required' => 'نام قابله را وارد کنید.',
            'cluster_id.required' => 'کلستر را انتخاب کنید.',
            'cluster_id.exists' => 'کلستر نامعتبر است.',
            'pin.min' => 'پین حداقل 4 رقم باشد.',
        ]) + ['leave_balance' => (int) $request->input('leave_balance', 12)];
    }

    private function nextCode(): string
    {
        $max = (int) Midwife::max('id');

        return 'MW-'.str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }
}
