<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Khu vực / chuồng / ao nuôi. */
class Zone extends Model
{
    protected $fillable = ['name', 'capacity', 'note', 'sort_order'];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function activeBatches()
    {
        return $this->hasMany(Batch::class)->where('status', 'active');
    }
}
