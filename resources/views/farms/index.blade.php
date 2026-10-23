@extends('layouts.app')
@section('title', 'Các trại · Chăm')
@section('page-title', 'Các trại')
@section('content')
<div class="p-4 pb-28 md:p-8 max-w-xl mx-auto space-y-4" x-data="{ adding: {{ $errors->any() ? 'true' : 'false' }} }">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-warehouse"></i></div>
        <h2 class="text-lg font-bold text-neutral-900 flex-1">Các trại của tôi</h2>
        <button type="button" @click="adding = !adding" class="px-3.5 py-2 rounded-xl bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white text-sm font-semibold"><i class="fa-solid fa-plus mr-1"></i>Thêm trại</button>
    </div>

    <form method="POST" action="{{ route('farms.store') }}" x-show="adding" x-cloak class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm space-y-3">
        @csrf
        <x-input name="name" label="Tên trại mới" icon="fa-house-chimney" value="{{ old('name') }}" required maxlength="100" />
        <x-input name="address" label="Địa chỉ" icon="fa-location-dot" value="{{ old('address') }}" maxlength="255" />
        <x-button icon="fa-floppy-disk">Tạo trại</x-button>
    </form>

    <div class="space-y-2">
        @foreach($farms as $f)
            <div class="bg-white rounded-xl border {{ $f->id === $farm->id ? 'border-[var(--accent)]' : 'border-neutral-200' }} shadow-sm px-4 py-3.5 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-house-chimney"></i></div>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-bold text-neutral-900 truncate">{{ $f->name }}</div>
                    <div class="text-xs text-neutral-500">{{ $f->active_batches_count }} lứa đang nuôi · {{ money((int) $f->animals_count) }} con @if(! $f->is_active)· <span class="text-red-500">bị khoá</span>@endif</div>
                </div>
                @if($f->id === $farm->id)
                    <span class="text-xs font-semibold text-[var(--accent-text)]"><i class="fa-solid fa-circle-check mr-1"></i>Đang dùng</span>
                @elseif($f->is_active)
                    <form method="POST" action="{{ route('farms.switch', $f->id) }}">@csrf<button class="px-3 py-1.5 rounded-lg bg-neutral-100 hover:bg-neutral-200 text-xs font-semibold text-neutral-700">Chuyển sang</button></form>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
