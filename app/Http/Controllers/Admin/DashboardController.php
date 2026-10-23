<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * CLOUD — khu vực Super Admin, quản lý TOÀN BỘ trại đã đăng ký.
 * Không thuộc phạm vi 1 trại cụ thể nên KHÔNG dùng session current_farm_id.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $farms = Farm::withCount(['staff', 'batches', 'events'])
            ->with('owner')
            // Mốc hoạt động gần nhất — 1 phép tổng hợp cho cả trang, chỉ lấy đúng 1 mốc thời gian.
            ->withMax('events as last_event_at', 'occurred_at')
            ->when($request->query('q'), function ($q, $search) {
                $q->where(fn ($q2) => $q2->where('name', 'like', "%{$search}%")
                    ->orWhereHas('owner', fn ($q3) => $q3->where('phone', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")));
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => Farm::count(),
            'active' => Farm::where('is_active', true)->count(),
            'inactive' => Farm::where('is_active', false)->count(),
        ];

        return view('admin.dashboard', compact('farms', 'stats'));
    }

    /**
     * Chi tiết 1 trại — CHỈ để đánh giá trại còn hoạt động hay không (hỗ trợ vận hành).
     * CỐ TÌNH chỉ trả về SỐ LƯỢNG, tuyệt đối KHÔNG load nội dung nhật ký, giá, doanh thu,
     * lợi nhuận — dữ liệu chăn nuôi thật của từng trại không thuộc phạm vi Super Admin
     * (nguyên tắc data minimization).
     */
    public function show(Farm $farm): View
    {
        $farm->load('owner');

        $lastEventAt = $farm->events()->max('occurred_at');
        $firstEventAt = $farm->events()->min('occurred_at');

        return view('admin.farms.show', [
            'farm' => $farm,
            'stats' => [
                'staff_count' => $farm->staff()->count(),
                'species_count' => $farm->species()->count(),
                'active_batches' => $farm->batches()->where('status', 'active')->count(),
                'closed_batches' => $farm->batches()->where('status', 'closed')->count(),
                'event_count' => $farm->events()->count(),
                'item_count' => $farm->items()->count(),
                'sale_count' => $farm->sales()->count(),
                'routine_count' => $farm->taskTemplates()->where('is_active', true)->count(),
                'events_7d' => $farm->events()->where('occurred_at', '>=', now()->subDays(7))->count(),
                'events_30d' => $farm->events()->where('occurred_at', '>=', now()->subDays(30))->count(),
                'last_event_at' => $lastEventAt ? Carbon::parse($lastEventAt) : null,
                'first_event_at' => $firstEventAt ? Carbon::parse($firstEventAt) : null,
            ],
        ]);
    }

    /** Khoá/mở lại 1 trại — trại bị khoá thì chủ trại/thành viên không đăng nhập được nữa. */
    public function toggleActive(Farm $farm): RedirectResponse
    {
        $farm->forceFill(['is_active' => ! $farm->is_active])->save();

        return back()->with('status', 'Đã '.($farm->is_active ? 'mở lại' : 'tạm khoá').' trại "'.$farm->name.'".');
    }
}
