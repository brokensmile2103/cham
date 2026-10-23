<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Concerns\ResolvesCurrentFarm;
use App\Http\Controllers\Controller;
use App\Services\EventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Nhật ký (event log): xem timeline + ghi chăm sóc / thay đổi số lượng. */
class EventController extends Controller
{
    use ResolvesCurrentFarm;

    public function index(Request $request): View
    {
        $farm = $this->currentFarm($request);

        $types = array_keys(config('cham.event_types'));

        $events = $farm->events()
            ->with(['batch.species', 'item', 'relatedBatch', 'creator'])
            ->when($request->query('batch'), fn ($q, $v) => $q->where('batch_id', (int) $v))
            ->when(in_array($request->query('type'), $types, true), fn ($q) => $q->where('type', $request->query('type')))
            ->when(parse_date($request->query('from')), fn ($q, $v) => $q->where('occurred_at', '>=', $v->copy()->startOfDay()))
            ->when(parse_date($request->query('to')), fn ($q, $v) => $q->where('occurred_at', '<=', $v->copy()->endOfDay()))
            ->latest('occurred_at')->latest('id')
            ->paginate(30)->withQueryString();

        return view('events.index', [
            'farm' => $farm,
            'events' => $events,
            'batches' => $farm->batches()->with('species')->orderByDesc('status')->orderBy('name')->get(),
            'isOwner' => $request->user()->isOwner(),
            'userId' => $request->user()->id,
        ]);
    }

    public function create(Request $request): View
    {
        $farm = $this->currentFarm($request);

        $type = in_array($request->query('type'), config('cham.loggable_types'), true) ? $request->query('type') : 'cho_an';

        $selected = collect((array) $request->query('batch'))->map(fn ($v) => (int) $v)->filter()->values()->all();

        return view('events.create', [
            'farm' => $farm,
            'type' => $type,
            'selected' => $selected,
            'batches' => $farm->batches()->active()->with(['species', 'zone'])->orderBy('name')->get(),
            'items' => $farm->items()->orderBy('category')->orderBy('name')->get(),
            'returnTo' => $this->safeReturn($request, ''),
        ]);
    }

    public function store(Request $request, EventService $service): RedirectResponse
    {
        $farm = $this->currentFarm($request);

        $type = (string) $request->input('type');
        abort_unless(in_array($type, config('cham.loggable_types'), true), 422);
        $meta = config('cham.event_types.'.$type);
        $isPop = $meta['group'] === 'pop';

        $rules = [
            'type' => 'required',
            'batch_ids' => 'required|array|min:1',
            'batch_ids.*' => ['integer', Rule::exists('batches', 'id')->where('farm_id', $farm->id)],
            'occurred_on' => 'required|date|before_or_equal:today',
            'occurred_time' => 'nullable|date_format:H:i',
            'note' => 'nullable|string|max:500',
        ];

        if ($isPop) {
            $rules['quantity'] = ['required', 'integer', $type === 'dieu_chinh' ? 'not_in:0' : 'min:1', 'max:10000000'];
            $rules['cost'] = 'nullable|numeric|min:0|max:100000000000';
            $rules['target_batch_id'] = ['nullable', Rule::exists('batches', 'id')->where('farm_id', $farm->id)];
        } else {
            $rules['item_id'] = ['nullable', Rule::exists('inventory_items', 'id')->where('farm_id', $farm->id)->whereNull('deleted_at')];
            $rules['item_quantity'] = 'nullable|numeric|min:0|max:100000000';
            $rules['weight_kg'] = [$type === 'can' ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100000'];
        }

        $data = $request->validate($rules, [
            'batch_ids.required' => 'Hãy chọn ít nhất 1 vật nuôi/lứa.',
            'weight_kg.required' => 'Nhập cân nặng trung bình mỗi con (kg).',
        ]);

        $when = $this->resolveWhen($data['occurred_on'], $data['occurred_time'] ?? null);
        $batches = $farm->batches()->active()->whereIn('id', array_unique(array_map('intval', $data['batch_ids'])))->with('species')->get();
        $return = $this->safeReturn($request, route('events.index'));

        if ($batches->count() !== count(array_unique(array_map('intval', $data['batch_ids'])))) {
            throw ValidationException::withMessages(['batch_ids' => 'Có lứa/vật nuôi đã kết thúc. Hãy mở lại lứa trước khi ghi nhật ký.']);
        }

        if ($isPop) {
            if ($batches->count() !== 1) {
                throw ValidationException::withMessages(['batch_ids' => 'Loại ghi nhận này chỉ áp dụng cho 1 lứa tại một thời điểm.']);
            }

            $service->logPopulation($farm, $request->user(), $batches->first(), $type, (int) $data['quantity'], $when, $data['note'] ?? null, [
                'cost' => $data['cost'] ?? 0,
                'target' => ! empty($data['target_batch_id']) ? $farm->batches()->find($data['target_batch_id']) : null,
            ]);

            return redirect()->to($return)->with('status', 'Đã ghi nhận: '.$meta['label'].'.');
        }

        $item = ! empty($data['item_id']) ? $farm->items()->find($data['item_id']) : null;

        $result = $service->logCare(
            $farm, $request->user(), $batches, $type, $when, $item,
            isset($data['item_quantity']) && $data['item_quantity'] !== null ? (float) $data['item_quantity'] : null,
            isset($data['weight_kg']) && $data['weight_kg'] !== null ? (float) $data['weight_kg'] : null,
            $data['note'] ?? null,
        );

        $redirect = redirect()->to($return)->with('status', 'Đã ghi nhật ký '.mb_strtolower($meta['label']).' cho '.$batches->count().' lứa/vật nuôi.');

        return $result['warnings'] ? $redirect->with('warning', implode(' ', $result['warnings'])) : $redirect;
    }

    public function destroy(Request $request, EventService $service, int $event): RedirectResponse
    {
        $farm = $this->currentFarm($request);
        $event = $farm->events()->findOrFail($event);
        $user = $request->user();

        if ($event->type === 'ban') {
            return back()->with('warning', 'Đơn bán được xoá ở mục Xuất bán.');
        }

        // Chủ trại xoá được mọi ghi nhận; thành viên chỉ xoá được ghi nhận CỦA MÌNH vừa tạo trong 2 giờ qua.
        $allowed = $user->isOwner() || ($event->created_by === $user->id && $event->created_at->gt(now()->subHours(2)));
        abort_unless($allowed, 403, 'Bạn chỉ xoá được ghi nhận của mình trong vòng 2 giờ.');

        $service->deleteEvent($event);

        return back()->with('status', 'Đã xoá ghi nhận.');
    }

    private function resolveWhen(string $date, ?string $time): Carbon
    {
        $day = Carbon::parse($date);

        if (! $time) {
            return $day->isToday() ? now() : $day->setTime(12, 0);
        }

        $when = $day->setTimeFromTimeString($time);

        return $when->isFuture() ? now() : $when;
    }
}
