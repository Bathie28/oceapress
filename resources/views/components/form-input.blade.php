@props(['type' => 'text'])
<input type="{{ $type }}" {{ $attributes->merge(['class' => 'block w-full rounded-xl border border-gray-300 px-3 py-2 focus:border-blue-600 focus:outline-none']) }}>
