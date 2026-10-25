<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    protected $table = 'photo';
    protected $primaryKey = 'idPhoto';
    public $timestamps = false;

    protected $fillable = ['urlPhoto', 'datePrise', 'type', 'idArticle'];

    public function article()
    {
        return $this->belongsTo(Article::class, 'idArticle');
    }
}
