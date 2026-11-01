@props(['type' => 'submit', 'href' => null])
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'op-button-primary bg-[#2563EB] text-white hover:bg-[#1E40AF]']) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => 'op-button-primary bg-[#2563EB] text-white hover:bg-[#1E40AF]']) }}>{{ $slot }}</button>
@endif
