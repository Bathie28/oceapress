<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrCode extends Model
{
    protected $table = 'qr_code';
    protected $primaryKey = 'idQRCode';
    public $timestamps = false;

    protected $fillable = ['token', 'dateCreation', 'actif', 'idCommande'];
}
