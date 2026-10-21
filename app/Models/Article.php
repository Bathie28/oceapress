<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'article';
    protected $primaryKey = 'idArticle';
    public $timestamps = false;

    protected $fillable = [
        'typeArticle',
        'description',
        'couleur',
        'etatInitial',
        'etatFinal',
        'quantite',
        'prixUnitaire',
        'idCommande',
    ];

    protected function casts(): array
    {
        return ['quantite' => 'integer', 'prixUnitaire' => 'decimal:0'];
    }
}
