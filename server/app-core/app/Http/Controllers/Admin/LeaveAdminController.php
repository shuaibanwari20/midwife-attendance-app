<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Midwife;
use App\Support\DariDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveAdminController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $leaves = Leave::with('midwife.cluster')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($leave) {
                $leave->display_range = DariDate::dayMonthYear($leave->from_date).' تا '.DariDate::dayMonthYear($leave->to_date);

                return $leave;
            });

        $midwives = Midwife::with('cluster')
            ->where('active', true)
            ->orderBy('code')
            ->get();

        return view('admin.leaves', [
            'leaves' => $leaves,
            'status' => $status,
            'midwives' => $midwives,
        ]);
    }

    /**
     * رخصتی رسمی دفتر — the admin grants official leave directly:
     * for ONE midwife or for ALL midwives (e.g. a public holiday).
     * Official leave is approved immediately and does NOT consume the
     * midwife's personal leave balance.
     */
    public function storeManual(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scope' => ['required', 'in:single,all'],
            'midwife_id' => ['nullable', 'integer', 'exists:midwives,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['nullable', 'string', 'max:300'],
        ], [
            'scope.required' => 'نوع رخصتی را انتخاب کنید.',
            'from_date.required' => 'تاریخ شروع رخصتی را وارد کنید.',
            'to_date.required' => 'تاریخ ختم رخصتی را وارد کنید.',
            'to_date.after_or_equal' => 'تاریخ ختم باید بعد از تاریخ شروع باشد.',
        ]);

        if ($data['scope'] === 'single' && empty($data['midwife_id'])) {
            return back()->withErrors(['midwife_id' => 'قابله را انتخاب کنید.'])->withInput();
        }

        $reason = $data['reason'] ?? 'رخصتی رسمی دفتر';

        $targets = $data['scope'] === 'all'
            ? Midwife::where('active', true)->get()
            : Midwife::where('id', $data['midwife_id'])->get();

        $created = 0;
        $skipped = 0;

        foreach ($targets as $midwife) {
            $overlap = Leave::where('midwife_id', $midwife->id)
                ->where('status', 'approved')
                ->whereDate('from_date', '<=', $data['to_date'])
                ->whereDate('to_date', '>=', $data['from_date'])
                ->exists();

            if ($overlap) {
                $skipped++;
                continue;
            }

            Leave::create([
                'midwife_id' => $midwife->id,
                'from_date' => $data['from_date'],
                'to_date' => $data['to_date'],
                'reason' => $reason,
                'status' => 'approved',
                'source' => 'office',
                'reviewed_by' => $request->user()->id,
                'review_note' => 'رخصتی رسمی دفتر',
            ]);
            $created++;
        }

        if ($created === 0) {
            return back()->withErrors(['leave' => 'رخصتی ثبت نشد — برای این تاریخ‌ها قبلاً رخصتی تاییدشده وجود دارد.']);
        }

        $message = $data['scope'] === 'all'
            ? 'رخصتی رسمی برای '.$created.' قابله ثبت شد.'
            : 'رخصتی رسمی ثبت شد.';
        if ($skipped > 0) {
            $message .= ' ('.$skipped.' قابله به دلیل داشتن رخصتی هم‌زمان رد شد.)';
        }

        return back()->with('ok', $message);
    }

    /** Delete a leave. Approved midwife-requests refund the balance days. */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status === 'approved' && $leave->source === 'request' && $leave->midwife) {
            $midwife = $leave->midwife;
            $midwife->update([
                'leave_balance' => $midwife->leave_balance + $leave->durationDays(),
            ]);
        }

        $leave->delete();

        return back()->with('ok', 'رخصتی حذف شد.');
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status !== 'pending') {
            return back()->withErrors(['leave' => 'این درخواست قبلاً بررسی شده است.']);
        }

        $leave->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'review_note' => $request->input('note'),
        ]);

        // Deduct leave balance (days inclusive)
        $midwife = $leave->midwife;
        if ($midwife) {
            $days = $leave->durationDays();
            $midwife->update(['leave_balance' => max(0, $midwife->leave_balance - $days)]);
        }

        return back()->with('ok', 'رخصتی تایید شد.');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status !== 'pending') {
            return back()->withErrors(['leave' => 'این درخواست قبلاً بررسی شده است.']);
        }

        $leave->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'review_note' => $request->input('note'),
        ]);

        return back()->with('ok', 'درخواست رخصتی رد شد.');
    }
}
