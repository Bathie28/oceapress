<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $table = 'paiement';
    protected $primaryKey = 'idPaiement';
    public $timestamps = false;

    protected $fillable = [
        'referencePaiement',
        'montant',
        'datePaiement',
        'methode',
        'statut',
        'idFacture',
    ];

    public function facture()
    {
        return $this->belongsTo(Facture::class, 'idFacture');
    }
}
