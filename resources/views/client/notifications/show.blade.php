@extends('layouts.app')

@section('title', $notification->titre.' — OceaPress')
@section('navigation')
    <a href="{{ route('client.commandes.index') }}" class="text-blue-700 hover:underline">Mes commandes</a>
    <a href="{{ route('client.notifications.index') }}" class="text-blue-700 hover:underline">Mes notifications</a>
@endsection

@section('content')
    <article class="mx-auto max-w-3xl space-y-4 py-5">
        <a href="{{ route('client.notifications.index') }}" class="text-blue-700 hover:underline">← Mes notifications</a>
        <section class="rounded-xl bg-white p-6 shadow">
            <h1 class="text-2xl font-bold">{{ $notification->titre }}</h1>
            <p class="mt-4 whitespace-pre-line">{{ $notification->message }}</p>
            <time class="mt-4 block text-sm text-slate-500">{{ $notification->dateEnvoi }}</time>
        </section>
    </article>
@endsection
