<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Breed extends Model
{
    protected $fillable = ['farm_id', 'species_id', 'name'];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function species()
    {
        return $this->belongsTo(Species::class);
    }
}
