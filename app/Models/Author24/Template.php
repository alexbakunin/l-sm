<?php

namespace App\Models\Author24;

use App\Scopes\ExternalServices\Author24Scope;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $connection = 'crm';
    protected $table      = 'external_estimate_templates';
    public    $timestamps = false;
    protected static function booted()
    {
//        static::addGlobalScope(new Author24Scope);
    }
}
