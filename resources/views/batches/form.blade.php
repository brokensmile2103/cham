@extends('layouts.app')
@php
    $editing = $batch->exists;
    $title = $editing ? 'Sửa '.$batch->name : ($batch->kind === 'individual' ? 'Thêm thú cưng / cá thể' : 'Thêm lứa / đàn mới');
    $speciesData = $species->mapWithKeys(fn ($s) => [$s->id => ['unit' => $s->sale_unit]])->all();
@endphp
@section('title', $title.' · Chăm')
@section('page-title', $title)
@section('content')
<div class="p-4 pb-28 md:p-8 max-w-xl mx-auto"
     x-data="{
        kind: @js(old('kind', $batch->kind ?? 'batch')),
        speciesId: @js((string) old('species_id', $batch->species_id)),
        costMode: @js(old('cost_mode', 'total')),
        qty: @js((int) old('initial_quantity', $batch->initial_quantity ?: 1)),
        cost: @js((string) old('purchase_cost', $batch->purchase_cost > 0 ? (int) $batch->purchase_cost : '')),
        digits(v) { return parseInt(String(v).replace(/\D/g, '')) || 0 },
        get total() { return this.costMode === 'unit' ? this.digits(this.cost) * (this.kind === 'individual' ? 1 : (parseInt(this.qty) || 0)) : this.digits(this.cost) },
     }">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ $editing ? route('batches.show', $batch->id) : route('batches.index') }}" class="w-10 h-10 rounded-xl bg-white border border-neutral-200 text-neutral-500 flex items-center justify-center shrink-0 hover:bg-neutral-50"><i class="fa-solid fa-arrow-left"></i></a>
        <h2 class="text-lg font-bold text-neutral-900">{{ $title }}</h2>
    </div>

    @if($species->isEmpty())
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm mb-5">
            Bạn chưa có loài nào. <a href="{{ route('catalog.index') }}" class="font-semibold underline">Thêm loài vật nuôi</a> trước nhé.
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('batches.update', $batch->id) : route('batches.store') }}" class="bg-white rounded-2xl p-4 sm:p-5 border border-neutral-200 shadow-sm space-y-4">
        @csrf
        @if($editing) @method('PUT') @endif
        <input type="hidden" name="kind" x-model="kind">

        @if(! $editing)
            <div class="grid grid-cols-2 gap-1 bg-neutral-100 rounded-xl p-1">
                <button type="button" @click="kind = 'batch'" :class="kind === 'batch' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500'" class="text-sm font-semibold py-2 rounded-lg transition"><i class="fa-solid fa-layer-group mr-1"></i>Lứa / đàn</button>
                <button type="button" @click="kind = 'individual'" :class="kind === 'individual' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500'" class="text-sm font-semibold py-2 rounded-lg transition"><i class="fa-solid fa-heart mr-1"></i>Thú cưng / cá thể</button>
            </div>
        @endif

        <x-select name="species_id" label="Loài" x-model="speciesId" required>
            <option value="">— Chọn loài —</option>
            @foreach($species as $sp)
                <option value="{{ $sp->id }}">{{ $sp->emoji }} {{ $sp->name }}</option>
            @endforeach
        </x-select>

        <div>
            <label for="breed_name" class="block text-sm font-medium text-neutral-700 mb-2">Giống <span class="text-neutral-400 font-normal">(chọn hoặc gõ giống mới)</span></label>
            <input type="text" name="breed_name" id="breed_name" :list="'breeds-' + speciesId" value="{{ old('breed_name', $batch->breed?->name) }}" maxlength="80" autocomplete="off"
                   class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition">
            @foreach($species as $sp)
                <datalist id="breeds-{{ $sp->id }}">@foreach($sp->breeds as $br)<option value="{{ $br->name }}">@endforeach</datalist>
            @endforeach
        </div>

        <x-input name="name" label="Tên" icon="fa-tag" placeholder="{{ $editing ? '' : 'Để trống để tự đặt tên' }}" value="{{ old('name', $batch->name) }}" />

        <div class="grid grid-cols-2 gap-3">
            <x-input type="date" name="started_on" label="Ngày nhập" value="{{ old('started_on', optional($batch->started_on)->toDateString() ?? now()->toDateString()) }}" required />
            <div x-show="kind === 'batch'">
                <x-input type="number" min="1" name="initial_quantity" label="Số con ban đầu" x-model="qty" required />
            </div>
            <div x-show="kind === 'individual'" x-cloak>
                <x-select name="gender" label="Giới tính">
                    <option value="">— Không rõ —</option>
                    <option value="duc" @selected(old('gender', $batch->gender) === 'duc')>Đực</option>
                    <option value="cai" @selected(old('gender', $batch->gender) === 'cai')>Cái</option>
                </x-select>
            </div>
        </div>

        <div x-show="kind === 'individual'" x-cloak>
            <x-input type="date" name="birth_date" label="Ngày sinh (nếu biết)" value="{{ old('birth_date', optional($batch->birth_date)->toDateString()) }}" />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <x-select name="zone_id" label="Khu vực / chuồng">
                <option value="">— Chưa phân khu —</option>
                @foreach($zones as $z)
                    <option value="{{ $z->id }}" @selected((int) old('zone_id', $batch->zone_id) === $z->id)>{{ $z->name }}</option>
                @endforeach
            </x-select>
            <x-input name="zone_name" label="…hoặc tạo khu mới" placeholder="VD: Chuồng A" value="{{ old('zone_name') }}" maxlength="80" />
        </div>

        <div class="pt-3 border-t border-neutral-100 space-y-3">
            <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Chi phí con giống</p>
            <div class="grid grid-cols-2 gap-1 bg-neutral-100 rounded-xl p-1" x-show="kind === 'batch'">
                <label class="cursor-pointer"><input type="radio" name="cost_mode" value="total" x-model="costMode" class="sr-only"><span :class="costMode === 'total' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500'" class="block text-center text-sm font-semibold py-2 rounded-lg transition">Tổng tiền</span></label>
                <label class="cursor-pointer"><input type="radio" name="cost_mode" value="unit" x-model="costMode" class="sr-only"><span :class="costMode === 'unit' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500'" class="block text-center text-sm font-semibold py-2 rounded-lg transition">Giá mỗi con</span></label>
            </div>
            <input type="hidden" name="cost_mode" value="total" x-bind:disabled="kind === 'batch'">
            <x-input type="text" inputmode="numeric" class="money-input" name="purchase_cost" label="Số tiền (đ)" icon="fa-money-bill" value="{{ old('purchase_cost', $batch->purchase_cost > 0 ? (int) $batch->purchase_cost : '') }}" x-on:input="cost = $event.target.value" />
            <p class="text-xs text-neutral-500" x-show="total > 0">Tổng tiền con giống: <strong class="text-neutral-800" x-text="total.toLocaleString('vi-VN') + 'đ'"></strong></p>
            <x-input type="number" step="any" min="0" name="initial_weight_kg" label="Cân nặng TB lúc nhập (kg/con) — không bắt buộc" icon="fa-weight-scale" value="{{ old('initial_weight_kg', $batch->initial_weight_kg !== null ? input_number($batch->initial_weight_kg) : '') }}" />
        </div>

        <x-input name="source" label="Nguồn giống / nơi mua" icon="fa-store" value="{{ old('source', $batch->source) }}" maxlength="120" />

        <div>
            <label for="note" class="block text-sm font-medium text-neutral-700 mb-2">Ghi chú</label>
            <textarea name="note" id="note" rows="2" maxlength="2000" class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition">{{ old('note', $batch->note) }}</textarea>
        </div>

        <x-button icon="fa-floppy-disk" :disabled="$species->isEmpty()">{{ $editing ? 'Lưu thay đổi' : 'Thêm vào trại' }}</x-button>
    </form>
</div>
@endsection
