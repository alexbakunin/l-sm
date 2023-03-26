<?php

namespace App\Scopes\ExternalServices;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class Author24Scope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('source', '=', 1);
    }
}
