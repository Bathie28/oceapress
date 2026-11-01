@props(['href' => null, 'type' => 'button'])
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'op-button-secondary border border-[#CBD5E1] bg-white text-[#0F172A]']) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => 'op-button-secondary border border-[#CBD5E1] bg-white text-[#0F172A]']) }}>{{ $slot }}</button>
@endif
