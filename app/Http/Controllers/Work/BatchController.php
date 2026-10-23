<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Concerns\ResolvesCurrentFarm;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Farm;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Vật nuôi / Lứa nuôi. Xem: mọi thành viên. Tạo/sửa/đóng/xoá: chỉ chủ trại (route middleware). */
class BatchController extends Controller
{
    use ResolvesCurrentFarm;

    public function index(Request $request): View
    {
        $farm = $this->currentFarm($request);

        $status = in_array($request->query('status'), ['active', 'closed'], true) ? $request->query('status') : 'active';
        $speciesId = $request->query('species');

        $batches = $farm->batches()
            ->with(['species', 'zone', 'breed'])
            ->withMax('events as last_event_at', 'occurred_at')
            ->where('status', $status)
            ->when($speciesId, fn ($q) => $q->where('species_id', (int) $speciesId))
            ->orderByDesc('started_on')->orderByDesc('id')
            ->get();

        return view('batches.index', [
            'farm' => $farm,
            'batches' => $batches,
            'status' => $status,
            'speciesId' => $speciesId ? (int) $speciesId : null,
            'species' => $farm->species()->orderBy('sort_order')->orderBy('name')->get(),
            'counts' => [
                'active' => $farm->batches()->where('status', 'active')->count(),
                'closed' => $farm->batches()->where('status', 'closed')->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $farm = $this->currentFarm($request, true);

        return view('batches.form', $this->formData($farm, new Batch(['kind' => $request->query('kind') === 'individual' ? 'individual' : 'batch', 'species_id' => (int) $request->query('species') ?: null, 'started_on' => Carbon::today(), 'initial_quantity' => 1])));
    }

    public function store(Request $request): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $data = $this->validated($request, $farm);

        $species = $farm->species()->findOrFail($data['species_id']);
        $quantity = $data['kind'] === 'individual' ? 1 : (int) $data['initial_quantity'];
        $purchase = $this->purchaseTotal($data, $quantity);

        $batch = new Batch([
            'species_id' => $species->id,
            'breed_id' => $this->resolveBreed($farm, $species->id, $data['breed_name'] ?? null),
            'zone_id' => $this->resolveZone($farm, $data['zone_id'] ?? null, $data['zone_name'] ?? null),
            'kind' => $data['kind'],
            'code' => $this->nextCode($farm, $data['kind']),
            'name' => ($data['name'] ?? null) ?: $this->defaultName($species->name, $data['kind'], $data['started_on']),
            'gender' => $data['kind'] === 'individual' ? ($data['gender'] ?? null) : null,
            'birth_date' => $data['birth_date'] ?? null,
            'started_on' => $data['started_on'],
            'initial_quantity' => $quantity,
            'purchase_cost' => $purchase,
            'initial_weight_kg' => $data['initial_weight_kg'] ?? null,
            'source' => $data['source'] ?? null,
            'note' => $data['note'] ?? null,
        ]);
        $batch->farm_id = $farm->id;
        $batch->current_quantity = $quantity;
        $batch->status = 'active';
        $batch->save();

        return redirect()->route('batches.show', $batch->id)->with('status', 'Đã thêm "'.$batch->name.'".');
    }

    public function show(Request $request, FinanceService $finance, int $batch): View
    {
        $farm = $this->currentFarm($request);
        $batch = $farm->batches()->with(['species', 'zone', 'breed'])->findOrFail($batch);
        $isOwner = $request->user()->isOwner();

        $events = $batch->events()->with(['item', 'relatedBatch', 'creator'])
            ->latest('occurred_at')->latest('id')->paginate(25);

        $weights = $batch->events()->where('type', 'can')->whereNotNull('weight_kg')->orderBy('occurred_at')->get(['occurred_at', 'weight_kg']);

        // Tăng trọng TB/ngày (g/ngày): từ cân nặng lúc nhập (hoặc lần cân đầu) tới lần cân gần nhất.
        $adg = null;
        $lastWeight = $weights->last();
        if ($lastWeight) {
            $firstWeight = $batch->initial_weight_kg !== null
                ? ['kg' => (float) $batch->initial_weight_kg, 'at' => $batch->started_on]
                : ($weights->count() > 1 ? ['kg' => (float) $weights->first()->weight_kg, 'at' => $weights->first()->occurred_at] : null);

            if ($firstWeight) {
                $days = max(1, (int) $firstWeight['at']->copy()->startOfDay()->diffInDays($lastWeight->occurred_at->copy()->startOfDay()));
                $adg = ((float) $lastWeight->weight_kg - $firstWeight['kg']) / $days * 1000;
            }
        }

        $lost = (int) $batch->events()->whereIn('type', ['chet', 'hao_hut'])->sum('quantity_delta') * -1;

        return view('batches.show', [
            'farm' => $farm,
            'batch' => $batch,
            'events' => $events,
            'isOwner' => $isOwner,
            'summary' => $isOwner ? $finance->forBatch($batch) : null,
            'sales' => $isOwner ? $batch->sales()->latest('sold_on')->latest('id')->get() : collect(),
            'expenses' => $isOwner ? $batch->expenses()->latest('occurred_on')->latest('id')->limit(20)->get() : collect(),
            'weights' => $weights,
            'lastWeight' => $lastWeight,
            'adg' => $adg,
            'lost' => $lost,
            'zones' => $isOwner ? $farm->zones()->orderBy('name')->get() : collect(),
        ]);
    }

    public function edit(Request $request, int $batch): View
    {
        $farm = $this->currentFarm($request, true);

        return view('batches.form', $this->formData($farm, $farm->batches()->with(['breed', 'zone'])->findOrFail($batch)));
    }

    public function update(Request $request, int $batch): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $batch = $farm->batches()->findOrFail($batch);
        $data = $this->validated($request, $farm, $batch);

        $species = $farm->species()->findOrFail($data['species_id']);
        $quantity = $batch->isIndividual() ? 1 : (int) $data['initial_quantity'];

        // Không cho giảm số con ban đầu xuống dưới mức làm số hiện tại bị âm.
        $delta = (int) $batch->events()->sum('quantity_delta');
        if ($quantity + $delta < 0) {
            return back()->withInput()->withErrors(['initial_quantity' => 'Số con ban đầu quá thấp so với các ghi nhận tăng/giảm đã có (tối thiểu '.max(0, -$delta).').']);
        }

        $batch->fill([
            'species_id' => $species->id,
            'breed_id' => $this->resolveBreed($farm, $species->id, $data['breed_name'] ?? null),
            'zone_id' => $this->resolveZone($farm, $data['zone_id'] ?? null, $data['zone_name'] ?? null),
            'name' => ($data['name'] ?? null) ?: $batch->name,
            'gender' => $batch->isIndividual() ? ($data['gender'] ?? null) : null,
            'birth_date' => $data['birth_date'] ?? null,
            'started_on' => $data['started_on'],
            'initial_quantity' => $quantity,
            'purchase_cost' => $this->purchaseTotal($data, $quantity),
            'initial_weight_kg' => $data['initial_weight_kg'] ?? null,
            'source' => $data['source'] ?? null,
            'note' => $data['note'] ?? null,
        ]);
        $batch->save();
        $batch->recalcQuantity(allowReopen: true);

        return redirect()->route('batches.show', $batch->id)->with('status', 'Đã cập nhật.');
    }

    /** Chuyển nhanh sang khu vực khác (không cần mở form sửa đầy đủ). */
    public function move(Request $request, int $batch): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $batch = $farm->batches()->findOrFail($batch);

        $data = $request->validate([
            'zone_id' => ['nullable', Rule::exists('zones', 'id')->where('farm_id', $farm->id)],
        ]);

        $batch->update(['zone_id' => $data['zone_id'] ?: null]);

        return back()->with('status', 'Đã chuyển khu vực.');
    }

