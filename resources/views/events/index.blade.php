@extends('layouts.app')
@section('title', 'Nhật ký · Chăm')
@section('page-title', 'Nhật ký')
@section('content')
@php $types = config('cham.event_types'); @endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-clipboard-list"></i></div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Nhật ký chăm sóc</h2>
        <a href="{{ route('events.create') }}" class="px-3.5 py-2 rounded-xl bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white text-sm font-semibold"><i class="fa-solid fa-plus mr-1"></i>Ghi mới</a>
    </div>

    <form method="GET" class="bg-white rounded-2xl p-3 border border-neutral-200 shadow-sm grid grid-cols-2 gap-2 mb-4">
        <select name="batch" class="rounded-xl border border-neutral-300 bg-white py-2.5 px-3 text-sm col-span-2 sm:col-span-1">
            <option value="">Tất cả vật nuôi</option>
            @foreach($batches as $b)<option value="{{ $b->id }}" @selected((int) request('batch') === $b->id)>{{ $b->species->emoji }} {{ $b->name }}{{ $b->status === 'closed' ? ' (đã kết thúc)' : '' }}</option>@endforeach
        </select>
        <select name="type" class="rounded-xl border border-neutral-300 bg-white py-2.5 px-3 text-sm col-span-2 sm:col-span-1">
            <option value="">Tất cả loại</option>
            @foreach($types as $k => $t)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $t['label'] }}</option>@endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-xl border border-neutral-300 py-2.5 px-3 text-sm" aria-label="Từ ngày">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-xl border border-neutral-300 py-2.5 px-3 text-sm" aria-label="Đến ngày">
        <button class="col-span-2 rounded-xl bg-neutral-900 text-white text-sm font-semibold py-2.5"><i class="fa-solid fa-filter mr-1"></i>Lọc</button>
    </form>

    <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm px-4">
        @forelse($events as $event)
            @include('events._row', ['event' => $event, 'showBatch' => true])
        @empty
            <div class="text-center py-12 text-neutral-400">
                <i class="fa-solid fa-clipboard-list text-3xl mb-2 block"></i>
                <p class="text-sm">Chưa có ghi nhận nào.</p>
            </div>
        @endforelse
    </div>
    <div class="mt-4">{{ $events->links() }}</div>
</div>
@endsection
