<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notification';
    protected $primaryKey = 'idNotification';
    public $timestamps = false;

    protected $fillable = [
        'titre',
        'message',
        'type',
        'canal',
        'dateEnvoi',
        'lu',
        'idClient',
    ];
}
