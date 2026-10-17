<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarif extends Model
{
    public const FORMULE_LAVAGE = 'LAVAGE';
    public const FORMULE_REPASSAGE = 'REPASSAGE';
    public const FORMULE_LAVAGE_REPASSAGE = 'LAVAGE_REPASSAGE';
    public const FORMULES = [
        self::FORMULE_LAVAGE,
        self::FORMULE_REPASSAGE,
        self::FORMULE_LAVAGE_REPASSAGE,
    ];

    protected $table = 'tarif';
    protected $primaryKey = 'idTarif';
    public $timestamps = false;

    protected $fillable = ['idPressing', 'typeArticle', 'formule', 'unite', 'prix'];

    protected function casts(): array
    {
        return ['prix' => 'decimal:2'];
    }
}
