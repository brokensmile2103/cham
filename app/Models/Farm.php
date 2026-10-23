<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Trại = 1 tenant. Mọi dữ liệu nghiệp vụ gắn farm_id. */
class Farm extends Model
{
    /** 'owner_id' và 'is_active' cố tình không fillable (xem User). */
    protected $fillable = ['name', 'address', 'font_scale', 'accent_color'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function staff()
    {
        return $this->belongsToMany(User::class);
    }

    public function species()
    {
        return $this->hasMany(Species::class);
    }

    public function breeds()
    {
        return $this->hasMany(Breed::class);
    }

    public function zones()
    {
        return $this->hasMany(Zone::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function items()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function taskTemplates()
    {
        return $this->hasMany(TaskTemplate::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
