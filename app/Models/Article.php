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
        'prixUnitaire',
        'idCommande',
    ];

    protected function casts(): array
    {
        return ['prixUnitaire' => 'decimal:2'];
    }
}
