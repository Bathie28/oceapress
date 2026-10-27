@extends('layouts.app')

@section('title', 'Pressings — OceaPress')

@section('content')
<div class="space-y-6 py-5">
    <header class="op-banner flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-3xl font-bold">Pressings</h1>
        <a href="{{ route('admin.pressings.create') }}" class="rounded-xl bg-blue-600 px-4 py-2 font-semibold text-white">Ajouter un pressing</a>
    </header>
    <section class="op-card p-6">
        <ul class="divide-y divide-slate-200">
            @foreach ($pressings as $pressing)
                <li class="flex flex-wrap items-center justify-between gap-3 py-4">
                    <a href="{{ route('admin.pressings.tarifs', $pressing->idPressing) }}" class="font-semibold">{{ $pressing->nom }}</a>
                    <span class="text-sm text-slate-600">Pesée : {{ $pressing->pesee ? 'Oui' : 'Non' }}</span>
                    @php($nombreTarifsAffiches = $pressing->tarifsAffiches()->count())
                    <span class="op-badge">{{ $nombreTarifsAffiches }} {{ $nombreTarifsAffiches === 1 ? 'tarif' : 'tarifs' }}</span>
                    <a href="{{ route('admin.pressings.edit', $pressing->idPressing) }}" class="font-semibold text-blue-700">Modifier</a>
                </li>
            @endforeach
        </ul>
    </section>
</div>
@endsection
