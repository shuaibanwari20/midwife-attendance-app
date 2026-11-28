<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayAdminController extends Controller
{
    /** Public holidays — non-obligated days for every midwife (his order, 2026-11-28). */
    public function index(): View
    {
        $holidays = Holiday::orderBy('date')->get();

        return view('admin.holidays', ['holidays' => $holidays]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'unique:holidays,date'],
            'name' => ['required', 'string', 'max:120'],
        ], [
            'date.unique' => 'برای این تاریخ قبلاً رخصتی ثبت شده است.',
        ]);

        Holiday::create($data);

        return back()->with('ok', 'رخصتی عمومی «'.$data['name'].'» اضافه شد.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $holiday = Holiday::findOrFail($id);
        $name = $holiday->name;
        $holiday->delete();

        return back()->with('ok', 'رخصتی عمومی «'.$name.'» حذف شد.');
    }
}
