<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentFarm;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    use ResolvesCurrentFarm;

    public function index(Request $request): View
    {
        return view('settings.index', ['farm' => $this->currentFarm($request, true)]);
    }

    public function updateFarm(Request $request): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);

        $farm->update($request->validate([
            'name' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
        ]));

        return back()->with('status', 'Đã lưu thông tin trại.');
    }

    public function updateAppearance(Request $request): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);

        $farm->update($request->validate([
            'accent_color' => ['required', Rule::in(array_keys(config('cham.accent_colors')))],
            'font_scale' => 'required|in:sm,md,lg',
        ]));

        return back()->with('status', 'Đã lưu giao diện.');
    }
}
