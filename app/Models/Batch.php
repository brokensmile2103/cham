<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Lứa nuôi (kind=batch) hoặc 1 cá thể (kind=individual). current_quantity là số
 * cache — luôn đồng bộ lại bằng recalcQuantity() sau mọi event làm đổi số lượng.
 */
class Batch extends Model
{
    protected $fillable = [
        'species_id', 'breed_id', 'zone_id', 'kind', 'code', 'name', 'gender', 'birth_date', 'started_on',
        'initial_quantity', 'purchase_cost', 'initial_weight_kg', 'source', 'note',
    ];

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'birth_date' => 'date',
            'closed_on' => 'date',
            'purchase_cost' => 'decimal:2',
            'initial_weight_kg' => 'decimal:3',
            'initial_quantity' => 'integer',
            'current_quantity' => 'integer',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function species()
    {
        return $this->belongsTo(Species::class);
    }

    public function breed()
    {
        return $this->belongsTo(Breed::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isIndividual(): bool
    {
        return $this->kind === 'individual';
    }

    /** Tuổi (ngày): tính từ ngày sinh nếu có, ngược lại từ ngày nhập; dừng ở ngày kết thúc nếu đã đóng. */
    public function ageDays(): int
    {
        $from = $this->birth_date ?? $this->started_on;
        $to = $this->closed_on ?? Carbon::today();

        return max(0, (int) $from->diffInDays($to));
    }

    public function ageLabel(): string
    {
        $days = $this->ageDays();

        if ($days < 60) {
            return $days.' ngày';
        }

        if ($days < 730) {
            return round($days / 30).' tháng';
        }

        return number_format($days / 365, 1, ',', '.').' năm';
    }

    /**
     * Đồng bộ lại số lượng từ event log; tự kết thúc lứa khi hết con.
     * $allowReopen = true khi XOÁ event/đơn bán làm số lượng > 0 trở lại (mở lại lứa đã tự đóng).
     */
    public function recalcQuantity(bool $allowReopen = false): void
    {
        $before = (int) $this->current_quantity;
        $this->current_quantity = (int) $this->initial_quantity + (int) $this->events()->sum('quantity_delta');

        // Chỉ mở lại lứa đã TỰ ĐÓNG vì hết con — lứa chủ trại chủ động "Kết thúc" (còn con) giữ nguyên.
        if ($allowReopen && $before <= 0 && $this->current_quantity > 0 && $this->status === 'closed') {
            $this->status = 'active';
            $this->closed_on = null;
        }

        if ($this->current_quantity <= 0 && $this->status === 'active') {
            $this->status = 'closed';
            $this->closed_on = $this->events()->max('occurred_at')
                ? Carbon::parse($this->events()->max('occurred_at'))->toDateString()
                : Carbon::today()->toDateString();
        }

        $this->save();
    }
}
