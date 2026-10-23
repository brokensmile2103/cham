<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Event;
use App\Models\Farm;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Ghi nhận mọi thay đổi vào event log: chăm sóc (cho ăn, thuốc, cân...),
 * thay đổi số lượng (chết, hao hụt, chuyển đàn...), xuất bán. Là NƠI DUY NHẤT
 * tạo/xoá Event để số lượng con, tồn kho và chi phí luôn khớp nhau.
 */
class EventService
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * Ghi 1 hoạt động chăm sóc cho 1 hoặc nhiều lứa. Nếu có vật tư và nhiều lứa,
     * tổng lượng được chia theo tỷ lệ số con của từng lứa.
     *
     * @return array{events: Collection<int, Event>, warnings: list<string>}
     */
    public function logCare(
        Farm $farm,
        ?User $user,
        Collection $batches,
        string $type,
        Carbon $when,
        ?InventoryItem $item = null,
        ?float $itemQty = null,
        ?float $weightKg = null,
        ?string $note = null,
    ): array {
        $meta = config('cham.event_types.'.$type);
        abort_unless($meta && $meta['group'] === 'care', 422, 'Loại hoạt động không hợp lệ.');

        $useItem = $meta['item'] && $item && $itemQty && $itemQty > 0;
        $events = collect();
        $warnings = [];

        DB::transaction(function () use ($farm, $user, $batches, $type, $when, $item, $itemQty, $weightKg, $note, $useItem, &$events, &$warnings) {
            $shares = $this->splitShares($batches, $useItem ? $itemQty : null);

            foreach ($batches->values() as $i => $batch) {
                $event = Event::create([
                    'farm_id' => $farm->id,
                    'batch_id' => $batch->id,
                    'type' => $type,
                    'occurred_at' => $when,
                    'weight_kg' => $type === 'can' ? $weightKg : null,
                    'item_id' => $useItem ? $item->id : null,
                    'item_quantity' => $useItem ? $shares[$i] : null,
                    'note' => $note,
                    'created_by' => $user?->id,
                ]);

                if ($useItem && $shares[$i] > 0) {
                    [$cost, $stockAfter] = $this->inventory->consume($item, $shares[$i], $when, $user, $batch->id, $event->id);

                    $event->update(['cost' => $cost, 'cost_category' => $item->costCategory()]);

                    if ($stockAfter < 0) {
                        $warnings[$item->id] = 'Kho "'.$item->name.'" đang âm — nhớ ghi nhập kho để giá vốn chính xác.';
                    }
                }

                $events->push($event);
            }
        });

        return ['events' => $events, 'warnings' => array_values($warnings)];
    }

    /**
     * Ghi thay đổi số lượng con của 1 lứa (chết, hao hụt, nhập thêm, sinh, chuyển đàn, điều chỉnh).
     * $opts: cost (nhập thêm), target (Batch đích khi chuyển đàn).
     */
    public function logPopulation(Farm $farm, ?User $user, Batch $batch, string $type, int $quantity, Carbon $when, ?string $note, array $opts = []): Event
    {
        $meta = config('cham.event_types.'.$type);
        abort_unless($meta && $meta['group'] === 'pop' && $type !== 'ban', 422, 'Loại hoạt động không hợp lệ.');

        return DB::transaction(function () use ($farm, $user, $batch, $type, $quantity, $when, $note, $opts, $meta) {
            $batch = Batch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();

            if ($type === 'dieu_chinh') {
                if ($quantity === 0) {
                    throw ValidationException::withMessages(['quantity' => 'Số lượng điều chỉnh phải khác 0.']);
                }
                $delta = $quantity; // có dấu
            } else {
                if ($quantity <= 0) {
                    throw ValidationException::withMessages(['quantity' => 'Số lượng phải lớn hơn 0.']);
                }
                $delta = $meta['sign'] * $quantity;
            }

            if ($batch->current_quantity + $delta < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Chỉ còn '.$batch->current_quantity.' con trong "'.$batch->name.'", không thể giảm '.abs($delta).' con.',
                ]);
            }

            $groupKey = null;
            $target = null;

            // Chuyển đàn: có lứa đích → ghép cặp đi/đến; không chọn lứa đích = chuyển ra ngoài trại (chỉ giảm số con).
            if ($type === 'chuyen_di' && ! empty($opts['target'])) {
                $target = Batch::query()->whereKey($opts['target']->id)->lockForUpdate()->first();
                if (! $target || $target->farm_id !== $farm->id || $target->id === $batch->id || ! $target->isActive()) {
                    throw ValidationException::withMessages(['target_batch_id' => 'Hãy chọn lứa đích đang nuôi (khác lứa hiện tại).']);
                }
                $groupKey = (string) Str::uuid();
            }

            $cost = $type === 'nhap_them' ? max(0, (float) ($opts['cost'] ?? 0)) : 0;

            $event = Event::create([
                'farm_id' => $farm->id,
                'batch_id' => $batch->id,
                'type' => $type,
                'occurred_at' => $when,
                'quantity_delta' => $delta,
                'cost' => $cost,
                'cost_category' => $cost > 0 ? 'con_giong' : null,
                'related_batch_id' => $target?->id,
                'group_key' => $groupKey,
                'note' => $note,
                'created_by' => $user?->id,
            ]);

            $batch->recalcQuantity();

            if ($target) {
                Event::create([
                    'farm_id' => $farm->id,
                    'batch_id' => $target->id,
                    'type' => 'chuyen_den',
                    'occurred_at' => $when,
                    'quantity_delta' => $quantity,
                    'related_batch_id' => $batch->id,
                    'group_key' => $groupKey,
                    'note' => $note,
                    'created_by' => $user?->id,
                ]);
                $target->recalcQuantity();
            }

            return $event;
        });
    }

    /** Xuất bán: tạo Sale + event 'ban' (trừ số con). Có thể bán nhiều lần trong 1 lứa. */
    public function recordSale(Farm $farm, ?User $user, Batch $batch, array $data): Sale
    {
        return DB::transaction(function () use ($farm, $user, $batch, $data) {
            $batch = Batch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $quantity = (int) $data['quantity'];

            if ($quantity <= 0 || $quantity > $batch->current_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Số con xuất phải từ 1 đến '.$batch->current_quantity.' (số con hiện có).',
                ]);
            }

            $priceUnit = $data['price_unit'];
            $weight = isset($data['weight_kg']) && $data['weight_kg'] !== '' ? (float) $data['weight_kg'] : null;
            $unitPrice = (float) $data['unit_price'];

            if ($priceUnit === 'kg' && ! $weight) {
                throw ValidationException::withMessages(['weight_kg' => 'Bán theo kg cần nhập tổng khối lượng.']);
            }

            $total = self::saleTotal($priceUnit, $quantity, $weight, $unitPrice);
            $soldOn = Carbon::parse($data['sold_on']);

            $sale = Sale::create([
                'farm_id' => $farm->id,
                'batch_id' => $batch->id,
                'sold_on' => $soldOn->toDateString(),
                'quantity' => $quantity,
                'weight_kg' => $weight,
                'price_unit' => $priceUnit,
                'unit_price' => $unitPrice,
                'total_amount' => $total,
                'buyer' => $data['buyer'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => $user?->id,
            ]);

            Event::create([
                'farm_id' => $farm->id,
                'batch_id' => $batch->id,
                'type' => 'ban',
                'occurred_at' => $soldOn->isToday() ? now() : $soldOn->copy()->setTime(12, 0),
                'quantity_delta' => -$quantity,
                'weight_kg' => $weight && $quantity > 0 ? round($weight / $quantity, 3) : null,
                'sale_id' => $sale->id,
                'note' => $data['note'] ?? null,
                'created_by' => $user?->id,
            ]);

            $batch->recalcQuantity();

            return $sale;
        });
    }

    public static function saleTotal(string $priceUnit, int $quantity, ?float $weight, float $unitPrice): float
    {
        return round(match ($priceUnit) {
            'kg' => ($weight ?? 0) * $unitPrice,
            'con' => $quantity * $unitPrice,
            default => $unitPrice, // lo = trọn lô
        }, 2);
    }

    public function deleteSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $batch = $sale->batch;
            $sale->event()->delete();
            $sale->delete();
            $batch->recalcQuantity(allowReopen: true);
        });
    }

    /** Xoá 1 event (hoàn lại tồn kho, ghép cặp chuyển đàn, tính lại số con). Không dùng cho event 'ban'. */
    public function deleteEvent(Event $event): void
    {
        abort_if($event->type === 'ban', 422, 'Hãy xoá ở mục Xuất bán.');

        DB::transaction(function () use ($event) {
            $events = $event->group_key
                ? Event::query()->where('group_key', $event->group_key)->get()
                : collect([$event]);

            // Kiểm tra: xoá xong không được làm số con của lứa nào bị âm.
            foreach ($events->groupBy('batch_id') as $batchId => $group) {
                $batch = Batch::query()->whereKey($batchId)->lockForUpdate()->first();
                if ($batch && $batch->current_quantity - $group->sum('quantity_delta') < 0) {
                    throw ValidationException::withMessages([
                        'event' => 'Không thể xoá vì số con của "'.$batch->name.'" sẽ bị âm. Hãy xoá các ghi nhận giảm số con sau đó trước.',
                    ]);
                }
            }

            $batchIds = $events->pluck('batch_id')->unique();

            foreach ($events as $e) {
                $this->inventory->restoreForEvent($e);
                $e->delete();
            }

            Batch::query()->whereIn('id', $batchIds)->get()->each(fn (Batch $b) => $b->recalcQuantity(allowReopen: true));
        });
    }

    /** @return list<float|null> */
    private function splitShares(Collection $batches, ?float $total): array
    {
        $n = $batches->count();

        if ($total === null) {
            return array_fill(0, $n, null);
        }

        $weights = $batches->values()->map(fn (Batch $b) => max(1, (int) $b->current_quantity))->all();
        $sum = array_sum($weights);
        $shares = [];
        $allocated = 0.0;

        foreach ($weights as $i => $w) {
            if ($i === $n - 1) {
                $share = round($total - $allocated, 3);
            } else {
                $share = round($total * $w / $sum, 3);
                $allocated += $share;
            }
            $shares[] = max(0.0, $share);
        }

        return $shares;
    }
}
