<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Photo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class EnregistrerPhoto
{
    private const TAILLE_MAX_OCTETS = 5 * 1024 * 1024;

    public function valider(UploadedFile $fichier): string
    {
        if (! $fichier->isValid()) {
            throw new RuntimeException('Le fichier photo reçu est invalide.');
        }

        if (($fichier->getSize() ?? 0) > self::TAILLE_MAX_OCTETS) {
            throw new RuntimeException('Chaque photo doit faire 5 Mo maximum.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($fichier->getRealPath());
        if (! in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new RuntimeException('Les photos doivent être au format JPEG ou PNG.');
        }

        return $mime;
    }

    public function executer(UploadedFile $fichier, Article $article): Photo
    {
        $mime = $this->valider($fichier);
        $extension = $mime === 'image/jpeg' ? 'jpg' : 'png';
        $nomAleatoire = bin2hex(random_bytes(24)).'.'.$extension;
        $chemin = $fichier->storeAs('photos', $nomAleatoire, 'local');

        if ($chemin === false) {
            throw new RuntimeException('La photo n’a pas pu être enregistrée.');
        }

        try {
            return Photo::create([
                'urlPhoto' => $chemin,
                'datePrise' => now(),
                'type' => 'restitution',
                'idArticle' => $article->idArticle,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($chemin);
            throw $exception;
        }
    }
}
