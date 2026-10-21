<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pressing extends Model
{
    public const STATUT_ACTIF = 'ACTIF';
    public const STATUT_INACTIF = 'INACTIF';
    public const STATUT_FERME = 'FERME';

    protected $table = 'pressing';
    protected $primaryKey = 'idPressing';
    public $timestamps = false;

    protected $fillable = ['nom', 'adresse', 'telephone', 'email', 'dateCreation', 'statut', 'pesee'];

    protected function casts(): array
    {
        return ['pesee' => 'boolean'];
    }

    public function employes()
    {
        return $this->hasMany(Employe::class, 'idPressing');
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class, 'idPressing');
    }

    public function tarifs()
    {
        return $this->hasMany(Tarif::class, 'idPressing');
    }

    public function typesPourFormule(string $formule): array
    {
        return $this->tarifs()
            ->where('unite', 'PIECE')
            ->where('formule', $formule)
            ->whereNotNull('typeArticle')
            ->distinct()
            ->orderBy('typeArticle')
            ->pluck('typeArticle')
            ->all();
    }

    public function tarifsAffiches()
    {
        $tarifs = $this->relationLoaded('tarifs')
            ? $this->getRelation('tarifs')
            : $this->tarifs()->get();

        return ($this->pesee ? $tarifs : $tarifs->where('unite', 'PIECE'))->values();
    }
}
