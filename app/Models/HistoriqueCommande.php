<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoriqueCommande extends Model
{
    protected $table = 'historique_commande';
    protected $primaryKey = 'idHistorique';
    public $timestamps = false;

    protected $fillable = [
        'ancienStatut',
        'nouveauStatut',
        'dateChangement',
        'commentaire',
        'idCommande',
        'idEmploye',
    ];
}
