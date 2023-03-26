<?php

namespace App\Services\Author24\GraphQL\Queries\SubQueries;

use App\Services\Author24\GraphQL\GraphQLQuery;
use App\Services\Author24\GraphQL\GraphQLQueryInterface;
use GraphQL\Query;

class GetWorkTypeSubgroupInfoQuery extends GraphQLQuery implements GraphQLQueryInterface
{

    public function __construct()
    {
        $this->query = (new Query('subgroup'))->setSelectionSet([
            'id', 'name'
        ]);
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
