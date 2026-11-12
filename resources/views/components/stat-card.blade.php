@props(['title', 'value', 'icon', 'tint' => 'blue', 'name' => null, 'subtitle' => null, 'label' => 'Total', 'progress' => null, 'caption' => null])
@php
    $teintes = [
        'blue' => 'bg-op-tint-blue text-op-icon-blue',
        'green' => 'bg-op-tint-green text-op-icon-green',
        'orange' => 'bg-op-tint-orange text-op-icon-orange',
        'cyan' => 'bg-op-tint-cyan text-op-icon-cyan',
    ];
    $pourcentage = $progress === null ? null : max(0, min(100, (int) round($progress)));
@endphp
<div {{ $attributes->merge(['class' => 'op-card flex h-full flex-col p-5']) }} @if ($name) data-stat="{{ $name }}" @endif>
    <div class="flex items-center gap-4">
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl {{ $teintes[$tint] ?? $teintes['blue'] }}" data-stat-icon><x-icon :name="$icon" class="h-6 w-6 shrink-0" /></span>
        <div class="min-w-0">
            <p class="text-base font-semibold text-op-ink">{{ $title }}</p>
            @if ($subtitle)
                <p class="text-sm text-op-muted">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    <div class="mt-auto flex items-end justify-between gap-4 pt-5">
        <p class="text-sm text-op-muted">{{ $label }}</p>
        <p class="text-3xl font-bold leading-none text-op-ink" data-stat-value>{{ $value }}</p>
    </div>

    @if ($pourcentage !== null)
        <div class="mt-4 h-2 overflow-hidden rounded-full bg-op-page" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pourcentage }}" aria-label="{{ $caption ?? $title }}" data-stat-progress="{{ $pourcentage }}">
            <div class="h-2 rounded-full bg-op-accent" style="width: {{ $pourcentage }}%"></div>
        </div>
    @endif
    @if ($caption)
        <p class="mt-2 text-sm text-op-muted">{{ $caption }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-2 text-sm text-op-muted">{{ $slot }}</div>
    @endif
</div>
