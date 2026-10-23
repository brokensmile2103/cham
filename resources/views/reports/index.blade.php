@extends('layouts.app')
@section('title', 'Lời / lỗ · Chăm')
@section('page-title', 'Lời / lỗ')
@section('content')
@php
    $labels = config('cham.cost_categories');
    $presets = ['week' => 'Tuần này', 'month' => 'Tháng này', 'quarter' => 'Quý này', 'year' => 'Năm nay'];
    $profitClass = fn ($v) => $v >= 0 ? 'text-emerald-700' : 'text-red-600';
@endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-chart-line"></i></div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Lời / lỗ</h2>
        <a href="{{ route('reports.export', ['status' => $status]) }}" class="text-xs font-semibold text-[var(--accent-text)]"><i class="fa-solid fa-file-csv mr-1"></i>Xuất CSV</a>
    </div>

    {{-- Theo kỳ --}}
    <div class="flex gap-2 overflow-x-auto scrollbar-hide pb-2 mb-2 -mx-1 px-1">
        @foreach($presets as $k => $l)
            <a href="{{ route('reports.index', ['preset' => $k, 'status' => $status]) }}" class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold border bg-white text-neutral-600 border-neutral-300 hover:border-[var(--accent-ring)]">{{ $l }}</a>
        @endforeach
    </div>
    <form method="GET" class="grid grid-cols-5 gap-2 mb-4 items-center">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="col-span-2 rounded-xl border border-neutral-300 py-2.5 px-3 text-sm bg-white" aria-label="Từ ngày">
        <input type="date" name="to" value="{{ $to->toDateString() }}" class="col-span-2 rounded-xl border border-neutral-300 py-2.5 px-3 text-sm bg-white" aria-label="Đến ngày">
        <button class="rounded-xl bg-neutral-900 text-white text-sm font-semibold py-2.5"><i class="fa-solid fa-filter"></i></button>
    </form>

    <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 mb-6">
        <p class="text-xs text-neutral-400 mb-2">Cả trại · {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</p>
        <div class="grid grid-cols-3 gap-2 text-center mb-4">
            <div><div class="text-[0.65rem] text-neutral-400 uppercase font-semibold">Doanh thu</div><div class="text-base font-extrabold text-emerald-700">{{ money_compact($period['revenue']) }}</div></div>
            <div><div class="text-[0.65rem] text-neutral-400 uppercase font-semibold">Chi phí</div><div class="text-base font-extrabold text-neutral-900">{{ money_compact($period['cost_total']) }}</div></div>
            <div><div class="text-[0.65rem] text-neutral-400 uppercase font-semibold">{{ $period['profit'] >= 0 ? 'Lợi nhuận' : 'Lỗ' }}</div><div class="text-base font-extrabold {{ $profitClass($period['profit']) }}">{{ signed_money($period['profit']) }}</div></div>
        </div>
        <div class="space-y-1.5 border-t border-neutral-100 pt-3">
            @foreach($labels as $k => $l)
                @php $v = $period['costs'][$k] ?? 0; $pct = $period['cost_total'] > 0 ? $v / $period['cost_total'] * 100 : 0; @endphp
                <div>
                    <div class="flex justify-between text-xs"><span class="text-neutral-600">{{ $l }}</span><span class="font-semibold text-neutral-800">{{ money($v) }}đ</span></div>
                    <div class="h-1.5 rounded-full bg-neutral-100 mt-1"><div class="h-1.5 rounded-full bg-[var(--accent)]" style="width: {{ round($pct) }}%"></div></div>
                </div>
            @endforeach
        </div>
        <p class="text-[0.7rem] text-neutral-400 mt-3">Chi phí gồm tiền mua con giống nhập trong kỳ, vật tư tiêu hao theo nhật ký và các khoản chi khác.</p>
    </div>

    {{-- Theo lứa --}}
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-sm font-bold text-neutral-900">Theo từng lứa / vật nuôi (toàn thời gian)</h3>
    </div>
    <div class="grid grid-cols-3 gap-1 bg-neutral-100 rounded-xl p-1 mb-3">
        @foreach(['all' => 'Tất cả', 'active' => 'Đang nuôi', 'closed' => 'Đã kết thúc'] as $k => $l)
            <a href="{{ route('reports.index', ['status' => $k, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="text-center text-xs font-semibold py-2 rounded-lg transition {{ $status === $k ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500' }}">{{ $l }}</a>
        @endforeach
    </div>
    <div class="bg-neutral-900 text-white rounded-xl px-4 py-3 mb-3 grid grid-cols-3 text-center">
        <div><div class="text-[0.65rem] text-neutral-400 uppercase">Doanh thu</div><div class="text-sm font-bold">{{ money_compact($totals['revenue']) }}</div></div>
        <div><div class="text-[0.65rem] text-neutral-400 uppercase">Chi phí</div><div class="text-sm font-bold">{{ money_compact($totals['cost']) }}</div></div>
        <div><div class="text-[0.65rem] text-neutral-400 uppercase">Lời / lỗ</div><div class="text-sm font-bold {{ $totals['profit'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ signed_money($totals['profit']) }}</div></div>
    </div>
    <div class="space-y-2">
        @forelse($rows as $r)
            @php $b = $r['batch']; $s = $r['summary']; @endphp
            <a href="{{ route('batches.show', $b->id) }}" class="block bg-white rounded-xl border border-neutral-200 shadow-sm px-4 py-3 hover:border-[var(--accent-ring)] transition">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">{{ $b->species->emoji }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold text-neutral-900 truncate">{{ $b->name }} @if(! $b->isActive())<span class="text-[0.65rem] font-semibold text-neutral-400">· đã kết thúc</span>@endif</div>
                        <div class="text-xs text-neutral-500">Doanh thu {{ money_compact($s['revenue']) }} · Chi phí {{ money_compact($s['cost_total']) }}</div>
                    </div>
                    <div class="text-right"><div class="text-sm font-extrabold {{ $profitClass($s['profit']) }}">{{ signed_money($s['profit']) }}</div></div>
                </div>
                <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-2 text-xs text-neutral-500">
                    <span>Giá thành: <strong class="text-neutral-700">{{ money($s['cost_per_head']) }}đ/con</strong></span>
                    @if($s['profit_per_sold_head'] !== null)<span>Lãi: <strong class="{{ $profitClass($s['profit_per_sold_head']) }}">{{ signed_money($s['profit_per_sold_head']) }}/con bán</strong></span>@endif
                    <span>Hao hụt: <strong class="text-neutral-700">{{ number_format($s['loss_rate'], 1, ',', '.') }}%</strong></span>
                </div>
            </a>
        @empty
            <p class="text-center text-sm text-neutral-400 py-8">Chưa có dữ liệu.</p>
        @endforelse
    </div>
</div>
@endsection
