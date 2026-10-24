@extends('layouts.app')

@section('title', 'Mes notifications — OceaPress')
@section('navigation')
    <a href="{{ route('client.commandes.index') }}" class="text-blue-700 hover:underline">Mes commandes</a>
    <a href="{{ route('client.notifications.index') }}" class="text-blue-700 hover:underline">Mes notifications</a>
@endsection

@section('content')
    <div class="mx-auto max-w-4xl space-y-5 py-5">
        <h1 class="text-3xl font-bold">Mes notifications</h1>
        <div class="space-y-3">
            @forelse ($notifications as $notification)
                <a href="{{ route('client.notifications.show', $notification->idNotification) }}" class="block rounded-xl bg-white p-5 shadow hover:ring-2 hover:ring-blue-200">
                    <div class="flex items-start justify-between gap-4"><h2 class="font-semibold">{{ $notification->titre }}</h2>@unless ($notification->lu)<span class="text-sm font-medium text-blue-700">Non lue</span>@endunless</div>
                    <p class="mt-2 text-slate-700">{{ $notification->message }}</p>
                    <time class="mt-2 block text-sm text-slate-500">{{ $notification->dateEnvoi }}</time>
                </a>
            @empty
                <p class="rounded-xl bg-white p-6 text-slate-600">Aucune notification.</p>
            @endforelse
        </div>
    </div>
@endsection
