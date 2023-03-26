<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $connection = 'crm';
    protected $table      = 'author24_prices';
    public    $timestamps = false;

}
