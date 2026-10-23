@extends('layouts.app')
@section('title', 'Chi phí · Chăm')
@section('page-title', 'Chi phí')
@section('content')
@php $cats = config('cham.expense_categories'); @endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto" x-data="{ adding: {{ $errors->any() ? 'true' : 'false' }} }">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-receipt"></i></div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Chi phí khác</h2>
        <button type="button" @click="adding = !adding" class="px-3.5 py-2 rounded-xl bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white text-sm font-semibold"><i class="fa-solid fa-plus mr-1"></i>Thêm</button>
    </div>
    <p class="text-xs text-neutral-500 mb-4">Điện nước, nhân công, thuê chuồng, thú y… Chi phí gắn với lứa sẽ vào giá thành của lứa đó; chi phí chung tính vào lời/lỗ cả trại. Thức ăn và thuốc đã tự tính từ nhật ký.</p>

    <form method="POST" action="{{ route('expenses.store') }}" x-show="adding" x-cloak class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm space-y-3 mb-4">
        @csrf
        <div class="grid grid-cols-2 gap-3">
            <x-select name="category" label="Loại chi phí">@foreach($cats as $k => $l)<option value="{{ $k }}" @selected(old('category') === $k)>{{ $l }}</option>@endforeach</x-select>
            <x-input type="date" name="occurred_on" label="Ngày" value="{{ old('occurred_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required />
        </div>
        <x-input type="text" inputmode="numeric" class="money-input" name="amount" label="Số tiền (đ)" icon="fa-money-bill" value="{{ old('amount') }}" required />
        <x-select name="batch_id" label="Gắn với lứa">
            <option value="">— Chi phí chung của trại —</option>
            @foreach($batches as $b)<option value="{{ $b->id }}" @selected((int) old('batch_id') === $b->id)>{{ $b->name }}{{ $b->status === 'closed' ? ' (đã kết thúc)' : '' }}</option>@endforeach
        </x-select>
        <x-input name="description" label="Ghi chú" value="{{ old('description') }}" maxlength="255" />
        <x-button icon="fa-floppy-disk">Lưu khoản chi</x-button>
    </form>

    <div class="flex items-center justify-between bg-white rounded-xl border border-neutral-200 px-2 py-2 mb-3">
        <a href="{{ route('expenses.index', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="w-9 h-9 rounded-lg hover:bg-neutral-100 flex items-center justify-center text-neutral-500"><i class="fa-solid fa-chevron-left"></i></a>
        <div class="text-center"><div class="text-sm font-bold text-neutral-900">Tháng {{ $month->format('m/Y') }}</div><div class="text-xs text-neutral-500">Tổng: <strong class="text-neutral-800">{{ money($total) }}đ</strong></div></div>
        <a href="{{ route('expenses.index', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="w-9 h-9 rounded-lg hover:bg-neutral-100 flex items-center justify-center text-neutral-500"><i class="fa-solid fa-chevron-right"></i></a>
    </div>

    @if($byCategory->isNotEmpty())
        <div class="flex flex-wrap gap-1.5 mb-3">
            @foreach($byCategory as $k => $v)<span class="text-xs bg-neutral-100 text-neutral-600 rounded-full px-2.5 py-1">{{ $cats[$k] ?? $k }}: <strong>{{ money_compact($v) }}</strong></span>@endforeach
        </div>
    @endif

    <div class="space-y-2">
        @forelse($expenses as $e)
            <div class="flex items-center gap-3 bg-white rounded-xl border border-neutral-200 shadow-sm px-4 py-3">
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold text-neutral-900">{{ $cats[$e->category] ?? $e->category }}@if($e->description) <span class="font-normal text-neutral-500">· {{ $e->description }}</span>@endif</div>
                    <div class="text-xs text-neutral-400">{{ $e->occurred_on->format('d/m/Y') }} · {{ $e->batch ? $e->batch->name : 'Chung cả trại' }}</div>
                </div>
                <div class="text-sm font-bold text-neutral-900">{{ money($e->amount) }}đ</div>
                <form method="POST" action="{{ route('expenses.destroy', $e->id) }}" onsubmit="return confirm('Xoá khoản chi này?')">@csrf @method('DELETE')<button class="text-neutral-300 hover:text-red-500 p-1"><i class="fa-solid fa-trash-can text-xs"></i></button></form>
            </div>
        @empty
            <div class="text-center py-12 text-neutral-400"><i class="fa-solid fa-receipt text-3xl mb-2 block"></i><p class="text-sm">Tháng này chưa có khoản chi nào.</p></div>
        @endforelse
    </div>
</div>
@endsection
