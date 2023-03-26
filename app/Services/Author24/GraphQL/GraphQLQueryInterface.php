<?php

namespace App\Services\Author24\GraphQL;

use GraphQL\Query;

interface GraphQLQueryInterface
{
public function getQuery(): Query;
}
