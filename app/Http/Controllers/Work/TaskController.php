<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Concerns\ResolvesCurrentFarm;
use App\Http\Controllers\Controller;
use App\Services\TaskScheduler;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    use ResolvesCurrentFarm;

    public function index(Request $request, TaskScheduler $scheduler): View
    {
        $farm = $this->currentFarm($request);
        $scheduler->generateForFarm($farm);

        $tab = in_array($request->query('tab'), ['today', 'upcoming', 'done'], true) ? $request->query('tab') : 'today';
        $today = Carbon::today();

        $query = $farm->tasks()->with(['batch.species', 'zone', 'item', 'completer']);

        $tasks = match ($tab) {
            'upcoming' => $query->where('status', 'pending')->where('due_at', '>', $today->copy()->endOfDay())->orderBy('due_at')->limit(100)->get(),
            'done' => $query->whereIn('status', ['done', 'skipped'])->orderByDesc('completed_at')->limit(60)->get(),
            default => $query->where('status', 'pending')->where('due_at', '>=', $today->copy()->subDays(7))->where('due_at', '<=', $today->copy()->endOfDay())->orderBy('due_at')->get(),
        };

        return view('tasks.index', [
            'farm' => $farm,
            'tab' => $tab,
            'tasks' => $tasks,
            'batches' => $farm->batches()->active()->with('species')->orderBy('name')->get(),
            'zones' => $farm->zones()->orderBy('name')->get(),
            'items' => $farm->items()->orderBy('name')->get(),
        ]);
    }

    /** Thêm 1 việc làm 1 lần (không lặp). Việc lặp lại tạo ở "Lịch định kỳ". */
    public function store(Request $request): RedirectResponse
    {
        $farm = $this->currentFarm($request);

        $data = $request->validate([
            'title' => 'required|string|max:120',
            'type' => ['required', Rule::in(config('cham.task_types'))],
            'target' => 'required|in:batch,zone,all,none',
            'batch_id' => ['nullable', 'required_if:target,batch', Rule::exists('batches', 'id')->where('farm_id', $farm->id)],
            'zone_id' => ['nullable', 'required_if:target,zone', Rule::exists('zones', 'id')->where('farm_id', $farm->id)],
            'item_id' => ['nullable', Rule::exists('inventory_items', 'id')->where('farm_id', $farm->id)->whereNull('deleted_at')],
            'item_quantity' => 'nullable|numeric|min:0',
            'due_date' => 'required|date',
            'due_time' => 'required|date_format:H:i',
        ]);

        $farm->tasks()->create([
            'title' => $data['title'],
            'type' => $data['type'],
            'target' => $data['target'],
            'batch_id' => $data['target'] === 'batch' ? $data['batch_id'] : null,
            'zone_id' => $data['target'] === 'zone' ? $data['zone_id'] : null,
            'item_id' => $data['item_id'] ?? null,
            'item_quantity' => $data['item_quantity'] ?? null,
            'due_at' => Carbon::parse($data['due_date'].' '.$data['due_time']),
            'status' => 'pending',
        ]);

        return back()->with('status', 'Đã thêm công việc.');
    }

    public function complete(Request $request, TaskService $service, int $task): RedirectResponse
    {
        $farm = $this->currentFarm($request);
        $task = $farm->tasks()->findOrFail($task);

        $data = $request->validate([
            'item_quantity' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:200',
        ]);

        $warnings = $service->complete(
            $task,
            $request->user(),
            isset($data['item_quantity']) && $data['item_quantity'] !== '' ? (float) $data['item_quantity'] : null,
            isset($data['weight_kg']) && $data['weight_kg'] !== '' ? (float) $data['weight_kg'] : null,
            $data['note'] ?? null,
        );

        $redirect = back()->with('status', 'Đã hoàn thành: '.$task->title);

        return $warnings ? $redirect->with('warning', implode(' ', $warnings)) : $redirect;
    }

    public function skip(Request $request, TaskService $service, int $task): RedirectResponse
    {
        $farm = $this->currentFarm($request);
        $service->skip($farm->tasks()->findOrFail($task), $request->user());

        return back()->with('status', 'Đã bỏ qua công việc.');
    }

    public function reopen(Request $request, TaskService $service, int $task): RedirectResponse
    {
        $farm = $this->currentFarm($request);
        $service->reopenSkipped($farm->tasks()->findOrFail($task));

        return back()->with('status', 'Đã mở lại công việc.');
    }

    public function destroy(Request $request, int $task): RedirectResponse
    {
        $farm = $this->currentFarm($request);
        $task = $farm->tasks()->where('status', 'pending')->findOrFail($task);
        $task->delete();

        return back()->with('status', 'Đã xoá công việc.');
    }

    public function skipOverdue(Request $request): RedirectResponse
    {
        $farm = $this->currentFarm($request);

        $count = $farm->tasks()->where('status', 'pending')->where('due_at', '<', Carbon::today())
            ->update(['status' => 'skipped', 'completed_at' => now(), 'completed_by' => $request->user()->id]);

        return back()->with('status', "Đã bỏ qua {$count} công việc quá hạn.");
    }
}
