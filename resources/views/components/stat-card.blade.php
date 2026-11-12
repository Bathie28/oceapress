@props(['title', 'value', 'icon', 'tint' => 'blue', 'name' => null])
@php
    $teintes = [
        'blue' => 'bg-op-tint-blue text-op-icon-blue',
        'green' => 'bg-op-tint-green text-op-icon-green',
        'orange' => 'bg-op-tint-orange text-op-icon-orange',
        'cyan' => 'bg-op-tint-cyan text-op-icon-cyan',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'op-card flex h-full items-center justify-between gap-4 p-5']) }} @if ($name) data-stat="{{ $name }}" @endif>
    <div class="min-w-0">
        <p class="text-sm font-medium text-op-muted">{{ $title }}</p>
        <p class="mt-2 text-3xl font-bold text-op-ink" data-stat-value>{{ $value }}</p>
        @if ($slot->isNotEmpty())
            <div class="mt-2 text-sm text-op-muted">{{ $slot }}</div>
        @endif
    </div>
    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl {{ $teintes[$tint] ?? $teintes['blue'] }}" data-stat-icon><x-icon :name="$icon" class="h-6 w-6 shrink-0" /></span>
</div>
