<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentFarm;
use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\TaskTemplate;
use App\Services\TaskScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Công việc định kỳ (routine): cho ăn mỗi ngày, vệ sinh mỗi tuần, tiêm vaccine theo lịch... */
class RoutineController extends Controller
{
    use ResolvesCurrentFarm;

    public function index(Request $request): View
    {
        $farm = $this->currentFarm($request, true);

        return view('routines.index', [
            'farm' => $farm,
            'templates' => $farm->taskTemplates()->with(['batch', 'zone', 'item'])->orderByDesc('is_active')->orderBy('title')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $farm = $this->currentFarm($request, true);

        return view('routines.form', $this->formData($farm, new TaskTemplate([
            'type' => $request->query('type', 'cho_an'),
            'target' => 'all',
            'frequency' => 'daily',
            'times' => '07:00,17:00',
            'starts_on' => Carbon::today(),
        ])));
    }

    public function store(Request $request, TaskScheduler $scheduler): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);

        $template = new TaskTemplate($this->payload($request, $farm));
        $template->farm_id = $farm->id;
        $template->created_by = $request->user()->id;
        $template->is_active = true;
        $template->save();

        $scheduler->regenerate($template);

        return redirect()->route('routines.index')->with('status', 'Đã tạo lịch "'.$template->title.'" — công việc sẽ tự xuất hiện ở mục Hôm nay.');
    }

    public function edit(Request $request, int $template): View
    {
        $farm = $this->currentFarm($request, true);

        return view('routines.form', $this->formData($farm, $farm->taskTemplates()->findOrFail($template)));
    }

    public function update(Request $request, TaskScheduler $scheduler, int $template): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $template = $farm->taskTemplates()->findOrFail($template);

        $template->fill($this->payload($request, $farm))->save();
        $scheduler->regenerate($template);

        return redirect()->route('routines.index')->with('status', 'Đã cập nhật lịch "'.$template->title.'".');
    }

    public function toggle(Request $request, TaskScheduler $scheduler, int $template): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $template = $farm->taskTemplates()->findOrFail($template);

        $template->forceFill(['is_active' => ! $template->is_active])->save();
        $scheduler->regenerate($template);

        return back()->with('status', $template->is_active ? 'Đã bật lại lịch.' : 'Đã tạm dừng lịch — các việc chưa làm sắp tới đã được gỡ.');
    }

    public function destroy(Request $request, TaskScheduler $scheduler, int $template): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $template = $farm->taskTemplates()->findOrFail($template);

        $scheduler->clearFuturePending($template);
        $template->delete(); // các task đã làm giữ lại làm lịch sử (template_id về null)

        return back()->with('status', 'Đã xoá lịch "'.$template->title.'".');
    }

    private function formData(Farm $farm, TaskTemplate $template): array
    {
        return [
            'farm' => $farm,
            'template' => $template,
            'batches' => $farm->batches()->active()->with('species')->orderBy('name')->get(),
            'zones' => $farm->zones()->orderBy('name')->get(),
            'items' => $farm->items()->orderBy('name')->get(),
        ];
    }

    private function payload(Request $request, Farm $farm): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'type' => ['required', Rule::in(config('cham.task_types'))],
            'target' => 'required|in:batch,zone,all,none',
            'batch_id' => ['nullable', 'required_if:target,batch', Rule::exists('batches', 'id')->where('farm_id', $farm->id)],
            'zone_id' => ['nullable', 'required_if:target,zone', Rule::exists('zones', 'id')->where('farm_id', $farm->id)],
            'item_id' => ['nullable', Rule::exists('inventory_items', 'id')->where('farm_id', $farm->id)->whereNull('deleted_at')],
            'item_quantity' => 'nullable|numeric|min:0|max:100000000',
            'frequency' => ['required', Rule::in(array_keys(config('cham.frequencies')))],
            'weekdays' => 'nullable|required_if:frequency,weekly|array',
            'weekdays.*' => 'integer|between:1,7',
            'month_day' => 'nullable|required_if:frequency,monthly|integer|between:1,31',
            'interval_days' => 'nullable|required_if:frequency,interval|integer|between:1,365',
            'times' => 'required|array|min:1|max:8',
            'times.*' => 'required|date_format:H:i',
            'starts_on' => 'required|date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
            'note' => 'nullable|string|max:255',
        ], [
            'weekdays.required_if' => 'Hãy chọn ít nhất 1 ngày trong tuần.',
            'times.required' => 'Hãy nhập ít nhất 1 giờ.',
        ]);

        $times = collect($data['times'])->unique()->sort()->values()->implode(',');

        return [
            'title' => $data['title'],
            'type' => $data['type'],
            'target' => $data['target'],
            'batch_id' => $data['target'] === 'batch' ? $data['batch_id'] : null,
            'zone_id' => $data['target'] === 'zone' ? $data['zone_id'] : null,
            'item_id' => $data['item_id'] ?? null,
            'item_quantity' => ($data['item_id'] ?? null) ? ($data['item_quantity'] ?? null) : null,
            'frequency' => $data['frequency'],
            'weekdays' => $data['frequency'] === 'weekly' ? collect($data['weekdays'] ?? [])->map(fn ($d) => (int) $d)->unique()->sort()->implode(',') : null,
            'month_day' => $data['frequency'] === 'monthly' ? (int) $data['month_day'] : null,
            'interval_days' => $data['frequency'] === 'interval' ? (int) $data['interval_days'] : null,
            'times' => $times,
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'] ?? null,
            'note' => $data['note'] ?? null,
        ];
    }
}
