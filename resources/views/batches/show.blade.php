@extends('layouts.app')
@section('title', $batch->name.' · Chăm')
@section('page-title', $batch->name)
@section('content')
@php
    $here = route('batches.show', $batch->id, false);
    $active = $batch->isActive();
    $canSell = $isOwner && $active && $batch->current_quantity > 0;
    $defaultUnit = $batch->species->sale_unit === 'kg' ? 'kg' : 'con';
    $s = $summary;
@endphp
<div class="p-4 pb-28 md:p-8 max-w-3xl mx-auto space-y-5"
     x-data="{ panel: @js($errors->has('quantity') || $errors->has('weight_kg') || $errors->has('unit_price') ? 'sale' : null) }">

    <div class="flex items-center gap-3">
        <a href="{{ route('batches.index') }}" class="w-10 h-10 rounded-xl bg-white border border-neutral-200 text-neutral-500 flex items-center justify-center shrink-0 hover:bg-neutral-50"><i class="fa-solid fa-arrow-left"></i></a>
        <div class="min-w-0 flex-1">
            <h2 class="text-lg font-bold text-neutral-900 truncate">{{ $batch->species->emoji }} {{ $batch->name }}</h2>
            <p class="text-xs text-neutral-500 truncate">
                {{ $batch->code }} · {{ $batch->species->name }}@if($batch->breed) · {{ $batch->breed->name }}@endif
                @if($batch->zone) · {{ $batch->zone->name }}@endif
            </p>
        </div>
        <span class="text-xs px-2.5 py-1 rounded-full font-semibold shrink-0 {{ $active ? 'bg-emerald-50 text-emerald-700' : 'bg-neutral-100 text-neutral-500' }}">{{ $active ? 'Đang nuôi' : 'Đã kết thúc' }}</span>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Số liệu nhanh --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
        <div class="bg-white rounded-2xl p-3 border border-neutral-200 shadow-sm">
            <p class="text-xs text-neutral-500 font-medium">{{ $batch->isIndividual() ? 'Trạng thái' : 'Hiện có' }}</p>
            <p class="text-xl font-bold text-neutral-900">@if($batch->isIndividual()){{ $batch->current_quantity > 0 ? 'Còn nuôi' : 'Đã mất' }}@else{{ money($batch->current_quantity) }}<span class="text-xs font-medium text-neutral-400"> /{{ money($batch->initial_quantity) }}</span>@endif</p>
        </div>
        <div class="bg-white rounded-2xl p-3 border border-neutral-200 shadow-sm">
            <p class="text-xs text-neutral-500 font-medium">Tuổi</p>
            <p class="text-xl font-bold text-neutral-900">{{ $batch->ageLabel() }}</p>
        </div>
        <div class="bg-white rounded-2xl p-3 border border-neutral-200 shadow-sm">
            <p class="text-xs text-neutral-500 font-medium">Cân gần nhất</p>
            <p class="text-xl font-bold text-neutral-900">@if($lastWeight){{ quantity($lastWeight->weight_kg, 2) }}<span class="text-xs font-medium text-neutral-400"> kg/con</span>@else — @endif</p>
            @if($adg !== null)<p class="text-[0.7rem] text-neutral-400">{{ $adg >= 0 ? '+' : '' }}{{ number_format($adg, 0, ',', '.') }} g/ngày</p>@endif
        </div>
        <div class="bg-white rounded-2xl p-3 border border-neutral-200 shadow-sm">
            <p class="text-xs text-neutral-500 font-medium">Hao hụt</p>
            <p class="text-xl font-bold {{ $lost > 0 ? 'text-red-600' : 'text-neutral-900' }}">{{ $lost }}<span class="text-xs font-medium text-neutral-400"> con</span></p>
        </div>
    </div>

    {{-- Lời/lỗ — chỉ chủ trại --}}
    @if($isOwner && $s)
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-bold text-neutral-900"><i class="fa-solid fa-chart-pie text-[var(--accent)] mr-1.5"></i>Lời / lỗ{{ $active ? ' tạm tính' : '' }}</p>
                <p class="text-xl font-extrabold {{ $s['profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ signed_money($s['profit']) }}đ</p>
            </div>
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-neutral-500">Doanh thu ({{ $s['sold_qty'] }} con đã bán)</span><span class="font-semibold text-emerald-700">{{ money($s['revenue']) }}đ</span></div>
                @foreach(config('cham.cost_categories') as $key => $label)
                    @if($s['costs'][$key] > 0)
                        <div class="flex justify-between"><span class="text-neutral-500">{{ $label }}</span><span class="text-neutral-800">−{{ money($s['costs'][$key]) }}đ</span></div>
                    @endif
                @endforeach
                <div class="flex justify-between pt-1.5 border-t border-neutral-100"><span class="font-semibold text-neutral-700">Tổng chi phí</span><span class="font-semibold text-neutral-900">−{{ money($s['cost_total']) }}đ</span></div>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t border-neutral-100 text-center">
                <div><p class="text-[0.7rem] text-neutral-400">Chi phí TB/con</p><p class="text-sm font-bold text-neutral-800">{{ money($s['cost_per_head']) }}đ</p></div>
                <div><p class="text-[0.7rem] text-neutral-400">Thu TB/con bán</p><p class="text-sm font-bold text-neutral-800">{{ $s['revenue_per_sold_head'] !== null ? money($s['revenue_per_sold_head']).'đ' : '—' }}</p></div>
                <div><p class="text-[0.7rem] text-neutral-400">Lãi TB/con bán</p><p class="text-sm font-bold {{ ($s['profit_per_sold_head'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $s['profit_per_sold_head'] !== null ? signed_money($s['profit_per_sold_head']).'đ' : '—' }}</p></div>
            </div>
            @if($active && $s['sold_qty'] < $batch->initial_quantity)
                <p class="text-[0.7rem] text-neutral-400 mt-3 leading-relaxed">Còn {{ $batch->current_quantity }} con chưa bán nên lãi/lỗ sẽ thay đổi khi xuất bán thêm.</p>
            @endif
        </div>
    @endif

    {{-- Hành động --}}
    @if($active)
        <div class="grid grid-cols-4 gap-2">
            @foreach([['cho_an','Cho ăn'],['thuoc','Thuốc'],['can','Cân'],['chet','Chết']] as [$type, $label])
                @php $m = config('cham.event_types.'.$type); @endphp
                <a href="{{ route('events.create', ['type' => $type, 'batch' => $batch->id, 'return_to' => $here]) }}" class="flex flex-col items-center gap-1.5 bg-white rounded-xl border border-neutral-200 shadow-sm py-3 hover:border-[var(--accent-ring)] active:scale-95 transition">
                    <span class="w-9 h-9 rounded-xl {{ color_badge($m['color']) }} flex items-center justify-center"><i class="fa-solid {{ $m['icon'] }}"></i></span>
                    <span class="text-xs font-semibold text-neutral-700">{{ $label }}</span>
                </a>
            @endforeach
        </div>
        <a href="{{ route('events.create', ['batch' => $batch->id, 'return_to' => $here]) }}" class="block text-center text-sm font-semibold text-[var(--accent-text)] hover:underline -mt-2">+ Ghi loại khác (tiêm phòng, vệ sinh, nhập thêm, chuyển đàn…)</a>
    @endif

    @if($isOwner)
        <div class="flex flex-wrap gap-2">
            @if($canSell)
                <button @click="panel = panel === 'sale' ? null : 'sale'" class="text-xs px-3.5 py-2 rounded-lg bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white font-semibold transition"><i class="fa-solid fa-sack-dollar mr-1"></i>Xuất bán</button>
            @endif
            <button @click="panel = panel === 'expense' ? null : 'expense'" class="text-xs px-3.5 py-2 rounded-lg bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-50 font-semibold transition"><i class="fa-solid fa-receipt mr-1"></i>Thêm chi phí</button>
            <button @click="panel = panel === 'move' ? null : 'move'" class="text-xs px-3.5 py-2 rounded-lg bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-50 font-semibold transition"><i class="fa-solid fa-location-arrow mr-1"></i>Chuyển khu</button>
            <a href="{{ route('batches.edit', $batch->id) }}" class="text-xs px-3.5 py-2 rounded-lg bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-50 font-semibold transition"><i class="fa-solid fa-pen mr-1"></i>Sửa</a>
            @if($active)
                <form method="POST" action="{{ route('batches.close', $batch->id) }}" onsubmit="return confirm('Kết thúc lứa này? Bạn vẫn xem được lời/lỗ và nhật ký, và có thể mở lại sau.')">
                    @csrf<button class="text-xs px-3.5 py-2 rounded-lg bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-50 font-semibold transition"><i class="fa-solid fa-flag-checkered mr-1"></i>Kết thúc lứa</button>
                </form>
            @elseif($batch->current_quantity > 0)
                <form method="POST" action="{{ route('batches.reopen', $batch->id) }}">
                    @csrf<button class="text-xs px-3.5 py-2 rounded-lg bg-white border border-neutral-300 text-neutral-700 hover:bg-neutral-50 font-semibold transition"><i class="fa-solid fa-rotate-left mr-1"></i>Mở lại</button>
                </form>
            @endif
            <form method="POST" action="{{ route('batches.destroy', $batch->id) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from('XOÁ VĨNH VIỄN "'.$batch->name.'" cùng toàn bộ nhật ký, bán hàng và chi phí của lứa? Không thể hoàn tác.') }})">
                @csrf @method('DELETE')<button class="text-xs px-3.5 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 font-semibold transition"><i class="fa-solid fa-trash-can mr-1"></i>Xoá</button>
            </form>
        </div>

        @if($canSell)
            <form x-show="panel === 'sale'" x-cloak method="POST" action="{{ route('sales.store') }}"
                  x-data="{ unit: '{{ old('price_unit', $defaultUnit) }}', tick: 0,
                            n(r) { return parseFloat(String($refs[r]?.value || '').replace(/\./g, '').replace(',', '.')) || 0 },
                            get total() { this.tick; const q = this.n('qty'), w = this.n('weight'), p = this.n('price'); return this.unit === 'kg' ? w * p : (this.unit === 'con' ? q * p : p) } }"
                  x-on:input="tick++" class="bg-white rounded-2xl border border-[var(--accent-ring)] shadow-sm p-4 space-y-3">
                @csrf
                <input type="hidden" name="batch_id" value="{{ $batch->id }}">
                <input type="hidden" name="return_to" value="{{ $here }}">
                <p class="text-sm font-bold text-neutral-900"><i class="fa-solid fa-sack-dollar text-[var(--accent)] mr-1.5"></i>Xuất bán / xuất chuồng</p>
                <div class="grid grid-cols-2 gap-3">
                    <x-input type="date" name="sold_on" label="Ngày bán" value="{{ old('sold_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required />
                    <x-input type="number" min="1" max="{{ $batch->current_quantity }}" name="quantity" label="Số con (tối đa {{ $batch->current_quantity }})" value="{{ old('quantity', $batch->isIndividual() ? 1 : '') }}" x-ref="qty" required />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <x-select name="price_unit" label="Tính giá theo" x-model="unit">
                        <option value="kg">Theo kg</option>
                        <option value="con">Theo con</option>
                        <option value="lo">Trọn lô</option>
                    </x-select>
                    <x-input type="number" step="any" min="0" name="weight_kg" label="Tổng khối lượng (kg)" value="{{ old('weight_kg') }}" x-ref="weight" />
                </div>
                <x-input type="text" inputmode="numeric" class="money-input" name="unit_price" label="Đơn giá (đ) — theo kg / theo con / cả lô" icon="fa-money-bill" value="{{ old('unit_price') }}" x-ref="price" required />
                <div class="flex items-center justify-between bg-[var(--accent-light)] rounded-xl px-4 py-3">
                    <span class="text-sm text-neutral-600">Thành tiền</span>
                    <span class="text-lg font-extrabold text-[var(--accent-text)]" x-text="Math.round(total).toLocaleString('vi-VN') + 'đ'"></span>
                </div>
                <x-input name="buyer" label="Người mua (không bắt buộc)" icon="fa-user" value="{{ old('buyer') }}" />
                <x-input name="note" label="Ghi chú" value="{{ old('note') }}" />
                <x-button icon="fa-check">Ghi nhận bán</x-button>
            </form>
        @endif

        <form x-show="panel === 'expense'" x-cloak method="POST" action="{{ route('expenses.store') }}" class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 space-y-3">
            @csrf
            <input type="hidden" name="batch_id" value="{{ $batch->id }}">
            <input type="hidden" name="return_to" value="{{ $here }}">
            <p class="text-sm font-bold text-neutral-900"><i class="fa-solid fa-receipt text-[var(--accent)] mr-1.5"></i>Chi phí phát sinh cho lứa này</p>
            <div class="grid grid-cols-2 gap-3">
                <x-select name="category" label="Loại chi phí">
                    @foreach(config('cham.expense_categories') as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                </x-select>
                <x-input type="date" name="occurred_on" label="Ngày" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required />
            </div>
            <x-input type="text" inputmode="numeric" class="money-input" name="amount" label="Số tiền (đ)" icon="fa-money-bill" required />
            <x-input name="description" label="Mô tả" placeholder="VD: Tiền điện tháng 10" />
            <x-button icon="fa-check">Lưu chi phí</x-button>
        </form>

        <form x-show="panel === 'move'" x-cloak method="POST" action="{{ route('batches.move', $batch->id) }}" class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 space-y-3">
            @csrf
            <p class="text-sm font-bold text-neutral-900"><i class="fa-solid fa-location-arrow text-[var(--accent)] mr-1.5"></i>Chuyển sang khu vực khác</p>
            <x-select name="zone_id" label="Khu vực">
                <option value="">— Chưa phân khu —</option>
                @foreach($zones as $z)<option value="{{ $z->id }}" @selected($batch->zone_id === $z->id)>{{ $z->name }}</option>@endforeach
            </x-select>
            <x-button icon="fa-check">Lưu</x-button>
        </form>
    @endif

    @if($batch->note || $batch->source || $batch->gender || $batch->birth_date)
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm p-4 text-sm text-neutral-600 space-y-1">
            @if($batch->gender)<p><span class="text-neutral-400">Giới tính:</span> {{ $batch->gender === 'duc' ? 'Đực' : ($batch->gender === 'cai' ? 'Cái' : 'Không rõ') }}</p>@endif
            @if($batch->birth_date)<p><span class="text-neutral-400">Ngày sinh:</span> {{ $batch->birth_date->format('d/m/Y') }}</p>@endif
            <p><span class="text-neutral-400">Ngày nhập:</span> {{ $batch->started_on->format('d/m/Y') }}@if($batch->source) · {{ $batch->source }}@endif</p>
            @if($batch->note)<p class="whitespace-pre-line">{{ $batch->note }}</p>@endif
        </div>
    @endif

    @if($isOwner && $sales->isNotEmpty())
        <div>
            <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Các lần xuất bán</p>
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm divide-y divide-neutral-100">
                @foreach($sales as $sale)
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-neutral-900">{{ $sale->sold_on->format('d/m/Y') }} · {{ $sale->quantity }} con @if($sale->weight_kg) · {{ quantity($sale->weight_kg, 2) }} kg @endif</p>
                            <p class="text-xs text-neutral-500">{{ money($sale->unit_price) }}đ/{{ $sale->priceUnitLabel() }}@if($sale->buyer) · {{ $sale->buyer }}@endif</p>
                        </div>
                        <span class="text-sm font-bold text-emerald-700">{{ money($sale->total_amount) }}đ</span>
                        <form method="POST" action="{{ route('sales.destroy', $sale->id) }}" onsubmit="return confirm('Xoá đơn bán này? {{ $sale->quantity }} con sẽ được hoàn lại vào lứa.')">
                            @csrf @method('DELETE')<button class="text-neutral-300 hover:text-red-500 transition p-1"><i class="fa-solid fa-trash-can text-xs"></i></button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($isOwner && $expenses->isNotEmpty())
        <div>
            <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Chi phí phát sinh</p>
            <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm divide-y divide-neutral-100">
                @foreach($expenses as $ex)
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-neutral-900">{{ $ex->categoryLabel() }}@if($ex->description) · {{ $ex->description }}@endif</p>
                            <p class="text-xs text-neutral-500">{{ $ex->occurred_on->format('d/m/Y') }}</p>
                        </div>
                        <span class="text-sm font-bold text-neutral-800">−{{ money($ex->amount) }}đ</span>
                        <form method="POST" action="{{ route('expenses.destroy', $ex->id) }}" onsubmit="return confirm('Xoá khoản chi này?')">
                            @csrf @method('DELETE')<button class="text-neutral-300 hover:text-red-500 transition p-1"><i class="fa-solid fa-trash-can text-xs"></i></button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Dòng thời gian</p>
        <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm px-4">
            @forelse($events as $event)
                @include('events._row', ['event' => $event, 'showBatch' => false])
            @empty
                <p class="text-center text-sm text-neutral-400 py-8">Chưa có ghi nhận nào. Bấm “Cho ăn”, “Cân”… ở trên để bắt đầu.</p>
            @endforelse
        </div>
        <div class="mt-3">{{ $events->links() }}</div>
    </div>
</div>
@endsection
