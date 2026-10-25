@extends('layouts.app')

@section('title', $commande->referenceCommande.' — OceaPress')
@section('navigation')
    <a href="{{ route('client.commandes.index') }}" class="text-blue-700 hover:underline">Mes commandes</a>
@endsection

@section('content')
    <div class="mx-auto max-w-6xl space-y-6 py-5">
        <a href="{{ route('client.commandes.index') }}" class="text-blue-700 hover:underline">← Mes commandes</a>
        <section class="rounded-xl bg-white p-6 shadow">
            <h1 class="text-2xl font-bold">{{ $commande->referenceCommande }}</h1>
            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Statut</dt><dd><x-status-badge :status="$commande->statut" :label="str_replace('_', ' ', $commande->statut)" /></dd></div>
                <div><dt class="text-sm text-slate-500">Date</dt><dd>{{ $commande->dateCreation }}</dd></div>
                <div><dt class="text-sm text-slate-500">Montant</dt><dd>{{ number_format((float) $commande->montantTotal, 0, '.', ' ') }}</dd></div>
            </dl>
        </section>

        <section class="rounded-xl bg-white p-6 shadow">
            <h2 class="mb-4 text-xl font-semibold">Facture</h2>
            @if (session('paiement_resultat'))
                <p class="mb-4 rounded-lg bg-emerald-50 p-3 text-emerald-800" role="status">{{ session('paiement_resultat') }}</p>
            @endif
            @if ($errors->has('paiement') || $errors->has('methode'))
                <p class="mb-4 rounded-lg bg-red-50 p-3 text-red-700" role="alert">{{ $errors->first('paiement') ?: $errors->first('methode') }}</p>
            @endif
            @if ($commande->facture)
                <dl class="grid gap-4 sm:grid-cols-3">
                    <div><dt class="text-sm text-slate-500">Numéro</dt><dd>{{ $commande->facture->numeroFacture }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Montant</dt><dd>{{ number_format((float) $commande->facture->montantTotal, 0, '.', ' ') }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Statut</dt><dd><x-status-badge :status="$commande->facture->statut" :label="str_replace('_', ' ', $commande->facture->statut)" /></dd></div>
                </dl>
                @if ($commande->facture->statut === 'EN_ATTENTE')
                    <form method="POST" action="{{ route('client.factures.paiement', $commande->facture->idFacture) }}" class="mt-5 flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <label for="methode" class="mb-1 block text-sm font-medium">Méthode de paiement</label>
                            <select id="methode" name="methode" required class="rounded-lg border border-slate-300 px-3 py-2">
                                <option value="" selected disabled>Choisir une méthode</option>
                                <option value="WAVE">Wave</option>
                                <option value="ORANGE_MONEY">Orange Money</option>
                            </select>
                        </div>
                        <button class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Lancer le paiement</button>
                    </form>
                @endif
            @else
                <p>Facture non encore émise</p>
            @endif
        </section>

        <section class="rounded-xl bg-white p-6 shadow">
            <h2 class="mb-4 text-xl font-semibold">Articles</h2>
            <ul class="divide-y divide-slate-100">
                @forelse ($commande->articles as $article)
                    <li class="flex flex-col justify-between gap-4 py-3 sm:flex-row">
                        <span>{{ $article->typeArticle }}@if ($article->couleur) · {{ $article->couleur }}@endif</span>
                        <span>Quantité : {{ $article->quantite }}</span>
                        @foreach ($article->photos as $photo)<a href="{{ route('photos.show', $photo->idPhoto) }}"><img class="mt-2 h-28 rounded object-cover" src="{{ route('photos.show', $photo->idPhoto) }}" alt="Photo de restitution de {{ $article->typeArticle }}"></a>@endforeach
                    </li>
                @empty
                    <li class="py-3 text-slate-500">Aucun article.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-xl bg-white p-6 shadow">
            <h2 class="mb-4 text-xl font-semibold">Votre QR Code</h2>
            @if ($qrCodeSvg !== null)
                <button type="button" id="agrandir-qr" aria-label="Agrandir le QR Code" class="inline-flex cursor-zoom-in rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-700">
                    {!! $qrCodeSvg !!}
                </button>
                <p class="mt-3 text-sm text-slate-600">Commande {{ $commande->referenceCommande }}</p>
                <dialog id="qr-plein-ecran" aria-label="QR Code agrandi" class="h-screen w-screen max-h-none max-w-none bg-slate-950/95 p-6 text-white backdrop:bg-slate-950/90">
                    <div class="flex h-full flex-col items-center justify-center gap-6">
                        <form method="dialog" class="absolute right-5 top-5"><button class="rounded-lg bg-white px-4 py-2 font-semibold text-slate-900">Fermer</button></form>
                        <div id="qr-plein-ecran-image" class="rounded-xl bg-white p-4"></div>
                        <p>Commande {{ $commande->referenceCommande }}</p>
                    </div>
                </dialog>
                <script>
                    (() => {
                        const button = document.getElementById('agrandir-qr');
                        const dialog = document.getElementById('qr-plein-ecran');
                        const target = document.getElementById('qr-plein-ecran-image');
                        const image = button.querySelector('svg').cloneNode(true);
                        image.setAttribute('width', 'min(80vw, 80vh)');
                        image.setAttribute('height', 'min(80vw, 80vh)');
                        image.classList.add('max-h-[80vh]', 'max-w-[80vw]');
                        target.append(image);
                        button.addEventListener('click', () => dialog.showModal());
                    })();
                </script>
            @else
                <p class="text-slate-600">QR Code indisponible, contactez le pressing</p>
            @endif
        </section>

        <section class="rounded-xl bg-white p-6 shadow">
            <h2 class="mb-4 text-xl font-semibold">Historique</h2>
            <ol class="space-y-3">
                @forelse ($commande->historiques as $historique)
                    <li class="border-l-2 border-blue-600 pl-3"><strong>{{ str_replace('_', ' ', $historique->nouveauStatut) }}</strong><span class="ml-2 text-sm text-slate-500">{{ $historique->dateChangement }}</span>@if ($historique->commentaire)<p>{{ $historique->commentaire }}</p>@endif</li>
                @empty
                    <li class="text-slate-500">Aucun historique.</li>
                @endforelse
            </ol>
        </section>
    </div>
@endsection
