@props(['type' => 'info', 'message' => null])
@php($alertClass = match ($type) { 'success' => 'rounded-xl border border-[#BBF7D0] bg-[#ECFDF5] p-4 text-[#166534]', 'error' => 'rounded-xl border border-[#FECACA] bg-[#FEF2F2] p-4 text-[#B91C1C]', default => 'rounded-xl border border-[#BFDBFE] bg-[#DBEAFE] p-4 text-[#1E40AF]' })
<div {{ $attributes->merge(['class' => $alertClass]) }} role="{{ $type === 'error' ? 'alert' : 'status' }}">{{ $message ?? $slot }}</div>
