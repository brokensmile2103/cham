<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Farm;
use App\Models\InventoryItem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(private EventService $events) {}

    /**
     * Hoàn thành 1 công việc: nếu gắn với vật nuôi thì tự ghi vào nhật ký chăm sóc
     * (kèm trừ kho nếu có vật tư). Trả về các cảnh báo (VD: kho âm).
     *
     * @return list<string>
     */
    public function complete(Task $task, ?User $user, ?float $itemQty = null, ?float $weightKg = null, ?string $note = null): array
    {
        $warnings = [];

        DB::transaction(function () use ($task, $user, $itemQty, $weightKg, $note, &$warnings) {
            // Khoá dòng + đọc lại trạng thái: bấm "Xong" 2 lần (mạng chậm, 2 người cùng bấm)
            // không được ghi nhật ký / trừ kho 2 lần.
            $locked = Task::query()->whereKey($task->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isPending()) {
                return;
            }

            $farm = Farm::query()->find($locked->farm_id);
            $batches = $this->targetBatches($locked);
            $meta = config('cham.event_types.'.$locked->type);

            if ($farm && $batches->isNotEmpty() && $meta && $meta['group'] === 'care') {
                $item = $locked->item_id ? InventoryItem::query()->where('farm_id', $locked->farm_id)->find($locked->item_id) : null;
                $qty = $itemQty ?? ($locked->item_quantity !== null ? (float) $locked->item_quantity : null);

                $result = $this->events->logCare(
                    $farm,
                    $user,
                    $batches,
                    $locked->type,
                    now(),
                    $item,
                    $qty,
                    $locked->type === 'can' ? $weightKg : null,
                    trim($locked->title.($note ? ' — '.$note : '')),
                );

                $warnings = $result['warnings'];
            }

            $locked->forceFill([
                'status' => 'done',
                'completed_at' => now(),
                'completed_by' => $user?->id,
                'note' => $note ?: $locked->note,
            ])->save();

            $task->setRawAttributes($locked->getAttributes(), true);
        });

        return $warnings;
    }

    public function skip(Task $task, ?User $user): void
    {
        if ($task->isPending()) {
            $task->forceFill(['status' => 'skipped', 'completed_at' => now(), 'completed_by' => $user?->id])->save();
        }
    }

    /** Mở lại việc đã BỎ QUA (việc đã hoàn thành đã ghi nhật ký nên không mở lại được — hãy xoá nhật ký). */
    public function reopenSkipped(Task $task): void
    {
        if ($task->status === 'skipped') {
            $task->forceFill(['status' => 'pending', 'completed_at' => null, 'completed_by' => null])->save();
        }
    }

    /** @return Collection<int, Batch> */
    private function targetBatches(Task $task): Collection
    {
        $query = Batch::query()->where('farm_id', $task->farm_id)->active();

        return match ($task->target) {
            'batch' => $query->where('id', $task->batch_id)->get(),
            'zone' => $query->where('zone_id', $task->zone_id)->get(),
            'all' => $query->get(),
            default => collect(),
        };
    }
}
