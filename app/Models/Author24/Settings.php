<?php

namespace App\Models\Author24;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table      = 'author24_settings';
    public    $timestamps = false;
    protected $connection = 'crm';
    protected $fillable   = ['account_id', 'data'];
    protected $casts      = [
        'data' => 'array'
    ];
}
