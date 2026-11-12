@props(['title', 'id' => null])
<section {{ $attributes->merge(['class' => 'op-card p-6']) }} @if ($id) aria-labelledby="{{ $id }}" @endif>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 @if ($id) id="{{ $id }}" @endif class="text-lg font-semibold text-op-ink">{{ $title }}</h2>
        @isset($action)
            <div class="text-sm font-semibold" data-section-action>{{ $action }}</div>
        @endisset
    </div>
    <div class="mt-4">{{ $slot }}</div>
</section>
