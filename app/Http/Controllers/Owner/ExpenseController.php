<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentFarm;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Chi phí phát sinh khác (điện nước, nhân công, thú y...) — gắn với 1 lứa hoặc chi phí chung của trại. */
class ExpenseController extends Controller
{
    use ResolvesCurrentFarm;

    public function index(Request $request): View
    {
        $farm = $this->currentFarm($request, true);

        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('month')) ? Carbon::parse($request->query('month').'-01') : Carbon::today()->startOfMonth();

        $base = $farm->expenses()->whereBetween('occurred_on', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()]);

        return view('expenses.index', [
            'farm' => $farm,
            'month' => $month,
            'expenses' => (clone $base)->with('batch')->latest('occurred_on')->latest('id')->get(),
            'total' => (float) (clone $base)->sum('amount'),
            'byCategory' => (clone $base)->selectRaw('category, SUM(amount) as total')->groupBy('category')->pluck('total', 'category'),
            'batches' => $farm->batches()->orderByDesc('status')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);

        $data = $request->validate([
            'batch_id' => ['nullable', Rule::exists('batches', 'id')->where('farm_id', $farm->id)],
            'category' => ['required', Rule::in(array_keys(config('cham.expense_categories')))],
            'amount' => 'required|numeric|gt:0|max:100000000000',
            'occurred_on' => 'required|date|before_or_equal:today',
            'description' => 'nullable|string|max:255',
        ]);

        $farm->expenses()->create([
            'batch_id' => $data['batch_id'] ?? null,
            'category' => $data['category'],
            'amount' => $data['amount'],
            'occurred_on' => $data['occurred_on'],
            'description' => $data['description'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->to($this->safeReturn($request, route('expenses.index')))->with('status', 'Đã ghi nhận chi phí '.money($data['amount']).'đ.');
    }

    public function destroy(Request $request, int $expense): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $farm->expenses()->findOrFail($expense)->delete();

        return back()->with('status', 'Đã xoá khoản chi.');
    }
}
