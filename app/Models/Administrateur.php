<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Administrateur extends Model
{
    protected $table = 'administrateur';
    protected $primaryKey = 'idAdministrateur';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    public function pressings()
    {
        return $this->belongsToMany(
            Pressing::class,
            'gestion_pressing',
            'idAdministrateur',
            'idPressing'
        );
    }
}
