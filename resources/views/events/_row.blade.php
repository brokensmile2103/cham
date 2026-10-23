{{-- Dòng timeline của 1 event. Biến: $event, $showBatch (bool), $canDelete (bool) --}}
@php
    $meta = $event->meta();
    $isOwnerRow = auth()->user()?->isOwner();
    $deletable = $event->type !== 'ban' && ($isOwnerRow || ($event->created_by === auth()->id() && $event->created_at->gt(now()->subHours(2))));
@endphp
<div class="flex items-start gap-3 py-3 border-b border-neutral-100 last:border-0">
    <div class="w-9 h-9 rounded-xl {{ color_badge($meta['color']) }} flex items-center justify-center shrink-0 text-sm">
        <i class="fa-solid {{ $meta['icon'] }}"></i>
    </div>
    <div class="min-w-0 flex-1">
        <div class="text-sm font-semibold text-neutral-900">
            {{ $meta['label'] }}
            @if(($showBatch ?? true) && $event->batch)
                <a href="{{ route('batches.show', $event->batch_id) }}" class="font-medium text-[var(--accent-text)] hover:underline">
                    · {{ $event->batch->species?->emoji }} {{ $event->batch->name }}
                </a>
            @endif
        </div>
        @if($event->summary())
            <div class="text-xs text-neutral-600 mt-0.5">{{ $event->summary() }}</div>
        @endif
        @if($event->note)
            <div class="text-xs text-neutral-500 mt-0.5 italic">“{{ $event->note }}”</div>
        @endif
        <div class="text-xs text-neutral-400 mt-0.5">
            {{ $event->occurred_at->format('H:i') }}@if(! ($sameDay ?? false)) · {{ $event->occurred_at->format('d/m/Y') }}@endif
            @if($event->creator) · {{ $event->creator->name }}@endif
            @if(auth()->user()?->isOwner() && (float) $event->cost > 0) · <span class="text-neutral-500">chi phí {{ money($event->cost) }}đ</span>@endif
        </div>
    </div>
    @if($deletable)
        <form method="POST" action="{{ route('events.destroy', $event->id) }}" onsubmit="return confirm('Xoá ghi nhận này? Tồn kho và số lượng con sẽ được hoàn lại.')">
            @csrf @method('DELETE')
            <button class="text-neutral-300 hover:text-red-500 transition p-1" title="Xoá"><i class="fa-solid fa-trash-can text-xs"></i></button>
        </form>
    @endif
</div>
