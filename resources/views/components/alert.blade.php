@props(['type' => 'info', 'message' => null])
@php($alertClass = match ($type) { 'success' => 'op-alert-success', 'error' => 'op-alert-error', default => 'op-alert-info' })
<div {{ $attributes->merge(['class' => $alertClass]) }} role="{{ $type === 'error' ? 'alert' : 'status' }}">{{ $message ?? $slot }}</div>
