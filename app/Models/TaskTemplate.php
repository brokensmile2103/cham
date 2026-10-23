<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Công việc định kỳ (routine) — sinh ra các Task theo lịch. */
class TaskTemplate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'last_generated_on' => 'date',
            'is_active' => 'boolean',
            'item_quantity' => 'decimal:3',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id')->withTrashed();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'template_id');
    }

    /** @return list<string> các giờ dạng "HH:MM", đã sắp xếp */
    public function timesList(): array
    {
        $times = array_values(array_filter(array_map('trim', explode(',', (string) $this->times))));
        sort($times);

        return $times ?: ['07:00'];
    }

    /** @return list<int> thứ ISO (1=T2..7=CN) */
    public function weekdaysList(): array
    {
        return array_values(array_unique(array_map('intval', array_filter(explode(',', (string) $this->weekdays)))));
    }

    public function targetLabel(): string
    {
        return match ($this->target) {
            'batch' => $this->batch?->name ?? '(lứa đã xoá)',
            'zone' => 'Khu '.($this->zone?->name ?? '(đã xoá)'),
            'all' => 'Tất cả vật nuôi đang nuôi',
            default => 'Chung (không gắn vật nuôi)',
        };
    }

    /** Mô tả lịch lặp bằng tiếng Việt, VD: "Mỗi ngày lúc 07:00, 17:00". */
    public function scheduleLabel(): string
    {
        $when = match ($this->frequency) {
            'weekly' => 'Hằng tuần vào '.implode(', ', array_map(
                fn ($d) => config('cham.weekday_labels.'.$d, $d), $this->weekdaysList()
            )),
            'monthly' => 'Hằng tháng vào ngày '.($this->month_day ?: 1),
            'interval' => 'Cách '.max(1, (int) $this->interval_days).' ngày',
            default => 'Mỗi ngày',
        };

        return $when.' lúc '.implode(', ', $this->timesList());
    }
}
