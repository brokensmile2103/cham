@extends('layouts.app')
@php
    $types = config('cham.event_types');
    $loggable = config('cham.loggable_types');
    $typeData = collect($loggable)->mapWithKeys(fn ($k) => [$k => [
        'label' => $types[$k]['label'], 'icon' => $types[$k]['icon'], 'group' => $types[$k]['group'],
        'item' => $types[$k]['item'], 'cats' => $types[$k]['item_categories'],
    ]])->all();
    $itemData = $items->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit, 'cat' => $i->category, 'stock' => (float) $i->current_stock])->values()->all();
    $batchData = $batches->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'emoji' => $b->species->emoji, 'qty' => $b->current_quantity, 'ind' => $b->isIndividual(), 'zone' => $b->zone?->name])->values()->all();
@endphp
@section('title', 'Ghi nhật ký · Chăm')
@section('page-title', 'Ghi nhật ký')
@section('content')
<div class="p-4 pb-28 md:p-8 max-w-xl mx-auto"
     x-data="{
        type: @js(old('type', $type)),
        types: @js($typeData),
        items: @js($itemData),
        batches: @js($batchData),
        sel: @js(array_map('intval', old('batch_ids', $selected))),
        itemId: @js((string) old('item_id', '')),
        itemQty: @js((string) old('item_quantity', '')),
        get isPop() { return this.types[this.type].group === 'pop' },
        get cur() { return this.types[this.type] },
        get itemList() {
            const c = this.cur.cats;
            return this.items.filter(i => !c.length || c.includes(i.cat) || i.id == this.itemId);
        },
        get item() { return this.items.find(i => i.id == this.itemId) },
        toggle(id) {
            if (this.isPop) { this.sel = [id]; return; }
            this.sel = this.sel.includes(id) ? this.sel.filter(x => x !== id) : [...this.sel, id];
        },
        setType(t) { this.type = t; if (this.types[t].group === 'pop' && this.sel.length > 1) this.sel = [this.sel[0]]; if (!this.types[t].item) { this.itemId = ''; } },
     }">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ $returnTo ?: route('events.index') }}" class="w-10 h-10 rounded-xl bg-white border border-neutral-200 text-neutral-500 flex items-center justify-center shrink-0 hover:bg-neutral-50"><i class="fa-solid fa-arrow-left"></i></a>
        <h2 class="text-lg font-bold text-neutral-900">Ghi nhật ký</h2>
    </div>

    @if($batches->isEmpty())
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm">
            Chưa có vật nuôi nào đang nuôi. <a href="{{ route('batches.index') }}" class="font-semibold underline">Thêm lứa / đàn</a> trước nhé.
        </div>
    @else
    <form method="POST" action="{{ route('events.store') }}" class="space-y-4">
        @csrf
        @if($returnTo)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
        <input type="hidden" name="type" :value="type">

        <div class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm">
            <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider mb-2">Loại ghi nhận</p>
            <div class="grid grid-cols-4 gap-2">
                @foreach($loggable as $t)
                    <button type="button" @click="setType('{{ $t }}')"
                        :class="type === '{{ $t }}' ? 'border-[var(--accent)] bg-[var(--accent-light)] text-[var(--accent-text)]' : 'border-neutral-200 text-neutral-600 hover:bg-neutral-50'"
                        class="flex flex-col items-center gap-1 rounded-xl border px-1 py-2.5 transition">
                        <i class="fa-solid {{ $types[$t]['icon'] }} text-base"></i>
                        <span class="text-[0.65rem] font-semibold leading-tight text-center">{{ $types[$t]['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Vật nuôi / lứa</p>
                <button type="button" x-show="!isPop" @click="sel = sel.length === batches.length ? [] : batches.map(b => b.id)" class="text-xs font-semibold text-[var(--accent-text)]" x-text="sel.length === batches.length ? 'Bỏ chọn hết' : 'Chọn tất cả'"></button>
            </div>
            <p class="text-xs text-neutral-400 mb-2" x-show="isPop" x-cloak>Loại này chỉ áp dụng cho 1 lứa mỗi lần.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <template x-for="b in batches" :key="b.id">
                    <button type="button" @click="toggle(b.id)"
                        :class="sel.includes(b.id) ? 'border-[var(--accent)] bg-[var(--accent-light)]' : 'border-neutral-200 hover:bg-neutral-50'"
                        class="flex items-center gap-2.5 rounded-xl border px-3 py-2.5 text-left transition">
                        <span class="text-xl" x-text="b.emoji"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-neutral-900 truncate" x-text="b.name"></span>
                            <span class="block text-xs text-neutral-500 truncate" x-text="(b.ind ? 'cá thể' : b.qty.toLocaleString('vi-VN') + ' con') + (b.zone ? ' · ' + b.zone : '')"></span>
                        </span>
                        <i class="fa-solid fa-circle-check text-[var(--accent)]" x-show="sel.includes(b.id)"></i>
                    </button>
                </template>
            </div>
            <template x-for="id in sel" :key="id"><input type="hidden" name="batch_ids[]" :value="id"></template>
            @error('batch_ids')<p class="mt-2 text-xs text-red-600"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>@enderror
        </div>

        <div class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm space-y-4">
            {{-- Chăm sóc: vật tư --}}
            <div x-show="!isPop && cur.item" x-cloak class="space-y-3">
                <div>
                    <label for="item_id" class="block text-sm font-medium text-neutral-700 mb-2">Vật tư dùng <span class="text-neutral-400 font-normal">(trừ kho, tự tính chi phí)</span></label>
                    <select name="item_id" id="item_id" x-model="itemId" :disabled="isPop || !cur.item" class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-3 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition">
                        <option value="">— Không dùng vật tư —</option>
                        <template x-for="i in itemList" :key="i.id"><option :value="i.id" x-text="i.name + ' (tồn ' + i.stock.toLocaleString('vi-VN') + ' ' + i.unit + ')'" :selected="i.id == itemId"></option></template>
                    </select>
                    @if($items->isEmpty())<p class="text-xs text-neutral-400 mt-1.5">Chưa có vật tư nào. Chủ trại thêm ở mục <a href="{{ route('inventory.index') }}" class="underline">Kho</a>.</p>@endif
                </div>
                <div x-show="itemId" x-cloak>
                    <label for="item_quantity" class="block text-sm font-medium text-neutral-700 mb-2">Số lượng dùng <span class="text-neutral-400 font-normal" x-show="sel.length > 1">(tổng, chia theo số con)</span></label>
                    <div class="relative">
                        <input type="number" step="any" min="0" name="item_quantity" id="item_quantity" x-model="itemQty" :disabled="!itemId" class="w-full rounded-xl border border-neutral-300 bg-white py-3 pl-4 pr-16 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-neutral-400" x-text="item ? item.unit : ''"></span>
                    </div>
                    @error('item_quantity')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div x-show="type === 'can'" x-cloak>
                <label for="weight_kg" class="block text-sm font-medium text-neutral-700 mb-2">Cân nặng trung bình (kg/con)</label>
                <input type="number" step="any" min="0" name="weight_kg" id="weight_kg" value="{{ old('weight_kg') }}" :disabled="type !== 'can'" class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition">
                @error('weight_kg')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Thay đổi số lượng --}}
            <div x-show="isPop" x-cloak class="space-y-3">
                <div>
                    <label for="quantity" class="block text-sm font-medium text-neutral-700 mb-2"><span x-text="type === 'dieu_chinh' ? 'Số lượng thay đổi (+ thêm / − bớt)' : 'Số con'"></span></label>
                    <input type="number" step="1" name="quantity" id="quantity" value="{{ old('quantity') }}" :disabled="!isPop" :min="type === 'dieu_chinh' ? null : 1" class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition">
                    @error('quantity')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div x-show="type === 'nhap_them'" x-cloak>
                    <label for="cost" class="block text-sm font-medium text-neutral-700 mb-2">Tổng tiền mua thêm (đ)</label>
                    <input type="text" inputmode="numeric" name="cost" id="cost" value="{{ old('cost') }}" :disabled="type !== 'nhap_them'" class="money-input w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition">
                </div>
                <div x-show="type === 'chuyen_di'" x-cloak>
                    <label for="target_batch_id" class="block text-sm font-medium text-neutral-700 mb-2">Chuyển sang lứa <span class="text-neutral-400 font-normal">(không bắt buộc)</span></label>
                    <select name="target_batch_id" id="target_batch_id" :disabled="type !== 'chuyen_di'" class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-3 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition">
                        <option value="">— Không chọn (chuyển ra ngoài) —</option>
                        <template x-for="b in batches.filter(x => !sel.includes(x.id))" :key="b.id"><option :value="b.id" x-text="b.emoji + ' ' + b.name"></option></template>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-input type="date" name="occurred_on" label="Ngày" value="{{ old('occurred_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required />
                <x-input type="time" name="occurred_time" label="Giờ" value="{{ old('occurred_time', now()->format('H:i')) }}" />
            </div>

            <div>
                <label for="note" class="block text-sm font-medium text-neutral-700 mb-2">Ghi chú</label>
                <textarea name="note" id="note" rows="2" maxlength="500" placeholder="VD: ăn hết nhanh, có con bỏ ăn…" class="w-full rounded-xl border border-neutral-300 bg-white py-3 px-4 text-base focus:border-[var(--accent-ring)] focus:ring-2 focus:ring-[var(--accent-ring)]/30 focus:outline-none transition">{{ old('note') }}</textarea>
            </div>
        </div>

        <x-button icon="fa-floppy-disk" x-bind:disabled="sel.length === 0">Lưu ghi nhận</x-button>
    </form>
    @endif
</div>
@endsection
