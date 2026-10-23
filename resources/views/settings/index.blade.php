@extends('layouts.app')
@section('title', 'Cài đặt · Chăm')
@section('page-title', 'Cài đặt')
@section('content')
@php $hex = ['emerald' => '#059669', 'amber' => '#d97706', 'orange' => '#ea580c', 'blue' => '#2563eb', 'rose' => '#e11d48', 'violet' => '#7c3aed']; @endphp
<div class="p-4 pb-28 md:p-8 max-w-xl mx-auto space-y-5">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[var(--accent-light)] text-[var(--accent-text)] flex items-center justify-center shrink-0"><i class="fa-solid fa-gear"></i></div>
        <h2 class="text-lg font-bold text-neutral-900">Cài đặt trại</h2>
    </div>

    <form method="POST" action="{{ route('settings.farm') }}" class="bg-white rounded-2xl p-4 sm:p-5 border border-neutral-200 shadow-sm space-y-4">
        @csrf @method('PUT')
        <p class="text-sm font-bold text-neutral-900">Thông tin trại</p>
        <x-input name="name" label="Tên trại / hộ nuôi" icon="fa-house-chimney" value="{{ old('name', $farm->name) }}" required maxlength="100" />
        <x-input name="address" label="Địa chỉ" icon="fa-location-dot" value="{{ old('address', $farm->address) }}" maxlength="255" />
        <x-button icon="fa-floppy-disk">Lưu</x-button>
    </form>

    <form method="POST" action="{{ route('settings.appearance') }}" class="bg-white rounded-2xl p-4 sm:p-5 border border-neutral-200 shadow-sm space-y-4">
        @csrf @method('PUT')
        <p class="text-sm font-bold text-neutral-900">Giao diện</p>
        <div>
            <p class="text-sm font-medium text-neutral-700 mb-2">Màu chủ đạo</p>
            <div class="flex flex-wrap gap-3">
                @foreach(config('cham.accent_colors') as $k => $label)
                    <label class="cursor-pointer text-center">
                        <input type="radio" name="accent_color" value="{{ $k }}" class="peer sr-only" @checked(old('accent_color', $farm->accent_color ?: 'emerald') === $k)>
                        <span class="block w-11 h-11 rounded-full ring-2 ring-transparent ring-offset-2 peer-checked:ring-neutral-900 transition" style="background: {{ $hex[$k] ?? '#059669' }}"></span>
                        <span class="block text-[0.65rem] text-neutral-500 mt-1">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div>
            <p class="text-sm font-medium text-neutral-700 mb-2">Cỡ chữ</p>
            <div class="grid grid-cols-3 gap-1 bg-neutral-100 rounded-xl p-1">
                @foreach(['sm' => 'Nhỏ', 'md' => 'Vừa', 'lg' => 'Lớn'] as $k => $l)
                    <label class="cursor-pointer"><input type="radio" name="font_scale" value="{{ $k }}" class="peer sr-only" @checked(old('font_scale', $farm->font_scale ?: 'md') === $k)><span class="block text-center text-sm font-semibold py-2 rounded-lg text-neutral-500 peer-checked:bg-white peer-checked:text-neutral-900 peer-checked:shadow-sm transition">{{ $l }}</span></label>
                @endforeach
            </div>
        </div>
        <x-button icon="fa-palette">Lưu giao diện</x-button>
    </form>

    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-neutral-200 shadow-sm space-y-3">
        <p class="text-sm font-bold text-neutral-900">Dữ liệu của bạn</p>
        <p class="text-xs text-neutral-500">Tải toàn bộ dữ liệu của trại (vật nuôi, nhật ký, kho, bán hàng, chi phí) về máy bất cứ lúc nào.</p>
        <a href="{{ route('settings.data-export') }}" class="w-full inline-flex items-center justify-center gap-2 rounded-xl py-3 font-semibold text-base bg-neutral-100 hover:bg-neutral-200 text-neutral-800 transition"><i class="fa-solid fa-download"></i>Tải dữ liệu trại</a>
    </div>

    <div class="grid grid-cols-2 gap-2">
        <a href="{{ route('farms.index') }}" class="flex items-center justify-center gap-2 px-3 py-3 rounded-xl bg-white border border-neutral-200 text-neutral-700 text-sm font-semibold hover:bg-neutral-50"><i class="fa-solid fa-warehouse"></i>Các trại của tôi</a>
        <a href="{{ route('profile.edit') }}" class="flex items-center justify-center gap-2 px-3 py-3 rounded-xl bg-white border border-neutral-200 text-neutral-700 text-sm font-semibold hover:bg-neutral-50"><i class="fa-solid fa-user"></i>Tài khoản</a>
    </div>
</div>
@endsection
