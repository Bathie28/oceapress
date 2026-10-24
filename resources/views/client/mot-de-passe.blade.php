<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Changer le mot de passe</title></head><body>
<main><h1>Changer le mot de passe</h1>
@if ($errors->any())<ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<form method="post" action="{{ route('client.mot-de-passe.update') }}">@csrf
<label>Nouveau mot de passe <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
<label>Confirmer le mot de passe <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
<button type="submit">Enregistrer</button></form>
<form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Déconnexion</button></form>
</main></body></html>
