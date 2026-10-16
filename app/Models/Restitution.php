<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restitution extends Model
{
    protected $table = 'restitution';
    protected $primaryKey = 'idRestitution';
    public $timestamps = false;

    protected $fillable = [
        'dateRestitution',
        'statut',
        'observation',
        'idCommande',
        'idEmploye',
    ];
}
