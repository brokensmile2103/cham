@extends('layouts.app')
@section('title', 'Xuất bán · Chăm')
@section('page-title', 'Xuất bán')
@section('content')
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-sack-dollar"></i></div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Xuất bán</h2>
        <a href="{{ route('batches.index') }}" class="text-xs font-semibold text-[var(--accent-text)]">Bán từ lứa <i class="fa-solid fa-arrow-right ml-0.5"></i></a>
    </div>

    <form method="GET" class="bg-white rounded-2xl p-3 border border-neutral-200 shadow-sm grid grid-cols-5 gap-2 mb-4 items-center">
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="col-span-2 rounded-xl border border-neutral-300 py-2.5 px-3 text-sm" aria-label="Từ ngày">
        <input type="date" name="to" value="{{ $to->toDateString() }}" class="col-span-2 rounded-xl border border-neutral-300 py-2.5 px-3 text-sm" aria-label="Đến ngày">
        <button class="rounded-xl bg-neutral-900 text-white text-sm font-semibold py-2.5"><i class="fa-solid fa-filter"></i></button>
    </form>

    <div class="grid grid-cols-3 gap-2 mb-4">
        <div class="bg-white rounded-xl border border-neutral-200 p-3 text-center"><div class="text-[0.65rem] text-neutral-400 uppercase font-semibold">Doanh thu</div><div class="text-sm font-extrabold text-emerald-700 mt-0.5">{{ money_compact($totals['amount']) }}</div></div>
        <div class="bg-white rounded-xl border border-neutral-200 p-3 text-center"><div class="text-[0.65rem] text-neutral-400 uppercase font-semibold">Số con</div><div class="text-sm font-extrabold text-neutral-900 mt-0.5">{{ money($totals['quantity']) }}</div></div>
        <div class="bg-white rounded-xl border border-neutral-200 p-3 text-center"><div class="text-[0.65rem] text-neutral-400 uppercase font-semibold">Số đơn</div><div class="text-sm font-extrabold text-neutral-900 mt-0.5">{{ money($totals['count']) }}</div></div>
    </div>

    <div class="space-y-2">
        @forelse($sales as $s)
            <div class="flex items-center gap-3 bg-white rounded-xl border border-neutral-200 shadow-sm px-4 py-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-xl shrink-0">{{ $s->batch->species->emoji }}</div>
                <div class="min-w-0 flex-1">
                    <a href="{{ route('batches.show', $s->batch_id) }}" class="text-sm font-bold text-neutral-900 hover:underline truncate block">{{ $s->batch->name }}</a>
                    <div class="text-xs text-neutral-500">{{ $s->sold_on->format('d/m/Y') }} · {{ $s->quantity }} con @if($s->weight_kg) · {{ quantity($s->weight_kg, 2) }} kg @endif · {{ money($s->unit_price) }}đ/{{ $s->priceUnitLabel() }}</div>
                    @if($s->buyer)<div class="text-xs text-neutral-400">{{ $s->buyer }}</div>@endif
                </div>
                <div class="text-sm font-bold text-emerald-700">{{ money($s->total_amount) }}đ</div>
                <form method="POST" action="{{ route('sales.destroy', $s->id) }}" onsubmit="return confirm('Xoá đơn bán này? {{ $s->quantity }} con sẽ được hoàn lại vào lứa.')">
                    @csrf @method('DELETE')
                    <button class="text-neutral-300 hover:text-red-500 p-1" title="Xoá"><i class="fa-solid fa-trash-can text-xs"></i></button>
                </form>
            </div>
        @empty
            <div class="text-center py-12 text-neutral-400"><i class="fa-solid fa-sack-dollar text-3xl mb-2 block"></i><p class="text-sm">Chưa có đơn bán nào trong kỳ này.</p></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $sales->links() }}</div>
</div>
@endsection
