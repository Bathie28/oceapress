@props(['href' => null, 'type' => 'button'])
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'op-button-secondary']) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => 'op-button-secondary']) }}>{{ $slot }}</button>
@endif
