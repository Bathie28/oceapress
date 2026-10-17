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

    protected $fillable = ['nom', 'adresse', 'telephone', 'email', 'dateCreation', 'statut'];

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
}
