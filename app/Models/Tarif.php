<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarif extends Model
{
    protected $table = 'tarif';
    protected $primaryKey = 'idTarif';
    public $timestamps = false;

    protected $fillable = ['idPressing', 'typeArticle', 'formule', 'unite', 'prix'];

    protected function casts(): array
    {
        return ['prix' => 'decimal:2'];
    }
}
