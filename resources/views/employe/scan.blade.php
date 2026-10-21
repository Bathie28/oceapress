@extends('layouts.app')

@section('title', 'Scanner un QR code — OceaPress')
@section('navigation')
    <a href="{{ route('employe.accueil') }}" class="text-blue-700 hover:underline">Accueil employé</a>
    <a href="{{ route('employe.commandes.index') }}" class="text-blue-700 hover:underline">Commandes</a>
@endsection

@section('content')
<div class="mx-auto max-w-2xl space-y-6 py-5">
    <h1 class="text-3xl font-bold">Scanner un QR code</h1>
    <p>Scannez le QR code d’une commande ou saisissez son code manuellement.</p>
    @if ($errors->has('token'))
        <p class="rounded-lg bg-red-50 p-4 text-red-700" role="alert">{{ $errors->first('token') }}</p>
    @endif
    <div id="qrcode-reader" class="max-w-md overflow-hidden rounded-xl bg-white p-3 shadow" aria-label="Lecteur de QR code"></div>
    <p id="camera-error" class="rounded-lg bg-amber-50 p-4 text-amber-900" role="status" hidden>Caméra indisponible : saisissez le code</p>

    <form id="scan-form" method="POST" action="{{ route('employe.scan.store') }}" class="rounded-xl bg-white p-6 shadow">
        @csrf
        <input id="token" type="hidden" name="token" value="{{ old('token') }}">
        <div>
            <label for="token-manuel" class="mb-2 block font-medium">Saisie manuelle du code</label>
            <input id="token-manuel" name="token-manuel" type="text" maxlength="255" value="{{ old('token') }}" autocomplete="off" class="w-full rounded border border-slate-300 p-3">
        </div>
        <button type="submit" class="rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white">Rechercher la commande</button>
    </form>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(() => {
    const form = document.getElementById('scan-form');
    const token = document.getElementById('token');
    const manualToken = document.getElementById('token-manuel');
    const cameraError = document.getElementById('camera-error');
    const showCameraError = () => { cameraError.hidden = false; };
    let submitted = false;

    form.addEventListener('submit', () => {
        token.value = manualToken.value.trim();
    });

    const isLocalhost = window.location.hostname === 'localhost';
    if (!window.isSecureContext && !isLocalhost) {
        showCameraError();
        return;
    }

    try {
        const scanner = new Html5Qrcode('qrcode-reader');
        scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            decodedToken => {
                if (submitted) return;
                submitted = true;
                token.value = decodedToken;
                form.submit();
            },
            () => {},
        ).catch(showCameraError);
    } catch (error) {
        showCameraError();
    }
})();
</script>
@endsection
