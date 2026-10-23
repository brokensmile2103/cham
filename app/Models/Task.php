<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'item_quantity' => 'decimal:3',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function template()
    {
        return $this->belongsTo(TaskTemplate::class, 'template_id');
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

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_at->isPast();
    }

    public function meta(): array
    {
        return config('cham.event_types.'.$this->type, config('cham.event_types.khac'));
    }

    public function targetLabel(): ?string
    {
        return match ($this->target) {
            'batch' => $this->batch?->name,
            'zone' => $this->zone ? 'Khu '.$this->zone->name : null,
            'all' => 'Tất cả vật nuôi',
            default => null,
        };
    }
}
