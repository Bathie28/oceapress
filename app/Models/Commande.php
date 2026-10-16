<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_EN_TRAITEMENT = 'EN_TRAITEMENT';
    public const STATUT_PRETE = 'PRETE';
    public const STATUT_RECUPEREE = 'RECUPEREE';
    public const STATUT_ANNULEE = 'ANNULEE';

    protected $table = 'commande';
    protected $primaryKey = 'idCommande';
    public $timestamps = false;

    protected $fillable = [
        'referenceCommande',
        'dateCreation',
        'datePrevue',
        'dateRecuperation',
        'statut',
        'montantTotal',
        'idPressing',
        'idClient',
    ];

    public function pressing()
    {
        return $this->belongsTo(Pressing::class, 'idPressing');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'idClient');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'idCommande');
    }

    public function qrCodes()
    {
        return $this->hasMany(QrCode::class, 'idCommande');
    }

    public function qrCodeActif()
    {
        return $this->hasOne(QrCode::class, 'idCommande')->where('actif', true);
    }

    public function facture()
    {
        return $this->hasOne(Facture::class, 'idCommande');
    }

    public function historiques()
    {
        return $this->hasMany(HistoriqueCommande::class, 'idCommande');
    }

    public function restitution()
    {
        return $this->hasOne(Restitution::class, 'idCommande');
    }
}
