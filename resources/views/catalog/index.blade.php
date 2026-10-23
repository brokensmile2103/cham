@extends('layouts.app')
@section('title', 'Danh mục · Chăm')
@section('page-title', 'Danh mục')
@section('content')
@php $groups = config('cham.species_groups'); @endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto space-y-6" x-data="{ editSp: null, editZone: null, newSp: false }">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-list-check"></i></div>
        <h2 class="text-lg font-bold text-neutral-900">Loài, giống & khu vực</h2>
    </div>

    {{-- Gợi ý nhanh --}}
    @if($presets->isNotEmpty())
    <form method="POST" action="{{ route('catalog.quick-add') }}" class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm" x-data="{ n: 0 }">
        @csrf
        <p class="text-sm font-bold text-neutral-900 mb-1">Chọn nhanh loài bạn nuôi</p>
        <p class="text-xs text-neutral-500 mb-3">Tự thêm kèm các giống thường gặp, sửa lại sau cũng được.</p>
        @error('presets')<p class="text-xs text-red-600 mb-2">{{ $message }}</p>@enderror
        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2 mb-3">
            @foreach($presets as $p)
                <label class="cursor-pointer">
                    <input type="checkbox" name="presets[]" value="{{ $p['key'] }}" class="peer sr-only" @change="n += $event.target.checked ? 1 : -1">
                    <span class="flex flex-col items-center gap-0.5 rounded-xl border border-neutral-200 py-2.5 text-center transition peer-checked:border-[var(--accent)] peer-checked:bg-[var(--accent-light)]">
                        <span class="text-2xl">{{ $p['emoji'] }}</span>
                        <span class="text-xs font-semibold text-neutral-700">{{ $p['name'] }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        <x-button icon="fa-plus" x-bind:disabled="n === 0">Thêm các loài đã chọn</x-button>
    </form>
    @endif

    {{-- Loài --}}
    <section>
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-bold text-neutral-900">Loài vật nuôi ({{ $species->count() }})</h3>
            <button type="button" @click="newSp = !newSp" class="text-xs font-semibold text-[var(--accent-text)]"><i class="fa-solid fa-plus mr-1"></i>Thêm loài khác</button>
        </div>

        <form method="POST" action="{{ route('species.store') }}" x-show="newSp" x-cloak class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm space-y-3 mb-3">
            @csrf
            <div class="grid grid-cols-4 gap-3">
                <div class="col-span-3"><x-input name="name" label="Tên loài" value="{{ old('name') }}" required maxlength="60" /></div>
                <x-input name="emoji" label="Biểu tượng" placeholder="🐾" value="{{ old('emoji') }}" maxlength="16" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <x-select name="group" label="Nhóm">@foreach($groups as $k => $g)<option value="{{ $k }}" @selected(old('group') === $k)>{{ $g }}</option>@endforeach</x-select>
                <x-select name="sale_unit" label="Bán theo"><option value="kg">Cân ký (kg)</option><option value="con">Theo con</option></x-select>
            </div>
            <x-button icon="fa-floppy-disk">Lưu loài</x-button>
        </form>

        <div class="space-y-2">
            @forelse($species as $sp)
                <div class="bg-white rounded-xl border border-neutral-200 shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">{{ $sp->emoji }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-bold text-neutral-900">{{ $sp->name }}</div>
                            <div class="text-xs text-neutral-500">{{ $groups[$sp->group] ?? '' }} · bán theo {{ $sp->sale_unit === 'kg' ? 'kg' : 'con' }} · {{ $sp->batches_count }} lứa</div>
                        </div>
                        <button type="button" @click="editSp = editSp === {{ $sp->id }} ? null : {{ $sp->id }}" class="w-8 h-8 rounded-lg hover:bg-neutral-100 text-neutral-400"><i class="fa-solid fa-pen text-xs"></i></button>
                    </div>

                    <div class="flex flex-wrap gap-1.5 mt-3">
                        @foreach($sp->breeds as $br)
                            <form method="POST" action="{{ route('breeds.destroy', $br->id) }}" class="inline-flex items-center gap-1 bg-neutral-100 rounded-full pl-2.5 pr-1.5 py-1 text-xs text-neutral-700">
                                @csrf @method('DELETE')
                                {{ $br->name }}
                                <button class="text-neutral-400 hover:text-red-500" title="Xoá giống"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                        @endforeach
                        <form method="POST" action="{{ route('breeds.store', $sp->id) }}" class="inline-flex">
                            @csrf
                            <input type="text" name="name" placeholder="+ Thêm giống" maxlength="80" autocomplete="off" class="w-28 rounded-full border border-dashed border-neutral-300 px-3 py-1 text-xs focus:outline-none focus:border-[var(--accent-ring)]">
                        </form>
                    </div>

                    <div x-show="editSp === {{ $sp->id }}" x-cloak class="mt-4 pt-4 border-t border-neutral-100">
                        <form method="POST" action="{{ route('species.update', $sp->id) }}" class="space-y-3">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-4 gap-3">
                                <div class="col-span-3"><x-input name="name" label="Tên loài" value="{{ $sp->name }}" required maxlength="60" /></div>
                                <x-input name="emoji" label="Biểu tượng" value="{{ $sp->emoji }}" maxlength="16" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <x-select name="group" label="Nhóm">@foreach($groups as $k => $g)<option value="{{ $k }}" @selected($sp->group === $k)>{{ $g }}</option>@endforeach</x-select>
                                <x-select name="sale_unit" label="Bán theo"><option value="kg" @selected($sp->sale_unit === 'kg')>Cân ký (kg)</option><option value="con" @selected($sp->sale_unit === 'con')>Theo con</option></x-select>
                            </div>
                            <x-button icon="fa-floppy-disk">Lưu</x-button>
                        </form>
                        <form method="POST" action="{{ route('species.destroy', $sp->id) }}" onsubmit="return confirm('Xoá loài này?')" class="mt-2">
                            @csrf @method('DELETE')
                            <button class="w-full text-center text-sm text-red-600 font-semibold py-2"><i class="fa-solid fa-trash-can mr-1"></i>Xoá loài</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-center text-sm text-neutral-400 py-6">Chưa có loài nào — chọn ở phần gợi ý phía trên.</p>
            @endforelse
        </div>
    </section>

    {{-- Khu vực --}}
    <section>
        <h3 class="text-sm font-bold text-neutral-900 mb-2">Khu vực / chuồng ({{ $zones->count() }})</h3>
        <form method="POST" action="{{ route('zones.store') }}" class="bg-white rounded-2xl p-4 border border-neutral-200 shadow-sm mb-3">
            @csrf
            <div class="grid grid-cols-3 gap-3 mb-3">
                <div class="col-span-2"><x-input name="name" label="Tên khu" placeholder="VD: Chuồng A, Ao 1" value="{{ old('name') }}" required maxlength="80" /></div>
                <x-input type="number" min="0" name="capacity" label="Sức chứa" value="{{ old('capacity') }}" />
            </div>
            <x-button icon="fa-plus" variant="ghost">Thêm khu vực</x-button>
        </form>
        <div class="space-y-2">
            @foreach($zones as $z)
                <div class="bg-white rounded-xl border border-neutral-200 shadow-sm px-4 py-3">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-warehouse text-neutral-300"></i>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-bold text-neutral-900">{{ $z->name }}</div>
                            <div class="text-xs text-neutral-500">{{ $z->active_batches_count }} lứa đang nuôi @if($z->capacity) · sức chứa {{ money($z->capacity) }} @endif</div>
                        </div>
                        <button type="button" @click="editZone = editZone === {{ $z->id }} ? null : {{ $z->id }}" class="w-8 h-8 rounded-lg hover:bg-neutral-100 text-neutral-400"><i class="fa-solid fa-pen text-xs"></i></button>
                    </div>
                    <div x-show="editZone === {{ $z->id }}" x-cloak class="mt-3 pt-3 border-t border-neutral-100">
                        <form method="POST" action="{{ route('zones.update', $z->id) }}" class="space-y-3">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-3 gap-3">
                                <div class="col-span-2"><x-input name="name" label="Tên khu" value="{{ $z->name }}" required maxlength="80" /></div>
                                <x-input type="number" min="0" name="capacity" label="Sức chứa" value="{{ $z->capacity }}" />
                            </div>
                            <x-input name="note" label="Ghi chú" value="{{ $z->note }}" maxlength="255" />
                            <x-button icon="fa-floppy-disk">Lưu</x-button>
                        </form>
                        <form method="POST" action="{{ route('zones.destroy', $z->id) }}" onsubmit="return confirm('Xoá khu vực này? Các lứa trong khu sẽ thành \'chưa phân khu\'.')" class="mt-2">
                            @csrf @method('DELETE')
                            <button class="w-full text-center text-sm text-red-600 font-semibold py-2"><i class="fa-solid fa-trash-can mr-1"></i>Xoá khu vực</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
