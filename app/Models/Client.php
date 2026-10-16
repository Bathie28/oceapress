<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'client';
    protected $primaryKey = 'idClient';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = ['idClient', 'dateInscription'];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'idClient', 'idUtilisateur');
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class, 'idClient');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'idClient');
    }
}
