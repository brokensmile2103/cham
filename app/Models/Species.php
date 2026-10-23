<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Species extends Model
{
    protected $table = 'species';

    protected $fillable = ['name', 'emoji', 'group', 'sale_unit', 'sort_order'];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function breeds()
    {
        return $this->hasMany(Breed::class)->orderBy('name');
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function groupLabel(): string
    {
        return config('cham.species_groups.'.$this->group, 'Khác');
    }
}