    public function close(Request $request, int $batch): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $batch = $farm->batches()->findOrFail($batch);

        $batch->forceFill(['status' => 'closed', 'closed_on' => Carbon::today()])->save();

        return back()->with('status', 'Đã kết thúc "'.$batch->name.'". Xem lời/lỗ ngay bên dưới.');
    }

    public function reopen(Request $request, int $batch): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $batch = $farm->batches()->findOrFail($batch);

        if ($batch->current_quantity <= 0) {
            return back()->with('warning', 'Lứa đã hết con nên không thể mở lại. Hãy ghi "Nhập thêm con" hoặc "Điều chỉnh số lượng" trước.');
        }

        $batch->forceFill(['status' => 'active', 'closed_on' => null])->save();

        return back()->with('status', 'Đã mở lại "'.$batch->name.'".');
    }

    public function destroy(Request $request, int $batch): RedirectResponse
    {
        $farm = $this->currentFarm($request, true);
        $batch = $farm->batches()->findOrFail($batch);
        $name = $batch->name;

        // Hoàn lại vật tư đã trừ kho cho lứa này trước khi xoá (event bị xoá theo cascade).
        // Bọc transaction: lỗi giữa chừng không được để kho đã hoàn mà lứa chưa xoá (hoàn 2 lần khi thử lại).
        \Illuminate\Support\Facades\DB::transaction(function () use ($batch) {
            $inventory = app(\App\Services\InventoryService::class);
            foreach ($batch->events()->whereNotNull('item_id')->get() as $event) {
                $inventory->restoreForEvent($event);
            }

            // Lứa đích của các lần "chuyển đàn đi" vẫn giữ số con đã nhận — chỉ gỡ liên kết ghép cặp.
            \App\Models\Event::query()->where('related_batch_id', $batch->id)->update(['group_key' => null]);

            $batch->delete();
        });

        return redirect()->route('batches.index')->with('status', 'Đã xoá "'.$name.'" cùng toàn bộ nhật ký, bán hàng và chi phí của lứa.');
    }

    // ---------------------------------------------------------------------

    private function formData(Farm $farm, Batch $batch): array
    {
        return [
            'farm' => $farm,
            'batch' => $batch,
            'species' => $farm->species()->with('breeds')->orderBy('sort_order')->orderBy('name')->get(),
            'zones' => $farm->zones()->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, Farm $farm, ?Batch $existing = null): array
    {
        $kind = $existing?->kind ?? $request->input('kind', 'batch');

        $data = $request->validate([
            'kind' => ['required', Rule::in(['batch', 'individual'])],
            'species_id' => ['required', Rule::exists('species', 'id')->where('farm_id', $farm->id)],
            'name' => 'nullable|string|max:100',
            'breed_name' => 'nullable|string|max:80',
            'zone_id' => ['nullable', Rule::exists('zones', 'id')->where('farm_id', $farm->id)],
            'zone_name' => 'nullable|string|max:80',
            'started_on' => 'required|date',
            'initial_quantity' => $kind === 'individual' ? 'nullable' : 'required|integer|min:1|max:10000000',
            'cost_mode' => 'nullable|in:total,unit',
            'purchase_cost' => 'nullable|numeric|min:0|max:100000000000',
            'initial_weight_kg' => 'nullable|numeric|min:0|max:100000',
            'gender' => 'nullable|in:duc,cai,khong_ro',
            'birth_date' => 'nullable|date|before_or_equal:today',
            'source' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:2000',
        ]);

        if ($existing) {
            $data['kind'] = $existing->kind;
        }

        return $data;
    }

    private function purchaseTotal(array $data, int $quantity): float
    {
        $cost = (float) ($data['purchase_cost'] ?? 0);

        return ($data['cost_mode'] ?? 'total') === 'unit' ? round($cost * $quantity, 2) : round($cost, 2);
    }

    private function resolveBreed(Farm $farm, int $speciesId, ?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $breed = $farm->breeds()->where('species_id', $speciesId)->where('name', $name)->first()
            ?? $farm->breeds()->create(['species_id' => $speciesId, 'name' => $name]);

        return $breed->id;
    }

    private function resolveZone(Farm $farm, $zoneId, ?string $newName): ?int
    {
        $newName = trim((string) $newName);

        if ($newName !== '') {
            return ($farm->zones()->where('name', $newName)->first() ?? $farm->zones()->create(['name' => $newName]))->id;
        }

        return $zoneId ? (int) $zoneId : null;
    }

    private function nextCode(Farm $farm, string $kind): string
    {
        $prefix = $kind === 'individual' ? 'C' : 'L';

        // Lấy số lớn nhất đang có (không dùng count: xoá 1 lứa rồi thêm mới sẽ bị trùng mã).
        $max = $farm->batches()->where('kind', $kind)->pluck('code')
            ->map(fn ($c) => preg_match('/^'.$prefix.'(\d+)$/', (string) $c, $m) ? (int) $m[1] : 0)
            ->max() ?? 0;

        return $prefix.str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    private function defaultName(string $speciesName, string $kind, $startedOn): string
    {
        $date = Carbon::parse($startedOn);

        return $kind === 'individual'
            ? $speciesName.' '.$date->format('d/m/Y')
            : $speciesName.' đợt '.$date->format('m/Y');
    }
}
