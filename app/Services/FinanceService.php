<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Expense;
use App\Models\Farm;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tổng hợp chi phí / doanh thu / lời-lỗ. Chi phí của 1 lứa gồm:
 *  - con giống: tiền nhập ban đầu (batches.purchase_cost) + nhập thêm (events.cost, con_giong)
 *  - vật tư tiêu hao theo giá vốn bình quân (events.cost: thức ăn / thuốc-vaccine / vật tư / khác)
 *  - chi phí phát sinh khác gắn với lứa (expenses)
 */
class FinanceService
{
    /**
     * @param  list<int>  $batchIds
     * @return array<int, array<string, mixed>>
     */
    public function forBatches(array $batchIds): array
    {
        if ($batchIds === []) {
            return [];
        }

        $batches = Batch::query()->whereIn('id', $batchIds)->get()->keyBy('id');

        $eventCosts = DB::table('events')->whereIn('batch_id', $batchIds)
            ->selectRaw('batch_id, cost_category, SUM(cost) as total')
            ->groupBy('batch_id', 'cost_category')->get()->groupBy('batch_id');

        $heads = DB::table('events')->whereIn('batch_id', $batchIds)
            ->selectRaw("batch_id,
                SUM(CASE WHEN quantity_delta > 0 THEN quantity_delta ELSE 0 END) as added,
                SUM(CASE WHEN type IN ('chet', 'hao_hut') THEN -quantity_delta ELSE 0 END) as lost")
            ->groupBy('batch_id')->get()->keyBy('batch_id');

        $sales = DB::table('sales')->whereIn('batch_id', $batchIds)
            ->selectRaw('batch_id, SUM(total_amount) as revenue, SUM(quantity) as qty, SUM(COALESCE(weight_kg, 0)) as weight')
            ->groupBy('batch_id')->get()->keyBy('batch_id');

        $expenses = DB::table('expenses')->whereIn('batch_id', $batchIds)
            ->selectRaw('batch_id, SUM(amount) as total')
            ->groupBy('batch_id')->pluck('total', 'batch_id');

        $result = [];

        foreach ($batchIds as $id) {
            $batch = $batches[$id] ?? null;
            if (! $batch) {
                continue;
            }

            $costs = array_fill_keys(array_keys(config('cham.cost_categories')), 0.0);
            $costs['con_giong'] += (float) $batch->purchase_cost;

            foreach ($eventCosts[$id] ?? [] as $row) {
                $key = $row->cost_category && isset($costs[$row->cost_category]) ? $row->cost_category : 'khac';
                $costs[$key] += (float) $row->total;
            }

            $costs['khac'] += (float) ($expenses[$id] ?? 0);

            $result[$id] = $this->compose(
                $costs,
                (float) ($sales[$id]->revenue ?? 0),
                (int) ($sales[$id]->qty ?? 0),
                (float) ($sales[$id]->weight ?? 0),
                (int) $batch->initial_quantity + (int) ($heads[$id]->added ?? 0),
                (int) ($heads[$id]->lost ?? 0),
            );
        }

        return $result;
    }

    public function forBatch(Batch $batch): array
    {
        return $this->forBatches([$batch->id])[$batch->id];
    }

    /**
     * Lời/lỗ của cả trại trong 1 khoảng ngày (theo thời điểm phát sinh):
     * doanh thu = bán trong kỳ; chi phí = con giống nhập trong kỳ + vật tư tiêu hao trong kỳ + chi phí khác trong kỳ.
     *
     * @return array<string, mixed>
     */
    public function forFarmPeriod(Farm $farm, Carbon $from, Carbon $to): array
    {
        $fromDt = $from->copy()->startOfDay();
        $toDt = $to->copy()->endOfDay();

        $revenueRow = Sale::query()->where('farm_id', $farm->id)->whereBetween('sold_on', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('SUM(total_amount) as revenue, SUM(quantity) as qty')->first();

        $costs = array_fill_keys(array_keys(config('cham.cost_categories')), 0.0);

        $costs['con_giong'] += (float) Batch::query()->where('farm_id', $farm->id)
            ->whereBetween('started_on', [$from->toDateString(), $to->toDateString()])->sum('purchase_cost');

        $eventRows = DB::table('events')->where('farm_id', $farm->id)->whereBetween('occurred_at', [$fromDt, $toDt])
            ->selectRaw('cost_category, SUM(cost) as total')->groupBy('cost_category')->get();

        foreach ($eventRows as $row) {
            $key = $row->cost_category && isset($costs[$row->cost_category]) ? $row->cost_category : 'khac';
            $costs[$key] += (float) $row->total;
        }

        $costs['khac'] += (float) Expense::query()->where('farm_id', $farm->id)
            ->whereBetween('occurred_on', [$from->toDateString(), $to->toDateString()])->sum('amount');

        $revenue = (float) ($revenueRow->revenue ?? 0);
        $costTotal = array_sum($costs);

        return [
            'revenue' => $revenue,
            'sold_qty' => (int) ($revenueRow->qty ?? 0),
            'costs' => $costs,
            'cost_total' => $costTotal,
            'profit' => $revenue - $costTotal,
        ];
    }

    /** @param array<string, float> $costs */
    private function compose(array $costs, float $revenue, int $soldQty, float $soldWeight, int $headsTotal, int $lost): array
    {
        $costTotal = array_sum($costs);
        $profit = $revenue - $costTotal;

        return [
            'revenue' => $revenue,
            'sold_qty' => $soldQty,
            'sold_weight' => $soldWeight,
            'costs' => $costs,
            'cost_total' => $costTotal,
            'profit' => $profit,
            'heads_total' => $headsTotal,
            'lost' => $lost,
            'loss_rate' => $headsTotal > 0 ? $lost / $headsTotal * 100 : 0.0,
            'cost_per_head' => $headsTotal > 0 ? $costTotal / $headsTotal : 0.0,
            'revenue_per_sold_head' => $soldQty > 0 ? $revenue / $soldQty : null,
            'profit_per_sold_head' => $soldQty > 0 ? $profit / $soldQty : null,
            'revenue_per_kg' => $soldWeight > 0 ? $revenue / $soldWeight : null,
        ];
    }
}
