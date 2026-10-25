<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PhotoController extends Controller
{
    public function show(Request $request, int $photo): BinaryFileResponse
    {
        $photo = Photo::query()->with('article.commande')->findOrFail($photo);
        $commande = $photo->article?->commande;
        abort_if($commande === null, 404);

        $utilisateur = $request->user();
        $estEmployeDuPressing = $utilisateur->employe !== null
            && (int) $utilisateur->employe->idPressing === (int) $commande->idPressing;
        $estClientDeLaCommande = $utilisateur->client !== null
            && (int) $utilisateur->client->idClient === (int) $commande->idClient;
        abort_unless($estEmployeDuPressing || $estClientDeLaCommande, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($photo->urlPhoto), 404);
        $chemin = $disk->path($photo->urlPhoto);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($chemin);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png'], true), 404);

        return response()->file($chemin, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
