@extends('layouts.app')
@section('title', 'Hôm nay · Chăm')
@section('page-title', 'Hôm nay')
@section('content')
@php
    $isOwner = auth()->user()->isOwner();
    $totalToday = $todayTasks->count() + $doneToday;
    $quick = [
        ['cho_an', 'Cho ăn'], ['thuoc', 'Thuốc'], ['tiem_phong', 'Tiêm phòng'],
        ['can', 'Cân'], ['ve_sinh', 'Vệ sinh'], ['chet', 'Chết / hao hụt'],
    ];
@endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto space-y-5">

    <div>
        <h2 class="text-xl font-bold text-neutral-900">Xin chào, {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->last() }} 👋</h2>
        <p class="text-sm text-neutral-500">{{ day_label(now()) }} · {{ now()->format('d/m/Y') }} · {{ $farm->name }}</p>
    </div>

    @if($needsSetup)
        <div class="bg-white rounded-2xl border-2 border-dashed border-[var(--accent-ring)] p-5">
            <h3 class="font-bold text-neutral-900 mb-1"><i class="fa-solid fa-seedling text-[var(--accent)] mr-1.5"></i>Bắt đầu nào — bạn nuôi con gì?</h3>
            @if($isOwner)
                <p class="text-sm text-neutral-500 mb-4">Chọn các loài bạn nuôi (chọn nhiều được), Chăm sẽ thêm sẵn kèm các giống phổ biến. Thêm/sửa lại bất cứ lúc nào ở mục Loài, giống & khu vực.</p>
                <form method="POST" action="{{ route('catalog.quick-add') }}" x-data="{ picked: [] }">
                    @csrf
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach($presets as $p)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="presets[]" value="{{ $p['key'] }}" class="peer sr-only">
                                <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-full border border-neutral-300 bg-white text-sm font-medium text-neutral-700 peer-checked:bg-[var(--accent)] peer-checked:text-white peer-checked:border-[var(--accent)] transition select-none">
                                    <span class="text-base">{{ $p['emoji'] }}</span>{{ $p['name'] }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-button icon="fa-check">Thêm các loài đã chọn</x-button>
                </form>
            @else
                <p class="text-sm text-neutral-500">Chủ trại chưa thêm loài vật nuôi nào. Hãy nhờ chủ trại thiết lập trước nhé.</p>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-3 gap-2 sm:gap-3">
        <a href="{{ route('batches.index') }}" class="bg-white rounded-2xl p-3 sm:p-4 border border-neutral-200 shadow-sm hover:border-[var(--accent-ring)] transition">
            <p class="text-xs text-neutral-500 font-medium mb-1">Đang nuôi</p>
            <p class="text-xl sm:text-2xl font-bold text-neutral-900">{{ money($animals) }}<span class="text-xs font-medium text-neutral-400 ml-1">con</span></p>
        </a>
        <a href="{{ route('batches.index') }}" class="bg-white rounded-2xl p-3 sm:p-4 border border-neutral-200 shadow-sm hover:border-[var(--accent-ring)] transition">
            <p class="text-xs text-neutral-500 font-medium mb-1">Lứa / cá thể</p>
            <p class="text-xl sm:text-2xl font-bold text-neutral-900">{{ $batchCount }}</p>
        </a>
        <a href="{{ route('tasks.index') }}" class="bg-white rounded-2xl p-3 sm:p-4 border border-neutral-200 shadow-sm hover:border-[var(--accent-ring)] transition">
            <p class="text-xs text-neutral-500 font-medium mb-1">Việc hôm nay</p>
            <p class="text-xl sm:text-2xl font-bold text-neutral-900">{{ $doneToday }}<span class="text-sm font-medium text-neutral-400">/{{ $totalToday }}</span></p>
        </a>
    </div>

    <div>
        <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Ghi nhanh</p>
        <div class="grid grid-cols-3 gap-2">
            @foreach($quick as [$type, $label])
                @php $m = config('cham.event_types.'.$type); @endphp
                <a href="{{ route('events.create', ['type' => $type]) }}" class="flex flex-col items-center gap-1.5 bg-white rounded-xl border border-neutral-200 shadow-sm py-3 px-1 hover:border-[var(--accent-ring)] active:scale-95 transition">
                    <span class="w-9 h-9 rounded-xl {{ color_badge($m['color']) }} flex items-center justify-center"><i class="fa-solid {{ $m['icon'] }}"></i></span>
                    <span class="text-xs font-semibold text-neutral-700 text-center leading-tight">{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </div>

    @if($overdue->isNotEmpty())
        <div>
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-bold text-red-500 uppercase tracking-wider"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Quá hạn ({{ $overdue->count() }})</p>
                <form method="POST" action="{{ route('tasks.skip-overdue') }}" onsubmit="return confirm('Bỏ qua tất cả công việc quá hạn?')">
                    @csrf
                    <button class="text-xs text-neutral-500 hover:text-neutral-700 underline">Bỏ qua tất cả</button>
                </form>
            </div>
            <div class="space-y-2">
                @foreach($overdue as $task) @include('tasks._row', ['task' => $task]) @endforeach
            </div>
        </div>
    @endif

    <div>
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider">Việc hôm nay</p>
            <a href="{{ route('tasks.index') }}" class="text-xs font-semibold text-[var(--accent-text)] hover:underline">Xem tất cả</a>
        </div>
        @if($todayTasks->isEmpty())
            <div class="bg-white rounded-xl border border-neutral-200 px-4 py-6 text-center text-sm text-neutral-400">
                <i class="fa-solid fa-circle-check text-2xl text-emerald-400 mb-2 block"></i>
                @if($totalToday > 0) Đã xong hết việc hôm nay, giỏi quá! @else Hôm nay chưa có việc nào.
                    @if($isOwner) <a href="{{ route('routines.create') }}" class="text-[var(--accent-text)] font-semibold hover:underline">Tạo lịch định kỳ</a> để việc tự xuất hiện mỗi ngày. @endif
                @endif
            </div>
        @else
            <div class="space-y-2">
                @foreach($todayTasks as $task) @include('tasks._row', ['task' => $task]) @endforeach
            </div>
        @endif
    </div>

    @if($lowStock->isNotEmpty())
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
            <p class="text-sm font-bold text-red-700 mb-2"><i class="fa-solid fa-box-open mr-1.5"></i>Kho sắp hết</p>
            <div class="space-y-1">
                @foreach($lowStock as $it)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-neutral-800">{{ $it->name }}</span>
                        <span class="text-red-600 font-semibold">còn {{ quantity($it->current_stock) }} {{ $it->unit }}</span>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('inventory.index') }}" class="inline-block mt-2 text-xs font-semibold text-red-700 hover:underline">Mở kho →</a>
        </div>
    @endif

    @if($bySpecies->isNotEmpty())
        <div>
            <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Đàn của bạn</p>
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm divide-y divide-neutral-100">
                @foreach($bySpecies as $row)
                    <a href="{{ route('batches.index', ['species' => $row['species']->id]) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-neutral-50 transition">
                        <span class="text-2xl">{{ $row['species']->emoji }}</span>
                        <span class="flex-1 text-sm font-semibold text-neutral-800">{{ $row['species']->name }}</span>
                        <span class="text-xs text-neutral-400">{{ $row['batches'] }} lứa</span>
                        <span class="text-sm font-bold text-neutral-900 w-20 text-right">{{ money($row['animals']) }} con</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if($recentEvents->isNotEmpty())
        <div>
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider">Vừa ghi nhận</p>
                <a href="{{ route('events.index') }}" class="text-xs font-semibold text-[var(--accent-text)] hover:underline">Mở nhật ký</a>
            </div>
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm px-4">
                @foreach($recentEvents as $event) @include('events._row', ['event' => $event, 'showBatch' => true]) @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
