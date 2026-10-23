<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Concerns\ResolvesCurrentFarm;
use App\Http\Controllers\Controller;
use App\Services\TaskScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Màn hình "Hôm nay": việc cần làm, tổng quan đàn, cảnh báo kho, hoạt động gần đây. */
class TodayController extends Controller
{
    use ResolvesCurrentFarm;

    public function index(Request $request, TaskScheduler $scheduler): View
    {
        $farm = $this->currentFarm($request);

        // Sinh sẵn công việc định kỳ (idempotent) — không cần cron trên hosting rẻ.
        $scheduler->generateForFarm($farm);

        $today = Carbon::today();

        $tasks = $farm->tasks()
            ->with(['batch.species', 'zone', 'item'])
            ->where('status', 'pending')
            ->where('due_at', '>=', $today->copy()->subDays(7))
            ->where('due_at', '<=', $today->copy()->endOfDay())
            ->orderBy('due_at')
            ->get();

        $doneToday = $farm->tasks()->where('status', 'done')->whereDate('completed_at', $today)->count();

        $activeBatches = $farm->batches()->active()->with('species')->get();

        $bySpecies = $activeBatches->groupBy('species_id')->map(fn ($group) => [
            'species' => $group->first()->species,
            'animals' => $group->sum('current_quantity'),
            'batches' => $group->count(),
        ])->sortByDesc('animals')->values();

        $lowStock = $farm->items()->where('low_stock_threshold', '>', 0)
            ->whereColumn('current_stock', '<=', 'low_stock_threshold')->orderBy('name')->get();

        $recentEvents = $farm->events()->with(['batch.species', 'item'])->latest('occurred_at')->latest('id')->limit(6)->get();

        return view('today', [
            'farm' => $farm,
            'overdue' => $tasks->filter(fn ($t) => $t->due_at->lt($today)),
            'todayTasks' => $tasks->filter(fn ($t) => $t->due_at->gte($today)),
            'doneToday' => $doneToday,
            'animals' => $activeBatches->sum('current_quantity'),
            'batchCount' => $activeBatches->count(),
            'bySpecies' => $bySpecies,
            'lowStock' => $lowStock,
            'recentEvents' => $recentEvents,
            'needsSetup' => ! $farm->species()->exists(),
            'presets' => config('cham.species_presets'),
        ]);
    }
}
