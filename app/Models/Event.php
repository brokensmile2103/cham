<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'quantity_delta' => 'integer',
            'weight_kg' => 'decimal:3',
            'item_quantity' => 'decimal:3',
            'cost' => 'decimal:2',
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

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id')->withTrashed();
    }

    public function relatedBatch()
    {
        return $this->belongsTo(Batch::class, 'related_batch_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function meta(): array
    {
        return config('cham.event_types.'.$this->type, config('cham.event_types.khac'));
    }

    /** Mô tả ngắn gọn 1 dòng cho timeline. */
    public function summary(): string
    {
        $parts = [];

        if ($this->quantity_delta !== 0) {
            $parts[] = ($this->quantity_delta > 0 ? '+' : '−').abs($this->quantity_delta).' con';
        }

        if ($this->item_id && $this->item) {
            $parts[] = quantity($this->item_quantity).' '.$this->item->unit.' '.$this->item->name;
        }

        if ($this->weight_kg !== null) {
            $parts[] = 'TB '.quantity($this->weight_kg, 3).' kg/con';
        }

        if ($this->type === 'chuyen_di' && $this->relatedBatch) {
            $parts[] = '→ '.$this->relatedBatch->name;
        }

        if ($this->type === 'chuyen_den' && $this->relatedBatch) {
            $parts[] = '← '.$this->relatedBatch->name;
        }

        return implode(' · ', $parts);
    }
}
