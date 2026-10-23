<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sold_on' => 'date',
            'weight_kg' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'quantity' => 'integer',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function event()
    {
        return $this->hasOne(Event::class);
    }

    public function priceUnitLabel(): string
    {
        return ['kg' => 'kg', 'con' => 'con', 'lo' => 'cả lô'][$this->price_unit] ?? $this->price_unit;
    }
}
