@extends('layouts.admin')
@section('title', $farm->name.' · Super Admin')
@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-6">
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-neutral-900 truncate">{{ $farm->name }}</h1>
            <p class="text-sm text-neutral-500">Chủ trại: {{ $farm->owner?->name ?? '(đã xoá)' }} · {{ $farm->owner?->phone }} · Đăng ký {{ $farm->created_at->format('d/m/Y') }}</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-neutral-500 hover:text-neutral-700 flex items-center gap-1.5 shrink-0">
            <i class="fa-solid fa-arrow-left"></i>Quay lại
        </a>
    </div>

    <p class="text-xs text-neutral-500 bg-white border border-neutral-200 rounded-xl px-4 py-3 mb-5 leading-relaxed">
        <i class="fa-solid fa-shield-halved mr-1"></i>Chỉ hiển thị SỐ LIỆU hoạt động để hỗ trợ vận hành. Nội dung nhật ký, giá cả, doanh thu và lợi nhuận của trại không được hiển thị ở đây.
    </p>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 mb-6">
        @foreach([
            ['Đang nuôi (lứa/cá thể)', $stats['active_batches']],
            ['Đã kết thúc', $stats['closed_batches']],
            ['Loài đã tạo', $stats['species_count']],
            ['Thành viên', $stats['staff_count']],
            ['Tổng ghi nhận nhật ký', $stats['event_count']],
            ['Ghi nhận 7 ngày', $stats['events_7d']],
            ['Ghi nhận 30 ngày', $stats['events_30d']],
            ['Lịch định kỳ đang bật', $stats['routine_count']],
            ['Mặt hàng trong kho', $stats['item_count']],
            ['Lần xuất bán', $stats['sale_count']],
        ] as [$label, $value])
            <div class="bg-white rounded-2xl p-3 sm:p-4 border border-neutral-200">
                <p class="text-xs text-neutral-500 font-medium mb-1">{{ $label }}</p>
                <p class="text-xl font-bold text-neutral-900">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-neutral-200 p-4 sm:p-5 text-sm text-neutral-700 space-y-1.5 mb-5">
        <p>Ghi nhận đầu tiên: <strong>{{ $stats['first_event_at']?->format('d/m/Y H:i') ?? 'chưa có' }}</strong></p>
        <p>Ghi nhận gần nhất: <strong>{{ $stats['last_event_at']?->format('d/m/Y H:i') ?? 'chưa có' }}</strong>
            @if($stats['last_event_at']) <span class="text-neutral-400">({{ (int) $stats['last_event_at']->diffInDays(now()) }} ngày trước)</span> @endif
        </p>
    </div>

    <form method="POST" action="{{ route('admin.farms.toggle', $farm->id) }}"
          onsubmit="return confirm('{{ $farm->is_active ? 'Tạm khoá' : 'Mở lại' }} trại này?')">
        @csrf @method('PUT')
        <button class="text-sm px-4 py-2 rounded-xl font-semibold transition {{ $farm->is_active ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
            {{ $farm->is_active ? 'Tạm khoá trại' : 'Mở lại trại' }}
        </button>
    </form>
@endsection
