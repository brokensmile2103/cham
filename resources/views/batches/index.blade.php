@extends('layouts.app')
@section('title', 'Vật nuôi · Chăm')
@section('page-title', 'Vật nuôi')
@section('content')
@php $isOwner = auth()->user()->isOwner(); @endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-5 md:mb-6">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0">
            <i class="fa-solid fa-paw"></i>
        </div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Vật nuôi — {{ $farm->name }}</h2>
    </div>

    @if($isOwner)
        <div class="grid grid-cols-2 gap-2 mb-5">
            <a href="{{ route('batches.create', ['species' => $speciesId]) }}" class="flex items-center justify-center gap-2 px-3 py-3 rounded-xl bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white text-sm font-semibold transition">
                <i class="fa-solid fa-plus"></i>Thêm lứa / đàn
            </a>
            <a href="{{ route('batches.create', ['kind' => 'individual', 'species' => $speciesId]) }}" class="flex items-center justify-center gap-2 px-3 py-3 rounded-xl bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-50 text-sm font-semibold transition">
                <i class="fa-solid fa-heart"></i>Thêm thú cưng / cá thể
            </a>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-1 bg-neutral-100 rounded-xl p-1 mb-3">
        <a href="{{ route('batches.index', ['status' => 'active', 'species' => $speciesId]) }}" class="text-center text-sm font-semibold py-2 rounded-lg transition {{ $status === 'active' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500' }}">Đang nuôi ({{ $counts['active'] }})</a>
        <a href="{{ route('batches.index', ['status' => 'closed', 'species' => $speciesId]) }}" class="text-center text-sm font-semibold py-2 rounded-lg transition {{ $status === 'closed' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500' }}">Đã kết thúc ({{ $counts['closed'] }})</a>
    </div>

    @if($species->count() > 1)
        <div class="flex gap-2 overflow-x-auto scrollbar-hide pb-2 mb-3 -mx-1 px-1">
            <a href="{{ route('batches.index', ['status' => $status]) }}" class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold border transition {{ ! $speciesId ? 'bg-[var(--accent)] text-white border-[var(--accent)]' : 'bg-white text-neutral-600 border-neutral-300' }}">Tất cả</a>
            @foreach($species as $sp)
                <a href="{{ route('batches.index', ['status' => $status, 'species' => $sp->id]) }}" class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold border transition {{ $speciesId === $sp->id ? 'bg-[var(--accent)] text-white border-[var(--accent)]' : 'bg-white text-neutral-600 border-neutral-300' }}">{{ $sp->emoji }} {{ $sp->name }}</a>
            @endforeach
        </div>
    @endif

    <div class="space-y-2">
        @forelse($batches as $b)
            <a href="{{ route('batches.show', $b->id) }}" class="flex items-center gap-3 bg-white rounded-xl px-4 py-3.5 border border-neutral-200 shadow-sm hover:border-[var(--accent-ring)] transition">
                <div class="w-12 h-12 rounded-xl bg-[var(--accent-light)] flex items-center justify-center text-2xl shrink-0">{{ $b->species->emoji }}</div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-neutral-900 truncate">{{ $b->name }}</span>
                        @if($b->isIndividual())<span class="text-[0.65rem] px-1.5 py-0.5 rounded-full bg-rose-50 text-rose-600 font-semibold shrink-0">cá thể</span>@endif
                    </div>
                    <div class="text-xs text-neutral-500 truncate">
                        {{ $b->species->name }}@if($b->breed) · {{ $b->breed->name }}@endif
                        @if($b->zone) · {{ $b->zone->name }}@endif
                    </div>
                    <div class="text-xs text-neutral-400 mt-0.5">
                        {{ $b->ageLabel() }} · ghi nhật ký gần nhất: {{ ago($b->last_event_at) }}
                    </div>
                </div>
                <div class="text-right shrink-0">
                    @if($b->isIndividual())
                        <i class="fa-solid fa-chevron-right text-neutral-300"></i>
                    @else
                        <div class="text-lg font-bold text-neutral-900 leading-tight">{{ money($b->current_quantity) }}</div>
                        <div class="text-[0.65rem] text-neutral-400">/ {{ money($b->initial_quantity) }} con</div>
                    @endif
                </div>
            </a>
        @empty
            <div class="text-center py-12 text-neutral-400">
                <i class="fa-solid fa-paw text-3xl mb-2 block"></i>
                <p class="text-sm">
                    @if($status === 'active') Chưa có vật nuôi nào đang nuôi.@else Chưa có lứa nào kết thúc.@endif
                    @if($isOwner && $status === 'active')
                        <br>@if($species->isEmpty()) <a href="{{ route('today') }}" class="text-[var(--accent-text)] font-semibold hover:underline">Chọn loài bạn nuôi</a> trước, rồi bấm "Thêm lứa". @else Bấm "Thêm lứa / đàn" ở trên để bắt đầu. @endif
                    @endif
                </p>
            </div>
        @endforelse
    </div>
</div>
@endsection
