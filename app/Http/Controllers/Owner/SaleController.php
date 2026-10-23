<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentFarm;
use App\Http\Controllers\Controller;
use App\Services\EventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Xuất chuồng / bán — có thể chia nhiều lần xuất trong cùng 1 lứa. */
class SaleController extends Controller
{
    use ResolvesCurrentFarm;

    public function index(Request $request): View
    {
        $farm = $this->currentFarm($request, true);

        $from = parse_date($request->query('from'), Carbon::today()->startOfMonth());
        $to = parse_date($request->query('to'), Carbon::today());
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $base = $farm->sales()->whereBetween('sold_on', [$from->toDateString(), $to->toDateString()]);

        return view('sales.index', [
            'farm' => $farm,
            'from' => $from,
            'to' => $to,
            'sales' => (clone $base)->with('batch.species')->latest('sold_on')->latest('id')->paginate(30)->withQueryString(),
            'totals' => [
                'amount' => (float) (clone $base)->sum('total_amount'),
                'quantity' => (int) (clone $base)->sum('quantity'),
                'count' => (clone $base)->count(),
            ],
        ]);
    }

    public function store(Request $request, EventService $service): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);

        $data = $request->validate([
            'batch_id' => ['required', Rule::exists('batches', 'id')->where('farm_id', $farm->id)],
            'sold_on' => 'required|date|before_or_equal:today',
            'quantity' => 'required|integer|min:1|max:10000000',
            'weight_kg' => 'nullable|numeric|min:0|max:100000000',
            'price_unit' => 'required|in:kg,con,lo',
            'unit_price' => 'required|numeric|min:0|max:100000000000',
            'buyer' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:500',
        ]);

        $batch = $farm->batches()->findOrFail($data['batch_id']);
        $sale = $service->recordSale($farm, $request->user(), $batch, $data);

        $batch->refresh();
        $message = 'Đã ghi nhận bán '.$sale->quantity.' con — '.money($sale->total_amount).'đ.';
        if ($batch->status === 'closed') {
            $message .= ' Lứa đã hết con nên tự kết thúc.';
        }

        return redirect()->to($this->safeReturn($request, route('batches.show', $batch->id)))->with('status', $message);
    }

    public function destroy(Request $request, EventService $service, int $sale): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $sale = $farm->sales()->findOrFail($sale);

        $service->deleteSale($sale);

        return back()->with('status', 'Đã xoá đơn bán và hoàn lại '.$sale->quantity.' con vào lứa.');
    }
}
