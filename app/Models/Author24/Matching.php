<?php

namespace App\Models\Author24;

use App\Scopes\ExternalServices\Author24Scope;
use Illuminate\Database\Eloquent\Model;

class Matching extends Model
{
    protected $connection = 'crm';
    protected $table      = 'external_matching';
    public    $timestamps = false;
    protected $casts      = [
        'matching' => 'collection'
    ];

    protected static function booted()
    {
        static::addGlobalScope(new Author24Scope);
    }

}
