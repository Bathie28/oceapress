<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anomalie extends Model
{
    protected $table = 'anomalie';
    protected $primaryKey = 'idAnomalie';
    public $timestamps = false;

    protected $fillable = ['type', 'description', 'idArticle', 'dateSignalement'];

    public function article()
    {
        return $this->belongsTo(Article::class, 'idArticle');
    }
}
