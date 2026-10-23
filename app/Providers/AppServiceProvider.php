<?php

namespace App\Providers;

use App\Models\Farm;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Số liệu cho menu điều hướng của layouts.app: tính 1 LẦN tại đây (layout hiện diện ở mọi trang).
        // Gói trong try/catch: badge lỗi âm thầm về 0 KHÔNG được làm sập cả trang.
        View::composer('layouts.app', function ($view) {
            $view->with([
                'currentFarm' => null,
                'taskBadgeCount' => 0,
                'lowStockBadgeCount' => 0,
            ]);

            $user = auth()->user();
            $farmId = session('current_farm_id');

            if (! $user || ! $farmId) {
                return;
            }

            try {
                $farm = Farm::find($farmId);

                if (! $farm) {
                    return;
                }

                $allowed = $user->isOwner()
                    ? $farm->owner_id === $user->id
                    : $farm->staff()->where('users.id', $user->id)->exists();

                if (! $allowed) {
                    return;
                }

                $view->with([
                    'currentFarm' => $farm,
                    // Việc quá hạn + việc hôm nay chưa làm
                    // Cùng khung 7 ngày với danh sách ở Hôm nay/Công việc — badge không đếm việc quá cũ mà danh sách không hiện.
                    'taskBadgeCount' => $farm->tasks()->where('status', 'pending')
                        ->where('due_at', '>=', now()->startOfDay()->subDays(7))
                        ->where('due_at', '<=', now()->endOfDay())->count(),
                    'lowStockBadgeCount' => $farm->items()->where('low_stock_threshold', '>', 0)->whereColumn('current_stock', '<=', 'low_stock_threshold')->count(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }
}
