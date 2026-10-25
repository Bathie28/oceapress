@props(['type' => 'submit', 'href' => null])
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'op-button-primary']) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => 'op-button-primary']) }}>{{ $slot }}</button>
@endif
