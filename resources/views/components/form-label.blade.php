@props(['for' => null])
<label @if ($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => 'block mb-1 text-sm font-medium text-gray-700']) }}>{{ $slot }}</label>
