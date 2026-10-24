<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Utilisateur extends Authenticatable
{
    use Notifiable;

    protected $table = 'utilisateur';
    protected $primaryKey = 'idUtilisateur';
    public $timestamps = false;

    protected $fillable = ['nom', 'prenom', 'email', 'telephone', 'motDePasse', 'doitChangerMotDePasse'];
    protected $hidden = ['motDePasse'];

    public function client()
    {
        return $this->hasOne(Client::class, 'idClient', 'idUtilisateur');
    }

    public function employe()
    {
        return $this->hasOne(Employe::class, 'idEmploye', 'idUtilisateur');
    }

    public function administrateur()
    {
        return $this->hasOne(Administrateur::class, 'idAdministrateur', 'idUtilisateur');
    }

    public function getAuthPasswordName(): string
    {
        return 'motDePasse';
    }

    protected function casts(): array
    {
        return ['motDePasse' => 'hashed', 'doitChangerMotDePasse' => 'boolean'];
    }
}
