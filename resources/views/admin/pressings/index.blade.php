@extends('layouts.app')

@section('title', 'Pressings — OceaPress')

@section('content')
<div class="space-y-6 py-5">
    <header class="op-banner"><h1 class="text-3xl font-bold">Pressings</h1></header>
    <section class="op-card p-6">
        <ul class="divide-y divide-slate-200">
            @foreach ($pressings as $pressing)
                <li class="flex flex-wrap items-center justify-between gap-3 py-4">
                    <a href="{{ route('admin.pressings.tarifs', $pressing->idPressing) }}" class="font-semibold">{{ $pressing->nom }}</a>
                    <span class="text-sm text-slate-600">Pesée : {{ $pressing->pesee ? 'Oui' : 'Non' }}</span>
                    @php($nombreTarifsAffiches = $pressing->tarifsAffiches()->count())
                    <span class="op-badge">{{ $nombreTarifsAffiches }} {{ $nombreTarifsAffiches === 1 ? 'tarif' : 'tarifs' }}</span>
                </li>
            @endforeach
        </ul>
    </section>
</div>
@endsection
