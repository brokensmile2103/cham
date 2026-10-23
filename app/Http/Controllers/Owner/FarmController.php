<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentFarm;
use App\Http\Controllers\Controller;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Quản lý nhiều trại (nếu chủ trại có nhiều cơ sở) và chuyển qua lại. */
class FarmController extends Controller
{
    use ResolvesCurrentFarm;

    public function index(Request $request): View
    {
        $current = $this->currentFarm($request, true);

        $farms = $request->user()->ownedFarms()
            ->withCount(['batches as active_batches_count' => fn ($q) => $q->where('status', 'active')])
            ->withSum(['batches as animals_count' => fn ($q) => $q->where('status', 'active')], 'current_quantity')
            ->orderBy('id')->get();

        return view('farms.index', ['farm' => $current, 'farms' => $farms]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        $farm = new \App\Models\Farm($data);
        $farm->owner_id = $user->id;
        $farm->is_active = true;
        $farm->save();
        $farm->staff()->attach($user->id);

        $request->session()->put('current_farm_id', $farm->id);

        return redirect()->route('today')->with('status', 'Đã tạo trại "'.$farm->name.'" và chuyển sang trại này.');
    }

    public function update(Request $request, int $farm): RedirectResponse
    {
        $farm = $request->user()->ownedFarms()->findOrFail($farm);

        $farm->update($request->validate([
            'name' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
        ]));

        return back()->with('status', 'Đã cập nhật trại.');
    }

    public function switch(Request $request, int $farm): RedirectResponse
    {
        $farm = $request->user()->ownedFarms()->where('is_active', true)->findOrFail($farm);
        $request->session()->put('current_farm_id', $farm->id);

        return redirect()->route('today')->with('status', 'Đang xem trại "'.$farm->name.'".');
    }
}
