<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employe extends Model
{
    protected $table = 'employe';
    protected $primaryKey = 'idEmploye';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = ['idEmploye', 'fonction', 'dateEmbauche', 'idPressing'];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'idEmploye', 'idUtilisateur');
    }

    public function pressingActif(): int
    {
        return (int) $this->idPressing;
    }

    public function pressing()
    {
        return $this->belongsTo(Pressing::class, 'idPressing');
    }
}
