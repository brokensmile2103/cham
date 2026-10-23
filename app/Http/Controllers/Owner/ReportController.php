<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentFarm;
use App\Http\Controllers\Controller;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Báo cáo lời/lỗ: tổng quan theo kỳ + bảng theo từng lứa. */
class ReportController extends Controller
{
    use ResolvesCurrentFarm;

    public function index(Request $request, FinanceService $finance): View
    {
        $farm = $this->currentFarm($request, true);
        [$from, $to] = $this->range($request);

        $rows = $this->batchRows($farm, $finance, (string) $request->query('status', 'all'));

        return view('reports.index', [
            'farm' => $farm,
            'from' => $from,
            'to' => $to,
            'period' => $finance->forFarmPeriod($farm, $from, $to),
            'rows' => $rows,
            'status' => in_array($request->query('status'), ['active', 'closed'], true) ? $request->query('status') : 'all',
            'totals' => [
                'revenue' => collect($rows)->sum(fn ($r) => $r['summary']['revenue']),
                'cost' => collect($rows)->sum(fn ($r) => $r['summary']['cost_total']),
                'profit' => collect($rows)->sum(fn ($r) => $r['summary']['profit']),
            ],
        ]);
    }

    public function export(Request $request, FinanceService $finance): StreamedResponse
    {
        $farm = $this->currentFarm($request, true);
        $rows = $this->batchRows($farm, $finance, (string) $request->query('status', 'all'));
        $labels = config('cham.cost_categories');

        $filename = 'cham-loi-lo-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($rows, $labels) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM để Excel đọc đúng tiếng Việt

            fputcsv($out, array_merge(
                ['Mã', 'Lứa / vật nuôi', 'Loài', 'Trạng thái', 'Ngày nhập', 'Số con ban đầu', 'Số con hiện tại', 'Đã bán (con)', 'Hao hụt (%)'],
                array_values($labels),
                ['Tổng chi phí', 'Doanh thu', 'Lợi nhuận', 'Chi phí TB/con', 'Lãi TB/con bán']
            ));

            foreach ($rows as $r) {
                $b = $r['batch'];
                $s = $r['summary'];

                fputcsv($out, array_merge(
                    [csv_safe($b->code), csv_safe($b->name), csv_safe($b->species->name), $b->isActive() ? 'Đang nuôi' : 'Đã kết thúc', $b->started_on->format('d/m/Y'), $b->initial_quantity, $b->current_quantity, $s['sold_qty'], round($s['loss_rate'], 1)],
                    array_map(fn ($k) => round($s['costs'][$k]), array_keys($labels)),
                    [round($s['cost_total']), round($s['revenue']), round($s['profit']), round($s['cost_per_head']), $s['profit_per_sold_head'] !== null ? round($s['profit_per_sold_head']) : '']
                ));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function range(Request $request): array
    {
        $preset = $request->query('preset');

        $from = parse_date($request->query('from'));
        $to = parse_date($request->query('to'));

        if ($from && $to) {
            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }

            return [$from, $to];
        }

        return match ($preset) {
            'week' => [Carbon::today()->startOfWeek(), Carbon::today()],
            'quarter' => [Carbon::today()->startOfQuarter(), Carbon::today()],
            'year' => [Carbon::today()->startOfYear(), Carbon::today()],
            default => [Carbon::today()->startOfMonth(), Carbon::today()],
        };
    }

    /** @return list<array{batch: \App\Models\Batch, summary: array<string, mixed>}> */
    private function batchRows($farm, FinanceService $finance, string $status): array
    {
        $batches = $farm->batches()->with('species')
            ->when(in_array($status, ['active', 'closed'], true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('started_on')->orderByDesc('id')->get();

        $summaries = $finance->forBatches($batches->pluck('id')->all());

        return $batches->map(fn ($b) => ['batch' => $b, 'summary' => $summaries[$b->id]])->all();
    }
}
