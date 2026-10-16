<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    protected $table = 'facture';
    protected $primaryKey = 'idFacture';
    public $timestamps = false;

    protected $fillable = [
        'numeroFacture',
        'dateEmission',
        'montantTotal',
        'statut',
        'idCommande',
    ];
}
